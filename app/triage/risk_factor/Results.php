<?php

namespace App\triage\risk_factor;

use Illuminate\Database\Eloquent\Model;

class Results extends Model
{
    protected $table = 'risk_factor_results';
    protected $fillable = ['patient_id', 'category_id', 'question_id', 'response_id', 'created_by', 'created_at', 'updated_at'];

}
