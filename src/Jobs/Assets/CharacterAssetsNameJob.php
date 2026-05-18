<?php

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetsNameJob extends EsiJob
{
    const int CELESTIAL_CATEGORY = 2;

    const int SHIP_CATEGORY = 6;

    const int DEPLOYABLE_CATEGORY = 22;

    const int STARBASE_CATEGORY = 23;

    const int ORBITALS_CATEGORY = 46;

    const int STRUCTURE_CATEGORY = 65;

    private Collection $assetNames;

    public function __construct(public int $character_id)
    {
        $this->assetNames = collect();
    }

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'assets', 'name'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
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
            ->where('assetable_id', $this->character_id)
            ->where('is_singleton', true)
            ->pluck('item_id')
            ->chunk(1000)
            ->each(function (Collection $itemIds) use ($esi) {
                $response = $esi->assets()->postCharactersCharacterIdAssetsNames(
                    $itemIds->values()->toArray(),
                    $this->character_id
                );

                $this->assetNames = $this->assetNames->merge(collect($response->data));
            });

        $this->assetNames
            ->filter(fn (object $item) => $item->name !== 'None')
            ->each(fn (object $item) => Asset::query()
                ->where('assetable_id', $this->character_id)
                ->where('item_id', $item->item_id)
                ->update(['name' => $item->name])
            );
    }
}
