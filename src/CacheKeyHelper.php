<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class CacheKeyHelper extends DataExtension
{
    /*
    An associative array of the form <Name>LastEdited -> some calculated key value
    */
    private static $_last_edited_values = array();

    /*
    Flag to ensure that the query is run only once
    */
    private static $cachekeysinitialised = false;

    /*
    Obtain a part of the cache key fragment based on a parameter name
    In a template this would look like <tt>$CacheParamKey('start')</tt>
    */
    public function CacheParamKey($param)
    {
        if (!self::$cachekeysinitialised) {
            $this->prime_cache_keys();
            self::$cachekeysinitialised = true;
        }

        // check URL parameters
        $key = 'PARAM_'.$param;
        $value = null;
        if (isset(self::$_last_edited_values[$key])) {
            $value = self::$_last_edited_values[$key];
        };

        // if still null check parameters from routing configuration
        if ($value === null) {
            $request = Controller::curr()->request;
            $value = $request->param($param);
        }

        return '_'.$param.'_'.$value;
    }

    /*
        Append a url parameter to the cache key.  This is useful for example when using pagination
    */
    public function CacheKeyGetParam($paramname)
    {
        $getvars = Controller::curr()->request;
        $result = '';
        if (isset($getvars[$paramname])) {
            $result = $getvars[$paramname];
        }

        return $paramname . '_' . $result;
    }


    /**
     * Provide for a portion of a key that is cached for a certain amount of time. This is useful
     * for calling external APIs, where you do not want to hit them every request (for example,
     * getting the current weather could be delayed to every 15 mins)
     *
     * @param $periodInSeconds the length of time the cache key should be valid
     */
    public function PeriodKey($periodInSeconds): int
    {
        return 'period_' . $periodInSeconds . '_' . (int)(\time() / $periodInSeconds);
    }

    /*
    Old name for this method, as request params now included
    */
    public function CacheKey($prefix, $classname)
    {
        return $this->CacheDataKey($prefix, $classname);
    }

    /*
    Obtain a cache for a given class with a given prefix, to be used in templates for partial
    caching.  It is designed to ensure that the query for caching is only called once and that all
    caching values are calculated at this time.  They are subsequently stored in a
    local static variable for use in other parts of the page being rendered.

    @param $prefix - an arbitrary prefix to differentiate from different areas of a page of pages,
    e.g. folderofarticles,homepagearticles
    @param $classname - the classname that we wish to find the most recent last edited value of
    */
    public function CacheDataKey($prefix, $classname)
    {
        // only initialise the cache keys once
        if (!self::$cachekeysinitialised) {
            $this->prime_cache_keys();
            self::$cachekeysinitialised = true;
        }

        return $prefix.'_'.self::$_last_edited_values[$classname.'LastEdited'];
    }

    /*
    Using configuration values for classes required to be cache keys from both SiteTree andDataObject, form a large single query to get last edited
    values for each of these individual classes.  Also for good measure the following are added
    utomatically:
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
        // get the classes to get a cache key with from the site tree
        $classes = $this->owner->config()->get(SiteTree::class);

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
        $classes = $this->owner->config()->get(DataObject::class);

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
        $sql .= "(SELECT LastEdited from SiteTree_Live where ID='".$this->owner->ID.
                "') as CurrentPageLastEdited,";

        // siblings, needed for side menu
        $sql .= "(SELECT MAX(LastEdited) from SiteTree_Live where ParentID='".
            $this->owner->ParentID."') as SiblingPageLastEdited,";

        // add a clause to check if any page on the site has changed, a major cache buster
        $sql .= '(SELECT MAX(LastEdited) from SiteTree_Live) as SiteTreeLastEdited;';


        $records = DB::query($sql)->first();


        // now append the request params, stored as PARAM_<parameter name> -> parameter value
        foreach (Controller::curr()->request->requestVars() as $k => $v) {
            $records['PARAM_'.$k] = $v;
        }

        self::$_last_edited_values = $records;
    }


    /**
     * @param $classname
     * @return array
     */
    private function getTableName($classname): array
    {
        return Config::inst()->get($classname, 'table_name');
    }
}
