<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\FunctionalTest;

class CacheKeyHelperTest extends FunctionalTest
{
    protected static $fixture_file = 'fixtures.yml';

    /** @var \SilverStripe\CMS\Model\SiteTree */
    private $homePage;

    public function setUp(): void
    {
        parent::setUp();

        $this->homePage = $this->objFromFixture(SiteTree::class, 'homepage');
        /** @var array<\Page> $pages */
        $pages = SiteTree::get();
        foreach ($pages as $page) {
            $page->publishRecursive();
        }
    }


    public function testCacheKeyParamVar(): void
    {
        // These are valid GET variables, but params come from the routing
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('page'));
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('q'));

        // this does not exist as a get variable, or a parameter
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('doesnotexist'));

        \error_log('Title of home page: ' . $this->homePage->Title);
        \error_log('Last edited of home page: ' . $this->homePage->LastEdited);
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

/*
 *     [ChildPageLastEdited] =>
    [MemberLastEdited] => 2021-11-26 21:53:39
    [GroupLastEdited] => 2021-11-26 21:53:39
    [FileLastEdited] =>
    [TopTwoLevelsLastEdited] => 2021-11-26 21:53:40
    [SiteConfigLastEdited] => 2021-11-26 21:53:39
    [SiblingPageLastEdited] => 2021-11-26 21:53:40
    [PARAM_page] => 2
    [PARAM_q] => Aristotle

 */
    public function testCacheKeyCurrentPageLastEdited(): void
    {
        $this->checkLastEditedFor('CurrentPage');
    }


    public function testCacheKeySiteTreeLastEdited(): void
    {
        $this->checkLastEditedFor('SiteTree');
    }


    public function testCacheKeySiblingPageLastEdited(): void
    {
        $this->checkLastEditedFor('SiblingPage');
    }


    /** @throws \Exception */
    private function checkLastEditedFor(string $entity, $expectedOriginalTime = null): void
    {
        if (is_null($expectedOriginalTime)) {
            $expectedOriginalTime = time();
        }

        // the prefix test can be anything and should differ for different fragments of a page
        $cacheKey = $this->homePage->cacheKeyLastEdited('test', $entity);
        $this->assertStringStartsWith('test_', $cacheKey);
        $dateOnly = \substr($cacheKey, 5);
        $dt = new \DateTime($dateOnly);
        $timestamp = $dt->getTimestamp();
        $this->assertGreaterThan($timestamp, $expectedOriginalTime);
    }
}
