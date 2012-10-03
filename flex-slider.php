<?php
/**
 * Plugin Name: Inpixelitrust Flex Slider
 * Plugin URI: 
  * Description: A responsive Image slider, based on the <a href="http://flexslider.woothemes.com/index.html">FlexSlider2</a>. See settings for configuration !
 * Version: 1.0
 * Author: 
 * Author URI: 
 *
 */

/* activation hook. */
register_activation_hook( __FILE__, 'flex_slider_activation' );
	
/* dectivation hook. */
register_deactivation_hook( __FILE__, 'flex_slider_deactivation' );

/* uninstall hook. */
register_uninstall_hook( __FILE__, 'flex_slider_uninstall' );



function flex_slider_activation() {	
	flex_slider_default_settings();	
}
function flex_slider_deactivation() {
    flush_rewrite_rules();
}
function flex_slider_uninstall() {
	delete_option( 'flex_slider_options' );	
}


/* Load translations on the backend. */
if ( is_admin() )
		load_plugin_textdomain( 'flex_slider', false, plugins_url( '/languages' , __FILE__ ) );


/***** Here come our CPT *****************/

/* Register the custom post types. */
	add_action( 'init', 'flex_slider_cpt' );
	
/** Register the CPT ****/	
function flex_slider_cpt() {
	
	$labels = array(
		'name'                 => __( 'Flex Slide ', 'flex_slider' ),
		'singular_name'        => __( 'Flex Slides', 'flex_slider' ),
		'all_items'            => __( 'All Slides', 'flex_slider' ),
		'add_new'              => __( 'Add New Slide', 'flex_slider' ),
		'add_new_item'         => __( 'Add New  Slide', 'flex_slider' ),
		'edit_item'            => __( 'Edit Slide', 'flex_slider' ),
		'new_item'             => __( 'New Slide', 'flex_slider' ),
		'view_item'            => __( 'View Slide', 'flex_slider' ),
		'search_items'         => __( 'Search Slides', 'flex_slider' ),
		'not_found'            => __( 'No Slide found', 'flex_slider' ),
		'not_found_in_trash'   => __( 'No Slide found in Trash', 'flex_slider' ), 
		'parent_item_colon'    => ''
	);
	
	$args = array(
		'labels'               => $labels,
		'public'               => true,
		'publicly_queryable'   => true,
		'_builtin'             => false,
		'show_ui'              => true, 
		'query_var'            => true,
		'rewrite'              => array( "slug" => "flexi-slides" ),
		'capability_type'      => 'post',
		'hierarchical'         => false,
		'menu_position'        => 20,
		'supports'             => array( 'title','thumbnail', 'page-attributes' ),
		'taxonomies'           => array(),
		'has_archive'          => true,
		'show_in_nav_menus'    => false
	);
	
	register_post_type( 'flexi-slides', $args );
}




/**** Add some meta boxes ******/

add_action( 'add_meta_boxes', 'flexic_meta_box_add' );
function flexic_meta_box_add(){
	add_meta_box( 'img-url', __( 'Image link', 'flex_slider' ), 'flexic_meta_box_cb', 'flexi-slides', 'advanced', 'high' );
}

function flexic_meta_box_cb( $post ){
	$values = get_post_custom( $post->ID );
	$url_img = isset( $values['url_metabox'] ) ? esc_attr( $values['url_metabox'][0] ) : '';	
	$url_title = isset( $values['title_metabox'] ) ? esc_attr( $values['title_metabox'][0] ) : '';	
	wp_nonce_field( 'my_meta_box_nonce', 'meta_box_nonce' );
	?>
	<p>
		<label for="url_metabox"><?php _e( 'URL:', 'flex_slider' );?> </label>
		<input type="text" style="width: 90%;" name="url_metabox" id="url_metabox" value="<?php echo $url_img; ?>" />
			</p>
		<p><span class="description"><?php _e( 'The URL this image should link to.', 'flex_slider' );?></span>
	</p>
	<p>
		<label for="title_metabox"><?php _e( 'Title:', 'flex_slider' );?> </label>
		<input type="text" style="width: 90%;" name="title_metabox" id="title_metabox" value="<?php echo $url_title; ?>" />
			</p>
		<p><span class="description"><?php _e( 'Title when you hover the link', 'flex_slider' );?></span>
	</p>	
	<?php	
}

