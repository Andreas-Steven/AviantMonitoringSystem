<?php

namespace App\Domains\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_LOCKED = 'LOCKED';

    protected $table = 'payroll_periods';
    protected $primaryKey = 'payroll_period_id';

    protected $fillable = [
        'period_code',
        'period_start_date',
        'period_end_date',
        'payroll_year',
        'payroll_month',
        'payroll_period_status_code',
        'notes',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
    ];

    public function isOpen(): bool
    {
        return $this->payroll_period_status_code === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->payroll_period_status_code === self::STATUS_CLOSED;
    }

    public function isLocked(): bool
    {
        return $this->payroll_period_status_code === self::STATUS_LOCKED;
    }
}