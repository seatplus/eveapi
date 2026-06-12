<?php

use Seatplus\Eveapi\Exceptions\SettingException;

test('set global setting', function () {
    $testValue = 'settingTest';
    setting(['test', $testValue]);

    $this->assertDatabaseHas('global_settings', [
        'name' => 'test',
    ]);

    expect(setting('test'))->toEqual($testValue);
});

test('get global setting', function () {
    $testingValue = bin2hex(random_bytes(10));

    // 1. try to get a non set setting, returning null
    $value = setting('test');

    expect($value)->toBeNull();

    // 2. set setting and expect the setting to return the previously set value.

    $value = setting(['test', $testingValue]);

    $this->assertNotNull($value);

    expect($testingValue)->toEqual($value);

    $this->assertDatabaseHas('global_settings', [
        'name' => 'test',
    ]);
});

test('setting throws SettingException when fewer than 2 keys given', function () {
    expect(fn () => setting(['only_one']))->toThrow(SettingException::class);
});

test('setting returns array directly when multiple values stored', function () {
    $values = ['alpha', 'beta'];
    setting(['multi', $values]);

    $result = setting('multi');

    expect($result)->toEqual($values);
});
