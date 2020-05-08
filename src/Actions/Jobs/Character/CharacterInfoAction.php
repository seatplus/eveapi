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

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class CharacterInfoAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    protected $path_values;

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/characters/{character_id}/';
    }

    public function getVersion(): string
    {
        return 'v4';
    }

    public function getPathValues(): array
    {

        return $this->path_values;
    }

    public function execute(int $character_id)
    {

        $this->setPathValues([
            'character_id' => $character_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) return;

        CharacterInfo::updateOrCreate([
            'character_id' => $character_id,
        ], [
            'name'            => $response->name,
            'description'     => $response->optional('description'),
            'birthday'        => $response->birthday,
            'gender'          => $response->gender,
            'race_id'         => $response->race_id,
            'bloodline_id'    => $response->bloodline_id,
            'ancestry_id'    => $response->optional('ancestry_id'),
            'security_status' => $response->optional('security_status'),
            'faction_id'      => $response->optional('faction_id'),
            'title' => $response->optional('title'),
        ]);

    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }
}
