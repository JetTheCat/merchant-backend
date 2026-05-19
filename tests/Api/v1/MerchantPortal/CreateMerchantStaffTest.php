<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class CreateMerchantStaffTest extends TestCase
{
    use MerchantPortalEndpoints;

    private const VALID_PASSWORD = 'SecurePass123!@#';

    private const VALID_MERCHANT_STAFF_RESPONSE_FIELDS = [
        'id',
        'merchantId',
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
            ->postJson(self::createMerchantStaffEndpoint(), [
                'name' => 'Jane Staff',
                'username' => 'janestaff',
                'password' => self::VALID_PASSWORD,
            ]);

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

    public function testOwnerCanCreateStaffUserWithPhone(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [
                'name' => 'Jane Staff',
                'username' => 'janestaff',
                'password' => self::VALID_PASSWORD,
                'phone' => '+60123456789',
            ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('merchant_users', [
            'username' => 'janestaff',
            'phone' => '+60123456789',
        ]);
    }

    public function testUsernamesMustBeUniqueWithinMerchant(): void
    {
        MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'username' => 'janestaff',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [
                'name' => 'Another Jane',
                'username' => 'janestaff',
                'password' => self::VALID_PASSWORD,
            ]);

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
            ->postJson(self::createMerchantStaffEndpoint(), [
                'name' => 'New Jane',
                'username' => 'janestaff',
                'password' => self::VALID_PASSWORD,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function testSameUsernameCanExistAcrossDifferentMerchants(): void
    {
        $otherMerchant = Merchant::factory()->create();
        MerchantUser::factory()->staff()->active()->for($otherMerchant)->create([
            'username' => 'janestaff',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [
                'name' => 'Jane Staff',
                'username' => 'janestaff',
                'password' => self::VALID_PASSWORD,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    #[DataProvider('invalidStaffPayloads')]
    public function testOwnerCannotCreateStaffWithInvalidData(array $payload, array $expectedErrors): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidStaffPayloads(): array
    {
        return [
            'missing all fields' => [
                'payload' => [],
                'expectedErrors' => ['name', 'username', 'password'],
            ],
            'missing name' => [
                'payload' => ['username' => 'janestaff', 'password' => 'SecurePass123!@#'],
                'expectedErrors' => ['name'],
            ],
            'missing username' => [
                'payload' => ['name' => 'Jane Staff', 'password' => 'SecurePass123!@#'],
                'expectedErrors' => ['username'],
            ],
            'missing password' => [
                'payload' => ['name' => 'Jane Staff', 'username' => 'janestaff'],
                'expectedErrors' => ['password'],
            ],
            'weak password' => [
                'payload' => ['name' => 'Jane Staff', 'username' => 'janestaff', 'password' => 'weak'],
                'expectedErrors' => ['password'],
            ],
            'password too short' => [
                'payload' => ['name' => 'Jane Staff', 'username' => 'janestaff', 'password' => 'Short1!'],
                'expectedErrors' => ['password'],
            ],
            'password without symbols' => [
                'payload' => ['name' => 'Jane Staff', 'username' => 'janestaff', 'password' => 'NoSymbols123'],
                'expectedErrors' => ['password'],
            ],
            'password without numbers' => [
                'payload' => ['name' => 'Jane Staff', 'username' => 'janestaff', 'password' => 'NoNumbers!@#'],
                'expectedErrors' => ['password'],
            ],
            'password without mixed case' => [
                'payload' => ['name' => 'Jane Staff', 'username' => 'janestaff', 'password' => 'nouppercase123!'],
                'expectedErrors' => ['password'],
            ],
            'username with spaces' => [
                'payload' => ['name' => 'Jane Staff', 'username' => 'jane staff', 'password' => 'SecurePass123!@#'],
                'expectedErrors' => ['username'],
            ],
            'username with special characters' => [
                'payload' => ['name' => 'Jane Staff', 'username' => 'jane@staff', 'password' => 'SecurePass123!@#'],
                'expectedErrors' => ['username'],
            ],
        ];
    }

    public function testStaffCannotCreateStaffUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->postJson(self::createMerchantStaffEndpoint(), [
                'name' => 'New Staff',
                'username' => 'newstaff',
                'password' => self::VALID_PASSWORD,
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Only the merchant owner can perform this action.']);
    }

    public function testCreateMerchantUserRequiresAuthentication(): void
    {
        $response = $this->postJson(self::createMerchantStaffEndpoint(), [
            'name' => 'New Staff',
            'username' => 'newstaff',
            'password' => self::VALID_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotCreateMerchantUser(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson(self::createMerchantStaffEndpoint(), [
                'name' => 'New Staff',
                'username' => 'newstaff',
                'password' => self::VALID_PASSWORD,
            ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
