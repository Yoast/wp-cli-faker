<?php

namespace Yoast\WP\Tools\Generators;

use Exception;
use Faker\Generator;

/**
 * Generator for core data.
 */
class Core_Generator {

	/**
	 * The faker instance.
	 *
	 * @var Generator
	 */
	private $faker;

	/**
	 * Construct a generator for core data.
	 *
	 * @param Generator $faker The faker instance.
	 */
	public function __construct( Generator $faker ) {
		$this->faker = $faker;
	}

	/**
	 * Generates a user.
	 *
	 * @param string $role The role of the user.
	 *
	 * @return int The user id.
	 *
	 * @throws Exception If the user could not be saved.
	 */
	public function generate_user( $role = 'author' ) {
		$author_id = \wp_insert_user(
			[
				'user_login'      => $this->faker->unique()->userName,
				'user_pass'       => $this->faker->password,
				'user_url'        => $this->faker->url,
				'user_email'      => $this->faker->email,
				'first_name'      => $this->faker->firstName,
				'last_name'       => $this->faker->lastName,
				'description'     => $this->faker->paragraph,
				'user_registered' => $this->faker->dateTimeThisCentury->format( 'Y-m-d H:i:s' ),
				'role'            => $role,
			]
		);

		if ( is_wp_error( $author_id ) ) {
			throw new Exception( $author_id->get_error_message() );
		}

		return $author_id;
	}

	/**
	 * Generates an attachment.
	 *
	 * @param int    $width     They width of the image.
	 * @param int    $height    The height of the image.
	 * @param string $keyword   The keyword to search loremflicker.com for.
	 * @param string $file_name The file name to save the image as.
	 *
	 * @return int The attachment id.
	 *
	 * @throws Exception If the image could not be downloaded or saved.
	 */
	public function generate_attachment( $width, $height, $keyword, $file_name ) {
		$file_array         = [];
		$file_array['name'] = $file_name;

		// Download file to temp location.
		$file_array['tmp_name'] = download_url( "https://loremflickr.com/$width/$height/$keyword" );
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			throw new Exception( $file_array['tmp_name']->get_error_message() );
		}

		// Do the validation and storage stuff.
		$attachment_id = media_handle_sideload( $file_array, 0, null );

		if ( is_wp_error( $attachment_id ) ) {
			throw new Exception( $attachment_id->get_error_message() );
		}

		return $attachment_id;
	}

	/**
	 * Generates a term
	 *
	 * @param string $taxonomy   The taxonomy of the term.
	 * @param array  $parent_ids Optional. The list of possible parent ids. 50% a random one will be assigned.
	 *
	 * @return int The term id.
	 *
	 * @throws Exception If the term could not be saved.
	 */
	public function generate_term( $taxonomy, $parent_ids = [] ) {
		$category = \wp_insert_term(
			$this->faker->unique()->catchPhrase,
			$taxonomy,
			[
				'description' => $this->faker->paragraph,
				'parent'      => ( $this->faker->boolean && count( $parent_ids ) > 0 ) ? $this->faker->randomElement( $parent_ids ) : null,
			]
		);

		if ( \is_wp_error( $category ) ) {
			throw new Exception( $category->get_error_message() );
		}

		return $category['term_id'];
	}

	/**
	 * Generates a post.
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
		$date    = $this->faker->dateTimeThisYear();
		$post_id = \wp_insert_post(
			[
				'post_author'   => $this->faker->randomElement( $author_ids ),
				'post_date'     => $date->format( 'Y-m-d H:i:s' ),
				'post_content'  => $this->generate_post_content( $attachment_ids ),
				'post_title'    => $this->faker->catchPhrase,
				'post_type'     => $post_type,
				'post_status'   => 'publish',
				'post_parent'   => ( $this->faker->boolean( 25 ) && count( $parent_ids ) > 0 ) ? $this->faker->randomElement( $parent_ids ) : null,
				'post_modified' => ( ( $this->faker->boolean ) ? $this->faker->dateTimeBetween( $date ) : $date )->format( 'Y-m-d H:i:s' ),
				'post_category' => $this->faker->randomElements( $category_ids, $this->faker->numberBetween( 1, 2 ) ),
				'tags_input'    => $this->faker->randomElements( $tag_ids, $this->faker->numberBetween( 0, 4 ) ),
			]
		);

		if ( \is_wp_error( $$post_id ) ) {
			throw new Exception( $$post_id->get_error_message() );
		}

		\set_post_thumbnail( $post_id, $this->faker->randomElement( $attachment_ids ) );

		return $post_id;
	}

	/**
	 * Generates post content.
	 *
	 * @param int[] $attachment_ids The possible attachment ids.
	 *
	 * @return string The post content.
	 */
	public function generate_post_content( $attachment_ids ) {
		$blocks      = [];
		$block_count = $this->faker->numberBetween( 8, 12 );

		for ( $i = 0; $i < $block_count; $i++ ) {
			if ( $this->faker->boolean( 90 ) ) {
				$blocks[] = "<!-- wp:paragraph -->\n<p>" . $this->faker->paragraph . "</p>\n<!-- /wp:paragraph -->";
				continue;
			}

			$attachment_id  = $this->faker->randomElement( $attachment_ids );
			$attachment_url = wp_get_attachment_url( $attachment_id );
			$blocks[]       = "<!-- wp:image {\"id\":$attachment_id,\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"$attachment_url\" alt=\"\" class=\"wp-image-$attachment_id\"/></figure>\n<!-- /wp:image -->";
		}

		return implode( "\n", $blocks );
	}
}
