<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class JobDivisiFormOrder extends Model
{
    protected $guarded = [];
    protected $appends = ["tanggal"];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }

            if (auth()->check()) {
                $model->updated_by = $model->updated_by ?? auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function pnbp()
    {
        return $this->hasOne(Pnbp::class, "job_divisi_form_order_id", "id");
    }

    public function dispo()
    {
        return $this->hasOne(Dispo::class, "job_divisi_form_order_id", "id");
    }

    public function finance()
    {
        return $this->hasOne(JobDivisiFinance::class, "job_divisi_form_order_id", "id");
    }


    public function getTanggalAttribute()
    {
        return $this->created_at->format("d M Y H:i");
    }

    public function user()
    {
        return  $this->belongsTo(User::class, "created_by", "id");
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, "updated_by", "id");
    }

    public function jobDivisi()
    {
        return $this->belongsTo(JobDivisi::class, "job_divisi_id", "id"); // job_divisis
    }

    public function statusJobOps()
    {
        return $this->hasMany(StatusJobOps::class, "job_divisi_form_order_id", "id");  // status_job_
    }

    public function nomorPpat()
    {
        return $this->hasOne(NomorPpat::class, "job_divisi_form_order_id", "id");
    }

    public function pembatalanItemDetail()
    {
        return $this->hasOne(PembatalanItemDetail::class, "job_form_order_id", "id");
    }

    public function objek()
    {
        return $this->belongsTo(JobDivisiObjek::class, 'objek_id', 'id');
    }
    public function pekerjaan()
    {
        return $this->belongsTo(Pekerjaan::class, 'pekerjaan_id', 'id');
    }
}
