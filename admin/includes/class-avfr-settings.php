<?php
/**
 * Plugin settings
 *
 * @package             Feature-Request
 * @author              Averta
 * @license             GPL-2.0+
 * @link                http://averta.net
 * @copyright           2015 Averta
 *
 */

require_once dirname( __FILE__ ) . '/class-avfr-settings-api.php';
if ( !class_exists('Avfr_Settings' ) ):
class Avfr_Settings {

    private $settings_api;

    const version = '1.0';

    function __construct() {

        $this->settings_api = new Avfr_Settings_API;

        add_action( 'admin_init', 						array($this, 'admin_init') );
        add_action( 'admin_menu', 						array($this, 'submenu_page'));
        add_action( 'admin_head',                       array($this, 'reset_votes'));
        add_action( 'wp_ajax_avfr_reset',               array($this, 'avfr_reset' ));

    }
    
    function submenu_page() { 
        
         add_submenu_page( 'edit.php?post_type=avfr', 'Settings', __('Settings','feature-request'), 'manage_options', 'feature-request-settings',array($this,'submenu_page_callback') );

    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }


    function submenu_page_callback() {
        echo '<div class="wrap">';
            ?><h2><?php _e('Feature Request Settings','feature-request');?></h2><?php
            $this->settings_api->show_navigation();
            $this->settings_api->show_forms();
        echo '</div>';
    }

    /**
    *
    *   Handel the click event for resetting votes
    *
    */
    function reset_votes() {

        $nonce = wp_create_nonce('avfr-reset');
            ?>
                <!-- Reset Votes -->
                <script>
                    jQuery(document).ready(function($){
                        // reset post meta
                        jQuery('.feature-request-reset-votes').click(function(e){

                            var r = confirm('Are you sure to reset all votes?');
                            if ( r == true ) {
                                e.preventDefault();
                                var data = {
                                    action: $(this).hasClass('reset-db') ? 'avfr_db_reset' : 'avfr_reset',
                                    security: '<?php echo $nonce;?>'
                                };
                                jQuery.post(ajaxurl, data, function(response) {
                                    if( response ){
                                        alert(response);
                                        location.reload();
                                    }
                                });
                            }
                        });
                    });
                </script>

        <?php 

    }
    
