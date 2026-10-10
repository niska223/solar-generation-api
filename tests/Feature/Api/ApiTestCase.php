<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Base class for API feature tests.
 *
 * Uses DatabaseTransactions so any row created by a test (e.g. an ingested
 * reading or an administratively-created province) is rolled back afterwards
 * and the seeded data is never mutated permanently.
 */
abstract class ApiTestCase extends TestCase
{
    use DatabaseTransactions;

    protected string $operatorToken = 'test-operator-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['api.admin_token' => $this->operatorToken]);
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function operatorHeaders(array $extra = []): array
    {
        return ['Authorization' => 'Bearer '.$this->operatorToken] + $extra;
    }
}
