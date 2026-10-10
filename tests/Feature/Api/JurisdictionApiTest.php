<?php

namespace Tests\Feature\Api;

use App\Models\SolarInstallation;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class JurisdictionApiTest extends ApiTestCase
{
    private function headersFor(User $user): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenFor($user)];
    }

    #[Test]
    public function a_national_operator_sees_every_resource(): void
    {
        $this->authGet('/api/provinces')->assertJsonPath('meta.total', 9);
        $this->authGet('/api/districts')->assertJsonPath('meta.total', 25);
        $this->authGet('/api/installations')->assertJsonPath('meta.total', 216);
        $this->authGet('/api/grid-substations')->assertJsonPath('meta.total', 24);
    }

    #[Test]
    public function a_provincial_operator_only_sees_their_own_province_and_descendants(): void
    {
        $provincial = User::factory()->provincial(1)->create();
        $headers = $this->headersFor($provincial);

        $this->getJson('/api/provinces', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'WP');

        $this->getJson('/api/districts', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/installations', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 36);

        $this->getJson('/api/grid-substations', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 4);
    }

    #[Test]
    public function a_provincial_operator_cannot_read_another_province_or_its_districts(): void
    {
        $provincial = User::factory()->provincial(1)->create();
        $headers = $this->headersFor($provincial);

        $this->getJson('/api/provinces/2', $headers)->assertNotFound();
        $this->getJson('/api/districts/4', $headers)->assertNotFound();
        $this->getJson('/api/provinces/2/districts', $headers)->assertNotFound();
    }

    #[Test]
    public function a_district_operator_only_sees_their_own_district(): void
    {
        $district = User::factory()->district(1)->create();
        $headers = $this->headersFor($district);

        $this->getJson('/api/districts', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Colombo');

        $this->getJson('/api/provinces', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'WP');

        $this->getJson('/api/installations', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 18);

        $this->getJson('/api/grid-substations', $headers)
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function a_district_operator_cannot_read_sibling_districts_or_their_installations(): void
    {
        $district = User::factory()->district(1)->create();
        $headers = $this->headersFor($district);

        $this->getJson('/api/districts/2', $headers)->assertNotFound();
        $this->getJson('/api/provinces/2', $headers)->assertNotFound();
        $this->getJson('/api/districts/2/grid-substations', $headers)->assertNotFound();
        $this->getJson('/api/districts/4/generation-summary', $headers)->assertNotFound();
    }

    #[Test]
    public function out_of_jurisdiction_installations_and_readings_are_treated_as_missing(): void
    {
        $district = User::factory()->district(1)->create();
        $headers = $this->headersFor($district);

        $foreignInstallation = SolarInstallation::query()
            ->whereHas('gridSubstation', fn ($q) => $q->where('district_id', '!=', 1))
            ->firstOrFail();

        $this->getJson('/api/installations/'.$foreignInstallation->id, $headers)->assertNotFound();
        $this->getJson('/api/installations/'.$foreignInstallation->id.'/latest-reading', $headers)->assertNotFound();
        $this->getJson('/api/installations/'.$foreignInstallation->id.'/readings', $headers)->assertNotFound();
    }

    #[Test]
    public function a_provincial_operator_sees_the_scoped_district_count_on_a_province(): void
    {
        $provincial = User::factory()->provincial(1)->create();
        $headers = $this->headersFor($provincial);

        $this->getJson('/api/provinces/1', $headers)
            ->assertOk()
            ->assertJsonPath('data.districts_count', 3);
    }
}
