<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Attendance\Services\AttendanceDailyBuilderService;

class BuildAttendanceDailyCommand extends Command
{
    protected $signature = 'attendance:build-daily
                            {--date-from= : Start date (Y-m-d)}
                            {--date-to= : End date (Y-m-d)}';

    protected $description = 'Build attendance_daily from attendance_logs_normalized';

    public function handle(AttendanceDailyBuilderService $service): int
    {
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        $result = $service->build($dateFrom, $dateTo);

        $this->info('Attendance daily build completed.');
        $this->line('Groups processed: ' . ($result['group_count'] ?? 0));
        $this->line('Rows inserted: ' . ($result['inserted_count'] ?? 0));
        $this->line('Groups skipped: ' . ($result['skipped_groups'] ?? 0));

        return self::SUCCESS;
    }
}