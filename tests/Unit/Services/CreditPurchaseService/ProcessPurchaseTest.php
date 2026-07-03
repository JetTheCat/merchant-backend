<?php

declare(strict_types=1);

namespace Tests\Unit\Services\CreditPurchaseService;

use App\DataTransferObjects\PaymentIntentResult;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\CreditPurchaseService;
use App\Services\StripeService;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProcessPurchaseTest extends TestCase
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

        Config::set('wallet.limit', 250);

        $this->service = app(CreditPurchaseService::class);
    }

    public function testProcessPurchaseCreatesPaymentAndTransaction(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()->active()->create([
            'credit_amount' => 100,
            'selling_price' => 90
        ]);

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
        $package = CreditPackage::factory()->active()->create([
            'credit_amount' => 100,
            'selling_price' => 90
        ]);

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

    public function testProcessPurchaseFailsWhenPackageIsSoldOut(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()->active()->create([
            'total_quantity' => 2,
        ]);

        CreditTransaction::factory()->purchase()->count(2)->sequence(
            ['status' => TransactionStatus::COMPLETED],
            ['status' => TransactionStatus::PENDING],
        )->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->processPurchase($user, $package);
    }

    public function testProcessPurchaseFailsWhenUserExceedsPurchaseLimit(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()->active()->create([
            'purchase_limit_per_user' => 1,
        ]);

        CreditTransaction::factory()->purchase()->completed()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->processPurchase($user, $package);
    }

    public function testProcessPurchaseFailsWhenMerchantHasCreditSalesPaused(): void
    {
        $user = User::factory()->create();

        $merchant = Merchant::factory()->creditSalesPaused()->create();
        $package = CreditPackage::factory()
        ->active()
        ->for($merchant)
        ->create([
            'credit_amount' => 100,
            'selling_price' => 90
        ]);

        try {
            $this->service->processPurchase($user, $package);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('merchant', $e->errors());
        }
    }

    public function testProcessPurchaseFailsIfItExceedsWalletLimit(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()
        ->active()
        ->create([
            'credit_amount' => 100,
            'selling_price' => 90
        ]);

        UserCredit::factory()
            ->for($user)
            ->for($package->merchant)
            ->withBalance(250)
            ->create();

        try {
            $this->service->processPurchase($user, $package);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('wallet', $e->errors());
        }
    }

    public function testProcessPurchaseSucceedsWhenExactlyAtWalletLimit(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()
            ->active()
            ->create([
                'credit_amount' => 100,
                'selling_price' => 90,
            ]);

        UserCredit::factory()
            ->for($user)
            ->for($package->merchant)
            ->withBalance(150)
            ->create();

        $result = $this->service->processPurchase($user, $package);

        $this->assertInstanceOf(PaymentIntentResult::class, $result);
    }

    public function testProcessPurchaseRespectsConfiguredWalletLimit(): void
    {
        Config::set('wallet.limit', 50);

        $user = User::factory()->create();
        $package = CreditPackage::factory()
            ->active()
            ->create([
                'credit_amount' => 100,
                'selling_price' => 90,
            ]);

        try {
            $this->service->processPurchase($user, $package);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('wallet', $e->errors());
        }
    }

    public function testPendingPurchasesCountTowardsUserLimit(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()->active()->create([
            'purchase_limit_per_user' => 1,
        ]);

        CreditTransaction::factory()->purchase()->pending()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->processPurchase($user, $package);
    }

    public function testFailedPurchasesDoNotCountTowardsUserLimit(): void
    {
        $user = User::factory()->create();
        $package = CreditPackage::factory()->active()->create([
            'credit_amount' => 100,
            'selling_price' => 90,
            'purchase_limit_per_user' => 1,
        ]);

        CreditTransaction::factory()->purchase()->failed()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
        ]);

        $result = $this->service->processPurchase($user, $package);

        $this->assertInstanceOf(PaymentIntentResult::class, $result);
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

        $transaction = CreditTransaction::factory()->purchase()->pending()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
        ]);

        $this->service->failPurchase($payment);

        $this->assertEquals(TransactionStatus::FAILED, $transaction->fresh()->status);
    }
}
