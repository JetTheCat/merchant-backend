<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class GenerateRedemptionQrTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testMerchantUserCanGenerateRedemptionQr(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateRedemptionQrEndpoint(), ['amount' => 25.00]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'data' => [
                    'transactionId',
                    'qrCode',
                    'redemptionCode',
                    'amount',
                    'expiresAt',
                ],
            ]);

        $this->assertDatabaseHas('transactions', [
            'merchant_id' => $this->merchant->id,
            'merchant_user_id' => $this->merchantUser->id,
            'type' => TransactionType::CREDIT_REDEMPTION,
            'status' => TransactionStatus::PENDING,
            'amount' => '25.00',
        ]);
    }

    public function testGeneratingNewQrCancelsPreviousPendingQr(): void
    {
        $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateRedemptionQrEndpoint(), ['amount' => 10.00]);

        $firstTransactionId = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateRedemptionQrEndpoint(), ['amount' => 20.00])
            ->json('data.transactionId');

        $this->assertDatabaseHas('transactions', [
            'id' => $firstTransactionId,
            'status' => TransactionStatus::PENDING,
        ]);

        $this->assertDatabaseMissing('transactions', [
            'merchant_user_id' => $this->merchantUser->id,
            'type' => TransactionType::CREDIT_REDEMPTION,
            'status' => TransactionStatus::PENDING,
            'amount' => '10.00',
        ]);
    }

    public function testAmountIsRequired(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateRedemptionQrEndpoint(), []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['amount']);
    }

    public function testAmountMustBePositive(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateRedemptionQrEndpoint(), ['amount' => 0]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['amount']);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->postJson(self::generateRedemptionQrEndpoint(), ['amount' => 10.00]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotAccessThisEndpoint(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson(self::generateRedemptionQrEndpoint(), ['amount' => 10.00]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRateLimitIsEnforced(): void
    {
        for ($i = 0; $i < config('rate_limiting.redemption_qr.attempts'); $i++) {
            $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
                ->postJson(self::generateRedemptionQrEndpoint(), ['amount' => 10.00]);
        }

        $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateRedemptionQrEndpoint(), ['amount' => 10.00])
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
