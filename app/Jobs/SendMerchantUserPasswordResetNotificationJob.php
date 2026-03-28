<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\QueueConnections;
use App\Constants\QueueNames;
use App\Models\MerchantUser;
use App\Notifications\MerchantUserPasswordResetNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMerchantUserPasswordResetNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 120, 300];

    public function __construct(
        public readonly MerchantUser $merchantUser
    ) {
        $this->onConnection(QueueConnections::REDIS)
            ->onQueue(QueueNames::EMAILS);
    }

    public function handle(): void
    {
        $this->merchantUser->notify(new MerchantUserPasswordResetNotification);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send merchant user password reset email', [
            'merchant_user_id' => $this->merchantUser->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
