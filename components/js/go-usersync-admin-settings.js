(function($) {
	'use strict';	

	// update the debug option when user clicks on the debug checkbox
	$('#go-syncuser-debug').click( function( e ) {
		var debug = ( 'checked' === $('#go-syncuser-debug').attr("checked") );

		$.ajax({
			url: go_usersync_ajax.admin_ajax_url +
				'?action=go_syncuser_set_debug' +
				'&debug=' + debug,
			success: function(result) {
				if ( 'ok' !== result ) {
					// if we didn't get a confirmation from the server,
					// alert the error and revert the debug setting
					alert( 'unable to save the option: ' + result );
					$('#go-syncuser-debug').attr('checked', ! debug );
				}
			},
			error: function(result, status, error) {
				// if we received an error from the server, alert the
				// error and revert the debug setting
				alert(error);
				$('#go-syncuser-debug').attr('checked', ! debug );
			},
			async: false
		});

	});
})(jQuery);
