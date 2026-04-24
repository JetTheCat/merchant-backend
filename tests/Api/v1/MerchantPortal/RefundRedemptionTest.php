<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use App\Models\UserCredit;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class RefundRedemptionTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $owner;

    private User $customer;

    private UserCredit $userCredit;

    private CreditTransaction $redemption;

    private const VALID_STRUCTURE = [
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

        $this->merchant = Merchant::factory()->active()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
        $this->customer = User::factory()->create();
        $this->userCredit = UserCredit::factory()
            ->for($this->customer)
            ->for($this->merchant)
            ->withBalance(50)
            ->create(['total_redeemed' => 30]);
        $this->redemption = CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->for($this->customer)
            ->create([
                'merchant_user_id' => $this->owner->id,
                'user_credit_id' => $this->userCredit->id,
                'amount' => 30,
                'balance_before' => 80,
                'balance_after' => 50,
            ]);
    }

    public function testOwnerCanRefundACompletedRedemption(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::refundRedemptionEndpoint($this->redemption->id));

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(self::VALID_STRUCTURE);
    }

    public function testCustomerBalanceIsRestoredAfterRefund(): void
    {
        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::refundRedemptionEndpoint($this->redemption->id));

        $this->userCredit->refresh();

        $this->assertEquals('80.00', $this->userCredit->balance);
        $this->assertEquals('0.00', $this->userCredit->total_redeemed);
    }

    public function testOriginalRedemptionIsMarkedReversed(): void
    {
        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::refundRedemptionEndpoint($this->redemption->id));

        $this->redemption->refresh();

        $this->assertEquals(TransactionStatus::REVERSED, $this->redemption->status);
    }

    public function testRefundAcceptsOptionalNotes(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::refundRedemptionEndpoint($this->redemption->id), [
                'notes' => 'Wrong amount charged.',
            ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('transactions', [
            'type' => TransactionType::CREDIT_REFUND->value,
            'merchant_id' => $this->merchant->id,
            'notes' => 'Wrong amount charged.',
        ]);
    }

    public function testStaffCannotRefundRedemption(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->postJson(self::refundRedemptionEndpoint($this->redemption->id));

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testCannotRefundAlreadyReversedRedemption(): void
    {
        $this->redemption->update(['status' => TransactionStatus::REVERSED]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::refundRedemptionEndpoint($this->redemption->id));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCannotRefundRedemptionFromAnotherMerchant(): void
    {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherOwner = MerchantUser::factory()->owner()->active()->for($otherMerchant)->create();

        $response = $this->actingAs($otherOwner, self::MERCHANT_USER_GUARD)
            ->postJson(self::refundRedemptionEndpoint($this->redemption->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRefundRedemptionRequiresAuthentication(): void
    {
        $response = $this->postJson(self::refundRedemptionEndpoint($this->redemption->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotRefundRedemption(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson(self::refundRedemptionEndpoint($this->redemption->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
