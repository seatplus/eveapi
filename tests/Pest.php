<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

/** @link https://pestphp.com/docs/underlying-test-case */

use DG\BypassFinals;
use Faker\Factory;
use Firebase\JWT\JWT;
use Mockery\MockInterface;
use Seatplus\EsiSchema\Contracts\EsiRawResponse;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Esi\GetUpToDateRefreshTokenService;
use Seatplus\Eveapi\Tests\TestCase;

// pest-plugin-type-coverage v5.0.0 analyses files across forked pokio workers that
// each read-modify-write one shared cache file (vendor/.../.temp/v3.php). Its lock
// gives up after ~100ms and runs the write anyway (Support/Cache.php withinLock()),
// so concurrent non-atomic file_put_contents() splice the cache into invalid PHP.
// The plugin then include()s that file, which makes the breakage sticky: every later
// run fails on the poisoned cache until it is deleted. Recovery is
//     rm -rf vendor/pestphp/pest-plugin-type-coverage/.temp
// Failures are intermittent, not deterministic — cold-cache runs failed 5/5 in one
// sample here and ~25% in another on the same tree, so rate depends on machine load.
//
// Setting this env is the plugin's own opt-out: Analyser.php clamps $maxProcesses to
// 1 when it is set, which leaves a single writer. It is assigned here in PHP rather
// than as an env var on the composer script (as seatplus/web does) because $_ENV is
// only populated from the environment when variables_order contains E, and the
// php.ini PHP ships for production uses GPCS — so the shell form can silently no-op.
//
// CI does not strictly need this: formats.yml already sets FORK_MEM_PER_PROC=100GB,
// which drives pokio's maxProcesses() to 1 for the same effect. This makes local runs
// deterministic without depending on that env being set.
//
// Gated to --type-coverage so nothing else in the suite is affected.
if (in_array('--type-coverage', $_SERVER['argv'] ?? [], true)) {
    $_ENV['__PEST_PLUGIN_ENV'] = '1';
}

BypassFinals::enable();

uses(TestCase::class)->in('Unit', 'Integration', 'Jobs');
// uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)->in('Unit', 'Integration', 'Jobs');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/** @link https://pestphp.com/docs/expectations#custom-expectations */

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function faker()
{
    return Factory::create();
}

function makeEsiResult(mixed $data, bool $isCachedLoad = false, int $pages = 1): EsiResult
{
    return new EsiResult(data: $data, pages: $pages, isCachedLoad: $isCachedLoad);
}

function mockTokenService(): void
{
    $mock = Mockery::mock(GetUpToDateRefreshTokenService::class, function (MockInterface $mock) {
        $mock->shouldReceive('get')
            ->andReturnUsing(fn (RefreshToken $token) => $token);
    });
    app()->instance(GetUpToDateRefreshTokenService::class, $mock);
}

function makeEsiRawResponse(mixed $result): EsiRawResponse
{
    if ($result instanceof EsiResult) {
        return new EsiRawResponse(
            data: $result->data,
            isCachedLoad: $result->isCachedLoad,
            pages: $result->pages,
            rateLimitRemaining: $result->rateLimitRemaining,
        );
    }

    $isCachedLoad = false;
    $pages = 1;
    $rateLimitRemaining = null;
    $data = $result;

    if (is_object($result)) {
        $isCachedLoad = $result->isCachedLoad ?? false;
        $pages = $result->pages ?? 1;
        $rateLimitRemaining = $result->rateLimitRemaining ?? null;
        $data = (object) collect(get_object_vars($result))
            ->except(['isCachedLoad', 'pages', 'rateLimitRemaining'])
            ->all();
    }

    return new EsiRawResponse(
        data: $data,
        isCachedLoad: $isCachedLoad,
        pages: $pages,
        rateLimitRemaining: $rateLimitRemaining,
    );
}

function mockEsiTransport(MockInterface $esi, mixed $result): void
{
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();

    if ($result instanceof Throwable) {
        $esi->shouldReceive('invoke')->andThrow($result);

        return;
    }

    $esi->shouldReceive('invoke')->andReturn(makeEsiRawResponse($result));
}

function testCharacter()
{
    return CharacterInfo::first();
}

function updateRefreshTokenScopes(RefreshToken $refreshToken, array $scopes): RefreshToken
{
    $jwt = $refreshToken->getRawOriginal('token');
    $jwtPayloadBase64Encoded = explode('.', (string) $jwt)[1];
    // create an associative array
    $jwtPayload = json_decode(JWT::urlsafeB64Decode($jwtPayloadBase64Encoded), true);
    // update scopes
    $jwtPayload['scp'] = $scopes;
    // create a json object
    $jwtPayload = json_encode($jwtPayload);

    $jwtHeader = json_encode([
        'alg' => 'RS256',
        'kid' => 'JWT-Signature-Key',
        'typ' => 'JWT',
    ]);

    $data = JWT::urlsafeB64Encode($jwtHeader).'.'.JWT::urlsafeB64Encode($jwtPayload);

    $signature = hash_hmac(
        'sha256',
        base64_encode($jwtHeader).'.'.base64_encode($jwtPayload),
        'test'
    );

    $refreshToken->token = "{$data}.{$signature}";

    return $refreshToken;
}

function updateCharacterRoles(array $roles)
{
    $characterRoles = testCharacter()->roles;

    $characterRoles->roles = $roles;
    $characterRoles->save();
}
