<?php

/**
 * Settings validation logic including settings view validation callback function and
 * generic option data validators that can be called also from elsewhere (health check, etc).
 */


/**
 * Validates API key option format. Only letters, numbers and dashes are allowed.
 * @param string $api_key
 * @return bool
 */
function neuwo_validator_api_key_option($api_key) {
	if (is_string($api_key) && preg_match('/^[a-zA-Z0-9-]+$/', $api_key)) {
		return true;
	}
	return false;
}

/**
 * Validates Neuwo Post Types option format which should be an array of strings.
 * @param array $post_types_arr
 * @return bool
 */
function neuwo_validator_post_types_option($post_types_arr){
	if (is_array($post_types_arr)) {
		foreach ($post_types_arr as $post_type) {
			if (!is_string($post_type)) {
				return false;
			}
		}
		return true;
	}
}

/**
 * Validates Neuwo Enabled option format
 * @param string $enabled_str
 * @return bool
 */
function neuwo_validator_enabled_option($enabled_str){
	if (is_string($enabled_str) && in_array($enabled_str, ['enabled', 'disabled'])){
		return true;
	}
	return false;
}

/**
 * Validates Neuwo publication ID option format, only letters, numbers and dashes are allowed.
 * @param string $publication_id_str
 * @return bool
 */
function neuwo_validator_publication_id_option($publication_id_str){
	if (is_string($publication_id_str) && preg_match('/^[a-zA-Z0-9-]+$/', $publication_id_str)){
		return true;
	}
	return false;
}

/**
 * Validates Neuwo Enabled option format
 * @param string $enabled_str
 * @return bool
 */
function neuwo_validator_custom_tags_enabled_option($enabled_str){
	if (is_string($enabled_str) && $enabled_str == '1' ){
		return true;
	}
	return false;
}

/**
 * Validates Neuwo Custom Tags Data option format.
 * @param array $custom_tags_arr
 * @return bool
 */
function neuwo_validator_custom_tags_data_option($custom_tags_arr){
	if (!is_array($custom_tags_arr)) {
		return false;
	}

	foreach ($custom_tags_arr as $key => $tag) {
		if (!is_integer($key)) {
			return false;
		}
		if (!is_string($tag)) {
			return false;
		}
	}
	return true;
}

/**
 * Sanitize and validate settings page fields.
 * - apikey: should contain letters, numbers and dashes (required) empty is allowed when creating trial
 * - neuwo_post_types: should be a string with types separated with ',' (required)
 * - publication_id: should contain letters, numbers and dashes (optional)
 * - enabled: should be either 'enabled' or 'disabled' set by radio buttons (required)
 * - custom_tags_enabled: should be true or false, set by checkbox (optional)
 * - custom_tags: Fetched automatically from API during save.
 */
function neuwo_plugin_validate_options($input) {

	/* Trial API key */

	// Avoid missing API token warning if user just requested a new trial token
	$trial_being_created = false;

	if (isset($input['trial_email']) && strlen($input['trial_email']) > 0) {
		$trial_being_created = true;
		$trial_email_raw = $input['trial_email'];
		$trial_email_safe_valid = sanitize_email($trial_email_raw);

		if (!$trial_email_safe_valid){
			add_settings_error(
				'neuwo_plugin_text_string',
				'neuwo_plugin_texterror',
				'Email "' . esc_html($trial_email_raw) . '" is not in supported format.',
				'error'
			);
		} else {
			neuwo_request_api_token($trial_email_safe_valid);
		}
	}

	/* API key */

	// TODO Validate API token via API using utils.php::neuwo_validate_api_token

	// Skip when trial is being created
	if (!$trial_being_created && isset($input['apikey'])) {
		$api_key_safe = sanitize_text_field($input['apikey']);

		if (strlen($api_key_safe) == 0){
			add_settings_error(
				'neuwo_plugin_text_string',
				'neuwo_plugin_texterror',
				'API token is not set.',
			);
		} elseif (!neuwo_validator_api_key_option($api_key_safe)){
			add_settings_error(
				'neuwo_plugin_text_string',
				'neuwo_plugin_texterror',
				'API token format is invalid. It can only contain letters, numbers and dashes.',
			);
		} else {
			$valid['apikey'] = $api_key_safe;
		}
	}

	/* Neuwo Post Types */

	$neuwo_post_types_safe = sanitize_text_field($input['neuwo_post_types']);
	$neuwo_post_types_stripped = str_replace(' ', '', $neuwo_post_types_safe);
	$valid_post_types = array();

	if (is_array($input['neuwo_post_types'])) {  // TODO, check safe instead
		// Skip second run when already sanitized and $input has array
		// (quick fix to https://github.com/jasalt/neuwo/issues/2)
		$valid['neuwo_post_types'] = $input['neuwo_post_types'];
	} else {
		// First run doing sanitization "regular way"
		if (strlen($neuwo_post_types_stripped) == 0) {  // Got empty string
			add_settings_error(
				'neuwo_plugin_text_string',
				'neuwo_plugin_texterror',
				'Set at least one post type, such as post.',
				'error'
			);
		} else {
			$neuwo_post_types_exploded = explode(',', $neuwo_post_types_stripped);

			$usable_post_types = neuwo_get_usable_post_types();
			$valid_post_types = array();

			foreach ($neuwo_post_types_exploded as $post_type) {
				if (in_array($post_type, $usable_post_types)) {
					array_push($valid_post_types, $post_type);
				} else {
					add_settings_error(
						'neuwo_plugin_text_string',
						'neuwo_plugin_texterror',
						'Input invalid Post Type: "' . $post_type . '"',
						'error'
					);
				}
			}
		}
		if (neuwo_validator_post_types_option($valid_post_types)){
			$valid['neuwo_post_types'] = $valid_post_types;
		}
	}

	/* Enabled switch */

	$enabled_safe = sanitize_text_field($input['enabled']);

	if (neuwo_validator_enabled_option($enabled_safe)) {
		$valid['enabled'] = $enabled_safe;
	} else {
		add_settings_error(
			'neuwo_plugin_text_string',
			'neuwo_plugin_texterror',
			'Enabled can only be set to "enabled" or "disabled".',
			'error'
		);
	}

	/* Publication ID */

	if (isset($input['publication_id']) && strlen($input['publication_id']) > 0) {
		$publication_id_safe = sanitize_text_field($input['publication_id']);

		if (neuwo_validator_publication_id_option($publication_id_safe)){
			$valid['publication_id'] = $publication_id_safe;
		} else {
			add_settings_error(
				'neuwo_plugin_text_string',
				'neuwo_plugin_texterror',
				'Publication ID can only contain letters, numbers and dashes.',
				'error'
			);
		}
	}

	/* Custom Tags Enabled switch */

	if (
		isset($input['custom_tags_enabled']) &&
		neuwo_validator_custom_tags_enabled_option($input['custom_tags_enabled'])
	) {
		if (!isset($valid['publication_id'])) {
			add_settings_error(
				'neuwo_plugin_text_string',
				'neuwo_plugin_texterror',
				'Custom tags feature requires Publication ID to be set.',
				'error'
			);
		} else {
			$valid['custom_tags_enabled'] = true;

			// Fetch, validate and save custom tags
			$custom_tags_data = neuwo_get_custom_tags($api_key_safe);
			if (neuwo_validator_custom_tags_data_option($custom_tags_data)) {
				$valid['site_custom_tags'] = $custom_tags_data;
			}
		}
	}

	return $valid;
}
