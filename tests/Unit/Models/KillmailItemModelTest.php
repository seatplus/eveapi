<?php

it('has a has_content attribute', function () {
    $killmail_item = \Seatplus\Eveapi\Models\Killmails\KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->has_content)->toBeFalse();

    // create content
    \Seatplus\Eveapi\Models\Killmails\KillmailItem::query()->create([
        'location_id' => $killmail_item->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->refresh()->has_content)->toBeTrue();
});

it('has a content relationship', function () {
    $killmail_item = \Seatplus\Eveapi\Models\Killmails\KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->content)->toBeEmpty();

    // create content
    \Seatplus\Eveapi\Models\Killmails\KillmailItem::query()->create([
        'location_id' => $killmail_item->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->refresh()->content)->toHaveCount(1);
});

it('deletes content', function () {
    $killmail_item = \Seatplus\Eveapi\Models\Killmails\KillmailItem::query()->create([
        'location_id' => 123,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    // create content
    $content = \Seatplus\Eveapi\Models\Killmails\KillmailItem::query()->create([
        'location_id' => $killmail_item->id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    expect($killmail_item->refresh()->content)->toHaveCount(1);

    $killmail_item->delete();

    expect(\Seatplus\Eveapi\Models\Killmails\KillmailItem::count())->toBe(0);
});
