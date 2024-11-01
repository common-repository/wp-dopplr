=== Plugin Name ===
Contributors: frodenas
Tags: dopplr, travel, trips, badge, sidebar, widget
Requires at least: 2.5 or higher
Tested up to: 3.0.1
Stable tag: 1.6

WP-DOPPLR is a Wordpress plugin that displays your DOPPLR travel information on your blog.

== Description ==

WP-DOPPLR is a Wordpress plugin that displays your [DOPPLR](http://www.dopplr.com/) travel information on your blog.

It can be used as a widget or directly as a PHP call in the theme.

== Installation ==

1. Download WP-DOPPLR.
2. Decompress and upload the contents of the archive into /wp-content/plugins/.
3. Activate the plugin on your WP Admin > Plugins page by clicking 'Activate' at the left of the 'WP-DOPPLR' row.
4. Configure the plugin on your WP Admin > Settings > Dopplr page. You must sign in to your DOPPLR account in order to get an API key (URL provided).
5. To use it, there are two options:
* Add the WP-DOPPLR widget on your WP Admin > Appearance > Widgets page.
* Add &lt;?php wpdopplr_badge() ?&gt; at the place in the theme you want the DOPPLR information to appear.

== Frequently Asked Questions ==

= Is this plugin the official DOPPLR blog badge? =

No. Visit the [Dopplr badge for your blog](http://blog.dopplr.com/index.php/2007/10/08/dopplr-badge-for-your-blog/) post to find out how to get the official DOPPLR blog badge.

= Which are the plugin prerequisites? =

* A [DOPPLR](http://www.dopplr.com/) account.
* WP-DOPPLR needs PHP version >= 5.1.0.
* WP-DOPPLR uses [cURL](http://curl.haxx.se/) function calls, so your PHP installation must be compiled with cURL support.

= Can I modify the widget look & feel? =

The DOPPLR widget information is in a &lt;div class="wpdopplr"&gt; ... &lt;/div&gt;. So to modify the look & feel more precisely, just set the css properties of .wpdopplr.

= Can I customise the information that it is displayed? =

If you are using the widget, you can not customize the information that it is displayed.

If you are calling the plugin directly in you theme, you can use these PHP functions:

* wpdopplr&#95;badge(): prints your badge.
* wpdopplr&#95;traveller&#95;info(): returns a text var with your current status.
* wpdopplr&#95;local&#95;time(): returns a text var with the local time at your current location.
* wpdopplr&#95;trips&#95;info(): returns an array with all your trips.
* wpdopplr&#95;past&#95;trips&#95;info(): returns an array with all your past trips.
* wpdopplr&#95;future&#95;trips&#95;info(): returns an array with all your future trips.

If you wish to display another information, please make a comment in [this post](http://www.rodenas.org/blog/2007/10/09/wp-dopplr/).

== Screenshots ==

1. WP-DOPPLR Options Panel
2. Widget activation

== Changelog ==

= Version 1.0 - 2007/10/09: =
* Initial release.

= Version 1.1 - 2007/10/14: =
* Use the Dopplr AuthSub process.

= Version 1.2 - 2008/01/13: =
* Add new method future&#95;trips&#95;info.
* Add new method local&#95;time.
* Add new option to display cities local time.
* Cache Dopplr query results (Thanks to Boris Anthony for this suggestion).

= Version 1.3 - 2008/02/29: =
* Add CSS classes.

= Version 1.4 - 2008/11/05: =
* Add new method past&#95;trips&#95;info.
* Add new option to display past and future trips.
* Add new option to display start and finish trip dates.
* Add new option to modify the date and time format.
* Add new option to display city colours.
* Add new option to display countries.

= Version 1.5 - 2009/06/10: =
* Bug: Determine the correct wp-content directory.

= Version 1.6 - 2009/09/14 =
* Bug: Determine the correct local date & time.
* Add new option to specify cities links.
* Add new option to specify cities colour type.
* Add new option to dismiss the API key.
* Add new option to clear the cache contents.
* Enhance settings and widget menu.
* Enhance installation process (subdirectories allowed).
* Translatable strings (internationalization).
