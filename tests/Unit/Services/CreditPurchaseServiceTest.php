<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DataTransferObjects\PaymentIntentResult;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\CreditPackage;
use App\Models\Merchant;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\CreditPurchaseService;
use App\Services\StripeService;
use Tests\TestCase;

class CreditPurchaseServiceTest extends TestCase
{
    private CreditPurchaseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $mockStripeService = $this->mock(StripeService::class);
        $mockStripeService->shouldReceive('createPaymentIntent')
            ->andReturnUsing(fn (Payment $payment) => new PaymentIntentResult(
                paymentId: $payment->id,
                paymentIntentId: 'pi_test_'.fake()->regexify('[A-Za-z0-9]{24}'),
                clientSecret: 'pi_test_secret_'.fake()->regexify('[A-Za-z0-9]{24}'),
            ));

        $this->service = app(CreditPurchaseService::class);
    }

    public function testProcessPurchaseCreatesPaymentAndTransaction(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()->active()->create(['credit_amount' => 100, 'selling_price' => 90]);

        $result = $this->service->processPurchase($user, $package);

        $this->assertInstanceOf(PaymentIntentResult::class, $result);
        $this->assertNotNull($result->paymentId);
        $this->assertNotNull($result->clientSecret);

        $this->assertDatabaseHas('payments', [
            'id' => $result->paymentId,
            'user_id' => $user->id,
            'amount' => '90.00',
            'status' => PaymentStatus::REQUIRES_PAYMENT_METHOD,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'merchant_id' => $package->merchant_id,
            'status' => TransactionStatus::PENDING,
            'amount' => '100.00',
            'paid_amount' => '90.00',
        ]);
    }

    public function testProcessPurchaseCreatesUserCreditIfNotExists(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()->active()->create();

        $this->assertDatabaseMissing('user_credits', [
            'user_id' => $user->id,
            'merchant_id' => $package->merchant_id,
        ]);

        $this->service->processPurchase($user, $package);

        $this->assertDatabaseHas('user_credits', [
            'user_id' => $user->id,
            'merchant_id' => $package->merchant_id,
        ]);
    }

    public function testGrantCreditsUpdatesBalanceAndCompletesTransaction(): void
    {
        $user = User::factory()->create();
        $merchant = Merchant::factory()
            ->active()
            ->create(['platform_fee_rate' => 0.05]);

        $package = CreditPackage::factory()
            ->active()
            ->for($merchant)
            ->create([
                'credit_amount' => 100,
                'selling_price' => 90,
            ]);

        $userCredit = UserCredit::factory()->for($user)->withBalance(50)->create([
            'merchant_id' => $package->merchant_id,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::PROCESSING,
        ]);

        $transaction = $package->transactions()->create([
            'user_id' => $user->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
            'category' => 'credit',
            'type' => 'credit_purchase',
            'reference_code' => 'TXN-TEST-001',
            'status' => TransactionStatus::PENDING,
            'amount' => 100,
            'paid_amount' => 90,
            'balance_before' => 50,
            'balance_after' => 150,
        ]);

        $this->service->grantCredits($payment);

        $transaction->refresh();
        $userCredit->refresh();

        $this->assertEquals(TransactionStatus::COMPLETED, $transaction->status);
        $this->assertEquals('50.00', $transaction->balance_before);
        $this->assertEquals('150.00', $transaction->balance_after);
        $this->assertEquals('0.0500', $transaction->platform_fee_rate);
        $this->assertEquals('4.50', $transaction->platform_fee);
        $this->assertEquals('150.00', $userCredit->balance);
        $this->assertEquals('150.00', $userCredit->total_purchased);
        $this->assertNotNull($userCredit->last_purchase_at);
    }

    public function testGrantCreditsIncrementsSoldQuantity(): void
    {
        $user = User::factory()->create();
        $merchant = Merchant::factory()->active()->create(['platform_fee_rate' => 0.05]);

        $package = CreditPackage::factory()
            ->active()
            ->for($merchant)
            ->create([
                'credit_amount' => 100,
                'selling_price' => 90,
                'sold_quantity' => 0,
            ]);

        $userCredit = UserCredit::factory()->for($user)->create([
            'merchant_id' => $package->merchant_id,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::PROCESSING,
        ]);

        $package->transactions()->create([
            'user_id' => $user->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
            'category' => 'credit',
            'type' => 'credit_purchase',
            'reference_code' => 'TXN-TEST-003',
            'status' => TransactionStatus::PENDING,
            'amount' => 100,
            'paid_amount' => 90,
            'balance_before' => 0,
            'balance_after' => 100,
        ]);

        $this->service->grantCredits($payment);

        $this->assertDatabaseHas('credit_packages', [
            'id' => $package->id,
            'sold_quantity' => 1,
        ]);
    }

    public function testFailPurchaseMarksTransactionAsFailed(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()->active()->create();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::PAYMENT_FAILED,
        ]);

        $userCredit = UserCredit::factory()
            ->for($user)
            ->for($package->merchant)
            ->create();

        $transaction = $package->transactions()->create([
            'user_id' => $user->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
            'category' => 'credit',
            'type' => 'credit_purchase',
            'reference_code' => 'TXN-TEST-002',
            'status' => TransactionStatus::PENDING,
            'amount' => 100,
            'paid_amount' => 90,
            'balance_before' => 0,
            'balance_after' => 100,
        ]);

        $this->service->failPurchase($payment);

        $this->assertEquals(TransactionStatus::FAILED, $transaction->fresh()->status);
    }
}
