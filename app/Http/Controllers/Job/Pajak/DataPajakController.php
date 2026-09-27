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

    public function index(Request $request)
    {
        $tipe = "pajak";

        $parent = $request->parent;
        $proses = $request->proses;
        $nomorObjek = $request->nomor_objek;
        $namaDebitur = $request->nama_debitur;
        $namaBank = $request->nama_bank;
        $statusAkad = $request->status_akad;
        $status = $request->status;

        /*
    |--------------------------------------------------------------------------
    | Jumlah filter aktif
    |--------------------------------------------------------------------------
    */

        $countFilter = collect([
            'parent' => $parent,
            'proses' => $proses,
            'nomor_objek' => $nomorObjek,
            'nama_debitur' => $namaDebitur,
            'nama_bank' => $namaBank,
            'status_akad' => $statusAkad,
            'status' => $status,
        ])
            ->filter(fn($value) => filled($value))
            ->count();


        /*
    |--------------------------------------------------------------------------
    | Option Proses
    |--------------------------------------------------------------------------
    */

        $prosesOptions = JobDivisiFormOrder::query()
            ->whereNotIn('status', ['rejected', 'Dibatalkan'])
            ->where('kategori', $tipe)
            ->whereHas('jobDivisi')
            ->select('nama')
            ->whereNotNull('nama')
            ->distinct()
            ->orderBy('nama')
            ->pluck('nama');


        /*
    |--------------------------------------------------------------------------
    | Option Status Akad
    |--------------------------------------------------------------------------
    */

        $statusAkadOptions = JobDivisi::query()
            ->whereHas('formOrder', function ($query) use ($tipe) {
                $query
                    ->where('kategori', $tipe)
                    ->whereNotIn('status', ['rejected', 'Dibatalkan']);
            })
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');


        /*
    |--------------------------------------------------------------------------
    | Option Status Pajak
    |--------------------------------------------------------------------------
    */

        $statusOptions = StatusJobOps::query()
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->prepend('Belum dikerjakan')
            ->unique()
            ->values();


        /*
    |--------------------------------------------------------------------------
    | Data Pajak
    |--------------------------------------------------------------------------
    */

        $items = JobDivisiFormOrder::with([
            'jobDivisi.jenisAkad',
            'jobDivisi.objek',
            'jobDivisi.debitur',
            'jobDivisi.listBank',
            'jobDivisi.finance',
            'statusJobOps.createdBy',
            'statusJobOps.user',
            'nomorPpat',
        ])
            ->orderBy('id', 'desc')

            ->whereNotIn('status', ['rejected', 'Dibatalkan'])

            ->where('kategori', $tipe)

            ->whereHas('jobDivisi')


            /*
        |--------------------------------------------------------------------------
        | Filter Parent
        |--------------------------------------------------------------------------
        */

            ->when($parent, function ($query) use ($parent) {

                $query->whereHas('jobDivisi', function ($query) use ($parent) {

                    $query->where(
                        'kode',
                        'LIKE',
                        "%{$parent}%"
                    );
                });
            })


            /*
        |--------------------------------------------------------------------------
        | Filter Proses
        |--------------------------------------------------------------------------
        */

            ->when($proses, function ($query) use ($proses) {

                $query->where(
                    'nama',
                    $proses
                );
            })


            /*
        |--------------------------------------------------------------------------
        | Filter Nomor Objek
        |--------------------------------------------------------------------------
        */

            ->when($nomorObjek, function ($query) use ($nomorObjek) {

                $query->whereHas('jobDivisi.objek', function ($query) use ($nomorObjek) {

                    $query->where(
                        'no_sertifikat',
                        'LIKE',
                        "%{$nomorObjek}%"
                    );
                });
            })


            /*
        |--------------------------------------------------------------------------
        | Filter Nama Debitur
        |--------------------------------------------------------------------------
        */

            ->when($namaDebitur, function ($query) use ($namaDebitur) {

                $query->whereHas('jobDivisi.debitur', function ($query) use ($namaDebitur) {

                    $query->where(
                        'nama',
                        'LIKE',
                        "%{$namaDebitur}%"
                    );
                });
            })


            /*
        |--------------------------------------------------------------------------
        | Filter Nama Bank
        |--------------------------------------------------------------------------
        */

            ->when($namaBank, function ($query) use ($namaBank) {

                $query->whereHas('jobDivisi.listBank', function ($query) use ($namaBank) {

                    $query->where(
                        'nama_bank',
                        'LIKE',
                        "%{$namaBank}%"
                    );
                });
            })


            /*
        |--------------------------------------------------------------------------
        | Filter Status Akad
        |--------------------------------------------------------------------------
        */

            ->when($statusAkad, function ($query) use ($statusAkad) {

                $query->whereHas('jobDivisi', function ($query) use ($statusAkad) {

                    $query->where(
                        'status',
                        $statusAkad
                    );
                });
            })


            /*
        |--------------------------------------------------------------------------
        | Filter Status Pajak
        |--------------------------------------------------------------------------
        */

            ->when($status, function ($query) use ($status) {

                if ($status === 'Belum dikerjakan') {

                    return $query->doesntHave('statusJobOps');
                }

                return $query->whereHas('statusJobOps', function ($query) use ($status) {

                    $query->where('status', $status);
                });
            })


            ->paginate(10)
            ->withQueryString();


        /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    */

        $users = User::orderBy('name')->get();


        return view('pages.Job.Pajak.index', compact(
            'items',
            'countFilter',
            'prosesOptions',
            'statusAkadOptions',
            'statusOptions',
            'users'
        ));
    }
    // public function index()
    // {
    //     $tipe = "pajak";
    //     $items = JobDivisiFormOrder::with([
    //         "jobDivisi.jenisAkad",
    //         "jobDivisi.objek",
    //         "jobDivisi.debitur",
    //         "jobDivisi.listBank",
    //         "jobDivisi.finance",
    //         "statusJobOps.createdBy",
    //         "statusJobOps.user",
    //         "nomorPpat"
    //     ])
    //         ->orderBy("id", "desc")
    //         ->whereNotIn("status", ["rejected", "Dibatalkan"])
    //         // ->where("status", "!=", "pending")
    //         ->where('kategori', $tipe) // ✅ legalisasi, pajak, akta ['notaris', 'ppat', 'legalisasi']
    //         ->whereHas("jobDivisi")
    //         ->paginate(10);

    //     $userOps = User::orderBy("name", "asc")->get()->map(function ($user) {
    //         return [
    //             'value' => (string)$user->id,
    //             'label' => $user->name,
    //         ];
    //     });


    //     return view("pages.Job.Pajak.index", [
    //         "items" => $items,
    //     ]);
    // }

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
