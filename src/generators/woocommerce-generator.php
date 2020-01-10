<?php

namespace Yoast\WP\Tools\Generators;

use Exception;
use Faker\Generator;

/**
 * Generator for core data.
 */
class WooCommerce_Generator {

	/**
	 * The faker instance.
	 *
	 * @var Generator
	 */
	private $faker;

	/**
	 * Construct a generator for core data.
	 *
	 * @param Generator      $faker          The faker instance.
	 * @param Core_Generator $core_generator The core generator instance.
	 */
	public function __construct( $faker, $core_generator ) {
		$this->faker          = $faker;
		$this->core_generator = $core_generator;
	}

	/**
	 * Generates a review
	 *
	 * @param int $product_id The product id.
	 *
	 * @return int The review id.
	 *
	 * @throws Exception If the review could not be saved.
	 */
	public function generate_review( $product_id ) {
		$controller = new \WC_REST_Product_Reviews_Controller();

		$params  = [
			'product_id'     => $product_id,
			'review'         => $this->faker->paragraph,
			'reviewer'       => $this->faker->name,
			'reviewer_email' => $this->faker->email,
			'rating'         => $this->faker->numberBetween( 0, 5 ),
			'verified'       => $this->faker->boolean,
		];
		$request = new \WP_REST_Request( 'POST' );
		$request->set_body_params( $params );

		$response = $controller->create_item( $request );

		if ( \is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		return $response->data['id'];
	}

	/**
	 * Generates a product category
	 *
	 * @param int[] $attachment_ids The possible attachment ids.
	 *
	 * @return int The product category id.
	 *
	 * @throws Exception If the product category could not be saved.
	 */
	public function generate_category( $attachment_ids ) {
		$controller = new \WC_REST_Product_Categories_Controller();

		$params  = [
			'name'        => $this->faker->unique()->catchPhrase,
			'description' => $this->core_generator->generate_post_content( $attachment_ids ),
			'image'       => [ 'id' => $this->faker->randomElement( $attachment_ids ) ],
		];
		$request = new \WP_REST_Request( 'POST' );
		$request->set_body_params( $params );

		$response = $controller->create_item( $request );

		if ( \is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		return $response->data['id'];
	}

	/**
	 * Generates a product
	 *
	 * @param int[] $attachment_ids The possible attachment ids.
	 * @param int[] $category_ids   The possible category ids.
	 * @param int[] $brand_ids      The possible brand ids.
	 *
	 * @return int The product id.
	 *
	 * @throws Exception If the product could not be saved.
	 */
	public function generate_product( $attachment_ids, $category_ids, $brand_ids ) {
		$controller = new \WC_REST_Products_Controller();

		$params = [
			'name'          => $this->faker->unique()->catchPhrase,
			'description'   => $$this->core_generator->generate_post_content( $attachment_ids ),
			'status'        => 'publish',
			'type'          => 'Simple',
			'featured'      => $this->faker->boolean( 10 ),
			'sku'           => $this->faker->numerify( '######' ),
			'regular_price' => $this->faker->numberBetween( 10, 100 ),
			'images'        => [],
			'categories'    => [],
		];

		$number_of_images = $this->faker->numberBetween( 1, 3 );
		for ( $i = 0; $i < $number_of_images; $i++ ) {
			$params['images'][] = [ 'id' => $this->faker->randomElement( $attachment_ids ) ];
		}

		$number_of_categories = $this->faker->numberBetween( 1, 2 );
		for ( $i = 0; $i < $number_of_categories; $i++ ) {
			$params['categories'][] = [ 'id' => $this->faker->randomElement( $category_ids ) ];
		}

		$number_of_brands = $this->faker->numberBetween( 1, 2 );
		$params['brands'] = $this->faker->randomElements( $brand_ids, $number_of_brands );

		$request = new \WP_REST_Request( 'POST' );
		$request->set_body_params( $params );
		$response = $controller->create_item( $request );

		if ( \is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		return $response->data['id'];
	}
}
