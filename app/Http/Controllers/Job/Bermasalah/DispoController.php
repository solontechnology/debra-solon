<?php

namespace App\Http\Controllers\Job\Bermasalah;

use App\Http\Controllers\Controller;
use App\Models\Dispo;
use App\Models\JobDivisiFormOrder;
use App\Models\User;
use App\Services\Notifikasi\NotifikasiServis;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $notifikasiServis;

    public function __construct(NotifikasiServis $notifikasiServis)
    {
        $this->notifikasiServis = $notifikasiServis;
    }

    // Ekspresi SQL per kolom. Statis (bukan input user), input cuma dipakai milih key.
    private const SORT_COLUMNS = [
        'kode_parent' => [
            'type' => 'text',
            'expr' => '(select j.kode from {j} j
            join {f} f on f.job_divisi_id = j.id
            where f.id = {t}.job_divisi_form_order_id limit 1)',
        ],
        'pekerjaan' => [
            'type' => 'text',
            'expr' => '(select f.nama from {f} f where f.id = {t}.job_divisi_form_order_id limit 1)',
        ],
        'start_date' => [
            'type' => 'date',
            'expr' => '{t}.start_date',
        ],
        'end_date' => [
            'type' => 'date',
            'expr' => '{t}.end_date',
        ],
        'dibuat_oleh' => [
            'type' => 'text',
            'expr' => '(select u.name from {u} u where u.id = {t}.created_by limit 1)',
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

        $query = Dispo::orderBy("id", "desc")
            ->with("jobDivisiFormOrder.jobDivisi", "dibuat");
        $this->applySort($query, $sort, $direction);

        $items = $query->paginate(12)->withQueryString();

        return view("pages.dispo.index", compact("items", "sort", "direction"));
    }

    private function applySort($query, ?string $sort, string $direction)
    {
        $col = self::SORT_COLUMNS[$sort] ?? null;

        // gak ada sort aktif: urutan default (id desc) tetap jalan
        if (!$col) {
            return $query;
        }

        // nama tabel diambil dari model, jadi gak perlu nebak
        $t = (new Dispo)->getTable();
        $expr = str_replace(
            ['{t}', '{f}', '{j}', '{u}'],
            [
                $t,
                (new JobDivisiFormOrder)->getTable(),
                (new \App\Models\JobDivisi)->getTable(),
                (new User)->getTable(),
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

        DB::beginTransaction();

        try {

            // $insertData = Dispo::create([
            //     "job_divisi_form_order_id" => $request->form_order_id,
            //     "created_by" => Auth::user()->id,
            //     "keterangan" => $request->keterangan,
            //     "start_date" => now(),
            // ]);

            // $formOrder = JobDivisiFormOrder::find($request->form_order_id);
            // $formOrder->status = "dispo";
            // $formOrder->save();

            $insertData = Dispo::create([
                "job_divisi_form_order_id" => $request->form_order_id,
                "created_by" => Auth::user()->id,
                "keterangan" => $request->keterangan,
                "start_date" => now(),
            ]);

            $formOrder = JobDivisiFormOrder::with('jobDivisi')
                ->find($request->form_order_id);

            $formOrder->status = "dispo";
            $formOrder->save();

            $superAdmins = User::whereHas('roles', function ($query) {

                $query->where('name', 'super admin');
            })->get();

            foreach ($superAdmins as $admin) {

                $this->notifikasiServis->create(
                    $admin->id,
                    "Berkas Dispo",
                    Auth::user()->name . " menambahkan dispo",
                    route('berkas-bermasalah.dispo.index'),
                    $formOrder->jobDivisi?->id,
                    $formOrder->id
                );
            }



            DB::commit();

            return redirect()->back()->with("success", "Dispo berhasil ditambahkan");
        } catch (Exception $th) {
            DB::rollBack();
            dd($th->getMessage());
            return redirect()->back()->with("error", "Terjadi kesalahan server");
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
        DB::beginTransaction();

        try {

            $data = Dispo::with("jobDivisiFormOrder")->findOrFail($id);
            $data->user_id = Auth::user()->id;
            $data->end_date = now();
            $data->save();

            $start_date = Carbon::parse($data->start_date);
            $end_date = Carbon::parse($data->end_date);
            $calculateDay = $start_date->diffInDays($end_date, false);


            $formOrder = $data->jobDivisiFormOrder;
            $formOrder->lama_proses += (int)$calculateDay;
            $formOrder->status = "approved";

            $formOrder->save();

            DB::commit();

            return redirect()->back()->with("success", "Dispo berhasil diupdate");
        } catch (Exception $th) {
            DB::rollBack();
            dd($th);
            return redirect()->back()->with("error", "Terjadi kesalahan server");
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
