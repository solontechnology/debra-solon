<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobBank extends Model
{
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }

            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, "bank_id", "id");
    }

    public function headLegalBank()
    {
        return $this->belongsTo(BankKepalaLegal::class, "head_legal", "id");
    }
    public function legalBank()
    {
        return $this->belongsTo(BankLegal::class, "legal", "id");
    }
    public function headMarketing()
    {
        return $this->belongsTo(BankKepalaMarketing::class, "head_marketing", "id");
    }
    public function marketing()
    {
        return $this->belongsTo(BankMarketing::class, "marketing", "id");
    }
    public function files()
    {
        return $this->morphMany(JobDivisiFile::class, 'fileable');
    }
}
