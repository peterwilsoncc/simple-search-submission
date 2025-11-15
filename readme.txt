=== Simple Search Submission for IndexNow ===
Contributors: peterwilsoncc
Tags: seo, indexnow, crawling
Tested up to: 6.9
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://github.com/peterwilsoncc/simple-search-submission/blob/main/LICENSE

A simplified plugin for submitting crawl requests to search engines supporting IndexNow.

== Description ==

[IndexNow](https://www.indexnow.org/) is a simple way of notifying search engines of updates to URLs on a site.

IndexNow allows website owners to submit a request to search engines to prioritize the crawling of a particular page or blog post on your site.

This plugin provides a no-fuss, simple alternative to just submit updates to the search engines.

Crawl requests will be sent to search engines when:

* new content is published
* existing content is updated
* existing content is unpublished (this encourages de-indexing)
* the post slug changes (both the old and new are sent index the redirect URL)

De-indexed and redirect URLs are only sent to IndexNow once.

The biggest features of this plugin are the features that it's missing:

* there are no settings, it just works
* there are no onboarding steps, it just works
* there are no custom database tables, it just works
* simply put, there is no fuss!

Simple Search Submission for IndexNow submits the update requests as you save your content. If you wish to submit the URLs asynchronously via a cron job, you can include the code `add_filter( 'simple_search_submission_notify_async', '__return_true' );` in your theme or or plugin.

== Frequently Asked Questions ==

= Which search engines support IndexNow? =

At the time of writing, IndexNow is supported by Bing, Naver, Seznam, Yandex and Yep.

= Where is the settings page? =

There is no settings page for this plugin, simply activate the plugin and it will work as intended.

= Is this needed if I run an SEO plugin? (Spoiler: possibly) =

Possibly.

Most SEO Plugins only include IndexNow support in their paid versions of the plugin. If you are using a free SEO plugin (such as Yoast SEO or All In One SEO), it is likely you will need this plugin to speed up indexing of your site's new content.

If your SEO plugin does support IndexNow then you should not install this plugin. Doing so will double up requests to search engines to index your content. For large sites publishing regularly, this will increase the chance you reach your maximum number of URL submissions.

= Is this needed if I run Bing's IndexNow plugin? =

No, but it's more complicated than that.

This plugin is intended as a replacement for the Bing plugin that is much simplified and just works.

= Why does this submit requests when content is unpublished? =

IndexNow supports both notifications for newly published content and newly unpublished content.

When unpublishing content, the notification serves as a request to de-index the newly 404 response on your site. Sending a de-indexing request ensures that your site's old content does not appear in indexes and result in a file not found error.

= Are notifications sent from non-production sites? =

No.

The plugin uses `wp_get_environment_type()` to determine whether to send notifications to IndexNow.

For non-production environments, the request that would have been sent are logged in the PHP error log file to allow for developers to debug any requests. The notification is not actually sent to IndexNow.

= What happens for sites set to "Discourage search engines from indexing this site"? =

There is no check for this setting in the plugin.

As an SEO feature, the expectation for installing this plugin that you want search engines to index your site. If this is not the case then you should deactivate this plugin.

As mentioned above, notifications are not sent for non-production sites.

== Changelog ==

= 1.2.0 =

* Only submit de-indexed and redirect URLs to IndexNow once.
* Use `wp_safe_remote_post()` for sending requests to IndexNow.

= 1.1.0 =

Initial WordPress.org release.

= 1.0.0 =

Initial release
