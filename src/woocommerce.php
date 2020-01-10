<?php

namespace Yoast\WP\Tools;

use Faker\Factory;
use WP_CLI;
use Yoast\WP\Tools\Generators\Core_Generator;
use Yoast\WP\Tools\Generators\WooCommerce_Generator;

/**
 * The WooCommerce faker command.
 */
class WooCommerce {

	/**
	 * Generates core content.
	 *
	 * ## OPTIONS
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
	 * default: jewelry
	 * ---
	 *
	 * [--categories=<categories>]
	 * : The number of categories to generate.
	 * ---
	 * default: 25
	 * ---
	 *
	 * [--brands=<brands>]
	 * : The number of brands to generate.
	 * ---
	 * default: 25
	 * ---
	 *
	 * [--products=<products>]
	 * : The number of products to generate.
	 * ---
	 * default: 300
	 * ---
	 *
	 * [--min-reviews=<min-reviews>]
	 * : The minimum number of reviews to generate.
	 * ---
	 * default: 3
	 * ---
	 *
	 * [--max-reviews=<max-reviews>]
	 * : The maximum number of reviews to generate.
	 * ---
	 * default: 8
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp faker woocommerce products
	 *
	 * @when after_wp_load
	 *
	 * @param string[] $args       The command line arguments.
	 * @param array    $assoc_args The associative command line arguments.
	 */
	public function products( $args, $assoc_args ) {
		$faker          = Factory::create();
		$core_generator = new Core_Generator( $faker );
		$woo_generator  = new WooCommerce_Generator( $faker, $core_generator );

		$attachment_keyword = $assoc_args['attachment-keyword'];
		$attachment_ids     = generate_with_progress(
			'attachment',
			(int) $assoc_args['attachments'],
			function( $i ) use ( $core_generator, $attachment_keyword ) {
				return $core_generator->generate_attachment( 640, 480, $attachment_keyword, "$attachment_keyword$i.jpg" );
			}
		);

		$category_ids = generate_with_progress(
			'product category',
			(int) $assoc_args['categories'],
			function() use ( $woo_generator, $attachment_ids ) {
				return $woo_generator->generate_category( $attachment_ids );
			}
		);

		$brand_ids = [];
		if ( \taxonomy_exists( 'pwb-brand' ) ) {
			$brand_ids = generate_with_progress(
				'brand',
				(int) $assoc_args['brands'],
				function() use ( $core_generator, $attachment_ids ) {
					$id = $$core_generator->generate_term( 'pwb-brand' );
					\update_term_meta( $id, 'pwb_brand_image', $this->faker->randomElement( $attachment_ids ) );
					return $id;
				}
			);
		}

		$product_ids = generate_with_progress(
			'product',
			(int) $assoc_args['products'],
			function() use ( $woo_generator, $attachment_ids, $category_ids, $brand_ids ) {
				return $woo_generator->generate_product( $attachment_ids, $category_ids, $brand_ids );
			}
		);

		add_filter( 'wp_is_comment_flood', '__return_false', PHP_INT_MAX );
		add_filter( 'pre_comment_approved', '__return_true' );
		generate_with_progress(
			'reviews',
			(int) $assoc_args['products'],
			function( $i ) use ( $woo_generator, $faker, $product_ids, $assoc_args ) {
				$review_ids        = [];
				$number_of_reviews = $faker->numberBetween( $assoc_args['min-reviews'], $assoc_args['max-reviews'] );
				for ( $i = 0; $i < $number_of_reviews; $i++ ) {
					$review_ids[] = $woo_generator->generate_review( $product_ids[ $i ] );
				}
				return $review_ids;
			}
		);
		remove_filter( 'wp_is_comment_flood', '__return_false', PHP_INT_MAX );
		remove_filter( 'pre_comment_approved', '__return_true' );
	}
}

if ( class_exists( WP_CLI::class ) ) {
	WP_CLI::add_command( 'faker woocommerce', WooCommerce::class );
}
