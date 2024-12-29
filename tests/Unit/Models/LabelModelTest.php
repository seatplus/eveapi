<?php

it('has labelable relationship', function () {
    $label = \Seatplus\Eveapi\Models\Contacts\Label::factory()->create([
        'labelable_id' => testCharacter()->character_id,
        'labelable_type' => \Seatplus\Eveapi\Models\Character\CharacterInfo::class,
    ]);

    expect($label->labelable())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class)
        ->and($label->labelable->character_id)->toEqual(testCharacter()->character_id);
});
