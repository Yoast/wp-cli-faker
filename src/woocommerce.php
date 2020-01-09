<?php

namespace Yoast\WP\Tools;

use Faker\Factory;
use Faker\Generator;
use Perfect_Woocommerce_Brands\PWB_API_Support;

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
     */
    public function products( $args, $assoc_args ) {
        $faker = Factory::create();

        $attachment_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['attachments']; $i++ ) {
            $file_array         = [];
            $file_array['name'] = "{$assoc_args['attachment-keyword']}$i.jpg";

            // Download file to temp location.
            $file_array['tmp_name'] = download_url( "https://loremflickr.com/640/480/{$assoc_args['attachment-keyword']}" );
            if ( is_wp_error( $file_array['tmp_name'] ) ) {
                continue;
            }

            // Do the validation and storage stuff.
            $attachment_ids[] = media_handle_sideload( $file_array, 0, null );
        }

        $category_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['categories']; $i++ ) {
            $category_id = $this->generate_category( $faker, $attachment_ids );
            if ( $category_id ) {
                $category_ids[] = $category_id;
            }
        }

        $brand_ids = [];
        if ( \taxonomy_exists( 'pwb-brand') ) {
            for ( $i = 0; $i < (int) $assoc_args['brands']; $i++ ) {
                $brand_id = $this->generate_brand( $faker, $attachment_ids );
                if ( $brand_id ) {
                    $brand_ids[] = $brand_id;
                }
            }
        }

        $product_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['products']; $i++ ) {
            $product_id = $this->generate_product( $faker, $attachment_ids, $category_ids, $brand_ids );
            if ( $product_id ) {
                $product_ids[] = $product_id;
            }
        }

        add_filter( 'wp_is_comment_flood', '__return_false', PHP_INT_MAX );
        add_filter( 'pre_comment_approved', '__return_true' );
        foreach ( $product_ids as $product_id ) {
            $number_of_reviews = $faker->numberBetween( $assoc_args['min-reviews'], $assoc_args['max-reviews'] );
            for ( $i = 0; $i < $number_of_reviews; $i++ ) {
                $this->generate_review( $faker, $product_id );
            }
        }
        remove_filter( 'wp_is_comment_flood', '__return_false', PHP_INT_MAX );
        remove_filter( 'pre_comment_approved', '__return_true' );
    }

    private function generate_post_content( $faker, $attachment_ids ) {
        $blocks      = [];
        $block_count = $faker->numberBetween( 8, 12 );

        for ( $i = 0; $i < $block_count; $i++ ) {
            if ( $faker->boolean( 90 ) ) {
                $blocks[] = "<!-- wp:paragraph -->\n<p>" . $faker->paragraph . "</p>\n<!-- /wp:paragraph -->";
                continue;
            }

            $attachment_id  = $faker->randomElement( $attachment_ids );
            $attachment_url = wp_get_attachment_url( $attachment_id );
            $blocks[] = "<!-- wp:image {\"id\":$attachment_id,\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"$attachment_url\" alt=\"\" class=\"wp-image-$attachment_id\"/></figure>\n<!-- /wp:image -->";
        }

        return implode( "\n", $blocks );
    }

    private function generate_brand( Generator $faker, $attachment_ids ) {
        $term = \wp_insert_term( $faker->unique()->catchPhrase, 'pwb-brand', [ 'description' => $faker->paragraph ] );

        if ( \is_wp_error( $term ) ) {
            \WP_CLI::warning( "Couln't create brand: " . $$term->get_error_message() );
            return false;
        }

        \update_term_meta( $term['term_id'], 'pwb_brand_image', $faker->randomElement( $attachment_ids ) );

        return $term['term_id'];
    }

    private function generate_review( Generator $faker, $product_id ) {
        $controller = new \WC_REST_Product_Reviews_Controller();

        $params = [
            'product_id' => $product_id,
            'review' => $faker->paragraph,
            'reviewer' => $faker->name,
            'reviewer_email' => $faker->email,
            'rating' => $faker->numberBetween( 0, 5 ),
            'verified' => $faker->boolean
        ];
        $request = new \WP_REST_Request( 'POST' );
        $request->set_body_params( $params );

        $response = $controller->create_item( $request );

        if ( \is_wp_error( $response ) ) {
            \WP_CLI::warning( "Couln't create review: " . $response->get_error_message() );
            return false;
        }

        return $response->data['id'];
    }

    private function generate_category( Generator $faker, $attachment_ids ) {
        $controller = new \WC_REST_Product_Categories_Controller();

        $params = [
            'name' => $faker->unique()->catchPhrase,
            'description' => $this->generate_post_content( $faker, $attachment_ids ),
            'image' => [ 'id' => $faker->randomElement( $attachment_ids ) ],
        ];
        $request = new \WP_REST_Request( 'POST' );
        $request->set_body_params( $params );

        $response = $controller->create_item( $request );

        if ( \is_wp_error( $response ) ) {
            \WP_CLI::warning( "Couln't create category: " . $response->get_error_message() );
            return false;
        }

        return $response->data['id'];
    }

    private function generate_product( Generator $faker, $attachment_ids, $category_ids, $brand_ids ) {
        $controller = new \WC_REST_Products_Controller();

        $params = [
            'name' => $faker->unique()->catchPhrase,
            'description' => $this->generate_post_content( $faker, $attachment_ids ),
            'status' =>  'publish',
            'type' => 'Simple',
            'featured' => $faker->boolean( 10 ),
            'sku' => $faker->numerify( '######' ),
            'regular_price' => $faker->numberBetween( 10, 100 ),
            'images' => [],
            'categories' => [],
        ];

        $number_of_images = $faker->numberBetween( 1, 3 );
        for ( $i = 0; $i < $number_of_images; $i++ ) {
            $params['images'][] = [ 'id' => $faker->randomElement( $attachment_ids ) ];
        }

        $number_of_categories = $faker->numberBetween( 1, 2 );
        for ( $i = 0; $i < $number_of_categories; $i++ ) {
            $params['categories'][] = [ 'id' => $faker->randomElement( $category_ids ) ];
        }

        $number_of_brands = $faker->numberBetween( 1, 2 );
        $params['brands'] = $faker->randomElements( $brand_ids, $number_of_brands );

        $request = new \WP_REST_Request( 'POST' );
        $request->set_body_params( $params );
        $response = $controller->create_item( $request );

        if ( \is_wp_error( $response ) ) {
            \WP_CLI::warning( "Couln't create product: " . $response->get_error_message() );
            return false;
        }

        return $response->data['id'];
    }
}

\WP_CLI::add_command( 'faker woocommerce', WooCommerce::class );
