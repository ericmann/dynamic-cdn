<?php
/**
 * The core namespace for the Dynamic CDN project
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace EAMann\Dynamic_CDN\Core;

use EAMann\Dynamic_CDN\DomainManager;

/**
 * Default setup routine
 *
 * @uses add_action()
 * @uses do_action()
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'init',             $n( 'init' ) );
	add_action( 'dynamic_cdn_init', $n( 'initialize_manager' ) );

	/**
	 * Allow other plugins (i.e. mu-plugins) to hook in and populate the CDN domain array.
	 */
	do_action( 'dynamic_cdn_first_loaded' );
}

/**
 * Get the site's current domain
 *
 * @return string
 */
function get_site_domain() {
	$url_parts = parse_url( get_bloginfo( 'url' ) );

	$site_domain = $url_parts['host'] . ( isset( $url_parts['port'] ) ? ':' . $url_parts['port'] : '' );

	/**
	 * Update the stored site domain, should an aliasing plugin be used (for example)
	 *
	 * @param string $site_domain
	 */
	$site_domain = apply_filters( 'dynamic_cdn_site_domain', $site_domain );

	return $site_domain;
}


/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @uses do_action()
 *
 * @return void
 */
function init() {
	do_action( 'dynamic_cdn_init' );
}

/**
 * Initialize the CDN domain manager for the current domain.
 */
function initialize_manager() {
	$site_domain = get_site_domain();

	// Instantiate the domain manager
	$manager = \EAMann\Dynamic_CDN\DomainManager( $site_domain );

	/**
	 * Flag whether to filter all media content or just uploads. Set to `true` to only process uploads from the CDN
	 *
	 * @param bool $uploads_only
	 */
	$manager->uploads_only = apply_filters( 'dynamic_cdn_uploads_only', false );

	/**
	 * Filter the file extensions that will be served from the CDN. Expects an array of RegEx-style strings.
	 *
	 * @param array $extensions
	 */
	$manager->extensions = apply_filters( 'dynamic_cdn_extensions', array( 'jpe?g', 'gif', 'png', 'bmp', 'js', 'ico' ) );

	if ( ! is_admin() ) {
		add_action( 'template_redirect', '\EAMann\Dynamic_CDN\Core\create_buffer' );

		if ( $manager->uploads_only ) {
			add_filter( 'the_content'/*'dynamic_cdn_content'*/, '\EAMann\Dynamic_CDN\Core\filter_uploads_only' );
		} else {
			add_filter( 'dynamic_cdn_content', '\EAMann\Dynamic_CDN\Core\filter' );
		}

		add_filter( 'wp_calculate_image_srcset', '\EAMann\Dynamic_CDN\Core\srcsets', 10, 5 );

		add_action( 'rest_api_init', '\EAMann\Dynamic_CDN\Core\create_buffer' );

		$cdn_domains = array();
		if ( defined( 'DYNCDN_DOMAINS' ) ) {
			$cdn_domains = explode( ',', DYNCDN_DOMAINS );
			$cdn_domains = array_map( 'trim', $cdn_domains );
		}

		/**
		 * Programmatically control and/or override any CDN domains as passed in via a hard-coded constant.
		 *
		 * @param array $cdn_domains
		 */
		$cdn_domains = apply_filters( 'dynamic_cdn_default_domains', $cdn_domains );

		// Add all domains to the manager
		array_map( function( $domain ) use ( $manager ) { $manager->add( $domain ); }, $cdn_domains );
	}
}

/**
 * Handles the WP 4.4 srcset Dynamic CDN support
 *
 * @param array  $sources
 * @param array  $size_array
 * @param string $image_src
 * @param array  $image_meta
 * @param int    $attachment_id
 *
 * @return mixed
 */
