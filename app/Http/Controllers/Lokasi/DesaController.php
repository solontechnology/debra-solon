<?php

namespace App\Http\Controllers\Lokasi;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesaController extends Controller
{
    public function index(Request $request)
    {
        $items = Desa::query()
            ->with('kecamatan.kota.provinsi')
            ->when($request->q, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->orderBy('name', 'asc')
            ->paginate(12)
            ->withQueryString();

        $provinsis = Provinsi::orderBy('name', 'asc')->get();

        $kotas = Kota::with('provinsi')
            ->orderBy('name', 'asc')
            ->get();

        $kecamatans = Kecamatan::with('kota')
            ->orderBy('name', 'asc')
            ->get();

        return view(
            'pages.MasterData.Lokasi.Desa.index',
            compact(
                'items',
                'provinsis',
                'kotas',
                'kecamatans'
            )
        );
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $name = strtoupper(trim($request->name));

        $request->validate([
            'provinsi_id' => [
                'required',
                'exists:provinsis,id',
            ],

            'kota_id' => [
                'required',
                'exists:kotas,id',
            ],

            'kecamatan_id' => [
                'required',
                'exists:kecamatans,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique('desas', 'name')
                    ->where(function ($query) use ($request) {

                        $kodeKecamatan = Kecamatan::where(
                            'id',
                            $request->kecamatan_id
                        )->value('id_kecamatan');

                        return $query->where(
                            'kode_kecamatan',
                            $kodeKecamatan
                        );
                    }),
            ],
        ], [
            'provinsi_id.required' => 'Provinsi wajib dipilih.',
            'provinsi_id.exists' => 'Provinsi yang dipilih tidak valid.',

            'kota_id.required' => 'Kota/kabupaten wajib dipilih.',
            'kota_id.exists' => 'Kota/kabupaten yang dipilih tidak valid.',

            'kecamatan_id.required' => 'Kecamatan wajib dipilih.',
            'kecamatan_id.exists' => 'Kecamatan yang dipilih tidak valid.',

            'name.required' => 'Nama desa wajib diisi.',
            'name.unique' => 'Desa tersebut sudah terdaftar di kecamatan yang dipilih.',
            'name.max' => 'Nama desa maksimal 255 karakter.',
        ]);

        $provinsi = Provinsi::findOrFail($request->provinsi_id);

        $kota = Kota::findOrFail($request->kota_id);

        $kecamatan = Kecamatan::findOrFail($request->kecamatan_id);

        /*
        |--------------------------------------------------------------------------
        | Validasi hubungan wilayah
        |--------------------------------------------------------------------------
        */

        if ($kota->kode_provinsi != $provinsi->kode) {
            return back()
                ->withErrors([
                    'kota_id' => 'Kota/kabupaten tidak sesuai dengan provinsi yang dipilih.',
                ])
                ->withInput();
        }

        if ($kecamatan->kode_kota != $kota->id_kota) {
            return back()
                ->withErrors([
                    'kecamatan_id' => 'Kecamatan tidak sesuai dengan kota/kabupaten yang dipilih.',
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Generate ID Desa
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | Kecamatan : 3101010
        | Desa 1    : 3101010001
        | Desa 2    : 3101010002
        | Desa 3    : 3101010003
        |
        */

        $kodeKecamatan = $kecamatan->id_kecamatan;

        $nomorDesa = 1;

        while (
            Desa::where('kode_kecamatan', $kodeKecamatan)
            ->where(
                'id_desa',
                ($kodeKecamatan * 1000) + $nomorDesa
            )
            ->exists()
        ) {
            $nomorDesa++;
        }

        $idDesa =
            ($kodeKecamatan * 1000) + $nomorDesa;

        Desa::create([
            'kode_kecamatan' => $kodeKecamatan,
            'id_desa' => $idDesa,
            'name' => $name,
            'is_active' => 1,
        ]);

        return redirect()
            ->route('master-data.desa.index')
            ->with('success', 'Desa berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $desa = Desa::findOrFail($id);

        $name = strtoupper(trim($request->name));

        $request->validate([
            'provinsi_id' => [
                'required',
                'exists:provinsis,id',
            ],

            'kota_id' => [
                'required',
                'exists:kotas,id',
            ],

            'kecamatan_id' => [
                'required',
                'exists:kecamatans,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique('desas', 'name')
                    ->where(function ($query) use ($request) {

                        $kodeKecamatan = Kecamatan::where(
                            'id',
                            $request->kecamatan_id
                        )->value('id_kecamatan');

                        return $query->where(
                            'kode_kecamatan',
                            $kodeKecamatan
                        );
                    })
                    ->ignore($desa->id),
            ],
        ], [
            'provinsi_id.required' => 'Provinsi wajib dipilih.',
            'provinsi_id.exists' => 'Provinsi yang dipilih tidak valid.',

            'kota_id.required' => 'Kota/kabupaten wajib dipilih.',
            'kota_id.exists' => 'Kota/kabupaten yang dipilih tidak valid.',

            'kecamatan_id.required' => 'Kecamatan wajib dipilih.',
            'kecamatan_id.exists' => 'Kecamatan yang dipilih tidak valid.',

            'name.required' => 'Nama desa wajib diisi.',
            'name.unique' => 'Desa tersebut sudah terdaftar di kecamatan yang dipilih.',
            'name.max' => 'Nama desa maksimal 255 karakter.',
        ]);

        $provinsi = Provinsi::findOrFail($request->provinsi_id);

        $kota = Kota::findOrFail($request->kota_id);

        $kecamatan = Kecamatan::findOrFail($request->kecamatan_id);

        /*
        |--------------------------------------------------------------------------
        | Validasi hubungan wilayah
        |--------------------------------------------------------------------------
        */

        if ($kota->kode_provinsi != $provinsi->kode) {
            return back()
                ->withErrors([
                    'kota_id' => 'Kota/kabupaten tidak sesuai dengan provinsi yang dipilih.',
                ])
                ->withInput();
        }

        if ($kecamatan->kode_kota != $kota->id_kota) {
            return back()
                ->withErrors([
                    'kecamatan_id' => 'Kecamatan tidak sesuai dengan kota/kabupaten yang dipilih.',
                ])
                ->withInput();
        }

        $kodeKecamatanBaru = $kecamatan->id_kecamatan;

        /*
        |--------------------------------------------------------------------------
        | Kecamatan tidak berubah
        |--------------------------------------------------------------------------
        */

        if ($desa->kode_kecamatan == $kodeKecamatanBaru) {

            $desa->update([
                'name' => $name,
            ]);
        } else {

            /*
            |--------------------------------------------------------------------------
            | Kecamatan berubah
            |--------------------------------------------------------------------------
            */

            $nomorDesa = 1;

            while (
                Desa::where('kode_kecamatan', $kodeKecamatanBaru)
                ->where(
                    'id_desa',
                    ($kodeKecamatanBaru * 1000) + $nomorDesa
                )
                ->exists()
            ) {
                $nomorDesa++;
            }

            $idDesaBaru =
                ($kodeKecamatanBaru * 1000) + $nomorDesa;

            $desa->update([
                'kode_kecamatan' => $kodeKecamatanBaru,
                'id_desa' => $idDesaBaru,
                'name' => $name,
            ]);
        }

        return redirect()
            ->route('master-data.desa.index')
            ->with('success', 'Desa berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $desa = Desa::findOrFail($id);

        $desa->forceDelete();

        return redirect()
            ->route('master-data.desa.index')
            ->with(
                'success',
                'Desa berhasil dihapus.'
            );
    }
}
