<?php

namespace Database\Factories;

use App\Models\CorrectionBreak;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrectionBreakFactory extends Factory
{
    protected $model = CorrectionBreak::class;

    public function definition(): array
    {
        return [
            'correction_id' => AttendanceCorrectionRequest::factory(),
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ];
    }
}
