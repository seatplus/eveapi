<?php

namespace Seatplus\Eveapi\database\factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Universe\Type;

class SkillFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Skill::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id' => CharacterInfo::factory(),
            'active_skill_level' => $this->faker->randomDigitNotNull(),
            'skill_id' => Type::factory(),
            'skillpoints_in_skill' => $this->faker->numberBetween($min = 1000, $max = 9000), // 8567,
            'trained_skill_level' => $this->faker->randomDigitNotNull(),
        ];
    }
}
