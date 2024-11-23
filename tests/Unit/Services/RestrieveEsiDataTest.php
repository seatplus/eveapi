<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\EsiClient\Exceptions\RequestFailedException;
use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Esi\GetUpToDateRefreshTokenService;
use Seatplus\Eveapi\Services\Esi\RetrieveEsiData;

beforeEach(function () {
    Event::fake();
    Mockery::close();
});

describe('executes request successfully', function () {

    beforeEach(function () {

        // build response with raw header X-Kevinrob-Cache HIT
        $response = new EsiResponse(json_encode([]), ['X-Kevinrob-Cache' => 'HIT'], 'now', 200);

        $this->client = mock(\Seatplus\EsiClient\EsiClient::class, function ($mock) use ($response) {
            $mock->shouldReceive('invoke')
                ->andReturn($response);
        });
    });

    it('via executeInstance', function () {
        $service = new RetrieveEsiData(
            method: 'get',
            endpoint: 'foo/bar',
            version: 'v4',
            client: $this->client
        );

        $response = $service->executeInstance();

        expect($response)->toBeInstanceOf(EsiResponse::class);
    });

    it('via execute', function () {
        $container = new EsiRequestContainer(
            method: 'get',
            version: 'v4',
            endpoint: 'foo/bar',
        );

        $response = RetrieveEsiData::execute($container, $this->client);

        expect($response)->toBeInstanceOf(EsiResponse::class);
    });


});

describe('throws request failed exception', function () {

    beforeEach(function () {
        $this->refrehToken = mock(RefreshToken::class);

        $this->requestFailedException = new RequestFailedException(new Exception('failed'), new EsiResponse(json_encode([]), [], 'now', 200));
    });

    it('during build client', function () {

        $getUpToDateRefreshTokenService = mock(GetUpToDateRefreshTokenService::class, function ($mock) {
            $mock->shouldReceive('__invoke')
                ->andThrow($this->requestFailedException);
        });

        $service = new RetrieveEsiData(
            method: 'get',
            endpoint: 'foo/bar',
            version: 'v4',
            refresh_token: $this->refrehToken,
            getUpToDateRefreshTokenService: $getUpToDateRefreshTokenService
        );

        $service->executeInstance();
    })->throws(\Seatplus\EsiClient\Exceptions\RequestFailedException::class);

    it('during execute', function () {

        $client = mock(\Seatplus\EsiClient\EsiClient::class, function ($mock) {
            $mock->shouldReceive('invoke')
                ->andThrow($this->requestFailedException);
        });

        $service = new RetrieveEsiData(
            method: 'get',
            endpoint: 'foo/bar',
            version: 'v4',
            client: $client
        );

        $service->executeInstance();
    })->throws(\Seatplus\EsiClient\Exceptions\RequestFailedException::class);

});

it('throws InvalidAuthenticationException', function () {
    $client = mock(\Seatplus\EsiClient\EsiClient::class, function ($mock) {
        $mock->shouldReceive('invoke')
            ->andThrow(new \Seatplus\EsiClient\Exceptions\InvalidAuthenticationException('failed'));
    });

    $service = new RetrieveEsiData(
        method: 'get',
        endpoint: 'foo/bar',
        version: 'v4',
        client: $client
    );

    $service->executeInstance();
})->throws(\Seatplus\EsiClient\Exceptions\InvalidAuthenticationException::class);

it('builds client with authentication',function () {

    $refresh_token = RefreshToken::factory()->create();

    $getUpToDateRefreshTokenService = mock(GetUpToDateRefreshTokenService::class, function ($mock) use ($refresh_token) {
        $mock->shouldReceive('__invoke')
            ->andReturn($refresh_token);
    });

    $service = new RetrieveEsiData(
        method: 'get',
        endpoint: 'foo/bar',
        version: 'v4',
        refresh_token: $refresh_token,
        getUpToDateRefreshTokenService: $getUpToDateRefreshTokenService
    );

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);

    $client = $property->getValue($service);

    $client_reflection = new ReflectionClass($client);
    $client_property = $client_reflection->getProperty('authentication');
    $client_property->setAccessible(true);

    $authentication = $client_property->getValue($client);

    expect($authentication)->not()->toBeNull();

});

