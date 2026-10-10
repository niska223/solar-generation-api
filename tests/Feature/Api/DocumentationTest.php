<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;

class DocumentationTest extends ApiTestCase
{
    #[Test]
    public function the_openapi_json_document_is_served(): void
    {
        $response = $this->getJson('/api/docs');

        $response->assertOk()
            ->assertJsonPath('openapi', '3.0.0')
            ->assertJsonPath('info.title', 'SLSEA Solar Generation Data API')
            ->assertJsonPath('components.securitySchemes.bearerAuth.type', 'http')
            ->assertJsonPath('components.securitySchemes.bearerAuth.scheme', 'bearer');
    }

    #[Test]
    public function the_openapi_document_describes_the_jwt_secured_endpoints(): void
    {
        $spec = $this->getJson('/api/docs')->json();

        $this->assertArrayHasKey('/api/auth/operator/login', $spec['paths']);
        $this->assertArrayHasKey('/api/auth/device/login', $spec['paths']);
        $this->assertArrayHasKey('/api/installations/{installation}/readings', $spec['paths']);
        $this->assertArrayHasKey('AuthTokenResponse', $spec['components']['schemas']);
        $this->assertArrayHasKey('GenerationReading', $spec['components']['schemas']);
    }

    #[Test]
    public function the_swagger_ui_is_served(): void
    {
        $response = $this->get('/api/documentation');

        $response->assertOk();
        $this->assertStringContainsString('swagger', strtolower((string) $response->getContent()));
    }
}