/*** Save the boxes ******/
add_action( 'save_post', 'flexic_meta_box_save' );
function flexic_meta_box_save( $post_id ){
	// Bail if we're doing an auto save
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;	
	// if our nonce isn't there, or we can't verify it, bail
	if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return;	
	// if our current user can't edit this post, bail
	if( !current_user_can( 'edit_post' ) ) return;	
	// Probably a good idea to make sure your data is set
	if( isset( $_POST['url_metabox'] ) )
		update_post_meta( $post_id, 'url_metabox', wp_kses( $_POST['url_metabox']) );

	if( isset( $_POST['title_metabox'] ) )
		update_post_meta( $post_id, 'title_metabox', wp_kses( $_POST['title_metabox'] ) );
	
}

/** change the mextaboxes *-**/

add_action('do_meta_boxes', 'flex_slider_change_metaboxes');
function flex_slider_change_metaboxes() {	
    remove_meta_box( 'postimagediv', 'flexi-slides', 'side' );
	remove_meta_box( 'pageparentdiv', 'flexi-slides', 'side' );
    add_meta_box('postimagediv', __('Slider Images', 'flex_slider' ), 'post_thumbnail_meta_box', 'flexi-slides', 'normal', 'high');
	add_meta_box('pageparentdiv', __('Order of images', 'flex_slider' ), 'page_attributes_meta_box', 'flexi-slides', 'side', 'low');
}

/*** add a settings page !! ***/

/* Add 'Settings' submenu to 'Slides'.*/
	add_action('admin_menu', 'flex_slider_settings');	
	/* Register and define the slider settings. */
	add_action( 'admin_init', 'flex_slider_settings_init' );

/**
 * Add 'Settings' submenu to 'Slides'. 
 */
function flex_slider_settings() {
	add_submenu_page( 'edit.php?post_type=flexi-slides', __( 'Slider Settings', 'flexi-slides' ), __( 'Settings', 'flexi-slides' ), 'manage_options', 'flex-slider-settings', 'flex_slider_settings_page' );
}

/**
 * Create the Slider Settings page.
 */
function flex_slider_settings_page() { 	?>

<div class="wrap">
	<?php screen_icon( 'plugins' ); ?>
		<h2><?php _e( 'Flex Slider Settings', 'flexi-slides' ); ?></h2>
		
		<form method="post" action="options.php">
			<?php settings_fields( 'flex_slider_options' ); ?>
			<?php do_settings_sections( 'flex-slider-settings' ); ?>
			<br /><p><input type="submit" name="Submit" value="<?php _e( 'Update Settings', 'flexi-slides' ); ?>" class="button-primary" /></p>			
		</form>		
	</div>
<?php }

