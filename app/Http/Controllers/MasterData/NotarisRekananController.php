<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Kota;
use App\Models\NotarisRekanan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotarisRekananController extends Controller
{
    public function index(Request $request)
    {
        $items = NotarisRekanan::query()
            ->with('kota')
            ->when($request->q, fn ($query) => $query->where('nama', 'like', '%' . $request->q . '%'))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();
        $kotas = Kota::query()->with('provinsi')->where('is_active', 1)->orderBy('name')->get();

        return view('pages.MasterData.NotarisRekanan.index', compact('items', 'kotas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('notaris_rekanans', 'nama')],
            'kota_id' => ['required', 'integer', Rule::exists('kotas', 'id')->where('is_active', 1)->whereNull('deleted_at')],
        ]);

        NotarisRekanan::create($validated);

        return redirect()->route('master-data.notaris-rekanan.index')
            ->with('success', 'Notaris rekanan berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $notarisRekanan = NotarisRekanan::findOrFail($id);
        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('notaris_rekanans', 'nama')->ignore($notarisRekanan->id),
            ],
            'kota_id' => ['required', 'integer', Rule::exists('kotas', 'id')->where('is_active', 1)->whereNull('deleted_at')],
        ]);

        $notarisRekanan->update($validated);

        return redirect()->route('master-data.notaris-rekanan.index')
            ->with('success', 'Notaris rekanan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $notarisRekanan = NotarisRekanan::findOrFail($id);
        $notarisRekanan->delete();

        return redirect()->route('master-data.notaris-rekanan.index')
            ->with('success', 'Notaris rekanan berhasil dihapus.');
    }
}
