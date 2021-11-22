<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\FunctionalTest;

class CacheKeyHelperTest extends FunctionalTest
{
    public function testCacheParamKey(): void
    {
        $homePage = SiteTree::get_by_id(1);
        $response = $this->get('home/?x=40&y=52');
        error_log(print_r($response, true));

        // Home page should load..
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals('_x_', $homePage->CacheParamKey('x'));
        $this->assertEquals('_y_', $homePage->CacheParamKey('y'));

        // this will still work even if not present in the URL
        $this->assertEquals('_z_', $homePage->CacheParamKey('z'));
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
