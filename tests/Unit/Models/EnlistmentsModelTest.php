<?php


use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Recruitment\Enlistments;

test('has corporation relationship', function () {
    $enlistment = Enlistments::create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'type' => 'user',
    ]);

    $this->assertDatabaseHas('enlistments', ['corporation_id' => $enlistment->corporation_id]);

    expect($enlistment->corporation)->toBeInstanceOf(CorporationInfo::class);
});

it('supports multistep', function () {
    $enlistment = Enlistments::create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'type' => 'user',
    ]);

    expect($enlistment)
        ->steps->toBeArray()
        ->steps_count->toBeInt()->toBe(1);

    $enlistment->steps = "One; Two, three but actually two";
    $enlistment->save();

    expect($enlistment)
        ->steps->toBeArray()
        ->steps_count->toBeInt()->toBe(2);

});
