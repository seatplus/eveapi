<?php

use Seatplus\Eveapi\Traits\HasPages;

beforeEach(function () {
    $this->trait = new class
    {
        use HasPages;
    };
});

it('returns initial page number', function () {
    $result = $this->trait->getPage();
    expect($result)->toBe(1);
});

it('increments page number', function () {
    $this->trait->incrementPage();
    $result = $this->trait->getPage();
    expect($result)->toBe(2);
});

it('increments page number multiple times', function () {
    $this->trait->incrementPage();
    $this->trait->incrementPage();
    $result = $this->trait->getPage();
    expect($result)->toBe(3);
});
