<?php

namespace App\Http\Controllers\Lokasi;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KotaController extends Controller
{
    public function index(Request $request)
    {
        $items = Kota::query()
            ->with('provinsi')
            ->when($request->q, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->orderBy('name', 'asc')
            ->paginate(12)
            ->withQueryString();

        $provinsis = Provinsi::orderBy('name', 'asc')->get();

        return view(
            'pages.MasterData.Lokasi.Kota.index',
            compact('items', 'provinsis')
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kotas', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where(
                            'kode_provinsi',
                            Provinsi::where('id', $request->provinsi_id)->value('kode')
                        );
                    }),
            ],
        ], [
            'provinsi_id.required' => 'Provinsi wajib dipilih.',
            'provinsi_id.exists' => 'Provinsi yang dipilih tidak valid.',
            'name.required' => 'Nama kota/kabupaten wajib diisi.',
            'name.unique' => 'Kota/kabupaten tersebut sudah terdaftar di provinsi yang dipilih.',
            'name.max' => 'Nama kota/kabupaten maksimal 255 karakter.',
        ]);

        $provinsi = Provinsi::findOrFail($request->provinsi_id);

        $kodeProvinsi = $provinsi->kode;

        /*
        |--------------------------------------------------------------------------
        | Cari nomor kota terkecil yang belum digunakan
        |--------------------------------------------------------------------------
        |
        | Contoh:
        | 3101 sudah ada
        | 3102 sudah ada
        | 3103 belum ada
        |
        | Maka id_kota = 3103
        |
        */

        $nomorKota = 1;

        while (
            Kota::where('kode_provinsi', $kodeProvinsi)
            ->where('id_kota', $kodeProvinsi * 100 + $nomorKota)
            ->exists()
        ) {
            $nomorKota++;
        }

        $idKota = ($kodeProvinsi * 100) + $nomorKota;

        Kota::create([
            'kode_provinsi' => $kodeProvinsi,
            'id_kota' => $idKota,
            'name' => $name,
            'is_active' => 1,
        ]);

        return redirect()
            ->route('master-data.kota.index')
            ->with('success', 'Kota/kabupaten berhasil ditambahkan.');
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
        $kota = Kota::findOrFail($id);

        $name = strtoupper(trim($request->name));

        $request->validate([
            'provinsi_id' => [
                'required',
                'exists:provinsis,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kotas', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where(
                            'kode_provinsi',
                            Provinsi::where('id', $request->provinsi_id)->value('kode')
                        );
                    })
                    ->ignore($kota->id),
            ],
        ], [
            'provinsi_id.required' => 'Provinsi wajib dipilih.',
            'provinsi_id.exists' => 'Provinsi yang dipilih tidak valid.',
            'name.required' => 'Nama kota/kabupaten wajib diisi.',
            'name.unique' => 'Kota/kabupaten tersebut sudah terdaftar di provinsi yang dipilih.',
            'name.max' => 'Nama kota/kabupaten maksimal 255 karakter.',
        ]);

        $provinsi = Provinsi::findOrFail($request->provinsi_id);

        $kodeProvinsiBaru = $provinsi->kode;

        /*
        |--------------------------------------------------------------------------
        | Kalau provinsi tidak berubah
        |--------------------------------------------------------------------------
        |
        | id_kota tetap dipertahankan.
        |
        */

        if ($kota->kode_provinsi == $kodeProvinsiBaru) {

            $kota->update([
                'name' => $name,
            ]);
        } else {

            /*
            |--------------------------------------------------------------------------
            | Kalau provinsi berubah
            |--------------------------------------------------------------------------
            |
            | Generate id_kota baru berdasarkan kode provinsi baru.
            |
            */

            $nomorKota = 1;

            while (
                Kota::where('kode_provinsi', $kodeProvinsiBaru)
                ->where(
                    'id_kota',
                    ($kodeProvinsiBaru * 100) + $nomorKota
                )
                ->exists()
            ) {
                $nomorKota++;
            }

            $idKotaBaru = ($kodeProvinsiBaru * 100) + $nomorKota;

            $kota->update([
                'kode_provinsi' => $kodeProvinsiBaru,
                'id_kota' => $idKotaBaru,
                'name' => $name,
            ]);
        }

        return redirect()
            ->route('master-data.kota.index')
            ->with('success', 'Kota/kabupaten berhasil diperbarui.');
    }
    public function destroy(string $id)
    {
        $kota = Kota::findOrFail($id);

        // Ambil semua Kecamatan di kota/kabupaten ini
        $kodeKecamatans = Kecamatan::where('kode_kota', $kota->id_kota)
            ->pluck('id_kecamatan');

        // Hapus semua Desa
        Desa::whereIn('kode_kecamatan', $kodeKecamatans)
            ->forceDelete();

        // Hapus semua Kecamatan
        Kecamatan::where('kode_kota', $kota->id_kota)
            ->forceDelete();

        // Hapus Kota/Kabupaten
        $kota->forceDelete();

        return redirect()
            ->route('master-data.kota.index')
            ->with(
                'success',
                'Kota/kabupaten beserta data kecamatan dan desa berhasil dihapus.'
            );
    }
    // public function destroy(string $id)
    // {
    //     $kota = Kota::findOrFail($id);

    //     $kota->forceDelete();

    //     return redirect()
    //         ->route('master-data.kota.index')
    //         ->with('success', 'Kota/kabupaten berhasil dihapus.');
    // }
}
