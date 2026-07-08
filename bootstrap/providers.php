<?php

use App\Providers\RepositoryServiceProvider;
use Illuminate\Support\Facades\Gate;

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\SecurityServiceProvider::class,
    RepositoryServiceProvider::class,
];
