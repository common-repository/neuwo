<?php

/* Settings page related utility functions */


/**
 * Get all installed post types and return ones that would be relevant to use with Neuwo plugin.
 * Uses get_post_types and filters out stock WP post types other than post or article.
 * @return array
 */
function neuwo_get_usable_post_types() {
	$installed_post_types = get_post_types();
	$usable_post_types = [];
	$unusable_post_types = [
		'attachment', 'revision', 'nav_menu_item', 'custom_css',
		'customize_changeset', 'oembed_cache', 'user_request',
		'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles',
		'wp_navigation'
	];

	foreach ($installed_post_types as $post_type) {
		if (in_array($post_type, $unusable_post_types)) {
			continue;
		} else {
			array_push($usable_post_types, $post_type);
		}
	}
	return $usable_post_types;
}


/**
 * Request free API token from Neuwo API field which user receives via email
 * to entered address.
 * @params $email_address string
 */
function neuwo_request_api_token($email_address) {
	$neuwo_token_api_url =
		"https://fx3ymadfp9.execute-api.eu-central-1.amazonaws.com/prod/api/v1/tokens" .
		"?key=neuwo_wp_plugin&email=" . $email_address;

	$args = [
		'method' => 'POST',
		'headers' => [
			'accept' => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded'
		]
	];

	$request = wp_remote_post($neuwo_token_api_url, $args);

	$body = wp_remote_retrieve_body($request);
	$data = json_decode($body);

	if ($request['response']['code'] == 200) {
		add_settings_error(
			'neuwo_plugin_text_string',
			'neuwo_plugin_text_success',
			'Neuwo trial started for email address ' . $email_address .
			', please see your inbox for verification and API token.',

			'success'
		);
	} else {
		add_settings_error(
			'neuwo_plugin_text_string',
			'neuwo_plugin_texterror',
			$data->detail . ' (Error requesting trial ' .
			$request['response']['code'] . ')',

			'error'
		);
	}

	if (is_wp_error($request)) {
		add_settings_error(
			'neuwo_plugin_text_string',
			'neuwo_plugin_texterror',
			$data->detail . ' (Error requesting trial ' .
			$request['response']['code'] . ') ',

			'error'
		);
	}
}


/**
 * Gets site's custom tags from Neuwo API, validates response and returns as array.
 * Returns False if API request or validation fails and adds settings error.
 * @param $api_key string
 * @return array | False
 */
function neuwo_get_custom_tags($api_key){
	if (!$api_key){
		return;
	}

	$api_url = 'https://m1api.neuwo.ai/GetMarketingOntology?token=' . $api_key;
	$args = [
		'method' => 'GET',
		'headers' => [
			'accept' => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded'
		],
		'timeout' => 15,
	];

	$request = wp_remote_get( $api_url, $args);

	if( is_wp_error( $request ) || $request['response']['code'] != 200) {
		add_settings_error(
			'neuwo_plugin_text_string',
			'neuwo_plugin_texterror',
			'Error getting custom tags from Neuwo API: ' .
			$request['response']['message'] .
			' (Error code ' . $request['response']['code'] . ')',

			'error'
		);
		return False;
	}

	$body = wp_remote_retrieve_body( $request );
	$data = json_decode( $body );

	if (!property_exists($data, 'categories') || !is_array($data->categories)) {
		add_settings_error(
			'neuwo_plugin_text_string',
			'neuwo_plugin_texterror',
			'Error reading categories array from Neuwo API response, received: ' .
			print_r($data, true),
			'error'
		);
		return False;
	} else {
		$valid_data = [];

		try {
			foreach ($data->categories as $value) {
				$valid_data[intval($value->id)] = $value->label;
			}
			return $valid_data;
		} catch (Exception $ex){
			add_settings_error(
				'neuwo_plugin_text_string',
				'neuwo_plugin_texterror',
				'Error parsing individual custom tags from Neuwo API response.' .
				"Received: " . print_r($data, true) .
				' Exception: ' . $ex->getMessage(),

				'error'
			);
			return False;
		}
	}
}


/** Validate API token via Neuwo API (not implemented) */
function neuwo_validate_api_token($token) {
	$neuwo_token_api_url =
		"https://fx3ymadfp9.execute-api.eu-central-1.amazonaws.com/prod/api/v1/tokens/" .
		$token . '/' . '?key=neuwo_wp_plugin';

	$args = [
		'method' => 'GET',
		'headers' => [
			'accept' => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded'
		]
	];

	$request = wp_remote_get($neuwo_token_api_url, $args);

	$body = wp_remote_retrieve_body($request);
	$data = json_decode($body);

	if ($request['response']['code'] != 200) {
		add_settings_error(
			'neuwo_plugin_text_string',
			'neuwo_plugin_texterror',
			'Error validating token ' . $token . ' reason: ' . esc_html($data->detail) .
			' (Error code ' . $request['response']['code'] . ')',

			'error'
		);
		return false;
	} else {
		add_settings_error(
			'neuwo_plugin_text_string',
			'neuwo_plugin_text_success',
			'Token ' . $token . ' is valid.',
			'success'
		);
		return true;
	}
	if (is_wp_error($request)) {
		return false;
	}
}
