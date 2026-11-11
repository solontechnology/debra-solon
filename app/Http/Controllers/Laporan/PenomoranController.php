<?php

namespace App\Http\Controllers\Laporan;

use App\Exports\PenomoranExport;
use App\Http\Controllers\Controller;
use App\Models\JobDivisiFormOrder;
use App\Models\MasterDataFormOrder;
use App\Models\MasterDataFormOrderDetail;
use App\Models\NomorPpat;
use App\Models\NotarisRekanan;
use App\Models\Pekerjaan;
use App\Models\PenomoranSetting;
use App\Services\Akta\InputNomorServis;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
// use App\Models\NomorPpat;
// use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PenomoranController extends Controller
{

    // ID job divisi lewat form order. Dipakai ulang di beberapa kolom.
    private const JOB_ID = '(select fo.job_divisi_id from {fo} fo
    where fo.id = {t}.job_divisi_form_order_id limit 1)';

    // Ekspresi SQL per kolom. Statis (bukan input user), input cuma dipakai milih key.
    private const SORT_COLUMNS = [
        'parent' => [
            'type' => 'text',
            'expr' => '(select j.kode from {j} j where j.id = ' . self::JOB_ID . ' limit 1)',
        ],
        // nomor dianggap angka (sama kayak sort di BundleController), tie-breaker teks ada di applySort
        'nomor' => [
            'type' => 'number',
            'expr' => 'CAST({t}.nomor AS UNSIGNED)',
        ],
        // sama kayak view: kalau form_order_id ada pakai nama pekerjaan, kalau gak pakai nama form order
        'proses' => [
            'type' => 'text',
            'expr' => 'CASE WHEN {t}.form_order_id IS NOT NULL AND {t}.form_order_id <> 0
            THEN (select p.nama from {pk} p where p.id = {t}.form_order_id limit 1)
            ELSE (select fo.nama from {fo} fo where fo.id = {t}.job_divisi_form_order_id limit 1) END',
        ],
        'notaris_rekanan' => [
            'type' => 'text',
            'expr' => '(select n.nama from {nr} n where n.id = {t}.notaris_rekanan_id limit 1)',
        ],
        'tanggal' => [
            'type' => 'date',
            'expr' => '{t}.tanggal',
        ],
        // prioritas ngikutin view: Debitur, Pembeli, Penjual, lalu fallback nama notaris pengambil
        'nama_debitur' => [
            'type' => 'text',
            'expr' => "COALESCE(
            NULLIF((select min(d.nama) from {d} d where d.job_divisi_id = " . self::JOB_ID . "), ''),
            NULLIF((select min(pb.nama) from {pb} pb where pb.job_divisi_id = " . self::JOB_ID . "), ''),
            NULLIF((select min(pj.nama) from {pj} pj where pj.job_divisi_id = " . self::JOB_ID . "), ''),
            NULLIF({t}.nama_debitur_notaris_pengambil, '')
        )",
        ],
        'nomor_objek' => [
            'type' => 'text',
            'expr' => '(select min(o.no_sertifikat) from {o} o where o.job_divisi_id = ' . self::JOB_ID . ')',
        ],
    ];

    public function __construct(protected InputNomorServis $inputNomorServis) {}

    public function index(Request $request, $kategori = null)
    {
        $filters = $this->validateDateFilters($request);

        $masterPekerjaan = Pekerjaan::query()
            ->when($kategori, fn($query) => $query->where('kategori', $kategori))
            ->orderBy('nama')
            ->get();
        $penomoranSetting = $kategori
            ? PenomoranSetting::query()->where('kategori', $kategori)->first()
            : null;

        // whitelist
        $sortInput = $request->query('sort');
        $sort = is_string($sortInput) && isset(self::SORT_COLUMNS[$sortInput]) ? $sortInput : null;
        $direction = in_array($request->query('direction'), ['asc', 'desc', 'empty'], true)
            ? $request->query('direction')
            : 'asc';

        $query = NomorPpat::with([
            'formOrder.jobDivisi.debitur',
            'formOrder.objek',
            'notarisRekanan',
        ])
            ->when($kategori, fn($query) => $query->where('kategori', $kategori))
            ->when(($filters['filter_type'] ?? null) === 'month', function ($query) use ($filters) {
                $query->whereMonth('tanggal', $filters['month'])
                    ->whereYear('tanggal', $filters['year']);
            })
            ->when(($filters['filter_type'] ?? null) === 'range', function ($query) use ($filters) {
                $query->whereDate('tanggal', '>=', $filters['start_date'])
                    ->whereDate('tanggal', '<=', $filters['end_date']);
            })
            ->orderBy('id', 'desc');

        $this->applySort($query, $sort, $direction);

        $items = $query->paginate(12)->appends($request->query());

        $notarisRekanan = NotarisRekanan::query()->with('kota')->orderBy('nama')->get();

        return view('pages.Laporan.nomor-notaris.index', compact(
            'items',
            'kategori',
            'masterPekerjaan',
            'notarisRekanan',
            'penomoranSetting',
            'filters',
            'sort',
            'direction'
        ));
    }

    private function applySort($query, ?string $sort, string $direction)
    {
        $col = self::SORT_COLUMNS[$sort] ?? null;

        // gak ada sort aktif: urutan default (id desc) tetap jalan
        if (!$col) {
            return $query;
        }

        $t = (new NomorPpat)->getTable();
        $expr = str_replace(
            ['{t}', '{fo}', '{j}', '{pk}', '{nr}', '{d}', '{pb}', '{pj}', '{o}'],
            [
                $t,
                (new JobDivisiFormOrder)->getTable(),
                (new \App\Models\JobDivisi)->getTable(),
                (new Pekerjaan)->getTable(),
                (new NotarisRekanan)->getTable(),
                (new \App\Models\Debitur)->getTable(),
                (new \App\Models\Pembeli)->getTable(),
                (new \App\Models\Penjual)->getTable(),
                (new \App\Models\JobDivisiObjek)->getTable(),
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

        // nomor: kalau angkanya sama (atau bukan angka), urutin teks aslinya
        if ($sort === 'nomor') {
            $query->orderBy("$t.nomor", $direction === 'desc' ? 'desc' : 'asc');
        }

        return $query->orderByDesc("$t.id");
    }

    public function inputNomorRekanan(Request $request)
    {

        $validated = $request->validate([
            "kategori" => ['required', \Illuminate\Validation\Rule::in(array_keys(PenomoranSetting::kategori()))],
            "group_proses" => [
                "required",
                "integer",
                \Illuminate\Validation\Rule::exists('pekerjaans', 'id')
                    ->where('kategori', $request->input('kategori')),
            ],
            "tanggal_nomor" => "required|date",
            "objek_notaris_pengambil" => "required",
            "nama_debitur_notaris_pengambil" => "required",
            "nomor" => ['nullable', 'string', 'max:255'],
            "notaris_rekanan_id" => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('notaris_rekanans', 'id')->whereNull('deleted_at'),
            ],
        ]);
        if ($validated['group_proses']) {
            $processCategory = Pekerjaan::query()
                ->whereKey($validated['group_proses'])
                ->value('kategori');
            if ($processCategory !== $validated['kategori']) {
                return back()->withInput()->withErrors([
                    'group_proses' => 'Nama proses harus sesuai dengan kategori penomoran yang dipilih.',
                ]);
            }
        }

        try {
            $kategori = $request->kategori;
            $tanggal_nomor = $request->tanggal_nomor;
            $nomor = $this->inputNomorServis->resolveForSystem(
                $kategori,
                $tanggal_nomor,
                $validated['nomor'] ?? null
            );
            $formData = [
                'user_id' => Auth::user()->id,
                "nomor" => $nomor,
                "rekanan" => 0,
                "notaris_rekanan_id" => $validated['notaris_rekanan_id'],
                "objek_notaris_pengambil" => $request->objek_notaris_pengambil,
                "nama_debitur_notaris_pengambil" => $request->nama_debitur_notaris_pengambil,
                "tanggal" => Carbon::parse($request->tanggal_nomor),
                "kategori" => $request->kategori,
                "form_order_id" => $request->group_proses,
                "job_divisi_form_order_id" => 0
            ];

            NomorPpat::create($formData);
            return redirect()->back()->with("success", "Berhasil simpan nomor");
        } catch (Exception $th) {

            return redirect()->back()->withInput()->with("error", "Gagal simpan nomor: " . $th->getMessage());
        }
    }

    public function export($kategori = null)
    {
        return Excel::download(
            new PenomoranExport($kategori),
            'laporan-penomoran-' . $kategori . '.xlsx'
        );
    }
    // [tipe, field di $row]
    private const PEMAKAIAN_SORT_COLUMNS = [
        'notaris_rekanan' => ['text',   'notaris_rekanan'],
        'kategori'        => ['text',   'kategori_label'], // urut berdasarkan label yang tampil
        'nomor'           => ['text',   'nomor'],          // natural sort, jadi 2 < 10
        'tanggal_job'     => ['text',   'tanggal_job'],
        'tanggal_report'  => ['text',   'tanggal_report'],
        'job_count'       => ['number', 'job_count'],
        'report_count'    => ['number', 'report_count'],
        'status'          => ['text',   'status'],
    ];

    /** Sort Collection of array di PHP. Kosong = null, '' atau '-'. */
    private function sortRows($rows, array $columns, ?string $sort, string $direction)
    {
        $rows = collect($rows)->values();

        if (!$sort || !isset($columns[$sort])) {
            return $rows;
        }

        [$type, $field] = $columns[$sort];

        $value = function ($row) use ($field, $type) {
            $v = $row[$field] ?? null;
            if ($type === 'number') {
                return is_numeric($v) ? (float) $v : null;
            }
            $v = trim((string) $v);
            return ($v === '' || $v === '-') ? null : $v;
        };

        return $rows
            ->map(fn($row, $i) => ['row' => $row, 'i' => $i, 'v' => $value($row)])
            ->sort(function ($a, $b) use ($type, $direction) {
                $aEmpty = $a['v'] === null;
                $bEmpty = $b['v'] === null;

                if ($aEmpty !== $bEmpty) {
                    // asc/desc: kosong di bawah, empty: kosong di atas
                    $emptyFirst = $direction === 'empty';
                    return $aEmpty ? ($emptyFirst ? -1 : 1) : ($emptyFirst ? 1 : -1);
                }
                if ($aEmpty) {
                    return $a['i'] <=> $b['i'];
                }

                $cmp = $type === 'text'
                    ? strnatcasecmp($a['v'], $b['v'])
                    : $a['v'] <=> $b['v'];

                if ($direction === 'desc') {
                    $cmp = -$cmp;
                }

                // tie-breaker: urutan asli, biar stabil
                return $cmp ?: $a['i'] <=> $b['i'];
            })
            ->pluck('row')
            ->values();
    }
    public function pemakaianNomor(Request $request)
    {
        abort_unless($request->user()->can('laporan/pemakaian-nomor/list'), 403);

        $validated = $request->validate(array_merge([
            'notaris_rekanan_id' => ['nullable', 'integer', 'exists:notaris_rekanans,id'],
            'kategori' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(PenomoranSetting::kategori()))],
        ], $this->dateFilterRules()));
        $filters = array_intersect_key($validated, array_flip([
            'filter_type',
            'month',
            'year',
            'start_date',
            'end_date',
        ]));
        $notarisRekanan = NotarisRekanan::query()->orderBy('nama')->get();
        $selectedRekananId = $request->integer('notaris_rekanan_id');
        $kategori = $validated['kategori'] ?? null;

        $numbers = NomorPpat::query()
            ->with('notarisRekanan')
            ->whereNotNull('notaris_rekanan_id')
            ->when($selectedRekananId, fn($query) => $query->where('notaris_rekanan_id', $selectedRekananId))
            ->when($kategori, fn($query) => $query->where('kategori', $kategori))
            ->when(($filters['filter_type'] ?? null) === 'month', function ($query) use ($filters) {
                $query->whereMonth('tanggal', $filters['month'])
                    ->whereYear('tanggal', $filters['year']);
            })
            ->when(($filters['filter_type'] ?? null) === 'range', function ($query) use ($filters) {
                $query->whereDate('tanggal', '>=', $filters['start_date'])
                    ->whereDate('tanggal', '<=', $filters['end_date']);
            })
            ->get(['id', 'notaris_rekanan_id', 'kategori', 'nomor', 'tanggal', 'job_divisi_form_order_id', 'rekanan']);

        $jobs = $numbers
            ->filter(fn($number) => (int) $number->job_divisi_form_order_id > 0 && (int) $number->rekanan === 1)
            ->groupBy(fn($number) => $number->notaris_rekanan_id . '|' . $number->kategori . '|' . trim($number->nomor));
        $reported = $numbers
            ->filter(fn($number) => (int) $number->job_divisi_form_order_id === 0 && (int) $number->rekanan === 0)
            ->groupBy(fn($number) => $number->notaris_rekanan_id . '|' . $number->kategori . '|' . trim($number->nomor));

        $comparison = $jobs->keys()
            ->merge($reported->keys())
            ->unique()
            ->map(function ($key) use ($jobs, $reported) {
                [$rekananId, $numberCategory, $numberValue] = explode('|', $key, 3);
                $jobRecords = $jobs->get($key, collect());
                $reportRecords = $reported->get($key, collect());

                return [
                    'notaris_rekanan' => $jobRecords->first()?->notarisRekanan?->nama
                        ?? $reportRecords->first()?->notarisRekanan?->nama
                        ?? '-',
                    'notaris_rekanan_id' => (int) $rekananId,
                    'kategori' => $numberCategory,
                    'nomor' => $numberValue,
                    'job_count' => $jobRecords->count(),
                    'report_count' => $reportRecords->count(),
                    'tanggal_job' => $jobRecords->pluck('tanggal')->filter()->unique()->join(', '),
                    'tanggal_report' => $reportRecords->pluck('tanggal')->filter()->unique()->join(', '),
                    'status' => $jobRecords->isNotEmpty() && $reportRecords->isNotEmpty()
                        ? ($jobRecords->count() === $reportRecords->count()
                            ? 'Nomor sama, pemilik berbeda'
                            : 'Jumlah berbeda')
                        : ($jobRecords->isNotEmpty() ? 'Hanya di Job' : 'Hanya di Pelaporan'),
                ];
            })
            ->sortBy(fn($row) => strtolower($row['notaris_rekanan'] . '|' . $row['kategori'] . '|' . $row['nomor']))
            ->values();
        $categories = PenomoranSetting::kategori();
        // whitelist
