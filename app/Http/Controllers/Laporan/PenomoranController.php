<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\JobDivisiFormOrder;
use App\Models\MasterDataFormOrder;
use App\Models\MasterDataFormOrderDetail;
use App\Models\NomorPpat;
use App\Models\NotarisRekanan;
use App\Models\PenomoranSetting;
use App\Models\Pekerjaan;
use App\Services\Akta\InputNomorServis;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\PenomoranExport;
use Maatwebsite\Excel\Facades\Excel;


class PenomoranController extends Controller
{

    public function __construct(protected InputNomorServis $inputNomorServis) {}

    public function index(Request $request, $kategori = null)
    {
        $filters = $this->validateDateFilters($request);

        $masterPekerjaan = Pekerjaan::query()
            ->when($kategori, fn ($query) => $query->where('kategori', $kategori))
            ->orderBy('nama')
            ->get();
        $penomoranSetting = $kategori
            ? PenomoranSetting::query()->where('kategori', $kategori)->first()
            : null;

      $items = NomorPpat::with([
    'formOrder.jobDivisi.debitur',
    'formOrder.objek',
    'notarisRekanan',
])
            ->when($kategori, fn ($query) => $query->where('kategori', $kategori))
            ->when(($filters['filter_type'] ?? null) === 'month', function ($query) use ($filters) {
                $query->whereMonth('tanggal', $filters['month'])
                    ->whereYear('tanggal', $filters['year']);
            })
            ->when(($filters['filter_type'] ?? null) === 'range', function ($query) use ($filters) {
                $query->whereDate('tanggal', '>=', $filters['start_date'])
                    ->whereDate('tanggal', '<=', $filters['end_date']);
            })
            ->orderBy('id', 'desc')
            ->paginate(12)
            ->appends($request->query());

        $notarisRekanan = NotarisRekanan::query()->with('kota')->orderBy('nama')->get();

        return view('pages.Laporan.nomor-notaris.index', compact(
            'items',
            'kategori',
            'masterPekerjaan',
            'notarisRekanan',
            'penomoranSetting',
            'filters'
        ));
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
            ->when($selectedRekananId, fn ($query) => $query->where('notaris_rekanan_id', $selectedRekananId))
            ->when($kategori, fn ($query) => $query->where('kategori', $kategori))
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
            ->filter(fn ($number) => (int) $number->job_divisi_form_order_id > 0 && (int) $number->rekanan === 1)
            ->groupBy(fn ($number) => $number->notaris_rekanan_id . '|' . $number->kategori . '|' . trim($number->nomor));
        $reported = $numbers
            ->filter(fn ($number) => (int) $number->job_divisi_form_order_id === 0 && (int) $number->rekanan === 0)
            ->groupBy(fn ($number) => $number->notaris_rekanan_id . '|' . $number->kategori . '|' . trim($number->nomor));

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
            ->sortBy(fn ($row) => strtolower($row['notaris_rekanan'] . '|' . $row['kategori'] . '|' . $row['nomor']))
            ->values();
        $categories = PenomoranSetting::kategori();

        return view('pages.Laporan.pemakaian-nomor.index', compact(
            'notarisRekanan',
            'categories',
            'selectedRekananId',
            'kategori',
            'comparison',
            'filters'
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