function flex_slider_settings_init() {	
	/* Register the slider settings. */
	register_setting( 'flex_slider_options', 'flex_slider_options', 'flex_slider_validate_options' );	
	/* Add settings section. */
	add_settings_section( 'flex_slider_options_main', __( ' ', 'flexi-slides' ), 'flex_slider_section_text', 'flex-slider-settings' );	


	/* Add settings fields. */
	add_settings_field( 'flex_animation', __( 'flex_animation type:', 'flexi-slides' ), 'flex_animation', 'flex-slider-settings', 'flex_slider_options_main' );

	add_settings_field( 'flex_animationLoop', __( 'Should the flex_animation loop? <span class="description">(If No, flex_directionNav will received "disable" classes at either end)</span>', 'flexi-slides' ), 'flex_animationLoop', 'flex-slider-settings', 'flex_slider_options_main' );	

   add_settings_field( 'flex_smoothHeight', __( 'Smooth height <span class="description">(animate on horizontal height)</span>:', 'flexi-slides' ), 'flex_smoothHeight', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'flex_slideshow_auto', __( ' Automatic slideshow', 'flexi-slides' ), 'flex_slideshow_auto', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'flex_controlNav', __( 'Display bottom navigation pagination', 'flexi-slides' ), 'flex_controlNav', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'flex_directionNav', __( 'Create navigation for previous/next navigation?', 'flexi-slides' ), 'flex_directionNav', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'flex_prevText', __( 'Create navigation for previous/next navigation?', 'flexi-slides' ), 'flex_prevText', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'flex_nextText', __( 'Set the text for the "next" flex_directionNav item', 'flexi-slides' ), 'flex_nextText', 'flex-slider-settings', 'flex_slider_options_main' );

	/* Add settings section. */
	add_settings_section( 'flex_slider_options_carousel', __( ' ', 'flexi-slides' ), 'flex_slider_carousel', 'flex-slider-settings' );	
	
	/** Enable carousel**/
	add_settings_field( 'flex_enable_carousel', __( 'Enable carousel?', 'flexi-slides' ), 'flex_enable_carousel', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'flex_itemWidth', __( 'Width of the images:', 'flexi-slides' ), 'flex_itemWidth', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'flex_itemMargin', __( 'Margin between items:', 'flexi-slides' ), 'flex_itemMargin', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'flex_enable_range', __( 'Add With Min & Max Range?', 'flexi-slides' ), 'flex_enable_range', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'flex_minItems', __( 'Minimum number of item visible:', 'flexi-slides' ), 'flex_minItems', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'flex_maxItems', __( 'Maximum number of items visible:', 'flexi-slides' ), 'flex_maxItems', 'flex-slider-settings', 'flex_slider_options_carousel' );
	
}
	
/* Output the section header text. */
function flex_slider_section_text() {
	echo '<h3>' . __( ' General settings for all sliders ', 'flexi-slides' ) . '</h3>';
}
function flex_slider_carousel() {
	echo '<h3>' . __( 'Use the settings above if you want to use a carousel ', 'flexi-slides' ) . '</h3>';
	echo '<p class="description"> <strong>' . __( 'Note that the transition must be set to slide in order for the carousel to work', 'flexi-slides' ) . '</strong></p>';
}

function flex_itemWidth() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_itemWidth = $options['flex_itemWidth'];	
	/* Echo the field. */ ?>
	<input type="text" id="flex_itemWidth" name="flex_slider_options[flex_itemWidth]" value="<?php echo esc_attr($flex_itemWidth); ?>" /> <span class="description"><?php _e( 'px', 'flexi-slides' ); ?></span>
	
<?php }

function flex_itemMargin() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_itemMargin = $options['flex_itemMargin'];	
	/* Echo the field. */ ?>
	<input type="text" id="flex_itemMargin" name="flex_slider_options[flex_itemMargin]" value="<?php echo esc_attr($flex_itemMargin); ?>" /> <span class="description"><?php _e( 'px', 'flexi-slides' ); ?></span>	
<?php }


function flex_minItems() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_minItems = $options['flex_minItems'];	
	/* Echo the field. */ ?>
	<input type="text" id="flex_minItems" name="flex_slider_options[flex_minItems]" value="<?php echo esc_attr($flex_minItems); ?>" /> 
<?php }

function flex_maxItems() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_maxItems = $options['flex_maxItems'];	
	/* Echo the field. */ ?>
	<input type="text" id="flex_maxItems" name="flex_slider_options[flex_maxItems]" value="<?php echo esc_attr($flex_maxItems); ?>" /> 
<?php }


function flex_prevText() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_prevText = $options['flex_prevText'];	
	/* Echo the field. */ ?>
	<input type="text" id="flex_prevText" name="flex_slider_options[flex_prevText]" value="<?php echo esc_attr($flex_prevText); ?>" /> 
<?php }


function flex_nextText() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_nextText = $options['flex_nextText'];	
	/* Echo the field. */ ?>
	<input type="text" id="flex_nextText" name="flex_slider_options[flex_nextText]" value="<?php echo esc_attr($flex_nextText); ?>" /> 
<?php }

function flex_animation() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_animation = $options['flex_animation'];
	
	/* Echo the field. */
	echo "<select id='flex_animation' name='flex_slider_options[flex_animation]'>";
	echo '<option value="fade" ' . selected( $flex_animation, 'fade', false ) . ' >' . __( 'fade', 'flexi-slides' ) . '</option>';
	echo '<option value="slide" ' . selected( $flex_animation, 'slide', false ) . ' >' . __( 'slide', 'flexi-slides' ) . '</option>';
	echo '</select>';	
}

