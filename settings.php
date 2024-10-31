<?php

/*
 * Plugin customization options and settings page views.
 */

// Register our uninstall function
register_uninstall_hook( __FILE__, 'neuwo_plugin_uninstall' );

// Deregister our settings group and delete all options
function neuwo_plugin_uninstall() {

	// Clean de-registration of registered setting
	unregister_setting( 'neuwo_plugin_options', 'neuwo_plugin_options' );

	// Remove saved options from the database
	// delete_option( 'neuwo_plugin_options' );

}

// Add a menu for our option page
add_action( 'admin_menu', 'neuwo_plugin_add_settings_menu' );

function neuwo_plugin_add_settings_menu() {

    add_options_page( 'Neuwo', 'Neuwo', 'manage_options',
        'neuwo_plugin', 'neuwo_plugin_option_page' );
}
        
// Create the option page
function neuwo_plugin_option_page() {
    ?>
    <div class="wrap">
	    <!-- <h2>Neuwo.ai</h2> -->
	    <form action="options.php" method="post">
		    <?php 
            settings_fields( 'neuwo_plugin_options' );
		    do_settings_sections( 'neuwo_plugin' );
		    submit_button( 'Save Changes', 'primary' ); 
            ?>
	    </form>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'neuwo_plugin_admin_init');

function neuwo_plugin_admin_init(){

	// Define the setting args
	$args = array(
	    'type' 				=> 'string', 
	    'sanitize_callback' => 'neuwo_plugin_validate_options',
	    'default' 			=> NULL
	);

    // Register settings
    register_setting( 'neuwo_plugin_options', 'neuwo_plugin_options', $args );
    
    // Add a settings section
    add_settings_section( 
    	'neuwo_plugin_main', 
    	'Neuwo.ai Tag Suggestion Settings',
        'neuwo_plugin_section_text', 
        'neuwo_plugin' 
    );
    
    // Create settings field for ordering trial apikey to email address
    add_settings_field( 
        'neuwo_plugin_trial_email', 
        '',
        'neuwo_plugin_setting_trial_email', 
        'neuwo_plugin', 
        'neuwo_plugin_main' 
    );
    
    // Create settings field for apikey
    add_settings_field( 
    	'neuwo_plugin_apikey', 
    	'Neuwo API Token',
        'neuwo_plugin_setting_apikey', 
        'neuwo_plugin', 
        'neuwo_plugin_main' 
    );

    // Create settings field for post types
    add_settings_field( 
        'neuwo_plugin_post_types', 
        'Post Types',
        'neuwo_plugin_setting_post_types', 
        'neuwo_plugin', 
        'neuwo_plugin_main' 
    );

    // Create settings field for publication_id
    add_settings_field( 
        'neuwo_plugin_publication_id', 
        'Neuwo Publication ID',
        'neuwo_plugin_setting_publication_id', 
        'neuwo_plugin', 
        'neuwo_plugin_main' 
    );

    // Create settings field
    add_settings_field( 
    	'neuwo_plugin_enabled', 
    	'Enable Neuwo',
        'neuwo_plugin_setting_enabled', 
        'neuwo_plugin', 
        'neuwo_plugin_main' 
    );

    // Extend with AI -settings
    add_settings_section( 
        'neuwo_plugin_ai_extension', 
        'Extend with AI -settings',
        'neuwo_plugin_section_text_ai_extension', 
        'neuwo_plugin' 
    );

    add_settings_field( 
    	'neuwo_plugin_ai_token', 
    	'Extend with AI: OpenAI token',
        'neuwo_plugin_ai_setting_token', 
        'neuwo_plugin', 
        'neuwo_plugin_ai_extension' 
    );

    add_settings_field( 
    	'neuwo_plugin_ai_prompt', 
    	'Extend with AI: prompt',
        'neuwo_plugin_ai_setting_prompt', 
        'neuwo_plugin', 
        'neuwo_plugin_ai_extension' 
    );

    add_settings_field( 
    	'neuwo_plugin_ai_body', 
    	'Extend with AI: body',
        'neuwo_plugin_ai_setting_body', 
        'neuwo_plugin', 
        'neuwo_plugin_ai_extension' 
    );
}

