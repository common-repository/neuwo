<?php

/**
 * Classic Editor metabox panels. Disabled for block editor.
 */


/**
 * Check if current page is block editor.
 * ref: https://wordpress.stackexchange.com/questions/309862/check-if-gutenberg-is-currently-in-use
 * @return bool
 */
function neuwo_is_block_editor() {
	$current_screen = get_current_screen();
	return $current_screen->is_block_editor();
}


/**
 * Load metabox CSS and JS on classic editor pages for enabled post types.
 * Skips loading for block editor.
 */
function load_neuwo_metabox_css_js($hook) {
	if ('post-new.php' !== $hook && 'post.php' !== $hook) {  // Load only on editor page
		return;
	}
	if (!neuwo_get_option_value("enabled")) {
		return;
	}

	// Load only for enabled post types
	$enabled_post_types = neuwo_get_option_value('neuwo_post_types');
	if (!in_array(get_post_type(), $enabled_post_types, true)) {
		return;
	}
	if (neuwo_is_block_editor()) {
		return;
	}
	wp_enqueue_style('neuwo-metabox-style', plugins_url('neuwo-metabox.css', __FILE__));
	wp_enqueue_script('neuwo-metabox-script', plugin_dir_url(__FILE__) . 'neuwo-metabox.js', array('jquery'));
}
add_action('admin_enqueue_scripts', 'load_neuwo_metabox_css_js');


/**
 * Register metaboxes for enabled post types.
 */
function neuwo_add_meta_boxes() {
	$options = get_option('neuwo_plugin_options');
	if ($options) {
		if (array_key_exists('neuwo_post_types', $options)) {
			$neuwo_post_types = $options['neuwo_post_types'];
		}
	} else {
		return;
	}

	foreach ($neuwo_post_types as $post_type) {
		add_meta_box(
			'neuwo_box_id',                  // Unique ID
			'Neuwo.ai Keywords',             // Box title
			'neuwo_meta_box_aitopics_html',  // Content callback, must be of type callable
			$post_type,
			'side',                          // Location
			'default',                       // Priority
			array(                           // Show on Classic Editor only
				'__back_compat_meta_box' => true,
				'__block_editor_compatible_meta_box' => false
			)
		);

		// Load custom tags metabox if feature is enabled
		if (key_exists('custom_tags_enabled', $options) && $options['custom_tags_enabled'] == 'enabled') {
			add_meta_box(
				'neuwo_box_custom_tags_id',
				'Neuwo.ai Custom Tags',
				'neuwo_meta_box_custom_tags_html',
				$post_type,
				'side',
				'default',
				array(
					// '__back_compat_meta_box' => true,
					'__block_editor_compatible_meta_box' => true
				)
			);
		}
	}
}
add_action('add_meta_boxes', 'neuwo_add_meta_boxes', 99);

/**
 * Render GetAiTopics metabox HTML
 */
