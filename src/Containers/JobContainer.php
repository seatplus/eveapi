<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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

namespace Seatplus\Eveapi\Containers;

use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class JobContainer
{
    protected $data = [
        'refresh_token' => null,
        'character_id' => null,
        'corporation_id' => null,
        'alliance_id' => null,
        'queue' => 'default',
    ];

    public function __construct(array $data = null)
    {
        if (! is_null($data)) {
            foreach ($data as $key => $value) {
                if (! array_key_exists($key, $this->data)) {
                    throw new InvalidContainerDataException(
                        'Key ' . $key . ' is not valid for this container'
                    );
                }

                $this->$key = $value;
            }
        }
    }

    public function __set($key, $value): void
    {
        if (array_key_exists($key, $this->data)) {
            $this->data[$key] = $value;
        }
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return '';
    }

    public function getCharacterId()
    {
        return $this->character_id ?? optional($this->refresh_token)->character_id;
    }

    public function getCorporationId()
    {
        return $this->corporation_id ?? optional(CharacterInfo::find($this->getCharacterId()))->corporation_id;
    }

    public function getAllianceId()
    {
        return $this->alliance_id ?? optional(CharacterInfo::find($this->getCharacterId()))->alliance_id;
    }

    public function getRefreshToken()
    {
        return $this->refresh_token;
    }
}