$sortInput = $request->query('sort');
$sort = is_string($sortInput) && isset(self::PEMAKAIAN_SORT_COLUMNS[$sortInput]) ? $sortInput : null;
$direction = in_array($request->query('direction'), ['asc', 'desc', 'empty'], true)
    ? $request->query('direction')
    : 'asc';

// label kategori yang tampil di view, dipakai buat sort kolom Kategori
$comparison = $comparison->map(function ($row) use ($categories) {
    $row['kategori_label'] = $categories[$row['kategori']] ?? $row['kategori'];
    return $row;
});

$comparison = $this->sortRows($comparison, self::PEMAKAIAN_SORT_COLUMNS, $sort, $direction);
return view('pages.Laporan.pemakaian-nomor.index', compact(
    'notarisRekanan',
    'categories',
    'selectedRekananId',
    'kategori',
    'comparison',
    'filters',
    'sort',
    'direction'
));
    }

    private function validateDateFilters(Request $request): array
    {
        return $request->validate($this->dateFilterRules());
    }

    private function dateFilterRules(): array
    {
        return [
            'filter_type' => ['nullable', \Illuminate\Validation\Rule::in(['month', 'range'])],
            'month' => ['nullable', 'required_if:filter_type,month', 'integer', 'between:1,12'],
            'year' => ['nullable', 'required_if:filter_type,month', 'integer', 'between:1900,2200'],
            'start_date' => ['nullable', 'required_if:filter_type,range', 'date_format:Y-m-d'],
            'end_date' => [
                'nullable',
                'required_if:filter_type,range',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
        ];
    }

    public function edit($id)
    {
        $item = NomorPpat::findOrFail($id);

        $masterPekerjaan = Pekerjaan::all();

        return view(
            'pages.Laporan.nomor-notaris.edit',
            compact('item', 'masterPekerjaan')
        );
    }
    public function update(Request $request)
    {
        $item = NomorPpat::findOrFail($request->id);
        $validated = $request->validate([
            'notaris_rekanan_id' => ['nullable', 'integer', 'exists:notaris_rekanans,id'],
        ]);

        $item->update([
            'form_order_id' => $request->group_proses,
            'notaris_rekanan_id' => $validated['notaris_rekanan_id'] ?? null,
            'nama_debitur_notaris_pengambil' => $request->nama_debitur_notaris_pengambil,
            'objek_notaris_pengambil' => $request->objek_notaris_pengambil,
            'tanggal' => $request->tanggal_nomor,
        ]);

        return back()->with('success', 'Berhasil update data');
    }

    public function perpanjang(Request $request)
    {
        $request->validate([
            'nomor_ppat_id' => ['required', 'integer', 'exists:nomor_ppats,id'],
        ]);

        $formOrderBaru = DB::transaction(function () use ($request) {
            $nomor = NomorPpat::with('formOrder.jobDivisi')
                ->lockForUpdate()
                ->findOrFail($request->nomor_ppat_id);

            $formOrderLama = $nomor->formOrder;
            $jobDivisi = $formOrderLama?->jobDivisi;

            if (!$formOrderLama || !$jobDivisi) {
                throw ValidationException::withMessages([
                    'nomor_ppat_id' => 'Data Form Order atau pekerjaan induk tidak ditemukan.',
                ]);
            }

            if ($jobDivisi->status === 'Batal Akad') {
                throw ValidationException::withMessages([
                    'nomor_ppat_id' => 'Pekerjaan Batal Akad tidak dapat diperpanjang.',
                ]);
            }

            if (
                !$nomor->tanggal_expired ||
                \Carbon\Carbon::parse($nomor->tanggal_expired)->startOfDay()->gt(today())
            ) {
                throw ValidationException::withMessages([
                    'nomor_ppat_id' => 'Nomor belum expired.',
                ]);
            }

            // Cegah duplikasi apabila Form Order perpanjangan sudah dibuat.
            $sudahAda = \App\Models\JobDivisiFormOrder::query()
                ->where('job_divisi_id', $formOrderLama->job_divisi_id)
                ->where('kategori', $formOrderLama->kategori)
                ->where('created_at', '>', \Carbon\Carbon::parse(
                    $nomor->tanggal_expired
                )->startOfDay())
                ->whereDoesntHave('nomorPpat')
                ->exists();

            if ($sudahAda) {
                throw ValidationException::withMessages([
                    'nomor_ppat_id' => 'Form Order perpanjangan untuk proses ini sudah tersedia.',
                ]);
            }

            // Salin atribut Form Order lama ke record baru.
            $formOrderBaru = $formOrderLama->replicate();
            $formOrderBaru->save();

            return $formOrderBaru;
        });

        return redirect()->back()->with(
            'success',
            'Form Order perpanjangan berhasil ditambahkan.'
        );
    }
    // public function update(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required',
    //         'group_proses' => 'required',
    //         'tanggal_nomor' => 'required',
    //         'notaris_pengambil' => 'required',
    //         'nama_debitur_notaris_pengambil' => 'required',
    //     ]);

    //     try {

    //         $nomor = NomorPpat::findOrFail($request->id);

    //         $nomor->update([
    //             'form_order_id' => $request->group_proses,
    //             'notaris_pengambil' => $request->notaris_pengambil,
    //             'objek_notaris_pengambil' => $request->objek_notaris_pengambil,
    //             'nama_debitur_notaris_pengambil' => $request->nama_debitur_notaris_pengambil,
    //             'tanggal' => Carbon::parse($request->tanggal_nomor),
    //         ]);

    //         return redirect()
    //             ->route('laporan.nomor-notaris.index', $nomor->kategori)
    //             ->with('success', 'Data berhasil diupdate');
    //     } catch (\Exception $e) {

    //         return back()->with('error', $e->getMessage());
    //     }
    // }
}
