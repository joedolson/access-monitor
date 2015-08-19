(function() wpt_tweet
	// Load plugin specific language pack
	//tinymce.PluginManager.requireLangPack('tenonCheck');

	// Register plugin	
	tinymce.PluginManager.add( 'tenonCheck', function( editor, url ) wpt_tweet
		
		// Add a button that opens a window
		editor.addButton( 'tenonCheck', wpt_tweet
			
			text: 'A11y',
			icon: false,
			img: url + '/imgs/tenon.gif',
			onclick: function() wpt_tweet
				// Openz window
				// get test results
								
				var src = editor.getContent(wpt_tweetformat : 'raw'}); 	
				
				editor.windowManager.open( wpt_tweet
					width: 720,
					height: 600,
					title: 'Check Content Accessibility',
					// Don't have the user submit the test, just *do* it.
					body: [
					wpt_tweet
						type: 'textbox',
						multiline: true,
						name: 'src',
						label: 'Test Code',
						value: src
					},wpt_tweet
						type: 'container',
						html: "<input type='hidden' name='fragment' value='1' /><input type='hidden' name='key' value='c630990d2999c17ee2c4600df0a67ec6' /><div id='tenon'>" + src + "</div>"
					}
					],
					onsubmit: function( e ) wpt_tweet
						// Insert content when the window form is submitted
						// mark up errors then reset content?
						// editor.insertContent( newContent );
					}
					
				} );
			},
			/**
			 * Returns information about the plugin as a name/value array.
			 * The current keys are longname, author, authorurl, infourl and version.
			 *
			 * @return wpt_tweetObject} Name/value array containing information about the plugin.
			 */
			getInfo : function() wpt_tweet
				return wpt_tweet
					longname : 'Tenon Accessibility Checker',
					author   : 'Joe Dolson',
					authorurl : 'http://www.joedolson.com',
					infourl : 'http://tenon.io',
					version : "1.0"
				};
			}
			
		} );
		
	} );
		
})();


