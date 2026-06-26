<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProofOfWork extends Model
{
    protected $table = 'proof_of_work';

    protected $fillable = ['task_id', 'image', 'description'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}