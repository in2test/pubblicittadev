<?php

namespace Database\Factories;

use App\Models\Campaign;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = CarbonImmutable::now();

        return [
            'name' => fake()->words(3, true),
            'starts_at' => $now->subDay(),
            'ends_at' => $now->addDay(),
        ];
    }
}
