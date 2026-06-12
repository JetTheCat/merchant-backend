<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class GetAuthenticatedMerchantUserTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    private const array VALID_STRUCTURE = [
        'data' => [
            'id',
            'merchantId',
            'merchant',
            'name',
            'username',
            'email',
            'phone',
            'role',
            'roleLabel',
            'status',
            'statusLabel',
            'emailVerifiedAt',
            'lastLoginAt',
            'createdAt',
            'updatedAt',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testReturnsAuthenticatedMerchantUserDetails(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getAuthenticatedMerchantUserEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_STRUCTURE)
            ->assertJsonPath('data.id', $this->merchantUser->id)
            ->assertJsonPath('data.merchantId', $this->merchant->id)
            ->assertJsonPath('data.merchant.id', $this->merchant->id)
            ->assertJsonPath('data.merchant.code', $this->merchant->code);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::getAuthenticatedMerchantUserEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotAccessThisEndpoint(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::getAuthenticatedMerchantUserEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
