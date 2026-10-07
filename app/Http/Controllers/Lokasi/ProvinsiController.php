<?php

namespace App\Http\Controllers\Lokasi;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Http\Request;

class ProvinsiController extends Controller
{
    public function index(Request $request)
    {
        $items = Provinsi::query()
            ->when($request->q, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->orderBy('name', 'asc')
            ->paginate(12)
            ->withQueryString();

        return view(
            'pages.MasterData.Lokasi.Provinsi.index',
            compact('items')
        );
    }

    public function store(Request $request)
    {
        $name = strtoupper(trim($request->name));

        validator(
            [
                'name' => $name,
            ],
            [
                'name' => 'required|string|max:255|unique:provinsis,name',
            ],
            [
                'name.required' => 'Nama provinsi wajib diisi.',
                'name.unique' => 'Provinsi tersebut sudah terdaftar.',
                'name.max' => 'Nama provinsi maksimal 255 karakter.',
            ]
        )->validate();

        // Cari kode terkecil yang belum digunakan
        $kode = 1;

        while (Provinsi::where('kode', $kode)->exists()) {
            $kode++;
        }

        Provinsi::create([
            'kode' => $kode,
            'name' => $name,
            'is_active' => 1,
        ]);

        return redirect()
            ->route('master-data.provinsi.index')
            ->with('success', 'Provinsi berhasil ditambahkan.');
    }
    public function update(Request $request, string $id)
    {
        $provinsi = Provinsi::findOrFail($id);

        $name = strtoupper(trim($request->name));

        validator(
            [
                'name' => $name,
            ],
            [
                'name' => 'required|string|max:255|unique:provinsis,name,' . $provinsi->id,
            ],
            [
                'name.required' => 'Nama provinsi wajib diisi.',
                'name.unique' => 'Provinsi tersebut sudah terdaftar.',
                'name.max' => 'Nama provinsi maksimal 255 karakter.',
            ]
        )->validate();

        $provinsi->update([
            'name' => $name,
        ]);

        return redirect()
            ->route('master-data.provinsi.index')
            ->with('success', 'Provinsi berhasil diperbarui.');
    }

    // public function destroy(string $id)
    // {
    //     $provinsi = Provinsi::findOrFail($id);

    //     $provinsi->forceDelete();

    //     return redirect()
    //         ->route('master-data.provinsi.index')
    //         ->with('success', 'Provinsi berhasil dihapus.');
    // }

    public function destroy(string $id)
    {
        $provinsi = Provinsi::findOrFail($id);

        // Ambil semua ID Kota/Kabupaten di provinsi ini
        $kodeKotas = Kota::where('kode_provinsi', $provinsi->kode)
            ->pluck('id_kota');

        // Ambil semua ID Kecamatan dari kota-kota tersebut
        $kodeKecamatans = Kecamatan::whereIn('kode_kota', $kodeKotas)
            ->pluck('id_kecamatan');

        // Hapus semua Desa
        Desa::whereIn('kode_kecamatan', $kodeKecamatans)
            ->forceDelete();

        // Hapus semua Kecamatan
        Kecamatan::whereIn('kode_kota', $kodeKotas)
            ->forceDelete();

        // Hapus semua Kota/Kabupaten
        Kota::where('kode_provinsi', $provinsi->kode)
            ->forceDelete();

        // Hapus Provinsi
        $provinsi->forceDelete();

        return redirect()
            ->route('master-data.provinsi.index')
            ->with(
                'success',
                'Provinsi beserta data kota/kabupaten, kecamatan, dan desa berhasil dihapus.'
            );
    }
}
