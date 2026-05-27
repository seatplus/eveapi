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

    $test_class = new RateLimitedTestJob(testCharacter()->refresh_token);
    $test_class->queue = 'high';

    assertJobRanSuccessfully($test_class);
    assertJobRanSuccessfully($test_class);
});

it('has limits for character_batch if queue is not high', function () {

    // arrange

    $test_class = new RateLimitedTestJob(testCharacter()->refresh_token);
    $test_class->queue = 'default';

    assertJobRanSuccessfully($test_class);
    assertJobWasReleased($test_class);
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

    public function __construct(public $refresh_token) {}

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new RateLimitedWithRedis('character_batch')];
    }
}
