<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FileJobDivisi;
use App\Models\JobDivisi;
use App\Services\FileStoreServis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    public function __construct(
        protected FileStoreServis $fileStoreServis
    ) {}

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:7168',
            'nama' => 'required|string',
            'job_divisi_id' => 'required|exists:job_divisis,id',
        ]);

        $jobDivisi = JobDivisi::findOrFail($request->job_divisi_id);

        // Hapus file lama kalau ada
        $fileLama = FileJobDivisi::where('job_divisi_id', $jobDivisi->id)
            ->where('nama', $request->nama)
            ->first();

        if ($fileLama) {
            $this->fileStoreServis->deleteFile($fileLama);
        }

        $file = $request->file('file');

        $path = "job_divisi/{$request->nama}";

        // Gunakan nama file asli
        $namaFile = $file->getClientOriginalName();

        $file->storeAs(
            $path,
            $namaFile,
            'public'
        );

        FileJobDivisi::create([
            'job_divisi_id' => $jobDivisi->id,
            'tipe' => 'file',
            'nama' => $request->nama,
            'path' => $path . '/' . $namaFile,
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->back()
            ->with('success', "File {$request->nama} berhasil diupload");
    }

    public function destroy(string $id)
    {
        $file = FileJobDivisi::findOrFail($id);

        $this->fileStoreServis->deleteFile($file);

        return redirect()
            ->back()
            ->with(
                'success',
                'File berhasil dihapus'
            );
    }
}
