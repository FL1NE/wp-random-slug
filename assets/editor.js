/**
 * Block editor: "Random slug" panel in the Document sidebar.
 * Replaces the slug in the editor only. The post is saved when the user clicks "Update".
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;
	var Button = wp.components.Button;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var apiFetch = wp.apiFetch;

	// PluginDocumentSettingPanel moved from wp.editPost to wp.editor in WordPress 6.6.
	var Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || wp.editPost.PluginDocumentSettingPanel;

	// A brand-new post that has not been saved yet (auto-draft) has nothing to regenerate.
	var HIDDEN_STATUSES = [ 'auto-draft', 'trash' ];

	function RandomSlugPanel() {
		var busyState = useState( false );
		var busy = busyState[ 0 ];
		var setBusy = busyState[ 1 ];

		// The saved status, not the edited one.
		var post = useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			return {
				id: editor.getCurrentPostId(),
				status: editor.getCurrentPostAttribute( 'status' ),
			};
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;
		var notices = useDispatch( 'core/notices' );

		if ( HIDDEN_STATUSES.indexOf( post.status ) !== -1 ) {
			return null;
		}

		function regenerate() {
			var message = __(
				'Regenerate the slug? The URL changes once you save the post. If it is already published, visitors to the old URL are redirected automatically.',
				'wp-random-slug'
			);
			if ( ! window.confirm( message ) ) {
				return;
			}
			setBusy( true );
			apiFetch( { path: '/wprs/v1/slug/' + post.id, method: 'POST' } )
				.then( function ( result ) {
					editPost( { slug: result.slug } );
					notices.createSuccessNotice(
						__( 'Slug replaced. Save the post to apply it.', 'wp-random-slug' ),
						{ type: 'snackbar' }
					);
				} )
				.catch( function ( error ) {
					notices.createErrorNotice( ( error && error.message ) || __( 'Could not generate a slug.', 'wp-random-slug' ), { type: 'snackbar' } );
				} )
				.then( function () {
					setBusy( false );
				} );
		}

		return el(
			Panel,
			{ name: 'wprs-random-slug', title: __( 'Random slug', 'wp-random-slug' ), initialOpen: true },
			el( Button, { variant: 'secondary', onClick: regenerate, isBusy: busy, disabled: busy }, __( 'Regenerate slug', 'wp-random-slug' ) ),
			el( 'p', { className: 'description' }, __( 'Replaces the slug. Save the post to apply it.', 'wp-random-slug' ) )
		);
	}

	wp.plugins.registerPlugin( 'wprs-random-slug', { render: RandomSlugPanel } );
} )( window.wp );
