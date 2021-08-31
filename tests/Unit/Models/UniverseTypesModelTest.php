<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->type = Event::fakeFor(fn () => Type::factory()->create());
});

it('has group', function () {
    $group = Event::fakeFor(fn () => Group::factory()->create(['group_id' => $this->type->group_id]));

    //$this->type->group()->save($group);

    $this->assertNotNull($this->type->group);
});

it('has no group', function () {
    expect($this->type->group)->toBeNull();
});

it('has no category', function () {
    $group = Event::fakeFor(fn () => Group::factory()->create());

    $this->type->group()->save($group);

    expect($this->type->category)->toBeNull();
});