function flex_animationLoop() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_animationLoop = $options['flex_animationLoop'];	
	/* Echo the field. */
   
	/* Echo the field. */ ?>
	<label for="flex_animationLoop_true" > <?php _e( ' True', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($flex_animationLoop == "true") echo'checked="checked"' ; ?> id="flex_animationLoop_true" name="flex_slider_options[flex_animationLoop]" value="true" /> 
	<label for="flex_animationLoop_false" > <?php _e( ' False', 'flexi-slides' ); ?></label>
	<input type="radio" id="flex_animationLoop_false" <?php if ($flex_animationLoop == "false") echo'checked="checked"' ; ?> name="flex_slider_options[flex_animationLoop]" value="false" /> 
	<?php
}
function flex_enable_carousel() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_enable_carousel = $options['flex_enable_carousel'];	
 
	/* Echo the field. */ ?>
	<label for="flex_enable_carousel_true" > <?php _e( ' Yes', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($flex_enable_carousel == "yes") echo'checked="checked"' ; ?> id="flex_enable_carousel_true" name="flex_slider_options[flex_enable_carousel]" value="yes" /> 
	<label for="flex_enable_carousel_false" > <?php _e( ' No', 'flexi-slides' ); ?></label>
	<input type="radio" id="flex_enable_carousel_false" <?php if ($flex_enable_carousel == "no") echo'checked="checked"' ; ?> name="flex_slider_options[flex_enable_carousel]" value="no" /> 
	<?php
}

function flex_enable_range() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_enable_range = $options['flex_enable_range'];	

	/* Echo the field. */ ?>
	<label for="flex_enable_range_true" > <?php _e( ' Yes', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($flex_enable_range == "yes") echo'checked="checked"' ; ?> id="flex_enable_range_true" name="flex_slider_options[flex_enable_range]" value="yes" /> 
	<label for="flex_enable_range_false" > <?php _e( ' No', 'flexi-slides' ); ?></label>
	<input type="radio" id="flex_enable_range_false" <?php if ($flex_enable_range == "no") echo'checked="checked"' ; ?> name="flex_slider_options[flex_enable_range]" value="no" /> 
	<?php
}

function flex_smoothHeight() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_smoothHeight = $options['flex_smoothHeight'];	

	/* Echo the field. */ ?>
	<label for="flex_smoothHeight_true" > <?php _e( ' True', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($flex_smoothHeight == "true") echo'checked="checked"' ; ?> id="flex_smoothHeight_true" name="flex_slider_options[flex_smoothHeight]" value="true" /> 
	<label for="flex_smoothHeight_false" > <?php _e( ' False', 'flexi-slides' ); ?></label>
	<input type="radio" id="flex_smoothHeight_false" <?php if ($flex_smoothHeight == "false") echo'checked="checked"' ; ?> name="flex_slider_options[flex_smoothHeight]" value="false" /> 
	<?php
}

function flex_slideshow_auto() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_slideshow_auto = $options['flex_slideshow_auto'];	

	/* Echo the field. */ ?>
	<label for="flex_slideshow_auto_true" > <?php _e( ' True', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($flex_slideshow_auto == "true") echo'checked="checked"' ; ?> id="flex_slideshow_auto_true" name="flex_slider_options[flex_slideshow_auto]" value="true" /> 
	<label for="flex_slideshow_auto_false" > <?php _e( ' False', 'flexi-slides' ); ?></label>
	<input type="radio" id="flex_slideshow_auto_false" <?php if ($flex_slideshow_auto == "false") echo'checked="checked"' ; ?> name="flex_slider_options[flex_slideshow_auto]" value="false" /> 
	<?php
}

