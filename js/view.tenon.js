(function( $ ) { 'use strict';

	$( 'button.toggle-results' ).on( 'click', function(e) {
		e.preventDefault();
		var expanded = $( this ).attr( 'aria-expanded' );
		if ( expanded == 'true' ) {
			$( '.tenon-results' ).css( 'height', '6em' );
			$( this ).attr( 'aria-expanded', 'false' ).text( ami18n.expand );			
		} else {
			$( '.tenon-results' ).css( 'height', '50%' );
			$( this ).attr( 'aria-expanded', 'true' ).text( ami18n.collapse );
		}
	});
	
	$( '.view-results' ).hide();	
	$( 'button.toggle-view' ).on( 'click', function(e) {
		e.preventDefault();
		var controls = $( this ).attr( 'aria-controls' );
		var expanded = $( this ).attr( 'aria-expanded' );
		if ( expanded == 'true' ) {
			$( '#' + controls ).hide();
			$( this ).attr( 'aria-expanded', 'false' ).text( ami18n.expand );			
		} else {
			$( '#' + controls ).show();
			$( this ).attr( 'aria-expanded', 'true' ).text( ami18n.collapse );
		}
	});	
	
}(jQuery));