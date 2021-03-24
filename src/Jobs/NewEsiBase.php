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

namespace Seatplus\Eveapi\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\MinutesUntilNextSchedule;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

abstract class NewEsiBase extends RetrieveFromEsiBase implements ShouldQueue, NewBaseJobInterface, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, RateLimitsEsiCalls;

    public int $tries = 1;

    public ?RefreshToken $refresh_token;

    public ?int $character_id;

    public ?int $corporation_id;

    public ?int $alliance_id;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 3600;

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return implode($this->tags());
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refresh_token;
    }

    public function setRefreshToken(?RefreshToken $refresh_token): void
    {
        $this->refresh_token = $refresh_token;
    }

    public function setCharacterId(?int $character_id): void
    {
        $this->character_id = $character_id;
    }

    /**
     * @return int|null
     */
    public function getCharacterId(): ?int
    {
        return $this->character_id;
    }

    /**
     * @param int $corporation_id
     */
    public function setCorporationId(?int $corporation_id): void
    {
        $this->corporation_id = $corporation_id;
    }

    /**
     * @return int
     */
    public function getCorporationId(): int
    {
        return $this->corporation_id ?? optional(CharacterAffiliation::find($this->character_id))->corporation_id;
    }

    /**
     * @param int $alliance_id
     *
     * @return void
     */
    public function setAllianceId(?int $alliance_id): void
    {
        $this->alliance_id = $alliance_id;
    }

    /**
     * @return int|null
     */
    public function getAllianceId(): ?int
    {
        return $this->alliance_id;
    }

    /**
     * EsiBase constructor.
     *
     * @param JobContainer|null $job_container
     */
    public function __construct(?JobContainer $job_container = null)
    {
        $job_container = $job_container ?? new JobContainer();

        $this->setCharacterId($job_container->getCharacterId());
        $this->setCorporationId($job_container->getCorporationId());
        $this->setAllianceId($job_container->getAllianceId());
        $this->setRefreshToken($job_container->getRefreshToken());

        $this->uniqueFor = $this->getMinutesUntilTimeout() * 60;
    }

    abstract  public function tags(): array;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    abstract public function middleware(): array;

    /**
     * Execute the job.
     *
     * @return void
     */
    abstract public function handle(): void;

    /**
     * Get the job type.
     *
     * @return void
     */
    abstract public function getJobType(): string;

    final public function getMinutesUntilTimeout(): int
    {
        $type = $this->getJobType();

        $map = [
            'character' => UpdateCharacter::class,
            'corporation' => UpdateCorporation::class
        ];

        $scheduled_class = data_get($map, $type);

        if(is_null($scheduled_class))
            return 1;

        return MinutesUntilNextSchedule::get($scheduled_class);

    }
}
