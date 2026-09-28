<?php

namespace App\Domains\Production\Services;

class ProductionAchievementCalculator
{
    public function calculate(int $good, int $target): float
    {
        return $target > 0 ? round(($good / $target) * 100, 2) : 0.0;
    }
}
