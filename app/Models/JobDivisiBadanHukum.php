<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobDivisiBadanHukum extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_by)) {
                $model->created_by = $model->user_id ?? (auth()->check() ? auth()->id() : null);
            }

            if (empty($model->updated_by)) {
                $model->updated_by = $model->user_id ?? (auth()->check() ? auth()->id() : null);
            }
        });

        static::updating(function ($model) {
            if (empty($model->updated_by)) {
                $model->updated_by = $model->user_id ?? (auth()->check() ? auth()->id() : null);
            } elseif (auth()->check()) {
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

    public function files()
    {
        return $this->morphMany(JobDivisiFile::class, 'fileable');
    }
}
