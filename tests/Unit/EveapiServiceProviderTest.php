<?php

use Illuminate\Console\Scheduling\Schedule;

it('tests horizon auth with user', function () {

    $serviceProvider = new \Seatplus\Eveapi\EveapiServiceProvider(app());
    $serviceProvider->configureHorizon();

    $requestWithUser = new \Illuminate\Http\Request;
    $requestWithUser->setUserResolver(function () {
        $user = mock(\Seatplus\Auth\Models\User::class, function ($mock) {
            $mock->shouldReceive('can')->with('queue_manager')->andReturn(true);
        });

        return $user;
    });

    expect(\Laravel\Horizon\Horizon::check($requestWithUser))->toBeTrue();
});

it('tests horizon auth without user', function () {

    $serviceProvider = new \Seatplus\Eveapi\EveapiServiceProvider(app());
    $serviceProvider->configureHorizon();

    $requestWithoutUser = new \Illuminate\Http\Request;

    expect(\Laravel\Horizon\Horizon::check($requestWithoutUser))->toBeFalse();
});

it('returns null when exception is caught', function () {

    // arrange

    \Illuminate\Support\Facades\DB::shouldReceive('connection')->andThrow(new Exception('Database connection error'));

    $serviceProvider = new \Seatplus\Eveapi\EveapiServiceProvider(app());
    $serviceProvider->boot();

    // assert

    //expect no schedule to be added
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
    $instance = new \Illuminate\Queue\CallQueuedHandler(new \Illuminate\Bus\Dispatcher(app()), app());

    $job = mock(\Illuminate\Contracts\Queue\Job::class, function ($mock) {
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
    $instance = new \Illuminate\Queue\CallQueuedHandler(new \Illuminate\Bus\Dispatcher(app()), app());

    $job = mock(\Illuminate\Contracts\Queue\Job::class, function ($mock) {
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
    use \Illuminate\Foundation\Queue\Queueable, \Illuminate\Queue\InteractsWithQueue;

    public static $handled = false;

    public function __construct(public $refresh_token) {}

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [new \Illuminate\Queue\Middleware\RateLimitedWithRedis('character_batch')];
    }
}
