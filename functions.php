<?php

/**
 * Utility functions for interacting with Neuwo API and Wordpress database.
 */

/**
 * Return hyperlink to Neuwo settings page which can be used in admin notices.
 * @return string
 */
function neuwo_configure_now_link() {
	return ' <a href="/wp-admin/options-general.php?page=' . $GLOBALS['NEUWO_SETTINGS_SLUG'] .
		'">Configure Now</a>';
}

function neuwo_on_settings_page() {
	if (strpos($_SERVER['REQUEST_URI'], 'neuwo_plugin') !== false) {
		return true;
	} else {
		return false;
	}
}

/**
 * Returns true if Neuwo is enabled and API key is configured in settings,
 * otherwise returns false.
 * Sets admin notices if not enabled or configuration is not complete.
 * @return bool
 */
function neuwo_is_enabled() {
	$options = get_option('neuwo_plugin_options', $default = false);

	if (!$options) {
		if (!neuwo_on_settings_page()) {
			// Add configuration notice (when not on Neuwo settings page)
			add_action('admin_notices', function () {
				$class = 'notice notice-warning';
				$message = __('Neuwo is installed but not configured.', 'neuwo');
				printf(
					'<div class="%1$s"><p>%2$s ' . neuwo_configure_now_link() . '</p></div>',
					esc_attr($class),
					esc_html($message)
				);
			});
		}
		return false;
	}
	if (!array_key_exists('enabled', $options) || $options['enabled'] != 'enabled') {
		if (!neuwo_on_settings_page()) {
			add_action('admin_notices', function () {
				$class = 'notice notice-warning';
				$message = __(
					'Neuwo is installed but it is not enabled. ' .
						'Please visit settings panel at Settings > Neuwo.',
					'neuwo'
				);
				printf(
					'<div class="%1$s"><p>%2$s ' . neuwo_configure_now_link() . '</p></div>',
					esc_attr($class),
					esc_html($message)
				);
			});
		}
		return false;
	}
	if (!array_key_exists('apikey', $options)) {
		// Add notification to admin that API key is missing
		if (!neuwo_on_settings_page()) {
			add_action('admin_notices', function () {
				$class = 'notice notice-error';
				$message = __(
					'Neuwo API key is missing. ' .
						'Please add it in plugin settings at Settings > Neuwo.',
					'neuwo'
				);
				printf(
					'<div class="%1$s"><p>%2$s ' . neuwo_configure_now_link() . '</p></div>',
					esc_attr($class),
					esc_html($message)
				);
			});
		}
		return false;
	}
	// check if array is empty $options['neuwo_post_types'])

	if (!array_key_exists('neuwo_post_types', $options) || !$options['neuwo_post_types']) {
		// Add notification to admin that Neuwo post types are not set
		if (!neuwo_on_settings_page()) {
			add_action('admin_notices', function () {
				$class = 'notice notice-warning';
				$message = __(
					'Neuwo is activated for any post types. ' .
						'Please visit plugin settings at Settings > Neuwo.',
					'neuwo'
				);
				printf(
					'<div class="%1$s"><p>%2$s ' . neuwo_configure_now_link() . '</p></div>',
					esc_attr($class),
					esc_html($message)
				);
			});
		}
		return false;
	}
	return true;
}

/**
 * Get Neuwo settings option key from settings or false if not found.
 * @param string $key
 * @return mixed
 */
function neuwo_get_option_value($key) {
	$neuwo_options = get_option('neuwo_plugin_options', $default = false);
	if ($neuwo_options && array_key_exists($key, $neuwo_options)) {
		return $neuwo_options[$key];
	} else {
		return False;
	}
}

/**
 * Reads and validates existing Neuwo settings from database.
 * Clears them if they are in illegal format notifying user.
 * @return bool
 */
function neuwo_validate_settings_or_clear() {
	$neuwo_options = get_option('neuwo_plugin_options', $default = false);
	if ($neuwo_options) {
		// Check if API key is in correct format
		$test_ok = True;

		/* Validate each option value that has been set */

		if (
			isset($neuwo_options['apikey']) &&
			!neuwo_validator_api_key_option($neuwo_options['apikey'])
		) {
			$test_ok = False;
		}

		if (
			isset($neuwo_options['post_types']) &&
			!neuwo_validator_post_types_option($neuwo_options['post_types'])
		) {
			$test_ok = False;
		}

		if (
			isset($neuwo_options['enabled']) &&
			!neuwo_validator_enabled_option($neuwo_options['enabled'])
		) {
			$test_ok = False;
		}

		if (
			isset($neuwo_options['publication_id']) &&
			!neuwo_validator_publication_id_option($neuwo_options['publication_id'])
		) {
			$test_ok = False;
		}

		if (
			isset($neuwo_options['custom_tags_enabled']) &&
			!neuwo_validator_custom_tags_enabled_option($neuwo_options['custom_tags_enabled'])
		) {
			$test_ok = False;
		}

		if (
			isset($neuwo_options['site_custom_tags']) &&
			!neuwo_validator_custom_tags_data_option($neuwo_options['site_custom_tags'])
		) {
			$test_ok = False;
		}


		$allowed_settings = [
			'apikey', 'post_types', 'enabled', 'publication_id',
			'custom_tags_enabled', 'site_custom_tags'
		];


		/* Validate that there are no extra option values */

		// Check that there are no extra options that are not listed in $allowed_settings
		foreach ($neuwo_options as $key => $value) {
			if (!in_array($key, $allowed_settings)) {
				$test_ok = False;
			}
		}

		if ($test_ok) {
			return True;
		} else {
			// Clear settings if they are in illegal format
			delete_option('neuwo_plugin_options');
			set_transient('neuwo_healthcheck_fail_notice_transient', true, 5 * MINUTE_IN_SECONDS);
			return False;
		}
	}
}


