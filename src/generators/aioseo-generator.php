<?php

namespace Yoast\WP\Tools\Generators;

use Exception;

/**
 * AIOSEO post generator.
 *
 * Used for testing the AIOSEO importer.
 */
class AIOSEO_Generator extends Core_Generator {

	const META_KEYS = [
		'_aioseo_title',
		'_aioseo_description',
		'_aioseo_og_title',
		'_aioseo_og_description',
		'_aioseo_twitter_title',
		'_aioseo_twitter_description',
	];

	const REPLACE_VARS = [
		'#author_first_name',
		'#author_last_name',
		'#author_name',
		'#categories',
		'#current_date',
		'#current_day',
		'#current_month',
		'#current_year',
		'#permalink',
		'#post_content',
		'#post_date',
		'#post_day',
		'#post_month',
		'#post_title',
		'#post_year',
		'#post_excerpt_only',
		'#post_excerpt',
		'#separator_sa',
		'#site_title',
		'#tagline',
		'#taxonomy_title',
	];

	private static $custom_fields = [];

	/**
	 * Generates a post with AIOSEO meta.
	 *
	 * @param string $post_type      The post type.
	 * @param int[]  $author_ids     The possible author ids.
	 * @param int[]  $attachment_ids The possible attachment ids.
	 * @param int[]  $parent_ids     The possible parent ids.
	 * @param int[]  $category_ids   The possible category ids.
	 * @param int[]  $tag_ids        The possible tag ids.
	 *
	 * @return int The post id.
	 *
	 * @throws Exception If the post could not be saved.
	 */
	public function generate_post( $post_type, $author_ids, $attachment_ids, $parent_ids, $category_ids, $tag_ids ) {
		$post_id = parent::generate_post( $post_type, $author_ids, $attachment_ids, $parent_ids, $category_ids, $tag_ids );

		$all_replace_vars = self::REPLACE_VARS;
		foreach ( self::$custom_field_keys as $key ) {
			$all_replace_vars[] = '#custom_field-' . $key;
		}

		foreach ( self::META_KEYS as $meta_key ) {
			$replace_vars = $this->faker->randomElements( $all_replace_vars, $this->faker->numberBetween( 2, 5 ) );

			\add_post_meta( $post_id, $meta_key, \implode( ' ', $replace_vars ) );
		}

		return $post_id;
	}
}
