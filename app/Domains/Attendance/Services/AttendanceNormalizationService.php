<?php

namespace App\Domains\Attendance\Services;

use App\Domains\Attendance\Models\AttendanceLogRaw;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceNormalizationService
{
    public function normalize(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = AttendanceLogRaw::query()
            ->whereNotNull('emp_id')
            ->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('attendance_logs_normalized as n')
                    ->whereColumn('n.log_id', 'attendance_logs_raw.log_id');
            })
            ->orderBy('emp_id')
            ->orderBy('log_date')
            ->orderBy('log_datetime')
            ->orderBy('log_id');

        if ($dateFrom) {
            $query->whereDate('log_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('log_date', '<=', $dateTo);
        }

        $rawLogs = $query->get();

        $groupedByDay = $rawLogs->groupBy(function ($row) {
            return $row->emp_id . '|' . $row->log_date->format('Y-m-d');
        });

        $insertRows = [];
        $processedCount = 0;

        foreach ($groupedByDay as $dayGroupKey => $dayRows) {
            $dayRows = $dayRows->sortBy([
                ['log_datetime', 'asc'],
                ['log_id', 'asc'],
            ])->values();

            // Step 1: deduplicate per same timestamp
            $timestampGroups = $dayRows->groupBy(function ($row) {
                return $row->log_datetime->format('Y-m-d H:i:s');
            });

            $primaryRows = collect();
            $duplicateRows = collect();

            foreach ($timestampGroups as $timestampKey => $sameTimeRows) {
                $sameTimeRows = $sameTimeRows->values();

                $primary = $this->pickPrimaryRow($sameTimeRows);
                $primaryRows->push($primary);

                foreach ($sameTimeRows as $row) {
                    if ($row->log_id !== $primary->log_id) {
                        $duplicateRows->push($row);
                    }
                }
            }

            $primaryRows = $primaryRows->sortBy([
                ['log_datetime', 'asc'],
                ['log_id', 'asc'],
            ])->values();

            $primaryCount = $primaryRows->count();

            // Step 2: classify primary rows for the day
            foreach ($primaryRows as $index => $row) {
                $derivedEventType = 'UNKNOWN';
                $normalizedStatus = 'VALID';
                $notes = [];

                if ($primaryCount === 1) {
                    $derivedEventType = 'UNKNOWN';
                    $normalizedStatus = 'SUSPICIOUS';
                    $notes[] = 'Single tap in one day.';
                } else {
                    if ($index === 0) {
                        $derivedEventType = 'IN';
                        $normalizedStatus = 'VALID';
                        $notes[] = 'Primary row selected for timestamp.';
                        $notes[] = 'First event of the day.';
                    } elseif ($index === $primaryCount - 1) {
                        $derivedEventType = 'OUT';
                        $normalizedStatus = 'VALID';
                        $notes[] = 'Primary row selected for timestamp.';
                        $notes[] = 'Last event of the day.';
                    } else {
                        $derivedEventType = 'UNKNOWN';
                        $normalizedStatus = 'SUSPICIOUS';
                        $notes[] = 'Primary row selected for timestamp.';
                        $notes[] = 'Intermediate event between first and last.';
                    }
                }

                $insertRows[] = [
                    'log_id' => $row->log_id,
                    'emp_id' => $row->emp_id,
                    'log_datetime' => $row->log_datetime,
                    'derived_event_type_code' => $derivedEventType,
                    'is_duplicate_candidate' => false,
                    'duplicate_group_key' => null,
                    'normalized_status_code' => $normalizedStatus,
                    'notes' => implode(' ', $notes),
                ];

                $processedCount++;
            }

            // Step 3: classify duplicate rows
            foreach ($duplicateRows as $row) {
                $duplicateGroupKey =
                    $row->emp_id . '|' .
                    $row->log_date->format('Y-m-d') . '|' .
                    $row->log_datetime->format('Y-m-d H:i:s');

                $insertRows[] = [
                    'log_id' => $row->log_id,
                    'emp_id' => $row->emp_id,
                    'log_datetime' => $row->log_datetime,
                    'derived_event_type_code' => 'UNKNOWN',
                    'is_duplicate_candidate' => true,
                    'duplicate_group_key' => $duplicateGroupKey,
                    'normalized_status_code' => 'DUPLICATE',
                    'notes' => 'Duplicate row for same employee and timestamp.',
                ];

                $processedCount++;
            }
        }

        if (! empty($insertRows)) {
            foreach (array_chunk($insertRows, 1000) as $chunk) {
                DB::table('attendance_logs_normalized')->insert($chunk);
            }
        }

        return [
            'group_count' => $groupedByDay->count(),
            'processed_count' => $processedCount,
            'inserted_count' => count($insertRows),
        ];
    }

    private function pickPrimaryRow(Collection $rows): AttendanceLogRaw
    {
        return $rows
            ->sort(function ($a, $b) {
                $scoreA = $this->rowPriorityScore($a);
                $scoreB = $this->rowPriorityScore($b);

                if ($scoreA === $scoreB) {
                    return $a->log_id <=> $b->log_id;
                }

                return $scoreA <=> $scoreB;
            })
            ->first();
    }

    private function rowPriorityScore(AttendanceLogRaw $row): int
    {
        $payload = is_array($row->raw_payload) ? $row->raw_payload : [];
        $originalRow = $payload['original_row'] ?? [];
        $fullRow = $originalRow['full_row'] ?? [];

        $extraTexts = [];
        foreach ($fullRow as $value) {
            if ($value !== null && $value !== '') {
                $extraTexts[] = mb_strtolower((string) $value);
            }
        }

        $joined = implode(' | ', $extraTexts);

        $score = 0;

        // worse priority
        if (str_contains($joined, 'invalid')) {
            $score += 100;
        }

        if (str_contains($joined, 'repeat')) {
            $score += 50;
        }

        // FOT is allowed, do not penalize
        // lower score = better candidate
        return $score;
    }
}