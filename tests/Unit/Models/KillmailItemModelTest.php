<?php

use Seatplus\Eveapi\Models\Killmails\KillmailItem;

it('has a has_content attribute', function () {
    $killmail_item = KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->has_content)->toBeFalse();

    // create content
    KillmailItem::query()->create([
        'location_id' => $killmail_item->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->refresh()->has_content)->toBeTrue();
});

it('has a content relationship', function () {
    $killmail_item = KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->content)->toBeEmpty();

    // create content
    KillmailItem::query()->create([
        'location_id' => $killmail_item->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->refresh()->content)->toHaveCount(1);
});

it('deletes content', function () {
    $killmail_item = KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    // create content
    $content = KillmailItem::query()->create([
        'location_id' => $killmail_item->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->refresh()->content)->toHaveCount(1);

    $killmail_item->delete();

    expect(KillmailItem::count())->toBe(0);
});
