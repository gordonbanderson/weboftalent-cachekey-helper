<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

// @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
// @phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
class CacheKeyHelper extends DataExtension
{

    private static $indexes = [
        'LastEdited' => true
        ];

    /** @var \WebOfTalent\Cache\RequestProvider | null */
    private $requestProvider ;

    /** @var array<string,string> <Name>LastEdited -> some calculated key value */
    private static $last_edited_values = [];

    /** @var bool Flag to ensure that the query is run only once */
    private static $cachekeysinitialised = false;


    /**
    * Obtain a part of the cache key fragment based on a parameter name obtained from routing only
    * In a template this would look like <tt>$CacheParamKey('start')</tt>
     *
     * @param string $parameterName the parameter, obtained from routing, being used to cache
    * @return string a unique string suitable as a cache key related to the current request
    */
    public function CacheKeyParamVar(string $parameterName): string
    {
        $this->prime_cache_keys();

        // if no parameter match, we wish to return a blank
        $result = '';

        /* @phpstan-ignore-next-line */
        $request = $this->requestProvider->getRequest();

        // SilverStripe PHP doc is incorrect here, as such override
        /** @var string|null $paramValue */
        $paramValue = $request->param($parameterName);

        if (isset($paramValue)) {
            $result = '_'.$parameterName . '_' . $paramValue;
        }

        return $result;
    }


    /**
     * Append a url parameter to the cache key. This is useful for example when using pagination
     *
     * @param string $getVarName The name of the GET variable
     */
    public function CacheKeyGetVar(string $getVarName): string
    {
        /* @phpstan-ignore-next-line */
        $getvars = $this->requestProvider->getRequest()->getVars();
        $result = '';
        if (isset($getvars[$getVarName])) {
            $result = '_' . $getVarName . '_' . $getvars[$getVarName];
        }

        return $result;
    }


    /**
     * Provide for a portion of a key that is cached for a certain amount of time. This is useful
     * for calling external APIs, where you do not want to hit them every request (for example,
     * getting the current weather could be delayed to every 15 mins)
     *
     * @param int $periodInSeconds the length of time the cache key should be valid
     */
    public function PeriodKey(int $periodInSeconds): string
    {
        return 'period_' . $periodInSeconds . '_' . (int)(\time() / $periodInSeconds);
    }


    /**
    Obtain a cache for a given class with a given prefix, to be used in templates for partial
    caching. It is designed to ensure that the query for caching is only called once and that all
    caching values are calculated at this time. They are subsequently stored in a
    local static variable for use in other parts of the page being rendered.

    @param string $prefix - an arbitrary prefix to differentiate from different areas of a page of pages,
    e.g. folderofarticles,homepagearticles
    @param string $classname - the classname that we wish to find the most recent last edited value of
     * @return string the key for the possibly cached data
    */
    public function CacheKeyLastEdited(string $prefix, string $classname): string
    {
        $this->prime_cache_keys();
        $result = '';
        if (isset(self::$last_edited_values[$classname.'LastEdited'])) {
            $result = $prefix.'_'.self::$last_edited_values[$classname.'LastEdited'];
        }

        return $result;
    }


    public static function resetCache(): void
    {
        self::$cachekeysinitialised = false;
        self::$last_edited_values = [];
    }

