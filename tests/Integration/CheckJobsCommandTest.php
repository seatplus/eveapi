<?php

it('should not fail', function () {
    $this->artisan('seatplus:check:endpoints')
        ->assertSuccessful();
});