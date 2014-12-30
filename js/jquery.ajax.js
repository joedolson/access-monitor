(function ($) {
	$(function() {
		if ( am_current_screen == 'dashboard' ) {
			var src = document.documentElement.outerHTML; 		
		} else {
			var src = document.getElementById('wpbody-content').outerHTML; 
		}
		var query = {
				'action' : am_ajax_action,
				'tenon' : src,
				'current_screen' : am_current_screen
			};
		$( '#wp-admin-bar-tenonCheck').on( 'click', function() {
			$.ajax( {
				type: 'POST',
				url: am_ajax_url,
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
	
	$(function() {
		$('#add_field').on( 'click', function (e) {
			e.preventDefault();
			var maxFields = 20;
			var num = $('.clonedInput').length;
			var newNum = new Number(num + 1);
			var newElem = $('#field' + num).clone().attr( 'id', 'field' + newNum );
			$('#field' + num).after(newElem);
			$('#del_field').removeAttr('disabled');
			if ( newNum == maxFields ) {
				$('#add_field').attr('disabled', 'disabled');
			}
		});

		$('#del_field').on('click', function (e) {
			e.preventDefault();
			var num = $('.clonedInput').length; 
			$('#field' + num).remove();  
			$('#add_field').removeAttr('disabled');
			if ( num - 1 == 1 ) {
				$('#del_field').attr('disabled', 'disabled');
			}
		});
		$('#del_field').attr('disabled', 'disabled');
	});	
	
}(jQuery));