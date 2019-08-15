(function($){
	
	/**
	*  initialize_field
	*
	*  This function will initialize the $field.
	*
	*  @date	30/11/17
	*  @since	5.6.5
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function initialize_field( $field ) {

		var OPEN_DIALOG = null;
		var WPT_GEN_COUNT = 0;

		let polyField = $field[0].querySelector('#polyline_text textarea');
		let wptListElem = $field[0].querySelector('#coordinates_waypoints .coordinates_list');

		document.addEventListener('closeDialog', function() {
			OPEN_DIALOG = null;
		});

		$($field[0].querySelector('#polyline_field_controls button[data-event="polyline-generate"]')).on('click tap touch', function (event) {
			generateCode(polyField);
		});

		$($field[0].querySelector('#polyline_field_controls button[data-event="polyline-edit"]')).on('click tap touch', function (event) {
			lockEdit(polyField);
		});

		$($field[0].querySelector('#polyline_field_controls button[data-event="polyline-delete"]')).on('click tap touch', function (event) {
			handleRemovePolylineRequest();
		});

		$($field[0].querySelector('button[data-event="add-wpt"]')).on('click tap touch', function (event) {
			addWaypoint(wptListElem);
		});

		$field[0].querySelectorAll('div[data-event="edit-wpt"]').forEach(function(element) {
			$(element).on('click tap touch', handleEditorRequest);
		});

		/*
		*  Request data from Google
		*/

		function generateCode(polyField) {
			var checkFields = [],
				waypoints = [],
				validated = true,
				travelInput = $field[0].querySelector('select[name="travel_mode"]'),
				travelMode = travelInput.options[travelInput.selectedIndex].value,
				wptElements = $field[0].querySelectorAll('#coordinates_waypoints .coordinates_item')
				startLat = $field[0].querySelector('input[name="start_lat"]').value,
				startLng = $field[0].querySelector('input[name="start_lng"]').value,
				endLat = $field[0].querySelector('input[name="end_lat"]').value,
				endLng = $field[0].querySelector('input[name="end_lng"]').value;

			checkFields.push(
				$field[0].querySelector('input[name="start_lat"]'),
				$field[0].querySelector('input[name="start_lng"]'),
				$field[0].querySelector('input[name="end_lat"]'),
				$field[0].querySelector('input[name="end_lng"]')
			);

			/*
			*  Remove old messages
			*/

			var generatorAlertBox = document.getElementById('generator_alert');
			while (generatorAlertBox.firstChild) {
				generatorAlertBox.removeChild(generatorAlertBox.firstChild);
			}

			/*
			*  Gather coordinates
			*/

			for (let x = 0; x < wptElements.length; x++) {
				let inputFields = wptElements[x].querySelectorAll('input');
				for (y = 0; y < inputFields.length; y++) {
					checkFields.push(inputFields[y]);
					if (Number.isNaN(parseFloat(inputFields[y].value))) {
						waypointsCheck = false
					}
				}

				let xLat = parseFloat(wptElements[x].querySelector('[data-type="wpt_lat"]').value);
				let xLng = parseFloat(wptElements[x].querySelector('[data-type="wpt_lng"]').value);
				let point = xLat + ',' + xLng;

				waypoints.push({
					location: point,
					stopover: true
				});
			}

			let startCoords = startLat + ',' + startLng;
			let endCoords = endLat + ',' + endLng;

			/*
			*  Validate coordinates
			*  TODO: Allow and ignore if both input boxes are blank?
			*/

			for (let x = 0; x < checkFields.length; x++) {
				if (Number.isNaN(parseFloat(checkFields[x].value))) {
					checkFields[x].classList.add('input_error');
					checkFields[x].addEventListener('focus', function () {
						checkFields[x].classList.remove('input_error');
					}, { once: true });
					validated = false;
				}
			}

			if (!validated) {

				/*
				*  Error message
				*/

				let errorMessage = document.createElement('span');
					errorMessage.classList.add('alert_error');
					errorMessage.innerHTML = "* No directions generated.<br>Please enter valid Latitude and Longitude for all points and remove any blank fields.";

				generatorAlertBox.appendChild(errorMessage);

			} else {

				let routeProps = {
					origin: startCoords,
					destination: endCoords,
					waypoints: waypoints,
					optimizeWaypoints: false,
					travelMode: travelMode
				}

				/*
				*  Send request
				*/

				let directionsService = new google.maps.DirectionsService;

				directionsService.route(routeProps, function (response, status) {
					if (response.status === "OK") {

						polyField.value = JSON.stringify(response);

						let successMessage = document.createElement('span');
							successMessage.classList.add('alert_success');
							successMessage.innerHTML = '&check; Directions generated';

						generatorAlertBox.appendChild(successMessage);
					} else {
						let errorMessage = document.createElement('span');
							errorMessage.classList.add('alert_error');
							errorMessage.innerHTML = '* Directions generation failed. Response: ' + response.status;

						generatorAlertBox.appendChild(errorMessage);
					}
				});
			}
		}

		/*
		*  Basic CRUD
		*/

		function lockEdit(polyField) {
			var editButton = $('[data-event="polyline-edit"]')[0];

			if (polyField.readOnly) {
				polyField.readOnly = false;
				editButton.innerText = 'Lock';
			} else {
				polyField.readOnly = true;
				editButton.innerText = 'Edit';
			}
		}

		function removePolyline() {
			polyField = document.querySelector('#polyline_text textarea');
			polyField.value = '';
		}

		function removeWaypoint(wptElement) {
			wptElement.remove();
		}

		function moveWaypoint(wptElement, moveDirection) {
			if (
				moveDirection === 'up'
				&& wptElement.previousElementSibling !== null
			) {
				wptElement.parentNode.insertBefore(wptElement, wptElement.previousElementSibling);
			} else if (
				moveDirection === 'down'
				&& wptElement.nextElementSibling !== null
			) {
				wptElement.parentNode.insertBefore(wptElement, wptElement.nextElementSibling.nextElementSibling);
			}
		}

		function addWaypoint(wptListElem) {

			WPT_GEN_COUNT++;
			let wptName = 'new_wpt' + WPT_GEN_COUNT;

			let newPoint = document.createElement('div');
				newPoint.classList.add('waypoint', 'coordinates_item');

			let rowSet = document.createElement('div');
				rowSet.classList.add('coordinates_item_rows');

			let latRow = document.createElement('div');
				latRow.classList.add('coordinates_row');

			let lngRow = document.createElement('div');
				lngRow.classList.add('coordinates_row');

			let latLabel = document.createElement('label');
				latLabel.setAttribute('for', wptName + '_lat');
				latLabel.innerText = 'Lat.';

			let lngLabel = document.createElement('label');
				lngLabel.setAttribute('for', wptName + '_lng');
				lngLabel.innerText = 'Lon.';

			let latInput = document.createElement('input');
				latInput.setAttribute('type', 'text');
				latInput.setAttribute('name', wptName + '_lat');
				latInput.setAttribute('data-type', 'wpt_lat');

			let lngInput = document.createElement('input');
				lngInput.setAttribute('type', 'text');
				lngInput.setAttribute('name', wptName + '_lng');
				lngInput.setAttribute('data-type', 'wpt_lng');

			let latDegrees = document.createElement('span');
				latDegrees.classList.add('degrees_symbol');
				latDegrees.innerHTML = '&deg;';

			let lngDegrees = latDegrees.cloneNode();
				lngDegrees.innerHTML = '&deg;';

			let controls = document.createElement('div');
				controls.classList.add('coordinates_controls');
				
			let editor = document.createElement('div');
				editor.classList.add('coordinates_edit');
				editor.setAttribute('data-event', 'edit-wpt');
				editor.setAttribute('data-id', wptName);

			let cog = document.createElement('span');
				cog.classList.add('coordinates_edit_cog', 'dashicons', 'dashicons-admin-generic');

			$(editor).on('click tap touch', handleEditorRequest);

			latRow.appendChild(latLabel);
			latRow.appendChild(latInput);
			latRow.appendChild(latDegrees);
			lngRow.appendChild(lngLabel);
			lngRow.appendChild(lngInput);
			lngRow.appendChild(lngDegrees);
			editor.appendChild(cog);
			controls.appendChild(editor);
			rowSet.appendChild(latRow);
			rowSet.appendChild(lngRow);
			newPoint.appendChild(rowSet);
			newPoint.appendChild(controls);
			
			wptListElem.appendChild(newPoint);
		}

		/*
		*  Dialogs and handlers
		*/

		function handleRemovePolylineRequest() {

			/*
			*  Point at proper element, regardless of which child
			*  might have been clicked
			*/

			let parentElement = document.querySelector('#polyline_text').parentNode;

			if (OPEN_DIALOG) {
				// If a dialog already exists
				OPEN_DIALOG.close();
			}

			confirmRemovePolyline(parentElement);

			/*
			*  Stop event from bubbling up in the DOM. Without this,
			*  the events in Dialog.addEscapeHandlers are triggered automatically.
			*/

			event.preventDefault();
			event.stopPropagation();
		}

		function confirmRemovePolyline(parentElement) {

			OPEN_DIALOG = new Dialog(parentElement);

			let confirmText = document.createElement('p');
				confirmText.classList.add('dialog_header');
				confirmText.innerText = 'Clear Generated Polyline?';

			let confirm = document.createElement('button');
				confirm.classList.add('button', 'button_warning');
				confirm.setAttribute('type', 'button');
				confirm.innerText = 'Remove';

			let cancel = document.createElement('button');
				cancel.classList.add('button');
				cancel.setAttribute('type', 'button');
				cancel.innerText = 'Cancel';

			OPEN_DIALOG.controls.appendChild(confirmText);
			OPEN_DIALOG.controls.appendChild(confirm);
			OPEN_DIALOG.controls.appendChild(cancel);

			$(cancel).on('click tap touch', function () {
				event.preventDefault();
				event.stopPropagation();
				OPEN_DIALOG.close();
			});
			$(confirm).on('click tap touch', function () {
				removePolyline();
				OPEN_DIALOG.close();
			});

			/*
			*  Stop event from bubbling up in the DOM. Without this,
			*  the events in Dialog.addEscapeHandlers are triggered automatically.
			*/

			event.preventDefault();
			event.stopPropagation();
		}

		function handleEditorRequest(event) {

			/*
			*  Point at proper element, regardless of which child
			*  might have been clicked
			*/

			let editButtonEl = event.target.classList.contains('coordinates_edit') ?
				event.target :
				event.target.closest('.coordinates_edit');
			let eventParentEl = event.target.closest('.waypoint.coordinates_item');

			function dialogCloseEvent() {
				editButtonEl.classList.remove('editing');
				editButtonEl.parentNode.blur();
				document.removeEventListener('closeDialog', dialogCloseEvent);
			}

			if (OPEN_DIALOG) {
				// If a dialog already exists
				if (eventParentEl.contains(OPEN_DIALOG.container)) {
					// If user clicked the same element
					OPEN_DIALOG.close();
				} else {
					// If user clicked a different element
					OPEN_DIALOG.close();

					createEditDialog(eventParentEl);
					editButtonEl.classList.add('editing');
					document.addEventListener('closeDialog', dialogCloseEvent);
				}
			} else {
				createEditDialog(eventParentEl);
				editButtonEl.classList.add('editing');
				document.addEventListener('closeDialog', dialogCloseEvent);
			}

			/*
			*  Stop event from bubbling up in the DOM. Without this,
			*  the events in Dialog.addEscapeHandlers are triggered automatically.
			*/

			event.preventDefault();
			event.stopPropagation();
		}

		function moveWaypointHandler(event) {
			let moveDirection = event.target.dataset.direction;
			let wptElement = event.target.closest('.waypoint.coordinates_item');

			moveWaypoint(wptElement, moveDirection)
		}

		function confirmRemoveWaypoint(event, dialog) {

			dialog.clear();

			let confirmText = document.createElement('p');
				confirmText.classList.add('dialog_header');
				confirmText.innerText = 'Remove Waypoint?';

			let confirm = document.createElement('button');
				confirm.classList.add('button', 'button_warning');
				confirm.setAttribute('type', 'button');
				confirm.innerText = 'Remove';

			let cancel = document.createElement('button');
				cancel.classList.add('button');
				cancel.setAttribute('type', 'button');
				cancel.innerText = 'Cancel';

			dialog.controls.appendChild(confirmText);
			dialog.controls.appendChild(confirm);
			dialog.controls.appendChild(cancel);

			$(cancel).on('click tap touch', handleEditorRequest);
			$(confirm).on('click tap touch', event => {
				removeWaypoint(event.target.closest('.waypoint.coordinates_item'));
			});
	
			/*
			*  Stop event from bubbling up in the DOM. Without this,
			*  the events in Dialog.addEscapeHandlers are triggered automatically.
			*/

			event.preventDefault();
			event.stopPropagation();
		}

		function createEditDialog(wptElement) {

			OPEN_DIALOG = new Dialog(wptElement);

			OPEN_DIALOG.container.classList.add('edit_dialog_container');

			let moveUp = document.createElement('button');
				moveUp.classList.add('button');
				moveUp.setAttribute('type', 'button');
				moveUp.setAttribute('data-event', 'wpt-move');
				moveUp.setAttribute('data-direction', 'up');
				moveUp.innerText = 'Move Up';

			let remove = document.createElement('button');
				remove.classList.add('button', 'button_warning');
				remove.setAttribute('type', 'button');
				remove.setAttribute('data-event', 'wpt-remove');
				remove.innerText = 'Remove';

			let moveDown = document.createElement('button');
				moveDown.classList.add('button');
				moveDown.setAttribute('type', 'button');
				moveDown.setAttribute('data-event', 'wpt-move');
				moveDown.setAttribute('data-direction', 'down');
				moveDown.innerText = 'Move Down';

			OPEN_DIALOG.controls.appendChild(moveUp);
			OPEN_DIALOG.controls.appendChild(remove);
			OPEN_DIALOG.controls.appendChild(moveDown);

			$(moveUp).on('click tap touch', function() {
				moveWaypointHandler(event, OPEN_DIALOG);
			});
			$(remove).on('click tap touch', function() {
				confirmRemoveWaypoint(event, OPEN_DIALOG);
			});
			$(moveDown).on('click tap touch', function () {
				moveWaypointHandler(event, OPEN_DIALOG);
			});
		}

		/*
		*  Dialog class
		*/

		class Dialog {

			constructor(parent) {
				this.parent = parent;

				this.container = document.createElement('div');
				this.container.id = 'polyline_dialog';
				this.container.classList.add('dialog_container');

				this.overlay = document.createElement('div');
				this.overlay.classList.add('dialog_overlay');

				this.box = document.createElement('div');
				this.box.classList.add('dialog_box');

				this.controls = document.createElement('div');
				this.controls.classList.add('dialog_controls');

				this.parent.appendChild(this.container);
				this.container.appendChild(this.box);
				this.box.appendChild(this.overlay);
				this.box.appendChild(this.controls);

				this.closeEvent = new Event('closeDialog');

				this.addEscapeHandlers();
			}

			clickHandler(event) {

				if (!this.container.contains(event.target)) {
					this.close();
					this.removeEscapeHandlers();
				}

				event.preventDefault();
				event.stopPropagation();
			}

			keyHandler(event) {

				if (event.key === "Escape") {
					this.close();
					this.removeEscapeHandlers();
				}
			}
			
			addEscapeHandlers() {
				$(document).on('click tap touch', this.storedClickHandler = this.clickHandler.bind(this));
				$(document).on('keydown', this.storedKeyHandler = this.keyHandler.bind(this));
			}

			removeEscapeHandlers() {
				$(document).off('click tap touch', this.storedClickHandler);
				$(document).off('keydown', this.storedKeyHandler);
			}

			clear() {
				while (this.controls.firstChild) {
					this.controls.removeChild(this.controls.firstChild);
				}
			}

			close() {
				this.container.remove();
				this.removeEscapeHandlers();
				document.dispatchEvent(this.closeEvent);
			}
		}	
	}
	
	
	if( typeof acf.add_action !== 'undefined' ) {
	
		/*
		*  ready & append (ACF5)
		*
		*  These two events are called when a field element is ready for initizliation.
		*  - ready: on page load similar to $(document).ready()
		*  - append: on new DOM elements appended via repeater field or other AJAX calls
		*
		*  @param	n/a
		*  @return	n/a
		*/
		
		acf.add_action('ready_field/type=polyline', initialize_field);
		acf.add_action('append_field/type=polyline', initialize_field);
		
		
	} else {
		
		/*
		*  acf/setup_fields (ACF4)
		*
		*  These single event is called when a field element is ready for initizliation.
		*
		*  @param	event		an event object. This can be ignored
		*  @param	element		An element which contains the new HTML
		*  @return	n/a
		*/
		
		$(document).on('acf/setup_fields', function(e, postbox){
			
			// find all relevant fields
			$(postbox).find('.field[data-field_type="polyline"]').each(function(){
				
				// initialize
				initialize_field( $(this) );
				
			});
		
		});
	
	}

})(jQuery);
