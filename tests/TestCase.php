<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanEntityProfiles();
    }

    protected function cleanEntityProfiles(): void
    {
        foreach (['business-profiles', 'courier-profiles', 'agency-profiles'] as $directory) {
            $path = storage_path('app/'.$directory);

            if (! is_dir($path)) {
                continue;
            }

            foreach (glob($path.'/*.json') ?: [] as $file) {
                @unlink($file);
            }
        }
    }
}
