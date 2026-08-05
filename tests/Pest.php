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
