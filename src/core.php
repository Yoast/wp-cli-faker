<?php

namespace Yoast\WP\Tools;

use WP_CLI;
use Yoast\WP\Tools\Factories\Generator_Factory;

/**
 * The core faker command.
 */
class Core {

	/**
	 * Generates core content.
	 *
	 * ## OPTIONS
	 *
	 * [--authors=<authors>]
	 * : The number of authors to generate.
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--categories=<categories>]
	 * : The number of categories to generate.
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--tags=<tags>]
	 * : The number of tags to generate.
	 * ---
	 * default: 25
	 * ---
	 *
	 * [--attachments=<attachments>]
	 * : The number of attachments to generate.
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--attachment-keyword=<attachment-keyword>]
	 * : The keyword used to generate attachments.
	 * ---
	 * default: WordPress
	 * ---
	 *
	 * [--posts=<posts>]
	 * : The number of posts to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--pages=<pages>]
	 * : The number of pages to generate.
	 * ---
	 * default: 5
	 * ---
	 *
	 * [--type=<type>]
	 * : The type of generator to use. Passing aioseo generators posts with aioseo meta.
	 * ---
	 * default: default
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp faker core content
	 *
	 * @when after_wp_load
	 *
	 * @param string[] $args       The command line arguments.
	 * @param array    $assoc_args The associative command line arguments.
	 */
	public function content( $args, $assoc_args ) {
		$generator = Generator_Factory::get_core_generator( $assoc_args['type'] );

		$author_ids   = generate_with_progress(
			'author',
			(int) $assoc_args['authors'],
			function() use ( $generator ) {
				return $generator->generate_user();
			}
		);
		$category_ids = generate_with_progress(
			'category',
			(int) $assoc_args['categories'],
			function( $i, $ids ) use ( $generator ) {
				return $generator->generate_term( 'category', $ids );
			}
		);

		$tag_ids = generate_with_progress(
			'tag',
			(int) $assoc_args['tags'],
			function() use ( $generator ) {
				return $generator->generate_term( 'post_tag' );
			}
		);

		$attachment_keyword = $assoc_args['attachment-keyword'];
		$attachment_ids     = generate_with_progress(
			'attachment',
			(int) $assoc_args['attachments'],
			function( $i ) use ( $generator, $attachment_keyword ) {
				return $generator->generate_attachment( 640, 480, $attachment_keyword, "$attachment_keyword$i.jpg" );
			}
		);

		generate_with_progress(
			'post',
			(int) $assoc_args['posts'],
			function() use ( $generator, $author_ids, $attachment_ids, $category_ids, $tag_ids ) {
				return $generator->generate_post( 'post', $author_ids, $attachment_ids, [], $category_ids, $tag_ids );
			}
		);

		generate_with_progress(
			'page',
			(int) $assoc_args['pages'],
			function( $i, $ids ) use ( $generator, $author_ids, $attachment_ids, $category_ids, $tag_ids ) {
				return $generator->generate_post( 'page', $author_ids, $attachment_ids, $ids, $category_ids, $tag_ids );
			}
		);
	}
}

if ( class_exists( WP_CLI::class ) ) {
	WP_CLI::add_command( 'faker core', Core::class );
}
