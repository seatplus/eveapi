<?php

namespace Seatplus\Eveapi\database\factories;

use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Mail\Mail;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->unique()->randomNumber(),
            'subject' => $this->faker->sentence,
            'from' => CharacterInfo::factory(),
            'timestamp' => $this->faker->iso8601,
            'is_read' => $this->faker->boolean,
        ];
    }
}
