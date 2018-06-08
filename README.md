# Introduction
[![Build Status](https://travis-ci.org/gordonbanderson/weboftalent-cachekey-helper.svg?branch=master)](https://travis-ci.org/gordonbanderson/weboftalent-cachekey-helper)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gordonbanderson/weboftalent-cachekey-helper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gordonbanderson/weboftalent-cachekey-helper/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/gordonbanderson/weboftalent-cachekey-helper/badges/build.png?b=master)](https://scrutinizer-ci.com/g/gordonbanderson/weboftalent-cachekey-helper/build-status/master)
[![codecov.io](https://codecov.io/github/gordonbanderson/weboftalent-cachekey-helper/coverage.svg?branch=master)](https://codecov.io/github/gordonbanderson/weboftalent-cachekey-helper?branch=master)

[![Latest Stable Version](https://poser.pugx.org/weboftalent/cachekeyhelper/version)](https://packagist.org/packages/weboftalent/cachekeyhelper)
[![Latest Unstable Version](https://poser.pugx.org/weboftalent/cachekeyhelper/v/unstable)](//packagist.org/packages/weboftalent/cachekeyhelper)
[![Total Downloads](https://poser.pugx.org/weboftalent/cachekeyhelper/downloads)](https://packagist.org/packages/weboftalent/cachekeyhelper)
[![License](https://poser.pugx.org/weboftalent/cachekeyhelper/license)](https://packagist.org/packages/weboftalent/cachekeyhelper)
[![Monthly Downloads](https://poser.pugx.org/weboftalent/cachekeyhelper/d/monthly)](https://packagist.org/packages/weboftalent/cachekeyhelper)
[![Daily Downloads](https://poser.pugx.org/weboftalent/cachekeyhelper/d/daily)](https://packagist.org/packages/weboftalent/cachekeyhelper)

[![Dependency Status](https://www.versioneye.com/php/weboftalent:cachekeyhelper/badge.svg)](https://www.versioneye.com/php/weboftalent:cachekeyhelper)
[![Reference Status](https://www.versioneye.com/php/weboftalent:cachekeyhelper/reference_badge.svg?style=flat)](https://www.versioneye.com/php/weboftalent:cachekeyhelper/references)

![codecov.io](https://codecov.io/github/gordonbanderson/weboftalent-cachekey-helper/branch.svg?branch=master)

In order to improve the performance of a SilverStripe site it is very useful to use partial caching to cache fragments of a page against a given condition, usually either a LastEdited field or something of periodic time, e.g. caching a copy of a twitter feed on a site and updating it every 5 minutes.  When an item is edited the LastEdited date is updated, or when the period of time elapses the cache is 'busted' and  the partial fragement on the page is updated with the new rendering.  Whilst this technique works it still requires a hit against the database for each partially cached fragement of a page.  So why not get them all in a single query?

# Technique
When fine tuning a site it is useful to trace SQL activity as follows. Add a trace error_log statement in the query() method of MySQLDatabase.php as follows:

    	public function query($sql, $errorLevel = E_USER_ERROR) {
			error_log('SQL:'.$sql);

One can then observe SQL trace using a command similar to the following:

		tail -f /var/log/apache2/yoursite.silverstripe.errors.log | grep SQL

This is useful when trying to identify areas of the site where queries are being generated.  To get a purely numberic value of the number of SQL statements being executed, use a variant of the following:

		watch -n 1 'cat /var/log/apache2/yoursite.silverstripe.errors.log | grep SQL | wc -l'

Every time a page is loaded, the number of cumulative SQL statements executed will be shown in a terminal window.  One needs to do a bit of maths, but it's indicative of whether your dealing with 10s of queries, hundreds of queries or indeed thousands of them.

# Installation
## SilverStripe 4
```php
composer require "weboftalent/cachekeyhelper:^2"
```

## SilverStripe 3
```php
composer require "weboftalent/cachekeyhelper:^1"
```




# Usage

## Configuration
For any classes that one wishes to cache on a page, and the configuration is slightly different for Pages (that extend SiteTree) and non SiteTree objects.
Create a file in _config/ of your site or module, called for example cachekeys.yml 
By default, Page,Member, and Group already have their most recent LastEdited dates obtained.  If we for example have a class called Article that extends Page, and that Article has Links which extend DataObject then the configuration would look like this:

	CacheKeyHelper:
	  SilverStripe\CMS\Model\SiteTree:
	    Article
	  SilverStripe\CMS\Model\SiteTree:
	    - WebOfTalent\Link\Link
	    

Remember that a /dev/build is required for any configuration changes to be effected.

## Usage in Templates
### Caching Classes of Items
When creating a cache key, one can now use the following in a template:

	$CacheKey('someprefix','YourClassName')

Imagine the scenario of a home page where we show the most recent Articles and Links.  The template code for caching would look like this:

	<% cached ID,LastEdited,$CacheKey('articlehomepageslider', 'Article') %>
	... render articles here ...
	<% end_cached %>

Similarly the links would be cached thus:

	<% cached ID,LastEdited,$CacheKey('linkshomepageportlet', 'Link') %>
	... render latest links here ...
	<% end_cached %>

### Caching Current Page
The current page is cached under CurrentPage

	<% cached ID,$CacheKey('contactpage', 'CurrentPage') %>
	... render current page here ...
	<% end_cached %>

### Caching Child Folder Rendering
If one is rendering a folder of child items, a common enough idiom, the most recent child item LastEdited date is under the key ChildPage.

	<% cached ID,$CacheKey('galleryofpics', 'ChildPage') %>
	... render gallery of images here ...
	<% end_cached %>

### Caching Sibling Rendering
When rendering a sidebar menu one normally renders a list of pages from the same folder, i.e. siblings.  The relevant LastEdited value is stored under the key SiblingPage.

	<% cached ID,$CacheKey('galleryofpics', 'SiblingPage') %>
	... render gallery of images here ...
	<% end_cached %>

### Caching Drop Down Menu
For a drop down menu containing the top 2 levels of the SiteTree, one can use the cache key TopTwoLevels

	<% cached ID,$CacheKey('toplevelmenu', 'TopTwoLevels') %>
	... render gallery of images here ...
	<% end_cached %>

### SiteTree Last Edited
If one has a section of page that needs to be invalidated whenever anything is saved, use this key called SiteTree.  An example of this would be a site map.

	<% cached $CacheKey('sitemap', 'SiteTree') %>
	... rendering of sitemap here ...
	<% end_cached %>

### Site Configuration
Site configuration is cached under SiteConfig.
	
	<% cached $CacheKey('siteconfigtagline', 'SiteConfig') %>
	... $SiteConfig.TagLine ...
	<% end_cached %>

### Caching via Parameters
In the case of search results it is useful to be able to cache by a URL parameter.

     <% cached ID,LastEdited,$CacheParamKey('start') %>
