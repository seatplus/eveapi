<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Assets\PostCharactersCharacterIdAssetsNames;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\RefreshToken;

final class CharacterAssetsNameJob extends EsiJob
{
    protected const string OPERATION_CLASS = PostCharactersCharacterIdAssetsNames::class;

    const int CELESTIAL_CATEGORY = 2;

    const int SHIP_CATEGORY = 6;

    const int DEPLOYABLE_CATEGORY = 22;

    const int STARBASE_CATEGORY = 23;

    const int ORBITALS_CATEGORY = 46;

    const int STRUCTURE_CATEGORY = 65;

    private Collection $assetNames;

    public function __construct(public int $characterId)
    {
        $this->assetNames = collect();
    }

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->characterId}", 'assets', 'name'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        if ($this->batching() && $this->batch()->cancelled()) {
            return;
        }

        Asset::query()
            ->with('type.group')
            ->whereHas('type.group', fn (Builder $query) => $query->whereIn('category_id', [
                self::CELESTIAL_CATEGORY, self::SHIP_CATEGORY, self::DEPLOYABLE_CATEGORY,
                self::STARBASE_CATEGORY, self::ORBITALS_CATEGORY, self::STRUCTURE_CATEGORY,
            ]))
            ->where('assetable_id', $this->characterId)
            ->where('is_singleton', true)
            ->pluck('item_id')
            ->chunk(1000)
            ->each(function (Collection $itemIds) use ($esi) {
                $response = static::OPERATION_CLASS::execute(
                    $esi,
                    $itemIds->values()->toArray(),
                    $this->characterId
                );

                if ($response->isCachedLoad) {
                    return;
                }

                $this->assetNames = $this->assetNames->merge(collect($response->data));
            });

        $namedItems = $this->assetNames
            ->filter(fn (object $item) => $item->name !== 'None')
            ->values();

        if ($namedItems->isEmpty()) {
            return;
        }

        // Update each named asset by its (assetable_id, item_id) scope. This is deliberately
        // update-only — every item_id here was plucked from an existing asset row above, and the
        // where-scoped update simply matches nothing for any row that has since gone, rather than
        // inserting an orphan the way an upsert would.
        $namedItems->each(fn (object $item) => Asset::query()
            ->where('assetable_id', $this->characterId)
            ->where('item_id', $item->item_id)
            ->update(['name' => $item->name]));
    }
}
