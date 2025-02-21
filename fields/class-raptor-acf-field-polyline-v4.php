<?php

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('raptor_acf_field_polyline') ) :


class raptor_acf_field_polyline extends acf_field {
	
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
		$this->category = __("jQuery",'acf-polyline'); // Basic, Content, Choice, etc
		$this->defaults = array(
			'sub_fields'	=>	array(),
			'row_min'		=>	0,
			'row_limit'		=>	0,
			'layout' 		=> 'table',
			'button_label'	=>	__("Add Row",'acf-polyline')
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
	
	function create_options( $field ) {
		
		/*
		*  Use the stored polyline data to regenerate user input for start/end/waypoint
		*/
		
		$polyline_decoded = json_decode ( $field['value'] );

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
		<div class="polyline_stored_response">
			<div id="polyline_text">
				<textarea name="<?php echo esc_attr($field['name']) ?>" rows="10" readonly><?php echo $field['value']; ?></textarea>
		<?php
		echo $path_preview;
		?>
			</div>
		</div>
		<div class="inline_controls" id="polyline_field_controls">
			<button class="acf-button button" type="button" data-event="polyline-edit">Edit</button>
			<button class="acf-button button button_warning" type="button" data-event="polyline-delete">Remove</button>
			<button class="acf-button button button-primary" type="button" data-event="polyline-generate">Generate</button>
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
			<div class="coordinates_section" id="coordinates_waypoints">
				<p class="section_header">Waypoints</p>
				<div class="coordinates_list">
		<?php
		if (!empty($waypoints)) {
			$wp_counter = 0;
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
							<div class="coordinates_edit" data-event="edit-wpt" data-id="wpt<?php echo $wp_counter; ?>">
								<span class="coordinates_edit_cog dashicons dashicons-admin-generic"></span>
							</div>
						</div>
					</div>
		<?php
			}
		}
		?>
				</div>
				<div class="inline_controls">
					<button class="acf-button button button-primary button_right" type="button" data-event="add-wpt">Add Waypoint</button>
				</div>
			</div>
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
	*  Use this action to add CSS + JavaScript to assist your create_field() action.
	*
	*  $info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/

	function input_admin_enqueue_scripts()
	{
		// vars
		$url = $this->settings['url'];
		$version = $this->settings['version'];
		
		// register & include JS
		wp_register_script('acf-polyline', "{$url}assets/js/input.js", array('acf-input'), $version);
		wp_enqueue_script('acf-polyline');
		
		// register & include CSS
		wp_register_style('acf-polyline', "{$url}assets/css/input.css", array('acf-input'), $version);
		wp_enqueue_style('acf-polyline');
		
	}

}


// initialize
new raptor_acf_field_polyline( $this->settings );


// class_exists check
endif;

?>