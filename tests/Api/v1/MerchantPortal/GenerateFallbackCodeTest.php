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

class GenerateFallbackCodeTest extends TestCase
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

    public function testMerchantUserCanGenerateFallbackCode(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateFallbackCodeEndpoint(), ['amount' => 25.00]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'data' => [
                    'transactionId',
                    'code',
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

    public function testGeneratingNewCodeCancelsPreviousPending(): void
    {
        $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateFallbackCodeEndpoint(), ['amount' => 10.00]);

        $secondTransactionId = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateFallbackCodeEndpoint(), ['amount' => 20.00])
            ->json('data.transactionId');

        $this->assertDatabaseHas('transactions', [
            'id' => $secondTransactionId,
            'type' => TransactionType::CREDIT_REDEMPTION,
            'status' => TransactionStatus::PENDING,
            'amount' => '20.00',
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
            ->postJson(self::generateFallbackCodeEndpoint(), []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['amount']);
    }

    public function testAmountMustBePositive(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::generateFallbackCodeEndpoint(), ['amount' => 0]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['amount']);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->postJson(self::generateFallbackCodeEndpoint(), ['amount' => 10.00]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotAccessThisEndpoint(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson(self::generateFallbackCodeEndpoint(), ['amount' => 10.00]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
