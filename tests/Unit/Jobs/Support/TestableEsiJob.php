<?php

namespace Seatplus\Eveapi\Tests\Unit\Jobs\Support;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Mail\GetCharactersCharacterIdMail;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\RefreshToken;

/**
 * Minimal concrete EsiJob for testing the base class behaviours.
 *
 * Overrides release() so tests can assert it was called without
 * needing a real queue connection.
 */
class TestableEsiJob extends EsiJob
{
    public bool $executed = false;

    public ?EsiClient $receivedEsi = null;

    public bool $released = false;

    public int $releasedAfter = 0;

    public bool $failed = false;

    public ?\Throwable $failedWith = null;

    public mixed $executeCallback = null;

    public ?RefreshToken $refreshToken = null;

    public function release($delay = 0): void
    {
        $this->released = true;
        $this->releasedAfter = $delay;
    }

    public function fail($exception = null): void
    {
        $this->failed = true;
        $this->failedWith = $exception;
    }

    public function getRefreshToken(): ?RefreshToken
    {
        return $this->refreshToken;
    }

    public function executeJob(EsiClient $esi): void
    {
        $this->executed = true;
        $this->receivedEsi = $esi;

        if ($this->executeCallback !== null) {
            ($this->executeCallback)($esi);
        }
    }

    public function tags(): array
    {
        return ['test', 'esijob'];
    }
}

/**
 * Concrete job whose OPERATION_CLASS carries a RATE_LIMIT_GROUP constant.
 * Used to verify rateLimitGroup() resolves the group from the operation class.
 */
class TestableEsiJobWithOperation extends TestableEsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdMail::class;
}
