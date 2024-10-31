# Neuwo Plugin
Gutenberg sidebar plugin / classic editor metabox widget for fetching article metadata from Neuwo.ai NLP API including AI Topics, Brand Safety and IAB Marketing Categories. Allows quick adding tags as WP Post Tags from UI.

Neuwo article data is saved as 'neuwo_data' postmeta as PHP array and Neuwo article ID is saved to postmeta 'neuwo_article_id' which is used for updating data for existing post.

# Installation and usage

- Copy/clone plugin directory to wp-content/plugins/neuwo or install release zip file.
- Activate plugin on Wordpress plugin settings.
- Setup API key, enabled post types (and optional Publisher ID) via Settings > Neuwo.
- Fetch Neuwo data via Get Keywords button which also triggers when publishing or updating post.
- Postmeta can be utilized on front end PHP code via `$neuwo_data = get_post_meta($post_id, 'neuwo_data', true);`.

# Known issues

Plugin is in production use on various sites but is considered being under development. Apart from code refactoring needs there aren't known issues at the moment, please contact when encountering a bug with steps to reproduce it.

- Gutenberg: Editing the native tag control box does not inform Neuwo sidebar view about the updates very well. Could be worked further but left as is for now as it's more of a cosmetic issue.

See changelog.txt for more information.

# Notes

Should be WP multi site compatible but not tested throughly.

# Gutenberg Development
Using recommended ES6 build process with Babel:

    npm install  # install packages
    npm start    # watch and build after changes
