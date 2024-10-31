<?php
/*
 * Initialize Gutenberg specific post editor controls and REST API routes
 */


function neuwo_gutenberg_enqueue_assets() {

	// Validate Neuwo is on and enabled for current post type

	$neuwo_options = get_option('neuwo_plugin_options', null);
	$neuwo_enabled = $neuwo_options['enabled'] ?? '';
	if ($neuwo_enabled != 'enabled') {
		return;
	}

	if (array_key_exists('neuwo_post_types', $neuwo_options)) {
		$neuwo_post_types = $neuwo_options['neuwo_post_types'];
	} else {
		return;
	}

	$screen = get_current_screen();
	$current_post_type = $screen->post_type;

	if (in_array($current_post_type, $neuwo_post_types) != true) {
		return;
	}

	// TODO Initialise Block editor sidebar Neuwo controls

	$asset_file = include(plugin_dir_path(__FILE__) . 'build/index.asset.php');
	wp_enqueue_script(
		'neuwo-gutenberg-script',
		plugins_url('build/index.js', __FILE__),
		$asset_file['dependencies'],
		$asset_file['version']
	);

	wp_enqueue_style(
		'neuwo-gutenberg-style',
		plugins_url('build/index.css', __FILE__)
	);
}
add_action('enqueue_block_editor_assets', 'neuwo_gutenberg_enqueue_assets');

// REST API Handlers

add_action('rest_api_init', function () {

	// Fetch new data for post from Neuwo API
	// http://neuwo.local/wp-json/neuwo/v1/getAiTopics?postId=5
	register_rest_route('neuwo/v1', '/getAiTopics', [
		'methods' => 'GET',
		'callback' => function ($request) {
			$post_id = $request->get_param('postId');

			if (!is_numeric($post_id)) {
				return wp_send_json_error("Invalid postId parameter", 400);
			}

			// Set $_POST variable for REST request for reusing metabox logic
			$_POST = ['neuwo_should_update' => true];

			neuwo_get_ai_topics($post_id);
			$neuwo_data = get_post_meta($post_id, 'neuwo_data', true);
			return wp_send_json($neuwo_data);
		},
		'permission_callback' => function ($request) {
			$post_id = $request->get_param('postId');
			return current_user_can('edit_post', $post_id);
		},
	]);

	// Get existing neuwo_data postmeta for current post, workaround as serialized object is not accessible via
	// wp.data.select('core/editor').getEditedPostAttribute('meta')
	// http://neuwo.local/wp-json/neuwo/v1/getData?postId=5
	register_rest_route(
		'neuwo/v1',
		'/getData',
		[
			'methods' => 'GET',
			'callback' => function ($request) {
				$post_id = $request->get_param('postId');

				if (!is_numeric($post_id)) {
					return wp_send_json_error("Invalid postId parameter", 400);
				}

				$post_meta = get_post_meta($post_id, 'neuwo_data', true)
					?: (object)['tags' => [], 'brand_safety' => [], 'marketing_categories' => []];

				$post_tags = get_the_tags($post_id) ?: [];
				$post_tagids = [];
				foreach ($post_tags as $pt) {
					array_push($post_tagids, $pt->term_id);
				}

				$neuwo_tags =  $post_meta->tags;


				# Append possible WP Tag ID to Neuwo keyword and info if it's added to current post

				$all_wp_tags = get_terms('post_tag', ['hide_empty' => false]);

				# tag name -> tag id
				$all_wp_tagids_by_name = [];

				foreach ($all_wp_tags as $pt) {
					$name_htmldecoded = html_entity_decode($pt->name);  // Non-case-sensitive matching as tags rest api
					$name_htmldecoded_lowercase = strtolower($name_htmldecoded);  // Handle &amp; and others made by tags rest api
					$all_wp_tagids_by_name[$name_htmldecoded_lowercase] = $pt->term_id;
				}

				foreach ($neuwo_tags as $key => $tag) {
					if (array_key_exists(strtolower($tag->value), $all_wp_tagids_by_name)) {
						$wp_tag_id = $all_wp_tagids_by_name[strtolower($tag->value)];
						$neuwo_tags[$key]->WPTagId = $wp_tag_id;

						if (in_array($wp_tag_id, $post_tagids)) {
							$neuwo_tags[$key]->addedToPost = True;
						}
					}
				}


				$response = [
					'tags' => $neuwo_tags,
					'brand_safety' => $post_meta->brand_safety,
					'marketing_categories' => $post_meta->marketing_categories,
					'allWPTags' => $all_wp_tags,
					'postTagIds' => $post_tagids,
				];

				return wp_send_json($response);
			},
			'permission_callback' => function ($request) {
				$post_id = $request->get_param('postId');
				return current_user_can('edit_post_meta', $post_id, 'neuwo_data');
			},
		]
	);

	// Set neuwo_exclude_from_similarity postmeta for current post, workaround API route as
	// postmeta has not necessarily been set before updatePost request to Neuwo API.
	// http://neuwo.local/wp-json/neuwo/v1/excludeFromSimilarity
	// payload: postId=int exclude=true|false
	register_rest_route(
		'neuwo/v1',
		'/excludeFromSimilarity',
		[
			'methods' => 'POST',
			'callback' => function ($request) {
				$post_id = $request->get_param('postId');
				$exclude_raw = $request->get_param('exclude');

				if (!in_array($exclude_raw, ['true', 'false']) || !is_numeric($post_id)) {
					return wp_send_json_error("Invalid parameters", 400);
				}

				switch ($exclude_raw) {
					case 'true':
						$exclude = TRUE;
						break;
					case 'false':
						$exclude = FALSE;
						break;
				}

				$post_meta = update_post_meta($post_id, 'neuwo_exclude_from_similarity', $exclude);

				return wp_send_json($exclude);
			},
			'permission_callback' => function ($request) {
				$post_id = $request->get_param('postId');
				return current_user_can('edit_post_meta', $post_id, 'neuwo_exclude_from_similarity');
			},  // accessed on clientside via wpApiSettings.nonce
		]
	);
});
