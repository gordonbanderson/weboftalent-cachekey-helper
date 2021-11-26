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
use WebOfTalent\Cache\RequestProvider;

class MockedRequestProvider implements RequestProvider
{
    /** @var HTTPRequest */
    private $mockedRequest;

    /**
     * @param HTTPRequest $request
     */
    public function __construct($request)
    {
        $this->mockedRequest = $request;
    }

    /**
     * @return HTTPRequest
     */
    public function getRequest()
    {
        return $this->mockedRequest;
    }
}
