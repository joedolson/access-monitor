=== Theme Checker ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate.php
Tags: a11y, accessibility, wcag, wave, section508
Requires at least: 3.9.2
Tested up to: 4.0
License: GPLv2 or later
Stable tag: 0.1.0

Creates common custom post types for advanced content management: FAQ, Testimonials, people (staff, contributors, etc.), and others!

== Description ==

Theme Checker is a plug-in for examining the accessibility of a given page in a WordPress site, either on the front-end or in the administration. It runs an automated scan of your HTML using the Tenon.io web accessibility service. 

Theme Checker requires an API key for Tenon.io.

* In the admin, only the Dashboard tests the entirety of the WordPress admin code. On other admin pages, only information inside the wpbody-content container is checked.
* On the front-end, only Pages and individual posts or custom post type pages can currently be tested. Archive pages are not currently passed to Tenon.

Available languages (in order of completeness):
Nothing yet.

Visit the [Theme Checker translations site](http://translate.joedolson.com/projects/my-content-management/) to check the progress of a translation.

Translating my plug-ins is always appreciated. Visit <a href="http://translate.joedolson.com">my translations site</a> to start getting your language into shape!

<a href="http://www.joedolson.com/translator-credits/">Translator Credits</a>

== Changelog ==

= 0.1.0 =

* Pre-release version

== Installation ==

1. Upload the `theme-checker` folder to your `/wp-content/plugins/` directory
2. Activate the plugin from the `Plugins` menu in WordPress
3. Press the 'A11y Check' button in the adminbar.

== Frequently Asked Questions ==

= This is awesome! If I fix these problems, is my website accessible? =

Maybe, but there's no way to know using automated testing. Automated accessible testing can't test every accessibility issue; many issues require human checks. However, this will help you find all the issues that are fully testable using automation.

== Screenshots ==


== Upgrade Notice ==

* 0.1.0 Nothing to say yet.