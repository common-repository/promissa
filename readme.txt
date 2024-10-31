=== Pro Missa ===
Contributors: kerkenit
Donate link: https://www.promissa.nl
Author URI: https://www.kerkenit.nl
Plugin URI: https://www.promissa.nl/plugins/wordpress
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: kerk, vieringen, mistijden, kerkgebouwen, ledenadministratie, roosters
Requires at least: 5.2.1
Tested up to: 6.1.0
Stable tag: 1.4.1
Requires PHP: 5.2.4

This plugin will give you shortcodes and widgets with the latest masses and events of Pro Missa.

== Description ==

This plugin will show all the times of masses who are stored in Pro Missa.
This plugins will enable a shortcode and a widget with the upcoming events in your parish.
You can add multiple widgets to your theme of add a shortcode in you homepage.

= Options =

In the shortcodes and widgets there are several options:

* Select all churches of your parish or a specific church.
* Give an options of how many upcoming masses you want to show.
* You choose if you want to show the extra title. (The web text of an event in the [Pro Missa Portal](https://portal.promissa.nl).)
* The option to show the attendees on your website. (You choose in the portal which groups will be visible.)

Not familiar with Pro Missa?
Go to [promissa.nl](https://www.promissa.nl) to learn more.

Pro Missa is a tool created by [Kerk en IT](https://www.kerkenit.nl)

== Installation ==

You can use the built in installer and upgrader, or you can install the plugin manually.

= Installation via Wordpress =

1. Go to the menu WP admin > Plugins > 'Install' and search for 'Pro Missa'
1. Click 'install'
1. Go to settings in WP admin > Settings > 'Pro Missa'
1. Fill your API credentials from the Pro Missa portal

= Manual Installation =

1. Upload folder `promissa` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to settings in WP admin > 'Settings' > 'Pro Missa'
1. Fill your API credentials from the Pro Missa portal

You can use the shortcode `[promissa-calendar]` to show a full calendar

== Frequently Asked Questions ==

= Where do I get my API key? =

Go to the [Pro Missa Portal](https://portal.promissa.nl/api) and get the keys of your parish.
Note: You need to be authorised to get the keys

= Our parish doesn't use Pro Missa yet =

You can sign up a 100 days free trial to see if it will fit for your parish. [learn more](https://www.promissa.nl)

= Where can I find my shortcode? =

You can generate a temporary shortcode in the Widgets screen.
At the bottom of the widget you'll find the shortcode.
After copy the shortcode it's safe to remove the widget.

== Screenshots ==

1. Month view of calendar with `[promissa-calendar]` shortcode
2. Week view of calendar with `[promissa-calendar]` shortcode
3. Pro Missa Widget in frontend
4. Widget Settings

== Changelog ==

= 1.5.0 =

* Added filter for churches and week offset in shortcode `[promissa-intentions]`
* Added filter for masstype and week offset in shortcode `[promissa-upcoming-masses]`

= 1.4.2 =

* Fixed problem with the unix timestamp bug for the easter date

= 1.4.1 =

* Fixed layout issues for intentions

= 1.4.0 =

* Added WooCommerce plugin for ordering mass intentions

= 1.3.0 =

* Added signup form for parishioners to participate in mass.

= 1.2.8 =

* Minor change

= 1.2.7 =

* Added support for advanced filtering
* Bugfixes

= 1.2.6 =

* Added paging in upcoming masses widget
* Bugfixes

= 1.2.5 =

* Translations
* Optimised code

= 1.2.4 =

* Small fixes

= 1.2.3 =

* Small fixes

= 1.2.1 =

* Fixed styling calendar
* Small fixes

= 1.1.0 =

* Added calendar shortcode: `[promissa-calendar]`

= 1.0.1 =

* Small meta data fixes.
* Translated into Dutch.

= 1.0.0 =

* Initial version at the introduction of this plugin.


== Upgrade Notice ==

Just upgrade via Wordpress.

== Other Notes ==

You can use the shortcode `[promissa-calendar]` to show a full calendar
