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
	add_settings_field( 'animation', __( 'Animation type:', 'flexi-slides' ), 'animation', 'flex-slider-settings', 'flex_slider_options_main' );

	add_settings_field( 'animationLoop', __( 'Should the animation loop? <span class="description">(If No, directionNav will received "disable" classes at either end)</span>', 'flexi-slides' ), 'animationLoop', 'flex-slider-settings', 'flex_slider_options_main' );	

   add_settings_field( 'smoothHeight', __( 'Smooth height <span class="description">(animate on horizontal height)</span>:', 'flexi-slides' ), 'smoothHeight', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'slideshow_auto', __( ' Automatic slideshow', 'flexi-slides' ), 'slideshow_auto', 'flex-slider-settings', 'flex_slider_options_main' );

  add_settings_field( 'controlNav', __( 'Display bottom navigation pagination', 'flexi-slides' ), 'controlNav', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'directionNav', __( 'Create navigation for previous/next navigation?', 'flexi-slides' ), 'directionNav', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'prevText', __( 'Create navigation for previous/next navigation?', 'flexi-slides' ), 'prevText', 'flex-slider-settings', 'flex_slider_options_main' );

   add_settings_field( 'nextText', __( 'Set the text for the "next" directionNav item', 'flexi-slides' ), 'nextText', 'flex-slider-settings', 'flex_slider_options_main' );


	/* Add settings section. */
	add_settings_section( 'flex_slider_options_carousel', __( ' ', 'flexi-slides' ), 'flex_slider_carousel', 'flex-slider-settings' );	
	/** Enable carousel**/
	add_settings_field( 'enable_carousel', __( 'Enable carousel?', 'flexi-slides' ), 'enable_carousel', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'itemWidth', __( 'Width of the images:', 'flexi-slides' ), 'itemWidth', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'itemMargin', __( 'Margin between items:', 'flexi-slides' ), 'itemMargin', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'enable_range', __( 'Add With Min & Max Range?', 'flexi-slides' ), 'enable_range', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'minItems', __( 'Minimum number of item visible:', 'flexi-slides' ), 'minItems', 'flex-slider-settings', 'flex_slider_options_carousel' );

	add_settings_field( 'maxItems', __( 'Maximum number of items visible:', 'flexi-slides' ), 'maxItems', 'flex-slider-settings', 'flex_slider_options_carousel' );
	
}
	
/* Output the section header text. */
function flex_slider_section_text() {
	echo '<h3>' . __( ' General settings for all sliders ', 'flexi-slides' ) . '</h3>';
}
function flex_slider_carousel() {
	echo '<h3>' . __( 'Use the settings above if you want to use a carousel ', 'flexi-slides' ) . '</h3>';
	echo '<p class="description"> <strong>' . __( 'Note that the transition must be set to slide in order for the carousel to work', 'flexi-slides' ) . '</strong></p>';
}

function itemWidth() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$itemWidth = $options['itemWidth'];	
	/* Echo the field. */ ?>
	<input type="text" id="itemWidth" name="flex_slider_options[itemWidth]" value="<?php echo esc_attr($itemWidth); ?>" /> <span class="description"><?php _e( 'px', 'flexi-slides' ); ?></span>
	
<?php }

function itemMargin() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$itemMargin = $options['itemMargin'];	
	/* Echo the field. */ ?>
	<input type="text" id="itemMargin" name="flex_slider_options[itemMargin]" value="<?php echo esc_attr($itemMargin); ?>" /> <span class="description"><?php _e( 'px', 'flexi-slides' ); ?></span>	
<?php }


function minItems() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$minItems = $options['minItems'];	
	/* Echo the field. */ ?>
	<input type="text" id="minItems" name="flex_slider_options[minItems]" value="<?php echo esc_attr($minItems); ?>" /> 
<?php }

function maxItems() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$maxItems = $options['maxItems'];	
	/* Echo the field. */ ?>
	<input type="text" id="maxItems" name="flex_slider_options[maxItems]" value="<?php echo esc_attr($maxItems); ?>" /> 
<?php }


function prevText() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$prevText = $options['prevText'];	
	/* Echo the field. */ ?>
	<input type="text" id="prevText" name="flex_slider_options[prevText]" value="<?php echo esc_attr($prevText); ?>" /> 
<?php }


function nextText() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$nextText = $options['nextText'];	
	/* Echo the field. */ ?>
	<input type="text" id="nextText" name="flex_slider_options[nextText]" value="<?php echo esc_attr($nextText); ?>" /> 
<?php }

function animation() {	
	/* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$animation = $options['animation'];
	
	/* Echo the field. */
	echo "<select id='animation' name='flex_slider_options[animation]'>";
	echo '<option value="fade" ' . selected( $animation, 'fade', false ) . ' >' . __( 'fade', 'flexi-slides' ) . '</option>';
	echo '<option value="slide" ' . selected( $animation, 'slide', false ) . ' >' . __( 'slide', 'flexi-slides' ) . '</option>';
	echo '</select>';	
}

