/* Product Video Solution for WooCommerce by Sudipta Das (https://github.com/shiningsudipto) – front end: swap the poster for the real player on click. */
( function () {
	'use strict';

	function play( poster ) {
		var box = poster.closest( '.pvsw-video' );
		var tpl = box && box.querySelector( 'template.pvsw-player' );
		if ( ! tpl ) {
			return;
		}
		box.innerHTML = '';
		box.appendChild( tpl.content.cloneNode( true ) );
		box.classList.add( 'pvsw-playing' );

		var video = box.querySelector( 'video' );
		if ( video ) {
			var started = video.play();
			// If the browser still refuses sound, fall back to muted playback.
			if ( started && typeof started.catch === 'function' ) {
				started.catch( function () {
					video.muted = true;
					video.play();
				} );
			}
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var poster = e.target.closest && e.target.closest( '.pvsw-poster' );
		if ( poster ) {
			e.preventDefault();
			play( poster );
			return;
		}
		// Without controls, a click on the video toggles play and pause.
		var bare = e.target.closest && e.target.closest( '.pvsw-video video:not([controls])' );
		if ( bare ) {
			if ( bare.paused ) {
				bare.play();
			} else {
				bare.pause();
			}
		}
	} );
} )();
