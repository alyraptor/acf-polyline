<?php

	/*
	* Add options page
	*/
	
	function add_plugin_page() {
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

	function create_admin_page() {
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

    function page_init() {

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

    function sanitize( $input ) {

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

    function print_section_info() {
        print 'It is highly recommend that you <a href="https://developers.google.com/maps/documentation/javascript/get-api-key#restrict_key">restrict your API Key</a> before deploying publicly.';
    }

    /**
    * Get the settings option array and print one of its values
	*/
	
    function api_key_callback() {
        printf(
            '<input type="text" id="api_key" name="polyline_options[api_key]" value="%s" size="50" />',
            isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key'] ) : ''
        );
    }
	
    function travel_mode_callback() {
		?>
            <select name="polyline_options[travel_mode]">
		<?php
			$selected = (isset( $this->polyline_options['travel_mode'] ) && $this->polyline_options['travel_mode'] === 'DRIVING') ? 'selected' : '' ;
		?>
				<option value="DRIVING" <?php echo $selected; ?>>Driving</option>
		<?php
			$selected = (isset( $this->polyline_options['travel_mode'] ) && $this->polyline_options['travel_mode'] === 'WALKING') ? 'selected' : '' ;
		?>
				<option value="WALKING" <?php echo $selected; ?>>Walking</option>
		<?php
			$selected = (isset( $this->polyline_options['travel_mode'] ) && $this->polyline_options['travel_mode'] === 'BICYCLING') ? 'selected' : '' ;
		?>
				<option value="BICYCLING" <?php echo $selected; ?>>Bicycling</option>
		<?php
			$selected = (isset( $this->polyline_options['travel_mode'] ) && $this->polyline_options['travel_mode'] === 'TRANSIT') ? 'selected' : '' ;
		?>
				<option value="TRANSIT" <?php echo $selected; ?>>Transit</option>
			</select>
		<?php
    }

?>