/*
 * Helper function supporting both Gutenberg and metabox,
 * checked before calling Neuwo API endpoints.
 */
function neuwo_is_included_in_sim($post_id) {
	if (defined('REST_REQUEST')) {  // Gutenberg request
		// Set $_POST['exclude_from_similarity'] according to current postmeta state
		// during REST call on publish/update
		if (get_post_meta($post_id, 'neuwo_exclude_from_similarity', true)) {
			return false;
		} else {
			return true;
		}
	} else {  // Metabox request
		return isset($_POST['exclude_from_similarity']) ? False : True;
	}
}

/**
 * Get server domain name stripping protocol
 * @return string
 */
function neuwo_get_server_name() {
	$server_url_safe = esc_url($_SERVER['SERVER_NAME']);
	$server_url_parts = explode('://', $server_url_safe);
	$server_name = end($server_url_parts);
	return $server_name;
}

/**
 * POST article data to /GetAiTopics
 * Hooked to save_post action
 * TODO document better
 * Metabox specific logic uses $_POST global variable missing during REST call (?)
 * requires setting it manually for using via REST.
 */
function neuwo_get_ai_topics($post_id) {
	$post = get_post($post_id);

	// Handle editor metabox data saving for exclude_from_similarity and
	// neuwo_custom_tags_metabox_values

	if (!defined('REST_REQUEST')) {
		// Skipped during REST call by Gutenberg/React where postmeta is updated
		// with other post data with Javascript/React.

		if (isset($_POST['exclude_from_similarity'])) {
			update_post_meta($post_id, 'neuwo_exclude_from_similarity', True);
		} else {
			$excluded_from_similarity = get_post_meta($post_id, 'neuwo_exclude_from_similarity', true);
			if ($excluded_from_similarity) {
				// Unset if previously set
				update_post_meta($post_id, 'neuwo_exclude_from_similarity', False);
			}
		}
	}

	// Check that custom tags feature is enabled
	if (neuwo_get_option_value('custom_tags_enabled') ){

		// Check if custom tags are set in metabox form
		if (isset($_POST['neuwo_custom_tags_metabox_values'])){
			$custom_tags_raw = $_POST['neuwo_custom_tags_metabox_values'];

			// Convert checkbox input fields to integer (sanitize)
			$custom_tags_safe = array_map('intval', $custom_tags_raw);
			// Save custom tags to postmeta
			update_post_meta($post_id, 'neuwo_custom_tags', $custom_tags_safe);
		}
	}


	// Do not run by default during save_post if post is not published eg. when:
	// - creating a new post which is created as auto-draft in beginning of editing
	// - auto saves while editing
	// - saving a draft with "Save Draft" button
	if ($post->post_status != 'publish') {
		// Except if hidden checkbox is checked by pressing "Get Keywords" button
		// (should be faked during REST by setting the variable in advance)
		if (!isset($_POST['neuwo_should_update'])) {
			return;
		}
	}

	$content = $post->post_content;
	$content = wp_strip_all_tags($content);

	// $publication_id = neuwo_get_option_value('publication_id');

	$neuwo_documentid = neuwo_get_server_name() . '_' . $post_id;

	if ($content == '') {
		return new WP_Error(
			'post_content_is_empty',
			'Post content is empty',
			[$post->ID, $content, $post->post_content]
		);
	}

	$include_in_sim = neuwo_is_included_in_sim($post_id);

	$api_token = neuwo_get_option_value('apikey');

	$api_url = 'https://m1api.neuwo.ai/GetAiTopics?token=' . $api_token;
	$args = [
		'method' => 'POST',
		'headers' => [
			'accept' => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded'
		],
		'timeout' => 60,
		'body' => [
			'documentid' => $neuwo_documentid,
			'content' => $content,
			'include_in_sim' => $include_in_sim,
			// 'lang' => 'fi',
			'format' => 'json'
		]
	];

	$request = wp_remote_post($api_url, $args);

	if (is_wp_error($request)) {
		return $request;
	}

	// if 403 forbidden .. don't set postmeta
	if ($request['response']['code'] != 200) {
		return $request;
	}

	$body = wp_remote_retrieve_body($request);
	$data = json_decode($body);

	update_post_meta($post_id, 'neuwo_document_id', $neuwo_documentid);
	update_post_meta($post_id, 'neuwo_data', $data);
}


