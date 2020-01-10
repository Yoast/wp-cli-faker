<?php

namespace Yoast\WP\Tools;

use Exception;
use WP_CLI;

use function WP_CLI\Utils\make_progress_bar;

/**
 * Calls a generator function a number of times with a progress bar.
 *
 * @param string   $name      The name of the resource being generated.
 * @param int      $count     The amount of times the generator function should be run.
 * @param callable $generator The generator function.
 *
 * @return int[] The ids of the generated resources.
 */
function generate_with_progress( $name, $count, $generator ) {
	$ids      = [];
	$progress = make_progress_bar( "$name generation", $count );
	for ( $i = 0; $i < $count; $i++ ) {
		try {
			$ids[] = $generator( $i, $ids );
		} catch ( Exception $e ) {
			WP_CLI::warning( "Could not generate $name: " . $e->getMessage() );
		}
		$progress->tick();
	}
	$progress->finish();

	return $ids;
}
