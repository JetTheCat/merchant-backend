<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendPasswordResetNotificationJob;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPasswordResetNotificationTest extends TestCase
{
    public function testJobSendsResetPasswordNotification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $job = new SendPasswordResetNotificationJob($user);
        $job->handle();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function testJobSendsNotificationToCorrectUser(): void
    {
        Notification::fake();

        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $job = new SendPasswordResetNotificationJob($user1);
        $job->handle();

        Notification::assertSentTo($user1, ResetPasswordNotification::class);
        Notification::assertNotSentTo($user2, ResetPasswordNotification::class);
    }

    public function testJobOnlyDispatchesNotificationOnce(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        $job = new SendPasswordResetNotificationJob($user);
        $job->handle();

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    }
}
