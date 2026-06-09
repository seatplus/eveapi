<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Contacts;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\EsiResult;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Services\Contacts\ProcessContactLabelsResponse;
use Seatplus\Eveapi\Services\Contacts\ProcessContactResponse;

abstract class ContactBaseJob extends EsiJob
{
    abstract protected function fetchPage(EsiClient $esi, int $page): EsiResult;

    protected function handleProcessor(ProcessContactLabelsResponse|ProcessContactResponse $processor, EsiClient $esi): void
    {
        $knownIds = collect();
        $page = 1;

        do {
            $response = $this->fetchPage($esi, $page);
            if ($response->isCachedLoad) {
                return;
            }

            $processedIds = $processor->execute($response);
            $knownIds->push($processedIds);
            $page++;
        } while ($page <= $response->pages);

        $processor->remove_old_entries($knownIds->flatten()->unique()->toArray());
    }
}
