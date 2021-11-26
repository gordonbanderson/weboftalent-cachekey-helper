<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache\Tests;

use http\Env\Request;
use PHPStan\BetterReflection\Reflection\ReflectionProperty;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Security;
use WebOfTalent\Cache\CacheKeyHelper;

class CacheKeyHelperTest extends FunctionalTest
{
    public function testCacheParamKey(): void
    {
        $homePage = SiteTree::get_by_id(1);

        $this->assertEquals('_page_', $homePage->CacheParamKey('page'));
        $this->assertEquals('_q_', $homePage->CacheParamKey('q'));

        // this will still work even if not present in the URL
        $this->assertEquals('_z_', $homePage->CacheParamKey('z'));
    }


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