function animationLoop() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$animationLoop = $options['animationLoop'];	
	/* Echo the field. */
   
	/* Echo the field. */ ?>
	<label for="animationLoop_true" > <?php _e( ' True', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($animationLoop == "true") echo'checked="checked"' ; ?> id="animationLoop_true" name="flex_slider_options[animationLoop]" value="true" /> 
	<label for="animationLoop_false" > <?php _e( ' False', 'flexi-slides' ); ?></label>
	<input type="radio" id="animationLoop_false" <?php if ($animationLoop == "false") echo'checked="checked"' ; ?> name="flex_slider_options[animationLoop]" value="false" /> 
	<?php
}
function enable_carousel() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$enable_carousel = $options['enable_carousel'];	
 
	/* Echo the field. */ ?>
	<label for="enable_carousel_true" > <?php _e( ' Yes', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($enable_carousel == "yes") echo'checked="checked"' ; ?> id="enable_carousel_true" name="flex_slider_options[enable_carousel]" value="yes" /> 
	<label for="enable_carousel_false" > <?php _e( ' No', 'flexi-slides' ); ?></label>
	<input type="radio" id="enable_carousel_false" <?php if ($enable_carousel == "no") echo'checked="checked"' ; ?> name="flex_slider_options[enable_carousel]" value="no" /> 
	<?php
}

function enable_range() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$enable_range = $options['enable_range'];	

	/* Echo the field. */ ?>
	<label for="enable_range_true" > <?php _e( ' Yes', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($enable_range == "yes") echo'checked="checked"' ; ?> id="enable_range_true" name="flex_slider_options[enable_range]" value="yes" /> 
	<label for="enable_range_false" > <?php _e( ' No', 'flexi-slides' ); ?></label>
	<input type="radio" id="enable_range_false" <?php if ($enable_range == "no") echo'checked="checked"' ; ?> name="flex_slider_options[enable_range]" value="no" /> 
	<?php
}

function smoothHeight() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$smoothHeight = $options['smoothHeight'];	

	/* Echo the field. */ ?>
	<label for="smoothHeight_true" > <?php _e( ' True', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($smoothHeight == "true") echo'checked="checked"' ; ?> id="smoothHeight_true" name="flex_slider_options[smoothHeight]" value="true" /> 
	<label for="smoothHeight_false" > <?php _e( ' False', 'flexi-slides' ); ?></label>
	<input type="radio" id="smoothHeight_false" <?php if ($smoothHeight == "false") echo'checked="checked"' ; ?> name="flex_slider_options[smoothHeight]" value="false" /> 
	<?php
}

function slideshow_auto() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$slideshow_auto = $options['slideshow_auto'];	

	/* Echo the field. */ ?>
	<label for="slideshow_auto_true" > <?php _e( ' True', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($slideshow_auto == "true") echo'checked="checked"' ; ?> id="slideshow_auto_true" name="flex_slider_options[slideshow_auto]" value="true" /> 
	<label for="slideshow_auto_false" > <?php _e( ' False', 'flexi-slides' ); ?></label>
	<input type="radio" id="slideshow_auto_false" <?php if ($slideshow_auto == "false") echo'checked="checked"' ; ?> name="flex_slider_options[slideshow_auto]" value="false" /> 
	<?php
}

function controlNav() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$controlNav = $options['controlNav'];	

	/* Echo the field. */ ?>
	<label for="controlNav_true" > <?php _e( 'Yes', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($controlNav == "true") echo'checked="checked"' ; ?> id="controlNav_true" name="flex_slider_options[controlNav]" value="true" /> 
	<label for="autoanimation_false" > <?php _e( 'No', 'flexi-slides' ); ?></label>
	<input type="radio" id="controlNav_false" <?php if ($controlNav == "false") echo'checked="checked"' ; ?> name="flex_slider_options[controlNav]" value="false" /> 
	<?php
}

function directionNav() {
	 /* Get the option value from the database. */
	$options = get_option( 'flex_slider_options' );
	$directionNav = $options['directionNav'];	

	/* Echo the field. */ ?>
	<label for="directionNav_true" > <?php _e( 'Yes', 'flexi-slides' ); ?></label>
	<input type="radio" <?php if ($directionNav == "true") echo'checked="checked"' ; ?> id="directionNav_true" name="flex_slider_options[directionNav]" value="true" /> 
	<label for="autoanimation_false" > <?php _e( 'No', 'flexi-slides' ); ?></label>
	<input type="radio" id="directionNav_false" <?php if ($directionNav == "false") echo'checked="checked"' ; ?> name="flex_slider_options[directionNav]" value="false" /> 
	<?php
}


