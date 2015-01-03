=== Access Monitor ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate.php
Tags: a11y, accessibility, wcag, wave, section508
Requires at least: 3.9.2
Tested up to: 4.1
License: GPLv2 or later
Stable tag: 1.0.0

Test your WordPress site for accessibility compliance. Run on-demand tests or schedule a weekly accessibility check.

== Description ==

Access Monitor runs accessibility tests of your WordPress site, either on the front-end or in the administration. It runs an automated scan of your site using the Tenon.io web accessibility service. 

Theme Checker requires an [API key for Tenon.io](http://tenon.io/register.php).

= Testing your WordPress Site =

Access Monitor allows you to run a one-time test or schedule a test to be run on a weekly or monthly basis. All tests run through this system are saved for later review and comparison so you can track the performance of a page or a test over time.

* Duplicate issues are filtered out from the display. If an accessibility issue appears on multiple pages, it will only be shown the first time it's identified.
* You can re-run any test from the Accessibility Reports screen at any time. Re-running a test will create a new test on the same pages and using the same test parameters.

= Testing the WordPress Admin =

You can test the page you're viewing using a link in the admin bar labeled 'A11y Check'.

* In the admin, the entirety of the WordPress admin HTML is only tested on the Dashboard. On other admin pages, only the code inside the wpbody-content container is passed to Tenon.
* On the front-end, only Pages and individual posts or custom post type pages can be tested. Archive pages are not currently passed to Tenon.


Available languages (in order of completeness):
Nothing yet.

Visit the [Theme Checker translations site](http://translate.joedolson.com/projects/my-content-management/) to check the progress of a translation.

Translating my plug-ins is always appreciated. Visit <a href="http://translate.joedolson.com">my translations site</a> to start getting your language into shape!

<a href="http://www.joedolson.com/translator-credits/">Translator Credits</a>

== Changelog ==

= 1.0.0 =

* Initial release

== Installation ==

1. Upload the `access-monitor` folder to your `/wp-content/plugins/` directory
2. Activate the plugin from the `Plugins` menu in WordPress
3. Press the 'A11y Check' button in the adminbar for a one-off test of a page.
4. Go to Accessibility Reports > Add Report to run and save a report or schedule a series of reports.

== Frequently Asked Questions ==

= This is awesome! If I fix these problems, is my website accessible? =

Maybe, but there's no way to know using automated testing. Automated accessible testing can't test every accessibility issue; many issues require human checks. However, this will help you find the issues that are fully testable using automation.

== Screenshots ==

1. A11y Check Results for a single page
2. Accessibility Reports list
3. Set up an Accessibility Report
4. Accessibility Report

== Upgrade Notice ==

* 1.0.0 Nothing to say yet.