    /*
    Using configuration values for classes required to be cache keys from both SiteTree andDataObject,
     form a large single query to get last edited values for each of these individual classes.  Also
    for good measure the following are added  automatically:
    - CurrentPageLastEdited: The last edited value of the current page being viewed
    - ChildPageLastEdited: The last childPage object to be edited, useful when rendering a folder
    - SiblingPageLastEdited: Most recent last edited value of a page that is a sibling to the
        current one.  Useful for sidebar navigation
    - TopTwoLevelsLastEdited: Last edited for the first 2 levels of the SiteTree, for cache busting
        the top menu
    - CurrentMemberLastEdited: Last edited for the current user, rounded to the nearest 5 mins.
        The reason for the rounding is that LastVisited is updated every page load
    - SiteConfigLastEdited: Last edited value of the site configuration
    - SiteTreeLastEdited: Most recent item edited on the entire site
    */
    private function prime_cache_keys(): void
    {

        if (self::$cachekeysinitialised) {
            return;
        }

        if (\is_null($this->requestProvider)) {
            $this->requestProvider = Injector::inst()->get('WebOfTalent\Cache\CurrentControllerRequestProvider');
        }

        // get the classes to get a cache key with from the site tree
        // @phpstan-ignore-next-line
        $classes = $this->getOwner()->config()->get(SiteTree::class);

        $sql = 'SELECT (SELECT MAX("LastEdited") FROM "SiteTree_Live" WHERE "ParentID" = '.
            $this->owner->ID.') AS "ChildPageLastEdited",';

        if ($classes) {
            foreach ($classes as $classname) {
                $tableName = $this->getTableName($classname);

                $stanza = '(SELECT MAX("LastEdited") from "SiteTree_Live" '
                    . "WHERE \"ClassName\" = '". $classname."')  AS \"{$tableName}LastEdited\" , ";
                $sql .= $stanza;
            }
        }

        // get the classes to get a cache key with that are not in the site tree
        // @phpstan-ignore-next-line
        $classes = $this->getOwner()->config()->get(DataObject::class);

        if ($classes) {
            foreach ($classes as $classname) {
                $tableName = $this->getTableName($classname);
                $stanza = '(SELECT MAX("LastEdited") from "'.$tableName.'") AS "' .$tableName .'LastEdited", ';
                $sql .= $stanza;
            }
        }

        $sql .= '(SELECT Max("LastEdited") FROM
					(
						SELECT "LastEdited" FROM "SiteTree_Live"
						WHERE "ParentID" = 0
						AND "ShowInMenus" = 1

						UNION
						SELECT "LastEdited" FROM "SiteTree_Live"
						WHERE "ParentID" IN
							(SELECT "ID" FROM "SiteTree_Live" WHERE "ParentID" = 0 AND "ShowInMenus" = 1)
						AND "ShowInMenus" = 1
					) AS "TopLevels"

				  ) AS "TopTwoLevelsLastEdited",';

        // if there is a member, get the last edited - cache for 5 mins (300 seconds), as Member is
        // saved every page request to update last visited
  //      $sql .= "(SELECT CONCAT(ID,'_',LastEdited/300) from Member where ID=".
  //          Member::currentUserID().') as CurrentMemberLastEdited,';

        // site config
        $sql .= '(SELECT "LastEdited" FROM "SiteConfig") AS "SiteConfigLastEdited", ';

        // the current actual page
        // @phpstan-ignore-next-line
        $sql .= "(SELECT \"LastEdited\" FROM \"SiteTree_Live\" WHERE \"ID\"=".$this->getOwner()->ID.
                ") AS \"CurrentPageLastEdited\", ";

        // siblings, needed for side menu
        $sql .= '(SELECT MAX("LastEdited") FROM "SiteTree_Live" WHERE "ParentID"='.
            // @phpstan-ignore-next-line
            $this->getOwner()->ParentID.') as "SiblingPageLastEdited", ';

        // add a clause to check if any page on the site has changed, a major cache buster
        $sql .= '(SELECT MAX("LastEdited") FROM "SiteTree_Live") AS "SiteTreeLastEdited";';


        $records = DB::query($sql)->first();


        // @TODO is this necessary as POST vars would override
        // now append the request params, stored as PARAM_<parameter name> -> parameter value
        foreach ($this->requestProvider->getRequest()->requestVars() as $k => $v) {
            $records['PARAM_'.$k] = $v;
        }
        self::$cachekeysinitialised = true;
        self::$last_edited_values = $records;
    }


    /** @return string the table name associated with the classname above */
    private function getTableName(string $classname): string
    {
        return Config::inst()->get($classname, 'table_name');
    }
}
