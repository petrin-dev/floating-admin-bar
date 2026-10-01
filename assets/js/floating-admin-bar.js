( function () {
	'use strict';

	if ( ! window.fabData ) {
		return;
	}

	var STORAGE_KEY = 'fabPosition';
	var DRAG_THRESHOLD = 6;

	var isCoarsePointer = ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;

	var widget = document.createElement( 'div' );
	widget.id = 'fab-widget';

	var handle = document.createElement( 'button' );
	handle.type = 'button';
	handle.className = 'fab-handle';
	handle.setAttribute( 'aria-label', 'Admin menu — drag to move, click to open' );
	handle.setAttribute( 'aria-expanded', 'false' );
	handle.innerHTML = '<svg class="fab-handle-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
		'<circle cx="5" cy="5" r="2"></circle><circle cx="12" cy="5" r="2"></circle><circle cx="19" cy="5" r="2"></circle>' +
		'<circle cx="5" cy="12" r="2"></circle><circle cx="12" cy="12" r="2"></circle><circle cx="19" cy="12" r="2"></circle>' +
		'<circle cx="5" cy="19" r="2"></circle><circle cx="12" cy="19" r="2"></circle><circle cx="19" cy="19" r="2"></circle>' +
		'</svg>';

	var panel = document.createElement( 'div' );
	panel.className = 'fab-panel';

	var flyout = document.createElement( 'div' );
	flyout.className = 'fab-flyout';

	widget.appendChild( handle );
	widget.appendChild( panel );
	widget.appendChild( flyout );
	document.body.appendChild( widget );

	renderGrid( fabData.menu || [] );
	restorePosition();
	updateAnchor();
	bindInteractions();

	window.addEventListener( 'resize', function () {
		clampToViewport();
		updateAnchor();
	} );

	function iconClass( id ) {
		var map = fabData.iconMap || {};
		return map[ id ] || 'dashicons-admin-generic';
	}

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str || '';
		return div.innerHTML;
	}

	function renderGrid( nodes ) {
		panel.innerHTML = '';

		nodes.forEach( function ( node ) {
			var cell;

			if ( node.html ) {
				cell = document.createElement( 'div' );
				cell.className = 'fab-cell fab-cell-html';
				cell.innerHTML = node.html;
				panel.appendChild( cell );
				return;
			}

			if ( node.children && node.children.length && ! node.href ) {
				cell = document.createElement( 'button' );
				cell.type = 'button';
				cell.setAttribute( 'aria-haspopup', 'true' );
				cell.setAttribute( 'aria-expanded', 'false' );
				cell.addEventListener( 'click', function ( e ) {
					e.stopPropagation();
					openFlyout( node, cell );
				} );
				cell.addEventListener( 'pointerenter', function () {
					if ( ! isCoarsePointer ) {
						openFlyout( node, cell );
					}
				} );
			} else {
				cell = document.createElement( 'a' );
				cell.href = node.href || '#';
				if ( node.target ) {
					cell.target = node.target;
				}
				if ( node.title ) {
					cell.title = node.title;
				}

				if ( node.children && node.children.length ) {
					cell.setAttribute( 'aria-haspopup', 'true' );
					cell.setAttribute( 'aria-expanded', 'false' );
					cell.addEventListener( 'pointerenter', function () {
						if ( ! isCoarsePointer ) {
							openFlyout( node, cell );
						}
					} );
					// Keyboard users can't hover — reveal the submenu on focus so
					// it's reachable by Tab, without blocking Enter's navigation.
					cell.addEventListener( 'focus', function () {
						openFlyout( node, cell );
					} );
				}
			}

			cell.className = 'fab-cell';
			cell.innerHTML = '<span class="dashicons ' + iconClass( node.id ) + '" aria-hidden="true"></span>' +
				'<span class="fab-cell-label">' + escapeHtml( node.label ) + '</span>';

			panel.appendChild( cell );
		} );
	}

	function openFlyout( node, cell ) {
		Array.prototype.forEach.call( panel.querySelectorAll( '.fab-cell.fab-active' ), function ( el ) {
			el.classList.remove( 'fab-active' );
			el.setAttribute( 'aria-expanded', 'false' );
		} );
		cell.classList.add( 'fab-active' );
		cell.setAttribute( 'aria-expanded', 'true' );

		flyout.innerHTML = '';
		renderFlyoutItems( flyout, node.children );
		flyout.classList.add( 'fab-open' );
	}

	function renderFlyoutItems( container, nodes ) {
		nodes.forEach( function ( node ) {
			if ( node.html ) {
				var htmlWrap = document.createElement( 'div' );
				htmlWrap.className = 'fab-flyout-html';
				htmlWrap.innerHTML = node.html;
				container.appendChild( htmlWrap );
				return;
			}

			if ( node.children && node.children.length ) {
				var group = document.createElement( 'div' );
				group.className = 'fab-flyout-group';

				var toggle = document.createElement( 'button' );
				toggle.type = 'button';
				toggle.className = 'fab-flyout-item';
				toggle.textContent = node.label;
				toggle.addEventListener( 'click', function () {
					group.classList.toggle( 'fab-open' );
				} );

				var subitems = document.createElement( 'div' );
				subitems.className = 'fab-flyout-subitems';
				renderFlyoutItems( subitems, node.children );

				group.appendChild( toggle );
				group.appendChild( subitems );
				container.appendChild( group );
				return;
			}

			var link = document.createElement( 'a' );
			link.className = 'fab-flyout-item';
			link.href = node.href || '#';
			if ( node.target ) {
				link.target = node.target;
			}
			link.textContent = node.label;
			container.appendChild( link );
		} );
	}

	function closeAll() {
		widget.classList.remove( 'fab-expanded' );
		flyout.classList.remove( 'fab-open' );
		Array.prototype.forEach.call( panel.querySelectorAll( '.fab-cell.fab-active' ), function ( el ) {
			el.classList.remove( 'fab-active' );
			el.setAttribute( 'aria-expanded', 'false' );
		} );
		handle.setAttribute( 'aria-expanded', 'false' );
	}

	function openGrid() {
		widget.classList.add( 'fab-expanded' );
		handle.setAttribute( 'aria-expanded', 'true' );
	}

	var closeTimer = null;

	function scheduleClose() {
		clearTimeout( closeTimer );
		closeTimer = setTimeout( closeAll, 400 );
	}

	function cancelScheduledClose() {
		clearTimeout( closeTimer );
	}

	function bindInteractions() {
		if ( ! isCoarsePointer ) {
			widget.addEventListener( 'pointerenter', function () {
				cancelScheduledClose();
				openGrid();
			} );
			widget.addEventListener( 'pointerleave', scheduleClose );
		}

		// Keyboard activation (Enter/Space on a focused button) dispatches a "click"
		// with detail === 0, unlike a real mouse click (detail >= 1) — this lets
		// keyboard users toggle the grid without disturbing the hover-driven flow
		// that fine-pointer mouse users already get.
		handle.addEventListener( 'click', function ( e ) {
			if ( 0 !== e.detail ) {
				return;
			}

			if ( widget.classList.contains( 'fab-expanded' ) ) {
				closeAll();
			} else {
				cancelScheduledClose();
				openGrid();
			}
		} );

		widget.addEventListener( 'focusout', function ( e ) {
			if ( ! widget.contains( e.relatedTarget ) ) {
				closeAll();
			}
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( isCoarsePointer && ! widget.contains( e.target ) ) {
				closeAll();
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				closeAll();
			}
		} );

		bindDrag();
	}

	function bindDrag() {
		var dragging = false;
		var moved = false;
		var startX, startY, startRight, startBottom;

		handle.addEventListener( 'pointerdown', function ( e ) {
			dragging = true;
			moved = false;
			startX = e.clientX;
			startY = e.clientY;

			var rect = widget.getBoundingClientRect();
			startRight = window.innerWidth - rect.right;
			startBottom = window.innerHeight - rect.bottom;

			handle.setPointerCapture( e.pointerId );
		} );

		handle.addEventListener( 'pointermove', function ( e ) {
			if ( ! dragging ) {
				return;
			}

			var dx = e.clientX - startX;
			var dy = e.clientY - startY;

			if ( ! moved && Math.sqrt( ( dx * dx ) + ( dy * dy ) ) < DRAG_THRESHOLD ) {
				return;
			}

			moved = true;
			widget.classList.add( 'fab-dragging' );

			applyPosition( startRight - dx, startBottom - dy );
		} );

		handle.addEventListener( 'pointerup', function ( e ) {
			if ( ! dragging ) {
				return;
			}

			dragging = false;
			widget.classList.remove( 'fab-dragging' );
			handle.releasePointerCapture( e.pointerId );

			if ( moved ) {
				savePosition();
				updateAnchor();
				return;
			}

			if ( isCoarsePointer ) {
				if ( widget.classList.contains( 'fab-expanded' ) ) {
					closeAll();
				} else {
					openGrid();
				}
			}
		} );
	}

	function applyPosition( right, bottom ) {
		var rect = widget.getBoundingClientRect();
		var maxRight = Math.max( 0, window.innerWidth - rect.width );
		var maxBottom = Math.max( 0, window.innerHeight - rect.height );

		right = Math.max( 0, Math.min( right, maxRight ) );
		bottom = Math.max( 0, Math.min( bottom, maxBottom ) );

		widget.style.right = right + 'px';
		widget.style.bottom = bottom + 'px';
		widget.style.left = 'auto';
		widget.style.top = 'auto';
	}

	function savePosition() {
		var rect = widget.getBoundingClientRect();

		try {
			localStorage.setItem( STORAGE_KEY, JSON.stringify( {
				right: window.innerWidth - rect.right,
				bottom: window.innerHeight - rect.bottom,
			} ) );
		} catch ( err ) {
			// localStorage unavailable (private mode, quota) — position just won't persist.
		}
	}

	function restorePosition() {
		var saved = null;

		try {
			saved = JSON.parse( localStorage.getItem( STORAGE_KEY ) );
		} catch ( err ) {
			saved = null;
		}

		if ( saved && typeof saved.right === 'number' && typeof saved.bottom === 'number' ) {
			applyPosition( saved.right, saved.bottom );
		} else {
			var rect = widget.getBoundingClientRect();
			applyPosition( window.innerWidth - 24 - rect.width, 24 );
		}
	}

	function clampToViewport() {
		var rect = widget.getBoundingClientRect();
		applyPosition( window.innerWidth - rect.right, window.innerHeight - rect.bottom );
	}

	function updateAnchor() {
		var rect = widget.getBoundingClientRect();
		var centerX = rect.left + ( rect.width / 2 );
		var centerY = rect.top + ( rect.height / 2 );

		widget.classList.toggle( 'fab-anchor-left', centerX < window.innerWidth / 2 );
		widget.classList.toggle( 'fab-anchor-right', centerX >= window.innerWidth / 2 );
		widget.classList.toggle( 'fab-anchor-top', centerY < window.innerHeight / 2 );
		widget.classList.toggle( 'fab-anchor-bottom', centerY >= window.innerHeight / 2 );
	}
} )();
