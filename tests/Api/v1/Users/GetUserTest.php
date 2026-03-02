<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Models\User;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class GetUserTest extends TestCase
{
    use UserEndpoints;

    private const VALID_USER_RESPONSE_FIELDS = [
        'id',
        'firstName',
        'lastName',
        'email',
        'emailVerifiedAt',
        'phone',
        'createdAt',
        'updatedAt',
    ];

    public function testAuthenticatedUserCanGetTheirProfile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+60123456789',
        ]);

        $response = $this->actingAs($user)
            ->getJson(self::getUserEndpoint());

        $response->assertStatus(200)
            ->assertJsonStructure(self::VALID_USER_RESPONSE_FIELDS)
            ->assertJson([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '+60123456789',
            ]);
    }

    public function testUnauthenticatedUserCannotGetProfile(): void
    {
        $response = $this->getJson(self::getUserEndpoint());

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function testUserProfileDoesNotExposePassword(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(self::getUserEndpoint());

        $response->assertStatus(200)
            ->assertJsonMissing(['password'])
            ->assertJsonMissing(['remember_token']);
    }
}
