<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appraisals\AppraisalPeriod;

class SyncAppraisalPeriodStatus extends Command
{
    protected $signature = 'appraisal:sync-period-status';
    protected $description = 'Sync appraisal period status based on window dates';

    public function handle(): int
    {
        $today = now()->toDateString();

        AppraisalPeriod::query()->each(function (AppraisalPeriod $p) use ($today) {
            if ($p->status === 'locked')
                return;

            if ($today >= $p->window_open_from->toDateString() && $today <= $p->window_open_to->toDateString()) {
                $p->status = 'open';
            } elseif ($today > $p->window_open_to->toDateString()) {
                $p->status = 'closed';
            } else {
                $p->status = 'planned';
            }

            $p->save();
        });

        $this->info('Appraisal periods synced.');
        return self::SUCCESS;
    }
}
