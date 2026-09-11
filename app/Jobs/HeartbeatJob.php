<?php

namespace App\Jobs;

use App\Services\Health\Heartbeat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Does nothing but record that it ran. The scheduler queues one every minute;
 * if the worker is alive it runs within seconds, and the status page reads the
 * time it left behind.
 */
class HeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(Heartbeat $heartbeat): void
    {
        $heartbeat->beat(Heartbeat::QUEUE);
    }
}
