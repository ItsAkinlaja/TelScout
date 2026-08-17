<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\CandidateSkill;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\JobSkill;
use App\Models\User;
use App\Services\MatchScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchScoringTest extends TestCase
{
    use RefreshDatabase;

    private MatchScoringService $scorer;
    private CandidateProfile $profile;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new MatchScoringService();

        $user = User::factory()->create();
        $this->profile = CandidateProfile::create([
            'user_id'             => $user->id,
            'full_name'           => 'Test Dev',
            'primary_title'       => 'Full Stack Developer',
            'work_preference'     => 'remote',
            'years_of_experience' => 3,
        ]);

        foreach (['react', 'laravel', 'node.js', 'mysql', 'typescript'] as $skill) {
            CandidateSkill::create(['candidate_profile_id' => $this->profile->id, 'skill' => $skill]);
        }

        $this->company = Company::create(['name' => 'Test Co', 'website' => 'https://testco.com']);
    }

    public function test_perfect_skill_match_scores_high(): void
    {
        $job = JobListing::create([
            'company_id' => $this->company->id,
            'title'      => 'Full Stack Developer',
            'is_remote'  => true,
        ]);

        foreach (['react', 'laravel', 'mysql'] as $skill) {
            JobSkill::create(['job_listing_id' => $job->id, 'skill' => $skill]);
        }

        $result = $this->scorer->score($this->profile, $job->load(['skills', 'company']));

        $this->assertGreaterThanOrEqual(80, $result['score']);
        $this->assertContains('react', $result['matched_skills']);
        $this->assertContains('laravel', $result['matched_skills']);
    }

    public function test_no_skill_match_scores_low(): void
    {
        $job = JobListing::create([
            'company_id' => $this->company->id,
            'title'      => 'Data Scientist',
            'is_remote'  => true,
        ]);

        foreach (['python', 'tensorflow', 'spark', 'hadoop'] as $skill) {
            JobSkill::create(['job_listing_id' => $job->id, 'skill' => $skill]);
        }

        $result = $this->scorer->score($this->profile, $job->load(['skills', 'company']));

        $this->assertLessThan(60, $result['score']);
    }

    public function test_score_has_required_fields(): void
    {
        $job = JobListing::create(['company_id' => $this->company->id, 'title' => 'Dev', 'is_remote' => true]);

        $result = $this->scorer->score($this->profile, $job->load(['skills', 'company']));

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('classification', $result);
        $this->assertArrayHasKey('matched_skills', $result);
        $this->assertArrayHasKey('missing_skills', $result);
        $this->assertArrayHasKey('reasoning', $result);
        $this->assertArrayHasKey('score_breakdown', $result);
    }

    public function test_classification_categories_are_correct(): void
    {
        $this->assertEquals('excellent', $this->callClassify(95));
        $this->assertEquals('strong',    $this->callClassify(85));
        $this->assertEquals('good',      $this->callClassify(75));
        $this->assertEquals('possible',  $this->callClassify(65));
        $this->assertEquals('low',       $this->callClassify(50));
    }

    private function callClassify(float $score): string
    {
        $ref = new \ReflectionClass($this->scorer);
        $m   = $ref->getMethod('classify');
        $m->setAccessible(true);
        return $m->invoke($this->scorer, $score);
    }
}
