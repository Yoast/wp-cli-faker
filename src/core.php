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
     * [--posts=<posts>]
     * : The number of posts to generate.
     * ---
     * default: 100
     * ---
     * 
     * [--pages=<pages>]
     * : The number of pages to generate.
     * ---
     * default: 25
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
            $category_ids[] = \wp_insert_term( $faker->unique()->catchPhrase, 'category', [
                'description' => $faker->paragraph,
                'parent'      => ( $faker->boolean && count( $category_ids ) > 0 ) ? $faker->randomElement( $category_ids ) : null,
            ] );
        }

        $tag_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['tags']; $i++ ) {
            $tag_ids[] = \wp_insert_term( $faker->unique()->catchPhrase, 'tag', [ 'description' => $faker->paragraph ] );
        }

        $post_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['posts']; $i++ ) {
            $date = $faker->dateTimeThisCentury;
            $post_ids[] = \wp_insert_post( [
                'post_author'   => $faker->randomElement( $author_ids ),
                'post_date'     => $date->format( 'Y-m-d H:i:s' ),
                'post_content'  => $faker->paragraphs( $faker->numberBetween( 15, 25 ), true ),
                'post_title'    => $faker->catchPhrase,
                'post_status'   => 'publish',
                'post_modified' => ( $faker->boolean ? $faker->dateTimeBetween( $date ) : $date )->format( 'Y-m-d H:i:s' ),
                'post_category' => $faker->randomElements( $category_ids, $faker->numberBetween( 1, 2 ) ),
                'tags_input'    => $faker->randomElements( $tag_ids, $faker->numberBetween( 0, 4 ) ),
            ] );
        }

        $page_ids = [];
        for ( $i = 0; $i < (int) $assoc_args['posts']; $i++ ) {
            $date = $faker->dateTimeThisCentury;
            $page_ids[] = \wp_insert_post( [
                'post_author'   => $faker->randomElement( $author_ids ),
                'post_date'     => $date->format( 'Y-m-d H:i:s' ),
                'post_content'  => $faker->paragraphs( $faker->numberBetween( 15, 25 ), true ),
                'post_title'    => $faker->unique()->catchPhrase,
                'post_type'     => 'page',
                'post_status'   => 'publish',
                'post_parent'   => ( $faker->boolean( 25 ) && count( $page_ids ) > 0 ) ? $faker->randomElement( $page_ids ) : null,
                'post_modified' => ( $faker->boolean ? $faker->dateTimeBetween( $date ) : $date )->format( 'Y-m-d H:i:s' ),
                'post_category' => $faker->randomElements( $category_ids, $faker->numberBetween( 1, 2 ) ),
                'tags_input'    => $faker->randomElements( $tag_ids, $faker->numberBetween( 0, 4 ) ),
            ] );
        }
    }
}

\WP_CLI::add_command( 'faker core', Core::class );
