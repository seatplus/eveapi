<?php

it('throws error if not enough arguments are provided', function () {
    setting(['test']);
})->expectExceptionMessage('Must provide a name and value when setting a setting.');
