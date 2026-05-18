<?php

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetJob extends EsiJob
{
    private Collection $assets;

    public function __construct(public int $character_id)
    {
        $this->assets = collect();
    }

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->character_id}", 'assets'];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $page = 1;
        do {
            $response = $esi->assets()->getCharactersCharacterIdAssets($this->character_id, page: $page);
            if ($response->isCachedLoad) {
                return;
            }

            foreach ($response->data as $asset) {
                $this->assets->push([
                    'item_id' => $asset->item_id,
                    'assetable_id' => $this->character_id,
                    'assetable_type' => CharacterInfo::class,
                    'is_blueprint_copy' => $asset->is_blueprint_copy ?? false,
                    'is_singleton' => $asset->is_singleton,
                    'location_flag' => $asset->location_flag,
                    'location_id' => $asset->location_id,
                    'location_type' => $asset->location_type,
                    'quantity' => $asset->quantity,
                    'type_id' => $asset->type_id,
                ]);
            }
            $page++;
        } while ($page <= $response->pages);

        Asset::upsert($this->assets->toArray(), ['item_id'], [
            'assetable_id', 'assetable_type', 'is_blueprint_copy', 'is_singleton',
            'location_flag', 'location_id', 'location_type', 'quantity', 'type_id',
        ]);

        Asset::query()
            ->where('assetable_id', $this->character_id)
            ->whereNotIn('item_id', $this->assets->pluck('item_id')->toArray())
            ->delete();

        $this->resolveUnknownLocations();
        $this->resolveUnknownTypes();

        if (app()->bound('queue.worker')) {
            app('queue.worker')->shouldQuit = true;
        }
    }

    private function resolveUnknownLocations(): void
    {
        $unknownLocationIds = Asset::query()
            ->where('assetable_id', $this->character_id)
            ->doesntHave('location')
            ->pluck('location_id')
            ->unique();

        if ($unknownLocationIds->isEmpty()) {
            return;
        }

        $refreshToken = RefreshToken::find($this->character_id);
        $unknownLocationIds->each(fn (int $locationId) => ResolveLocationJob::dispatch($locationId, $refreshToken)->onQueue('high'));
    }

    private function resolveUnknownTypes(): void
    {
        Asset::query()
            ->where('assetable_id', $this->character_id)
            ->doesntHave('type')
            ->pluck('type_id')
            ->unique()
            ->each(fn (int $typeId) => ResolveUniverseTypeByIdJob::dispatch($typeId)->onQueue('high'));
    }
}
