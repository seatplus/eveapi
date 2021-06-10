<?php

namespace Seatplus\Eveapi\database\factories;

use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Universe\Type;

class SkillQueueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SkillQueue::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => CharacterInfo::factory(),
            'skill_id' => Type::factory(),
            'queue_position' => $this->faker->unique()->randomDigitNotNull,
            'finished_level' => $this->faker->numberBetween(0, 5),
            'start_date' => $this->faker->iso8601($max = 'now'),
            'finish_date' => $this->faker->iso8601($max = 'now'),
            'training_start_sp' => $this->faker->randomNumber,
            'level_start_sp' => $this->faker->randomNumber,
            'level_end_sp' => $this->faker->randomNumber,
        ];
    }
}
