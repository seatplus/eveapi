<?php


namespace Seatplus\Eveapi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition()
    {
        return [
            'contactable_id' => $this->faker->numberBetween(),
            'contactable_type' => $this->faker->randomElement([CharacterInfo::class, CorporationInfo::class, AllianceInfo::class]),
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
