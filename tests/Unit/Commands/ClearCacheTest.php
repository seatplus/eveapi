<?php

it('clears all caches when force option is provided', function () {
    $this->artisan('seatplus:cache:clear', ['--force' => true])
        ->expectsOutput('SeAT plus Cache Clearing Tool')
        ->expectsOutput('Clearing the Redis Cache')
        ->expectsOutput('Clearing the Artisan Cache')
        ->expectsOutput('cleanup pending batch updates')
        ->expectsOutput('success')
        ->assertExitCode(0);
});

it('prompts for confirmation when force option is not provided', function () {
    $this->artisan('seatplus:cache:clear')
        ->expectsOutput('SeAT plus Cache Clearing Tool')
        ->expectsQuestion('Are you sure you want to clear ALL caches (file/redis)?', 'no')
        ->expectsOutput('Exiting without clearing cache')
        ->assertExitCode(0);
});

it('clears caches when confirmation is given', function () {
    $this->artisan('seatplus:cache:clear')
        ->expectsOutput('SeAT plus Cache Clearing Tool')
        ->expectsQuestion('Are you sure you want to clear ALL caches (file/redis)?', 'yes')
        ->expectsOutput('Clearing the Redis Cache')
        ->expectsOutput('Clearing the Artisan Cache')
        ->expectsOutput('cleanup pending batch updates')
        ->expectsOutput('success')
        ->assertExitCode(0);
});

it('handles exception when Redis cache clearing fails', function () {
    \Illuminate\Support\Facades\Redis::shouldReceive('flushall')->andThrow(new Exception('Redis error'));

    $this->artisan('seatplus:cache:clear', ['--force' => true])
        ->expectsOutput('SeAT plus Cache Clearing Tool')
        ->expectsOutput('Clearing the Redis Cache')
        ->expectsOutput('Failed to clear the Redis Cache. Error: Redis error')
        ->expectsOutput('Clearing the Artisan Cache')
        ->expectsOutput('cleanup pending batch updates')
        ->expectsOutput('success')
        ->assertExitCode(0);
});
