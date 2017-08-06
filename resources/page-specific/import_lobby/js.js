var lastdragtime = 0;
jQuery( function() {
	tc_init_unmapped_file_upload();
	jQuery( '[data-import-wizard]' ).on( 'click', tc_handle_import_wizard_request );
	jQuery( 'html' ).on( 'dragover', tc_handle_on_dragover );
	tc_handle_no_more_dragover();
	setInterval( tc_handle_no_more_dragover, 100 );
	tc_get_all_files();
	jQuery( '.delete-selected-files' ).on( 'click', tc_handle_delete_multiple_files_from_upload_form );
	jQuery( '.start-all-pending-jobs' ).on( 'click', tc_handle_start_all_pending_jobs );
	jQuery( '.cancel-all-pending-jobs' ).on( 'click', tc_handle_cancel_all_pending_jobs );
	jQuery( '#check-all-delete-boxes' ).on( 'click', tc_handle_select_all_unmapped_files );
});

function tc_handle_select_all_unmapped_files( e ) {
	var obj = jQuery( this );
	if ( obj.is(':checked' ) ) {
		jQuery( '[name="files[]"]' ).prop( 'checked', true );
	}
	else {
		jQuery( '[name="files[]"]' ).prop( 'checked', false );
	}
}

function tc_handle_import_wizard_request( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	var wizard = obj.attr( 'data-import-wizard' );
	tc_notify_error( 'This feature is not yet implemented' );
	return;
}

function tc_handle_on_dragover( e ) {
	lastdragtime = tc_get_current_js_timestamp();
	jQuery( 'body' ).addClass( 'dragging' );
	jQuery( '#d-a-d-text' ).text( 'Drop Here!' );
	jQuery( '#d-a-d-text' ).addClass( 'beating' );
}

function tc_handle_no_more_dragover() {
	if ( tc_get_current_js_timestamp() - 101 > lastdragtime ) {
		if ( jQuery( 'body' ).hasClass( 'dragging' ) ) {
			jQuery( 'body' ).removeClass( 'dragging' );
			jQuery( '#d-a-d-text' ).removeClass( 'beating' );
			jQuery( window ).trigger( 'dragstopped' );
			jQuery( '#d-a-d-text' ).html( 'Drag and Drop <kbd>.csv</kbd> files in the window' );
		}
	}
}

function tc_get_current_js_timestamp() {
	return Date.now();
}

