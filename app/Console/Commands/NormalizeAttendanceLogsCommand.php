<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Attendance\Services\AttendanceNormalizationService;

class NormalizeAttendanceLogsCommand extends Command
{
    protected $signature = 'attendance:normalize
                            {--date-from= : Start date (Y-m-d)}
                            {--date-to= : End date (Y-m-d)}';

    protected $description = 'Normalize attendance raw logs into attendance_logs_normalized';

    public function handle(AttendanceNormalizationService $service): int
    {
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        $result = $service->normalize($dateFrom, $dateTo);

        $this->info('Attendance normalization completed.');
        $this->line('Groups processed: ' . $result['group_count']);
        $this->line('Rows processed: ' . $result['processed_count']);
        $this->line('Rows inserted: ' . $result['inserted_count']);

        return self::SUCCESS;
    }
}