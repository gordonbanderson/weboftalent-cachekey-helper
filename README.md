#Introduction
In order to improve the performance of a SilverStripe site it is very useful to use partial caching to cache fragments of a page against a given condition, usually either a LastEdited field or something of periodic time, e.g. caching a copy of a twitter feed on a site and updating it every 5 minutes.  When an item is edited the LastEdited date is updated, or when the period of time elapses the cache is 'busted' and  the partial fragement on the page is updated with the new rendering.  Whilst this technique works it still requires a hit against the database for each partially cached fragement of a page.  So why not get them all in a single query?

#Technique
When fine tuning a site it is useful to trace SQL activity as follows. Add a trace error_log statement in the query() method of MySQLDatabase.php as follows:

    	public function query($sql, $errorLevel = E_USER_ERROR) {
			error_log('SQL:'.$sql);

One can then observe SQL trace using a command similar to the following:

		tail -f /var/log/apache2/yoursite.silverstripe.errors.log | grep SQL

This is useful when trying to identify areas of the site where queries are being generated.  To get a purely numberic value of the number of SQL statements being executed, use a variant of the following:

		watch -n 1 'cat /var/log/apache2/yoursite.silverstripe.errors.log | grep SQL | wc -l'

Every time a page is loaded, the number of cumulative SQL statements executed will be shown in a terminal window.  One needs to do a bit of maths, but it's indicative of whether your dealing with 10s of queries, hundreds of queries or indeed thousands of them.

# Installation
    git clone git://github.com/gordonbanderson/weboftalent-cache-helper.git
    cd weboftalent-cache-helper
    git checkout 3.1


# Usage

## Configuration
For any classes that one wishes to cache on a page, and the configuration is slightly different for Pages (that extend SiteTree) and non SiteTree objects.
Create a file in _config/ of your site or module, called for example cachekeys.yml 
By default, Page,Member, and Group already have their most recent LastEdited dates obtained.  If we for example have a class called Article that extends Page, and that Article has Links which extend DataObject then the configuration would look like this:

	CacheKeyHelper:
	  SiteTree: ['Article']
	  DataObject: ['Link']

Remember that a /dev/build is required for any configuration changes to be effected.

##Usage in Templates
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

### Current Member
Member is slightly problematic in that the LastEdited field is updated every page load, due to updating the LastVisited field. This module opts for a five minute cache, guess it should be made configurable.  Note that post 3.1 trunk of SilverStripe has LastVisited turned off, and it can be added by an extension thus http://doc.silverstripe.org/framework/en/trunk/howto/track-member-logins

A common idiom is a logged in bar, with the user's name and possibly image.

	<% cached $CacheKey('memberloggedinbar', 'CurrentMember') %>
	... Member details here ...
	<% end_cached %>