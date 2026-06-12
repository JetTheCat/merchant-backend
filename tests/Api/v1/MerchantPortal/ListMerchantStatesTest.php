<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Constants\CacheTags;
use App\Enums\MalaysianState;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListMerchantStatesTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testOwnerCanListStates(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantStatesEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(count(MalaysianState::cases()), 'data')
            ->assertJsonFragment(['data' => MalaysianState::values()]);
    }

    public function testStaffCanListStates(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantStatesEndpoint())
            ->assertOk();
    }

    public function testStatesAreStoredInCacheAfterFirstRequest(): void
    {
        Cache::flush();

        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantStatesEndpoint())
            ->assertOk();

        $this->assertEquals(
            MalaysianState::values(),
            Cache::tags([CacheTags::STATES])->get('states.all')
        );
    }

    public function testStatesAreReturnedFromCacheOnSubsequentRequest(): void
    {
        $cachedStates = ['Only Selangor'];
        Cache::tags([CacheTags::STATES])->put('states.all', $cachedStates, 3600);

        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantStatesEndpoint())
            ->assertOk()
            ->assertJson(['data' => $cachedStates]);
    }

    public function testListStatesRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listMerchantStatesEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotListStates(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson(self::listMerchantStatesEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
