jQuery( function() {
	jQuery( '[data-delimiter]' ).on( 'click', tc_handle_special_tab_delimiters );
	jQuery( '[data-file-action]' ).on( 'click', tc_handle_file_map_action_request );
	jQuery( '.copy-from-other-file' ).on( 'click', tc_handle_copy_mapping_from_another_file_request );
});

function tc_handle_copy_file_mapping_from_modal( e, modal ) {
	var newfile = jQuery( '#list-of-files' ).val();
	var currile = jQuery( 'input[name="file"]' ).val();
	var modalbody = modal.find( '.modal-body' );
	tc_ajax(
		'tc_copy_file_mapping',
		{
			old: currile,
			new: newfile,
		},
		function( data ) {
			modal.find( '.progress>div' ).removeClass( 'progress-bar-warning' );
			modal.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			modal.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			modal.find( '.progress>div' ).addClass( 'progress-bar-success' );
			modal.find( '.progress>div>.percent' ).html( 'Copied Mapping Successfully' );
			modal.find( 'a' ).each( function() {
				var o = jQuery( this ).
				o.off( 'click' );
				o.addClass( 'disabled' );
				o.prop( 'disabled', true );
			})
			tc_notify_success( data );
		},
		function( data ) {
			modal.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			modal.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			modal.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			modal.find( '.progress>div' ).addClass( 'progress-bar-danger' );
			modal.find( '.progress>div>.percent' ).html( 'Failed to Copy File Mapping' );
			tc_notify_error( data );
			modal.modal( 'hide' );
			modal.remove();
			setTimeout( tc_handle_copy_mapping_from_another_file_request, 500 );
		},
		function() {
			modalbody.html( '<div class="progress"><div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
		},
		function( percent ) {
			if ( percent >= 1 ) {
				percent = 1;
			}
			modal.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
			modal.find( '.progress>div' ).css({
				width: ( percent * 100 ) + '%',
			});
			modal.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		}
	);
}

function tc_get_all_files( modal ) {
	tc_ajax(
		'get_all_files_aync',
		{},
		function( data ) {
			jQuery( '#list-of-files' ).prop( 'disabled', false );
			var filesHTML = '';
			for ( var file in data.rmf ) {
				filesHTML += sprintf( '<option value="%s">%s</option>', file, file );
			}
			for ( var file in data.umf ) {
				filesHTML += sprintf( '<option value="%s">%s</option>', file, file );
			}
			jQuery( '#list-of-files' ).html( filesHTML );
			jQuery( '#list-of-files' ).chosen({width:'100%'});
			jQuery( '#list-of-files' ).prop( 'disabled', false );
			jQuery( '#list-of-files' ).trigger( 'chosen:updated' );
			jQuery( '#copy-trigger' ).on( 'click', function( e ) {
				tc_handle_copy_file_mapping_from_modal( e, modal )
			} );
		},
		function( data ) {
			tc_notify_error( data );
		},
		function() {
			jQuery( '#list-of-files' ).prop( 'disabled', true );
		}
	);
}

function tc_handle_copy_mapping_from_another_file_request() {
	var md = '<div class="modal fade" tabindex="-1" role="dialog" id="sysmodal"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title">Choose a file</h4></div><div class="modal-body"><div class="form-group"><select id="list-of-files" class="form-control"></select></div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button><button type="button" class="btn btn-primary" id="copy-trigger">Copy Mapping</button></div></div></div></div>';
	jQuery( 'body' ).append( md );
	var modal = jQuery( '#sysmodal' );
	modal.modal({
		keyboard: false,
		show: true,
		backdrop: false,
	});
	tc_get_all_files( modal );
}

function tc_generate_file_preview( data ) {
	_tc_existing_columns = data.existingColumns
	for ( var i in data.columns ) {
		add_column(
			data.columns[i].index,
			data.columns[i].fieldmap,
			data.columns[i].key,
			data.columns[i].preview,
			data.columns[i].default,
			true
		);
	}
	jQuery( 'input[name="map_delimiter"]' ).val( jQuery( 'input[name="delimiter"]' ).val() );
	jQuery( 'input[name="map_encapsulation"]' ).val( jQuery( 'input[name="encapsulation"]' ).val() );
	jQuery( 'input[name="map_headerrow"]' ).val( jQuery( 'input[name="headerrow"]' ).val() );
	tc_notify_success( 'Loaded File Contents Successfully' );
}

function tc_handle_special_tab_delimiters( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	jQuery( '#delimiter' ).val( obj.attr( 'data-delimiter' ) );
}

function tc_handle_file_map_action_request( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	var form = jQuery( 'form[action="other_file_actions"]' );
	form.find( 'input[name="action"]' ).val( obj.attr( 'data-file-action' ) );
	form.submit();
}

function tc_parse_file_actions_return( data ) {
	switch ( data ) {
		case 'Reset':
			tc_notify_success( 'Reset File Mapping to Default' );
			break;

		case 'Deleted':
			tc_notify_success( 'Deleted and Removed File. Please wait while you are redirected.' );
			break;

		case 'Approved':
			tc_notify_success( 'File Map Approved. Please wait while you are redirected.' );
			break;

		default:
			tc_notify_error( $data );
			break;
	}
}