function tc_init_unmapped_file_upload() {
	if ( jQuery( '#files-from-filesystem' ).length > 0 ) {
		tc_init_uploader(
			'files-from-filesystem',
			function( id, filename, filesize, stopfunction, afterstop ) {
				tc_notify_alert( sprintf( 'Starting to Upload file "%s"', filename ) );
				jQuery( '#files-from-filesystem ul.file-list' ).append( sprintf( '<li id="%s" data-file-name="%s"><div class="progress"><div class="progress-bar progress-bar-primary " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0;"><span class="percent"></span></div></div><div class="file-name"><a href="javascript:void(0);" class="tc-stop-upload text-danger"><span class="fa fa-ban"></span></a>%s</div></li>', id, filename, filename, filename ) );
				tc_scroll_to_bottom_of_selector( '#files-from-filesystem ul.file-list' );
				var li = jQuery( sprintf( '#%s', id ) );
				li.find( '.tc-stop-upload' ).on( 'click', stopfunction );
			},
			function( id, filename, filesize, percent ) {
				if ( 100 == percent ) {
					var li = jQuery( sprintf( '#%s', id ) );
					li.find( '.progress>.progress-bar' ).attr( 'aria-valuenow', percent );
					li.find( '.progress>.progress-bar' ).css({
						width: percent + '%',
					});
					li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-primary' );
					li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-success' );
					li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-warning' );
					li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-danger' );
					li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-info' );
					li.find( '.progress>.progress-bar' ).addClass( 'progress-bar-info' );
					li.find( '.progress>.progress-bar>span.percent' ).html( 'Processing' );
				}
				else {
					var li = jQuery( sprintf( '#%s', id ) );
					li.find( '.progress>.progress-bar' ).attr( 'aria-valuenow', percent );
					li.find( '.progress>.progress-bar' ).css({
						width: percent + '%',
					});
					li.find( '.progress>.progress-bar>span.percent' ).html( percent + '%' );
				}
			},
			function( id, filename, filesize, msg ) {
				var percent = 100;
				var li = jQuery( sprintf( '#%s', id ) );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-primary' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-success' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-warning' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-danger' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-info' );
				li.find( '.progress>.progress-bar' ).addClass( 'progress-bar-danger' );
				li.find( '.progress>.progress-bar' ).attr( 'aria-valuenow', percent );
				li.find( '.progress>.progress-bar' ).css({
					width: percent + '%',
				});
				li.find( '.progress>.progress-bar>span.percent' ).html( msg );
				setTimeout( function() {
					li.slideUp( 500, function() {
						li.remove();
					});
				}, 3000 );
				tc_notify_alert( sprintf( 'Failed to Upload file "%s"', filename ) );
			},
			function( id, filename, filesize, msg ) {
				var li = jQuery( sprintf( '#%s', id ) );
				var percent = 100;
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-primary' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-success' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-warning' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-danger' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-info' );
				li.find( '.progress>.progress-bar' ).addClass( 'progress-bar-success' );
				li.find( '.progress>.progress-bar' ).attr( 'aria-valuenow', percent );
				li.find( '.progress>.progress-bar' ).css({
					width: percent + '%',
				});
				li.find( '.progress>.progress-bar>span.percent' ).html( msg );
				tc_notify_alert( sprintf( 'Finished uploading file "%s"', filename ) );
				setTimeout( function() {
					li.html( sprintf( '<a href="/leads/import/%s"><label class="mass-upload-file-action-check"><input type="checkbox" name="files[]" value="%s" /></label>%s</a>', msg, msg, msg ) );
				}, 3000 );
			},
			function( id ) {
				var percent = 100;
				var li = jQuery( sprintf( '#%s', id ) );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-primary' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-success' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-warning' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-danger' );
				li.find( '.progress>.progress-bar' ).removeClass( 'progress-bar-info' );
				li.find( '.progress>.progress-bar' ).addClass( 'progress-bar-danger' );
				li.find( '.progress>.progress-bar' ).attr( 'aria-valuenow', percent );
				li.find( '.progress>.progress-bar' ).css({
					width: percent + '%',
				});
				li.find( '.progress>.progress-bar>span.percent' ).html( 'Cancelled' );
				setTimeout( function() {
					li.slideUp( 500, function() {
						li.remove();
					});
				}, 500 );
			}
		);
	}
}

function tc_scroll_to_bottom_of_selector( selector ) {
	var obj = jQuery( selector );
	obj.scrollTop( parseInt( obj.prop( 'scrollHeight' ) ) );
}

