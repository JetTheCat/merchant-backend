<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\QueueConnections;
use App\Constants\QueueNames;
use App\Models\MerchantUser;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMerchantUserInvitationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly MerchantUser $merchantUser
    ) {
        $this->onConnection(QueueConnections::REDIS)
            ->onQueue(QueueNames::EMAILS);
    }

    public function handle(): void
    {
        try {
            $this->merchantUser->sendMerchantUserInvitationNotification();
        } catch (Exception $e) {
            Log::error('Failed to send merchant user invitation email', [
                'merchant_user_id' => $this->merchantUser->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Merchant user invitation email job failed permanently', [
            'user_id' => $this->merchantUser->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
