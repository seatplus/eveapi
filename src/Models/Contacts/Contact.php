<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Models\Contacts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Seatplus\Eveapi\database\factories\ContactFactory;

class Contact extends Model
{
    protected $guarded = false;

    use HasFactory;

    protected static function newFactory()
    {
        return ContactFactory::new();
    }

    public function contactable()
    {
        return $this->morphTo();
    }

    public function labels(): HasMany
    {
        return $this->hasMany(ContactLabel::class);
    }


    public function scopeAffiliated(Builder $query, array $affiliated_ids, ?array $contactable_ids = null): Builder
    {
        return $query->when($contactable_ids, function ($query, $contactable_ids) use ($affiliated_ids) {
            return $query->entityFilter(collect($contactable_ids)->map(fn ($character_id) => intval($character_id))->intersect($affiliated_ids)->toArray());
        }, function ($query) {
            return $query->entityFilter(auth()->user()->characters->pluck('character_id')->toArray());
        });
    }

    public function scopeEntityFilter(Builder $query, array $contactable_ids): Builder
    {
        return $query->whereIn('contactable_id', $contactable_ids);
    }

    public function scopeWithStandings(Builder $query, int $corporation_id, ?int $alliance_id = null)
    {

        $query->withExpression('corporation_standings', fn ($query) => $query
            ->select('standing', 'contact_id', DB::raw('(CASE WHEN contact_type = "character" THEN "1" WHEN contact_type = "faction" THEN "2" WHEN contact_type = "corporation" THEN "3" WHEN contact_type = "alliance" THEN "4" END) as level'))
            ->from('contacts')
            ->where('contactable_id', '=', $corporation_id)
        );

        $query->when(is_integer($alliance_id), fn($query) => $query
            ->withExpression('alliance_standings', fn ($query) => $query
                ->select('standing', 'contact_id', DB::raw('(CASE WHEN contact_type = "character" THEN "1" WHEN contact_type = "faction" THEN "2" WHEN contact_type = "corporation" THEN "3" WHEN contact_type = "alliance" THEN "4" END) as level'))
                ->from('contacts')
                ->where('contactable_id', '=', $alliance_id)
        ));

        $query->leftJoin('character_affiliations', function(JoinClause $join) {
            $join->on('contacts.contact_id', '=', 'character_affiliations.character_id')
                ->orOn('contacts.contact_id', '=', 'character_affiliations.corporation_id')
                ->orOn('contacts.contact_id', '=', 'character_affiliations.alliance_id')
                ->orOn('contacts.contact_id', '=', 'character_affiliations.faction_id');
        });

        $query->addSelect([
            'corporation_standing' => DB::table('corporation_standings')
                ->select('standing')
                ->whereColumn('corporation_standings.contact_id', 'character_affiliations.alliance_id')
                ->orWhereColumn('corporation_standings.contact_id', 'character_affiliations.corporation_id')
                ->orWhereColumn('corporation_standings.contact_id', 'character_affiliations.faction_id')
                ->orWhereColumn('corporation_standings.contact_id', 'character_affiliations.character_id')
                ->orderByDesc('level')
                ->take(1)
        ]);

        $query->when(is_integer($alliance_id), fn($query) => $query
            ->addSelect([
                    'alliance_standing' => DB::table('alliance_standings')
                        ->select('standing')
                        ->whereColumn('alliance_standings.contact_id', 'character_affiliations.alliance_id')
                        ->orWhereColumn('alliance_standings.contact_id', 'character_affiliations.corporation_id')
                        ->orWhereColumn('alliance_standings.contact_id', 'character_affiliations.faction_id')
                        ->orWhereColumn('alliance_standings.contact_id', 'character_affiliations.character_id')
                        ->orderByDesc('level')
                        ->take(1)
                    ])
        );
    }
}
