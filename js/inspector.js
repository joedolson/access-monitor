jQuery( document ).ready( function( $ ) {
	$( '.am-errors' ).hide();
	$( '#publish[name="publish"], button.inspect-a11y' ).on( 'click', function( e ) {
		var preview_url = $( '#post-preview' ).attr( 'href' );
		var preview_content = '';
		$.ajax({
		   url:preview_url,
		   type:'GET',
		   success: function(data){
				preview_content = $(data).find( am.container ).html();
				if ( !preview_content || preview_content == '' ) {
					preview_content = am.failed;
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
		var expanded = $( this ).attr( 'aria-expanded' );
		if ( expanded == 'false' ) {
			$( this ).text( am.hide ).attr( 'aria-expanded', 'true' );
		} else {
			$( this ).text( am.show ).attr( 'aria-expanded', 'false' );			
		}
	});	
});