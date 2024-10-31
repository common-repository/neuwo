<?php

/**
 * Plugin Name:       Neuwo
 * Plugin URI:        https://neuwo.ai/solutions/developers/
 * Description:       Neuwo.ai plugin
 * Version:           1.4.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Neuwo
 * Author URI:        https://neuwo.ai/contact-us/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       neuwo
 * Domain Path:       /public/lang
 */


require plugin_dir_path(__FILE__) . '/settings/settings.php';
require plugin_dir_path(__FILE__) . '/functions.php';
require plugin_dir_path(__FILE__) . '/metabox.php';
require plugin_dir_path(__FILE__) . '/gutenberg.php';


function neuwo_activation_admin_notice() {
	if (get_transient('neuwo_healthcheck_fail_notice_transient')) {
		// Delete the transient so it only shows once
		delete_transient('neuwo_healthcheck_fail_notice_transient');

		echo '<div class="notice notice-error is-dismissible"><p>';
		echo 'Neuwo plugin health check failed and settings were reseted. Please configure plugin again.
              Existing article metadata is kept.' .
			neuwo_configure_now_link();
		echo '</p></div>';
	}
}
add_action('admin_notices', 'neuwo_activation_admin_notice');


/** Settings validation during plugin activation */
function neuwo_healthcheck() {
	neuwo_validate_settings_or_clear();
}
register_activation_hook(__FILE__, 'neuwo_healthcheck');


/** Clean unregistration of registered settings */
function neuwo_plugin_uninstall() {
	unregister_setting('neuwo_plugin_options', 'neuwo_plugin_options');

	// Remove saved options from the database
	// delete_option( 'neuwo_plugin_options' );
}
register_uninstall_hook(__FILE__, 'neuwo_plugin_uninstall');


add_action('init', 'register_neuwo_postmeta');
function register_neuwo_postmeta() {
	$options = get_option('neuwo_plugin_options');
	if (!$options) {
		return;
	}
	if (!array_key_exists('neuwo_post_types', $options)) {
		return;
	}

	$neuwo_post_types = $options['neuwo_post_types'];

	foreach ($neuwo_post_types as $post_type) {
		register_post_meta(
			$post_type,
			'neuwo_data',
			[
				'auth_callback' => function () {
					return current_user_can('edit_posts');
				},
				// 'sanitize_callback' => 'sanitize_text_field',  # TODO breaks the object during save
				'type'         => 'object',
				'description'  => 'Neuwo.ai metadata',
				// TODO Commented as not compatible with OM and does not seem to affect Gutenberg
				// however in 2024/03. It's requirement in this case may have been changed after 2022/10.
				// https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/
				// 'show_in_rest' => [
				//     'single' => false,
				//     'schema' => [
				//         'type' => 'object',
				//         'properties' => [
				//             'tags' => [
				//                 'type' => 'array',
				//                 'items' => [
				//                     'type' => 'object',
				//                     'properties' => ['URI' => ['type' => 'string'],
				//                                      'value' => ['type' => 'string'],
				//                                      'score' => ['type' => 'string']]
				//                     ]
				//             ],
				//             'brand_safety' => [
				//                 'type' => 'object',
				//                 'properties' => [
				//                     'BS_score' => ['type' => 'string'],
				//                     'BS_indication' => ['type' => 'string']
				//                 ]
				//             ],
				//             'marketing_categories' => [
				//                 'type' => 'object',
				//                 'properties' => [
				//                     'iab_tier_1' => ['type' => 'array', 'items' => ['type' => 'string']],
				//                     'iab_tier_2' => ['type' => 'array', 'items' => ['type' => 'string']]
				//                 ]
				//             ]
				//         ]
				//     ]
				// ],
			]
		);

		register_post_meta(
			$post_type,
			'neuwo_exclude_from_similarity',
			[
				'auth_callback' => function () {
					return current_user_can('edit_posts');
				},
				// 'sanitize_callback' => 'sanitize_text_field',
				'single'       => true,
				'description'  => 'Exclude post from Neuwo.ai similarity suggestions',
				'type'         => 'boolean',
				'show_in_rest' => true,
				'auth_callback' => '__return_true',
			]
		);
	};
}

/**
 * Setup neuwo_get_ai_topics hooks for save_post and publish_post action on
 * each activated post type.
 */
if (neuwo_is_enabled()) {
	$neuwo_post_types = neuwo_get_option_value('neuwo_post_types');

	if ($neuwo_post_types) {
		// Hook to enabled post type save_post actions
		foreach ($neuwo_post_types as $post_type) {
			add_action('save_post_' . $post_type, 'neuwo_get_ai_topics');
			add_action('publish_' . $post_type, 'neuwo_update_post');
		}
	}
}
