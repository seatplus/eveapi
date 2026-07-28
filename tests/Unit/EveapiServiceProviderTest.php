<?php

use Illuminate\Bus\Dispatcher;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Horizon;
use Seatplus\Auth\Models\User;
use Seatplus\EsiClient\EsiConfiguration;
use Seatplus\Eveapi\EveapiServiceProvider;

it('sets EVE-compliant user agent on boot', function () {
    EsiConfiguration::resetInstance();

    $serviceProvider = new EveapiServiceProvider(app());
    $serviceProvider->boot();

    $userAgent = EsiConfiguration::getInstance()->http_user_agent;

    expect($userAgent)
        ->toContain('seatplus/eveapi/')
        ->toContain('+https://github.com/seatplus/eveapi');
});

it('wires the esi-client connection config onto the EsiConfiguration singleton on boot', function () {
    EsiConfiguration::resetInstance();

    config([
        'eveapi.config.esi-client.datasource' => 'singularity',
        'eveapi.config.esi-client.esi_scheme' => 'http',
        'eveapi.config.esi-client.esi_host' => 'esi.example.test',
        'eveapi.config.esi-client.esi_port' => 8443,
        'eveapi.config.esi-client.sso_scheme' => 'http',
        'eveapi.config.esi-client.sso_host' => 'login.example.test',
        'eveapi.config.esi-client.sso_port' => 9443,
    ]);

    $serviceProvider = new EveapiServiceProvider(app());
    $serviceProvider->boot();

    expect(EsiConfiguration::getInstance())
        ->datasource->toBe('singularity')
        ->esi_scheme->toBe('http')
        ->esi_host->toBe('esi.example.test')
        ->esi_port->toBe(8443)
        ->sso_scheme->toBe('http')
        ->sso_host->toBe('login.example.test')
        ->sso_port->toBe(9443);
});

it('leaves the esi-client schema-matched compatibility date untouched', function () {
    EsiConfiguration::resetInstance();

    $default = (new EsiConfiguration)->compatibility_date;

    $serviceProvider = new EveapiServiceProvider(app());
    $serviceProvider->boot();

    // eveapi must not override compatibility_date — it is pinned to the installed
    // esi-client/esi-schema version, not to application config.
    expect(EsiConfiguration::getInstance()->compatibility_date)->toBe($default);
});

it('tests horizon auth with user', function () {

    $serviceProvider = new EveapiServiceProvider(app());
    $serviceProvider->configureHorizon();

    $requestWithUser = new Request;
    $requestWithUser->setUserResolver(function () {
        $user = mock(User::class, function ($mock) {
            $mock->shouldReceive('can')->with('queue_manager')->andReturn(true);
        });

        return $user;
    });

    expect(Horizon::check($requestWithUser))->toBeTrue();
});

it('tests horizon auth without user', function () {

    $serviceProvider = new EveapiServiceProvider(app());
    $serviceProvider->configureHorizon();

    $requestWithoutUser = new Request;

    expect(Horizon::check($requestWithoutUser))->toBeFalse();
});

it('returns null when exception is caught', function () {

    // arrange

    DB::shouldReceive('connection')->andThrow(new Exception('Database connection error'));

    $serviceProvider = new EveapiServiceProvider(app());
    $serviceProvider->boot();

    // assert

    // expect no schedule to be added
    expect(app(Schedule::class)->events())->toBeArray();
});

it('has no limits for character_batch if queue is high', function () {

    // arrange

    $testClass = new RateLimitedTestJob(testCharacter()->refreshToken);
    $testClass->queue = 'high';

    assertJobRanSuccessfully($testClass);
    assertJobRanSuccessfully($testClass);
});

it('has limits for character_batch if queue is not high', function () {

    // arrange

    $testClass = new RateLimitedTestJob(testCharacter()->refreshToken);
    $testClass->queue = 'default';

    assertJobRanSuccessfully($testClass);
    assertJobWasReleased($testClass);
});

function assertJobRanSuccessfully($testJob)
{
    $testJob::$handled = false;
    $instance = new CallQueuedHandler(new Dispatcher(app()), app());

    $job = mock(Job::class, function ($mock) {
        $mock->shouldReceive('hasFailed')->once()->andReturn(false);
        $mock->shouldReceive('isReleased')->andReturn(false);
        $mock->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $mock->shouldReceive('delete')->once();
    });

    $instance->call($job, [
        'command' => serialize($testJob),
    ]);

    expect($testJob::$handled)->toBeTrue();
}

function assertJobWasReleased($testJob)
{
    $testJob::$handled = false;
    $instance = new CallQueuedHandler(new Dispatcher(app()), app());

    $job = mock(Job::class, function ($mock) {
        $mock->shouldReceive('hasFailed')->once()->andReturn(false);
        $mock->shouldReceive('release');
        $mock->shouldReceive('isReleased')->andReturn(true);
        $mock->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);
    });

    $instance->call($job, [
        'command' => serialize($testJob),
    ]);

    expect($testJob::$handled)->toBeFalse();
}

class RateLimitedTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function __construct(public $refreshToken) {}

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new RateLimitedWithRedis('character_batch')];
    }
}
