<?php

namespace Seatplus\Eveapi\database\factories;

use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailLabel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class MailLabelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MailLabel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'mail_id' => Mail::factory(),
            'label_id' => $this->faker->randomNumber(),
            'character_id' => CharacterInfo::factory(),
            'color' => $this->faker->randomElement(['#0000fe', '#006634', '#0099ff', '#00ff33', '#01ffff', '#349800', '#660066', '#666666', '#999999', '#99ffff', '#9a0000', '#ccff9a', '#e6e6e6', '#fe0000', '#ff6600', '#ffff01', '#ffffcd', '#ffffff']),
            'name' => $this->faker->name(),
            'unread_count' => $this->faker->randomNumber(),
        ];
    }
}
