<?php

namespace App\Http\Controllers\Job\Bermasalah;

use Exception;
use Carbon\Carbon;
use App\Models\JobPending;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
//   use App\Models\JobPending;

class PendingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
  

// Ekspresi SQL per kolom. Statis (bukan input user), input cuma dipakai milih key.
private const SORT_COLUMNS = [
    'kode' => [
        'type' => 'text',
        'expr' => '(select j.kode from job_divisis j where j.id = {t}.job_divisi_id limit 1)',
    ],
    'start_date' => [
        'type' => 'date',
        'expr' => '{t}.start_date',
    ],
    'end_date' => [
        'type' => 'date',
        'expr' => '{t}.end_date',
    ],
    'penambahan_sla' => [
        'type' => 'number',
        'expr' => '{t}.penambahan_sla',
    ],
];

public function index(Request $request)
{
    // whitelist
    $sortInput = $request->query('sort');
    $sort = is_string($sortInput) && isset(self::SORT_COLUMNS[$sortInput]) ? $sortInput : null;
    $direction = in_array($request->query('direction'), ['asc', 'desc', 'empty'], true)
        ? $request->query('direction')
        : 'asc';

    $query = JobPending::orderBy("id", "desc")->with("jobDivisi");
    $this->applySort($query, $sort, $direction);

    $items = $query->paginate(12)->withQueryString();

    return view("pages.job-pending.index", compact("items", "sort", "direction"));
}

private function applySort($query, ?string $sort, string $direction)
{
    $col = self::SORT_COLUMNS[$sort] ?? null;

    // gak ada sort aktif: urutan default (id desc) tetap jalan
    if (!$col) {
        return $query;
    }

    // nama tabel diambil dari model, jadi gak perlu nebak
    $t = (new JobPending)->getTable();
    $expr = str_replace('{t}', $t, $col['expr']);

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
        $pending = JobPending::with("jobDivisi")->findOrFail($id);

        $jobDivisi = $pending->jobDivisi;

        $jobFormOrder = $jobDivisi->formOrder->groupBy("kategori");
        $dataPendukung = explode(",", $jobDivisi->jenisAkad->jenis_data);

        $fileAkad = $jobDivisi->fileJob->where("tipe", "foto_akad")->first();
        $fileSertifikat = $jobDivisi->fileJob->where("tipe", "sertifikat");

        return view("pages.job-pending.detail", [
            "pending" => $pending,
            "jobDivisi" => $pending->jobDivisi,
            "jobFormOrder" => $jobFormOrder,
            "dataPendukung" => $dataPendukung,
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

        DB::beginTransaction();

        try {
            $data = JobPending::with("jobDivisi")->findOrFail($id);
            $jobDivisi = $data->jobDivisi;

            $data->end_date = now();
            $data->penambahan_sla = $request->tambah_sla;
            $data->save();

            $newTanggalEstimasiSelesai = Carbon::parse($jobDivisi->tanggal_estimasi_selesai)->addDays((int)$request->tambah_sla);
            $newTanggalEstimasiSelesaiEksternal = Carbon::parse($jobDivisi->tanggal_estimasi_selesai_eksternal)->addDays((int)$request->tambah_sla);

            $jobDivisi->tanggal_estimasi_selesai = $newTanggalEstimasiSelesai;
            $jobDivisi->tanggal_estimasi_selesai_eksternal = $newTanggalEstimasiSelesaiEksternal;
            $jobDivisi->is_pending = 0;
            $jobDivisi->status = "Akad";
            $jobDivisi->deleted_at = null;

            $jobDivisi->save();

            DB::commit();

            return redirect()->back()->with("success", "Pending Berhasil Dibuka");
        } catch (Exception $th) {
            return redirect()->back()->with("error", "Gagal buka pending, terjadi kesalahan server");
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
