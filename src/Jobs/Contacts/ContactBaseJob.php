<?php

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
        $known_ids = collect();
        $page = 1;

        do {
            $response = $this->fetchPage($esi, $page);
            if ($response->isCachedLoad) {
                return;
            }

            $processed_ids = $processor->execute($response);
            $known_ids->push($processed_ids);
            $page++;
        } while ($page <= $response->pages);

        $processor->remove_old_entries($known_ids->flatten()->unique()->toArray());
    }
}
