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

namespace Seatplus\Eveapi\Actions\Jobs\Universe;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\Constellation;

class ResolveUniverseConstellationByConstellationIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    private $path_values;

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/universe/constellations/{constellation_id}/';
    }

    public function getVersion(): string
    {
        return 'v1';
    }

    /**
     * @param mixed $path_values
     */
    public function setPathValues(array $path_values): void
    {
        $this->path_values = $path_values;
    }

    /**
     * @return mixed
     */
    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function execute(int $constellation_id)
    {
        $this->setPathValues([
            'constellation_id' => $constellation_id,
        ]);

        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        return Constellation::firstOrCreate(
            ['constellation_id' => $response->constellation_id],
            [
                'region_id' => $response->region_id,
                'name' => $response->name,
            ]
        );
    }
}
