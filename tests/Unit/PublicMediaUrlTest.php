<?php

namespace Tests\Unit;

use App\Support\PublicMediaUrl;
use PHPUnit\Framework\TestCase;

class PublicMediaUrlTest extends TestCase
{
    public function test_from_path_returns_relative_storage_url(): void
    {
        $this->assertSame(
            '/storage/business-logos/1/logo.png',
            PublicMediaUrl::fromPath('business-logos/1/logo.png')
        );
    }

    public function test_normalize_converts_absolute_storage_url_to_relative(): void
    {
        $this->assertSame(
            '/storage/business-logos/1/logo.png',
            PublicMediaUrl::normalize('http://localhost/storage/business-logos/1/logo.png')
        );
    }

    public function test_normalize_keeps_relative_storage_url(): void
    {
        $this->assertSame(
            '/storage/courier-photos/2/photo.jpg',
            PublicMediaUrl::normalize('/storage/courier-photos/2/photo.jpg')
        );
    }
}
