(function( $ ) { 'use strict';

	var xpaths = $( '.xpath-data code' );
	xpaths.each(function () {
		var xpath    = this.innerHTML;
		var title    = $( this ).attr( 'data-title' ).replace( '`', "<code>" ).replace( '`', "</code>" );
		var id       = $( this ).attr( 'data-id' );
		var noteID   = id.replace( 'tenon-', '' );
		var notes    = "<a href='#tenon-notes-" + noteID + "'>" + title + "</a>";
		var path     = xPathToCss( xpath );
		var display  = $( path ).css( 'display' );
		var priority = $( this ).attr( 'data-priority' );
		var certainty = 'cert-' + $( this ).attr( 'data-certainty' );

		if  ( $( path ).width() < 20 ) {
			$( path ).css( 'width', '1.2em' ).css( 'display', 'inline-block' );
		}

		$( path )
			.attr( 'id', 'path-' + noteID )
			.wrap( '<div class="tenon-error ' + priority + ' ' + certainty + '" style="display: ' + display + ';" id="source-tenon-' + noteID + '" tabindex="-1"></div>' )
			.attr( 'aria-describedby', id ).css( { 'outline' : '2px solid red', 'outline-offset' : '6px' } )
			.after( '<button class="toggle-view" aria-controls="' + id + '" data-notes="tenon-notes-' + noteID + '"><span class="dashicons dashicons-plus" aria-hidden="true"></span> <span class="screen-reader-text">' + ami18n.expand + '</span></button><div role="tooltip" class="tenon-error-comment" id="' + id + '"><p>' + notes + '</p></div>' );
	});
	$( '.tenon-error-comment' ).hide();

	var padding = ( window.innerHeight ) / 2;
	$( 'body' ).css( 'padding-bottom', padding );

	$( 'button.toggle-results' ).on( 'click', function(e) {
		e.preventDefault();
		var expanded = $( this ).attr( 'aria-expanded' );
		if ( expanded == 'true' ) {
			$( '.tenon-results' ).css( 'height', '120px' );
			$( 'body' ).css( 'padding-bottom', '120px' );
			$( this ).attr( 'aria-expanded', 'false' ).html( '<span class="dashicons dashicons-plus" aria-hidden="true"></span> ' + ami18n.expand );
		} else {
			var padding = ( window.innerHeight ) / 2;
			$( 'body' ).css( 'padding-bottom', padding );
			$( '.tenon-results' ).css( 'height', '50%' );
			$( this ).attr( 'aria-expanded', 'true' ).html( '<span class="dashicons dashicons-minus" aria-hidden="true"></span> ' + ami18n.collapse );
		}
	});

	$( '.view-results' ).hide();
	$( 'button.toggle-view' ).on( 'click', function(e) {
		e.preventDefault();
		$( '.tenon-result' ).removeClass( 'highlight-error' );
		var controls = $( this ).attr( 'aria-controls' );
		var expanded = $( this ).attr( 'aria-expanded' );
		var noteID   = $( this ).attr( 'data-notes' );
		var reference = $ ( this ).attr( 'data-notes' );
		var path = reference.replace( 'tenon-notes-', 'path-' );
		console.log( path );
		if ( expanded == 'true' ) {
			$( '#' + path ).css( { 'outline' : '0px dotted currentColor' } );
			$( '#' + controls ).hide();
			$( this ).attr( 'aria-expanded', 'false' ).html( '<span class="dashicons dashicons-plus" aria-hidden="true"></span> <span class="screen-reader-text">' + ami18n.expand+ '</span>' );
		} else {
			$( '#' + path ).css( { 'outline' : '1px dotted currentColor' } );
			$( '#' + controls ).show();
			$( this ).attr( 'aria-expanded', 'true' ).html( '<span class="dashicons dashicons-minus" aria-hidden="true"></span> <span class="screen-reader-text">' + ami18n.collapse + '</span>' );
			$( '#' + noteID ).addClass( 'highlight-error' );
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