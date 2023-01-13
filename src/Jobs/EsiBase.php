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

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\RetrieveFromEsiBase;
use Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCorporation;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\MinutesUntilNextSchedule;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;
use Throwable;

abstract class EsiBase extends RetrieveFromEsiBase implements ShouldQueue, BaseJobInterface, ShouldBeUnique
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use RateLimitsEsiCalls;

    public ?RefreshToken $refresh_token;

    public ?int $character_id;

    public ?int $corporation_id;

    public ?int $alliance_id;

    protected string $method;

    protected string $version;

    protected string $endpoint;

    protected string $jobType = '';

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [1 * 60, 5 * 60, 10 * 60];
    }

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
        return implode(', ', $this->tags());
    }

    /**
     * EsiBase constructor.
     */
    public function __construct(?JobContainer $job_container = null, ?string $jobType = null)
    {
        $job_container = $job_container ?? new JobContainer();

        $this->setCharacterId($job_container->getCharacterId());
        $this->setCorporationId($job_container->getCorporationId());
        $this->setAllianceId($job_container->getAllianceId());
        $this->setRefreshToken($job_container->getRefreshToken());

        if ($jobType) {
            $this->setJobType($jobType);
        }

        $this->uniqueFor = $this->getMinutesUntilTimeout() * 60;
    }

    abstract public function tags(): array;

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
     * @throws Exception
     */
    final public function handle(): void
    {
        try {

            $this->executeJob();

        } catch (Exception $exception) {

            report($exception);
            throw $exception;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    abstract public function executeJob(): void;

    // TODO Remove methode and setJobType
    final public function getMinutesUntilTimeout(): int
    {
        $type = isset($this->jobType) ? $this->getJobType() : '';

        $map = [
            'character' => UpdateCharacter::class,
            'corporation' => UpdateCorporation::class,
            'public' => MaintenanceJob::class,
        ];

        $scheduled_class = data_get($map, $type);

        if (is_null($scheduled_class)) {
            return 1;
        }

        return MinutesUntilNextSchedule::get($scheduled_class);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @return string
     */
    public function getJobType(): string
    {
        return $this->jobType;
    }

    /**
     * @param string $jobType
     */
    public function setJobType(string $jobType): void
    {
        $this->jobType = $jobType;
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
        if (! isset($this->character_id)) {
            $this->character_id = $this->getRefreshToken()->character_id;
        }

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
        if (! isset($this->corporation_id)) {
            $this->corporation_id = CharacterAffiliation::find($this->getCharacterId())?->corporation_id;
        }

        return $this->corporation_id;
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
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        if ($exception instanceof MaxAttemptsExceededException) {
            return;
        }

        if ($exception->getOriginalException()?->getResponse()?->getReasonPhrase() === 'Forbidden') {
            $this->fail($exception);
        }
    }
}
