<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PageHelpersTest extends TestCase
{
    public function testConfiguredPublicBaseUrlDefinesAppBasePath(): void
    {
        $config = [
            'publicBaseUrl' => 'https://example.com/uploader/',
        ];

        self::assertSame('/uploader/', \phpuploader_app_base_path($config));
    }

    public function testConfiguredPublicBaseUrlBuildsAbsoluteUrls(): void
    {
        $config = [
            'publicBaseUrl' => 'https://example.com/uploader/',
        ];
        $appBasePath = \phpuploader_app_base_path($config);

        self::assertSame(
            'https://example.com/uploader/image/cover.png',
            \phpuploader_absolute_url($appBasePath . 'image/cover.png', $appBasePath, $config)
        );

        self::assertSame(
            'https://example.com/uploader/show/123',
            \phpuploader_absolute_url('/uploader/show/123', $appBasePath, $config)
        );
    }
}
