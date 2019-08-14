<?php

/*
Plugin Name: Advanced Custom Fields: Polyline Generator
Plugin URI: https://github.com/alyraptor/acf-polyline
Description: A custom field type for Advanced Custom Fields to add a dynamically-generated polyline array using the Google Directions API
Version: 1.0.0
Author: Aly Richardson
Author URI: https://github.com/alyraptor
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Created using: https://github.com/AdvancedCustomFields/acf-field-type-template

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('raptor_acf_plugin_polyline_two') ) :

class raptor_acf_plugin_polyline_two {
	
	// vars
	var $settings;	
	
	/*
	*  __construct
	*
	*  This function will setup the class functionality
	*
	*  @type	function
	*  @date	17/02/2016
	*  @since	1.0.0
	*
	*  @param	void
	*  @return	void
	*/
	
	function __construct() {
		
		// settings
		// - these will be passed into the field class.
		$this->settings = array(
			'version'	=> '0.0.1',
			'url'		=> plugin_dir_url( __FILE__ ),
			'path'		=> plugin_dir_path( __FILE__ )
		);
		
		
		// include field
		add_action('acf/include_field_types', 	array($this, 'include_field')); // v5
		add_action('acf/register_fields', 		array($this, 'include_field')); // v4
		
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );

	}	
	
	/*
	*  include_field
	*
	*  This function will include the field type class
	*
	*  @type	function
	*  @date	17/02/2016
	*  @since	1.0.0
	*
	*  @param	$version (int) major ACF version. Defaults to false
	*  @return	void
	*/
	
	function include_field( $version = false ) {
		
		// support empty $version
		if( !$version ) $version = 4;
		
		
		// load acf-polyline
		load_plugin_textdomain( 'acf-polyline-two', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' ); 
		
		
		// include
		include_once('fields/class-raptor-acf-field-polyline-two-v' . $version . '.php');
	}

	/*
	* Add options page
	*/
	
	public function add_plugin_page() {
		add_options_page(
			'ACF Polyline Options', 
			'ACF Polyline', 
			'manage_options', 
			'acf_polyline', 
			array( $this, 'create_admin_page' )
		);
	}
	
	/*
	* Options page callback
	*/

	public function create_admin_page() {
		// Set class property
		$this->options = get_option( 'polyline_options' );

		?>
		<div class="wrap">
			<h1>ACF Polyline Options</h1>
			<form method="post" action="options.php">
			<?php
				// This prints out all hidden setting fields
				settings_fields( 'polyline_options_group' );
				do_settings_sections( 'acf_polyline' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/*
	* Register and add settings
	*/

    public function page_init() {

        register_setting(
            'polyline_options_group', // Option group
            'polyline_options', // Option name
			array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'gmap_api_settings', // ID
            'Google Maps Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'acf_polyline' // Page
        );

        add_settings_field(
            'api_key', // ID
            'API Key', // Title
            array( $this, 'api_key_callback' ), // Callback
            'acf_polyline', // Page
            'gmap_api_settings' // Section
        );

        add_settings_field(
            'travel_mode', // ID
            'Default Travel Mode', // Title
            array( $this, 'travel_mode_callback' ), // Callback
            'acf_polyline', // Page
            'gmap_api_settings' // Section
        );
    }

    /**
    * Sanitize each setting field as needed
    *
    * @param array $input Contains all settings fields as array keys
	*/

    public function sanitize( $input ) {

		var_dump($input);
        $new_input = array();
        if( isset( $input['api_key'] )) {
			$new_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}
		if( isset( $input['travel_mode'] )) {
			$new_input['travel_mode'] = sanitize_text_field( $input['travel_mode'] );
		}

        return $new_input;
    }

    /**
    * Print the Section text
	*/

    public function print_section_info() {
        print 'It is highly recommend that you <a href="https://developers.google.com/maps/documentation/javascript/get-api-key#restrict_key">restrict your API Key</a> before deploying publicly.';
    }

    /**
    * Get the settings option array and print one of its values
	*/
	
    public function api_key_callback() {
        printf(
            '<input type="text" id="api_key" name="polyline_options[api_key]" value="%s" size="50" />',
            isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key'] ) : ''
        );
    }
	
    public function travel_mode_callback() {
		?>
            <select name="polyline_options[travel_mode]">
		<?php
			$selected = (isset( $this->options['travel_mode'] ) && $this->options['travel_mode'] === 'DRIVING') ? 'selected' : '' ;
		?>
				<option value="DRIVING" <?php echo $selected; ?>>Driving</option>
		<?php
			$selected = (isset( $this->options['travel_mode'] ) && $this->options['travel_mode'] === 'WALKING') ? 'selected' : '' ;
		?>
				<option value="WALKING" <?php echo $selected; ?>>Walking</option>
		<?php
			$selected = (isset( $this->options['travel_mode'] ) && $this->options['travel_mode'] === 'BICYCLING') ? 'selected' : '' ;
		?>
				<option value="BICYCLING" <?php echo $selected; ?>>Bicycling</option>
		<?php
			$selected = (isset( $this->options['travel_mode'] ) && $this->options['travel_mode'] === 'TRANSIT') ? 'selected' : '' ;
		?>
				<option value="TRANSIT" <?php echo $selected; ?>>Transit</option>
			</select>
		<?php
    }
}


// initialize
new raptor_acf_plugin_polyline_two();

// class_exists check
endif;
	
?>