<?php
namespace EAMann\Dynamic_CDN;

use Mockery\Mock;
use WP_Mock as M;

/**
 * Class DomainManager_Tests
 *
 * @package EAMann\Dynamic_CDN
 *
 * @runTestsInSeparateProcesses
 */
class DomainManager_Tests extends TestCase {
	protected $testFiles = [
		'classes/DomainManager.php'
	];

	public function test_companion_constructor() {
		$first = new DomainManager( 'http://test.com' );
		$second = DomainManager( 'http://test2.com' );
		$third = DomainManager( 'http://test2.com' );

		$this->assertNotSame( $second, $first );
		$this->assertInstanceOf( '\EAMann\Dynamic_CDN\DomainManager', $first );
		$this->assertInstanceOf( '\EAMann\Dynamic_CDN\DomainManager', $second );
		$this->assertSame( $second, $third );
	}

	public function test_adds_domains() {
		$manager = DomainManager( 'http://test.com' );

		$this->assertTrue( $manager->add( 'http://cdn1.com' ) );
		$this->assertTrue( $manager->add( 'http://cdn2.com' ) );
		$this->assertFalse( $manager->add( 'http://cdn1.com' ) );
	}

	public function test_cdn_domain_selection() {
		$manager = DomainManager( 'http://test.com' );
		$manager->add( 'http://cdn1.com' );
		$manager->add( 'http://cdn2.com' );
		$manager->add( 'http://cdn3.com' );

		$this->assertEquals( $manager->cdn_domain( 'file1.jpg' ), $manager->cdn_domain( 'file1.jpg' ) );
		$this->assertNotEquals( $manager->cdn_domain( 'file1.jpg' ), $manager->cdn_domain( 'file2.jpg' ) );
	}

	public function test_domain_replacement() {
		$manager = DomainManager( 'http://test.com' );
		$manager->add( 'http://cdn1.com' );

		$original = 'http://test.com/image.png';
		$expected = 'http://cdn1.com/image.png';

		// Mocks
		M::wpFunction( 'is_ssl', [ 'return' => false ] );
		M::wpPassthruFunction( 'esc_url' );

		// Verify
		$this->assertEquals( $expected, $manager->new_url( $original ) );
	}

	public function test_domain_replacement_ssl() {
		$manager = DomainManager( 'https://test.com' );
		$manager->add( 'https://cdn1.com' );

		$original = 'https://test.com/image.png';
		$expected = 'https://cdn1.com/image.png';

		// Mocks
		M::wpFunction( 'is_ssl', [ 'return' => true ] );
		M::wpPassthruFunction( 'esc_url' );

		// Verify
		$this->assertEquals( $expected, $manager->new_url( $original ) );
	}

	public function test_domain_replacement_mixed() {
		$manager = DomainManager( 'http://test.com' );
		$manager->add( 'https://cdn1.com' );

		$original = 'http://test.com/image.png';
		$expected = 'https://cdn1.com/image.png';

		// Mocks
		M::wpFunction( 'is_ssl', [ 'return' => false ] );
		M::wpPassthruFunction( 'esc_url' );

		// Verify
		$this->assertEquals( $expected, $manager->new_url( $original ) );
	}

	public function test_has_domains() {
		$manager = DomainManager( 'http://test.com' );
		$this->assertFalse( $manager->has_domains() );

		$manager->add( 'https://cdn1.com' );
		$this->assertTrue( $manager->has_domains() );
	}

	public function test_default_manager() {
		M::wpFunction( 'get_bloginfo', [
			'args' => ['url'],
			'return' => 'http://test.com'
		] );

		$manager = DomainManager::current();

		$regular = DomainManager( 'http://test.com' );

		$this->assertSame( $manager, $regular );
	}
}