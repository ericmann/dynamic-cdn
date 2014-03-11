=== Dynamic CDN ===
Contributors:      10up, ericmann
Donate link:       http://10up.com
Tags:              CDN, images, performance
Requires at least: 3.8.1
Tested up to:      3.8.1
Stable tag:        0.2.0
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Dynamic CDN for front-end assets.

== Description ==

Dynamic solution for rewriting image asset URLs to a hosted content delivery network (CDN) with optional domain sharding for concurrent downloads.

This plugin is based heavily on the CDN dropin from Mark Jaquith's WP_Stack (https://github.com/markjaquith/WP-Stack).

== Installation ==

= Manual Installation =

1. Upload the entire `/dynamic-cdn` directory to the `/wp-content/plugins/` directory.
2. Activate Dynamic CDN through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Will this work as a mu-plugin? =

Yes.  The plugin, as designed, will work just fine in the mu-plugins directory.  We highly recommend a second mu-plugin be added to configure the CDN domains used by the system.

= How do I add a domain? =

In a function wired to `dynamic_cdn_first_loaded`, you'll reference the `->add_domain()` method of the `Dynamic_CDN` object.  For example:

    function my_cdn_domains() {
        Dynamic_CDN::factory()->add_domain( 'cdn0.mydomain.com' );
        Dynamic_CDN::factory()->add_domain( 'cdn1.mydomain.com' );
        Dynamic_CDN::factory()->add_domain( 'cdn2.mydomain.com' );
    }
    add_action( 'dynamic_cdn_first_loaded', 'my_cdn_domains' );

= What if I want to add my domains through wp-config.php?

Simply define a DYNCDN_DOMAINS constant that's a comma-delimited list of your cdn domains.  For example:

    define( 'DYNCDN_DOMAINS', 'cdn0.mydomain.com,cdn1.mydomain.com,cdn2.mydomain.com' );

= What if I don't add any domains, will this break my images? =

Hopefully not.  If you haven't added any domains the plugin will not rewrite anything, bypassing your images entirely.

== Screenshots ==

None at this time.

== Changelog ==

= 0.2.0 =
* New: CDN domains can be added with a constant.
* Fix: Make domain mapping multisite aware. props @trepmal

= 0.1.0 =
* First release

== Upgrade Notice ==

= 0.1.0 =
First Release