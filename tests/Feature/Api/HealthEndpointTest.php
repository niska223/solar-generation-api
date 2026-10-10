<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;

class HealthEndpointTest extends ApiTestCase
{
    #[Test]
    public function the_health_endpoint_reports_a_healthy_database_over_https_ready_json(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('database', 'ok')
            ->assertJsonStructure(['status', 'app', 'environment', 'database', 'timestamp']);
    }

    #[Test]
    public function the_health_endpoint_does_not_require_authentication(): void
    {
        $this->get('/api/health', ['Accept' => '*/*'])->assertOk();
    }

    #[Test]
    public function the_platform_liveness_probe_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    #[Test]
    public function the_health_endpoint_is_not_redirected_when_https_is_forced(): void
    {
        config(['app.force_https' => true]);

        $this->get('/api/health', ['Accept' => '*/*'])->assertOk();
        $this->get('/up')->assertOk();
    }
}