// Draw the section header
function neuwo_plugin_section_text() {
    echo '<p>Enter your settings here.</p>';
}

// Display and fill the apikey text form field
function neuwo_plugin_setting_trial_email() {

    $options = get_option( 'neuwo_plugin_options' );
    $apikey = '';

    if ($options){
        if (array_key_exists('apikey', $options)){
            $apikey = $options['apikey'];
        } 
    }

    if ($apikey == ''){
        ?>
        <p><strong>Request free Neuwo API token via email:</strong><br></p>
        <input id='trial_email' name='neuwo_plugin_options[trial_email]' type='text' placeholder="john.doe@example.com" value='' />
        <input type="submit" name="submit" id="submit" class="button button-secondary" value="Submit">
        <br>
        <label>With the free version you can use Neuwo AI - topics only.</label>
        <?php
    }
}

// Display and fill the apikey text form field
function neuwo_plugin_setting_apikey() {

    $options = get_option( 'neuwo_plugin_options' );
    $apikey = '';

    if ($options){
        if (array_key_exists('apikey', $options)){
            $apikey = $options['apikey'];
        }
    }

    // Echo the field
    echo "<input id='apikey' name='neuwo_plugin_options[apikey]'
        type='text' value='" . esc_attr( $apikey ) . "' />";
}

// Display and fill the publication_id text form field
function neuwo_plugin_setting_publication_id() {
    // Get option 'text_string' value from the database
    $options = get_option( 'neuwo_plugin_options' );
    $publication_id = '';

    if ($options){
        if (array_key_exists('publication_id', $options)){ // TODO HACK why this can give empty option sometimes?
            $publication_id = $options['publication_id'];
        }
    }

    // Echo the field
    echo "<input id='publication_id' name='neuwo_plugin_options[publication_id]'
        type='text' value='" . esc_attr( $publication_id ) . "' /><br>";
    ?><label>Optional</label> <?php
}

/*
 * Get all installed post types and return ones that would be relevant to use with Neuwo plugin.
 * Uses get_post_types and filters out stock WP post types other than post or article.
 */
function neuwo_get_usable_post_types() {
    $installed_post_types = get_post_types();
    $usable_post_types = [];
    $unusable_post_types = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 
                            'customize_changeset', 'oembed_cache', 'user_request', 
                            'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 
                            'wp_navigation'];

    foreach ($installed_post_types as $post_type){
        if (in_array($post_type, $unusable_post_types)){ 
            continue; 
        } else { 
            array_push($usable_post_types, $post_type); 
        }
    }
    return $usable_post_types;
}

// Display and fill the publication_id text form field
function neuwo_plugin_setting_post_types() {
    // Get option 'text_string' value from the database
    $options = get_option( 'neuwo_plugin_options' );
    $neuwo_post_types = '';
    $neuwo_post_types_str = '';
    
    // TODO HACK why this can give empty option sometimes?
    if ($options){
        if (array_key_exists('neuwo_post_types', $options)){
            $neuwo_post_types = $options['neuwo_post_types'];
            $neuwo_post_types_str = implode( ',', $neuwo_post_types );
        }
    }

    $usable_post_types = neuwo_get_usable_post_types();
    $usable_post_types_str = implode( ', ', $usable_post_types );

    // Echo the field
    echo "<input id='neuwo_post_types' name='neuwo_plugin_options[neuwo_post_types]'
        type='text' value='" . esc_attr( $neuwo_post_types_str ) . "' /> <br>";

    ?><label><strong>Available Post Types:</strong> <?php echo $usable_post_types_str ?> </label> 
      <br> <label> Separate Post Types with comma, eg. 'post,receipt,movie_review'</label>
    <?php
}

