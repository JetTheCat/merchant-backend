<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\CreditPackage;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class UpdateCreditPackageTest extends TestCase
{
    use MerchantPortalEndpoints;

    private MerchantUser $owner;

    private Merchant $merchant;

    private CreditPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
        $this->package = CreditPackage::factory()->for($this->merchant)->create();
    }

    public function testOwnerCanUpdateCreditPackage(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateCreditPackageEndpoint((string) $this->package->id), [
                'name' => 'Updated Package Name',
                'selling_price' => 85.00,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'merchantId',
                    'name',
                    'description',
                    'creditAmount',
                    'sellingPrice',
                    'discountPercentage',
                    'isActive',
                    'purchaseLimitPerUser',
                    'totalQuantity',
                    'soldQuantity',
                    'createdAt',
                    'updatedAt',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Updated Package Name',
                    'sellingPrice' => '85.00',
                ],
            ]);
    }

    #[DataProvider('activeStates')]
    public function testOwnerCanToggleActiveStatus(bool $initial, bool $updated): void
    {
        $this->package->update(['is_active' => $initial]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateCreditPackageEndpoint((string) $this->package->id), [
                'is_active' => $updated,
            ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals($updated, $this->package->fresh()->is_active);
    }

    public static function activeStates(): array
    {
        return [
            'activate package' => [false, true],
            'deactivate package' => [true, false],
        ];
    }

    public function testOwnerCannotUpdatePackageFromAnotherMerchant(): void
    {
        $otherPackage = CreditPackage::factory()->for(Merchant::factory()->create())->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateCreditPackageEndpoint($otherPackage->id), [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testStaffCannotUpdateCreditPackage(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateCreditPackageEndpoint((string) $this->package->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testReturnsNotFoundForNonExistentPackage(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateCreditPackageEndpoint(Str::uuid()->toString()), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[DataProvider('invalidPayloads')]
    public function testOwnerCannotUpdateWithInvalidData(array $payload, array $expectedErrors): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateCreditPackageEndpoint((string) $this->package->id), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidPayloads(): array
    {
        return [
            'name too long' => [
                'payload' => ['name' => str_repeat('a', 101)],
                'expectedErrors' => ['name'],
            ],
            'credit_amount below minimum' => [
                'payload' => ['credit_amount' => 0],
                'expectedErrors' => ['creditAmount'],
            ],
            'selling_price below minimum' => [
                'payload' => ['selling_price' => 0],
                'expectedErrors' => ['sellingPrice'],
            ],
            'is_active not boolean' => [
                'payload' => ['is_active' => 'yes'],
                'expectedErrors' => ['isActive'],
            ],
        ];
    }

    public function testUpdateCreditPackageRequiresAuthentication(): void
    {
        $response = $this->patchJson(self::updateCreditPackageEndpoint((string) $this->package->id), [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotUpdateCreditPackage(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->patchJson(self::updateCreditPackageEndpoint((string) $this->package->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
