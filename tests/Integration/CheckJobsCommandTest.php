<?php

use Seatplus\Eveapi\Commands\CheckJobsCommand;

it('should not fail', function () {
    $this->artisan(CheckJobsCommand::class)
        ->assertSuccessful();
});