function flex_controlNav() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_controlNav = $options['flex_controlNav'];	

	/* Echo the field. */ ?>
	<label for="flex_controlNav_true" > <?php _e( 'Yes', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($flex_controlNav == "true") echo'checked="checked"' ; ?> id="flex_controlNav_true" name="flex_slider_options[flex_controlNav]" value="true" /> 
	<label for="autoflex_animation_false" > <?php _e( 'No', 'flexi-slides' ); ?></label>
	<input type="radio" id="flex_controlNav_false" <?php if ($flex_controlNav == "false") echo'checked="checked"' ; ?> name="flex_slider_options[flex_controlNav]" value="false" /> 
	<?php
}

function flex_directionNav() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$flex_directionNav = $options['flex_directionNav'];	

	/* Echo the field. */ ?>
	<label for="flex_directionNav_true" > <?php _e( 'Yes', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($flex_directionNav == "true") echo'checked="checked"' ; ?> id="flex_directionNav_true" name="flex_slider_options[flex_directionNav]" value="true" /> 
	<label for="autoflex_animation_false" > <?php _e( 'No', 'flexi-slides' ); ?></label>
	<input type="radio" id="flex_directionNav_false" <?php if ($flex_directionNav == "false") echo'checked="checked"' ; ?> name="flex_slider_options[flex_directionNav]" value="false" /> 
	<?php
}


/**
 * Validate and/or sanitize user input.
 */
function flex_slider_validate_options( $input ) {	

	$options = get_option( 'flex_slider_options' );	
	$options['flex_animationLoop'] = wp_filter_nohtml_kses( $input['flex_animationLoop'] );
	$options['flex_animation'] = wp_filter_nohtml_kses( $input['flex_animation'] );
	$options['flex_itemWidth'] = wp_filter_nohtml_kses( intval( $input['flex_itemWidth'] ) );	
	$options['flex_itemMargin'] = wp_filter_nohtml_kses( intval( $input['flex_itemMargin'] ) );
	$options['flex_minItems'] = wp_filter_nohtml_kses( intval( $input['flex_minItems'] ) );
	$options['flex_maxItems'] = wp_filter_nohtml_kses( intval( $input['flex_maxItems'] ) );
	$options['flex_enable_carousel'] = wp_filter_nohtml_kses( $input['flex_enable_carousel'] );
	$options['flex_enable_range'] = wp_filter_nohtml_kses( $input['flex_enable_range'] );	
	$options['flex_smoothHeight'] = wp_filter_nohtml_kses( $input['flex_smoothHeight'] );	
	$options['flex_slideshow_auto'] = wp_filter_nohtml_kses( $input['flex_slideshow_auto'] );	
	$options['flex_controlNav'] = wp_filter_nohtml_kses( $input['flex_controlNav'] );	
	$options['flex_directionNav'] = wp_filter_nohtml_kses( $input['flex_directionNav'] );	
	$options['flex_prevText'] = wp_filter_nohtml_kses( $input['flex_prevText'] );	
	$options['flex_nextText'] = wp_filter_nohtml_kses( $input['flex_nextText'] );	

	return $options;
}


/**** activation default settings ***/
function flex_slider_default_settings() {

	/* Retrieve exisitng options, if any. */
	$ex_options = get_option( 'flex_slider_options' );
	
	/* Check if options are set. Add default values if not. */ 
	if ( !is_array( $ex_options ) ) {

		$default_options = array(	
			'flex_animation'     => "fade",
			'flex_animationLoop'    => "true",
			'flex_slideshow_auto' => "true",
			'flex_smoothHeight'    => "false",
			'flex_controlNav'     => "true",
			'flex_directionNav'  => "true",
			'flex_prevText'     => "Previous",
			'flex_nextText'		=> "Next",
			'flex_enable_carousel' => "no",
			'flex_enable_range' => "no"
		);	
		
		/* Set the default options. */
		update_option( 'flex_slider_options', $default_options );
	}	
}





/** create a shortcode !! **/
add_shortcode( 'flex-slider', 'flex_slider_shortcode' );

function flex_slider_shortcode() {	
	$flex_slider = flex_slider_slider();
	return $flex_slider;
}

