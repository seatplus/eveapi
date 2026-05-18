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

use Faker\Factory;
use Firebase\JWT\JWT;
use Mockery\MockInterface;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Esi\GetUpToDateRefreshTokenService;
use Seatplus\Eveapi\Tests\TestCase;

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
        $mock->shouldReceive('__invoke')
            ->andReturnUsing(fn (RefreshToken $token) => $token);
    });
    app()->instance(GetUpToDateRefreshTokenService::class, $mock);
}

function mockEsiClient(string $chain, mixed $result): EsiClient
{
    $parts = explode('->', $chain, 2);
    $resourceGetter = $parts[0];
    $endMethod = $parts[1] ?? null;

    // Determine the resource class from EsiClient
    $esiReflection = new ReflectionClass(EsiClient::class);
    $resourceClass = (string) $esiReflection->getMethod($resourceGetter)->getReturnType();

    // If there's a chain, convert plain objects to the correct typed DTO
    if ($endMethod !== null) {
        $resourceReflection = new ReflectionClass($resourceClass);
        $dtoClass = (string) $resourceReflection->getMethod($endMethod)->getReturnType();

        // Auto-convert stdClass/plain objects to the expected DTO type (if not EsiResult)
        if (
            $dtoClass !== EsiResult::class
            && ! ($result instanceof $dtoClass)
            && class_exists($dtoClass)
            && method_exists($dtoClass, 'from')
            && (is_object($result) && ! ($result instanceof EsiResult))
        ) {
            $isCachedLoad = $result->isCachedLoad ?? false;
            $pages = $result->pages ?? 1;
            $dto = $dtoClass::from($result);
            $dto->isCachedLoad = $isCachedLoad;
            $dto->pages = $pages;
            $result = $dto;
        }

        $resourceMock = Mockery::mock($resourceClass);
        $resourceMock->shouldReceive($endMethod)->andReturn($result);

        $esi = Mockery::mock(EsiClient::class);
        $esi->shouldReceive('withToken')->andReturnSelf();
        $esi->shouldReceive($resourceGetter)->andReturn($resourceMock);
    } else {
        $esi = Mockery::mock(EsiClient::class);
        $esi->shouldReceive('withToken')->andReturnSelf();
        $esi->shouldReceive($resourceGetter)->andReturn($result);
    }

    app()->instance(EsiClient::class, $esi);
    mockTokenService();

    return $esi;
}

function runJob(object $job): void
{
    app()->call([$job, 'handle']);
}

function testCharacter()
{
    return CharacterInfo::first();
}

function updateRefreshTokenScopes(RefreshToken $refreshToken, array $scopes): RefreshToken
{
    $jwt = $refreshToken->getRawOriginal('token');
    $jwt_payload_base64_encoded = explode('.', (string) $jwt)[1];
    // create an associative array
    $jwt_payload = json_decode(JWT::urlsafeB64Decode($jwt_payload_base64_encoded), true);
    // update scopes
    $jwt_payload['scp'] = $scopes;
    // create a json object
    $jwt_payload = json_encode($jwt_payload);

    $jwt_header = json_encode([
        'alg' => 'RS256',
        'kid' => 'JWT-Signature-Key',
        'typ' => 'JWT',
    ]);

    $data = JWT::urlsafeB64Encode($jwt_header).'.'.JWT::urlsafeB64Encode($jwt_payload);

    $signature = hash_hmac(
        'sha256',
        base64_encode($jwt_header).'.'.base64_encode($jwt_payload),
        'test'
    );

    $refreshToken->token = "{$data}.{$signature}";

    return $refreshToken;
}

function updateCharacterRoles(array $roles)
{
    $character_roles = testCharacter()->roles;

    $character_roles->roles = $roles;
    $character_roles->save();
}
