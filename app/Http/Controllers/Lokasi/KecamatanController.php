<?php

namespace App\Http\Controllers\Lokasi;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KecamatanController extends Controller
{
    public function index(Request $request)
    {
        $items = Kecamatan::query()
            ->with('kota.provinsi')
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

        return view(
            'pages.MasterData.Lokasi.Kecamatan.index',
            compact('items', 'provinsis', 'kotas')
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kecamatans', 'name')
                    ->where(function ($query) use ($request) {
                        $kodeKota = Kota::where(
                            'id',
                            $request->kota_id
                        )->value('id_kota');

                        return $query->where(
                            'kode_kota',
                            $kodeKota
                        );
                    }),
            ],
        ], [
            'provinsi_id.required' => 'Provinsi wajib dipilih.',
            'provinsi_id.exists' => 'Provinsi yang dipilih tidak valid.',
            'kota_id.required' => 'Kota/kabupaten wajib dipilih.',
            'kota_id.exists' => 'Kota/kabupaten yang dipilih tidak valid.',
            'name.required' => 'Nama kecamatan wajib diisi.',
            'name.unique' => 'Kecamatan tersebut sudah terdaftar di kota/kabupaten yang dipilih.',
            'name.max' => 'Nama kecamatan maksimal 255 karakter.',
        ]);

        $provinsi = Provinsi::findOrFail($request->provinsi_id);

        $kota = Kota::findOrFail($request->kota_id);

        // Pastikan kota yang dipilih memang berada
        // di provinsi yang dipilih.
        if ($kota->kode_provinsi != $provinsi->kode) {
            return back()
                ->withErrors([
                    'kota_id' => 'Kota/kabupaten tidak sesuai dengan provinsi yang dipilih.',
                ])
                ->withInput();
        }

        $kodeKota = $kota->id_kota;

        /*
         * Format id_kecamatan:
         *
         * 3101 + 010 = 3101010
         * 3101 + 020 = 3101020
         * 3101 + 030 = 3101030
         *
         * Nomor kecamatan menggunakan kelipatan 10.
         */

        $nomorKecamatan = 10;

        while (
            Kecamatan::where('kode_kota', $kodeKota)
            ->where(
                'id_kecamatan',
                ($kodeKota * 1000) + $nomorKecamatan
            )
            ->exists()
        ) {
            $nomorKecamatan += 10;
        }

        $idKecamatan = ($kodeKota * 1000) + $nomorKecamatan;

        Kecamatan::create([
            'kode_kota' => $kodeKota,
            'id_kecamatan' => $idKecamatan,
            'name' => $name,
            'is_active' => 1,
        ]);

        return redirect()
            ->route('master-data.kecamatan.index')
            ->with('success', 'Kecamatan berhasil ditambahkan.');
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
        $kecamatan = Kecamatan::findOrFail($id);

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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kecamatans', 'name')
                    ->where(function ($query) use ($request) {
                        $kodeKota = Kota::where(
                            'id',
                            $request->kota_id
                        )->value('id_kota');

                        return $query->where(
                            'kode_kota',
                            $kodeKota
                        );
                    })
                    ->ignore($kecamatan->id),
            ],
        ], [
            'provinsi_id.required' => 'Provinsi wajib dipilih.',
            'provinsi_id.exists' => 'Provinsi yang dipilih tidak valid.',
            'kota_id.required' => 'Kota/kabupaten wajib dipilih.',
            'kota_id.exists' => 'Kota/kabupaten yang dipilih tidak valid.',
            'name.required' => 'Nama kecamatan wajib diisi.',
            'name.unique' => 'Kecamatan tersebut sudah terdaftar di kota/kabupaten yang dipilih.',
            'name.max' => 'Nama kecamatan maksimal 255 karakter.',
        ]);

        $provinsi = Provinsi::findOrFail($request->provinsi_id);

        $kota = Kota::findOrFail($request->kota_id);

        // Pastikan kota sesuai dengan provinsi.
        if ($kota->kode_provinsi != $provinsi->kode) {
            return back()
                ->withErrors([
                    'kota_id' => 'Kota/kabupaten tidak sesuai dengan provinsi yang dipilih.',
                ])
                ->withInput();
        }

        $kodeKotaBaru = $kota->id_kota;

        /*
         * Kalau kota/kabupaten tidak berubah,
         * id_kecamatan tetap dipertahankan.
         */
        if ($kecamatan->kode_kota == $kodeKotaBaru) {

            $kecamatan->update([
                'name' => $name,
            ]);
        } else {

            /*
             * Kalau kota/kabupaten berubah,
             * generate id_kecamatan baru berdasarkan
             * kode kota yang baru.
             */

            $nomorKecamatan = 10;

            while (
                Kecamatan::where('kode_kota', $kodeKotaBaru)
                ->where(
                    'id_kecamatan',
                    ($kodeKotaBaru * 1000) + $nomorKecamatan
                )
                ->exists()
            ) {
                $nomorKecamatan += 10;
            }

            $idKecamatanBaru =
                ($kodeKotaBaru * 1000) + $nomorKecamatan;

            $kecamatan->update([
                'kode_kota' => $kodeKotaBaru,
                'id_kecamatan' => $idKecamatanBaru,
                'name' => $name,
            ]);
        }

        return redirect()
            ->route('master-data.kecamatan.index')
            ->with('success', 'Kecamatan berhasil diperbarui.');
    }
    public function destroy(string $id)
    {
        $kecamatan = Kecamatan::findOrFail($id);

        // Hapus semua Desa di Kecamatan
        Desa::where('kode_kecamatan', $kecamatan->id_kecamatan)
            ->forceDelete();

        // Hapus Kecamatan
        $kecamatan->forceDelete();

        return redirect()
            ->route('master-data.kecamatan.index')
            ->with(
                'success',
                'Kecamatan beserta data desa berhasil dihapus.'
            );
    }
    // public function destroy(string $id)
    // {
    //     $kecamatan = Kecamatan::findOrFail($id);

    //     $kecamatan->forceDelete();

    //     return redirect()
    //         ->route('master-data.kecamatan.index')
    //         ->with('success', 'Kecamatan berhasil dihapus.');
    // }
}
