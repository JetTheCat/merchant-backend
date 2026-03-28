<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class GetMerchantProfileTest extends TestCase
{
    use MerchantPortalEndpoints;

    private MerchantUser $owner;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);
    }

    public function testOwnerCanGetMerchantProfile(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantProfileEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', $this->merchant->id)
            ->assertJsonPath('data.name', $this->merchant->name)
            ->assertJsonStructure(['data' => ['id', 'name', 'businessName', 'status']]);
    }

    public function testStaffCanGetMerchantProfile(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantProfileEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', $this->merchant->id);
    }

    public function testGetMerchantProfileRequiresAuthentication(): void
    {
        $response = $this->getJson(self::getMerchantProfileEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testOwnerSeesFullBankAccountNumber(): void
    {
        $merchant = Merchant::factory()->create([
            'bank_account_number' => '1234567890',
        ]);
        $owner = MerchantUser::factory()->owner()->active()->create([
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->actingAs($owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantProfileEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.bankAccountNumber', '1234567890');
    }

    public function testStaffSeesMaskedBankAccountNumber(): void
    {
        $merchant = Merchant::factory()->create([
            'bank_account_number' => '1234567890',
        ]);
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantProfileEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.bankAccountNumber', '****7890');
    }

    public function testCustomerCannotAccessMerchantProfile(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson(self::getMerchantProfileEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
