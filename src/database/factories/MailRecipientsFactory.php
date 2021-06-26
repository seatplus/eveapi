<?php

namespace Seatplus\Eveapi\database\factories;

use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class MailRecipientsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MailRecipients::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        return [
            'mail_id' => Mail::factory(),
            'receivable_id' => CharacterInfo::factory(),
            'receivable_type' => CharacterInfo::class,
        ];
    }
}
