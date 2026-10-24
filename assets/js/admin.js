/* Product Video Solution for WooCommerce by Sudipta Das (https://github.com/shiningsudipto) – "Product Video" tab on the product edit screen. */
jQuery( function ( $ ) {
	'use strict';

	var $box = $( '.pvsw-admin' );
	if ( ! $box.length ) {
		return;
	}

	var cfg = window.pvswAdmin || { i18n: {} };
	var preview = document.getElementById( 'pvsw-preview' );
	var $ytPreview = $box.find( '.pvsw-yt-preview' );
	var $url = $( '#pvsw_url' );

	function source() {
		return $box.find( 'input[name="pvsw[source]"]:checked' ).val() || 'library';
	}

	function thumbMode() {
		return $box.find( 'input[name="pvsw[thumb_mode]"]:checked' ).val() || 'auto';
	}

	function youtubeId( url ) {
		var m = String( url ).match( /(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/|v\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/ );
		return m ? m[ 1 ] : '';
	}

	function isVimeo( url ) {
		return /vimeo\.com\//.test( String( url ) );
	}

	// External files need CORS so a frame can be copied; if the server refuses, reload without it so the preview still plays.
	preview.addEventListener( 'error', function () {
		if ( preview.crossOrigin && ! preview.dataset.retried ) {
			preview.dataset.retried = '1';
			var src = preview.src;
			preview.removeAttribute( 'crossorigin' );
			preview.src = src;
		}
	} );

	function setPreview( src, external ) {
		if ( preview.dataset.current === src ) {
			return;
		}
		preview.dataset.current = src;
		preview.dataset.retried = '';
		if ( external ) {
			preview.crossOrigin = 'anonymous';
		} else {
			preview.removeAttribute( 'crossorigin' );
		}
		if ( src ) {
			preview.src = src;
		} else {
			preview.removeAttribute( 'src' );
			preview.load();
		}
	}

	function refresh() {
		var s = source();
		var url = $.trim( $url.val() );

		$box.find( '[data-pvsw-show]' ).each( function () {
			$( this ).toggle( String( $( this ).data( 'pvsw-show' ) ).split( ' ' ).indexOf( s ) > -1 );
		} );

		var fileSrc = '';
		if ( s === 'library' ) {
			fileSrc = $box.find( '.pvsw-attachment-id' ).data( 'url' ) || '';
		} else if ( s === 'external' && ! isVimeo( url ) ) {
			fileSrc = url;
		}
		setPreview( fileSrc, s === 'external' );
		$( preview ).toggle( !! fileSrc );

		var yt = s === 'youtube' ? youtubeId( url ) : '';
		$ytPreview.attr( 'src', yt ? 'https://i.ytimg.com/vi/' + yt + '/hqdefault.jpg' : '' ).toggle( !! yt );

		// A frame can only be captured from a real video file.
		var canCapture = !! fileSrc;
		var $frame = $box.find( 'input[name="pvsw[thumb_mode]"][value="frame"]' );
		$frame.prop( 'disabled', ! canCapture ).closest( 'label' ).toggleClass( 'pvsw-disabled', ! canCapture );
		if ( ! canCapture && thumbMode() === 'frame' ) {
			$box.find( 'input[name="pvsw[thumb_mode]"][value="auto"]' ).prop( 'checked', true );
		}

		var t = thumbMode();
		$box.find( '[data-pvsw-thumb]' ).each( function () {
			$( this ).toggle( $( this ).data( 'pvsw-thumb' ) === t );
		} );
	}

	function pickMedia( type, title, done ) {
		var frame = wp.media( {
			title: title,
			library: { type: type },
			button: { text: cfg.i18n.use },
			multiple: false
		} );
		frame.on( 'select', function () {
			done( frame.state().get( 'selection' ).first().toJSON() );
		} );
		frame.open();
	}

	$box.on( 'change', 'input[name="pvsw[source]"], input[name="pvsw[thumb_mode]"]', refresh );
	$url.on( 'change input', refresh );

	$box.on( 'click', '.pvsw-pick-video', function ( e ) {
		e.preventDefault();
		pickMedia( 'video', cfg.i18n.chooseVideo, function ( a ) {
			$box.find( '.pvsw-attachment-id' ).val( a.id ).data( 'url', a.url );
			$box.find( '.pvsw-file-name' ).text( a.filename || a.title );
			$box.find( '.pvsw-remove-video' ).show();
			refresh();
		} );
	} );

	$box.on( 'click', '.pvsw-remove-video', function ( e ) {
		e.preventDefault();
		$box.find( '.pvsw-attachment-id' ).val( '' ).data( 'url', '' );
		$box.find( '.pvsw-file-name' ).text( '' );
		$( this ).hide();
		refresh();
	} );

	$box.on( 'click', '.pvsw-pick-image', function ( e ) {
		e.preventDefault();
		pickMedia( 'image', cfg.i18n.chooseImage, function ( a ) {
			var src = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
			$box.find( '.pvsw-image-id' ).val( a.id );
			$box.find( '.pvsw-image-img' ).attr( 'src', src ).show();
			$box.find( '.pvsw-remove-image' ).show();
		} );
	} );

	$box.on( 'click', '.pvsw-remove-image', function ( e ) {
		e.preventDefault();
		$box.find( '.pvsw-image-id' ).val( '' );
		$box.find( '.pvsw-image-img' ).attr( 'src', '' ).hide();
		$( this ).hide();
	} );

	$box.on( 'click', '.pvsw-capture-frame', function ( e ) {
		e.preventDefault();
		if ( ! preview.videoWidth ) {
			window.alert( cfg.i18n.noFrame );
			return;
		}

		var width = Math.min( preview.videoWidth, 1280 );
		var height = Math.round( preview.videoHeight * width / preview.videoWidth );
		var canvas = document.createElement( 'canvas' );
		var data;
		canvas.width = width;
		canvas.height = height;
		try {
			canvas.getContext( '2d' ).drawImage( preview, 0, 0, width, height );
			data = canvas.toDataURL( 'image/jpeg', 0.85 );
		} catch ( err ) {
			window.alert( cfg.i18n.tainted );
			return;
		}

		var $btn = $( this ).prop( 'disabled', true ).text( cfg.i18n.saving );
		$.post( cfg.ajaxUrl, {
			action: 'pvsw_save_frame',
			nonce: cfg.nonce,
			product_id: $box.data( 'product' ),
			image: data
		} ).done( function ( res ) {
			if ( res && res.success ) {
				$box.find( '.pvsw-frame-id' ).val( res.data.id );
				$box.find( '.pvsw-frame-img' ).attr( 'src', res.data.url ).show();
			} else {
				window.alert( ( res && res.data && res.data.message ) || cfg.i18n.failed );
			}
		} ).fail( function ( xhr ) {
			var msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
			window.alert( msg || cfg.i18n.failed );
		} ).always( function () {
			$btn.prop( 'disabled', false ).text( cfg.i18n.capture );
		} );
	} );

	// Browsers only autoplay muted video, so autoplay switches "Start muted" on.
	$box.on( 'change', '#pvsw_autoplay', function () {
		if ( this.checked ) {
			$( '#pvsw_muted' ).prop( 'checked', true );
		}
	} );

	refresh();
} );
