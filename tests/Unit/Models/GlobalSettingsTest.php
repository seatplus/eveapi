<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Settings\GlobalSettings;
use Seatplus\Eveapi\Tests\TestCase;

class GlobalSettingsTest extends TestCase
{

    /** @test */
    public function setGlobalSetting()
    {
        $test_value = 'settingTest';
        setting(['test', $test_value]);

        $this->assertDatabaseHas('global_settings', [
            'name' => 'test',
        ]);

        $this->assertEquals($test_value, setting('test'));

    }

    /** @test */
    public function getGlobalSetting()
    {

        $testing_value = bin2hex(random_bytes(10));

        // 1. try to get a non set setting, returning null
        $value = setting('test');

        $this->assertNull($value);

        // 2. set setting and expect the setting to return the previously set value.

        $value = setting(['test', $testing_value]);

        $this->assertNotNull($value);

        $this->assertEquals($value,$testing_value);

        $this->assertDatabaseHas('global_settings', [
            'name' => 'test',
        ]);

    }
}
