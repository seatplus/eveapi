<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Support\Collection;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Assets\GetCharactersCharacterIdAssets;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Assets\ResolveAssetRootLocations;

final class CharacterAssetJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdAssets::class;

    /**
     * PostgreSQL rejects a statement with more than 65,535 bind parameters. The upsert payload
     * carries 12 columns per row, so a single call tops out at 65,535 / 12 ≈ 5,461 rows. We chunk
     * well below that ceiling to stay safe across drivers and future column additions.
     */
    private const int UPSERT_CHUNK_SIZE = 5000;

    private readonly Collection $assets;

    public function __construct(public int $characterId)
    {
        $this->assets = collect();
    }

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['character', "character_id:{$this->characterId}", 'assets'];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $page = 1;
        do {
            $response = self::OPERATION_CLASS::execute($esi, $this->characterId, $page);
            if ($response->isCachedLoad) {
                return;
            }

            foreach ($response->data as $asset) {
                $this->assets->push([
                    'item_id' => $asset->item_id,
                    'assetable_id' => $this->characterId,
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

        // Resolve the top-level location/item each asset sits in from the in-memory set (no extra
        // query) and write them in the same upsert, so both roots land in one write.
        $roots = (new ResolveAssetRootLocations)->resolve($this->assets);

        $assetsWithRoots = $this->assets->map(fn (array $asset): array => [
            ...$asset,
            'root_location_id' => $roots->get($asset['item_id'])['root_location_id'],
            'root_item_id' => $roots->get($asset['item_id'])['root_item_id'],
        ]);

        $assetsWithRoots->chunk(self::UPSERT_CHUNK_SIZE)->each(fn (Collection $chunk): int => Asset::upsert($chunk->values()->toArray(), ['item_id'], [
            'assetable_id', 'assetable_type', 'is_blueprint_copy', 'is_singleton',
            'location_flag', 'location_id', 'location_type', 'quantity', 'type_id',
            'root_location_id', 'root_item_id',
        ]));

        Asset::query()
            ->where('assetable_id', $this->characterId)
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
            ->where('assetable_id', $this->characterId)
            ->doesntHave('location')
            ->pluck('location_id')
            ->unique();

        if ($unknownLocationIds->isEmpty()) {
            return;
        }

        $refreshToken = RefreshToken::find($this->characterId);
        $unknownLocationIds->each(fn (int $locationId) => ResolveLocationJob::dispatch($locationId, $refreshToken)->onQueue('high'));
    }

    private function resolveUnknownTypes(): void
    {
        Asset::query()
            ->where('assetable_id', $this->characterId)
            ->doesntHave('type')
            ->pluck('type_id')
            ->unique()
            ->each(fn (int $typeId) => ResolveUniverseTypeByIdJob::dispatch($typeId)->onQueue('high'));
    }
}
