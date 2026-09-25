<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class RuleType extends Model
{
    protected $table = 'rule_types';
    protected $primaryKey = 'rule_type_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}