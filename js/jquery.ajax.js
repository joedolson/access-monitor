(function ($) {
	$(function() {
		if ( tc_current_screen == 'dashboard' ) {
			var src = document.documentElement.outerHTML; 		
		} else {
			var src = document.getElementById('wpbody-content').outerHTML; 
		}
		var query = {
				'action' : tc_ajax_action,
				'tenon' : src,
				'current_screen' : tc_current_screen
			};
		$( '#wp-admin-bar-tenonCheck').on( 'click', function() {
			$.ajax( {
				type: 'POST',
				url: tc_ajax_url,
				data: query,
				success: function( data ) {
					$( '#tenon' ).html( data );
				},
				error: function(data) {
					$( '#tenon' ).html( "Tenon request failed" );
				}
			});
		});
	});
	
	$(function() {
		$( '.codepanel' ).hide();
		$( 'button.snippet' ).on( 'click', function(e) {
			e.preventDefault();
			var target = $( this ).attr( 'data-target' );
			$( '.codepanel' ).hide();
			$( 'button.snippet' ).attr( 'aria-expanded', 'false' );
			$( '#' + target ).show();
			$( '#' + target + ' button.close' ).focus();
			$( this ).attr( 'aria-expanded', 'true' );
		});
		$( 'button.close' ).on( 'click', function(e) {
			e.preventDefault();
			$( this ).parent( '.codepanel' ).hide();
			var source = $( this ).parent( '.codepanel' ).attr( 'id' );
			$( 'button[data-target="'+source+'"]' ).attr( 'aria-expanded', 'false' ).focus();
		});
	});
	
}(jQuery));