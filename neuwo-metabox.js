/*
 * Javascript for classic editor Neuwo metabox functionalities.
 */

jQuery(document).ready(function($) {
    
    // Check if "Publish" metabox controls not available
    if ($('#major-publishing-actions').length == 0){
        return; 
    }

    /*
     * "Get Keywords" button click handler. 
     * For unpublished post, triggers save draft action with a hidden form field set that tells backend to get Neuwo data.
     * For published post, triggers update post action which always gets Neuwo data.
     */
    $('#neuwo-get-keywords').on("click", function(e) {
        e.preventDefault();
        e.stopPropagation();

        if ($('#original_post_status').attr('value') == 'publish') {
            // Click 'Update' for already published article 

            if ($('#major-publishing-actions').length) {
                $('#major-publishing-actions').find('#publish').click();  // Classic Editor
            }

        } else {
            // Otherwise expect it to be draft, and click 'Save Draft'
            
            if ($('#minor-publishing-actions').find('#save-post').length){  
                $("#neuwo_should_update_btn").prop( "checked", true );  // Check hidden field read by backend from $_POST
                $('#minor-publishing-actions').find('#save-post').click();
            } 
        }
    });
    
    /*
     * Neuwo metabox AI Topic keyword quick add buttons that add them to tags metabox.
     */
    $('.neuwo_add_keyword_as_tag_btn').click(function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (e.target.classList.contains("button-disabled")){
            return;
        };

        $("#new-tag-post_tag").val(e.target.dataset['neuwo_keyword']);
        $("input.button.tagadd").click();

        e.target.className += "button-disabled";
        e.target.textContent = "✔";
    });
});
