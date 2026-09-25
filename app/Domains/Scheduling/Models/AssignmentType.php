<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentType extends Model
{
    protected $table = 'assignment_types';
    protected $primaryKey = 'assignment_type_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}