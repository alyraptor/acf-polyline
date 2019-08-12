<?php

// exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('raptor_acf_field_polyline_two') ) :


class raptor_acf_field_polyline_two extends acf_field {
	
	
	/*
	*  __construct
	*
	*  This function will setup the field type data
	*
	*  @type	function
	*  @date	5/03/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct( $settings ) {
		
		/*
		*  name (string) Single word, no spaces. Underscores allowed
		*/
		
		$this->name = 'polyline-two';
		
		
		/*
		*  label (string) Multiple words, can include spaces, visible when selecting a field type
		*/
		
		$this->label = __('Polyline Generator', 'acf-polyline-two');
		
		
		/*
		*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
		*/
		
		$this->category = 'jquery';
		
		
		/*
		*  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
		*/
		
		$this->defaults = array(
			'api_key' => '',
			'show_waypoint_preview' => 'show',
			'show_path_preview' => 'show'
		);
		
		
		/*
		*  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
		*  var message = acf._e('polyline', 'error');
		*/
		
		$this->l10n = array(
			'error'	=> __('Error! Please enter a higher value', 'acf-polyline-two'),
		);
		
		
		/*
		*  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
		*/
		
		$this->settings = $settings;
		
		
		// do not delete!
    	parent::__construct();
    	
	}
	
	
	/*
	*  render_field_settings()
	*
	*  Create extra settings for your field. These are visible when editing a field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	
	function render_field_settings( $field ) {
		
		/*
		*  acf_render_field_setting
		*
		*  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
		*  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
		*
		*  More than one setting can be added by copy/paste the above code.
		*  Please note that you must also have a matching $defaults value for the field name
		*/

		acf_render_field_setting( $field, array(
			'label'			=> __('Point Previews','acf-polyline-two'),
			'instructions'	=> __('Display thumbnail previews for path points?','acf-polyline-two'),
			'type'			=> 'radio',
			'name'			=> 'show_waypoint_preview',
			'layout'		=> 'horizontal',
			'choices'		=> array(
				'show'	=> __('Show', 'acf-polyline-two'),
				'nope'	=> __('Don\'t Show', 'acf-polyline-two')
			)
		));

		acf_render_field_setting( $field, array(
			'label'			=> __('Path Preview','acf-polyline-two'),
			'instructions'	=> __('Display thumbnail preview for polyline path?','acf-polyline-two'),
			'type'			=> 'radio',
			'name'			=> 'show_path_preview',
			'layout'		=> 'horizontal',
			'choices'		=> array(
				'show'	=> __('Show', 'acf-polyline-two'),
				'nope'	=> __('Don\'t Show', 'acf-polyline-two')
			)
		));

	}
	
	
	
	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	
	function render_field( $field ) {		
		
		/*
		*  Use the stored polyline data to regenerate user input for start/end/waypoint
		*/
		
		$polyline_decoded = json_decode ( $field['value'] );

		// TODO: Remove
		// echo '<pre>';
		// 	print_r( $this );
		// echo '</pre>';

		$start_coords 	= $polyline_decoded->request->origin->location;
		$end_coords 	= $polyline_decoded->request->destination->location;
		$waypoints 		= $polyline_decoded->request->waypoints;
		$travel_mode	= $polyline_decoded->request->travelMode;

		/*
		*  Set up map preview thumbnails
		*/

		$path_preview = '';

		if ($field['show_path_preview'] == 'show') {
			$path_preview = '<div class="waypoint_preview"></div>';
		}

		$travel_mode = isset($travel_mode) ? $travel_mode : get_option('polyline_options')['travel_mode'];

		/*
		*  Create HTML for user input
		*/
		?>
		<div id="polyline_text">
			<textarea name="<?php echo esc_attr($field['name']) ?>" rows="10" readonly><?php echo $field['value']; ?></textarea>
		<?php
		echo $path_preview;
		?>
		</div>
		<div class="inline_controls" id="polyline_field_controls">
			<a class="acf-button button" href="#" data-event="polyline-edit">Edit</a>
			<a class="acf-button button" href="#" data-event="polyline-delete">Remove</a>
			<a class="acf-button button button-primary" href="#" data-event="polyline-generate">Generate</a>
		</div>
		<div id="generator_alert"></div>
		<div class="coordinates_section" id="gmap_options">
			<p class="section_header">Travel Mode</p>
            <select name="travel_mode">
		<?php
			$selected = ($travel_mode === 'DRIVING') ? 'selected' : '' ;
		?>
				<option value="DRIVING" <?php echo $selected; ?>>Driving</option>
		<?php
			$selected = ($travel_mode === 'WALKING') ? 'selected' : '' ;
		?>
				<option value="WALKING" <?php echo $selected; ?>>Walking</option>
		<?php
			$selected = ($travel_mode === 'BICYCLING') ? 'selected' : '' ;
		?>
				<option value="BICYCLING" <?php echo $selected; ?>>Bicycling</option>
		<?php
			$selected = ($travel_mode === 'TRANSIT') ? 'selected' : '' ;
		?>
				<option value="TRANSIT" <?php echo $selected; ?>>Transit</option>
			</select>
		</div>
		<div class="coordinates">
			<div id="coordinates_start" class="coordinates_section">
				<p class="section_header">Origin</p>
				<div class="coordinates_list">
					<div class="coordinates_item">
						<div class="coordinates_item_rows">
							<div class="coordinates_row">
								<label for="start_lat">Lat.</label>
								<input type="text" name="start_lat" value="<?php echo esc_attr($start_coords->lat); ?>" />
								<span class="degrees_symbol">&deg;</span>
							</div>
							<div class="coordinates_row">
								<label for="start_lng">Lon.</label>
								<input type="text" name="start_lng" value="<?php echo esc_attr($start_coords->lng); ?>" />
								<span class="degrees_symbol">&deg;</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
		if (!empty($waypoints)) {
			$wp_counter = 0;
		?>
			<div class="coordinates_section" id="coordinates_waypoints">
				<p class="section_header">Waypoints</p>
				<div class="coordinates_list">
		<?php
			foreach($waypoints as $wp) {
				$wp_counter++;
		?>
					<div class="waypoint coordinates_item">
						<div class="coordinates_item_rows">
							<div class="coordinates_row">
								<label for="wpt<?php echo $wp_counter;?>_lat">Lat.</label>
								<input type="text" name="wpt<?php echo $wp_counter;?>_lat" data-type="wpt_lat" value="<?php echo $wp->location->location->lat; ?>" />
								<span class="degrees_symbol">&deg;</span>
							</div>
							<div class="coordinates_row">
								<label for="wpt<?php echo $wp_counter;?>_lng">Lon.</label>
								<input type="text" name="wpt<?php echo $wp_counter;?>_lng" data-type="wpt_lng" value="<?php echo $wp->location->location->lng; ?>" />
								<span class="degrees_symbol">&deg;</span>
							</div>
						</div>
						<div class="coordinates_controls">
							<a href="#" data-event="edit-wpt" data-id="wpt<?php echo $wp_counter; ?>" class="coordinates_edit">
								<span class="coordinates_edit_cog dashicons dashicons-admin-generic"></span>
							</a>
						</div>
					</div>
		<?php
			}
		?>
				</div>
				<div class="inline_controls">
					<a class="acf-button button button-primary button_right" href="#" data-event="add-wpt">Add Waypoint</a>
				</div>
			</div>
		<?php
		}
		?>
			<div id="coordinates_end" class="coordinates_section">
				<p class="section_header">Destination</p>
				<div class="coordinates_list">
					<div class="coordinates_item">
						<div class="coordinates_item_rows">
							<div class="coordinates_row">
								<label for="end_lat">Lat.</label>
								<input type="text" name="end_lat" value="<?php echo esc_attr($end_coords->lat); ?>" />
								<span class="degrees_symbol">&deg;</span>
							</div>
							<div class="coordinates_row">
								<label for="end_lng">Lon.</label>
								<input type="text" name="end_lng" value="<?php echo esc_attr($end_coords->lng); ?>" />
								<span class="degrees_symbol">&deg;</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	
		
	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function input_admin_enqueue_scripts() {
		
		// vars
		$url = $this->settings['url'];
		$version = $this->settings['version'];

		$gmap_api_url  = 'https://maps.googleapis.com/maps/api/js';
		$gmap_api_url .= '?key=' . get_option('polyline_options')['api_key'];
		
		// register & include JS
		wp_register_script('acf-polyline', "{$url}assets/js/input.js", array('acf-input'), $version);
		wp_enqueue_script('acf-polyline');
		
		
		// register & include CSS
		wp_register_style('acf-polyline', "{$url}assets/css/input.css", array('acf-input'), $version);
		wp_enqueue_style('acf-polyline');

		
		// include Google Maps API
		wp_enqueue_script('google-maps', $gmap_api_url, Array('acf-polyline'), false, true);
		
	}

	
	/*
	*  input_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
		
	function input_admin_head() {
	
		
		
	}
	
	*/
	
	
	/*
   	*  input_form_data()
   	*
   	*  This function is called once on the 'input' page between the head and footer
   	*  There are 2 situations where ACF did not load during the 'acf/input_admin_enqueue_scripts' and 
   	*  'acf/input_admin_head' actions because ACF did not know it was going to be used. These situations are
   	*  seen on comments / user edit forms on the front end. This function will always be called, and includes
   	*  $args that related to the current screen such as $args['post_id']
   	*
   	*  @type	function
   	*  @date	6/03/2014
   	*  @since	5.0.0
   	*
   	*  @param	$args (array)
   	*  @return	n/a
   	*/
   	
   	/*
   	
   	function input_form_data( $args ) {
	   	
		
	
   	}
   	
   	*/
	
	
	/*
	*  input_admin_footer()
	*
	*  This action is called in the admin_footer action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_footer)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
		
	function input_admin_footer() {
	
		
		
	}
	
	*/
	
	
	/*
	*  field_group_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
	*  Use this action to add CSS + JavaScript to assist your render_field_options() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
	
	function field_group_admin_enqueue_scripts() {
		
	}
	
	*/

	
	/*
	*  field_group_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is edited.
	*  Use this action to add CSS and JavaScript to assist your render_field_options() action.
	*
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
	
	function field_group_admin_head() {
	
	}
	
	*/


	/*
	*  load_value()
	*
	*  This filter is applied to the $value after it is loaded from the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/
	
	/*
	
	function load_value( $value, $post_id, $field ) {
		
		return $value;
		
	}
	
	*/
	
	
	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is saved in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/
	
	/*
	
	function update_value( $value, $post_id, $field ) {
		
		return $value;
		
	}
	
	*/
	
	
	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/
		
	/*
	
	function format_value( $value, $post_id, $field ) {
		
		// bail early if no value
		if( empty($value) ) {
		
			return $value;
			
		}
		
		
		// apply setting
		if( $field['font_size'] > 12 ) { 
			
			// format the value
			// $value = 'something';
		
		}
		
		
		// return
		return $value;
	}
	
	*/
	
	
	/*
	*  validate_value()
	*
	*  This filter is used to perform validation on the value prior to saving.
	*  All values are validated regardless of the field's required setting. This allows you to validate and return
	*  messages to the user if the value is not correct
	*
	*  @type	filter
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$valid (boolean) validation status based on the value and the field's required setting
	*  @param	$value (mixed) the $_POST value
	*  @param	$field (array) the field array holding all the field options
	*  @param	$input (string) the corresponding input name for $_POST value
	*  @return	$valid
	*/
	
	/*
	
	function validate_value( $valid, $value, $field, $input ){
		
		// Basic usage
		if( $value < $field['custom_minimum_setting'] )
		{
			$valid = false;
		}
		
		
		// Advanced usage
		if( $value < $field['custom_minimum_setting'] )
		{
			$valid = __('The value is too little!','acf-polyline'),
		}
		
		
		// return
		return $valid;
		
	}
	
	*/
	
	
	/*
	*  delete_value()
	*
	*  This action is fired after a value has been deleted from the db.
	*  Please note that saving a blank value is treated as an update, not a delete
	*
	*  @type	action
	*  @date	6/03/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (mixed) the $post_id from which the value was deleted
	*  @param	$key (string) the $meta_key which the value was deleted
	*  @return	n/a
	*/
	
	/*
	
	function delete_value( $post_id, $key ) {
		
		
		
	}
	
	*/
	
	
	/*
	*  load_field()
	*
	*  This filter is applied to the $field after it is loaded from the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0	
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/
	
	/*
	
	function load_field( $field ) {
		
		return $field;
		
	}	
	
	*/
	
	
	/*
	*  update_field()
	*
	*  This filter is applied to the $field before it is saved to the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/
	
	function update_field( $field ) {
		
		return $field;
		
	}
	
	
	/*
	*  delete_field()
	*
	*  This action is fired after a field is deleted from the database
	*
	*  @type	action
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	n/a
	*/
	
	/*
	
	function delete_field( $field ) {
		
		
		
	}	
	
	*/
	
	
}


// initialize
new raptor_acf_field_polyline_two( $this->settings );


// class_exists check
endif;

?>