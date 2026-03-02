<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\QueueConnections;
use App\Constants\QueueNames;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPasswordResetNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 120, 300];

    public function __construct(
        public readonly User $user
    ) {
        $this->onConnection(QueueConnections::REDIS)
            ->onQueue(QueueNames::EMAILS);
    }

    public function handle(): void
    {
        $this->user->notify(new ResetPasswordNotification);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send password reset email', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
