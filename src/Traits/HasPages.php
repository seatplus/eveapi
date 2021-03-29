<?php


namespace Seatplus\Eveapi\Traits;


trait HasPages
{
    public int $page = 1;

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function incrementPage(): void
    {
        $this->page++;
    }

}