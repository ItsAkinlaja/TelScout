<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_companies(): void
    {
        Company::factory()->count(3)->create();

        $this->actingAs($this->user)->getJson('/api/companies')
            ->assertOk()
            ->assertJsonStructure(['data', 'total', 'current_page']);
    }

    public function test_can_create_company(): void
    {
        $res = $this->actingAs($this->user)->postJson('/api/companies', [
            'name'    => 'Acme Corp',
            'website' => 'https://acme.example.com',
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('companies', ['name' => 'Acme Corp']);
    }

    public function test_domain_deduplication_prevents_duplicates(): void
    {
        // Create company via normalized domain
        $company1 = Company::findOrCreateByDomain([
            'name'    => 'Acme',
            'website' => 'https://www.acme.example.com',
        ]);

        $company2 = Company::findOrCreateByDomain([
            'name'    => 'Acme Corp',
            'website' => 'http://acme.example.com/',
        ]);

        // Same normalized domain → same record
        $this->assertEquals($company1->id, $company2->id);
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_normalize_domain_strips_www_and_trailing_slash(): void
    {
        $this->assertEquals('example.com', Company::normalizeDomain('https://www.example.com/'));
        $this->assertEquals('example.com', Company::normalizeDomain('http://example.com'));
        $this->assertEquals('sub.example.com', Company::normalizeDomain('https://sub.example.com/path'));
    }

    public function test_can_exclude_company(): void
    {
        $company = Company::factory()->create(['is_excluded' => false]);

        $this->actingAs($this->user)->postJson("/api/companies/{$company->id}/exclude")->assertOk();

        $this->assertTrue($company->fresh()->is_excluded);
    }
}
