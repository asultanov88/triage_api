<?php

namespace App\triage\demographics;

use Illuminate\Database\Eloquent\Model;

class QuestionResponseLk extends Model
{
    protected $table = 'demographics_quest_resp_lk';
    protected $fillable = ['question_id', 'response_id'];
}
