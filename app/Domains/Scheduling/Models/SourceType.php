<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class SourceType extends Model
{
    protected $table = 'source_types';
    protected $primaryKey = 'source_type_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}