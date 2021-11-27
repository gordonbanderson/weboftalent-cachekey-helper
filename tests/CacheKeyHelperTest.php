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
        $this->checkLastEditedFor($this->homePage, 'CurrentPage');
    }


    public function testCacheKeySiteTreeLastEdited(): void
    {
        $this->checkLastEditedFor($this->homePage, 'SiteTree');
    }


    public function testCacheKeyLevel2SiblingPageLastEdited(): void
    {
        // get the first page at the second level and note the last edited time
        $secondLevel1 = $this->objFromFixture(SiteTree::class, 'secondLevel1');
        $lastEdited = $secondLevel1->LastEdited;

        // wait, alter and then publish another item at the same level
        sleep(2);
        $secondLevel2 = $this->objFromFixture(SiteTree::class, 'secondLevel2');
        $secondLevel2->Content = 'This has been edited';
        $secondLevel2->write();
        $secondLevel2->publish('Stage', 'Live');

        $this->checkLastEditedFor($secondLevel1, 'SiblingPage', $lastEdited);
    }


    public function testCacheKeyLevel3SiblingPageLastEdited(): void
    {
        $thirdLevel1 = $this->objFromFixture(SiteTree::class, 'thirdLevel1');
        $lastEdited = $thirdLevel1->LastEdited;
        $this->checkLastEditedFor($this->homePage,'SiblingPage');

        sleep(2);
        $thirdLevel2 = $this->objFromFixture(SiteTree::class, 'thirdLevel2');
        $thirdLevel2->Content = 'This has been edited';
        $thirdLevel2->write();
        $thirdLevel2->publish('Stage', 'Live');

        $this->checkLastEditedFor($thirdLevel1, 'SiblingPage', $lastEdited);
    }


    /**
     * @param Page $page
     * @param string $entity
     * @param string|null $previousLastEdited
     * @return void
     * @throws \Exception
     */
    private function checkLastEditedFor($page, string $entity, $previousLastEdited = null): void
    {
        $expectedLastEdited = time();
        if (!is_null($previousLastEdited)) {
            $dt = new \DateTime($previousLastEdited);
            $expectedLastEdited = $dt->getTimestamp();
        }

        // the prefix test can be anything and should differ for different fragments of a page
        $cacheKey = $this->homePage->cacheKeyLastEdited('test', $entity);
        $this->assertStringStartsWith('test_', $cacheKey);
        $dateOnly = \substr($cacheKey, 5);
        $dt = new \DateTime($dateOnly);
        $timestamp = $dt->getTimestamp();
        error_log('T1: ' . $timestamp);
        error_log('T2: ' . $expectedLastEdited);
        $this->assertGreaterThan($timestamp, $expectedLastEdited);
    }
}
