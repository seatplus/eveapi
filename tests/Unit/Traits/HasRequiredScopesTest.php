<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

beforeEach(function () {
    $this->trait = new class
    {
        use HasRequiredScopes;
    };
});

it('returns refresh token for alliance_id', function () {

    Event::fake();
    // arrange
    $alliance_id = testCharacter()->alliance_id;
    $refreshToken = testCharacter()->refresh_token;

    // if no refresh token exists, create one
    if (! $refreshToken) {
        $refreshToken = RefreshToken::factory()->create([
            'character_id' => testCharacter()->character_id,
        ]);
    }

    expect($refreshToken)->toBeInstanceOf(RefreshToken::class);

    Event::fakeFor(fn () => updateRefreshTokenScopes($refreshToken, ['scope'])->save());

    $this->trait->alliance_id = $alliance_id;
    $this->trait->setRequiredScope('scope');

    expect($refreshToken->hasScope('scope'))->toBeTrue();

    // act

    $result = $this->trait->getRefreshToken();

    // assert
    expect($result->character_id)->toBe($refreshToken->refresh()->character_id);
});

it('throws exception if no refresh token is found for alliance_id', function () {

    // arrange
    $alliance_id = 1234;

    $this->trait->alliance_id = $alliance_id;
    $this->trait->setRequiredScope('scope');

    // act
    $result = $this->trait->getRefreshToken();

    // assert
})->throws(Exception::class, 'Could not find refresh token for alliance_id');
