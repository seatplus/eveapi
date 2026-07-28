<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        // Replace the per-row UPDATE loop with a single set-based UPDATE ... FROM (VALUES …) per
        // chunk. This is update-only — it never inserts, so item_ids we hold no asset row for are
        // simply not matched (an ON CONFLICT upsert would instead try to insert an orphan tuple and
        // fail the NOT NULL on assetable_id). Chunked to stay well under Postgres' 65535 bind cap
        // (2 binds per row + 1 for the character scope).
        $namedItems
            ->chunk(1000)
            ->each(fn (Collection $chunk) => $this->bulkUpdateNames($chunk));
    }

    private function bulkUpdateNames(Collection $items): void
    {
        $placeholders = $items->map(fn () => '(?, ?)')->implode(', ');

        $bindings = $items
            ->flatMap(fn (object $item) => [$item->item_id, $item->name])
            ->push($this->characterId)
            ->all();

        DB::update(
            "UPDATE assets SET name = v.name FROM (VALUES {$placeholders}) AS v(item_id, name) WHERE assets.item_id = v.item_id::bigint AND assets.assetable_id = ?",
            $bindings
        );
    }
}
