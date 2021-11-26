<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

// @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
// @phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
class CacheKeyHelper extends DataExtension
{
    /** @var array<string,string> <Name>LastEdited -> some calculated key value */
    private static $last_edited_values = array();

    /** @var bool Flag to ensure that the query is run only once */
    private static $cachekeysinitialised = false;

    /** @var RequestProvider */
    private $requestProvider;

    public function __construct()
    {
        parent::__construct();
        $this->requestProvider = Injector::inst()->get('WebOfTalent\Cache\CurrentControllerRequestProvider');
    }

    /**
    * Obtain a part of the cache key fragment based on a parameter name
    * In a template this would look like <tt>$CacheParamKey('start')</tt>

    * @param string $param the parameter being used to cache
    * @return string a unique string suitable as a cache key
    */
    public function CacheParamKey(string $param): string
    {
        $this->prime_cache_keys();

        // check URL parameters
        $key = 'PARAM_'.$param;
        $value = null;
        if (isset(self::$last_edited_values[$key])) {
            $value = self::$last_edited_values[$key];
        };

        // if still null check parameters from routing configuration
        if ($value === null) {
            $request = $this->requestProvider->getRequest();
            $value = $request->param($param);
        }

        return '_'.$param.'_'.$value;
    }


    /**
     * Append a url parameter to the cache key. This is useful for example when using pagination
     */
    public function CacheKeyGetParam(string $parameterName): string
    {
        $getvars = $this->requestProvider->getRequest()->getVars();
        error_log('CACHE KEY GET PARAM: getvars=' );
        error_log(print_r($getvars, true));
        $result = '';
        if (isset($getvars[$parameterName])) {
            $result = $parameterName . '_' . $getvars[$parameterName];
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
    * Old name for this method, as request params now included
     *
     * @param string $prefix - an arbitrary prefix to differentiate from different areas of a page of pages,
       e.g. folderofarticles,homepagearticles
     * @param string $classname - the classname that we wish to find the most recent last edited value of
     */
    public function CacheKey(string $prefix, string $classname): string
    {
        $this->prime_cache_keys();
        return $this->CacheDataKey($prefix, $classname);
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
    public function CacheDataKey(string $prefix, string $classname): string
    {
        $this->prime_cache_keys();
        return $prefix.'_'.self::$last_edited_values[$classname.'LastEdited'];
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

        // get the classes to get a cache key with from the site tree
        // @phpstan-ignore-next-line
        $classes = $this->getOwner()->config()->get(SiteTree::class);

        $sql = 'SELECT (SELECT MAX(LastEdited) FROM SiteTree_Live WHERE ParentID = '.
            $this->owner->ID.') AS ChildPageLastEdited,';

        if ($classes) {
            foreach ($classes as $classname) {
                $tableName = $this->getTableName($classname);

                $stanza = "(SELECT MAX(LastEdited) from SiteTree_Live "
                    . "where ClassName = '". $classname."')  AS {$tableName}LastEdited , ";
                $sql .= $stanza;
            }
        }

        // get the classes to get a cache key with that are not in the site tree
        // @phpstan-ignore-next-line
        $classes = $this->getOwner()->config()->get(DataObject::class);

        if ($classes) {
            foreach ($classes as $classname) {
                $tableName = $this->getTableName($classname);
                $stanza = '(SELECT MAX(LastEdited) from `'.$tableName.'`) AS ' .$tableName .'LastEdited, ';
                $sql .= $stanza;
            }
        }

        $sql .= '(SELECT Max(LastEdited) from
					(
						select LastEdited from SiteTree_Live
						where ParentID = 0
						AND ShowInMenus = 1

						union
						select LastEdited FROM SiteTree_Live
						where ParentID IN
							(SELECT ID from SiteTree_Live where ParentID = 0 and ShowInMenus = 1)
						AND ShowInMenus = 1
					) AS TopLevels

				  ) AS TopTwoLevelsLastEdited,';

        // if there is a member, get the last edited - cache for 5 mins (300 seconds), as Member is
        // saved every page request to update last visited
  //      $sql .= "(SELECT CONCAT(ID,'_',LastEdited/300) from Member where ID=".
  //          Member::currentUserID().') as CurrentMemberLastEdited,';

        // site config
        $sql .= "(SELECT LastEdited from `SiteConfig`) AS SiteConfigLastEdited, ";

        // the current actual page
        // @phpstan-ignore-next-line
        $sql .= "(SELECT LastEdited from SiteTree_Live where ID='".$this->getOwner()->ID.
                "') as CurrentPageLastEdited,";

        // siblings, needed for side menu
        $sql .= "(SELECT MAX(LastEdited) from SiteTree_Live where ParentID='".
            // @phpstan-ignore-next-line
            $this->getOwner()->ParentID."') as SiblingPageLastEdited,";

        // add a clause to check if any page on the site has changed, a major cache buster
        $sql .= '(SELECT MAX(LastEdited) from SiteTree_Live) as SiteTreeLastEdited;';


        $records = DB::query($sql)->first();


        // @TODO is this necessary as POST vars would override
        // now append the request params, stored as PARAM_<parameter name> -> parameter value
        foreach ($this->requestProvider->getRequest()->requestVars() as $k => $v) {
            $records['PARAM_'.$k] = $v;
        }
        self::$cachekeysinitialised = true;
        self::$last_edited_values = $records;

        error_log('==== RECORDS ====');
        error_log(print_r($records, true));
    }


    /** @return string the table name associated with the classname above */
    private function getTableName(string $classname): string
    {
        return Config::inst()->get($classname, 'table_name');
    }
}