    /**
    *
    * Process the votes reset
    *
    */
    function avfr_reset(){
         
            check_ajax_referer( 'avfr-reset', 'security' );
            $posts = get_posts( array('post_type' => 'avfr', 'posts_per_page' => -1 ) );

            if ( $posts ):

                foreach ( $posts as $post ) {

                    $total_votes = get_post_meta( $post->ID, '_avfr_total_votes', true );
                    $votes       = get_post_meta( $post->ID, '_avfr_votes', true );

                    if ( !empty( $total_votes ) ) {
                        update_post_meta( $post->ID, '_avfr_total_votes', 0 );
                    }

                    if ( !empty( $votes ) ) {
                        update_post_meta( $post->ID, '_avfr_votes', 0 );
                    }
                }

            endif;

            global $wpdb;

            $table = $wpdb->base_prefix.'feature_request';

            $delete = $wpdb->query('TRUNCATE TABLE '.$table.'');

            echo __('All votes reset!','feature-request');

            exit;
        
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' 	=> 'avfr_settings_main',
                'title' => __( 'Setup', 'feature-request' ),
                'desc'  => __( 'Setting up plugin','feature-request' )
            ),
            array(
                'id' 	=> 'avfr_settings_features',
                'title' => __( 'Features', 'feature-request' ),
                'desc'  => __( 'Features settings','feature-request' )
            ),
            array(
                'id'    => 'avfr_settings_mail',
                'title' => __( 'E-Mail', 'feature-request' ),
                'desc'  => __( 'Email settings, you can select when and who should be recieve emails.','feature-request' )
            ),
           	array(
                'id' 	=> 'avfr_settings_advanced',
                'title' => __( 'Advanced', 'feature-request' ),
                'desc'  => __( 'Advanced plugin option','feature-request' )
            ),
            array(
                'id'    => 'avfr_settings_resets',
                'title' => __( 'Reset', 'feature-request' ),
                'desc'  => __( 'Feature request reset','feature-request' )
            )

        );
        return $sections;
    }

    function get_settings_fields() {

		$domain 	= avfr_get_option('avfr_domain','avfr_settings_main','suggestions');

        $settings_fields = array(
            'avfr_settings_main' => array(
            	array(
                    'name' 				=> 'avfr_domain',
                    'label' 			=> __( 'Naming Convention', 'feature-request' ),
                    'desc' 				=> '<a href="'.get_post_type_archive_link( 'avfr' ).'">'. __( 'Link to features page', 'feature-request' ) .'</a> - ' . __( 'You should save permalinks after changing this.', 'feature-request' ),
                    'type' 				=> 'text',
                    'default' 			=> __('suggestions','feature-request'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'name' 				=> 'avfr_welcome',
                    'label' 			=> __( 'Welcome Message', 'feature-request' ),
                    'desc' 				=> __( 'Enter a message to display to users to vote. Some HTML ok.', 'feature-request' ),
                    'type' 				=> 'textarea',
                    'default' 			=> __('Submit and vote for new features!', 'feature-request'),
                    'sanitize_callback' => 'avfr_content_filter'
                ),
                array(
                    'name' 				=> 'avfr_approve_features',
                    'label' 			=> __( 'Require Feature Approval', 'feature-request' ),
                    'desc' 				=> __( 'Check this box to enable newly submitted features to be put into a pending status instead of automatically publishing.', 'feature-request' ),
                    'type'				=> 'checkbox',
                    'default' 			=> ''
                ),
                array(
                    'name' 				=> 'avfr_public_voting',
                    'label' 			=> __( 'Enable Public Voting', 'feature-request' ),
                    'desc' 				=> __( 'Enable the public (non logged in users) to submit and vote on new features.', 'feature-request' ),
                    'type'				=> 'checkbox',
                    'default' 			=> ''
                ),
            	array(
                    'name' 				=> 'avfr_threshold',
                    'label' 			=> __( 'Voting Threshold', 'feature-request' ),
                    'desc' 				=> __( 'Specify an optional number of votes that each feature must reach in order for its status to be automatically updated to "approved" , "declined", or "open."', 'feature-request' ),
                    'type' 				=> 'text',
                    'default' 			=> '',
                ),


                   array(
                    'name'              => 'avfr_disable_upload',
                    'label'             => __( 'Disable Uplaod Files', 'feature-request' ),
                    'desc'              => __( 'Disable upload for form (if checked).', 'feature-request' ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),

                  array(
                    'name'              => 'avfr_disable_captcha',
                    'label'             => __( 'Disable Captcha ', 'feature-request' ),
                    'desc'              => __( 'Disable Captcha code on submit form (if checked).', 'feature-request' ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),

                array(
                    'name'              => 'avfr_voting_type',
                    'label'             => __( 'Select voting type', 'feature-request' ),
                    'desc'              => __( 'Users can vote 1-5 star or can score + and - to any feature.', 'feature-request' ),
                    'type'              => 'radio',
                    'options'           => array('vote' => 'Vote', 'like' => 'Like/Dislike' ),
                    'default'           => 'like'
                ),
                array(
                    'name'              => 'avfr_votes_limitation_time',
                    'label'             => __( 'Select voting limitation time', 'feature-request' ),
                    'desc'              => __( 'Set limit to user vote (one user can vote only 10 time in month by defults change it in groups section)', 'feature-request' ),
                    'type'              => 'radio',
                    'options'           => array('YEAR' => 'Year', 'MONTH' => 'Month', 'WEEK' => 'Week' ),
                    'default'           => 'MONTH'
                ),

                array(
                    'name'              => 'avfr_flag',
                    'label'             => __( 'Show flag in features', 'feature-request' ),
                    'desc'              => __( 'If checked, users can report unpleasant features.', 'feature-request' ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),

                array(
                    'name'              => 'avfr_single',
                    'label'             => __( 'Single page for each feature', 'feature-request' ),
                    'desc'              => __( 'If checked, features has separate single page and permalink goes activate!.', 'feature-request' ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),

            ),
                /**
                 *features setting:upload,filesize,maximum character in title.
                 */
            'avfr_settings_features' 	=> array(
                /**
                 *Allowed file types
                 */
                array(
                    'name' 				=> 'avfr_allowed_file_types',
                    'label' 			=> __( 'Allowed file types', 'feature-request' ),
                    'desc' 				=> __( 'Enter file upload format that you want with above format', 'feature-request' ),
                    'type'				=> 'text',
                    'default' 			=> __('image/jpeg,image/jpg','feature-request'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                /**
                 *Maximum file size allowed
                 */
                array(
                    'name' 				=> 'avfr_max_file_size',
                    'label' 			=> __( 'Maximum allowed file size', 'feature-request' ),
                    'desc' 				=> __( 'Please enter maximum file size that user can be upload ! (Size Calcute in KB).', 'feature-request'  ),
                    'type'				=> 'text',
                    'default' 			=> '1024', // KB
                ),
                /**
                 *
                 *echo the explanation about size and type
                 *
                 */
                 array(
                    'name'              => 'avfr_echo_type_size',
                    'label'             => __( 'Tip upload Massage', 'feature-request' ),
                    'desc'              => __( 'Explain for your customer about image size and type that they can upload!', 'feature-request'  ),
                    'type'              => 'text',
                    'default'           => __('Please uplaod image file with jpg format >1024 KB size!', 'feature-request'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                 /**
                 *Number of related features to show
                 */
                array(
                    'name'              => 'avfr_related_feature_num',
                    'label'             => __( 'Number of related feature', 'feature-request' ),
                    'desc'              => __( 'Show familiar features (Enter 0 for disabling related feature show only in single page.)', 'feature-request' ),
                    'type'              => 'text',
                    'default'           => __( '3', 'feature-request' ),
                ),
            ),
            // setting for mail options (section : mail)
            // send_mail_status_reciever
            // mail_content_status_reciver
            'avfr_settings_mail' 	=> array(
                array(
                    'name'              => 'avfr_send_mail_approved_writer',
                    'label'             => __( 'Send mail if approved', 'feature-request' ),
                    'desc'              => __( 'If feature gone be approved, feature submitter will be inform via mail', 'feature-request'  ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),
                array(
                    'name'              => 'avfr_mail_content_approved_writer',
                    'label'             => __( 'Text will be sent', 'feature-request' ),
                    'type'              => 'textarea',
                    'default'           => '',
                    'desc'              => __('Above text will sent to feature submitter if feature gone approved!'),
                    'sanitize_callback' => 'esc_textarea'
                ),
                array(
                    'name'              => 'avfr_send_mail_approved_voters',
                    'label'             => __( 'Send mail to Voters', 'feature-request' ),
                    'desc'              => __( 'Send email to feature voters when feature approved.', 'feature-request'  ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),
                array(
                    'name'              => 'avfr_mail_content_approved_voters',
                    'label'             => __( 'Content (approved feature ) (voters)', 'feature-request' ),
                    'type'              => 'textarea',
                    'default'           => '',
                    'sanitize_callback' => 'esc_textarea'
                ),
                array(
                    'name'              => 'avfr_send_mail_completed_writer',
                    'label'             => __( 'To writer if completed', 'feature-request' ),
                    'desc'              => __( 'Send email to feature writer when feature completed.', 'feature-request'  ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),
                array(
                    'name'              => 'avfr_mail_content_completed_writer',
                    'label'             => __( 'Content (completed feature ) (writer)', 'feature-request' ),
                    'type'              => 'textarea',
                    'default'           => '',
                    'sanitize_callback' => 'esc_textarea'
                ),
                array(
                    'name'              => 'avfr_send_mail_completed_voters',
                    'label'             => __( 'To voters if approved', 'feature-request' ),
                    'desc'              => __( 'Send email to feature voters when feature completed.', 'feature-request'  ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),
                array(
                    'name'              => 'avfr_mail_content_completed_voters',
                    'label'             => __( 'Content (completed feature ) (voters)', 'feature-request' ),
                    'type'              => 'textarea',
                    'default'           => '',
                    'sanitize_callback' => 'esc_textarea'
                ),
                array(
                    'name'              => 'avfr_send_mail_declined_writer',
                    'label'             => __( 'To writer if declined', 'feature-request' ),
                    'desc'              => __( 'Send email to feature writer when feature declined.', 'feature-request'  ),
                    'type'              => 'checkbox',
                    'default'           => '',
                ),
                array(
                    'name'              => 'avfr_mail_content_declined_writer',
                    'label'             => __( 'Content (declined feature ) (writer)', 'feature-request' ),
                    'type'              => 'textarea',
                    'default'           => '',
                    'sanitize_callback' => 'esc_textarea'
                ),
                array(
                    'name'              => 'avfr_send_mail_declined_voters',
                    'label'             => __( 'To voters if declined', 'feature-request' ),
                    'desc'              => __( 'Send email to feature voters when feature declined.', 'feature-request'  ),
                    'type'              => 'checkbox',
                    'default'           => ''
                ),
                array(
                    'name'              => 'avfr_mail_content_declined_voters',
                    'label'             => __( 'Content (declined feature ) (voters)', 'feature-request' ),
                    'type'              => 'textarea',
                    'default'           => '',
                    'sanitize_callback' => 'esc_textarea'
                ),
            ),
            'avfr_settings_advanced' 	=> array(
            	array(
                    'name' 				=> 'avfr_disable_css',
                    'label' 			=> __( 'Disable Core CSS', 'feature-request' ),
                    'desc' 				=> __( 'Disable the core css file from loading.', 'feature-request' ),
                    'type'				=> 'checkbox',
                    'default' 			=> ''
                ),
                 array(
                    'name' 				=> 'avfr_disable_mail',
                    'label' 			=> __( 'Disable Emails', 'feature-request' ),
                    'desc' 				=> __( 'Disable the admin email notification of new submissions.', 'feature-request' ),
                    'type'				=> 'checkbox',
                    'default' 			=> ''
                ),
            ),
            'avfr_settings_resets'    => array(
                array(
                    'name'              => 'avfr_set_resets',
                    'label'             => __( 'Reset All Votes', 'feature-request' ),
                    'desc'              => __( '<a class="button feature-request-reset-votes" href="#" >Reset Votes</a>' ),
                    'type'              => 'html',
                    'default'           => ''
                )
            )
        );

        return $settings_fields;
    }


}
endif;

$settings = new Avfr_Settings();





function avfr_groups_edit_form_fields( $tag ) {

    $max_votes = $total_votes = $comments_disabled = $new_disabled = '';

    if ( is_object( $tag ) ) {

        $term_id = $tag->term_id;
        $max_votes = get_term_meta( $term_id, 'avfr_max_votes', true );
        $total_votes = get_term_meta( $term_id, 'avfr_total_votes', true);
        $comments_disabled = 'on' === get_term_meta( $term_id, 'avfr_comments_disabled', true ) ? 'checked' : '';
        $new_disabled = 'on' === get_term_meta( $term_id, 'avfr_new_disabled', true ) ? 'checked' : '';

    }

?>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="max-votes"><?php _e( 'Maximum votes :', 'averta-envato' ); ?></label>
        </th>
        <td>
            <input type="number" id="max-votes" name="max-votes" placeholder="3" maxlength="6" min="1" value="<?php echo esc_attr( $max_votes ); ?>">
            <p class="description"><?php _e( 'Maximum votes that user can vote in each feature request in this group.', 'averta-envato' ); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="total-votes"><?php _e( 'Total votes :', 'averta-envato' ); ?></label>
        </th>
        <td>
            <input type="number" id="total-votes" name="total-votes" placeholder="30" maxlength="6" min="1" value="<?php echo esc_attr( $total_votes ); ?>">
            <p class="description"><?php _e( 'Total votes that user can vote in this group in certain time that set in plugin settings.', 'averta-envato' ); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="cm-disabled"><?php _e( 'Disable comments', 'averta-envato' ); ?></label>
        </th>
        <td>
            <input type="checkbox" id="cm-disabled" name="cm-disabled" <?php echo esc_attr( $comments_disabled ); ?>>
            <p class="description"><?php _e( 'Disable comments in this group.', 'averta-envato' ); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="new-disabled"><?php _e( 'Disable new', 'averta-envato' ); ?></label>
        </th>
        <td>
            <input type="checkbox" id="new-disabled" name="new-disabled" <?php echo esc_attr( $new_disabled ); ?>>
            <p class="description"><?php _e( 'Disable posting new feature request submition in this groups.', 'averta-envato' ); ?></p>
        </td>
    </tr>
    <?php 
}

add_action('groups_edit_form_fields', 'avfr_groups_edit_form_fields');
add_action('groups_add_form_fields', 'avfr_groups_edit_form_fields');


function avfr_save_groups_custom_meta( $term_id ) {

    $max_votes_val = isset( $_POST['max-votes'] ) ? $_POST['max-votes'] : 0;
    $total_votes_val = isset( $_POST['total-votes'] ) ? $_POST['total-votes'] : 0;

    $max_votes = abs($max_votes_val);
    $total_votes = abs($total_votes_val);
    $comments_disabled = isset( $_POST['cm-disabled'] ) ? 'on' : 'off' ;
    $new_disabled = isset( $_POST['new-disabled'] ) ? 'on' : 'off' ;

    update_term_meta( $term_id, 'avfr_max_votes', $max_votes );
    update_term_meta( $term_id, 'avfr_total_votes', $total_votes );
    update_term_meta( $term_id, 'avfr_comments_disabled', $comments_disabled );
    update_term_meta( $term_id, 'avfr_new_disabled', $new_disabled );

}

add_action( 'edited_groups', 'avfr_save_groups_custom_meta', 10, 2 );
add_action( 'create_groups', 'avfr_save_groups_custom_meta', 10, 2 );