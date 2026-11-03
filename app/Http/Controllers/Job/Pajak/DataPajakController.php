<?php

namespace App\Http\Controllers\Job\Pajak;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Job\UpdateStatusJobDivisiController;
use App\Models\JobDivisi;
use App\Models\JobDivisiFinance;
use App\Models\JobDivisiFormOrder;
use App\Models\StatusJobOps;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DataPajakController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // private const FILTER_KEYS = [
    //     'parent',
    //     'proses',
    //     'nomor_objek',
    //     'nama_debitur',
    //     'nama_bank',
    //     'pembayaran_min',
    //     'pembayaran_max',
    //     'kode_billing',
    //     'status_akad',
    //     'status',
    // ];
    public function index(Request $request)
    {
        $tipe = "pajak";

        $filters     = \App\Services\Job\PajakFilter::resolve($request);
        $countFilter = \App\Services\Job\PajakFilter::count($filters);

        $prosesOptions = JobDivisiFormOrder::query()
            ->whereNotIn('status', ['rejected', 'Dibatalkan'])
            ->where('kategori', $tipe)
            ->whereHas('jobDivisi')
            ->whereNotNull('nama')
            ->select('nama')->distinct()->orderBy('nama')->pluck('nama');

        $statusAkadOptions = JobDivisi::query()
            ->whereHas('formOrder', fn($q) => $q->where('kategori', $tipe)->whereNotIn('status', ['rejected', 'Dibatalkan']))
            ->whereNotNull('status')
            ->select('status')->distinct()->orderBy('status')->pluck('status');

        $statusOptions = StatusJobOps::query()
            ->whereNotNull('status')
            ->select('status')->distinct()->orderBy('status')->pluck('status')
            ->prepend('Belum dikerjakan')->unique()->values();

        $bankOptions = \App\Models\Bank::where('is_active', 1)->orderBy('nama')->pluck('nama');

        $items = JobDivisiFormOrder::with([
            'jobDivisi.jenisAkad',
            'jobDivisi.objek',
            'jobDivisi.debitur',
            'jobDivisi.listBank',
            'jobDivisi.finance',
            'jobDivisi.fileJob.user',
            'statusJobOps.createdBy',
            'statusJobOps.user',
            'nomorPpat',
        ])
            ->orderBy('id', 'desc')
            ->whereNotIn('status', ['rejected', 'Dibatalkan'])
            ->where('kategori', $tipe)
            ->whereHas('jobDivisi');

        \App\Services\Job\PajakFilter::apply($items, $filters);

        $items = $items->paginate(10)->withQueryString()
            ->appends(['apply_filter' => null, 'reset_filter' => null]);

        $users = User::orderBy('name')->get();

        return view('pages.Job.Pajak.index', compact(
            'items',
            'countFilter',
            'filters',
            'prosesOptions',
            'statusAkadOptions',
            'statusOptions',
            'bankOptions',
            'users'
        ));
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
        // dd($request->all());
        $request->validate([
            "nominal_pembayaran" => "required",
            "kode_billing" => "required|string|max:255",
        ]);

        DB::beginTransaction();

        try {
            $job_divisi_form_order = JobDivisiFormOrder::find($request->form_id);
            $item = StatusJobOps::create([
                "created_by" => Auth::id(),
                "job_divisi_form_order_id" => $request->form_id,
                "status" => "Selesai",
                "user_id" => $request->ops,
                "keterangan" => $request->keterangan
            ]);

            $finance = JobDivisiFinance::create(
                [
                    "job_divisi_id" => $job_divisi_form_order->job_divisi_id,
                    "tanggal" => Carbon::parse($request->tanggal)->format("Y-m-d"),
                    "total" => str_replace(".", "", $request->nominal_pembayaran),
                    "kode_billing" => $request->kode_billing,
                    "keterangan" => "Pembayaran pajak ",
                    "metode_pembayaran" => "Pembayaran pajak",
                    "created_at" => now(),
                    "updated_at" => now(),
                    "user_id" => Auth::user()->id,
                    "created_by" => Auth::user()->id,
                    "tipe" => "out",
                    "peruntukan" => "pajak",
                    "status" => "Disetujui",
                    "job_divisi_form_order_id" => $request->form_id
                ]
            );

            $job_divisi_form_order->update([
                "harga_jual" => str_replace(".", "", $request->nominal_pembayaran),
                "harga_modal" => $job_divisi_form_order->harga_jual,
            ]);

            // $updateStatus = new UpdateStatusJobDivisiController();
            // $formOrder = JobDivisiFormOrder::with("jobDivisi")->find($request->form_id);
            // $updateSelesai  = $updateStatus->updateSelesai($formOrder->jobDivisi->id);


            DB::commit();

            return redirect()->back()->with("success", "Berhasil simpan data");
        } catch (Exception $th) {
            DB::rollBack();

            return redirect()->back()->with("error", $th->getMessage());
        }
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
