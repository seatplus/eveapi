<?php

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Label;

it('has labelable relationship', function () {
    $label = Label::factory()->create([
        'labelable_id' => testCharacter()->character_id,
        'labelable_type' => CharacterInfo::class,
    ]);

    expect($label->labelable())->toBeInstanceOf(MorphTo::class)
        ->and($label->labelable->character_id)->toEqual(testCharacter()->character_id);
});
