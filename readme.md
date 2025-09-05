# Simple Search Submission for IndexNow

A simplified WordPress plugin for submitting crawl requests to search engines supporting IndexNow.

## Description

[IndexNow](https://www.indexnow.org/) is a simple way of notifying search engines of updates to URLs on a site.

IndexNow allows website owners to submit a request to search engines to prioritize the crawling of a particular page or blog post on your site.

This plugin provides a no-fuss, simple alternative to just submit updates to the search engines.

Crawl requests will be sent to search engines when:

* new content is published
* existing content is updated
* existing content is unpublished (this encourages de-indexing)

The biggest features of this plugin are the features that it's missing:

* there are no settings, it just works
* there are no onboarding steps, it just works
* there are no custom database tables, it just works
* simply put, there is no fuss!

Simple Search Submission for IndexNow submits the update requests as you save your content. If you wish to submit the URLs asynchronously via a cron job, you can include the code `add_filter( 'simple_search_submission_notify_async', '__return_true' );` in your theme or or plugin.

## Installation

Simple Search Submission for IndexNow can be installed via the following methods

*Composer*

```
composer require peterwilsoncc/simple-search-submission
```

*Downloads*

* [Via WordPress.org](https://wordpress.org/plugins/simple-search-submission/)
* [Via GitHub](https://github.com/peterwilsoncc/simple-search-submission/releases/latest)

## Frequently Asked Questions

### Which search engines support IndexNow?

At the time of writing, IndexNow is supported by Bing, Naver, Seznam, Yandex and Yep.

### Where is the settings page?

There is no settings page for this plugin, simply activate the plugin and it will work as intended.

### Is this needed if I run an SEO plugin? (Spoiler: possibly)

Possibly.

Most SEO Plugins only include IndexNow support in their paid versions of the plugin. If you are using a free SEO plugin (such as Yoast SEO or All In One SEO), it is likely you will need this plugin to speed up indexing of your site's new content.

If your SEO plugin does support IndexNow then you should not install this plugin. Doing so will double up requests to search engines to index your content. For large sites publishing regularly, this will increase the chance you reach your maximum number of URL submissions.

### Is this needed if I run Bing's IndexNow plugin?

No, but it's more complicated than that.

This plugin is intended as a replacement for the Bing plugin that is much simplified and just works.

### Why does this submit requests when content is unpublished?

IndexNow supports both notifications for newly published content and newly unpublished content.

When unpublishing content, the notification serves as a request to de-index the newly 404 response on your site. Sending a de-indexing request ensures that your site's old content does not appear in indexes and result in a file not found error.

### Are notifications sent from non-production sites?

No.

The plugin uses `wp_get_environment_type()` to determine whether to send notifications to IndexNow.

For non-production environments, the request that would have been sent are logged in the PHP error log file to allow for developers to debug any requests. The notification is not actually sent to IndexNow.

## Changelog

### 1.1.0

Initial WordPress.org release

### 1.0.0

* Initial release