// Display and set radion button field
function neuwo_plugin_setting_enabled() {

	// Get option 'enabled' value from the database
    // Set to 'disabled' as a default if the option does not exist
	$options = get_option( 'neuwo_plugin_options', [ 'enabled' => 'enabled' ] );
	$enabled = $options['enabled'];
	
	// Define the radio button options
	$items = array( 'enabled', 'disabled' );

	foreach( $items as $item ) {

		// Loop the two radio button options and select if set in the option value
		echo "<label><input " . checked( $enabled, $item, false ) . " value='" . 
            esc_attr( $item ) . "' name='neuwo_plugin_options[enabled]' type='radio' />" . 
            esc_html( $item ) . "</label><br />";
	}
}

function neuwo_request_api_token($email_address) {
    $neuwo_token_api_url = "https://fx3ymadfp9.execute-api.eu-central-1.amazonaws.com/prod/api/v1/tokens?key=neuwo_wp_plugin&email=" . $email_address;
    
    $args = [
        'method' => 'POST',
        'headers' => [
            'accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]
    ];

    $request = wp_remote_post( $neuwo_token_api_url, $args);

    $body = wp_remote_retrieve_body( $request );
    $data = json_decode( $body );

    if ($request['response']['code'] != 200){
        add_settings_error(
            'neuwo_plugin_text_string',
            'neuwo_plugin_texterror',
            $data->detail .' (Error code '.$request['response']['code'].')',
            'error'
        );
    } else {
        add_settings_error(
            'neuwo_plugin_text_string',
            'neuwo_plugin_text_success',
            'Neuwo trial started for email address '. $email_address .', please see your inbox for verification and API token.',
            'success'
        );
    }
    if( is_wp_error( $request ) ) {
        return false; 
    }
}

function neuwo_validate_api_token($token) {  // TODO upcoming feature
    $neuwo_token_api_url = "https://fx3ymadfp9.execute-api.eu-central-1.amazonaws.com/prod/api/v1/tokens/" . $token . '/' . '?key=neuwo_wp_plugin';
    
    $args = [
        'method' => 'GET',
        'headers' => [
            'accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]
    ];

    $request = wp_remote_get( $neuwo_token_api_url, $args);

    $body = wp_remote_retrieve_body( $request );
    $data = json_decode( $body );

    if ($request['response']['code'] != 200){
        add_settings_error(
            'neuwo_plugin_text_string',
            'neuwo_plugin_texterror',
            'Error validating token '.$token.' reason: '. esc_html($data->detail) . ' (Error code '. $request['response']['code'].')',
            'error'
        );
        return false;
    } else {
        add_settings_error(
            'neuwo_plugin_text_string',
            'neuwo_plugin_text_success',
            'Token '. $token .' is valid.',
            'success'
        );
        return true;
    }
    if( is_wp_error( $request ) ) {
        return false; 
    }
}

function neuwo_plugin_validate_options( $input ) {
    
    // Disable warning about missing API token if user just requested a new trial token
    $trial_being_created = false;  
    if (array_key_exists('trial_email', $input)){
        $trial_email_raw = $input['trial_email'];
        $trial_email_safe = sanitize_email($trial_email_raw);
        
        if (strlen($input['trial_email']) != 0) {
            $trial_being_created = true;
            neuwo_request_api_token($trial_email_safe);
        } 
    }

    $api_token_raw = $input['apikey'];
    $api_token_safe = sanitize_text_field($api_token_raw);


    if (strlen($api_token_safe) == 0){
        if (!$trial_being_created)  
        {
        add_settings_error(
                'neuwo_plugin_text_string',
                'neuwo_plugin_texterror',
                'API token is missing.',
                'error'
            );
        }
    }

    // TODO Validate API token
    // if (neuwo_validate_api_token($api_token_safe)){
    // }

    $valid['apikey'] = $api_token_safe;

    $neuwo_post_types_safe = sanitize_text_field( $input['neuwo_post_types'] );
    $neuwo_post_types_stripped = str_replace(' ', '', $neuwo_post_types_safe);
    $valid_post_types = array();

    if ( strlen($neuwo_post_types_stripped) == 0 ) {  // Got empty string
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
    
        foreach ($neuwo_post_types_exploded as $post_type){
            if (in_array($post_type, $usable_post_types)){
                array_push($valid_post_types, $post_type);
            } else {
                add_settings_error(
                    'neuwo_plugin_text_string',
                    'neuwo_plugin_texterror',
                    'Input invalid Post Type: "' . $post_type .'"',
                    'error'
                );
            }
        }
    }
        
    $valid['neuwo_post_types'] = $valid_post_types;

    $valid['publication_id'] = sanitize_text_field( $input['publication_id'] );
    $valid['enabled'] = sanitize_text_field( $input['enabled'] );

    if ( !empty($input['extend_ai_token']) ) {
        $valid['extend_ai_token'] = sanitize_text_field( $input['extend_ai_token'] );
    }
    else {
        $preopt = get_option('neuwo_plugin_options');
        if (!is_null($preopt)){
            $valid['extend_ai_token'] = array_key_exists('extend_ai_token', $preopt) ? $preopt['extend_ai_token'] : '';
        }
        else {
            $valid['extend_ai_token'] = '';
        }
    }

    $valid['extend_ai_prompt'] = sanitize_textarea_field( $input['extend_ai_prompt'] );

    try {
        $bodyfield = sanitize_textarea_field( $input['extend_ai_body_container'] );
        if (!empty($bodyfield)) {
            $bodycz = json_decode($bodyfield, true); // will throw out of this step if invalid json
            $valid['extend_ai_body_container'] = $bodyfield;
        }
    } catch (\Throwable $th) {
        if (!empty($input['extend_ai_body_container'])) {
            add_settings_error(
                'neuwo_plugin_text_string',
                'neuwo_plugin_texterror',
                'JSON parsing failure at Extend with AI: body',
                'error'
            );
        }
    }

    return $valid;
}

function neuwo_plugin_section_text_ai_extension() {
    echo '<p>Enter your OpenAI extension settings here.</p>';
}

function neuwo_plugin_ai_setting_token() {
    $option_key = 'extend_ai_token';
    $options = get_option( 'neuwo_plugin_options' );
    $visible = (is_array($options) && array_key_exists($option_key, $options)) ? $options[$option_key] : '';

    echo "<input id='field-" . $option_key . "' name='neuwo_plugin_options[" . $option_key . "]'
        type='text' placeholder='" . esc_attr(substr($visible, 0, 14)) . "...' value='' /><br>";
}

function neuwo_plugin_ai_setting_prompt() {
    $option_key = 'extend_ai_prompt';
    $options = get_option( 'neuwo_plugin_options' );
    $visible = (is_array($options) && array_key_exists($option_key, $options)) ? $options[$option_key] : '';

    echo "<textarea id='field-" . $option_key . "' name='neuwo_plugin_options[" . $option_key . "]' cols='150'>";
    echo esc_attr( $visible );
    echo "</textarea><br>";
    echo "<label for='field-" . $option_key . "'>Will replace 'prompt' field in body sent to the AI API</label>";
    
    // TODO: loop through list of harvested post details for replacement tag display below
    echo "<div>replacement fields for [content], [title], [tags] available</div>";
}

function neuwo_plugin_ai_setting_body() {
    $option_key = 'extend_ai_body_container';
    $options = get_option( 'neuwo_plugin_options' );
    $visible = (is_array($options) && array_key_exists($option_key, $options)) ? $options[$option_key] : '';

    echo "<textarea id='field-" . $option_key . "' name='neuwo_plugin_options[" . $option_key . "]' cols='150'>";
    echo esc_attr( $visible );
    echo "</textarea><br>";
    echo "<label for='field-" . $option_key . "'>JSON string, 'prompt' field will be replaced</label>";
    echo "<div>adjust request parameters such as temperature or max_tokens with <code>{\"temperature\": 0.2, \"max_tokens\": 16, \"stop\": \"\\n\", \"model\": \"text-davinci-003\"}</code></div>";
}