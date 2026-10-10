<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;

class HttpSemanticsTest extends ApiTestCase
{
    #[Test]
    public function non_json_accept_header_is_rejected_with_406(): void
    {
        $this->get('/api/provinces', ['Accept' => 'text/html'])
            ->assertStatus(406)
            ->assertJsonPath('error.code', 'not_acceptable');
    }

    #[Test]
    public function wildcard_accept_header_is_served_as_json(): void
    {
        $this->get('/api/provinces', $this->operatorHeaders(['Accept' => '*/*']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');
    }

    #[Test]
    public function error_responses_use_a_consistent_json_envelope(): void
    {
        $response = $this->authGet('/api/provinces/123456789');

        $response->assertNotFound()
            ->assertJsonStructure(['error' => ['status', 'code', 'message', 'details']])
            ->assertJsonPath('error.status', 404)
            ->assertJsonPath('error.code', 'not_found');
    }

    #[Test]
    public function conditional_get_returns_304_with_empty_body_for_a_matching_etag(): void
    {
        $etag = $this->authGet('/api/provinces/1')->assertOk()->headers->get('ETag');
        $this->assertNotNull($etag);

        $this->authGet('/api/provinces/1', ['If-None-Match' => $etag])
            ->assertStatus(304)
            ->assertNoContent(304);
    }

    #[Test]
    public function conditional_get_returns_304_for_a_matching_last_modified(): void
    {
        $lastModified = $this->authGet('/api/provinces/1')->assertOk()->headers->get('Last-Modified');
        $this->assertNotNull($lastModified);

        $this->authGet('/api/provinces/1', ['If-Modified-Since' => $lastModified])
            ->assertStatus(304)
            ->assertNoContent(304);
    }

    #[Test]
    public function conditional_get_for_a_modified_resource_returns_200(): void
    {
        $this->authGet('/api/provinces/1', ['If-None-Match' => '"definitely-a-stale-etag"'])
            ->assertOk();
    }

    #[Test]
    public function get_responses_advertise_etag_support_via_vary_header(): void
    {
        $this->authGet('/api/installations/1/readings?per_page=2')
            ->assertOk()
            ->assertHeader('Vary', 'Accept');
    }

    #[Test]
    public function update_with_a_stale_if_match_header_returns_412(): void
    {
        $this->putJson('/api/provinces/1', ['name' => 'Western 2'], $this->administratorHeaders([
            'If-Match' => '"stale"',
        ]))->assertStatus(412)
            ->assertJsonPath('error.code', 'precondition_failed');
    }

    #[Test]
    public function update_with_a_matching_if_match_header_succeeds(): void
    {
        $etag = $this->authGet('/api/provinces/1')->assertOk()->headers->get('ETag');

        $this->putJson('/api/provinces/1', ['name' => 'Western Updated'], $this->administratorHeaders([
            'If-Match' => $etag,
        ]))->assertOk()
            ->assertJsonPath('data.name', 'Western Updated');
    }

    #[Test]
    public function update_with_a_stale_if_unmodified_since_header_returns_412(): void
    {
        $this->putJson('/api/provinces/1', ['name' => 'Western 3'], $this->administratorHeaders([
            'If-Unmodified-Since' => 'Mon, 01 Jan 1990 00:00:00 GMT',
        ]))->assertStatus(412)
            ->assertJsonPath('error.code', 'precondition_failed');
    }

    #[Test]
    public function administrative_write_without_credentials_is_denied_by_default(): void
    {
        $this->postJson('/api/provinces', ['name' => 'Atlantis', 'code' => 'ATL'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function administrative_write_with_an_invalid_token_returns_401(): void
    {
        $this->postJson('/api/provinces', ['name' => 'Atlantis', 'code' => 'ATL'], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function write_endpoints_send_401_when_no_credentials_are_given(): void
    {
        $this->postJson('/api/provinces', ['name' => 'Atlantis', 'code' => 'ATL'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }
}