describe('it logs warnings', function () {
    it('when response contained pages but none was expected', function () {

        $esiResponse = new EsiResponse(json_encode([]), ['X-Pages' => 1], 'now', 200);

        $client = mock(\Seatplus\EsiClient\EsiClient::class, function ($mock) use ($esiResponse) {
            $mock->shouldReceive('invoke')
                ->andReturn($esiResponse);
        });

        $service = new RetrieveEsiData(
            method: 'get',
            endpoint: 'foo/bar',
            version: 'v4',
            client: $client
        );

        $response = $service->executeInstance();

        expect($response)->toBe($esiResponse);
    });

    it('when response did not contain pages but one was expected', function () {

        $esiResponse = new EsiResponse(json_encode([]), [], 'now', 200);

        $client = mock(\Seatplus\EsiClient\EsiClient::class, function ($mock) use ($esiResponse) {
            $mock->shouldReceive('invoke')
                ->andReturn($esiResponse);
        });

        $service = new RetrieveEsiData(
            method: 'get',
            endpoint: 'foo/bar',
            version: 'v4',
            page: 1,
            client: $client,
        );

        $response = $service->executeInstance();

        expect($response)->toBe($esiResponse);
    });

    it('when response contained a warning', function () {

        $esiResponse = new EsiResponse(json_encode([]), ['Warning' => 'this is a warning'], 'now', 200);

        $client = mock(\Seatplus\EsiClient\EsiClient::class, function ($mock) use ($esiResponse) {
            $mock->shouldReceive('invoke')
                ->andReturn($esiResponse);
        });

        $service = new RetrieveEsiData(
            method: 'get',
            endpoint: 'foo/bar',
            version: 'v4',
            client: $client
        );

        $response = $service->executeInstance();

        expect($response)->toBe($esiResponse);
    });
});

describe('it handles exceptions', function () {

    it('handles exceeded EsiRateLimit hit', function () {

        $requestFailedException = new RequestFailedException(
            new Exception('failed.'),
            new EsiResponse(json_encode(['error' => 'This software has exceeded the error limit for ESI.']), [], 'now', 200)
        );

        $getUpToDateRefreshTokenService = mock(GetUpToDateRefreshTokenService::class, function ($mock) use ($requestFailedException) {
            $mock->shouldReceive('__invoke')
                ->andThrow($requestFailedException);
        });

        new RetrieveEsiData(
            method: 'get',
            endpoint: 'foo/bar',
            version: 'v4',
            refresh_token: RefreshToken::factory()->make(),
            getUpToDateRefreshTokenService: $getUpToDateRefreshTokenService
        );

    })->throws(\Seatplus\EsiClient\Exceptions\RequestFailedException::class);

    it('handles token expiry too far in future', function () {

        $requestFailedException = new RequestFailedException(
            new Exception('failed.', 403),
            new EsiResponse(json_encode(['error' => 'token expiry is too far in the future']), [], 'now', 200)
        );

        $getUpToDateRefreshTokenService = mock(GetUpToDateRefreshTokenService::class, function ($mock) use ($requestFailedException) {
            $mock->shouldReceive('__invoke')
                ->andThrow($requestFailedException);
        });

        $refreshToken = RefreshToken::factory()->create([
            'expires_on' => now()->addDays(30),
        ]);

        $expires_on = $refreshToken->expires_on;

        try {
            $service = new RetrieveEsiData(
                method: 'get',
                endpoint: 'foo/bar',
                version: 'v4',
                refresh_token: $refreshToken,
                getUpToDateRefreshTokenService: $getUpToDateRefreshTokenService
            );
        } catch (Exception $e) {
            //
            expect($e->getMessage())->toBe('token expiry is too far in the future');
        }

        expect($refreshToken->refresh()->expires_on->lessThan($expires_on))->toBeTrue();

    });

    it('handles token not able to login', function (string $message) {

        $requestFailedException = new RequestFailedException(
            new Exception('failed.', 400),
            new EsiResponse(json_encode(['error' => $message]), [], 'now', 200)
        );

        $getUpToDateRefreshTokenService = mock(GetUpToDateRefreshTokenService::class, function ($mock) use ($requestFailedException) {
            $mock->shouldReceive('__invoke')
                ->andThrow($requestFailedException);
        });

        $refreshToken = RefreshToken::factory()->create([
            'updated_at' => now()->subDays(20),
        ]);

        try {
            new RetrieveEsiData(
                method: 'get',
                endpoint: 'foo/bar',
                version: 'v4',
                refresh_token: $refreshToken,
                getUpToDateRefreshTokenService: $getUpToDateRefreshTokenService
            );
        } catch (Exception $e) {
            //
            expect($e->getMessage())->toBe($message);
        }

        expect($refreshToken->refresh()->trashed())->toBeTrue();

    })->with([
        'invalid_token: The refresh token is expired.',
        'invalid_token: The refresh token does not match the client specified.',
        'invalid_grant: Invalid refresh token. Character grant missing/expired.',
        'invalid_grant: Invalid refresh token. Unable to migrate grant.',
        'invalid_grant: Invalid refresh token. Token missing/expired.',
    ]);

});

it('builds client without refresh_token', function () {
    $service = new RetrieveEsiData(
        method: 'get',
        endpoint: 'foo/bar',
        version: 'v4',
    );

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);

    $client = $property->getValue($service);

    expect($client)->toBeInstanceOf(\Seatplus\EsiClient\EsiClient::class);
});
