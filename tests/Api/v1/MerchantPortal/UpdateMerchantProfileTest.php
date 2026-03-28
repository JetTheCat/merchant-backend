<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class UpdateMerchantProfileTest extends TestCase
{
    use MerchantPortalEndpoints;

    private const VALID_MERCHANT_PROFILE_RESPONSE_FIELDS = [
        'id',
        'name',
        'businessName',
        'businessRegistrationNumber',
        'category',
        'description',
        'logoUrl',
        'coverImageUrl',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'postcode',
        'latitude',
        'longitude',
        'platformFeeRate',
        'formattedPlatformFeeRate',
        'settlementCycle',
        'status',
        'bankName',
        'bankAccountNumber',
        'bankAccountName',
        'createdAt',
        'updatedAt',
    ];

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

    public function testOwnerCanUpdateMerchantProfile(): void
    {
        $payload = [
            'name' => 'HeyTea',
            'description' => 'we sell bubble tea :)',
            'logo_url' => null,
            'cover_image_url' => null,
            'phone' => '0416982330',
            'website' => null,
            'address' => 'No. 27 Jalan Setia Nusantara U13/19E',
            'city' => 'Shah Alam',
            'state' => 'Selangor',
            'postcode' => '40170',
        ];

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantProfileEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'data' => self::VALID_MERCHANT_PROFILE_RESPONSE_FIELDS,
            ])
            ->assertJson([
                'message' => 'Merchant profile updated successfully',
                'data' => [
                    'name' => 'HeyTea',
                    'description' => 'we sell bubble tea :)',
                    'phone' => '0416982330',
                    'address' => 'No. 27 Jalan Setia Nusantara U13/19E',
                    'city' => 'Shah Alam',
                    'state' => 'Selangor',
                    'postcode' => '40170',
                ],
            ]);
    }

    public function testOwnerCanPartiallyUpdateMerchantProfile(): void
    {
        $originalName = $this->merchant->name;

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantProfileEndpoint(), [
                'description' => 'Only updating description.',
            ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->merchant->refresh();
        $this->assertEquals($originalName, $this->merchant->name);
        $this->assertEquals('Only updating description.', $this->merchant->description);
    }

    public function testOwnerCanClearNullableFields(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantProfileEndpoint(), [
                'description' => null,
                'website' => null,
            ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->merchant->refresh();
        $this->assertNull($this->merchant->description);
        $this->assertNull($this->merchant->website);
    }

    public function testStaffCannotUpdateMerchantProfile(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantProfileEndpoint(), [
                'name' => 'Staff Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateDoesNotAffectAdminOnlyFields(): void
    {
        $originalFeeRate = $this->merchant->platform_fee_rate;
        $originalStatus = $this->merchant->status;

        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantProfileEndpoint(), [
                'name' => 'Updated Name',
                'platform_fee_rate' => '0.99',
                'status' => 'suspended',
                'settlement_cycle' => 'weekly',
            ]);

        $this->merchant->refresh();
        $this->assertEquals($originalFeeRate, $this->merchant->platform_fee_rate);
        $this->assertEquals($originalStatus, $this->merchant->status);
    }

    #[DataProvider('invalidUpdateData')]
    public function testOwnerCannotUpdateWithInvalidData(array $payload, array $expectedErrors): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantProfileEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidUpdateData(): array
    {
        return [
            'name too long' => [
                'payload' => ['name' => str_repeat('a', 256)],
                'expectedErrors' => ['name'],
            ],
            'description too long' => [
                'payload' => ['description' => str_repeat('a', 1001)],
                'expectedErrors' => ['description'],
            ],
            'invalid logo url' => [
                'payload' => ['logo_url' => 'not-a-valid-url'],
                'expectedErrors' => ['logoUrl'],
            ],
            'invalid cover image url' => [
                'payload' => ['cover_image_url' => 'not-a-valid-url'],
                'expectedErrors' => ['coverImageUrl'],
            ],
            'invalid website url' => [
                'payload' => ['website' => 'not-a-valid-url'],
                'expectedErrors' => ['website'],
            ],
            'phone too long' => [
                'payload' => ['phone' => str_repeat('1', 21)],
                'expectedErrors' => ['phone'],
            ],
            'address too long' => [
                'payload' => ['address' => str_repeat('a', 256)],
                'expectedErrors' => ['address'],
            ],
            'city too long' => [
                'payload' => ['city' => str_repeat('a', 101)],
                'expectedErrors' => ['city'],
            ],
            'state too long' => [
                'payload' => ['state' => str_repeat('a', 101)],
                'expectedErrors' => ['state'],
            ],
            'postcode too long' => [
                'payload' => ['postcode' => str_repeat('1', 11)],
                'expectedErrors' => ['postcode'],
            ],
        ];
    }

    public function testUpdateRequiresAuthentication(): void
    {
        $response = $this->patchJson(self::patchMerchantProfileEndpoint(), [
            'name' => 'Unauthenticated update',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
