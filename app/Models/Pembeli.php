<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembeli extends Model
{
    protected $guarded = [];
     protected $casts = [
        'tanggal_lahir' => 'date',
    ];

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

    public function jobDivisiBadanUsahaPembeli()
    {
        return $this->hasOne(JobDivisiBadanUsahaPembeli::class, 'pembeli_id', 'id');
    }
    public function files()
    {
        return $this->morphMany(JobDivisiFile::class, 'fileable');
    }
}
