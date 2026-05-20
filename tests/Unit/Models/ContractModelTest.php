<?php

use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;

it('has inRegionScope', function (string $location_id) {
    expect(Contract::all())->toHaveCount(0);

    $test_contract = Contract::factory()->create([
        $location_id => Location::factory()->create([
            'locatable_type' => Station::class,
            'locatable_id' => Station::factory()->create([
                'system_id' => System::factory()->create([
                    'constellation_id' => Constellation::factory()->create([
                        'region_id' => Region::factory(),
                    ]),
                ]),
            ]),
        ]),
    ]);

    $region_id = match ($location_id) {
        'start_location_id' => $test_contract->start_location->locatable->system->region->region_id,
        'end_location_id' => $test_contract->end_location->locatable->system->region->region_id
    };

    expect(Contract::query()->filterByRegionIds($region_id)->get())->toHaveCount(1)
        ->and(Contract::query()->filterByRegionIds($region_id + 1)->get())->toHaveCount(0);
})->with([
    'start_location_id',
    'end_location_id',
]);

it('has inSystemScope', function (string $location_id) {
    expect(Contract::all())->toHaveCount(0);

    $test_contract = Contract::factory()->create([
        $location_id => Location::factory()->create([
            'locatable_type' => Station::class,
            'locatable_id' => Station::factory()->create([
                'system_id' => System::factory(),
            ]),
        ]),
    ]);

    $system_id = match ($location_id) {
        'start_location_id' => $test_contract->start_location->locatable->system->system_id,
        'end_location_id' => $test_contract->end_location->locatable->system->system_id
    };

    expect(Contract::query()->filterBySystemIds($system_id)->get())->toHaveCount(1)
        ->and(Contract::query()->filterBySystemIds($system_id + 1)->get())->toHaveCount(0);
})->with([
    'start_location_id',
    'end_location_id',
]);

it('has ofTypes scope', function () {
    $contract = Contract::factory()->create();

    $item = ContractItem::factory()->create([
        'contract_id' => $contract->contract_id,
    ]);

    expect($item)
        ->type
        ->toBeInstanceOf(Type::class)
        ->and(Contract::query()->filterByTypeIds($item->type->type_id)->get())->toHaveCount(1)
        ->and(Contract::query()->filterByTypeIds($item->type->type_id + 1)->get())->toHaveCount(0);

});

it('has ofGroups scope', function () {
    $contract = Contract::factory()->create();

    $item = ContractItem::factory()->create([
        'contract_id' => $contract->contract_id,
    ]);

    expect($item)
        ->type->toBeInstanceOf(Type::class)
        ->type->group_id->toBeInt()
        ->and(Contract::query()->filterByGroupIds($item->type->group_id)->get())->toHaveCount(1)
        ->and(Contract::query()->filterByGroupIds($item->type->group_id + 1)->get())->toHaveCount(0);

});

it('has ofCategories scope', function () {
    $contract = Contract::factory()->create();

    $item = ContractItem::factory()->create([
        'contract_id' => $contract->contract_id,
        'type_id' => Type::factory()->create([
            'group_id' => Group::factory()->create(['category_id' => Category::factory()]),
        ]),
    ]);

    expect($item)
        ->type->toBeInstanceOf(Type::class)
        ->type->group->toBeInstanceOf(Group::class)
        ->type->group->category->toBeInstanceOf(Category::class)
        ->and(Contract::query()->filterByCategoryIds($item->type->group->category->category_id)->get())->toHaveCount(1)
        ->and(Contract::query()->filterByCategoryIds($item->type->group->category->category_id + 1)->get())->toHaveCount(0);

});

it('has assignee', function (int $assignee_id) {
    $contract = Contract::factory()->create([
        'assignee_id' => $assignee_id,
    ]);

    expect($contract->assignee)->not()->toBeNull();
})->with([
    fn () => testCharacter()->character_id,
    fn () => testCharacter()->corporation_id,
]);
