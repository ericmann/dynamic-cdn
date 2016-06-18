<?php
namespace EAMann\Dynamic_CDN\Core;

use Mockery\Mock;
use EAMann\Dynamic_CDN as Base;
use WP_Mock as M;

class Core_Tests extends Base\TestCase {
	protected $testFiles = [
		'functions/core.php'
	];
	
	/**
	 * Test load method.
	 */
	public function test_setup() {
		// Setup
		\WP_Mock::expectActionAdded( 'init', 'EAMann\Dynamic_CDN\Core\init' );
		\WP_Mock::expectAction( 'dynamic_cdn_first_loaded' );

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
		\WP_Mock::expectAction( 'dynamic_cdn_init' );
		
		// Act
		init();
		
		// Verify
		$this->assertConditionsMet();
	}
}