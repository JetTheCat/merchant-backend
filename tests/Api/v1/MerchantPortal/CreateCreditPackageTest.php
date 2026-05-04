<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class CreateCreditPackageTest extends TestCase
{
    use MerchantPortalEndpoints;

    private MerchantUser $owner;

    private Merchant $merchant;

    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
        $this->validPayload = [
            'name' => 'RM 100 Dining Credit',
            'description' => 'Get RM 100 credits for RM 90.',
            'credit_amount' => 100.00,
            'selling_price' => 90.00,
            'purchase_limit_per_user' => null,
            'total_quantity' => null,
        ];
    }

    public function testOwnerCanCreateCreditPackage(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createCreditPackageEndpoint(), $this->validPayload);

        $response->assertStatus(Response::HTTP_CREATED)
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
                    'name' => 'RM 100 Dining Credit',
                    'creditAmount' => '100.00',
                    'sellingPrice' => '90.00',
                    'isActive' => false,
                    'soldQuantity' => 0,
                ],
            ]);

        $this->assertDatabaseHas('credit_packages', [
            'merchant_id' => $this->merchant->id,
            'name' => 'RM 100 Dining Credit',
            'is_active' => false,
        ]);
    }

    public function testPackageDefaultsToInactive(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createCreditPackageEndpoint(), $this->validPayload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson(['data' => ['isActive' => false]]);
    }

    public function testOwnerCanCreatePackageWithOptionalFields(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createCreditPackageEndpoint(), [
                ...$this->validPayload,
                'purchase_limit_per_user' => 1,
                'total_quantity' => 100,
            ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'data' => [
                    'purchaseLimitPerUser' => 1,
                    'totalQuantity' => 100,
                ],
            ]);
    }

    public function testStaffCannotCreateCreditPackage(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->postJson(self::createCreditPackageEndpoint(), $this->validPayload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[DataProvider('invalidPayloads')]
    public function testOwnerCannotCreateWithInvalidData(array $payload, array $expectedErrors): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createCreditPackageEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidPayloads(): array
    {
        return [
            'missing name' => [
                'payload' => ['credit_amount' => 100, 'selling_price' => 90],
                'expectedErrors' => ['name'],
            ],
            'missing credit_amount' => [
                'payload' => ['name' => 'Test', 'selling_price' => 90],
                'expectedErrors' => ['creditAmount'],
            ],
            'missing selling_price' => [
                'payload' => ['name' => 'Test', 'credit_amount' => 100],
                'expectedErrors' => ['sellingPrice'],
            ],
            'credit_amount below minimum' => [
                'payload' => ['name' => 'Test', 'credit_amount' => 0, 'selling_price' => 90],
                'expectedErrors' => ['creditAmount'],
            ],
            'selling_price below minimum' => [
                'payload' => ['name' => 'Test', 'credit_amount' => 100, 'selling_price' => 0],
                'expectedErrors' => ['sellingPrice'],
            ],
            'name too long' => [
                'payload' => ['name' => str_repeat('a', 101), 'credit_amount' => 100, 'selling_price' => 90],
                'expectedErrors' => ['name'],
            ],
        ];
    }

    public function testCreateCreditPackageRequiresAuthentication(): void
    {
        $response = $this->postJson(self::createCreditPackageEndpoint(), $this->validPayload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotCreateCreditPackage(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson(self::createCreditPackageEndpoint(), $this->validPayload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
