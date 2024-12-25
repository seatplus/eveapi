<?php

arch()
    ->expect(['die', 'dd', 'dump'])
    ->not->toBeUsed();

arch()->preset()->php();
arch()->preset()->laravel();
arch()->preset()->security();