/***** Here comes the HTML rendering, owiiii ***/
function flex_slider_slider() {
	$options = get_option( 'flex_slider_options' );	
	$flex_enable_carousel = $options['flex_enable_carousel'];

	?>
	<div class="flexslider <?php if($flex_enable_carousel == 'yes') echo 'carousel' ; ?>">
        <ul class="slides">        	
    <?php	
	$args = array( 'post_type' => 'flexi-slides', 'posts_per_page' => 10 );
	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) : $loop->the_post();
	$values = get_post_custom( $post->ID );	
	$url_img = isset( $values['url_metabox'] ) ? esc_url( $values['url_metabox'][0] ) : '';
	$url_title = isset( $values['title_metabox'] ) ? esc_attr( $values['title_metabox'][0] ) : '';	
		echo '<li>';
	 	global $post;
	 	if ($url_img) {
	 		echo '<a href="'.$url_img.'" title="'.$url_title.'" >';
	 	}
		if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'slide-thumbnail' ); ?>
		<?php endif; 

		if ($url_img) {
			echo '</a>';
		}	
		echo '</li>';
	    endwhile;
	?>
	</ul>
        </div>
    <?php
}


/* javaScript. */
add_action( 'wp_enqueue_scripts', 'flex_slider_enqueue_scripts' );

function flex_slider_enqueue_scripts() {	
	/* Enqueue script. */
	wp_enqueue_script( 'flexi-slider', plugins_url('/jquery.flexslider.js', __FILE__), array( 'jquery' ), 0.1, true );
}

add_action( 'wp_print_footer_scripts', 'flex_print_footer_scripts' );


function flex_print_footer_scripts() {
	$options = get_option( 'flex_slider_options' );
	
	$flex_animation       = $options['flex_animation'];
	$flex_animationLoop   = $options['flex_animationLoop'];
	$flex_smoothHeight    = $options['flex_smoothHeight'];	
	$flex_slideshow_auto  = $options['flex_slideshow_auto'];	
	$flex_controlNav      = $options['flex_controlNav'];	
	$flex_directionNav    = $options['flex_directionNav'];
	$flex_prevText        = $options['flex_prevText'];	
	$flex_nextText        = $options['flex_nextText'];	
	$flex_enable_carousel = $options['flex_enable_carousel'];
	$flex_itemWidth       = $options['flex_itemWidth'];
	$flex_itemMargin      = $options['flex_itemMargin'];
	$flex_enable_range    = $options['flex_enable_range'];
	$flex_minItems        = $options['flex_minItems'];
	$flex_maxItems        = $options['flex_maxItems'];		

	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
      $('.flexslider').flexslider({
      	flex_animation: "<?php echo $flex_animation ; ?>"
	    ,flex_animationLoop: <?php echo $flex_animationLoop ; ?>
	    ,flex_smoothHeight:<?php echo $flex_smoothHeight ; ?>
	    ,slideshow:<?php echo $flex_slideshow_auto ; ?>
	    ,flex_controlNav:<?php echo $flex_controlNav ; ?> 
	    ,flex_directionNav:<?php echo $flex_directionNav ; ?>	 
	    <?php if ($flex_directionNav == "true"){ ?>
	    
	    ,flex_prevText: "<?php echo esc_js($flex_prevText) ; ?>"
	    
	    ,flex_prevText: "<?php echo esc_js($flex_nextText) ; ?>"
	    <?php } ?>
	    <?php if ($flex_enable_carousel == "yes"){ ?>
	    ,flex_itemWidth: <?php echo esc_js($flex_itemWidth) ; ?>
	    
	    ,flex_itemMargin: <?php echo esc_js($flex_itemMargin) ; ?>
	    <?php } ?>
	    <?php if ($flex_enable_range == "yes"){ ?>
	    
	    ,flex_minItems: <?php echo esc_js($flex_minItems) ; ?>
	    
	    ,flex_maxItems: <?php echo esc_js($flex_maxItems) ; ?>
	    <?php } ?>
	    
     });
  });
	
	</script>
	<?php
 }

/** stylesheet **/
add_action( 'wp_enqueue_scripts', 'flex_add_my_stylesheet' );

    function flex_add_my_stylesheet() {
        wp_register_style( 'prefix-style', plugins_url('flexslider.css', __FILE__) );
        wp_enqueue_style( 'prefix-style' );
}

?>