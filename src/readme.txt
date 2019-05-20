=== Access Monitor ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate/
Tags: a11y, accessibility, wcag, section508, tenon
Requires at least: 4.2
Tested up to: 5.2
Requires PHP: 5.3
License: GPLv2 or later
Text domain: access-monitor
Stable tag: 1.3.2

Test your WordPress site for accessibility compliance. Run on-demand tests or schedule a weekly accessibility check.

== Description ==

Access Monitor runs accessibility tests of your WordPress site. It runs an automated scan of your site using the Tenon.io web accessibility service. 

Access Monitor requires an API key for Tenon.io. <a href="http://www.tenon.io?rfsn=236617.3c55e">Get your API key now!</a> (Affiliate link)

= Monitor Site Accessibility =

No matter how hard you work to make your web site accessible, if you're adding new content regularly, the accessibility of your site changes equally often. If you change your theme, you may have a whole new set of issues. 

Access Monitor does two great things to help you test your site for accessibility. 

<strong>First</strong>, it gives you the freedom of automation - schedule a weekly or monthly report, and get notified each time a report is run. Test a specific set of pages, and return a list of definite accessibility issues. 

<strong>Second</strong>, it only reports accessibility issues that are machine-testable. It's won't raise red flags because it couldn't tell whether an issue is a problem. 

Access Monitor uses <a href="http://tenon.io">Tenon.io</a>, an automated accessibility testing service developed by web accessibility guru <a href="http://karlgroves.com">Karl Groves</a>. Tenon does what an automated tool should do - it delivers a report telling you what the issue is, where it was found, why it's an issue, and who is affected by it. 

A perfect score in Access Monitor doesn't mean that your web site is accessible - there's no way around manual testing. But Access Monitor makes sure that if an issue can be found using automation, you'll know about it. 

= Learn about Tenon and Automated Accessibility =

Go to <a href="http://tenon.io">Tenon.io to run a test of your site</a> or <a href="http://tenon.io/documentation/">read the Tenon.io documentation</a>. Check out Karl Groves' article series <a href="http://www.karlgroves.com/2014/02/13/everything-you-know-about-accessibility-testing-is-wrong-part-1/">Everything You Know About Accessibility Testing is Wrong</a>. Then <a href="http://www.tenon.io?rfsn=236617.3c55e">get yourself an API key</a> (affiliate link).

Keep up with <a href="https://github.com/joedolson/access-monitor">Access Monitor development on GitHub</a>.

= Test your WordPress Site =

Run a one-time test or schedule a test to be run on a weekly or monthly basis. All tests run through this system are saved for later review and comparison so you can track the performance of a page or a test over time.

* Duplicate issues are filtered out. If an accessibility issue appears on multiple pages, it will be shown the first time it's identified.
* Re-run any test from the Accessibility Reports screen at any time. Re-running a test will create a new test on the same pages using the same parameters.
* Apply rules that will test posts before you publish them and reject publication if they don't meet your criteria

= Test the WordPress Admin =

You can test the page you're viewing using a link in the admin bar labeled 'A11y Check'.

* On the front-end, only Pages and individual posts or custom post type pages can be tested. Archive pages are not currently passed to Tenon.

Help [translate Access Monitor](https://translate.wordpress.org/projects/wp-plugins/access-monitor/)!


== Changelog ==

= 1.3.2 =

* Don't attempt post inspection if $post not a valid Post object.
* Change Tenon API url
* Updates to inspector CSS so admin issue navigation better.

= 1.3.1 =

* Prevent Gutenberg editor if pre-publish checks are enabled.
* Remove 'log' and 'info' property references (no longer exist)

= 1.3.0 =

* Remove 'create_function' for PHP 7.2 compatibility
* Bug fix: error_description should be errorDescription
* Bug fix: Create new report broken due to redirect
* Remove sslverify=false
* Add Requires PHP 5.3 to readme
* Code conformance with WordPress code standards
* Minor file restructuring
* Change objects into arrays where necessary to meet code standards

= 1.2.0 =

* Feature: Add visualization of errors 
* Feature: Display relevant WCAG guidelines
* Improvement: Indication of current process
* Improvement: Full page test on all WordPress screens (to obtain valid xPath)
* Improvement: Allow admins to disable accessibility test results before saving draft.
* Bug fix: Modify collapsible display to allow full scrolling
* Bug fix: heading pluralization
* Change: Rename primary menu item for space and clarity

= 1.1.8 =

* Remove WAVE (not currently in use)
* Update tested to value

= 1.1.7 =

* Updated headings hierarchy to WP 4.4+ model.
* Option to set API key for entire network on network activated
* Bug fix: called a CSS file that did not exist

= 1.1.6 =

* Filter: Ability to filter default arguments on A11y Check button
* Change: Changed default arguments on A11y check button
* Test whether result from Tenon is an object before checking for properties
* Improved error messages
* Test with WordPress 4.6

= 1.1.5 =

* Change registration links to affiliate referral links
* Move public a11y test results from post content into footer container
* Add expand/collapse toggle for public test results
* Hide xPath data in public test results to make more compact
* Save test results in post meta for future reference.
* Add nonce testing on public test results
* Updated required WordPress version
* Removed deprecated appID from request

= 1.1.4 =

* Bug fix: Admin screens accessibility test did not print results
* Bug fix: Only show A11y Check button if API key provided.
* Bug fix: duplicate IDs on settings page
* Bug fix: properly render reference URL in results
* Bug fix: Display both parent and child reports in related reports meta box
* Removed en_AU .mo file in favor of .org language pack.

= 1.1.3 =

* Bug fix: Missing 'type' parameter in AJAX query caused page tests to fail due to URL length
* Bug fix: Removed URL from label

= 1.1.2 =

* Bug fix: Missing single quote on Plugins page broke layout.
* Bug fix: Text domain was not loaded.

= 1.1.1 =

* Improvement: Create custom container for post testing if no container assigned.
* Bug fix: PHP notice for am_post_types
* Add settings link to Plugins page
* Add dismissable marketing panel for Tenon.io

= 1.1.0 =

* Feature: Reject publication of posts if they don't meet configured accessibility requirements
* Feature: Inspect post content of any published or draft page on the fly.
* Feature: checkbox for Admins to override accessibility test results
* Feature: checkbox for non-admins to request admin review of accessibility

= 1.0.4 =

* Simpler CSS on front-end.
* Bug fix: Conflict with Press This
* Bug Fix: REF URL (no longer in result)
* Added Language: Portuguese (Brazil)
	
= 1.0.3 =

* Corrected a couple of textdomains.
* Fixed plugin URI.
* Added application ID for Tenon queries.
* Tenon is out of beta, so move off beta API endpoint.

= 1.0.2 =

* API Change: remove reportID; change systemID to projectID

= 1.0.1 =

* Use of old plug-in name in readme.
* Fixed donate button to be for this plug-in.
* Extended FAQ
* New Language: Dutch 
* Put ref links back in place now that they redirect properly.
* Bug fix: Disabled A11y Check in customizer.
* Add an admin notice to point to the API key field if it hasn't been set.

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
5. Settings for testing post accessibility

== Upgrade Notice ==

* 1.1.5: Minor bug fixes, update tested to 4.5