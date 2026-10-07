<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Models\ApprovalFreez;
use App\Models\JobDivisi;
use App\Services\Approval\ApprovalWorkflowService;
use App\Services\Notifikasi\NotifikasiServis;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FreezeController extends Controller
{
    public function __construct(
        protected ApprovalWorkflowService $approvalWorkflowService,
        protected NotifikasiServis $notifikasiServis
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_unless(
            Auth::user()->canAny([
                'berkas-bermasalah/freeze/list',
                'berkas-bermasalah/freeze/setuju',
                'berkas-bermasalah/freeze/tolak',
            ]),
            403
        );
        $items = ApprovalFreez::orderBy("id", "desc")->paginate(12);
        return view("pages.Freeze.index", [
            'items' => $items
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('job/divisi/freeze'), 403);

        $validasi = Validator::make($request->all(), [
            "job_id" => "required",
            "keterangan" => "required",
        ], [
            "required" => ":attribute harus diisi",
        ]);

        if ($validasi->fails()) {
            return redirect()->back()->with("error", $validasi->errors()->first())->withInput();
        }

        $jobDivisi = JobDivisi::find($request->job_id);

        if ($jobDivisi?->status !== "Akad") {
            // dd($jobDivisi, $jobDivisi?->status);
            return redirect()->back()->with("error", "Job tidak dapat di freeze");
        }

        DB::beginTransaction();

        try {


            $freezeData = ApprovalFreez::create([
                "job_divisi" => $request->job_id,
                "keterangan" => $request->keterangan,
                "created_by" => Auth::user()->id
            ]);
            $approverIds = $this->approvalWorkflowService->snapshot('freeze', $freezeData);
            foreach ($approverIds as $approverId) {
                $this->notifikasiServis->create(
                    $approverId,
                    'Persetujuan Freeze',
                    Auth::user()->name . ' mengajukan freeze berkas.',
                    route('berkas-bermasalah.freeze.show', $freezeData->id),
                    $jobDivisi->id
                );
            }

            DB::commit();

            return redirect()->back()->with('success', 'Berhasil simpan data');
            // dd($freezeData);
        } catch (Exception $th) {
            DB::rollBack();
            report($th);
            return redirect()->back()->with('error', 'Permintaan freeze gagal disimpan.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = ApprovalFreez::with("jobDivisi")->findOrFail($id);
        abort_unless(
            Auth::user()->can('berkas-bermasalah/freeze/detail')
                || $this->approvalWorkflowService->canApprove('freeze', $data, (int) Auth::id())
                || Auth::user()->can('berkas-bermasalah/freeze/buka'),
            403
        );
        $jobDivisi = $data->jobDivisi;

        $jobFormOrder = $jobDivisi->formOrder->groupBy("kategori");
        $dataPendukung = explode(",", $jobDivisi->jenisAkad->jenis_data);

        $fileAkad = $jobDivisi->fileJob->where("tipe", "foto_akad")->first();
        $fileSertifikat = $jobDivisi->fileJob->where("tipe", "sertifikat");
        $isApprover = $this->approvalWorkflowService->canApprove('freeze', $data, (int) Auth::id());


        return view("pages.Freeze.detail", [
            'freeze' => $data,
            'isApprover' => $isApprover,
            "dataPendukung" => $dataPendukung,
            "jobDivisi" => $jobDivisi,
            "jobFormOrder" => $jobFormOrder,
            "fileAkad" => $fileAkad,
            "fileSertifikat" => $fileSertifikat
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all());
        DB::beginTransaction();

        try {

            $data = ApprovalFreez::query()->lockForUpdate()->findOrFail($id);
            $jobDivisi = $data->jobDivisi;
            if ($request->status === 'Buka Freeze') {
                abort_unless(Auth::user()->can('berkas-bermasalah/freeze/buka'), 403);
                abort_unless($data->status === 'Disetujui', 409, 'Permintaan freeze belum disetujui atau sudah dibuka.');
            } else {
                abort_unless(
                    in_array($request->status, ['0', '1', 0, 1], true),
                    422,
                    'Keputusan approval freeze tidak valid.'
                );
                abort_unless($data->status === 'menunggu persetujuan', 409, 'Permintaan freeze sudah diproses.');
                $permission = (string) $request->status === '1'
                    ? 'berkas-bermasalah/freeze/setuju'
                    : 'berkas-bermasalah/freeze/tolak';
                abort_unless(Auth::user()->can($permission), 403);
                abort_unless(
                    $this->approvalWorkflowService->canApprove('freeze', $data, (int) Auth::id()),
                    403,
                    'Anda bukan approver yang ditetapkan untuk permintaan freeze ini.'
                );
            }

            $status = "Disetujui";

            if (!$request->status) {
                $status = "Ditolak";
            } else if ($request->status == "Buka Freeze") {
                $data->end_date = now();
                $data->penambahan_sla = $request->tambah_sla;

                $newTanggalEstimasiSelesai = Carbon::parse($jobDivisi->tanggal_estimasi_selesai)->addDays((int)$request->tambah_sla);
                $newTanggalEstimasiSelesaiEksternal = Carbon::parse($jobDivisi->tanggal_estimasi_selesai_eksternal)->addDays((int)$request->tambah_sla);

                $jobDivisi->tanggal_estimasi_selesai = $newTanggalEstimasiSelesai;
                $jobDivisi->tanggal_estimasi_selesai_eksternal = $newTanggalEstimasiSelesaiEksternal;
                $jobDivisi->deleted_at = null;
                $status = "Buka Freeze";
                $jobDivisi->save();
            } else {
                $jobDivisi->delete();
                $data->start_date = now();
            }


            $data->approval_1 = Auth::user()->id;
            $data->status = $status;
            $data->save();

            // dd($data, "Data");
            DB::commit();

            return redirect()->route("berkas-bermasalah.freeze.index")->with("success", "Freeze Berhasil $status");
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            DB::rollBack();
            throw $exception;
        } catch (Exception $th) {
            DB::rollBack();
            report($th);
            return redirect()->back()->with('error', 'Keputusan freeze gagal disimpan.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
