<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class CreateMerchantStaffTest extends TestCase
{
    use MerchantPortalEndpoints;

    private const string VALID_PASSWORD = 'SecurePass123!@#';

    private const array VALID_PAYLOAD = [
        'name' => 'Jane Staff',
        'username' => 'janestaff',
        'password' => self::VALID_PASSWORD,
    ];

    private const array VALID_MERCHANT_STAFF_RESPONSE_FIELDS = [
        'id',
        'merchantId',
        'merchantLocationId',
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
    ];

    private MerchantUser $owner;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testOwnerCanCreateStaffUser(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), self::VALID_PAYLOAD);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson(['message' => 'Merchant user created successfully.'])
            ->assertJsonStructure(['data' => self::VALID_MERCHANT_STAFF_RESPONSE_FIELDS])
            ->assertJsonPath('data.username', 'janestaff')
            ->assertJsonPath('data.role', MerchantUserRole::STAFF->value)
            ->assertJsonPath('data.status', MerchantUserStatus::ACTIVE->value)
            ->assertJsonPath('data.merchantId', $this->merchant->id);

        $this->assertDatabaseHas('merchant_users', [
            'merchant_id' => $this->merchant->id,
            'username' => 'janestaff',
            'role' => MerchantUserRole::STAFF->value,
            'status' => MerchantUserStatus::ACTIVE->value,
        ]);
    }

    public function testUsernameIsNormalisedToLowercase(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [...self::VALID_PAYLOAD, 'username' => 'JaneStaff']);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.username', 'janestaff');

        $this->assertDatabaseHas('merchant_users', [
            'username' => 'janestaff',
        ]);
    }

    public function testOwnerCanCreateStaffUserWithPhone(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [...self::VALID_PAYLOAD, 'phone' => '+60123456789']);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('merchant_users', [
            'username' => 'janestaff',
            'phone' => '+60123456789',
        ]);
    }

    public function testOwnerCanCreateStaffWithLocation(): void
    {
        $location = MerchantLocation::factory()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [...self::VALID_PAYLOAD, 'merchantLocationId' => $location->id]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.merchantLocationId', $location->id);

        $this->assertDatabaseHas('merchant_users', [
            'username' => 'janestaff',
            'merchant_location_id' => $location->id,
        ]);
    }

    public function testMerchantLocationIdIsNullWhenNotProvided(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), self::VALID_PAYLOAD);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.merchantLocationId', null);
    }

    public function testOwnerCannotAssignStaffToAnotherMerchantsLocation(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherLocation = MerchantLocation::factory()->for($otherMerchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [...self::VALID_PAYLOAD, 'merchantLocationId' => $otherLocation->id]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['merchantLocationId']);
    }

    public function testOwnerCannotAssignStaffToARemovedLocation(): void
    {
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();
        $location->delete();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [...self::VALID_PAYLOAD, 'merchantLocationId' => $location->id]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['merchantLocationId']);
    }

    public function testUsernamesMustBeUniqueWithinMerchant(): void
    {
        MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'username' => 'janestaff',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [...self::VALID_PAYLOAD, 'name' => 'Another Jane']);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['username']);
    }

    public function testUsernameCanBeReusedAfterStaffIsRemoved(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'username' => 'janestaff',
        ]);
        $staff->delete();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [...self::VALID_PAYLOAD, 'name' => 'New Jane']);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function testSameUsernameCanExistAcrossDifferentMerchants(): void
    {
        $otherMerchant = Merchant::factory()->create();
        MerchantUser::factory()->staff()->active()->for($otherMerchant)->create([
            'username' => 'janestaff',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), self::VALID_PAYLOAD);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    #[DataProvider('providesInvalidStaffPayload')]
    public function testOwnerCannotCreateStaffWithInvalidData(array $payload, array $expectedErrors): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function providesInvalidStaffPayload(): array
    {
        return [
            'missing all fields' => [
                'payload' => [],
                'expectedErrors' => ['name', 'username', 'password'],
            ],
            'missing name' => [
                'payload' => array_diff_key(self::VALID_PAYLOAD, ['name' => null]),
                'expectedErrors' => ['name'],
            ],
            'missing username' => [
                'payload' => array_diff_key(self::VALID_PAYLOAD, ['username' => null]),
                'expectedErrors' => ['username'],
            ],
            'missing password' => [
                'payload' => array_diff_key(self::VALID_PAYLOAD, ['password' => null]),
                'expectedErrors' => ['password'],
            ],
            'weak password' => [
                'payload' => [...self::VALID_PAYLOAD, 'password' => 'weak'],
                'expectedErrors' => ['password'],
            ],
            'password too short' => [
                'payload' => [...self::VALID_PAYLOAD, 'password' => 'Short1!'],
                'expectedErrors' => ['password'],
            ],
            'password without symbols' => [
                'payload' => [...self::VALID_PAYLOAD, 'password' => 'NoSymbols123'],
                'expectedErrors' => ['password'],
            ],
            'password without numbers' => [
                'payload' => [...self::VALID_PAYLOAD, 'password' => 'NoNumbers!@#'],
                'expectedErrors' => ['password'],
            ],
            'password without mixed case' => [
                'payload' => [...self::VALID_PAYLOAD, 'password' => 'nouppercase123!'],
                'expectedErrors' => ['password'],
            ],
            'username with spaces' => [
                'payload' => [...self::VALID_PAYLOAD, 'username' => 'jane staff'],
                'expectedErrors' => ['username'],
            ],
            'username with special characters' => [
                'payload' => [...self::VALID_PAYLOAD, 'username' => 'jane@staff'],
                'expectedErrors' => ['username'],
            ],
            'invalid location id' => [
                'payload' => [...self::VALID_PAYLOAD, 'merchantLocationId' => Str::uuid()->toString()],
                'expectedErrors' => ['merchantLocationId'],
            ],
        ];
    }

    public function testStaffCannotCreateStaffUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), self::VALID_PAYLOAD);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Only the merchant owner can perform this action.']);
    }

    public function testCreateMerchantUserRequiresAuthentication(): void
    {
        $response = $this->postJson(self::createMerchantStaffEndpoint(), self::VALID_PAYLOAD);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotCreateMerchantUser(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson(self::createMerchantStaffEndpoint(), self::VALID_PAYLOAD);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
