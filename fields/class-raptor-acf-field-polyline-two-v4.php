<?php

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('raptor_acf_field_polyline_two') ) :


class raptor_acf_field_polyline_two extends acf_field {
	
	// vars
	var $settings, // will hold info such as dir / path
		$defaults; // will hold default field options
		
		
	/*
	*  __construct
	*
	*  Set name / label needed for actions / filters
	*
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function __construct( $settings )
	{
		// vars
		$this->name = 'polyline';
		$this->label = __('Polyline Generator');
		$this->category = __("jQuery",'acf-polyline-two'); // Basic, Content, Choice, etc
		$this->defaults = array(
			'sub_fields'	=>	array(),
			'row_min'		=>	0,
			'row_limit'		=>	0,
			'layout' 		=> 'table',
			'button_label'	=>	__("Add Row",'acf-polyline-two')
		);
		
		
		// do not delete!
    	parent::__construct();
    	
    	
    	// settings
		$this->settings = $settings;

	}
	
	
	/*
	*  create_options()
	*
	*  Create extra options for your field. This is rendered when editing a field.
	*  The value of $field['name'] can be used (like below) to save extra data to the $field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field	- an array holding all the field's data
	*/
	
	function create_options( $field )
	{
		// defaults?
		/*
		$field = array_merge($this->defaults, $field);
		*/
		
		// key is needed in the field names to correctly save the data
		$key = $field['name'];
		
		
		// Create Field Options HTML
		?>
<tr class="field_option field_option_<?php echo $this->name; ?>">
	<td class="label">
		<label><?php _e("API Key",'acf-polyline-two'); ?></label>
		<p class="description"><?php _e("Enter your Google Maps API Key",'acf-polyline-two'); ?></p>
	</td>
	<td>
		<?php
		
		do_action('acf/create_field', array(
			'type'		=>	'text',
			'name'		=>	'fields['.$key.'][api_key]',
			'value'		=>	$field['api_key']
		));
		
		?>
	</td>
</tr>
		<?php
		
	}
	
	
	/*
	*  create_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function create_field( $field )
	{
				
		/*
		*  Use the stored polyline data to regenerate user input for start/end/waypoint
		*/
		
		$polyline_decoded = json_decode ( $field['value'] );

		$start_coords 	= $polyline_decoded->request->origin->location;
		$end_coords 	= $polyline_decoded->request->destination->location;
		$waypoints 		= $polyline_decoded->request->waypoints;

		$point_preview = '';
		$path_preview = '';

		if ($field['show_waypoint_preview'] == 'show') {
			$point_preview = '<div class="waypoint_preview"></div>';
		}

		if ($field['show_path_preview'] == 'show') {
			$path_preview = '<div class="waypoint_preview"></div>';
		}

