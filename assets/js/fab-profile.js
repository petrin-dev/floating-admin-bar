( function () {
	'use strict';

	if ( ! window.fabProfileData ) {
		return;
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var toolbarRow = document.querySelector( '.user-admin-bar-front-wrap' );
		var toolbarCheckbox = document.getElementById( 'admin_bar_front' );

		if ( ! toolbarRow || ! toolbarCheckbox ) {
			return;
		}

		var row = document.createElement( 'tr' );
		row.className = 'user-floating-admin-bar-wrap';

		var th = document.createElement( 'th' );
		th.setAttribute( 'scope', 'row' );
		row.appendChild( th );

		var td = document.createElement( 'td' );
		var label = document.createElement( 'label' );
		label.setAttribute( 'for', fabProfileData.fieldName );

		var checkbox = document.createElement( 'input' );
		checkbox.type = 'checkbox';
		checkbox.name = fabProfileData.fieldName;
		checkbox.id = fabProfileData.fieldName;
		checkbox.value = '1';
		checkbox.checked = !! fabProfileData.checked;

		label.appendChild( checkbox );
		label.appendChild( document.createTextNode( ' ' + fabProfileData.label ) );
		td.appendChild( label );
		row.appendChild( td );

		toolbarRow.insertAdjacentElement( 'afterend', row );

		// We intentionally avoid the `disabled` attribute: a disabled checkbox is dropped
		// from the form submission entirely, which would silently flip the saved preference
		// to "off" every time someone saves their profile while the native toolbar is off.
		// Blocking interaction instead (without touching the submitted value) keeps the
		// stored preference accurate regardless of how many times the form gets saved.
		function syncAvailability() {
			var available = toolbarCheckbox.checked;
			checkbox.style.pointerEvents = available ? '' : 'none';
			checkbox.tabIndex = available ? 0 : -1;
			checkbox.setAttribute( 'aria-disabled', available ? 'false' : 'true' );
			label.style.opacity = available ? '' : '0.5';
		}

		syncAvailability();
		toolbarCheckbox.addEventListener( 'change', syncAvailability );
	} );
} )();
