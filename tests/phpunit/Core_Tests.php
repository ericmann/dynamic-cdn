<?php
namespace EAMann\Dynamic_CDN\Core;

use Mockery\Mock;
use EAMann\Dynamic_CDN as Base;
use WP_Mock as M;

class Core_Tests extends Base\TestCase {
	protected $testFiles = [
		'classes/DomainManager.php',
		'functions/core.php'
	];

	/**
	 * Test load method.
	 */
	public function test_setup() {
		// Setup
		M::expectActionAdded( 'init',             'EAMann\Dynamic_CDN\Core\init' );
		M::expectActionAdded( 'dynamic_cdn_init', 'EAMann\Dynamic_CDN\Core\initialize_manager' );
		M::expectAction( 'dynamic_cdn_first_loaded' );

		// Act
		setup();

		// Verify
		$this->assertConditionsMet();
	}

	/**
	 * Test initialization method.
	 */
	public function test_init() {
		// Setup
		M::expectAction( 'dynamic_cdn_init' );

		// Act
		init();

		// Verify
		$this->assertConditionsMet();
	}

	public function test_srcset_replacement() {
		$manager = Base\DomainManager( 'http://test.com' );
		$manager->add( 'https://cdn1.com' );

		// Mocks
		M::wpFunction( 'is_ssl', [ 'return' => false ] );
		M::wpPassthruFunction( 'esc_url' );

		$source = [
			'url' => 'http://test.com/image.jpg'
		];

		$replacer = srcset_replacer( 'http://test.com' );

		$this->assertEquals( 'https://cdn1.com/image.jpg', $replacer( $source )['url'] );
	}

	public function test_srcset_replacement_no_protocol_cdn() {
		$manager = Base\DomainManager( 'http://test1.com' );
		$manager->add( 'cdn1.com' );

		// Mocks
		M::wpFunction( 'is_ssl', [ 'return' => false ] );
		M::wpPassthruFunction( 'esc_url' );

		$source = [
			'url' => 'http://test1.com/image.jpg'
		];

		$replacer = srcset_replacer( 'http://test1.com' );

		$this->assertEquals( 'http://cdn1.com/image.jpg', $replacer( $source )['url'] );
	}


	public function test_host_with_port() {
		M::wpFunction( 'is_ssl', [ 'return' => true ] );

		\WP_Mock::wpFunction( 'get_bloginfo', array(
			'args' => 'url',
			'return' => 'http://localhost:9001'
		) );
		\WP_Mock::wpFunction( 'wp_upload_dir', array(
			'return' => array(
				'baseurl' => 'http://localhost:9001/wp-content/uploads'
			)
		) );
		$manager = Base\DomainManager( 'localhost:9001' );
		$manager->extensions = array( 'jpg' );
		$manager->add( 'cdn1.com' );
		$content = '<img src="http://localhost:9001/puppy.jpg" />';
		$filtered_content = filter( $content );
		$expected = '<img src="https://cdn1.com/puppy.jpg" />';

		$this->assertEquals( $expected, $filtered_content );
	}

	public function test_query_string() {
		M::wpFunction( 'is_ssl', [ 'return' => true ] );

		\WP_Mock::wpFunction( 'get_bloginfo', array(
			'args' => 'url',
			'return' => 'http://localhost'
		) );
		\WP_Mock::wpFunction( 'wp_upload_dir', array(
			'return' => array(
				'baseurl' => 'http://localhost/wp-content/uploads'
			)
		) );
		$manager = Base\DomainManager( 'localhost' );
		$manager->extensions = array( 'jpg' );
		$manager->add( 'cdn1.com' );
		$site_url = 'http://localhost';
		$content = '
			<img src="/puppy.jpg" />
			<img src="/puppy.jpg?" />
			<img src="/puppy.jpg?a=b" />
		';
		$filtered_content = filter( $content );
		$expected = '
			<img src="https://cdn1.com/puppy.jpg" />
			<img src="https://cdn1.com/puppy.jpg?" />
			<img src="https://cdn1.com/puppy.jpg?a=b" />
		';

		$this->assertEquals( $expected, $filtered_content );
	}

	public function test_escaped_content() {
		M::wpFunction( 'is_ssl', [ 'return' => true ] );
		\WP_Mock::wpFunction( 'get_bloginfo', array(
			'args' => 'url',
			'return' => 'http://localhost'
		) );
		\WP_Mock::wpFunction( 'wp_upload_dir', array(
			'return' => array(
				'baseurl' => 'http://localhost/wp-content/uploads'
			)
		) );
		$manager = Base\DomainManager( 'localhost' );
		$manager->extensions = array( 'jpg' );
		$manager->add( 'cdn1.com' );
		$site_url = 'http://localhost';
		$content = '
			<img src=\"\/wp-content\/uploads\/2016\/06\/puppy-2.jpg\" \/>
			<img src=\"\/puppy-2.jpg\" \/>
		';
		$filtered_content = filter( $content );
		$expected = '
			<img src=\"https:\/\/cdn1.com\/wp-content\/uploads\/2016\/06\/puppy-2.jpg\" \/>
			<img src=\"https:\/\/cdn1.com\/puppy-2.jpg\" \/>
		';
		$this->assertEquals( $expected, $filtered_content );
	}

	public function test_escaped_content_uploads_only() {
		M::wpFunction( 'is_ssl', [ 'return' => true ] );
		\WP_Mock::wpFunction( 'get_bloginfo', array(
			'args' => 'url',
			'return' => 'http://localhost'
		) );
		\WP_Mock::wpFunction( 'wp_upload_dir', array(
			'return' => array(
				'baseurl' => 'http://localhost/wp-content/uploads'
			)
		) );
		$manager = Base\DomainManager( 'localhost' );
		$manager->extensions = array( 'jpg' );
		$manager->add( 'cdn1.com' );
		$site_url = 'http://localhost';
		$content = '
			<img src=\"\/wp-content\/uploads\/2016\/06\/puppy-2.jpg\" \/>
			<img src=\"\/puppy-2.jpg\" \/>
		';
		$filtered_content = filter_uploads_only( $content );
		$expected = '
			<img src=\"https:\/\/cdn1.com\/wp-content\/uploads\/2016\/06\/puppy-2.jpg\" \/>
			<img src=\"\/puppy-2.jpg\" \/>
		';
		$this->assertEquals( $expected, $filtered_content );
	}
}
