<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\CreditPackage;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class DeleteCreditPackageTest extends TestCase
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

    public function testOwnerCanDeleteCreditPackage(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteCreditPackageEndpoint((string) $this->package->id));

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('credit_packages', ['id' => $this->package->id]);
    }

    public function testOwnerCannotDeletePackageFromAnotherMerchant(): void
    {
        $otherPackage = CreditPackage::factory()->for(Merchant::factory()->create())->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteCreditPackageEndpoint($otherPackage->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);

        $this->assertNotSoftDeleted('credit_packages', ['id' => $otherPackage->id]);
    }

    public function testStaffCannotDeleteCreditPackage(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteCreditPackageEndpoint((string) $this->package->id));

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNotSoftDeleted('credit_packages', ['id' => $this->package->id]);
    }

    public function testReturnsNotFoundForNonExistentPackage(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteCreditPackageEndpoint(Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteCreditPackageRequiresAuthentication(): void
    {
        $response = $this->deleteJson(self::deleteCreditPackageEndpoint((string) $this->package->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotDeleteCreditPackage(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->deleteJson(self::deleteCreditPackageEndpoint((string) $this->package->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
