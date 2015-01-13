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

Access Monitor requires an [API key for Tenon.io](http://tenon.io/register.php).

= Monitor Site Accessibility =

No matter how hard you work to make your web site accessible, if it's adding new content on a daily basis, that accessibility might be changing every day. If you change your theme, you're in a whole new accessibility environment. 

You can make a plan to check for new issues every day, or every week - but how are you going to go about it? 

Access Monitor does two great things to help you test your site for accessibility. 

<strong>First</strong>, it gives you the freedom of automation - schedule a weekly or monthly report, and you'll get notified each time that report is run. It'll test a specific set of pages, and return a list of definite accessibility issues, if you have any. 

<strong>Second</strong>, it only reports accessibility issues that are machine-testable. It's not going to raise a bunch of red flags because the tests weren't able to tell whether a particular issue is a problem. You won't find yourself logging in to look at a report that's showing 30 new accessibility issues, but 27 of them are issues you've already checked manually and know aren't really problems. 

Access Monitor uses <a href="http://tenon.io">Tenon.io</a>, an automated accessibility testing service developed by web accessibility guru <a href="http://karlgroves.com">Karl Groves</a>. Tenon does what an automated tool should do - it delivers a report telling you what the issue is, where it was found, why it's an issue, and who is affected by it. 

It's true that a perfect score from Access Monitor doesn't mean that your web site is accessible - there's no way around manual testing in the web site accessibility world. But Access Monitor makes sure that if an issue can be found using automation, you'll know about it. 

= Learn about Tenon and Automated Accessibility =

Go to <a href="http://tenon.io">Tenon.io to run a test of your site</a> or <a href="http://tenon.io/documentation/">read the Tenon.io documentation</a>. Check out Karl Groves' article series <a href="http://www.karlgroves.com/2014/02/13/everything-you-know-about-accessibility-testing-is-wrong-part-1/">Everything You Know About Accessibility Testing is Wrong</a>. <a href="http://tenon.io/register.php">Get a free Tenon.io API key</a>, then install <a href="https://wordpress.org/plugins/access-monitor">Access Monitor</a> and try it out.

Keep up with <a href="https://github.com/joedolson/access-monitor">Access Monitor development on GitHub</a>.

= Testing your WordPress Site =

Access Monitor allows you to run a one-time test or schedule a test to be run on a weekly or monthly basis. All tests run through this system are saved for later review and comparison so you can track the performance of a page or a test over time.

* Duplicate issues are filtered out from the display. If an accessibility issue appears on multiple pages, it will only be shown the first time it's identified.
* You can re-run any test from the Accessibility Reports screen at any time. Re-running a test will create a new test on the same pages and using the same test parameters.

= Testing the WordPress Admin =

You can test the page you're viewing using a link in the admin bar labeled 'A11y Check'.

* In the admin, the entirety of the WordPress admin HTML is only tested on the Dashboard. On other admin pages, only the code inside the wpbody-content container is passed to Tenon.
* On the front-end, only Pages and individual posts or custom post type pages can be tested. Archive pages are not currently passed to Tenon.


Available languages (in order of completeness):
Dutch

Visit the [Access Monitor translations site](http://translate.joedolson.com/projects/access-monitor/) to check the progress of a translation.

Translating my plug-ins is always appreciated. Visit <a href="http://translate.joedolson.com">my translations site</a> to start getting your language into shape!

<a href="http://www.joedolson.com/translator-credits/">Translator Credits</a>


== Changelog ==

= 1.0.1 =

* Use of old plug-in name in readme.
* Fixed donate button to be for this plug-in.
* Extended FAQ
* New Language: Dutch 
* Put ref links back in place now that they redirect properly.
* Bug fix: Disabled A11y Check in customizer.

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

= If Access Monitor can't test everything, what's the point? =

No tool can pick up every Accessibility issue - but Access Monitor can help you eliminate issues that <strong>can</strong> be found in automated testing without wasting your time with a bunch of false positives. When you do your manual testing, you won't waste any time on those issues!

== Screenshots ==

1. A11y Check Results for a single page
2. Accessibility Reports list
3. Set up an Accessibility Report
4. Accessibility Report

== Upgrade Notice ==

* 1.0.0 Nothing to say yet.