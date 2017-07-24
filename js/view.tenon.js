(function( $ ) { 'use strict';

	var xpaths = $( '.xpath-data code' );
	xpaths.each(function () {
		var xpath    = this.innerHTML;
		var comments = $( this ).attr( 'data-comments' ).replace( '`', "<code>" ).replace( '`', "</code>" );
		var id       = $( this ).attr( 'data-id' );
		var path     = xPathToCss( xpath );
		$( path ).attr( 'aria-describedby', id ).css( { 'outline' : '2px solid red' } ).after( '<span class="tenon-error"><button class="toggle-view" aria-controls="' + id + '">' + ami18n.expand + '</button><span class="tenon-error-comment" id="' + id + '">' + comments + '</span></span>' );
	});
	$( '.tenon-error-comment' ).hide();

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

function xPathToCss(xpath) {
    return xpath
        .replace(/\[(\d+?)\]/g, function(s,m1){ return '['+(m1-1)+']'; })
        .replace(/\/{2}/g, '')
        .replace(/\/+/g, ' > ')
        .replace(/@/g, '')
        .replace(/\[(\d+)\]/g, ':eq($1)')
        .replace(/^\s+/, '');
}