<?php

namespace App\Http\Controllers\Export;

use App\Exports\ExportJobOps;
use App\Http\Controllers\Controller;
use App\Models\JobDivisiFormOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExportJobOpsController extends Controller
{
    public function export(Request $request)
    {
        $query = JobDivisiFormOrder::query()
            ->with([
                "jobDivisi.jenisAkad",
                "jobDivisi.listBank",
                "jobDivisi.debitur",
                "jobDivisi.objek",
                "statusJobOps"
            ])
            ->whereNotIn("status", ["rejected", "Dibatalkan"])
            ->where("kategori", "operasional")
            ->orderBy("id", "desc")
            ->whereHas("jobDivisi");

        // Role Filter
        if (Auth::user()->roles()->first()->name === "OPS staff") {
            $query->whereHas("statusJobOps", function ($q) {
                return $q->where("status", "Penugasan")->where("user_id", Auth::user()->id);
            });
        }

        // ==========================================
        // NEW FILTER Status & Tanggal Mulai
        // ==========================================
        
        // 1. Filter by stats
        if ($request->status) {
            $query->where("status", $request->status);
        }

        // 2. Filter by Tanggal Mulai
        if ($request->tanggal_mulai) {
            $query->whereDate("created_at", ">=", $request->tanggal_mulai);
        }
        if ($request->end_date) {
            $query->whereDate("created_at", "<=", $request->end_date);
        }
        
        if ($request->parent) {
            $query->whereHas("jobDivisi", fn($q) => $q->where("kode", "LIKE", "%{$request->parent}%"));
        }
        if ($request->nomor_objek) {
            $query->whereHas("jobDivisi.objek", fn($q) => $q->where("no_sertifikat", "LIKE", "%{$request->nomor_objek}%"));
        }
        if ($request->nama_penghadap) {
            $query->whereHas("jobDivisi.debitur", fn($q) => $q->where("nama", "LIKE", "%{$request->nama_penghadap}%"));
        }
        if ($request->status_pengerjaan) {
            if ($request->status_pengerjaan === "belum dikerjakan") {
                $query->doesntHave("statusJobOps");
            } elseif ($request->status_pengerjaan === "dispo") {
                $query->where("status", "dispo");
            } else {
                $query->whereHas("statusJobOps", fn($q) => $q->where("status", $request->status_pengerjaan));
            }
        }
        if ($request->status_akad) {
            $query->whereHas("jobDivisi", fn($q) => $q->where("status", $request->status_akad));
        }
        if ($request->proses) {
            $query->where("nama", $request->proses);
        }
        if ($request->bank) {
            $query->whereHas("jobDivisi.listBank", fn($q) => $q->where("nama_bank", $request->bank));
        }

        // Get the filtered collection
        $items = $query->get();

        // Download using Maatwebsite Excel
        return Excel::download(new ExportJobOps($items), 'job-ops-operasional.xlsx');
    }
}