<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginAvailabilityTest extends TestCase
{
    public function test_login_page_is_available_without_a_server_error(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Login - SIGEF', escape: false)
            ->assertSee('Entrar');
    }
}
