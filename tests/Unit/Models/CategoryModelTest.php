<?php

use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;

it('has many groups', function () {
    $category = Category::factory()->create();
    $group = Group::factory()->create(['category_id' => $category->category_id]);

    $category = $category->refresh();

    expect($category->groups)->toHaveCount(1)
        ->and($category->groups->first()->is($group))->toBeTrue();
});

it('casts attributes correctly', function () {
    $category = Category::factory()->create([
        'category_id' => '1',
        'name' => 'Test Category',
        'published' => '1',
    ]);

    expect($category->category_id)->toBeInt()
        ->and($category->name)->toBeString()
        ->and($category->published)->toBeBool();
});
