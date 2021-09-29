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

		$replace_vars = \array_merge(
			self::REPLACE_VARS,
			$this->get_custom_field_replace_vars( $post_id )
		);

		$aioseo_fields = [
			// "id" => 0, // Auto increment
			"post_id"             => $post_id,
			"title"               => $this->generate_aioseo_replace_vars( $replace_vars ),
			"description"         => $this->generate_aioseo_replace_vars( $replace_vars ),
			"og_title"            => $this->generate_aioseo_replace_vars( $replace_vars ),
			"og_description"      => $this->generate_aioseo_replace_vars( $replace_vars ),
			"twitter_title"       => $this->generate_aioseo_replace_vars( $replace_vars ),
			"twitter_description" => $this->generate_aioseo_replace_vars( $replace_vars ),
			"keywords"            => null, // We don't offer keywords functionality.
			/**
			 * {
			 *    "focus":{
			 *       "keyphrase":"Test",
			 *       "score": 0, // not important
			 *       "analysis": "" // not important
			 *    },
			 *    "additional":[
			 *       {
			 *           "keyphrase": "Additional",
			 *           "score": 0 // not important
			 *           "analysis": "" // not important
			 *       }
			 *    ]
			 * }
			 */
			"keyphrases"          => null, // JSON, see above.
			"page_analysis"       => null, // JSON, Not important
			"canonical_url"       => null, // string
			/**
			 * See link for object types.
			 *
			 * https://github.com/awesomemotive/all-in-one-seo-pack/blob/4a4a5224a2ce3c87d2f6c154a487ea013119c551/src/vue/plugins/constants.js#L1158
			 */
			"og_object_type"      => "default", // string
			/**
			 * See link for image types.
			 *
			 * https://github.com/awesomemotive/all-in-one-seo-pack/blob/4a4a5224a2ce3c87d2f6c154a487ea013119c551/src/vue/mixins/Image.js#L53
			 *
			 * Special remarks:
			 * - "custom" / Image from Custom Field (Saved in og_image_custom_fields)
			 * - "custom_image" / Custom Image (Saved in og_image_custom_url)
			 */
			"og_image_type"       => "default", // string
			"og_image_custom_url" => null, // string, value used as og:image when "custom_image" is selected as og_image_type
			"og_image_custom_fields" => null, // string, key of the custom field to be used as og:image when og_image_type "custom" is selected
			"og_custom_image_width" => 0, // int
			"og_custom_image_height" => 0, // int
			"og_video" => null, // ?
			"og_custom_url" => null, // ?
			"og_article_section" => "", // string, Output in a "article:section" metatag.
			/**
			 * [
			 *    {
			 *       "label":"tag1",
			 *       "value":"tag1"
			 *    },
			 *    {
			 *       "label":"tag2",
			 *       "value":"tag2"
			 *    },
			 * ]
			 */
			"og_article_tags" => "", // JSON, each tag's value is output in a separate "article:tags" metatag. See above.
			"twitter_use_og" => 0, // Boolean, corresponds to the "Use Data from Facebook Tab" toggle on the twitter tab.
			/**
			 * - "default" / Default (Set under Social Networks)
			 * - "summary" / Summary
			 * - "summary_large_image" / Summary with Large Image
			 */
			"twitter_card" => "default",
			"twitter_image_type" => "default", // Same options as og_image_type, see above.
			"twitter_image_custom_url" => null, // string, value used as twitter:image when "custom_image" is selected as twitter_image_type
			"twitter_image_custom_fields" => null, // string, key of the custom field to be used as twitter:image when twitter_image_type "custom" is selected
			"schema_type" => "default", // string
			/**
			 * {
			 *    "article":{
			 *       "articleType":"BlogPosting"
			 *    },
			 *    "course":{
			 *       "name":"",
			 *       "description":"",
			 *       "provider":""
			 *    },
			 *    "faq":{
			 *       "pages":[
			 *          
			 *       ]
			 *    },
			 *    "product":{
			 *       "reviews":[
			 *          
			 *       ]
			 *    },
			 *    "recipe":{
			 *       "ingredients":[
			 *          
			 *       ],
			 *       "instructions":[
			 *          
			 *       ],
			 *       "keywords":[
			 *          
			 *       ]
			 *    },
			 *    "software":{
			 *       "reviews":[
			 *          
			 *       ],
			 *       "operatingSystems":[
			 *          
			 *       ]
			 *    },
			 *    "webPage":{
			 *       "webPageType":"WebPage"
			 *    }
			 * }
			 */
			"schema_type_options" => null, // JSON, See above.
			"pillar_content" => 0, // Boolean
			"robots_default" => 1, // Boolean, whether or not to use default robots settings.
			"robots_noindex" => 0, // Boolean
			"robots_noarchive" => 0, // Boolean
			"robots_nosnippet" => 0, // Boolean
			"robots_nofollow" => 0, // Boolean
			"robots_noimageindex" => 0, // Boolean
			"robots_noodp" => 0, // Boolean
			"robots_notranslate" => 0, // Boolean
			"robots_max_snippet" => -1, // int
			"robots_max_videopreview" => -1, // int
			"robots_max_imagepreview" => "large", // string
			"tabs" => null, // JSON
			"images" => null, // JSON, Array of images with metadata in the post.
			"priority" => "default", // string
			"image_scan_date" => null, // Date string
			"frequency" => "default", // string
			"videos" => null, // JSON, Array of videos with metadata in the post.
			"video_scan_date" => null, // Date string
			// "created" => null, // Date string, defaults to current date
			// "updated" => null, // Date string, defaults to current date
		];

		global $wpdb;

		$wpdb->query( 
			$wpdb->prepare(
				"
				INSERT INTO {$wpdb->prefix}aioseo_posts
				(" . \implode( ",", \array_keys( $aioseo_fields ) ) . ")
				VALUES
				(" . \implode( ', ', \array_fill( 0, count( $aioseo_fields ), '%s' ) ) . ")
				",
				\array_values( $aioseo_fields )
			)
		);

		foreach ( self::META_KEYS as $meta_key ) {
			$db_key = str_replace( "_aioseo_", "", $meta_key );

			add_post_meta( $post_id, $meta_key, $aioseo_fields[ $db_key ] );
		}

		return $post_id;
	}

	/**
	 * Generates a random title or description using AIOSEO replacement variables.
	 *
	 * @param array $replace_vars Array of replacevars to use.
	 *
	 * @return string The generated replacevar string.
	 */
	private function generate_aioseo_replace_vars( $replace_vars ) {
		return \implode( ' ', $this->faker->randomElements( $replace_vars, $this->faker->numberBetween( 2, 5 ) ) );
	}

	/**
	 * Creates a list of AIOSEO replacement variables for the post's custom fields.
	 *
	 * @param int $post_id The post id.
	 *
	 * @return array List of AIOSEO replacement variables for custom fields.
	 */
	private function get_custom_field_replace_vars( $post_id ) {
		$custom_field_keys = \array_filter(
			\get_post_custom_keys( $post_id ), 
			function ( $key ) {
				return strpos( $key, self::$custom_field_prefix ) === 0;
			}
		);

		return array_map( 
			function( $key ) {
				return '#custom_field-' . $key;
			},
			$custom_field_keys
		);
	}
}
