<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriodStatus extends Model
{
    protected $table = 'payroll_period_statuses';
    protected $primaryKey = 'payroll_period_status_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}