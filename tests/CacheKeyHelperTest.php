<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\FunctionalTest;

class CacheKeyHelperTest extends FunctionalTest
{
    /** @var \SilverStripe\CMS\Model\SiteTree */
    private $homePage;

    public function setUp(): void
    {
        parent::setUp();

        $this->homePage = SiteTree::get_by_id(1);
    }


    public function testCacheKeyParamVar(): void
    {
        // These are valid GET variables, but params come from the routing
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('page'));
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('q'));

        // this does not exist as a get variable, or a parameter
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('doesnotexist'));


//        // @todo Is this correct behaviour?
//        $this->assertEquals('_z_', $homePage->CacheParamKey('z'));
//
//        //
//        $this->assertEquals('_z_', $homePage->CacheParamKey('TopTwoLevelsLastEdited'));
    }


    /**
     * If a URL has likes of ?page=2 create a param cache key of the form page_2
     */
    public function testCacheKeyGetVar(): void
    {
        $this->assertEquals('_page_2', $this->homePage->CacheKeyGetVar('page'));
        $this->assertEquals('_q_Aristotle', $this->homePage->CacheKeyGetVar('q'));
        $this->assertEquals('', $this->homePage->CacheKeyGetVar('doesnotexist'));
    }


    public function testPeriodKey(): void
    {
        # 15 mins
        $periodKey = $this->homePage->PeriodKey(900);
        $this->assertStringStartsWith('period_900_', $periodKey);
        $splits = \explode('_', $periodKey);
        $thePeriod = \intval($splits[2]);

        // this is potentially fragile if the test is run at exactly the boundary of a 15 minute period
        $this->assertGreaterThanOrEqual($thePeriod*900, \time());
    }


    public function testCacheKeyLastEdited(): void
    {
        $homePage = SiteTree::get_by_id(1);
        $this->assertEquals('wibble', $homePage->CacheKeyLastEdited('testing', 'CurrentPage'));
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
