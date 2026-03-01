<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\QueueConnections;
use App\Constants\QueueNames;
use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendEmailVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(
        public readonly User $user
    ) {
        $this->onConnection(QueueConnections::REDIS)
            ->onQueue(QueueNames::EMAILS);
    }

    public function handle(): void
    {
        try {
            $this->user->sendEmailVerificationNotification();
        } catch (Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Verification email job failed permanently', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
