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
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Security;

class CacheKeyHelperTest extends FunctionalTest
{
    public function testCacheParamKey(): void
    {
        $homePage = SiteTree::get_by_id(1);
        $response = $this->get('home/?x=40&y=52');


        // Home page should load..
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals('_x_', $homePage->CacheParamKey('x'));
        $this->assertEquals('_y_', $homePage->CacheParamKey('y'));

        // this will still work even if not present in the URL
        $this->assertEquals('_z_', $homePage->CacheParamKey('z'));
    }


    public function testCacheKeyGetParam(): void
    {
//        $homePage = SiteTree::get_by_id(1);
//        $response = $this->get('home/?x=40&y=52');


        $request = new HTTPRequest('GET', '/');
        $request->setUrl('/x=40&y=20');

        $request->setSession(new Session([]));
        $security = new Security();
        $security->setRequest($request);
        $reflection = new \ReflectionClass($security);
        $method = $reflection->getMethod('getResponseController');
        $method->setAccessible(true);
        $result = $method->invoke($security, 'Page');

        // Ensure page shares the same controller as security
        $this->assertInstanceOf(\PageController::class, $result);
        $this->assertEquals($request, $result->getRequest());

        $c = Controller::curr();
        error_log(print_r($c->getRequest()->requestVars(), true));
        error_log(print_r($c->getRequest()->getVars(), true));

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
