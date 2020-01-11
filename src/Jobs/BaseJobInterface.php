<?php

namespace Seatplus\Eveapi\Jobs;

use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;

interface BaseJobInterface
{
    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array;

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array;

    public function getActionClass(): RetrieveFromEsiInterface;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void;
}
