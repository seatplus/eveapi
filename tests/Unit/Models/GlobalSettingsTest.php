<?php


use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('set global setting', function () {
    $test_value = 'settingTest';
    setting(['test', $test_value]);

    $this->assertDatabaseHas('global_settings', [
        'name' => 'test',
    ]);

    $this->assertEquals($test_value, setting('test'));
});

test('get global setting', function () {
    $testing_value = bin2hex(random_bytes(10));

    // 1. try to get a non set setting, returning null
    $value = setting('test');

    $this->assertNull($value);

    // 2. set setting and expect the setting to return the previously set value.

    $value = setting(['test', $testing_value]);

    $this->assertNotNull($value);

    $this->assertEquals($value, $testing_value);

    $this->assertDatabaseHas('global_settings', [
        'name' => 'test',
    ]);
});
