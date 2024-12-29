<?php

use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

it('has many types', function () {
    $group = Group::factory()->create();
    $type = Type::factory()->create(['group_id' => $group->group_id]);

    expect($group->types)->toHaveCount(1)
        ->and($group->types->first()->is($type))->toBeTrue();
});

it('has one category', function () {
    $group = Group::factory()->create();
    $category = Category::factory()->create(['category_id' => $group->category_id]);

    expect($group->refresh()->category->is($category))->toBeTrue();
});

it('casts attributes correctly', function () {
    $group = Group::factory()->create([
        'group_id' => '1',
        'category_id' => '2',
        'name' => 'Test Group',
        'published' => '1',
    ]);

    expect($group->group_id)->toBeInt()
        ->and($group->category_id)->toBeInt()
        ->and($group->name)->toBeString()
        ->and($group->published)->toBeBool();
});
