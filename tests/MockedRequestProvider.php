<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache\Tests;

use SilverStripe\Control\HTTPRequest;
use WebOfTalent\Cache\RequestProvider;

class MockedRequestProvider implements RequestProvider
{
    /** @var \SilverStripe\Control\HTTPRequest */
    private $mockedRequest;

    public function __construct()
    {
        $this->mockedRequest = new HTTPRequest('GET', '/', [
            'page' => 2,
            'q' => 'Aristotle',
        ]);
    }


    public function getRequest(): HTTPRequest
    {
        return $this->mockedRequest;
    }
}
