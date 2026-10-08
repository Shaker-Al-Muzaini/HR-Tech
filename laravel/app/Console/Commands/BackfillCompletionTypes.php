<?php

namespace App\Console\Commands;

use App\Models\Interview;
use Illuminate\Console\Command;

class BackfillCompletionTypes extends Command
{
    protected $signature   = 'interview:backfill-completion-types';
    protected $description = 'تعبئة completion_type للمقابلات المكتملة القديمة';

    public function handle(): int
    {
        $count = Interview::where('status', 'completed')
            ->whereNull('completion_type')
            ->update(['completion_type' => 'ended_by_hr']);

        $this->info("✅ تم تحديث {$count} مقابلة");

        return self::SUCCESS;
    }
}