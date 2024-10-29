(function ($) {

	/* copied from badgeos-shortcode-embed.js */
	function badgeos_get_selected_shortcode() {
		return $( '#select_shortcode' ).val();
	}

	function badgeos_get_attributes( shortcode ) {
		var attrs = [];
		var inputs = badgeos_get_shortcode_inputs( shortcode );
		$.each( inputs, function( index, el ) {
			if ( '' !== el.value && undefined !== el.value ) {
				attrs.push( el.name + '="' + el.value + '"' );
			}
		});
		return attrs;
	}

	function badgeos_get_shortcode_inputs( shortcode ) {
		return $( '.text, .select', '#' + shortcode + '_wrapper' );
	}

	function badgeos_construct_shortcode( shortcode, attributes ) {
		var output = '[';
		output += shortcode;

		if ( attributes ) {
			for( i = 0; i < attributes.length; i++ ) {
				output += ' ' + attributes[i];
			}

			$.trim( output );
		}
		output += ']';

		return output;
	}

	$( '#badgeos_insert' ).on( 'click', function( e ) {
		e.preventDefault();
		var shortcode = badgeos_get_selected_shortcode();

		if (shortcode != 'award_on_click')
			return;

		e.stopImmediatePropagation();
		var attributes = badgeos_get_attributes( shortcode );
		var constructed = badgeos_construct_shortcode( shortcode, attributes );
		var editor = $('#' + wpActiveEditor);

		editor.surroundSelectedText(constructed, "[/" + shortcode + "]");
		tb_remove();
	});

	$(document).ready( function() {
		$( '#award_on_click_id' ).select2( {
			ajax: {
				url: ajaxurl,
				type: 'POST',
				data: function( term ) {
					term = (term._type) ?  key  = (term.term) ? term.term : '' : term;
					return {
						q: term,
						action: 'get-achievements-select2',
					};
				},
				processResults: function (results) {
					var res = results.data;
					var terms=[];
					if ( res ) {
						$.each( res, function( id, text ) {
							terms.push( { id: text.ID, text: text.post_title } );
						});
					}
					return {
						results: terms
					};
				}
			},
			id: function( item ) {
				return item.ID;
			},
			formatResult: function ( item ) {
				return item.post_title;
			},
			formatSelection: function ( item ) {
				return item.post_title;
			},
			placeholder: badgeos_shortcode_embed_messages.id_placeholder,
			allowClear: true,
			multiple: false
		} );
	});

}(jQuery));
