<?php
/*
Plugin Name: Polylang - Translate URL Rewrite Slugs
Plugin URI: https://github.com/KLicheR/wp-polylang-translate-rewrite-slugs
Description: Help translate post types rewrite slugs.
Version: 0.0.1
Author: KLicheR
Author URI: https://github.com/KLicheR
License: GPLv2 or later
*/

/*  Copyright 2013  Kristoffer Laurin-Racicot  (email : kristoffer.lr@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('PLL_TRS_DIR', dirname(__FILE__));
define('PLL_TRS_INC', PLL_TRS_DIR . '/include');

/**
 * Translate rewrite slugs for post types by doing 3 things:
 * - Translate the rewrite rules for these post types;
 * - Stop Polylang from translating rewrite rules for these post types;
 * - Fix "get_permalink" for these post types.
 *
 * To translate a post type rewrite slug, add the filter "pll_translated_post_type_rewrite_slugs"
 * to your functions.php file or your plugin to add infos about translated slugs.
 *
 * Example:
 *  add_filter('pll_translated_post_type_rewrite_slugs', function($post_type_translated_slugs) {
 *  	// Add translation for "my_post_type".
 *  	$post_type_translated_slugs['my_post_type'] = array(	
 *  		'en' => 'my-english/rewrite-slug',
 *  		'fr' => 'my-french/rewrite-slug',
 *  	);
 *  	return $post_type_translated_slugs;
 *  });
 */
class Polylang_Translate_Rewrite_Slugs {
	// Array of post types handle by "Polylang - Translate URL Rewrite Slugs"
	public $post_types;

	/**
	 * Contructor.
	 */
	public function __construct() {
		// Initiate the array that will contain the "PLL_TRS_Post_Type" object.
		$this->post_types = array();

		add_action('init', array($this, 'init_action'), 20);
	}

	/**
	 * Trigger on "init" action.
	 */
	public function init_action() {
		// Post type to handle.
		require_once(PLL_TRS_INC . '/post-type.php');
		$post_type_translated_slugs = apply_filters('pll_translated_post_type_rewrite_slugs', array());
		// For each post type...
		foreach ($post_type_translated_slugs as $post_type => $translated_slugs) {
			$this->add_post_type($post_type, $translated_slugs);
		}
		// Stop Polylang from translating rewrite rules for these post types.
		add_filter('pll_rewrite_rules', array($this, 'pll_rewrite_rules_filter'));
		// Fix "get_permalink" for these post types.
		add_filter('post_type_link', array($this, 'post_type_link_filter'), 10, 4);
	}

	/**
	 * Create a "PLL_TRS_Post_Type" and add it to the handled post type list.
	 */
	public function add_post_type($post_type, $translated_slugs) {
		$post_type_object = get_post_type_object($post_type);
		if (!is_null($post_type_object)) {
			$this->post_types[$post_type] = new PLL_TRS_Post_Type($post_type_object, $translated_slugs);
		}
	}

	/**
	 * Stop Polylang from translating rewrite rules for these post types.
	 */
	public function pll_rewrite_rules_filter($rules) {
		// We don't want Polylang to take care of these rewrite rules groups.
		foreach (array_keys($this->post_types) as $post_type) {
			$rule_key = array_search($post_type, $rules);
			if ($rule_key) {
				unset($rules[$rule_key]);
			}
		}

		return $rules;
	}

	/**
	 * Fix "get_permalink" for this post type.
	 */
	public function post_type_link_filter($post_link, $post, $leavename, $sample) {
		$lang = pll_current_language();
		
		// Check if the post type is handle.
		foreach ($this->post_types as $post_type => $pll_trs_post_type) {
			if ($post->post_type == $post_type) {
				// Build URL. Lang prefix is already handle.
				return site_url('/'.$pll_trs_post_type->translated_slugs[$lang].'/'.$post->post_name);
			}
		}

		return $post_link;
	}
}
new Polylang_Translate_Rewrite_Slugs();