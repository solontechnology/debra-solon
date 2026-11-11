<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JobDivisi;
use App\Models\JobDivisiFinance;
use App\Models\StatusJobOps;
use App\Services\Approval\ApprovalWorkflowService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FinanceJobDivisiController extends Controller
{
    public function __construct(protected ApprovalWorkflowService $approvalWorkflowService) {}

    // Ekspresi SQL per kolom. Statis (bukan input user), input cuma dipakai milih key.
    private const SORT_COLUMNS = [
        'tanggal' => [
            'type' => 'date',
            'expr' => '{t}.tanggal',
        ],
        'item_proses' => [
            'type' => 'text',
            'expr' => '(select f.nama from {f} f where f.id = {t}.job_divisi_form_order_id limit 1)',
        ],
        'total' => [
            'type' => 'number',
            'expr' => '{t}.total',
        ],
        'tipe' => [
            'type' => 'text',
            'expr' => '{t}.tipe',
        ],
        'peruntukan' => [
            'type' => 'text',
            'expr' => '{t}.peruntukan',
        ],
        'parent' => [
            'type' => 'text',
            'expr' => '(select j.kode from {j} j where j.id = {t}.job_divisi_id limit 1)',
        ],
        'status' => [
            'type' => 'text',
            'expr' => '{t}.status',
        ],
        'keterangan' => [
            'type' => 'text',
            'expr' => '{t}.keterangan',
        ],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_unless(
            auth()->user()->canAny(['finance/list', 'finance/approve']),
            403
        );
        // whitelist
        $sortInput = $request->query('sort');
        $sort = is_string($sortInput) && isset(self::SORT_COLUMNS[$sortInput]) ? $sortInput : null;
        $direction = in_array($request->query('direction'), ['asc', 'desc', 'empty'], true)
            ? $request->query('direction')
            : 'asc';

        $query = JobDivisiFinance::orderBy("id", 'desc')
            ->with(
                "jobDivisi.userOps",
                "jobDivisi.jenisAkad",
                'jobDivisi.debitur',
                "jobDivisi.objek.desa.kecamatan.kota",
                "formOrder.pembatalanItemDetail.pembatalanItem",
                "user",
                "pembuat"
            )
            ->whereHas("jobDivisi")
            ->where("tipe", $request->type);

        $this->applySort($query, $sort, $direction);
        $items = $query->get();
        $items->each(function (JobDivisiFinance $item) {
            $item->can_approve = $item->status === 'Menunggu persetujuan finance'
                && auth()->user()->can('finance/approve')
                && $this->approvalWorkflowService->canApprove('finance', $item, (int) auth()->id());
        });


        $type = $request->type;

        $jobDivisi = JobDivisi::query()->get();

        return view("pages.Finance.index", [
            "items" => $items,
            "type" => $type,
            "jobDivisi" => $jobDivisi,
            "sort" => $sort,
            "direction" => $direction,
        ]);
    }

    private function applySort($query, ?string $sort, string $direction)
{
    $col = self::SORT_COLUMNS[$sort] ?? null;

    // gak ada sort aktif: urutan default (id desc) tetap jalan
    if (!$col) {
        return $query;
    }

    // nama tabel diambil dari model, jadi gak perlu nebak
    $t = (new JobDivisiFinance)->getTable();
    $expr = str_replace(
        ['{t}', '{f}', '{j}'],
        [
            $t,
            (new \App\Models\JobDivisiFormOrder)->getTable(),
            (new JobDivisi)->getTable(),
        ],
        $col['expr']
    );

    $isEmpty = $col['type'] === 'text'
        ? "NULLIF(TRIM($expr), '') IS NULL"
        : "($expr) IS NULL";

    $query->reorder();

    if ($direction === 'empty') {
        $query->orderByRaw("$isEmpty DESC")->orderByRaw("($expr) ASC");
    } else {
        $query->orderByRaw("$isEmpty ASC")
            ->orderByRaw("($expr) " . ($direction === 'desc' ? 'DESC' : 'ASC'));
    }

    return $query->orderByDesc("$t.id");
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        $item = JobDivisiFinance::with("formOrder")->find($id);

        if (!$item) {
            return redirect()->back()->with("error", "Data tidak ditemukan");
        }
        $isApprovalRequest = $item->status === 'Menunggu persetujuan finance';
        if ($isApprovalRequest) {
            $request->validate(['status' => ['required', 'in:Disetujui,Tolak']]);
            abort_unless(auth()->user()->can('finance/approve'), 403);
            abort_unless(
                $this->approvalWorkflowService->canApprove('finance', $item, (int) auth()->id()),
                403,
                'Anda bukan approver yang ditetapkan untuk pengajuan finance ini.'
            );
        }

        DB::beginTransaction();

        try {
            $item = JobDivisiFinance::with("formOrder")->lockForUpdate()->findOrFail($id);
            if ($isApprovalRequest) {
                abort_unless(
                    $item->status === 'Menunggu persetujuan finance',
                    409,
                    'Pengajuan finance sudah diproses.'
                );
                abort_unless(in_array($request->status, ['Disetujui', 'Tolak'], true), 422);
                abort_unless(auth()->user()->can('finance/approve'), 403);
                abort_unless(
                    $this->approvalWorkflowService->canApprove('finance', $item, (int) auth()->id()),
                    403,
                    'Anda bukan approver yang ditetapkan untuk pengajuan finance ini.'
                );
            }

            $formOrder = $item->formOrder;


            $item->update([
                "total" => str_replace(".", "", $request->harga_proses),
                "user_id" => Auth::user()->id,
                "status" => $request->status
            ]);
            // dd($item->toArray());

            if ($request->status === "Disetujui") {
                $statusOps = StatusJobOps::query()
                    ->where("job_divisi_form_order_id", $item->job_divisi_form_order_id)
                    ->orderBy("id", "desc")
                    ->first();

                $statusOpsNew = $statusOps->replicate();
                $statusOpsNew->status = "Pengerjaan";
                $statusOpsNew->keterangan = "Disetujui " . Auth::user()->name;
                $statusOpsNew->save();

                // dd($statusOpsNew->toArray());

                $formOrder->update([
                    "harga_modal" => $item->total,
                    "harga_proses" => $item->total,
                ]);
            }

            // dd($item->toArray(), "commit");
            DB::commit();
            return redirect()->back()->with("success", "Data berhasil diubah");
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            DB::rollBack();
            throw $exception;
        } catch (Exception $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->with("error", "Gagal melakukan perubahan, Coba beberapa saat lagi");
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
