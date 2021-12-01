<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\FunctionalTest;
use WebOfTalent\Cache\CacheKeyHelper;

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

        CacheKeyHelper::resetCache();
    }


    public function testCacheKeyParamVar(): void
    {
        // These are valid GET variables, but params come from the routing
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('page'));
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('q'));

        // this does not exist as a get variable, or a parameter
        $this->assertEquals('', $this->homePage->CacheKeyParamVar('doesnotexist'));
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
    [SiteConfigLastEdited] => 2021-11-26 21:53:39
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


    public function testCacheKeyLevel1SiblingPageLastEdited(): void
    {
        // get the first page at the second level and note the last edited time
        $homepage = $this->objFromFixture(SiteTree::class, 'homepage');
        $lastEdited = $homepage->LastEdited;

        // wait, alter and then publish another item at the same level
        \sleep(2);
        $topLevel2 = $this->objFromFixture(SiteTree::class, 'topLevel2');
        $topLevel2->Content = 'This has been edited';
        $topLevel2->write();
        $topLevel2->publish('Stage', 'Live');

        $homepage = $this->objFromFixture(SiteTree::class, 'homepage');
        $this->checkLastEditedFor($homepage, 'SiblingPage', $lastEdited);
    }


    public function testCacheKeyLevel2SiblingPageLastEdited(): void
    {
        // get the first page at the second level and note the last edited time
        $secondLevel1 = $this->objFromFixture(SiteTree::class, 'secondLevel1');
        $lastEdited = $secondLevel1->LastEdited;

        // wait, alter and then publish another item at the same level
        \sleep(2);
        $secondLevel2 = $this->objFromFixture(SiteTree::class, 'secondLevel2');
        $secondLevel2->Content = 'This has been edited';
        $secondLevel2->write();
        $secondLevel2->publish('Stage', 'Live');

        $secondLevel1 = $this->objFromFixture(SiteTree::class, 'secondLevel1');
        $this->checkLastEditedFor($secondLevel1, 'SiblingPage', $lastEdited);
    }


    public function testCacheKeyLevel3SiblingPageLastEdited(): void
    {
        $thirdLevel1 = $this->objFromFixture(SiteTree::class, 'thirdLevel1');
        $lastEdited = $thirdLevel1->LastEdited;

        \sleep(2);
        $thirdLevel2 = $this->objFromFixture(SiteTree::class, 'thirdLevel2');
        $thirdLevel2->Content = 'This has been edited';
        $thirdLevel2->write();
        $thirdLevel2->publish('Stage', 'Live');

        $thirdLevel1 = $this->objFromFixture(SiteTree::class, 'thirdLevel1');
        $this->checkLastEditedFor($thirdLevel1, 'SiblingPage', $lastEdited);
    }


    public function testCacheKeyLevel1TopTwoLevelsLastEdited(): void
    {
        // get the first page at the second level and note the last edited time
        $homepage = $this->objFromFixture(SiteTree::class, 'homepage');
        $lastEdited = $this->getLastEditedTopTwoLevels($homepage);

        // wait, alter and then publish another item at the same level
        \sleep(2);
        $topLevel2 = $this->objFromFixture(SiteTree::class, 'topLevel2');
        $topLevel2->Content = 'This has been edited';
        $topLevel2->write();
        $topLevel2->publish('Stage', 'Live');

        $homepage = $this->objFromFixture(SiteTree::class, 'homepage');
        $this->checkLastEditedFor($homepage, 'TopTwoLevels', $lastEdited);
    }


    public function testCacheKeyLevel2TopTwoLevelsLastEdited(): void
    {
        // get the first page at the second level and note the last edited time
        $secondLevel1 = $this->objFromFixture(SiteTree::class, 'secondLevel1');
        $lastEdited = $this->getLastEditedTopTwoLevels($secondLevel1);

        // wait, alter and then publish another item at the same level
        \sleep(2);
        $secondLevel2 = $this->objFromFixture(SiteTree::class, 'secondLevel2');
        $secondLevel2->Content = 'This has been edited';
        $secondLevel2->write();
        $secondLevel2->publish('Stage', 'Live');

        $secondLevel1 = $this->objFromFixture(SiteTree::class, 'secondLevel1');
        $this->checkLastEditedFor($secondLevel1, 'TopTwoLevels', $lastEdited);
    }




    // @todo This appears to be flakey in CI, fix
    public function testCacheKeyLevel3TopTwoLevelsLastEdited(): void
    {
        $thirdLevel1 = $this->objFromFixture(SiteTree::class, 'thirdLevel1');
        $lastEdited = $this->getLastEditedTopTwoLevels($thirdLevel1);

        \sleep(2);
        $thirdLevel2 = $this->objFromFixture(SiteTree::class, 'thirdLevel2');
        $thirdLevel2->Content = 'This has been edited';
        $thirdLevel2->write();
        $thirdLevel2->publish('Stage', 'Live');

        // reload and then check that the timestamps are the same  Third level is NOT cache busted
        $thirdLevel1 = $this->objFromFixture(SiteTree::class, 'thirdLevel1');
        $this->checkLastEditedFor($thirdLevel1, 'TopTwoLevels', $lastEdited, false, true);
    }


    private function getLastEditedTopTwoLevels($page)
    {
        $key = $page->CacheKeyLastEdited('', 'TopTwoLevels');

        // remove leading underscore
        $key = substr($key, 1);

        return $key;
    }


    /** @throws \Exception */
    private function checkLastEditedFor(
        SiteTree $page,
        string $entity,
        ?string $previousLastEdited = null,
        bool $greaterThanCheck = true,
        bool $sameAsCheck = false
    ): void {
        $expectedLastEdited = \time();
        if (!\is_null($previousLastEdited)) {
            $dt = new \DateTime($previousLastEdited);
            $expectedLastEdited = $dt->getTimestamp();
        }

        // the prefix test can be anything and should differ for different fragments of a page
        CacheKeyhelper::resetCache();
        $cacheKey = $page->cacheKeyLastEdited('test', $entity);
        $this->assertStringStartsWith('test_', $cacheKey);
        $dateOnly = \substr($cacheKey, 5);
        $dt = new \DateTime($dateOnly);
        $timestamp = $dt->getTimestamp();

        if ($greaterThanCheck && !\is_null($previousLastEdited)) {
            $this->assertGreaterThan($expectedLastEdited, $timestamp);
        }

        if (!$sameAsCheck) {
            return;
        }

        $this->assertEquals($timestamp, $expectedLastEdited);
    }
}
