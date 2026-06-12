<?php

declare(strict_types=1);

namespace Tests\Feature\Broadcasting;

use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Traits\BroadcastingEndpoints;
use Tests\TestCase;

class MerchantLocationChannelAuthorizationTest extends TestCase
{
    use BroadcastingEndpoints;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    private MerchantLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create();
        $this->location = MerchantLocation::factory()->active()->for($this->merchant)->create();
        $this->merchantUser = MerchantUser::factory()
            ->active()
            ->for($this->merchant)
            ->for($this->location)
            ->create();
    }

    public function testMerchantUserCanAuthenticateToTheirLocation(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::broadcastAuthEndpoint(), [
                'socket_id' => '123.456',
                'channel_name' => $this->getChannel($this->location->id),
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(['auth']);
    }

    public function testMerchantUserCannotAuthenticateToADifferentLocation(): void
    {
        $otherLocation = MerchantLocation::factory()->active()->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->postJson(self::broadcastAuthEndpoint(), [
                'socket_id' => '123.456',
                'channel_name' => $this->getChannel($otherLocation->id),
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testMerchantUserWithoutLocationCannotAuthenticate(): void
    {
        $userWithoutLocation = MerchantUser::factory()->active()->for($this->merchant)->create([
            'merchant_location_id' => null,
        ]);

        $response = $this->actingAs($userWithoutLocation, self::MERCHANT_USER_GUARD)
            ->postJson(self::broadcastAuthEndpoint(), [
                'socket_id' => '123.456',
                'channel_name' => $this->getChannel($this->location->id),
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAnonymousRequestCannotAccessChannel(): void
    {
        $response = $this->postJson(self::broadcastAuthEndpoint(), [
            'socket_id' => '123.456',
            'channel_name' => $this->getChannel($this->location->id),
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testCustomerCannotAccessMerchantChannel(): void
    {
        $response = $this->actingAs(User::factory()->create(), self::USER_GUARD)
            ->postJson(self::broadcastAuthEndpoint(), [
                'socket_id' => '123.456',
                'channel_name' => $this->getChannel($this->location->id),
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    private function getChannel(string $locationId): string
    {
        return "private-merchant-location.{$locationId}";
    }
}
