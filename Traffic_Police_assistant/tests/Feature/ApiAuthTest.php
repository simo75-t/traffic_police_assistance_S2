<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    public function test_profile_route_returns_unauthorized_without_token()
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    public function test_login_route_requires_credentials()
    {
        $this->postJson('/api/login', [])->assertStatus(422);
    }
}
