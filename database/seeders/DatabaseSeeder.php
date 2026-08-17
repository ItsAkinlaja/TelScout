<?php

namespace Database\Seeders;

use App\Models\AutomationSettings;
use App\Models\CandidateProfile;
use App\Models\CandidateSkill;
use App\Models\CandidateExperience;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create the primary user
        $user = User::firstOrCreate(
            ['email' => 'timileyin@telscout.local'],
            [
                'name'     => 'Timileyin Akinlaja',
                'password' => Hash::make('password'),
            ]
        );

        // Candidate profile
        $profile = CandidateProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name'             => 'Timileyin Akinlaja',
                'primary_title'         => 'Software Engineer / Full Stack Engineer',
                'location'              => 'Lagos, Nigeria',
                'portfolio_url'         => 'https://akinlajatimileyin.dev',
                'summary'               => 'Full-stack software engineer experienced with React, Laravel, Node.js, MySQL and API-driven applications. Built production systems involving authentication, payments, KYC integrations, multi-tenant applications and deployment.',
                'work_preference'       => 'remote',
                'years_of_experience'   => 3,
                'preferred_roles'       => ['Software Engineer', 'Full Stack Developer', 'Backend Developer', 'Frontend Developer'],
                'preferred_locations'   => ['Lagos Nigeria', 'Remote', 'Worldwide'],
                'preferred_industries'  => ['Technology', 'FinTech', 'SaaS', 'EdTech'],
                'excluded_industries'   => [],
                'preferred_technologies'=> ['React', 'Laravel', 'Node.js', 'TypeScript', 'MySQL'],
                'preferred_currencies'  => ['USD', 'GBP', 'EUR', 'NGN'],
            ]
        );

        // Skills
        $skills = [
            ['skill' => 'React',                    'level' => 'advanced'],
            ['skill' => 'JavaScript',               'level' => 'advanced'],
            ['skill' => 'TypeScript',               'level' => 'advanced'],
            ['skill' => 'Laravel',                  'level' => 'advanced'],
            ['skill' => 'PHP',                      'level' => 'advanced'],
            ['skill' => 'Node.js',                  'level' => 'advanced'],
            ['skill' => 'MySQL',                    'level' => 'advanced'],
            ['skill' => 'REST APIs',                'level' => 'advanced'],
            ['skill' => 'HTML',                     'level' => 'expert'],
            ['skill' => 'CSS',                      'level' => 'expert'],
            ['skill' => 'Tailwind CSS',             'level' => 'advanced'],
            ['skill' => 'Git',                      'level' => 'advanced'],
            ['skill' => 'Docker',                   'level' => 'intermediate'],
            ['skill' => 'Next.js',                  'level' => 'intermediate'],
            ['skill' => 'Supabase',                 'level' => 'intermediate'],
            ['skill' => 'Cloudinary',               'level' => 'intermediate'],
            ['skill' => 'Shopify',                  'level' => 'intermediate'],
            ['skill' => 'WordPress',                'level' => 'intermediate'],
            ['skill' => 'API integrations',         'level' => 'advanced'],
            ['skill' => 'Authentication',           'level' => 'advanced'],
            ['skill' => 'Payment integrations',     'level' => 'advanced'],
            ['skill' => 'KYC integrations',         'level' => 'intermediate'],
            ['skill' => 'Multi-tenant applications','level' => 'intermediate'],
            ['skill' => 'Deployment',               'level' => 'intermediate'],
        ];

        foreach ($skills as $skill) {
            CandidateSkill::firstOrCreate(
                ['candidate_profile_id' => $profile->id, 'skill' => $skill['skill']],
                ['level' => $skill['level']]
            );
        }

        // Experience
        $experiences = [
            [
                'company'    => 'Vigilearn',
                'title'      => 'Software Engineer',
                'description'=> 'Built and maintained full-stack web applications using React and Laravel. Worked on authentication systems, API integrations, and deployment pipelines.',
                'is_current' => true,
                'sort_order' => 0,
            ],
            [
                'company'    => 'Avario Digitals',
                'title'      => 'Full Stack Developer',
                'description'=> 'Developed client-facing web applications with React and Node.js. Integrated payment systems, KYC workflows, and multi-tenant SaaS features.',
                'is_current' => false,
                'sort_order' => 1,
            ],
        ];

        foreach ($experiences as $i => $exp) {
            CandidateExperience::firstOrCreate(
                ['candidate_profile_id' => $profile->id, 'company' => $exp['company'], 'title' => $exp['title']],
                $exp
            );
        }

        // Automation settings
        AutomationSettings::firstOrCreate(
            ['user_id' => $user->id],
            [
                'daily_send_limit'        => 10,
                'hourly_send_limit'       => 3,
                'auto_send'               => false,
                'require_approval'        => true,
                'working_hours_start'     => '08:00',
                'working_hours_end'       => '18:00',
                'timezone'                => 'Africa/Lagos',
                'follow_up_interval_days' => 4,
                'max_follow_ups'          => 2,
                'minimum_match_score'     => 70,
                'discovery_enabled'       => true,
                'search_keywords'         => ['react developer', 'laravel developer', 'full stack developer', 'php developer', 'node.js developer'],
                'search_locations'        => ['Lagos Nigeria', 'Remote', 'Worldwide'],
                'remote_only'             => false,
            ]
        );

        $this->command->info("✅ User: timileyin@telscout.local | Password: password");
        $this->command->info("✅ Profile, skills, experiences and settings seeded.");
    }
}
