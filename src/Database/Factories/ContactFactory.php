<?php


namespace Seatplus\Eveapi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Contacts\Contact;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition()
    {
        return [
            'contact_id' => $this->faker->numberBetween(),
            'contact_type' => $this->faker->randomElement(['character', 'corporation', 'alliance', 'faction']),
            'standing' => $this->faker->randomFloat(2,-10, 10)
        ];
    }

    public function withLabels()
    {
        return $this->state(function (array $attributes) {
            return [
                'label_ids' => [1, 2, 3]
            ];
        });
    }
}