function neuwo_meta_box_aitopics_html($post) {
	$post_id = $post->ID;

	$neuwo_data = get_post_meta($post_id, 'neuwo_data', true);
	$neuwo_options = get_option('neuwo_plugin_options', null);

	$neuwo_apikey = $neuwo_options['apikey'] ?? '';
	if ($neuwo_apikey == ''){
		?> <p class="neuwo-warning">API token is missing</p> <?php
		?> <a href="/wp-admin/options-general.php?page=neuwo_plugin">Configure now</a> <?php
	}

	$neuwo_enabled = $neuwo_options['enabled'] ?? '';
	if ($neuwo_enabled != 'enabled'){
		?> <p class="neuwo-warning">Neuwo not enabled</p> <?php
	}

	if ($neuwo_apikey == '' || $neuwo_enabled != 'enabled') {
		return;
	}

	$block_editor = neuwo_is_block_editor();

	if (!$block_editor) {  // TODO cleanup?
		?>
			<div class="neuwo_metabox">
			<button id="neuwo-get-keywords" class="button">Get Keywords</button>
			<input name="neuwo_should_update" id="neuwo_should_update_btn" style="display:none;" type="checkbox"/>
		<?php
	}

	if ($neuwo_data) {

		?> <h4><strong>AI Topics</strong></h4> <?php
		$tags = $neuwo_data->tags;

		$post_taxonomies = get_post_taxonomies($post_id);

		$post_tags = [];
		$post_tag_objs = wp_get_post_tags($post_id);
		foreach ($post_tag_objs as $post_tag_obj){
			array_push($post_tags, strtolower($post_tag_obj->name));
		}

		foreach ($tags as $key => $tag) {
			$tag_value_normalized = strtolower(esc_html($tag->value));	// Handle eg. '&' characters that are saved in DB as '&amp;'
			$keyword_tag_exists = in_array($tag_value_normalized, $post_tags);
			?>
			<p class="neuwo_tag"> <?php echo esc_html($tag->value) ?>
				<span class="neuwo_tag_score"><?php echo esc_html($tag->score) ?></span>

				<?php
					// disable button for keywords with comma which are not supported in post_tag taxonomy terms
					if (strpos($tag->value, ',') === false &&
						in_array('post_tag', $post_taxonomies) // and for post types not using post_tag taxonomy
					) {
					if (!$block_editor){
						?>
							<button class="button neuwo_add_keyword_as_tag_btn <?php if ($keyword_tag_exists) { echo 'button-disabled'; }  ?>"
								data-neuwo_keyword="<?php echo esc_html($tag->value) ?>"><?php
									if ($keyword_tag_exists) {
										echo '✔';
									} else {
										echo '+';
									}  ?>
							</button>
						<?php
						}
					}
				?>
			</p>
			<?php
		}

		if (isset($neuwo_data->brand_safety)){

			?> <h4>Brand Safety</h4> <?php

			echo '<p>Score: ' . esc_html($neuwo_data->brand_safety->BS_score) . '</p>';
			echo '<p>Indication: ' . esc_html($neuwo_data->brand_safety->BS_indication) . '</p>';

		}

		if (isset($neuwo_data->marketing_categories->iab_tier_1) && count($neuwo_data->marketing_categories->iab_tier_1) != 0){

			?> <h4>IAB Marketing Categories</h4> <?php

			?> <h5>Tier 1</h5> <?php
			if (count($neuwo_data->marketing_categories->iab_tier_1) != 0){
				foreach ($neuwo_data->marketing_categories->iab_tier_1 as $key => $iab) {
					echo '<p>' . esc_html($iab->ID) . ' ' . esc_html($iab->label) .
					' <span class="neuwo_tag_score">' . esc_html($iab->relevance) . '</span></p>';
				}
			} else {
				echo '<p>-</p>';
			}

			?> <h5>Tier 2</h5> <?php
			if (count($neuwo_data->marketing_categories->iab_tier_2) != 0){
				foreach ($neuwo_data->marketing_categories->iab_tier_2 as $key => $iab) {
					echo '<p>' . esc_html($iab->ID) . ' ' . esc_html($iab->label) .
					' <span class="neuwo_tag_score">' . esc_html($iab->relevance) . '</span></p>';
				}
			} else {
				echo '<p>-</p>';
			}

		}

	} elseif ($block_editor) {
		?> <p>Reload page after publishing article to view Neuwo data.</p> <?php
	}
	if (!$block_editor){
		$excluded_from_similarity = get_post_meta($post_id, 'neuwo_exclude_from_similarity', true);
		?>
			<h4><strong>Options</strong></h4>
			<input name="exclude_from_similarity" type="checkbox" <?php if ($excluded_from_similarity) {
				echo 'checked'; } ?>/>Exclude from similarity suggestions
		</div>
		<?php
	}
}

/**
 * Render Custom Tags metabox HTML
 */
function neuwo_meta_box_custom_tags_html($post) {
	$site_custom_tags = neuwo_get_option_value("site_custom_tags");  // site global custom tags

	if (!$site_custom_tags) {
		echo "Custom tags have not been not loaded for the site, please see plugin options.";
		return;
	}

	$post_id = $post->ID;
	$current_custom_tags = get_post_meta($post_id, 'neuwo_custom_tags', true);  // user selections

	$neuwo_data = get_post_meta($post_id, 'neuwo_data', true);  // relevancy scores
	$custom_tag_relevancy_by_tag_id = [];

	// /GetAiTopics marketing_categories->marketing_items contains custom tag relevancy scores
	// TODO works with empty data?
	if (isset($neuwo_data) && isset($neuwo_data->marketing_categories->marketing_items)) {
		$custom_tag_relevancy_data = $neuwo_data->marketing_categories->marketing_items;
		foreach ($custom_tag_relevancy_data as $custom_tag) {
			$custom_tag_relevancy_by_tag_id[$custom_tag->ID] = $custom_tag->relevance;
		}
	}

	echo "<div id='neuwo_custom_tags_list'
               style='height: 300px; border: solid 1px lightgray;
                      padding-left: 3px; overflow:scroll;'>
            <ul>";

	foreach ($site_custom_tags as $tag_id => $tag_value){
		if (is_array($current_custom_tags)){
            $checked = in_array($tag_id, $current_custom_tags) ? "checked" : "";
        } else {
			$checked = "";
		}

		$tag_relevancy = "";
		if (array_key_exists($tag_id, $custom_tag_relevancy_by_tag_id)) {
			$tag_relevancy = " <small>" . $custom_tag_relevancy_by_tag_id[$tag_id] . "</small>";
		}

		echo "<li>
                  <input type='checkbox' name='neuwo_custom_tags_metabox_values[]'
                         value='" . esc_attr($tag_id) . "'" . $checked . "/>
                         " . esc_html($tag_value) . $tag_relevancy .
		     "</li>";
	}

	echo "</ul></div>";
	if (neuwo_is_block_editor()){
		echo "<small>Updated relevancy scores show here only after refreshing the editor page.</small>";
	}
}
