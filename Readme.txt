=== Plugin Name ===
Contributors: delieverynetwork
Tags: informers, информеры
Requires at least: 3.0.1
Tested up to: 4.2.2
Stable tag: trunk
License: Apache 2.0 license
License URI: http://www.apache.org/licenses/

Client for Fleetly.net informers. Example of working plugin you can see here http://blog.aport.ru/ in sidebar.

== Description ==

If you want to add on your site information about newest shopping offers from aport.ru or job vacancies from trud.com and jobtonic.com - you should use our informers.

== Installation ==

Working scheme is:

1. Client script sends to informer managing system URL, where script was called
1. System desides, which type of informer should be on that page
1. System asks data providers for fresh offers and sends it with informers template to client script
1. Script compiles data and template into HTML-code and outputs it into webpage
1. On the next call, but not earlier than 10 minutes, client re-asks fresh data for informer.

To register into informer programm - go to fleetly.net for registration.
You can also ask us via admin@fleetly.net

To install plugin - you must be registred on fleetly.net and have your site added in system to obtain API key and Site ID.
On settings page - you can enter this keys and test informers.

Note: informers will be shown only after approval of your site.

To install informers into specific place on your site you can use widget or shortcode [fleetly-informer].

== Changelog ==

v 1.1.0
The shortcode [fleetly-informer] was added