/*
 * Sends published article information to /UpdateArticle including publication date,
 * articleURL, headline and other data not available on draft posts.
 *
 * Runs on post publish or update on published post via publish_post -hook.
 */
function neuwo_update_post($post_id) {

	// If new post is published straight away, neuwo_get_ai_topics needs to be run first for setting
	// documentid / articleID on Neuwo API before calling /UpdateArticle.
	neuwo_get_ai_topics($post_id);

	$post = get_post($post_id);

	// Unhook it from running again after 'publish_post' hook
	// TODO unhook by current post type
	remove_action('save_post_' . $post->post_type, 'neuwo_get_ai_topics');

	$content = $post->post_content;
	$content = wp_strip_all_tags($content);


	$publication_id = neuwo_get_option_value('publication_id');

	$neuwo_documentid = neuwo_get_server_name() . '_' . $post_id;

	if ($content == '') {
		return false;
	}

	$published_date = get_the_date('Y-m-d', $post_id);

	$articleURL = get_permalink($post->ID) ?: '';

	$include_in_sim = neuwo_is_included_in_sim($post_id);

	$api_key = neuwo_get_option_value('apikey');
	// check and return
	$api_url = 'https://m1api.neuwo.ai/UpdateArticle/' . $neuwo_documentid . '?token=' . $api_key;

	$body = [
		'published' => $published_date,
		'include_in_sim' => $include_in_sim,
		'headline' => $post->post_title,
		'writer' => $post->post_author,
		'content' => $content,
		'publicationid' => $publication_id,
		'articleURL' => $articleURL,
		'format' => 'json'
	];
	if ($publication_id) {
		$body['publicationid'] = $publication_id;
	}


	$args = [
		'method' => 'PUT',
		'headers' => [
			'accept' => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded'
		],
		'body' => $body,
	];

	if ($post->post_excerpt != '') {
		$args['body']['summary'] = $post->post_excerpt;
	}

	$cats = get_the_category($post_id);
	$category = reset($cats);
	if ($category && $category->name != 'Uncategorized') {
		$args['body']['category'] = $category->name;
	}

	$image_url = get_the_post_thumbnail_url($post->ID, 'full');
	if ($image_url != false) {
		$args['body']['imageURL'] = $image_url;
	}

	$request = wp_remote_request($api_url, $args);

	if (is_wp_error($request)) {
		return false;
	}

	// if 403 forbidden when trial runs out .. don't set postmeta
	if ($request['response']['code'] != 200) {
		return;
	}

	$body = wp_remote_retrieve_body($request);
	// $data = json_decode($body);


	// MAYBE Set admin notice if post Neuwo does not return publicationid
	// if ($data->publicationid == ''){
	// add_action( 'admin_notices', 'neuwo_publisherid_missing_admin_notice' );
	// echo '<p class="neuwo-warning">
	//   Neuwo API did not return correct publisherid, verify that it is correct.
	// </p>';
	// }


	// Custom tags are posted to Neuwo API (if feature is enabled)
	if (neuwo_get_option_value('custom_tags_enabled') ){

		// API request example:
		// curl -X 'POST' \
		//   'https://m1api.neuwo.ai/TrainAiTopics?token=token_value' \
		//   -H 'accept: application/json' \
		//   -H 'Content-Type: application/x-www-form-urlencoded' \
		//   -d documentid=123' \
		//   -d 'tags=tag_val_1,tag_val_2,tag_val_3' \
		//   -d 'format=json'

		$custom_tags = get_post_meta($post_id, 'neuwo_custom_tags', true);

		if (isset($custom_tags) && is_array($custom_tags) && !empty($custom_tags)){

			$custom_tags_str = implode(',', $custom_tags);

			$api_url_custom_tags = 'https://m1api.neuwo.ai/TrainAiTopics?token=' . $api_key;

			$body_arr = [
				'documentid' => $neuwo_documentid,
				'format' => 'json',
				'tags' => $custom_tags_str
			];

			$args_custom_tags = [
				'method' => 'POST',
				'headers' => [
					'accept' => 'application/json',
					'Content-Type' => 'application/x-www-form-urlencoded'
				],
				'timeout' => 30,
				'body' => $body_arr
			];

			$request = wp_remote_post($api_url_custom_tags, $args_custom_tags);
			if (is_wp_error($request)) {
				return $request;
			}

			// if 403 forbidden .. don't set postmeta
			if ($request['response']['code'] != 200) {
				return $request;
			}

			$body = wp_remote_retrieve_body($request);
			//$data = json_decode($body);
		}
	}
}
