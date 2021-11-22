<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache\Tests;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Dev\SapphireTest;

class CacheKeyHelperTest extends FunctionalTest
{
    public function testCacheParamKey(): void
    {
        $page = $this->get('home/');

        // Home page should load..
        $this->assertEquals(200, $page->getStatusCode());

    }


    public function testCacheKeyGetParam(): void
    {
        $this->markTestSkipped('TODO');
    }


    public function testCacheKey(): void
    {
        $this->markTestSkipped('TODO');
    }


    public function testCacheDataKey(): void
    {
        $this->markTestSkipped('TODO');
    }


    public function testPrimeCacheKeys(): void
    {
        $this->markTestSkipped('TODO');
    }
}
