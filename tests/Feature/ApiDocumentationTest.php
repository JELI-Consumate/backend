<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ApiDocumentationTest extends TestCase
{
    public function test_swagger_ui_loads(): void
    {
        $this->get('/api/documentation')->assertOk();
    }

    public function test_openapi_json_lists_all_v1_endpoints(): void
    {
        $response = $this->get('/docs?api-docs.json');

        $response->assertOk();

        $paths = $response->json('paths');

        $this->assertIsArray($paths);
        $this->assertArrayHasKey('/sectors', $paths);
        $this->assertArrayHasKey('/auth/login', $paths);
        $this->assertArrayHasKey('/badges', $paths);
        $this->assertArrayHasKey('/empowerment-index', $paths);
        $this->assertGreaterThanOrEqual(24, count($paths));
    }
}
