<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * "/" tidak punya landing page publik — langsung arahkan ke login panel
     * Filament.
     */
    public function test_root_redirects_to_the_filament_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin/login');
    }
}
