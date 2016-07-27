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
		$manager->add( 'https://cdn1.com', 'uploads' );

		// Mocks
		M::wpFunction( 'is_ssl', [ 'return' => false ] );
		M::wpPassthruFunction( 'esc_url' );

		$source = [
			'url' => 'http://test.com/image.jpg'
		];

		$replacer = srcset_replacer( 'http://test.com' );

		$this->assertEquals( 'http://cdn1.com/image.jpg', $replacer( $source )['url'] );
	}
}
