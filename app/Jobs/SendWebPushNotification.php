<?php

namespace App\Jobs;

use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * @param  array<int,int>  $subscriptionIds
     * @param  array<string,mixed>  $payload
     */
    public function __construct(
        public array $subscriptionIds,
        public array $payload,
    ) {}

    public function handle(): void
    {
        WebPushService::deliver($this->subscriptionIds, $this->payload);
    }
}
