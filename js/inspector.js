jQuery( document ).ready( function( $ ) {
	$( '.am-errors' ).hide();
	$( '#publish' ).on( 'click', function( e ) {
		var preview_url = $( '#post-preview' ).attr( 'href' );
		var preview_content = '';
		$.ajax({
		   url:preview_url,
		   type:'GET',
		   success: function(data){
				preview_content = $(data).find( am.container ).html();
				if ( !preview_content || preview_content == '' ) {
					preview_content = 'Could not retrieve content from your content area. Set your content container in Access Monitor settings.';
				}
				
				var query = {
					'action' : am_ajax_action,
					'tenon' : preview_content,
					'current_screen' : am_current_screen,
					'level' : am.level,
					'certainty' : am.certainty,
					'priority' : am.priority,
					'fragment' : '1'
				};
				
				$.ajax({
					data: query,
					url: am_ajax_url,
					dataType: 'json',
					success: function( data ) {
						var response_content = data.formatted;
						var grade = data.grade;
						if ( grade < am.grade ) {
							$( '#am-errors' ).html( response_content );
							$( '.am-errors .score' ).text( grade.toFixed(2) + '%' );
							$( '.am-errors' ).addClass( 'updated error' ).show().attr( 'tabindex', '-1' ).focus();
							e.preventDefault();	
						}
					},
					error: function( data ) {
						/* console.log(data.responseText); */
					}	
				} );
				
				return false;			   
		   }
		});
		e.preventDefault();
	});
	
	/*$(document).ajaxError( function( event, requests, settings ) {
		console.log( event );
		console.log( requests );
		console.log( settings );
	});*/
	
	$( '.am-toggle' ).on( 'click', function(e) {
		e.preventDefault();
		$( '#am-errors' ).toggle();
		var verb = $( this ).find( '.am-verb' ).text();
		if ( verb == 'Show' ) {
			$( this ).find( '.am-verb' ).text( 'Hide' );
		} else {
			$( this ).find( '.am-verb' ).text( 'Show' );			
		}
	});	
});