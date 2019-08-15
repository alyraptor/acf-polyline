<?php

// exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('raptor_acf_field_polyline') ) :


class raptor_acf_field_polyline extends acf_field {
	
	
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
		
		$this->name = 'polyline';
		
		
		/*
		*  label (string) Multiple words, can include spaces, visible when selecting a field type
		*/
		
		$this->label = __('Polyline Generator', 'acf-polyline');
		
		
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
			'error'	=> __('Error! Please enter a higher value', 'acf-polyline'),
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
			'label'			=> __('Point Previews','acf-polyline'),
			'instructions'	=> __('Display thumbnail previews for path points?','acf-polyline'),
			'type'			=> 'radio',
			'name'			=> 'show_waypoint_preview',
			'layout'		=> 'horizontal',
			'choices'		=> array(
				'show'	=> __('Show', 'acf-polyline'),
				'nope'	=> __('Don\'t Show', 'acf-polyline')
			)
		));

		acf_render_field_setting( $field, array(
			'label'			=> __('Path Preview','acf-polyline'),
			'instructions'	=> __('Display thumbnail preview for polyline path?','acf-polyline'),
			'type'			=> 'radio',
			'name'			=> 'show_path_preview',
			'layout'		=> 'horizontal',
			'choices'		=> array(
				'show'	=> __('Show', 'acf-polyline'),
				'nope'	=> __('Don\'t Show', 'acf-polyline')
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
}


// initialize
new raptor_acf_field_polyline( $this->settings );


// class_exists check
endif;

?>