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
}(jQuery));