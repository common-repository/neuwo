<?php

/* Settings page related view functions */


/** Render settings page top level */
function neuwo_plugin_option_page() {
?>
	<div class="wrap">
		<form action="options.php" method="post">
			<?php
			settings_fields('neuwo_plugin_options');
			do_settings_sections('neuwo_plugin');
			submit_button('Save Changes', 'primary');
			?>
		</form>
	</div>
<?php
}


/** Render settings header section text */
function neuwo_plugin_section_text_header() {
	echo '<a target="_blank"
             href="https://wordpress.org/plugins/neuwo/#installation">Plugin installation guide</a> | ';
	echo 'Support <a target="_blank" href="mailto:neuwo-helpdesk@neuwo.ai">neuwo-helpdesk@neuwo.ai</a>';
}


/**
 * Render settings general section text
 */
function neuwo_plugin_section_text_general() {
	echo "<p>Required settings for all plugin users.</p>";
}


/** Render API key field */
function neuwo_plugin_setting_apikey() {
	$apikey = neuwo_get_option_value('apikey');

	// Echo the field
	echo "<input id='apikey' name='neuwo_plugin_options[apikey]'
                 type='text' size='40' value='" . esc_attr($apikey) . "' />";
}


/** Render trial API key request form (when API key is not entered) */
function neuwo_plugin_setting_trial_email() {
	$apikey = neuwo_get_option_value('apikey');

	if (!$apikey) {
		echo '<p><strong>Request free Neuwo API token:</strong><br></p>';
		echo '<input id="trial_email" name="neuwo_plugin_options[trial_email]" type="text"
                     placeholder="john.doe@example.com" value="" />';
		echo '<input type="submit" name="submit" id="submit" class="button button-secondary"
                     value="Submit">';
		echo '<br>';
		echo '<p>
               <strong>Note:</strong>
               Free version has limitless access to Neuwo AI Topics taxonomy only.
               Additional IAB 2.2 and 3.0 taxonomies and content brand safety scores are also enabled for a short trial period after ordering the token.
               Contact us for paid subscription that enables continuous use of all of our features.
              </p>';
	}
}


/** Render post_types field */
function neuwo_plugin_setting_post_types() {
	$neuwo_post_types = neuwo_get_option_value('neuwo_post_types');
	$neuwo_post_types_str = '';

	if ($neuwo_post_types) {
		$neuwo_post_types_str = implode(',', $neuwo_post_types);
	}

	$usable_post_types = neuwo_get_usable_post_types();
	$usable_post_types_str = implode(', ', $usable_post_types);

	// Echo the field
	echo "<input id='neuwo_post_types' name='neuwo_plugin_options[neuwo_post_types]' type='text'
                 size='40' placeholder='post,page,movie_review'
                 value='" . esc_attr($neuwo_post_types_str) . "' />";
	echo "<br>";
	echo "<p>Separate entries with comma.</p>";
	echo "<p><strong>Available:</strong> " . $usable_post_types_str . "</p>";
}


/** Render Enable Neuwo field */
function neuwo_plugin_setting_enabled() {
	// Set to 'enabled' as a default if the option does not exist
	$options = get_option('neuwo_plugin_options', ['enabled' => 'enabled']);

	if (isset($options['enabled'])){
		$enabled = $options['enabled'];
	} else {
		$enabled = "disabled";
	}

	// Define the radio button options
	$items = array('enabled', 'disabled');

	foreach ($items as $item) {
		// Loop the two radio button options and select if set in the option value
		echo "<label><input " . checked($enabled, $item, false) . " value='" .
			esc_attr($item) . "' name='neuwo_plugin_options[enabled]' type='radio' />" .
			esc_html($item) . "</label><br />";
	}
}


/**
 * Render settings extra section text
 */
function neuwo_plugin_section_text_extra() {
	echo "<p>Optional settings for advanced customer specific AI model features.
             Contact <a target='_blank'
                        href='mailto:neuwo-helpdesk@neuwo.ai'>neuwo-helpdesk@neuwo.ai</a>
             before use.</p>";
}


/** Render publication_id field */
function neuwo_plugin_setting_publication_id() {
	$publication_id = neuwo_get_option_value('publication_id');
	echo "<input id='publication_id' name='neuwo_plugin_options[publication_id]'
        type='text' size='40' value='" . esc_attr($publication_id) . "' /><br>";
}


/** Render Custom Tags field & data */
function neuwo_plugin_setting_custom_tags_enabled() {
	$custom_tags_enabled = neuwo_get_option_value('custom_tags_enabled');

	// Echo the field as checkbox
	echo "<input id='custom_tags_enabled' name='neuwo_plugin_options[custom_tags_enabled]'
		type='checkbox' value='1' " . checked(1, $custom_tags_enabled, false) . " />";

	$site_custom_tags = neuwo_get_option_value("site_custom_tags");

	if($custom_tags_enabled && $site_custom_tags){
		echo "<p>Custom Tags (Ordering ID - Tag Text):</p>";

		echo "<div style='height: 300px; border: solid 1px lightgray; background:white;
                      max-width: 24em;
                      padding: 3px; overflow:scroll;'>
            <ul>";

		foreach ($site_custom_tags as $id => $tag){
			echo "<li>" . esc_html($id) . " - " . esc_html($tag) . "</li>";
		}

		echo "  </ul>
          </div>";

	} elseif ($custom_tags_enabled) {
		echo "<p>Custom tags have not been loaded.</p>";

	} else {
		echo "<p><strong>Note: </strong>Publication ID is required for this feature to work.</p>";
	}

	echo "<p>Tags are refreshed from Neuwo API automatically after saving settings.</p>";

}
