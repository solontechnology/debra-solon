<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenambahanItemJobDivisiDetail extends Model
{
    protected $guarded = [];

    public function pekerjaan()
    {
        return $this->belongsTo(Pekerjaan::class, "pekerjaan_id", "id");
    }

    public function formOrder()
    {
        return $this->belongsTo(JobDivisiFormOrder::class, 'job_form_order_id', 'id');
    }
}
