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
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Security;
use WebOfTalent\Cache\CacheKeyHelper;
use WebOfTalent\Cache\CurrentControllerRequestProvider;
use WebOfTalent\Cache\RequestProvider;

class CurrentControllerRequestProviderTest extends SapphireTest
{
    public function testDefaultProvider()
    {
        /** @var RequestProvider $provider */
        $provider = new CurrentControllerRequestProvider();

        /** @var HTTPRequest $request */
        $request = $provider->getRequest();

        $this->assertInstanceOf('SilverStripe\Control\HTTPRequest', $request);
    }
}
