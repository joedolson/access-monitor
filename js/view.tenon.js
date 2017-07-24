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
		
		$( path )
			.wrap( '<div class="tenon-error ' + priority + ' ' + certainty + '" style="display: ' + display + ';" id="source-tenon-' + noteID + '" tabindex="-1"></div>' )
			.attr( 'aria-describedby', id ).css( { 'outline' : '2px solid red' } )
			.after( '<button class="toggle-view" aria-controls="' + id + '" data-notes="tenon-notes-' + noteID + '"><span class="dashicons dashicons-plus" aria-hidden="true"></span> <span class="screen-reader-text">' + ami18n.expand + '</span></button><div role="tooltip" class="tenon-error-comment" id="' + id + '"><p>' + notes + '</p></div>' );
	});
	$( '.tenon-error-comment' ).hide();

	$( 'button.toggle-results' ).on( 'click', function(e) {
		e.preventDefault();
		var expanded = $( this ).attr( 'aria-expanded' );
		if ( expanded == 'true' ) {
			$( '.tenon-results' ).css( 'height', '6em' );
			$( this ).attr( 'aria-expanded', 'false' ).html( '<span class="dashicons dashicons-plus" aria-hidden="true"></span>' + ami18n.expand );			
		} else {
			$( '.tenon-results' ).css( 'height', '50%' );
			$( this ).attr( 'aria-expanded', 'true' ).html( '<span class="dashicons dashicons-minus" aria-hidden="true"></span>' + ami18n.collapse );
		}
	});
	
	$( '.view-results' ).hide();	
	$( 'button.toggle-view' ).on( 'click', function(e) {
		e.preventDefault();
		$( '.tenon-result' ).removeClass( 'highlight-error' );
		var controls = $( this ).attr( 'aria-controls' );
		var expanded = $( this ).attr( 'aria-expanded' );
		var noteID   = $( this ).attr( 'data-notes' );
		if ( expanded == 'true' ) {
			$( '#' + controls ).hide();
			$( this ).attr( 'aria-expanded', 'false' ).html( '<span class="dashicons dashicons-plus" aria-hidden="true"></span><span class="screen-reader-text">' + ami18n.expand+ '</span>' );
		} else {
			$( '#' + controls ).show();
			$( this ).attr( 'aria-expanded', 'true' ).html( '<span class="dashicons dashicons-minus" aria-hidden="true"></span><span class="screen-reader-text">' + ami18n.collapse + '</span>' );
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