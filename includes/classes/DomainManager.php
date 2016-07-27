<?php
namespace EAMann\Dynamic_CDN;

/**
 * Companion method for DomainManager object.
 *
 * @param string $domain
 *
 * @return DomainManager
 */
function DomainManager( $domain ) {
	static $managers = array();

	if ( ! isset( $managers[ $domain ] ) ) {
		$managers[ $domain ] = new DomainManager( $domain );
	}

	return $managers[ $domain ];
}

class DomainManager {
	/**
	 * @var array Domain to use as a CDN.
	 */
	protected $cdn_domains = array();

	/**
	 * @var bool
	 */
	protected $has_domains = false;

	/**
	 * @var bool Flag to filter only uploaded content.
	 */
	public $uploads_only = false;

	/**
	 * @var array File extensions to filter.
	 */
	public $extensions = array();

	/**
	 * @var string
	 */
	public $site_domain;

	/**
	 * @var DomainManager Most recently-created manager
	 */
	protected static $last;

	/**
	 * Get the default domain manager for the current site.
	 *
	 * @return DomainManager
	 */
	public static function current() {
		return DomainManager( get_bloginfo( 'url' ) );
	}

	/**
	 * Fetch the most recently-created domain manager.
	 * While manager creation should be explicit, this gives us the ability
	 * to skip fetching the 'current' manager if we know there's only one (and
	 * instead just grab it directly).
	 *
	 * Note: This can be unstable if, for some reason, multiple managers are created
	 * using differing root domains!
	 *
	 * @return DomainManager
	 */
	public static function last() {
		return self::$last;
	}

	public function __construct( $domain ) {
		$this->site_domain = $domain;

		self::$last = $this;
	}

	/**
	 * Add a CDN domain to the collection.
	 *
	 * @param string $cdn_domain
	 *
	 * @return bool
	 */
	public function add( $cdn_domain, $context ) {
		if( ! isset( $this->cdn_domains[$context] ) ) {
			$this->cdn_domains[$context] = [];
		}
		if ( in_array( $cdn_domain, $this->cdn_domains[$context] ) ) {
			return false;
		} else {

			$this->cdn_domains[$context][] = $cdn_domain;

			$this->has_domains = true;

			return true;
		}
	}

	/**
	 * Get a CDN path for a given file, using a reduced checksum to automatically select from an array of available domains.
	 *
	 * @param string $file_path
	 *
	 * @return string
	 */
	public function cdn_domain( $file_path, $context = 'uploads' ) {
		// First, get a checksum for the file path to give us the index we'll use from the CDN domain array.
		$index = abs( crc32( $file_path ) ) % count( $this->cdn_domains[$context] );

		/**
		 * Return the correct CDN path to the file.
		 *
		 * @param string $cdn_domain
		 * @param string $file_path
		 */
		return apply_filters( 'dynamic_cdn_domain_for_file', $this->cdn_domains[$context][ $index ], $file_path );
	}

	/**
	 * Potentially replace a given file URL with a CDN-ified version
	 *
	 * @param string $file_url
	 *
	 * @return string
	 */
	public function new_url( $file_url ) {
		$domain = $this->cdn_domain( basename( $file_url ) );
		$url = explode( '://', $this->site_domain );

		$proto_pattern = '^((https?):)?\/\/';

		if ( count( $url ) > 1 ) array_shift($url);

		preg_match("#{$proto_pattern}#", $domain, $matches);

		/**
		 * Allows plugins to override the HTTPS protocol
		 *
		 * @param string $scheme
		 */
		$scheme = apply_filters(
			'dynamic_cdn_protocol',
			( count($matches) >= 2 ? $matches[2] : ( is_ssl() ? 'https' : 'http' ) )
		);

		$domain = esc_url( $scheme . '://' . preg_replace( "#{$proto_pattern}#" , '', $domain ) );

		/**
		 * Modify the domain we're rewriting, should an aliasing plugin be used (for example)
		 *
		 * @param string $site_domain
		 */
		$url = apply_filters( 'dynamic_cdn_site_domain', rtrim( implode( '://', $url ), '/' ) );

		$pattern = "#{$proto_pattern}" . preg_quote($url, '#') . '#';

		return preg_replace( $pattern, $domain, $file_url );
	}

	/**
	 * Verify whether or not the current manager has any registered CDN domains.
	 *
	 * @return bool
	 */
	public function has_domains( $context = 'uploads' ) {
		return ( isset( $this->cdn_domains[$context] ) && count( $this->cdn_domains[$context] ) > 0 ) ? true : false;
	}
}