function srcsets( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {

	// If we shouldn't process, ABORT!
	if( is_admin() || ! defined( 'DYNCDN_DOMAINS' ) ) {
		return $sources;
	}

	// Iteratively update each srcset
	$replacer = srcset_replacer( get_site_domain() );
	array_walk( $sources, $replacer );

	return $sources;
}

/**
 * Create a domain-specific srcset replacement function for use in array iterations
 *
 * @param string $domain
 * @return \Closure
 */
function srcset_replacer( $domain ) {
	$manager = \EAMann\Dynamic_CDN\DomainManager( $domain );

	/**
	 * Replace the URL for a specific source in a srcset with a CDN'd version
	 *
	 * @param array $source
	 *
	 * @return array
	 */
	return function( &$source ) use ( $manager ) {
		$source['url'] = $manager->new_url( $source['url'] );

		return $source;
	};
}

/**
 * Create an output buffer so we can dynamically rewrite any URLs going out.
 */
function create_buffer() {
	ob_start( '\EAMann\Dynamic_CDN\Core\ob' );
}

/**
 * Callback function for the output buffer that allows us to filter content.
 *
 * @param $contents
 *
 * @return mixed|void
 */
function ob( $contents ) {
	$manager = DomainManager::last();

	/**
	 * Filter the content from the output buffer
	 *
	 * @param string      $contents
	 * @param Dynamic_CDN $this
	 */
	return apply_filters( 'dynamic_cdn_content', $contents, $manager );
}

/**
 * Filter uploaded content (with the given extensions) and rewrite to a CDN.
 *
 * @param $content
 *
 * @return mixed
 */
function filter_uploads_only( $content ) {
	$manager = DomainManager::last();

	if ( ! $manager->has_domains() ) {
		return $content;
	}

	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['baseurl'];
	$url_parts = parse_url( $upload_dir );
	$domain = $url_parts['host'] . ( isset( $url_parts['port'] ) ? ':' . $url_parts['port'] : '' );
	$domain = preg_quote( $domain, '#' );

	$path = ltrim( parse_url( $upload_dir, PHP_URL_PATH ), '/' );
	$preg_path = str_replace( '/', '\\\?/', preg_quote( $path, '#' ) );

	// Targeted replace just on uploads URLs
	$pattern = "#(\\\?[\"'])"  // opening quote
	. "(https?:\\\?/\\\?/{$domain})?"// domain (optional)
	. "\\\?/($preg_path\\\?/" // uploads path
	// . "((?:(?!\\1]).)+)" // look for anything that's not our opening quote
	. "[\w\s\\\/\-\,\.]+)" // look for anything that's not our opening quote
	. "\.(" . implode( '|', $manager->extensions ) . ")" // extensions
	. "(\?((?:(?!\\1).)*))?" // match query strings
	. "\\1#"; // closing quote

	return preg_replace_callback( $pattern, 'EAMann\Dynamic_CDN\Core\filter_cb', $content );
}

/**
 * Filter all static content (with the given extensions) and rewrite to a CDN.
 *
 * @param $content
 *
 * @return mixed
 */
function filter( $content ) {
	$manager = DomainManager::last();

	if ( ! $manager->has_domains() ) {
		return $content;
	}
	$url = explode( '://', get_bloginfo( 'url' ) );
	array_shift( $url );

	/**
	 * Modify the domain we're rewriting, should an aliasing plugin be used (for example)
	 *
	 * @param string $site_domain
	 */
	$url = apply_filters( 'dynamic_cdn_site_domain', rtrim( implode( '://', $url ), '/' ) );
	$url = preg_quote( $url, '#' );

	$pattern = "#(\\\?[\"'])" // opening quote
	. "((?:https?:)?\\\?/\\\?/{$url})?\\\?/"  // domain (optional)
	// . "([^/](?:(?!\\1).)+)" // look for anything that's not our opening quote
	. "([^/][\w\s\\\/\-\,\.]+)" // look for anything that's not our opening quote
	. "\.(" . implode( '|', $manager->extensions ) . ")" // extensions
	. "(\?((?:(?!\\1).)*))?" // match query strings
	. "\\1#"; // closing quote

	return preg_replace_callback( $pattern, 'EAMann\Dynamic_CDN\Core\filter_cb', $content );
}

/**
 * Callback function used to automatically parse assets and replace the image src with a CDN version.
 *
 * @param $matches
 *
 * @return string
 */
function filter_cb( $matches ) {
	$manager = DomainManager::last();

	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['baseurl'];
	$upload_path = parse_url( $upload_dir, PHP_URL_PATH );

	$domain = $manager->cdn_domain( $matches[0] );

	$url = explode( '://', get_bloginfo( 'url' ) );
	array_shift( $url );

	/**
	 * Modify the domain we're rewriting, should an aliasing plugin be used (for example)
	 *
	 * @param string $site_domain
	 */
	$url = apply_filters( 'dynamic_cdn_site_domain', rtrim( implode( '://', $url ), '/' ) );
	$url = str_replace( $manager->site_domain, $domain, $url );

	// Make sure to use https if the request is over SSL
	$scheme = apply_filters( 'dynamic_cdn_protocol', ( is_ssl() ? 'https' : 'http' ) );

	// Append query string, if its available
	$query_string = isset( $matches[5] ) ? $matches[5] : '';

	// If backslashes were escaped, then they need to continue being escaped
	if( strpos( $matches[0], '\\/' ) !== false ) {
		$add_slashes = true;
	} else {
		$add_slashes = false;
	}

	$result = "{$matches[1]}{$scheme}:"
						. ( $add_slashes ? addcslashes("//{$url}/", '/') : "//{$url}/" )
						. "{$matches[3]}.{$matches[4]}{$query_string}{$matches[1]}";

	return $result;
}
