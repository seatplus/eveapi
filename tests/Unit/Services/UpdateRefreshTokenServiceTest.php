<?php

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Seatplus\EsiClient\DataTransferObjects\EsiAuthentication;
use Seatplus\EsiClient\Services\UpdateRefreshTokenService as EsiClientUpdateToken;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Esi\UpdateRefreshTokenService;

beforeEach(function () {
    $this->service = UpdateRefreshTokenService::make();
    Event::fake();
});

it('updates refresh token successfully', function () {

    $esiClientUpdateToken = mock(EsiClientUpdateToken::class, function (MockInterface $mock) {

        $mock->shouldReceive('getRefreshTokenResponse')
            ->with(Mockery::type(EsiAuthentication::class))
            ->andReturn([
                'access_token' => 'new_access_token',
                'expires_in' => 3600,
                'refresh_token' => 'new_refresh_token',
            ]);
    });

    $this->service->setRefreshTokenService($esiClientUpdateToken);

    $result = $this->service->update(RefreshToken::first());

    expect($result)
        ->toBeInstanceOf(RefreshToken::class)
        ->refresh_token->toBe('new_refresh_token')
        ->getRawOriginal('token')->toBe('new_access_token');
});

it('sets refresh token service if not set', function () {
    $result = $this->service->getRefreshTokenService();
    expect($result)->toBeInstanceOf(EsiClientUpdateToken::class);
});

it('uses existing refresh token service if already set', function () {
    $esiClientUpdateToken = Mockery::mock(EsiClientUpdateToken::class);
    $this->service->setRefreshTokenService($esiClientUpdateToken);

    $result = $this->service->getRefreshTokenService();
    expect($result)->toBe($esiClientUpdateToken);
});
