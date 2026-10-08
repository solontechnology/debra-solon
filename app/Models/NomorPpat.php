<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomorPpat extends Model
{
    protected $guarded = [];

    public function formOrder()
    {
        return $this->belongsTo(JobDivisiFormOrder::class, "job_divisi_form_order_id", "id");
    }

    public function notarisRekanan()
    {
        return $this->belongsTo(NotarisRekanan::class)->withTrashed();
    }

    public function getPemilikNomorAttribute(): string
    {
        return (int) $this->rekanan === 1
            ? ($this->notarisRekanan?->nama ?? 'Notaris Rekanan')
            : Setting::namaNotaris();
    }

    public function getPemakaiNomorAttribute(): string
    {
        return (int) $this->job_divisi_form_order_id > 0
            ? Setting::namaNotaris()
            : ($this->notarisRekanan?->nama
                ? 'Notaris Rekanan: ' . $this->notarisRekanan->nama
                : 'Notaris Rekanan');
    }

    public function pekerjaan()
    {
        return $this->belongsTo(Pekerjaan::class, "form_order_id", "id");
    }
}
