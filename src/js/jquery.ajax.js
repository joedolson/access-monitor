(function ($) {

	$(function() {

		$( 'a.menu-icon-tenon-report .wp-menu-name' ).text( am.plugin_name );

		var src = document.documentElement.outerHTML;
		var query = {
				'action' : am.ajax_action,
				'tenon' : src
			};
		$( '#wp-admin-bar-tenonCheck a' ).attr( 'aria-live', 'polite' ).append( ' <span class="tenon-updating dashicons dashicons-update" aria-hidden="true"></span><span class="screen-reader-text"></span>' );

		$( '#wp-admin-bar-tenonCheck a' ).on( 'click', function(e) {
			e.preventDefault();
			$( this ).find( '.tenon-updating' ).addClass( 'animating' );
			$( this ).find( '.screen-reader-text' ).text( ami18n.updating );
			$.ajax({
				type: 'POST',
				url: am.ajax_url,
				data: query,
				dataType: "json",
				success: function( data ) {
					var response = data.formatted;
					$( '#tenon' ).html( response );

					var xpaths = $( '.xpath-data code' );
					xpaths.each(function () {
						var xpath     = this.innerHTML;
						var title     = $( this ).attr( 'data-title' ).replace( '`', "<code>" ).replace( '`', "</code>" );
						var id        = $( this ).attr( 'data-id' );
						var noteID    = id.replace( 'tenon-', '' );
						var notes     = "<a href='#tenon-notes-" + noteID + "'>" + title + "</a>";
						var path      = xPathToCss( xpath );
						var display   = $( path ).css( 'display' );
						var priority  = $( this ).attr( 'data-priority' );
						var certainty = 'cert-' + $( this ).attr( 'data-certainty' );

						var wrapper   = 'div';
						if ( $( path ).is( 'option' ) ) {
							if ( $( path ).parent().is( 'optgroup' ) ) {
								var wrapper = 'option';
							} else {
								var wrapper = 'optgroup';
							}
						}

						$( path )
							.wrap( '<' + wrapper + ' class="tenon-error ' + priority + ' ' + certainty + '" style="display: ' + display + ';" id="source-tenon-' + noteID + '" tabindex="-1"></' + wrapper + '>' )
							.attr( 'aria-describedby', noteID ).css( { 'outline' : '2px solid red', 'outline-offset' : '6px' } )
							.after( '<a href="#tenon-notes-' + noteID + '" class="toggle-view ' + priority + ' ' + certainty + '"><span class="dashicons dashicons-arrow-down" aria-hidden="true"></span> <span class="screen-reader-text">' + ami18n.view + '</span></a>' );
					});

				},
				error: function(data) {
					$( '#tenon' ).html( "Tenon request failed" );
				},
				complete: function(data) {
					$( '#wp-admin-bar-tenonCheck a .tenon-updating' ).removeClass( 'animating' ).addClass( 'complete' );
					$( '#wp-admin-bar-tenonCheck a .screen-reader-text' ).text( ami18n.completed );
				}
			});

		});
	});

	$(function() {
		$( '.codepanel' ).hide();
		$( 'button.snippet' ).on( 'click', function(e) {
			e.preventDefault();
			var target = $( this ).attr( 'data-target' );
			var visible = $( '#' + target ).is( ':visible' );
			$( '.codepanel' ).hide();
			$( 'button.snippet' ).attr( 'aria-expanded', 'false' );
			if ( visible ) {
				$( '#' + target ).hide();
				$( this ).attr( 'aria-expanded', 'false' );
			} else {
				$( '#' + target ).show();
				$( this ).attr( 'aria-expanded', 'true' );
			}
		});
		$( 'button.close' ).on( 'click', function(e) {
			e.preventDefault();
			$( this ).parent( '.codepanel' ).hide();
			var source = $( this ).parent( '.codepanel' ).attr( 'id' );
			$( 'button[data-target="'+source+'"]' ).attr( 'aria-expanded', 'false' ).trigger('focus');
		});
	});

	$(function() {
		$( '.report-options' ).hide();
		$( 'button.toggle-options' ).on( 'click', function(e) {
			e.preventDefault();
			var visible = $( '.report-options' ).is( ':visible' );
			if ( visible ) {
				$( '.report-options' ).hide();
				$( this ).attr( 'aria-expanded', 'false' );
				$( 'button.toggle-options span' ).removeClass( 'dashicons-minus' );
				$( 'button.toggle-options span' ).addClass( 'dashicons-plus-alt' );
			} else {
				$( '.report-options' ).show();
				$( this ).attr( 'aria-expanded', 'true' );
				$( 'button.toggle-options span' ).removeClass( 'dashicons-plus-alt' );
				$( 'button.toggle-options span' ).addClass( 'dashicons-minus' );
			}
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