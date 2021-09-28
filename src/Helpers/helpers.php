<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use Seatplus\Eveapi\Exceptions\SettingException;
use Seatplus\Eveapi\Models\Settings\GlobalSettings;

if (! function_exists('setting')) {

    /**
     * Work with settings.
     *
     * Providing a string argument will retrieve a setting.
     * Providing an array argument will set a setting.
     *
     * @param      $name
     * @param bool $global
     *
     * @return mixed
     *
     * @throws \Seatplus\Eveapi\Exceptions\SettingException
     */
    function setting($name)
    {

        // If we received an array, it means we want to set.
        if (is_array($name)) {

            // Check that we have at least 2 keys.
            if (count($name) < 2) {
                throw new SettingException('Must provide a name and value when setting a setting.');
            }

            $setting = GlobalSettings::updateOrCreate(
                ['name' => $name[0]],
                ['value' => $name[1]]
            )
                ->value;

            return is_countable($setting) ? (count($setting) > 1 ? $setting : $setting[0]) : $setting;
        }

        // If we just got a string, it means we want to get.
        $setting = optional(GlobalSettings::where('name', $name)->first())->value;

        return is_countable($setting) ? (count($setting) > 1 ? $setting : $setting[0]) : $setting;
    }
}

if (! function_exists('carbon')) {

    /**
     * A helper to get a fresh instance of Carbon.
     *
     * @param null $data
     *
     * @return \Carbon\Carbon
     */
    function carbon($data = null)
    {
        if (! is_null($data)) {
            return new \Carbon\Carbon($data);
        }

        return new \Carbon\Carbon;
    }
}
