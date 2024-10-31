<?php

/* Settings page initialization, using WP Settings API */

// Settings page Url slug, used also in functions.php::neuwo_configure_now_link
$GLOBALS['NEUWO_SETTINGS_SLUG'] = "neuwo_plugin";

require plugin_dir_path( __FILE__ ) . '/utils.php';
require plugin_dir_path( __FILE__ ) . '/validation.php';
require plugin_dir_path( __FILE__ ) . '/views.php';


/** Adds settings page link to the admin menu */
function neuwo_plugin_add_settings_menu() {
	add_options_page(
		'Neuwo Settings',                       // Page title
		'Neuwo',                                // Menu title
		'manage_options', 					    // User capability required
		$GLOBALS['NEUWO_SETTINGS_SLUG'],        // Menu url slug
		'neuwo_plugin_option_page' 			    // Render fn
	);
}
add_action('admin_menu', 'neuwo_plugin_add_settings_menu');


/** Define settings fields */
function neuwo_plugin_admin_init() {
	$args = array(
		'type' 				=> 'string',
		'sanitize_callback' => 'neuwo_plugin_validate_options',
		'default' 			=> NULL
	);

	register_setting('neuwo_plugin_options', 'neuwo_plugin_options', $args);

	/* Main section */

	add_settings_section(
		'neuwo_plugin_header',                // Section ID
		'Neuwo.ai',                           // Title
		'neuwo_plugin_section_text_header',	  // Section/field render fn
		'neuwo_plugin'						  // Page
	);

	add_settings_section(
		'neuwo_plugin_general',
		'General settings',
		'neuwo_plugin_section_text_general',
		'neuwo_plugin'
	);

	add_settings_field(
		'neuwo_plugin_apikey',
		'API Token',
		'neuwo_plugin_setting_apikey',
		'neuwo_plugin',
		'neuwo_plugin_general'
	);

	add_settings_field(
		'neuwo_plugin_trial_email',
		'',  // Skip setting default label
		'neuwo_plugin_setting_trial_email',
		'neuwo_plugin',
		'neuwo_plugin_general'
	);

	add_settings_field(
		'neuwo_plugin_post_types',
		'Enabled Post Types',
		'neuwo_plugin_setting_post_types',
		'neuwo_plugin',
		'neuwo_plugin_general'
	);

	add_settings_field(
		'neuwo_plugin_enabled',
		'Enable Neuwo',
		'neuwo_plugin_setting_enabled',
		'neuwo_plugin',
		'neuwo_plugin_general'
	);

	/* Extra section */

	add_settings_section(
		'neuwo_plugin_extra',
		'Extra features',
		'neuwo_plugin_section_text_extra',
		'neuwo_plugin'
	);

	add_settings_field(
		'neuwo_plugin_publication_id',
		'Publication ID',
		'neuwo_plugin_setting_publication_id',
		'neuwo_plugin',
		'neuwo_plugin_extra'
	);

	add_settings_field(
		'neuwo_plugin_custom_tags_enabled',
		'User Custom Tags (beta)',
		'neuwo_plugin_setting_custom_tags_enabled',
		'neuwo_plugin',
		'neuwo_plugin_extra'
	);
}
add_action('admin_init', 'neuwo_plugin_admin_init');