/**
 * Validate and/or sanitize user input.
 */
function flex_slider_validate_options( $input ) {	

	$options = get_option( 'flex_slider_options' );	
	$options['animationLoop'] = wp_filter_nohtml_kses( $input['animationLoop'] );
	$options['animation'] = wp_filter_nohtml_kses( $input['animation'] );
	$options['itemWidth'] = wp_filter_nohtml_kses( intval( $input['itemWidth'] ) );	
	$options['itemMargin'] = wp_filter_nohtml_kses( intval( $input['itemMargin'] ) );
	$options['minItems'] = wp_filter_nohtml_kses( intval( $input['minItems'] ) );
	$options['maxItems'] = wp_filter_nohtml_kses( intval( $input['maxItems'] ) );
	$options['enable_carousel'] = wp_filter_nohtml_kses( $input['enable_carousel'] );
	$options['enable_range'] = wp_filter_nohtml_kses( $input['enable_range'] );	
	$options['smoothHeight'] = wp_filter_nohtml_kses( $input['smoothHeight'] );	
	$options['slideshow_auto'] = wp_filter_nohtml_kses( $input['slideshow_auto'] );	
	$options['controlNav'] = wp_filter_nohtml_kses( $input['controlNav'] );	
	$options['directionNav'] = wp_filter_nohtml_kses( $input['directionNav'] );	
	$options['prevText'] = wp_filter_nohtml_kses( $input['prevText'] );	
	$options['nextText'] = wp_filter_nohtml_kses( $input['nextText'] );	

	return $options;
}


/**** activation default settings ***/
function flex_slider_default_settings() {

	/* Retrieve exisitng options, if any. */
	$ex_options = get_option( 'flex_slider_options' );
	
	/* Check if options are set. Add default values if not. */ 
	if ( !is_array( $ex_options ) ) {

		$default_options = array(	
			'animation'     => "fade",
			'animationLoop'    => "true",
			'slideshow_auto' => "true",
			'smoothHeight'    => "false",
			'controlNav'     => "true",
			'directionNav'  => "true",
			'prevText'     => "Previous",
			'nextText'		=> "Next",
			'enable_carousel' => "no",
			'enable_range' => "no"
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
	$enable_carousel = $options['enable_carousel'];

	?>
	<div class="flexslider <?php if($enable_carousel == 'yes') echo 'carousel' ; ?>">
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

add_action( 'wp_print_footer_scripts', 'sf_print_footer_scripts' );



function sf_print_footer_scripts() {
	$options = get_option( 'flex_slider_options' );
	
	$animation = $options['animation'];
	$animationLoop = $options['animationLoop'];
	$smoothHeight = $options['smoothHeight'];	
	$slideshow_auto = $options['slideshow_auto'];	
	$controlNav = $options['controlNav'];	
	$directionNav = $options['directionNav'];
	$prevText = $options['prevText'];	
	$nextText = $options['nextText'];	
	$enable_carousel = $options['enable_carousel'];
	$itemWidth = $options['itemWidth'];
	$itemMargin = $options['itemMargin'];
	$enable_range = $options['enable_range'];
	$minItems = $options['minItems'];
	$maxItems = $options['maxItems'];		

	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
      $('.flexslider').flexslider({
      	animation: "<?php echo $animation ; ?>"
	    ,animationLoop: <?php echo $animationLoop ; ?>
	    ,smoothHeight:<?php echo $smoothHeight ; ?>
	    ,slideshow:<?php echo $slideshow_auto ; ?>
	    ,controlNav:<?php echo $controlNav ; ?> 
	    ,directionNav:<?php echo $directionNav ; ?>	 
	    <?php if ($directionNav == "true"){ ?>
	    
	    ,prevText: "<?php echo esc_js($prevText) ; ?>"
	    
	    ,prevText: "<?php echo esc_js($nextText) ; ?>"
	    <?php } ?>
	    <?php if ($enable_carousel == "yes"){ ?>
	    ,itemWidth: <?php echo esc_js($itemWidth) ; ?>
	    
	    ,itemMargin: <?php echo esc_js($itemMargin) ; ?>
	    <?php } ?>
	    <?php if ($enable_range == "yes"){ ?>
	    
	    ,minItems: <?php echo esc_js($minItems) ; ?>
	    
	    ,maxItems: <?php echo esc_js($maxItems) ; ?>
	    <?php } ?>
	    
     });
  });
	
	</script>
	<?php
 }

/** stylesheet **/
add_action( 'wp_enqueue_scripts', 'prefix_add_my_stylesheet' );

    function prefix_add_my_stylesheet() {
        wp_register_style( 'prefix-style', plugins_url('flexslider.css', __FILE__) );
        wp_enqueue_style( 'prefix-style' );
}

?>