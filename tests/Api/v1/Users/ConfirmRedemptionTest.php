<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Enums\TransactionStatus;
use App\Events\RedemptionCompleted;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class ConfirmRedemptionTest extends TestCase
{
    use UserEndpoints;

    private User $user;

    private Merchant $merchant;

    private MerchantLocation $location;

    private UserCredit $userCredit;

    private const array VALID_STRUCTURE = [
        'data' => [
            'id',
            'merchant',
            'type',
            'typeLabel',
            'status',
            'statusLabel',
            'referenceCode',
            'amount',
            'paidAmount',
            'balanceBefore',
            'balanceAfter',
            'createdAt',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([RedemptionCompleted::class]);

        $this->user = User::factory()->create();
        $this->merchant = Merchant::factory()->active()->create();
        $this->location = MerchantLocation::factory()
            ->active()
            ->for($this->merchant)
            ->create();

        $this->userCredit = UserCredit::factory()
            ->withBalance(100.00)
            ->for($this->user)
            ->for($this->merchant)
            ->create();
    }

    public function testUserCanConfirmRedemption(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 25.00,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_STRUCTURE)
            ->assertJsonPath('data.status', 'completed');
    }

    public function testRedemptionDeductsBalance(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 25.00,
            ]);

        $this->assertDatabaseHas('user_credits', [
            'id' => $this->userCredit->id,
            'balance' => '75.00',
        ]);
    }

    public function testRedemptionCreatesCompletedTransaction(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 25.00,
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'merchant_id' => $this->merchant->id,
            'merchant_location_id' => $this->location->id,
            'status' => TransactionStatus::COMPLETED,
            'amount' => 25.00,
            'settlement_amount' => 25.00,
            'gross_settlement_amount' => 25.00,
        ]);
    }

    public function testRedemptionDispatchesCompletedEvent(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 25.00,
            ]);

        Event::assertDispatched(RedemptionCompleted::class);
    }

    public function testRedemptionCodeIsRequired(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['amount' => 25.00]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['code']);
    }

    public function testRedemptionCodeMustBeUuid(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['code' => 'AB3K9P', 'amount' => 25.00]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['code']);
    }

    public function testRedemptionCodeDoesNotExistReturnsValidationError(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => Str::uuid()->toString(),
                'amount' => 25.00,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['code']);
    }

    public function testAmountIsRequired(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['code' => $this->location->redemption_code]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['amount']);
    }

    public function testAmountMustBePositive(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 0,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['amount']);
    }

    public function testInactiveLocationReturnsValidationError(): void
    {
        $this->location->update(['is_active' => false]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 25.00,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['code']);
    }

    public function testInsufficientBalanceReturnsValidationError(): void
    {
        $this->userCredit->update(['balance' => 10.00]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 25.00,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['amount']);
    }

    public function testNoWalletReturnsValidationError(): void
    {
        $this->userCredit->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 25.00,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['code']);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->postJson(self::confirmRedemptionEndpoint(), [
            'code' => $this->location->redemption_code,
            'amount' => 25.00,
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testMerchantUserCannotAccessThisEndpoint(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($merchantUser, 'merchant_user')
            ->postJson(self::confirmRedemptionEndpoint(), [
                'code' => $this->location->redemption_code,
                'amount' => 25.00,
            ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
