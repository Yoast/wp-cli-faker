<?php

namespace Yoast\WP\Tools\Factories;

use Faker\Factory;
use Yoast\WP\Tools\Generators\AIOSEO_Generator;
use Yoast\WP\Tools\Generators\Core_Generator;

/**
 * The generator factory.
 */
class Generator_Factory {

	/**
	 * Creates the core generator
	 *
	 * @param string $type The type of generator.
	 *
	 * @return Core_Generator The generator.
	 */
	public static function get_core_generator( $type ) {
		$faker = Factory::create();

		if ( $type === 'aioseo' ) {
			return new AIOSEO_Generator( $faker );
		}

		return new Core_Generator( $faker );
	}
}
