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

		var directionsService = new google.maps.DirectionsService;
		var polyField = $field[0].querySelector('#polyline_text textarea');
		var wptListElem = $field[0].querySelector('#coordinates_waypoints .coordinates_list');
		var wptGenCount = 0;

		$field[0].querySelector('#polyline_field_controls a[data-event="polyline-generate"]').addEventListener('click', function (event) {
			generateCode(polyField);
		});

		$field[0].querySelector('#polyline_field_controls a[data-event="polyline-edit"]').addEventListener('click', function (event) {
			lockEdit(polyField);
		});

		$field[0].querySelector('#polyline_field_controls a[data-event="polyline-delete"]').addEventListener('click', function (event) {
			removePolyline(polyField);
		});

		$field[0].querySelector('a[data-event="add-wpt"]').addEventListener('click', function (event) {
			addWaypoint(wptListElem);
		});

		$field[0].querySelectorAll('a[data-event="edit-wpt"]').forEach(function(element) {
			element.addEventListener('click', handleEditorRequest);
		});

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
			*  Future: Allow and ignore if both input boxes are blank?
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

				var routeProps = {
					origin: startCoords,
					destination: endCoords,
					waypoints: waypoints,
					optimizeWaypoints: false,
					travelMode: travelMode
				}

				/*
				*  Send request
				*/

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

		function removePolyline(polyField) {
			polyField.value = '';
		}

		function addWaypoint(wptListElem) {

			wptGenCount++;
			let wptName = 'new_wpt' + wptGenCount;

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
				
			let editor = document.createElement('a');
				editor.href = '#';
				editor.classList.add('coordinates_edit');
				editor.setAttribute('data-event', 'edit-wpt');
				editor.setAttribute('data-id', wptName);

			let cog = document.createElement('span');
				cog.classList.add('coordinates_edit_cog', 'dashicons', 'dashicons-admin-generic');

			editor.addEventListener('click', handleEditorRequest);

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

		function confirmRemoveWaypoint(event) {

			let confirmPrompt = document.createElement('div');
				confirmPrompt.classList.add('dialog_prompt');

			let confirmText = document.createElement('p');
				confirmText.classList.add('dialog_header');
				confirmText.innerText = 'Remove Waypoint?';

			let confirmPos = document.createElement('a');
				confirmPos.href='#';
				confirmPos.classList.add('button', 'button_warning');
				confirmPos.innerText = 'Remove';

			let confirmNeg = document.createElement('a');
				confirmNeg.href = '#';
				confirmNeg.classList.add('button');
				confirmNeg.innerText = 'Cancel';

			let dialogElement = document.getElementById('polyline_dialog');
			let dialogBox = dialogElement.querySelector('.edit_dialog_box');
			let dialogControls = dialogElement.querySelector('.edit_dialog_controls');

			dialogControls.style.display = 'none';

			confirmPrompt.appendChild(confirmText);
			confirmPrompt.appendChild(confirmPos);
			confirmPrompt.appendChild(confirmNeg);
			dialogBox.appendChild(confirmPrompt);

			confirmNeg.addEventListener('click', handleEditorRequest);
			document.addEventListener('click', function() {
				if (!dialogElement.contains(event.target)) {
					handleEditorRequest(event);
				}
			});

			confirmPos.addEventListener('click', event => {
				event.preventDefault();
				event.stopPropagation();
				removeWaypoint(event.target.closest('.waypoint.coordinates_item'));
				removeEscapeHandlers();
			});
		}

		function moveWaypointFromDialog(event) {
			let moveDirection = event.target.dataset.direction;
			let wptElement = event.target.closest('.waypoint.coordinates_item');

			moveWaypoint(wptElement, moveDirection)
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

		function handleEditorRequest(event) {

			let currentDialog = document.getElementById('polyline_dialog');
			let eventParentEl = event.target.closest('.waypoint.coordinates_item');
			let editButtonEl = event.target.classList.contains('coordinates_edit') ?
					event.target :
					event.target.closest('.coordinates_edit');

			if (currentDialog !== null) {
				// If a dialog already exists
				if (!eventParentEl.contains(currentDialog)) {
					// If user clicked a different element
					closeDialog();
					removeEscapeHandlers();

					createEditDialogElements(eventParentEl);
					addEscapeHandlers();
					editButtonEl.classList.add('editing');
				} else {
					// If user clicked the same element
					closeDialog();
				}
			} else {
				createEditDialogElements(eventParentEl);
				addEscapeHandlers();
				editButtonEl.classList.add('editing');
			}
			
			/*
			*  Stop event from bubbling up in the DOM. Without this,
			*  the events in addEscapeHandlers are triggered automatically.
			*/
			
			event.preventDefault();
			event.stopPropagation();
		}

		function createEditDialogElements(wptElement) {

			let diContainer = document.createElement('div');
				diContainer.id = 'polyline_dialog';
				diContainer.classList.add('edit_dialog_container');
			
			let diOverlay = document.createElement('div');
				diOverlay.classList.add('edit_dialog_overlay');

			let diBox = document.createElement('div');
				diBox.classList.add('edit_dialog_box');

			let diControls = document.createElement('div');
				diControls.classList.add('edit_dialog_controls');

			let moveUp = document.createElement('a');
				moveUp.classList.add('button');
				moveUp.setAttribute('data-event', 'wpt-move');
				moveUp.setAttribute('data-direction', 'up');
				moveUp.innerText = 'Move Up';

			let remove = document.createElement('a');
				remove.classList.add('button', 'button_warning');
				remove.setAttribute('data-event', 'wpt-remove');
				remove.innerText = 'Remove';

			let moveDown = document.createElement('a');
				moveDown.classList.add('button');
				moveDown.setAttribute('data-event', 'wpt-move');
				moveDown.setAttribute('data-direction', 'down');
				moveDown.innerText = 'Move Down';

			diControls.appendChild(moveUp);
			diControls.appendChild(remove);
			diControls.appendChild(moveDown);
			diBox.appendChild(diOverlay);
			diBox.appendChild(diControls);
			diContainer.appendChild(diBox);

			wptElement.appendChild(diContainer);

			moveUp.addEventListener('click', moveWaypointFromDialog);
			remove.addEventListener('click', confirmRemoveWaypoint);
			moveDown.addEventListener('click', moveWaypointFromDialog);
		}

		function addEscapeHandlers() {
			document.addEventListener('click', dialogClickHandler);
			document.addEventListener('keydown', dialogEscapeHandler);
		}

		function removeEscapeHandlers() {
			document.removeEventListener('click', dialogClickHandler);
			document.removeEventListener('keydown', dialogEscapeHandler);
		}

		/*
		*  If we click outside the dialog or press "Esc," close the dialog.
		*  (Storing handlers as namedfunctions so they can be removed with
		*  removeEventListener.)
		*/

		function dialogClickHandler(event) {

			let dialogElement = document.getElementById('polyline_dialog');
			if (!dialogElement.contains(event.target)) {
				closeDialog();
			}

			event.preventDefault();
			event.stopPropagation();
		}

		function dialogEscapeHandler(event) {
			if (event.key === "Escape") {
				closeDialog();
			}
		}
		
		function closeDialog() {

			let dialogElement = document.getElementById('polyline_dialog');
			let editingFlag = document.querySelector('.editing');
			let controlElement;
			
			if (dialogElement !== null) {
				controlElement = dialogElement.parentNode.querySelector('.coordinates_edit');
				dialogElement.remove();
			}

			/*
			*  Clean up visual indicators
			*/

			if (controlElement !== null) {
				controlElement.blur();
			}
			if (editingFlag !== null) {
				editingFlag.classList.remove('editing');
			}

			removeEscapeHandlers();
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
		
		acf.add_action('ready_field/type=polyline-two', initialize_field);
		acf.add_action('append_field/type=polyline-two', initialize_field);
		
		
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
