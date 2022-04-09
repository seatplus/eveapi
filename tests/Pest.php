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
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;

uses(\Seatplus\Eveapi\Tests\TestCase::class)->in('Unit', 'Integration', 'Jobs');
//uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)->in('Unit', 'Integration', 'Jobs');

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

function mockRetrieveEsiDataAction(array $body)
{
    $data = json_encode($body);

    $response = new EsiResponse($data, [], 'now', 200);

    RetrieveEsiData::shouldReceive('execute')
        ->once()
        ->andReturn($response);
}

function noRetrieveEsiDataAction()
{
    RetrieveEsiData::shouldReceive('execute')->never();
}

function testCharacter()
{
    return \Seatplus\Eveapi\Models\Character\CharacterInfo::first();
}

function updateRefreshTokenScopes(\Seatplus\Eveapi\Models\RefreshToken $refreshToken, array $scopes): \Seatplus\Eveapi\Models\RefreshToken
{
    $jwt = $refreshToken->getRawOriginal('token');
    $jwt_payload_base64_encoded = explode('.', $jwt)[1];
    // create an associative array
    $jwt_payload = json_decode(JWT::urlsafeB64Decode($jwt_payload_base64_encoded), true);
    // update scopes
    $jwt_payload['scp'] = $scopes;
    // create a json object
    $jwt_payload = json_encode($jwt_payload);

    $jwt_header = json_encode([
        "alg" => "RS256",
        "kid" => "JWT-Signature-Key",
        "typ" => "JWT",
    ]);

    $data = JWT::urlsafeB64Encode($jwt_header) . "." . JWT::urlsafeB64Encode($jwt_payload);

    $signature = hash_hmac(
        'sha256',
        base64_encode($jwt_header) . "." . base64_encode($jwt_payload),
        'test'
    );

    $refreshToken->token = "${data}.${signature}";

    return $refreshToken;
}
