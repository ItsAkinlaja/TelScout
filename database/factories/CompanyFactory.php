<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $domain = $this->faker->unique()->domainName();
        return [
            'name'              => $this->faker->company(),
            'domain'            => $domain,
            'normalized_domain' => $domain,
            'website'           => "https://{$domain}",
            'industry'          => $this->faker->randomElement(['Technology', 'FinTech', 'SaaS', 'EdTech']),
            'location'          => $this->faker->city() . ', ' . $this->faker->country(),
            'contact_status'    => 'unavailable',
            'is_excluded'       => false,
        ];
    }
}
