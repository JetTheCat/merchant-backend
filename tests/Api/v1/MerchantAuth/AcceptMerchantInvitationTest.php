<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantAuth;

use App\Enums\MerchantUserStatus;
use App\Models\MerchantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantAuthEndpoints;
use Tests\TestCase;

class AcceptMerchantInvitationTest extends TestCase
{
    use MerchantAuthEndpoints;

    public function testOwnerCanAcceptInvitationAndSetPassword(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->pendingVerification()->create();

        $url = $this->acceptMerchantInvitationUrl($merchantUser);

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Account set up successfully.',
            ]);

        $merchantUser->refresh();

        $this->assertEquals(MerchantUserStatus::ACTIVE, $merchantUser->status);
        $this->assertNotNull($merchantUser->email_verified_at);
        $this->assertTrue(Hash::check('NewPassword123!@#', $merchantUser->password));
    }

    public function testAcceptInvitationFailsIfAlreadyActive(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()->create();

        $url = $this->acceptMerchantInvitationUrl($merchantUser);

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'message' => 'This invitation has already been accepted.',
            ]);
    }

    public function testAcceptInvitationFailsIfAlreadySuspended(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->suspended()->create();

        $url = $this->acceptMerchantInvitationUrl($merchantUser);

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'message' => 'This invitation has already been accepted.',
            ]);
    }

    public function testAcceptInvitationFailsWithTamperedHash(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->pendingVerification()->create();

        $url = URL::temporarySignedRoute(
            'merchant.invitation.setup',
            now()->addHours(72),
            [
                'id' => $merchantUser->getKey(),
                'hash' => sha1('tampered@example.com'),
            ]
        );

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Invalid or expired invitation link.',
            ]);

        $this->assertEquals(MerchantUserStatus::PENDING_VERIFICATION, $merchantUser->fresh()->status);
    }

    public function testAcceptInvitationFailsWithTamperedSignature(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->pendingVerification()->create();

        $url = $this->acceptMerchantInvitationUrl($merchantUser);
        $tamperedUrl = "{$url}&tampered=true";

        $response = $this->postJson($tamperedUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertEquals(MerchantUserStatus::PENDING_VERIFICATION, $merchantUser->fresh()->status);
    }

    public function testAcceptInvitationFailsWithExpiredUrl(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->pendingVerification()->create();

        $expiredUrl = URL::temporarySignedRoute(
            'merchant.invitation.setup',
            now()->subHour(),
            [
                'id' => $merchantUser->getKey(),
                'hash' => sha1($merchantUser->email),
            ]
        );

        $response = $this->postJson($expiredUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertEquals(MerchantUserStatus::PENDING_VERIFICATION, $merchantUser->fresh()->status);
    }

    public function testAcceptInvitationFailsForStaffAccounts(): void
    {
        $staff = MerchantUser::factory()->staff()->pendingVerification()->create();

        $url = URL::temporarySignedRoute(
            'merchant.invitation.setup',
            now()->addHours(72),
            [
                'id' => $staff->getKey(),
                'hash' => sha1((string) $staff->email),
            ]
        );

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Invalid or expired invitation link.',
            ]);
    }

    public function testAcceptInvitationFailsIfUserDoesNotExist(): void
    {
        $nonExistentId = Str::uuid()->toString();

        $url = URL::temporarySignedRoute(
            'merchant.invitation.setup',
            now()->addHours(72),
            [
                'id' => $nonExistentId,
                'hash' => sha1('someone@example.com'),
            ]
        );

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Invalid or expired invitation link.',
            ]);
    }

    public function testAcceptInvitationFailsWithMissingPassword(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->pendingVerification()->create();

        $url = $this->acceptMerchantInvitationUrl($merchantUser);

        $response = $this->postJson($url, []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }

    public function testAcceptInvitationFailsWithPasswordMismatch(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->pendingVerification()->create();

        $url = $this->acceptMerchantInvitationUrl($merchantUser);

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'DifferentPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }
}