		/*
		*  Create HTML for user input
		*/
		?>
		<input type="text" name="<?php echo esc_attr($field['name']) ?>" value="<?php echo esc_attr($field['api_key']) ?>" disabled />
		<div class="coordinates">
			<div id="coordinates_start">
				<label for="start_lat">Start Latitude</label>
				<input type="text" name="start_lat" placeholder="0.0" value="<?php echo esc_attr($start_coords->lat); ?>" /><span class="degrees_symbol">&deg;</span>
				<label for="start_lng">Start Longitude</label>
				<input type="text" name="start_lng" placeholder="0.0" value="<?php echo esc_attr($start_coords->lng); ?>" /><span class="degrees_symbol">&deg;</span>
			</div>
		<?php
		echo $point_preview;
		if (!empty($waypoints)) {
			$wp_counter = 0;
		?>
			<div id="coordinates_waypoints">
		<?php
			foreach($waypoints as $wp) {
				$wp_counter++;
		?>
				<div class="coordinates_waypoint_item">
					<label for="wp<?php echo $wp_counter;?>_lat">Waypoint Latitude</label>
					<input type="text" name="wp<?php echo $wp_counter;?>_lat" value="<?php echo $wp->location->location->lat; ?>" /><span class="degrees_symbol">&deg;</span>
					<label for="wp<?php echo $wp_counter;?>_lng">Waypoint Longitude</label>
					<input type="text" name="wp<?php echo $wp_counter;?>_lng" value="<?php echo $wp->location->location->lng; ?>" /><span class="degrees_symbol">&deg;</span>
				</div>
		<?php
			echo $point_preview;
			}
		?>
			</div>
		<?php
		}
		?>
			<div id="coordinates_end">
				<label for="end_lat">End Latitude</label>
				<input type="text" name="end_lat" placeholder="0.0" value="<?php echo esc_attr($end_coords->lat); ?>" /><span class="degrees_symbol">&deg;</span>
				<label for="end_lng">End Longitude</label>
				<input type="text" name="end_lng" placeholder="0.0" value="<?php echo esc_attr($end_coords->lng); ?>" /><span class="degrees_symbol">&deg;</span>
			</div>
		<?php
		echo $point_preview;
		?>
		</div>
		<div class="polyline_text">
			<textarea name="<?php echo esc_attr($field['name']) ?>" rows="10"><?php echo $field['value']; ?></textarea>
		</div>
		<?php
		echo $path_preview;
		?>
		<div class="polyline_field_controls">
			<a class="acf-button button button-primary" href="#" data-event="polyline-generate">Generate</a>
			<a class="acf-button button" href="#" data-event="polyline-edit">Edit</a>
			<a class="acf-button button" href="#" data-event="polyline-delete">Remove</a>
		</div>
		<?php
	}
	
	
	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your create_field() action.
	*
	*  $info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function input_admin_enqueue_scripts()
	{
		// Note: This function can be removed if not used
		
		
		// vars
		$url = $this->settings['url'];
		$version = $this->settings['version'];
		
		
		// register & include JS
		wp_register_script('acf-polyline-two', "{$url}assets/js/input.js", array('acf-input'), $version);
		wp_enqueue_script('acf-polyline-two');
		
		
		// register & include CSS
		wp_register_style('acf-polyline-two', "{$url}assets/css/input.css", array('acf-input'), $version);
		wp_enqueue_style('acf-polyline-two');
		
	}
	
	
	/*
	*  input_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your create_field() action.
	*
	*  @info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function input_admin_head()
	{
		// Note: This function can be removed if not used
	}
	
	
	/*
	*  field_group_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
	*  Use this action to add CSS + JavaScript to assist your create_field_options() action.
	*
	*  $info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function field_group_admin_enqueue_scripts()
	{
		// Note: This function can be removed if not used
	}

	
	/*
	*  field_group_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is edited.
	*  Use this action to add CSS and JavaScript to assist your create_field_options() action.
	*
	*  @info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function field_group_admin_head()
	{
		// Note: This function can be removed if not used
	}


	/*
	*  load_value()
	*
		*  This filter is applied to the $value after it is loaded from the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value - the value found in the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field - the field array holding all the field options
	*
	*  @return	$value - the value to be saved in the database
	*/
	
	function load_value( $value, $post_id, $field )
	{
		// Note: This function can be removed if not used
		return $value;
	}
	
	
	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is updated in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value - the value which will be saved in the database
	*  @param	$post_id - the $post_id of which the value will be saved
	*  @param	$field - the field array holding all the field options
	*
	*  @return	$value - the modified value
	*/
	
	function update_value( $value, $post_id, $field )
	{
		// Note: This function can be removed if not used
		return $value;
	}
	
	
	/*
	*  format_value()
	*
	*  This filter is applied to the $value after it is loaded from the db and before it is passed to the create_field action
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/
	
	function format_value( $value, $post_id, $field )
	{
		// defaults?
		/*
		$field = array_merge($this->defaults, $field);
		*/
		
		// perhaps use $field['preview_size'] to alter the $value?
		
		
		// Note: This function can be removed if not used
		return $value;
	}
	
	
	/*
	*  format_value_for_api()
	*
	*  This filter is applied to the $value after it is loaded from the db and before it is passed back to the API functions such as the_field
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value	- the value which was loaded from the database
	*  @param	$post_id - the $post_id from which the value was loaded
	*  @param	$field	- the field array holding all the field options
	*
	*  @return	$value	- the modified value
	*/
	
	function format_value_for_api( $value, $post_id, $field )
	{
		// defaults?
		/*
		$field = array_merge($this->defaults, $field);
		*/
		
		// perhaps use $field['preview_size'] to alter the $value?
		
		
		// Note: This function can be removed if not used
		return $value;
	}
	
	
	/*
	*  load_field()
	*
	*  This filter is applied to the $field after it is loaded from the database
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field - the field array holding all the field options
	*
	*  @return	$field - the field array holding all the field options
	*/
	
	function load_field( $field )
	{
		// Note: This function can be removed if not used
		return $field;
	}
	
	
	/*
	*  update_field()
	*
	*  This filter is applied to the $field before it is saved to the database
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field - the field array holding all the field options
	*  @param	$post_id - the field group ID (post_type = acf)
	*
	*  @return	$field - the modified field
	*/

	function update_field( $field, $post_id )
	{
		// Note: This function can be removed if not used
		return $field;
	}

}


// initialize
new raptor_acf_field_polyline_two( $this->settings );


// class_exists check
endif;

?>