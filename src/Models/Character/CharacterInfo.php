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

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

#[Unguarded]
class CharacterInfo extends Model
{
    use HasFactory;

    public $incrementing = false;

    /**
     * @var string
     */
    protected $primaryKey = 'character_id';

    /** @return HasOne<RefreshToken, $this> */
    public function refresh_token(): HasOne
    {
        return $this->hasOne(RefreshToken::class, 'character_id', 'character_id');
    }

    public function corporation(): HasOneThrough
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

    public function alliance(): HasOneThrough
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

    /** @return HasOne<CharacterRole, $this> */
    public function roles(): HasOne
    {
        return $this->hasOne(CharacterRole::class, 'character_id', 'character_id')->withDefault();
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function character_affiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'character_id', 'character_id');
    }

    public function application(): MorphOne
    {
        return $this->morphOne(Application::class, 'applicationable')->whereStatus('open');
    }

    /** @return Attribute<int|null, never> */
    protected function corporationId(): Attribute
    {
        return Attribute::make(get: fn () => $this->character_affiliation?->corporation_id);
    }

    /** @return Attribute<int|null, never> */
    protected function allianceId(): Attribute
    {
        return Attribute::make(get: fn () => $this->character_affiliation?->alliance_id);
    }

    public function assets(): MorphMany
    {
        return $this->morphMany(Asset::class, 'assetable');
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function labels(): MorphMany
    {
        return $this->morphMany(Label::class, 'labelable');
    }

    public function wallet_journals(): MorphMany
    {
        return $this->morphMany(WalletJournal::class, 'wallet_journable');
    }

    public function wallet_transactions(): MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'wallet_transactionable');
    }

    public function contracts(): MorphToMany
    {
        return $this->morphToMany(
            Contract::class,
            'contractable',
            null,
            null,
            'contract_id'
        );
    }

    public function corporation_history(): HasMany
    {
        return $this->hasMany(CorporationHistory::class, 'character_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class, 'character_id');
    }

    public function skill_queues(): HasMany
    {
        return $this->hasMany(SkillQueue::class, 'character_id');
    }

    public function mails(): HasManyThrough
    {
        return $this->hasManyThrough(
            Mail::class,
            MailRecipients::class,
            'receivable_id',
            'id',
            'character_id',
            'mail_id'
        );
    }

    public function balance(): MorphOne
    {
        return $this->morphOne(Balance::class, 'balanceable');
    }

    public function batch_update(): MorphOne
    {
        return $this->morphOne(BatchUpdate::class, 'batchable');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'character_id' => 'integer',
            'corporation_id' => 'integer',
        ];
    }
}
