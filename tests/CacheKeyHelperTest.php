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

        // @todo Is this correct
        $this->assertEquals('_page_2', $homePage->CacheParamKey('page'));
        $this->assertEquals('_q_Aristotle', $homePage->CacheParamKey('q'));

        // @todo Is this correct behaviour?
        $this->assertEquals('_z_', $homePage->CacheParamKey('z'));
    }


    /**
     * If a URL has likes of ?page=2 create a param cache key of the form page_2
     */
    public function testCacheKeyGetParam(): void
    {
        $homePage = SiteTree::get_by_id(1);

        $this->assertEquals('page_2', $homePage->CacheKeyGetParam('page'));
        $this->assertEquals('q_Aristotle', $homePage->CacheKeyGetParam('q'));
        $this->assertEquals('', $homePage->CacheKeyGetParam('doesnotexist'));
    }


    public function testPeriodKey(): void
    {
        $homePage = SiteTree::get_by_id(1);
        # 15 mins
        $periodKey = $homePage->PeriodKey(900);
        $this->assertStringStartsWith('period_900_', $periodKey);
        $splits = \explode('_', $periodKey);
        $thePeriod = \intval($splits[2]);

        // this is potentially fragile if the test is run at exactly the boundary of a 15 minute period
        $this->assertGreaterThanOrEqual($thePeriod*900, \time());
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
