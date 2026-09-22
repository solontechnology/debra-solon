<?php

namespace App\Http\Controllers\Laporan\HistoryJobDivisi\Home;

use App\Http\Controllers\Controller;
use App\Models\JobDivisi;
use App\Models\Pekerjaan;
use App\Models\User;
use App\Services\FileStoreServis;
use App\Services\Job\JobDivisiIndexServis;
use App\Services\MasterData\BankService;
use App\Services\MasterData\BrokerService;
use App\Services\MasterData\DeveloperService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

class homeHistoryJobDivisiController extends Controller
{

    public function __construct(
        protected FileStoreServis $fileStoreServis,
        protected JobDivisiIndexServis $jobDivisiIndexServis,
        protected BankService $bankService,
        protected DeveloperService $developerService,
        protected BrokerService $brokerService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view("pages.Laporan.HistoryJobDivisi.Home.homeLaporanJobDivisi", $this->jobDivisiIndexServis->execute($request));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $jobDivisi = JobDivisi::with(
            "perubahanHarga",
            "jenisAkad",
            "pembuat",
            'userOps',
            'listBroker',
            'badanHukum',
            'developer.developer',
            "objek.desa.kecamatan.kota.provinsi",
            "formOrder.statusJobOps",
            "formOrder.statusJobOps.createdBy",
            "formOrder.statusJobOps.user",
            "finance.invoice",
            "finance.user",
            "finance.formOrder",
            "finance.pnbp.user",
            "debitur",
            "penjual",
            "pembeli",
            "listBank.bank",
            "listBank.headLegalBank",
            "listBank.legalBank",
            "listBank.headMarketing",
            "listBank.marketing",
            "listDebitur",
            "listBadanUsahaDebitur",
            "listBadanUsahaPembeli",
            "listBadanUsahaPenjual",
            "fileJob.user",
            "freeze",
            "invoice.detail"
        )->find($id);

        $approvedFinance = $jobDivisi->finance->filter(function ($finance) {

            $status = strtolower((string) $finance->status);

            return in_array($status, ['disetujui', 'approved']);
        });

        $filteredFinance = $approvedFinance
            ->when(request()->filled('tipe'), function ($query) {
                return $query->where('tipe', request()->tipe);
            })
            ->when(request()->filled('peruntukan'), function ($query) {
                return $query->where('peruntukan', request()->peruntukan);
            });

        $totalPemasukan = (float) $approvedFinance
            ->where('tipe', 'in')
            ->sum('total');

        $totalPengeluaran = (float) $approvedFinance
            ->where('tipe', 'out')
            ->sum('total');

        $totalBiaya = (float) $jobDivisi->formOrder->sum('harga_jual') - $jobDivisi->formOrder->sum('diskon');

        $profit = $totalPemasukan - $totalPengeluaran;

        $piutang = $totalBiaya - $totalPemasukan;

        $approveFormOrder = false;
        if ($jobDivisi->perubahanHarga?->status === "menunggu persetujuan") {
            $approveFormOrder = true;
        }

        if (!$jobDivisi) {
            Session::flash('error', 'Data Tidak Ditemukan');
            return to_route('job.divisi.index');
        }

        $dataPendukungObjek = $jobDivisi->jenisAkad->data_pendukung ? explode(",", $jobDivisi->jenisAkad->data_pendukung) : null;

        $dataPendukung = explode(",", $jobDivisi->jenisAkad->jenis_data);

        $roles = Cache::remember('master_roles', 86400, fn() => Role::orderBy("name", "asc")->get());
        $user = Cache::remember('master_users', 86400, fn() => User::orderBy("name", "asc")->get());
        $masterPekerjaan = Cache::remember('master_pekerjaan', 86400, fn() => Pekerjaan::orderBy("nama", "asc")->get());

        $jobFormOrder = $jobDivisi->formOrder->groupBy("kategori");

        $myRoles = Auth::user()->roles->first()->id;

        // $bank = Cache::remember('master_bank', 86400, function () {
        //     return Bank::orderBy("nama")->with("kepalaLegal", "legal", "kepalaMarketing", "marketing")->get();
        // });

        // $developer = Cache::remember('master_developer', 86400, fn() => Developer::with("marketing", "legal")->get());
        // $broker = Cache::remember('master_broker', 86400, fn() => Broker::with("marketing")->get());
        $bank = $this->bankService->getForForm();
        $developer = $this->developerService->getForForm();
        $broker = $this->brokerService->getForForm();

        $fileAkad = $jobDivisi->fileJob->where("tipe", "foto akad")->first();
        $fileSertifikat = $jobDivisi->fileJob->where("tipe", "sertifikat")->first();
        $fileCovernot = $jobDivisi->fileJob->where("tipe", "covernot")->first();

        $countFilter = collect([
            request()->tipe,
            request()->peruntukan,
        ])->filter()->count();

        $peruntukanList = $jobDivisi->finance
            ->pluck('peruntukan')
            ->filter()
            ->unique()
            ->values();

        return view("pages.Job.Divisi.detail", [
            "jobDivisi" => $jobDivisi,
            "roles" => $roles,
            "user" => $user,
            "jobFormOrder" => $jobFormOrder,
            "masterPekerjaan" => $masterPekerjaan,
            "myRoles" => $myRoles,
            "dataPendukung" => $dataPendukung,
            "bank" => $bank,
            // "desa" => $desa,
            "developer" => $developer,
            "dataPendukungObjek" => $dataPendukungObjek,
            "broker" => $broker,
            "fileAkad" => $fileAkad,
            "fileSertifikat" => $fileSertifikat,
            "fileCovernot" => $fileCovernot,
            "approveFormOrder" => $approveFormOrder,
            "approvedFinance" => $approvedFinance,
            "totalPemasukan" => $totalPemasukan,
            "totalPengeluaran" => $totalPengeluaran,
            "totalBiaya" => $totalBiaya,
            "profit" => $profit,
            "piutang" => $piutang,
            "countFilter" => $countFilter,
            "peruntukanList" => $peruntukanList,
            "filteredFinance" => $filteredFinance,
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function countData($id)
    {
        $jobDivisi = JobDivisi::withCount("formOrder", "finance")->find($id);

        // $jobDivisi->finance = $jobDivisi->finance_count;

        return response()->json($jobDivisi);
    }

}
