/**
 * Classic editor: "Regenerate slug" button in the Random slug meta box.
 * Replaces the slug field only. The post is saved when the user clicks "Update".
 */
( function ( $, wp ) {
	var __ = wp.i18n.__;

	$( function () {
		var $button = $( '#wprs-regenerate' );
		if ( ! $button.length ) {
			return;
		}

		$button.on( 'click', function () {
			var message = __(
				'Regenerate the slug? The URL changes once you save the post. If it is already published, visitors to the old URL are redirected automatically.',
				'wp-random-slug'
			);
			if ( ! window.confirm( message ) ) {
				return;
			}
			$button.prop( 'disabled', true );
			wp.apiFetch( { path: '/wprs/v1/slug/' + $( '#post_ID' ).val(), method: 'POST' } )
				.then( function ( result ) {
					// The slug is submitted through the post_name field (hidden input, or the Slug meta box).
					$( 'input[name="post_name"]' ).val( result.slug );
					// Update the permalink shown under the title as well.
					$( '#editable-post-name, #editable-post-name-full' ).text( result.slug );
					$( '#sample-permalink a' ).each( function () {
						var href = $( this ).attr( 'href' );
						if ( href ) {
							$( this ).attr( 'href', href.replace( /[^/?=]+(\/?)$/, result.slug + '$1' ) );
						}
					} );
					$( '#wprs-regenerate-notice' ).remove();
					$button.closest( '.inside' ).append(
						$( '<p id="wprs-regenerate-notice" class="description">' ).text( __( 'Slug replaced. Save the post to apply it.', 'wp-random-slug' ) )
					);
				} )
				.catch( function ( error ) {
					window.alert( ( error && error.message ) || __( 'Could not generate a slug.', 'wp-random-slug' ) );
				} )
				.then( function () {
					$button.prop( 'disabled', false );
				} );
		} );
	} );
} )( window.jQuery, window.wp );
