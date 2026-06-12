<?php

use Seatplus\Eveapi\Models\Killmails\KillmailItem;

it('has a has_content attribute', function () {
    $killmailItem = KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmailItem->has_content)->toBeFalse();

    // create content
    KillmailItem::query()->create([
        'location_id' => $killmailItem->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmailItem->refresh()->has_content)->toBeTrue();
});

it('has a content relationship', function () {
    $killmailItem = KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmailItem->content)->toBeEmpty();

    // create content
    KillmailItem::query()->create([
        'location_id' => $killmailItem->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmailItem->refresh()->content)->toHaveCount(1);
});

it('deletes content', function () {
    $killmailItem = KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    // create content
    $content = KillmailItem::query()->create([
        'location_id' => $killmailItem->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmailItem->refresh()->content)->toHaveCount(1);

    $killmailItem->delete();

    expect(KillmailItem::count())->toBe(0);
});
