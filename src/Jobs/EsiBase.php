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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\RefreshToken;

abstract class EsiBase implements ShouldQueue, BaseJobInterface
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken|null
     */
    public $refresh_token;

    /**
     * @var int
     */
    public $character_id;

    /**
     * @var int
     */
    public $corporation_id;

    /**
     * @return int
     */
    public function getCorporationId(): int
    {
        return $this->corporation_id ?? optional(CharacterAffiliation::find($this->character_id))->corporation_id;
    }

    /**
     * @var int
     */
    public $alliance_id;

    /**
     * @var mixed
     */
    public $action_class;

    /**
     * @param \Seatplus\Eveapi\Models\RefreshToken $refresh_token
     */
    public function setRefreshToken(?RefreshToken $refresh_token): void
    {
        $this->refresh_token = $refresh_token;
    }

    /**
     * @param int $character_id
     */
    public function setCharacterId(?int $character_id): void
    {
        $this->character_id = $character_id;
    }

    /**
     * @param int $corporation_id
     */
    public function setCorporationId(?int $corporation_id): void
    {
        $this->corporation_id = $corporation_id;
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
     * EsiBase constructor.
     *
     * @param \Seatplus\Eveapi\Containers\JobContainer|null $job_container
     *
     * @throws \Seatplus\Eveapi\Exceptions\InvalidContainerDataException
     */
    public function __construct(?JobContainer $job_container = null)
    {
        $job_container = $job_container ?? new JobContainer();

        $this->setCharacterId($job_container->getCharacterId());
        $this->setCorporationId($job_container->getCorporationId());
        $this->setAllianceId($job_container->getAllianceId());
        $this->setRefreshToken($job_container->getRefreshToken());
    }

    public function tags(): array
    {
        $tags = collect(property_exists($this, 'tags') ? $this->tags : []);

        if (is_null($this->refresh_token)) {
            $tags->push('public');
        }

        if ($this->character_id) {
            $tags->push('character_id:' . $this->character_id);
        }

        if ($this->corporation_id) {
            $tags->push('corporation_id:' . $this->corporation_id);
        }

        if ($this->alliance_id) {
            $tags->push('alliance_id:' . $this->alliance_id);
        }

        return $tags->toArray();
    }

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
}
