<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class LookupRedemptionQrTest extends TestCase
{
    use UserEndpoints;

    private const array VALID_RESPONSE_FIELDS = [
        'merchantId',
        'merchantName',
        'locationId',
        'locationName',
        'availableBalance',
    ];

    private User $user;

    private Merchant $merchant;

    private MerchantLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->merchant = Merchant::factory()->active()->create();
        $this->location = MerchantLocation::factory()
            ->active()
            ->for($this->merchant)
            ->create();
    }

    public function testValidRedemptionCodeReturnsLocationAndBalance(): void
    {
        UserCredit::factory()
            ->withBalance(75.00)
            ->for($this->user)
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($this->user, self::USER_GUARD)
            ->getJson(self::lookupRedemptionQrEndpoint($this->location->redemption_code));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data' => self::VALID_RESPONSE_FIELDS])
            ->assertJsonFragment([
                'merchantId' => $this->merchant->id,
                'merchantName' => $this->merchant->name,
                'locationId' => $this->location->id,
                'locationName' => $this->location->name,
                'availableBalance' => '75.00',
            ]);
    }

    public function testValidQrReturnsZeroBalanceWhenNoWallet(): void
    {
        $response = $this->actingAs($this->user, self::USER_GUARD)
            ->getJson(self::lookupRedemptionQrEndpoint($this->location->redemption_code));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.availableBalance', '0.00');
    }

    public function testRedemptionCodeMustBeUuid(): void
    {
        $response = $this->actingAs($this->user, self::USER_GUARD)
            ->getJson(self::lookupRedemptionQrEndpoint('not-a-uuid'));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testInvalidCodeReturns404(): void
    {
        $response = $this->actingAs($this->user, self::USER_GUARD)
            ->getJson(self::lookupRedemptionQrEndpoint(Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testInactiveLocationReturns404(): void
    {
        $this->location->update(['is_active' => false]);

        $response = $this->actingAs($this->user, self::USER_GUARD)
            ->getJson(self::lookupRedemptionQrEndpoint($this->location->redemption_code));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testSoftDeletedLocationReturns404(): void
    {
        $this->location->delete();

        $response = $this->actingAs($this->user, self::USER_GUARD)
            ->getJson(self::lookupRedemptionQrEndpoint($this->location->redemption_code));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::lookupRedemptionQrEndpoint($this->location->redemption_code));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testMerchantUserCannotAccessEndpoint(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();

        $response = $this->actingAs($merchantUser, 'merchant_user')
            ->getJson(self::lookupRedemptionQrEndpoint($this->location->redemption_code));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
