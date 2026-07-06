<?php

declare(strict_types=1);

namespace Tests\Unit\Services\CreditPurchaseService;

use App\DataTransferObjects\PaymentIntentResult;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
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
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class GrantCreditsTest extends TestCase
{
    private CreditPurchaseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $mockStripeService = $this->mock(StripeService::class);

        Config::set('stripe.fees.my.card.percentage', '0.03');
        Config::set('stripe.fees.my.card.fixed', '1.00');

        $this->service = app(CreditPurchaseService::class);
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

        $userCredit = UserCredit::factory()->for($user)->create([
            'merchant_id' => $package->merchant_id,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::PROCESSING,
            'payment_method' => PaymentType::CARD,
        ]);

        $transaction = CreditTransaction::factory()->purchase()->pending()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
            'amount' => 100,
            'paid_amount' => 90,
            'balance_before' => 0,
            'balance_after' => 100,
        ]);

        $this->service->grantCredits($payment);

        $transaction->refresh();
        $userCredit->refresh();
        $payment->refresh();

        $this->assertEquals(TransactionStatus::COMPLETED, $transaction->status);
        $this->assertEquals('0.00', $transaction->balance_before);
        $this->assertEquals('100.00', $transaction->balance_after);
        $this->assertEquals('0.0500', $transaction->platform_fee_rate);
        $this->assertEquals('4.50', $transaction->platform_fee);

        // processing_fee = 90 * 0.03 + 1.00 = 3.70
        // netCash = 90 - 4.50 - 3.70 = 81.80
        $this->assertEquals('3.70', $payment->processing_fee);
        $this->assertEquals('100.00', $userCredit->balance);
        $this->assertEquals('90.00', $userCredit->gross_paid_balance);
        $this->assertEquals('81.80', $userCredit->cash_balance);
        $this->assertEquals('100.00', $userCredit->total_purchased);
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
            'payment_method' => PaymentType::CARD,
        ]);

        CreditTransaction::factory()->purchase()->pending()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
            'amount' => 100,
            'paid_amount' => 90,
        ]);

        $this->service->grantCredits($payment);

        $this->assertDatabaseHas('credit_packages', [
            'id' => $package->id,
            'sold_quantity' => 1,
        ]);
    }

    public function testGrantCreditsAccumulatesOntoExistingBalances(): void
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

        $userCredit = UserCredit::factory()
            ->for($user)
            ->withBalance(100.00)
            ->create([
                'merchant_id' => $package->merchant_id,
            ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::PROCESSING,
            'payment_method' => PaymentType::CARD,
        ]);

        CreditTransaction::factory()->purchase()->pending()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
            'amount' => 100,
            'paid_amount' => 90,
        ]);

        $this->service->grantCredits($payment);

        $userCredit->refresh();

        // processing_fee = 90 * 0.03 + 1.00 = 3.70
        // platorm_fee = 90 * 0.05 = 4.5
        // netCash = 90 - 4.5 - 3.7 = 81.80
        // cashBalance = 100 + 81.80 = 181.80
        $this->assertEquals('181.80', $userCredit->cash_balance);
        $this->assertEquals('190.00', $userCredit->gross_paid_balance);
    }

    #[DataProvider('providesMalaysiaProcessingFeeScenarios')]
    public function testGrantCreditsAppliesProcessingFeeForPaymentMethod(
        PaymentType $paymentType,
        string $percentage,
        ?string $fixed,
        string $expectedProcessingFee,
    ): void {
        Config::set("stripe.fees.my.{$paymentType->value}.percentage", $percentage);

        if ($fixed !== null) {
            Config::set("stripe.fees.my.{$paymentType->value}.fixed", $fixed);
        }

        $user = User::factory()->create();
        $merchant = Merchant::factory()->active()->create(['platform_fee_rate' => 0.05]);

        $package = CreditPackage::factory()
            ->active()
            ->for($merchant)
            ->create([
                'credit_amount' => 100,
                'selling_price' => 90,
            ]);

        $userCredit = UserCredit::factory()->for($user)->create([
            'merchant_id' => $package->merchant_id,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::PROCESSING,
            'payment_method' => $paymentType,
        ]);

        CreditTransaction::factory()->purchase()->pending()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
            'amount' => 100,
            'paid_amount' => 90,
        ]);

        $this->service->grantCredits($payment);

        $this->assertEquals($expectedProcessingFee, $payment->refresh()->processing_fee);
    }

    public function testGrantCreditsThrowsWhenProcessingFeeConfigIsMissing(): void
    {
        Config::set('stripe.fees.my.card.percentage', null);
        Config::set('stripe.fees.my.card.fixed', null);

        $user = User::factory()->create();
        $merchant = Merchant::factory()->active()->create(['platform_fee_rate' => 0.05]);

        $package = CreditPackage::factory()
            ->active()
            ->for($merchant)
            ->create([
                'credit_amount' => 100,
                'selling_price' => 90,
            ]);

        $userCredit = UserCredit::factory()->for($user)->create([
            'merchant_id' => $package->merchant_id,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::PROCESSING,
            'payment_method' => PaymentType::CARD,
        ]);

        CreditTransaction::factory()->purchase()->pending()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'merchant_id' => $package->merchant_id,
            'user_credit_id' => $userCredit->id,
            'payment_id' => $payment->id,
            'amount' => 100,
            'paid_amount' => 90,
        ]);

        $this->expectException(RuntimeException::class);

        $this->service->grantCredits($payment);
    }

    public static function providesMalaysiaProcessingFeeScenarios(): array
    {
        return [
            'card with percentage and fixed fee' => [
                'paymentType' => PaymentType::CARD,
                'percentage' => '0.03',
                'fixed' => '1.00',
                'expectedProcessingFee' => '3.70',
            ],
            'fpx with percentage and fixed fee' => [
                'paymentType' => PaymentType::FPX,
                'percentage' => '0.02',
                'fixed' => '0.50',
                'expectedProcessingFee' => '2.30',
            ],
            'grabpay with percentage only' => [
                'paymentType' => PaymentType::GRABPAY,
                'percentage' => '0.04',
                'fixed' => null,
                'expectedProcessingFee' => '3.60',
            ],
        ];
    }
}
