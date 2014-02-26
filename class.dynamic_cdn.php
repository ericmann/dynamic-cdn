<?php

/**
 * Dynamic CDN object
 *
 * Enables filtering of content to automatically replace asset urls (i.e. images) with CDN-served equivalents.
 *
 * @package Dynamic CDN
 */
class Dynamic_CDN {

	/**
	 * @var array Domain to use as a CDN.
	 */
	protected $cdn_domains;

	/**
	 * @var bool
	 */
	protected $has_domains = false;

	/**
	 * @var bool Flag to filter only uploaded content.
	 */
	protected $uploads_only;

	/**
	 * @var array File extensions to filter.
	 */
	protected $extensions;

	/**
	 * @var string
	 */
	protected $site_domain;

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize the object and make sure the proper hooks are wired up.
	 */
	public function init() {
		$this->uploads_only = apply_filters( 'dynamic_cdn_uploads_only', false );
		$this->extensions = apply_filters( 'dynamic_cdn_extensions', array( 'jpe?g', 'gif', 'png', 'css', 'bmp', 'js', 'ico' ) );

		if ( ! is_admin() ) {
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );

			if ( $this->uploads_only ) {
				add_filter( 'dynamic_cdn_content', 'filter_uploads_only' );
			} else {
				add_filter( 'dynamic_cdn_content', 'filter' );
			}

			$this->site_domain = parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
			$this->cdn_domains = apply_filters( 'dynamic_cdn_default_domains', array() );
		}
	}

	/**
	 * Add a CDN domain to the collection.
	 *
	 * @param string $domain
	 */
	public function add_domain( $domain ) {
		$this->cdn_domains[] = $domain;

		$this->has_domains = true;
	}

	/**
	 * Get a CDN path for a given file, using a reduced checksum to automatically select from an array of available domains.
	 *
	 * @param string $file_path
	 *
	 * @return string
	 */
	public function cdn_domain( $file_path ) {
		// First, get a checksum for the file path to give us the index we'll use from the CDN domain array.
		$index = abs( crc32( $file_path ) ) % count( $this->cdn_domains );

		// Return the correct CDN path to the file
		return apply_filters( 'dynamic_cdn_domain_for_file', $this->cdn_domains[ $index ], $file_path );
	}

	/**
	 * Filter uploaded content (with the given extensions) and rewrite to a CDN.
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	public function filter_uploads_only( $content ) {
		if ( ! $this->has_domains ) {
			return $content;
		}

		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['baseurl'];
		$domain = preg_quote( parse_url( $upload_dir, PHP_URL_HOST ), '#' );
		$path = parse_url( $upload_dir, PHP_URL_PATH );
		$preg_path = preg_quote( $path, '#' );

		// Targeted replace just on uploads URLs
		return preg_replace( "#=([\"'])(https?://{$domain})?$preg_path/((?:(?!\\1]).)+)\.(" . implode( '|', $this->extensions ) . ")(\?((?:(?!\\1).)+))?\\1#", '=$1http://' . $this->cdn_domain( $path ) . $path . '/$3.$4$5$1', $content );
	}

	/**
	 * Filter all static content (with the given extensions) and rewrite to a CDN.
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	public function filter( $content ) {
		if ( ! $this->has_domains ) {
			return $content;
		}

		return preg_replace( "#=([\"'])(https?://{$this->site_domain})?/([^/](?:(?!\\1).)+)\.(" . implode( '|', $this->extensions ) . ")(\?((?:(?!\\1).)+))?\\1#", '=$1http://' . $this->cdn_domain . '/$3.$4$5$1', $content );
	}

	/**
	 * Create an output buffer so we can dynamically rewrite any URLs going out.
	 */
	public function template_redirect() {
		ob_start( array( $this, 'ob' ) );
	}

	/**
	 * Callback function for the output buffer that allows us to filter content.
	 *
	 * @param $contents
	 *
	 * @return mixed|void
	 */
	public function ob( $contents ) {
		return apply_filters( 'dynamic_cdn_content', $contents, $this );
	}

	/**
	 * Factory method for grabbing a single instance of the CDN object.
	 *
	 * Much better than Singleton-instantiation ;-)
	 *
	 * @return Dynamic_CDN
	 */
	public static function factory() {
		static $instance = false;

		if ( false === $instance ) {
			$instance = new self;
		}

		return $instance;
	}
} 