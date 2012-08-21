function flex_slider_settings_page( ){ 
    // variables for the field and option names 
    $opt_name = array('animation' =>'animation'	);
    $hidden_field_name = 'att_submit_hidden';

    // Read in existing option value from database
	$opt_val = array('animation' => get_option( $opt_name['animation'] )  );

	// See if the user has posted us some information
   // If they did, this hidden field will be set to 'Y'
    if(isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
    // Read their posted value
    $opt_val = array('animation' => $_POST[ $opt_name['animation'] ]
      );

   // Save the posted value in the database
    update_option( $opt_name['animation'], $opt_val['animation'] );    

?>
<div id="message" class="updated fade">
  <p><strong>  <?php _e('Slider option were saved', 'flex_slider' ); ?> </strong></p>
</div>
	<?php
	} ?>

<div class="wrap">
<h2><?php _e('Slideshow settings', 'flex_slider' ); ?></h2>

<?php
print_r($opt_val);
?>


<form name="att_img_options" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
  <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
<!-- 
 <p><select name="<?php echo $opt_name['animation']; ?>">
        <option value="fade" <?php echo ($opt_val['animation'] == "fade") ? 'selected="selected"' : ''; ?> >fade</option>
        <option value="slide" <?php echo ($opt_val['animation'] == "slide") ? 'selected="selected"' : ''; ?> >slide</option>
</select>
</p> -->


<input type="text" name="<?php echo $opt_name['animation']?>" value="<?php   echo $opt_val['animation']; ?>" />


 <p class="submit">
            <input type="submit" class="button-primary" name="Submit" value="Store Options" /></p> 

</form>
</div>

<?php
}

/** validation // clean data **/
function flexi_slider_options_validate( $input ) {
	die ();
	
	// $options = get_option( 'flexi_slider_options' );
	
	// $options['animation'] = wp_filter_nohtml_kses( intval( $input['animation'] ) );
	
	// return $options;
}
