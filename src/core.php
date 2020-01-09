<?php

namespace Yoast\WP\Tools;

use Faker\Factory;

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
     * default: wordpress
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
     * ## EXAMPLES
     *
     *     wp faker core content
     *
     * @when after_wp_load
     */
    public function content( $args, $assoc_args ) {
        $faker = Factory::create();

        $author_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['authors']; $i++ ) {
            $author_ids[] = \wp_insert_user( [
                'user_login'      => $faker->unique()->userName,
                'user_pass'       => $faker->password,
                'user_url'        => $faker->url,
                'user_email'      => $faker->email,
                'first_name'      => $faker->firstName,
                'last_name'       => $faker->lastName,
                'description'     => $faker->paragraph,
                'user_registered' => $faker->dateTimeThisCentury->format( 'Y-m-d H:i:s' ),
                'role'            => 'author',
            ] );
        }

        $category_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['categories']; $i++ ) {
            $category = \wp_insert_term( $faker->unique()->catchPhrase, 'category', [
                'description' => $faker->paragraph,
                'parent'      => ( $faker->boolean && count( $category_ids ) > 0 ) ? $faker->randomElement( $category_ids ) : null,
            ] );

            if ( \is_wp_error( $category ) ) {
                \WP_CLI::warning( "Couln't create category: " . $category->get_error_message() );
                continue;
            }

            $category_ids[] = $category['term_id'];
        }

        $tag_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['tags']; $i++ ) {
            $tag = \wp_insert_term( $faker->unique()->catchPhrase, 'post_tag', [ 'description' => $faker->paragraph ] );

            if ( \is_wp_error( $tag ) ) {
                \WP_CLI::warning( "Couln't create tag: " . $tag->get_error_message() );
                continue;
            }

            $tag_ids[] = $tag['term_id'];
        }

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

        for ( $i = 0; $i < (int) $assoc_args['posts']; $i++ ) {
            $date = $faker->dateTimeThisYear;
            $post_id = \wp_insert_post( [
                'post_author'   => $faker->randomElement( $author_ids ),
                'post_date'     => $date->format( 'Y-m-d H:i:s' ),
                'post_content'  => $this->generate_post_content( $faker, $attachment_ids ),
                'post_title'    => $faker->catchPhrase,
                'post_status'   => 'publish',
                'post_modified' => ( $faker->boolean ? $faker->dateTimeBetween( $date ) : $date )->format( 'Y-m-d H:i:s' ),
                'post_category' => $faker->randomElements( $category_ids, $faker->numberBetween( 1, 2 ) ),
                'tags_input'    => $faker->randomElements( $tag_ids, $faker->numberBetween( 0, 4 ) ),
            ] );
            \set_post_thumbnail( $post_id, $faker->randomElement( $attachment_ids ) );
        }

        $page_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['pages']; $i++ ) {
            $date = $faker->dateTimeThisYear;
            $page_id = \wp_insert_post( [
                'post_author'   => $faker->randomElement( $author_ids ),
                'post_date'     => $date->format( 'Y-m-d H:i:s' ),
                'post_content'  => $this->generate_post_content( $faker, $attachment_ids ),
                'post_title'    => $faker->unique()->catchPhrase,
                'post_type'     => 'page',
                'post_status'   => 'publish',
                'post_parent'   => ( $faker->boolean( 25 ) && count( $page_ids ) > 0 ) ? $faker->randomElement( $page_ids ) : null,
                'post_modified' => ( $faker->boolean ? $faker->dateTimeBetween( $date ) : $date )->format( 'Y-m-d H:i:s' ),
                'post_category' => $faker->randomElements( $category_ids, $faker->numberBetween( 1, 2 ) ),
                'tags_input'    => $faker->randomElements( $tag_ids, $faker->numberBetween( 0, 4 ) ),
            ] );
            \set_post_thumbnail( $page_id, $faker->randomElement( $attachment_ids ) );
            $page_ids[] = $page_id;
        }
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
}

\WP_CLI::add_command( 'faker core', Core::class );