function tc_get_all_files() {
	tc_ajax(
		'get_all_files_aync',
		{},
		function( data ) {
			if ( 'undefined' !== typeof( data.mf ) ) {
				var filesToMapHtml = '';
				for (var i = 0; i < data.mf.length; i++) {
					var file = data.mf[i];
					filesToMapHtml += sprintf( '<li><a href="/leads/import/%s"><label class="mass-upload-file-action-check"><input type="checkbox" name="files[]" value="%s" /></label>%s</a></li>', file, file, file );
				}
				jQuery( '#files-from-filesystem ul.file-list' ).html( filesToMapHtml );
			}
		},
		function( data ) {
			tc_notify_error( data );
		},
		function() {
			jQuery( '#files-from-filesystem ul.file-list' ).html( '<li class="loader"><main><div class="dank-ass-loader"><div class="row"><div class="arrow up outer outer-18"></div><div class="arrow down outer outer-17"></div><div class="arrow up outer outer-16"></div><div class="arrow down outer outer-15"></div><div class="arrow up outer outer-14"></div></div><div class="row"><div class="arrow up outer outer-1"></div><div class="arrow down outer outer-2"></div><div class="arrow up inner inner-6"></div><div class="arrow down inner inner-5"></div><div class="arrow up inner inner-4"></div><div class="arrow down outer outer-13"></div><div class="arrow up outer outer-12"></div></div><div class="row"><div class="arrow down outer outer-3"></div><div class="arrow up outer outer-4"></div><div class="arrow down inner inner-1"></div><div class="arrow up inner inner-2"></div><div class="arrow down inner inner-3"></div><div class="arrow up outer outer-11"></div><div class="arrow down outer outer-10"></div></div><div class="row"><div class="arrow down outer outer-5"></div><div class="arrow up outer outer-6"></div><div class="arrow down outer outer-7"></div><div class="arrow up outer outer-8"></div><div class="arrow down outer outer-9"></div></div></div></main></li>' );
			jQuery( '#import-jobs-table tbody' ).html( '<tr><td colspan="9" class="loader"><main><div class="dank-ass-loader"><div class="row"><div class="arrow up outer outer-18"></div><div class="arrow down outer outer-17"></div><div class="arrow up outer outer-16"></div><div class="arrow down outer outer-15"></div><div class="arrow up outer outer-14"></div></div><div class="row"><div class="arrow up outer outer-1"></div><div class="arrow down outer outer-2"></div><div class="arrow up inner inner-6"></div><div class="arrow down inner inner-5"></div><div class="arrow up inner inner-4"></div><div class="arrow down outer outer-13"></div><div class="arrow up outer outer-12"></div></div><div class="row"><div class="arrow down outer outer-3"></div><div class="arrow up outer outer-4"></div><div class="arrow down inner inner-1"></div><div class="arrow up inner inner-2"></div><div class="arrow down inner inner-3"></div><div class="arrow up outer outer-11"></div><div class="arrow down outer outer-10"></div></div><div class="row"><div class="arrow down outer outer-5"></div><div class="arrow up outer outer-6"></div><div class="arrow down outer outer-7"></div><div class="arrow up outer outer-8"></div><div class="arrow down outer outer-9"></div></div></div></main></td></tr>' );
		}
	);
}

function tc_handle_start_all_pending_jobs() {
	jQuery( '[data-action="import"]' ).each( function() { jQuery( this ).click() } );
	tc_notify_success( 'Started all available import jobs' );
}

function tc_handle_cancel_all_pending_jobs() {
	jQuery( '[data-action="cancel"]' ).each( function() { jQuery( this ).click() } );
	tc_notify_success( 'Cancelled all available import jobs' );
}

function tc_handle_delete_multiple_files_from_upload_form() {
	var form = jQuery( this ).closest( 'form' );
	tc_ajax(
		'tc_multiple_file_delete_from_import_lobby',
		form.serialize(),
		function( data ) {
			form.find( 'input[type="checkbox"]:checked' ).each( function() {
				var obj = jQuery( this );
				obj.prop( 'disabled', false );
				obj.prop( 'readonly', false );
				var a = jQuery( this ).closest( 'a' );
				a.css({
					backgroundColor: null,
					color: null,
				});
			});
			for ( var file in data ) {
				var href = sprintf( '/leads/import/%s', file );
				var item = jQuery( sprintf( 'a[href="%s"]', href ) );
				if ( item.length > 0 && false !== data[file] ) {
					var li = item.closest( 'li' );
					li.remove();
				}
			}
			jQuery( '[name="files[]"]' ).prop( 'checked', false );
		},
		function( data ) {
			form.find( 'input[type="checkbox"]:checked' ).each( function() {
				var obj = jQuery( this );
				obj.prop( 'disabled', false );
				obj.prop( 'readonly', false );
				var a = jQuery( this ).closest( 'a' );
				a.css({
					backgroundColor: null,
					color: null,
				});
			});
			tc_notify_error( data );
		},
		function() {
			form.find( 'input[type="checkbox"]:checked' ).each( function() {
				var obj = jQuery( this );
				obj.prop( 'disabled', true );
				obj.prop( 'readonly', true );
				var a = jQuery( this ).closest( 'a' );
				a.css({
					backgroundColor: 'rgba( 255,255,255,0.5 )',
					color: 'rgba( 0,0,0,0.5 )',
				});
			});
		}
	);
}