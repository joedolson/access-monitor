(function( $ ) { 'use strict';

	$( 'button.toggle-results' ).on( 'click', function(e) {
		e.preventDefault();
		var expanded = $( this ).attr( 'aria-expanded' );
		if ( expanded == 'true' ) {
			$( '.tenon-results' ).css( 'height', '6em' );
			$( this ).attr( 'aria-expanded', 'false' ).text( 'Expand' );			
		} else {
			$( '.tenon-results' ).css( 'height', '50%' );
			$( this ).attr( 'aria-expanded', 'true' ).text( 'Collapse' );
		}
	});
	
}(jQuery));