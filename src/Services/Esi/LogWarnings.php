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

namespace Seatplus\Eveapi\Services\Esi;

use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Eseye;

class LogWarnings
{
    protected $eseye;

    public function setEseyeClient(Eseye $eseye)
    {
        $this->eseye = $eseye;

        return $this;
    }

    public function execute(EsiResponse $response, int $page = null): void
    {
        if (! is_null($response->pages) && $page === null) {
            $this->eseye->getLogger()->warning('Response contained pages but none was expected');
        }

        if (! is_null($page) && $response->pages === null) {
            $this->eseye->getLogger()->warning('Expected a paged response but had none');
        }

        if (array_key_exists('Warning', $response->headers)) {
            $this->eseye->getLogger()->warning('A response contained a warning: ' .
                $response->headers['Warning']);
        }
    }
}
