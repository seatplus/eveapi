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

namespace Seatplus\Eveapi\Models\Character;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Seatplus\Eveapi\database\factories\CharacterInfoFactory;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

class CharacterInfo extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return CharacterInfoFactory::new();
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public $incrementing = false;

    /**
     * @var string
     */
    protected $primaryKey = 'character_id';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'corporation_id' => 'integer',
    ];

    public function refresh_token()
    {
        return $this->hasOne(RefreshToken::class, 'character_id', 'character_id');
    }

    public function corporation()
    {
        return $this->hasOneThrough(
            CorporationInfo::class,
            CharacterAffiliation::class,
            'character_id',
            'corporation_id',
            'character_id',
            'corporation_id'
        );
    }

    public function alliance()
    {
        return $this->hasOneThrough(
            AllianceInfo::class,
            CharacterAffiliation::class,
            'character_id',
            'alliance_id',
            'character_id',
            'alliance_id'
        );
    }

    public function roles()
    {
        return $this->hasOne(CharacterRole::class, 'character_id', 'character_id')->withDefault();
    }

    public function character_affiliation()
    {
        return $this->hasOne(CharacterAffiliation::class, 'character_id', 'character_id');
    }

    public function application()
    {
        return $this->morphOne(Application::class, 'applicationable')->whereStatus('open');
    }

    public function getCorporationIdAttribute()
    {
        return $this->character_affiliation?->corporation_id;
    }

    public function getAllianceIdAttribute()
    {
        return $this->character_affiliation?->alliance_id;
    }

    public function assets()
    {
        return $this->morphMany(Asset::class, 'assetable');
    }

    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function labels()
    {
        return $this->morphMany(Label::class, 'labelable');
    }

    public function wallet_journals()
    {
        return $this->morphMany(WalletJournal::class, 'wallet_journable');
    }

    public function wallet_transactions()
    {
        return $this->morphMany(WalletTransaction::class, 'wallet_transactionable');
    }

    public function contracts()
    {
        return $this->morphToMany(
            Contract::class,
            'contractable',
            null,
            null,
            'contract_id'
        );
    }

    public function corporation_history()
    {
        return $this->hasMany(CorporationHistory::class, 'character_id');
    }
}
