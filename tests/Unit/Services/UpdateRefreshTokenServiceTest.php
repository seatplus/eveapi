<?php

use Seatplus\EsiClient\Services\UpdateRefreshTokenService;

it('updates RefreshToken', function ($refresh_token, $access_token, $expires_in) {
    $mock = Mockery::mock(UpdateRefreshTokenService::class);
    $mock->shouldReceive('getRefreshTokenResponse')
        ->andReturn([
            'refresh_token' => $refresh_token,
            'access_token' => $access_token,
            'expires_in' => $expires_in,
        ]);

    // don't let event listener interfere
    \Illuminate\Support\Facades\Event::fake();

    // run the test
    $new_token = \Seatplus\Eveapi\Services\Esi\UpdateRefreshTokenService::make()
        ->setRefreshTokenService($mock)
        ->update(testCharacter()->refresh_token);

    // assert
    expect($new_token)
        ->refresh_token->toBeString()->toBe($refresh_token)
        ->getRawOriginal('token')->toBeString()->toBe($access_token);

    Mockery::close();
})->with([
    [faker()->uuid, faker()->uuid(), faker()->randomNumber(4)],
]);
