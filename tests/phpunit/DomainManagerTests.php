<?php
namespace EAMann\Dynamic_CDN;

use Mockery\Mock;
use WP_Mock as M;

class DomainManager_Tests extends TestCase {
	protected $testFiles = [
		'classes/DomainManager.php'
	];

	public function test_companion_constructor() {
		$first = new DomainManager( 'http://test.com' );
		$second = DomainManager( 'http://test2.com' );

		$this->assertNotSame( $second, $first );
		$this->assertInstanceOf( '\EAMann\Dynamic_CDN\DomainManager', $first );
		$this->assertInstanceOf( '\EAMann\Dynamic_CDN\DomainManager', $second );
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
}