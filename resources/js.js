var _tc_existing_columns = [];
var _tc_shown_export_jobs = [];
var _tc_shown_import_files = [];
var nogo = true;
var s;
var graphs = {};
var _alert;
var starttime;
var endtime;
var currentTabIndex = 1;
var firstloaded = false;
var clips = {};
jQuery( function() {
	jQuery( 'form[method="AJAX"]' ).on( 'submit', tc_handle_ajax_form_submit );
	jQuery( 'a.logout-click-action' ).on( 'click', tc_handle_logout_request );
	jQuery( 'a.reset-cache' ).on( 'click', tc_handle_reset_cache_request );
	jQuery( '.no-action' ).on( 'click', function( e ) { e.preventDefault() });
	jQuery( 'form[method="AJAX"] input' ).prop( 'disabled', false );
	jQuery( 'form[method="AJAX"] select' ).prop( 'disabled', false );
	jQuery( 'form[method="AJAX"] textarea' ).prop( 'disabled', false );
	jQuery( '#lead-list-panel input' ).prop( 'disabled', true );
	jQuery( '#lead-list-panel select' ).prop( 'disabled', true );
	jQuery( '#lead-list-panel textarea' ).prop( 'disabled', true );
	jQuery( '#tc-add-new-column' ).on( 'click', tc_handle_new_column_request );
	jQuery( 'form[action="generate_file_preview"]' ).submit();
	jQuery( '[data-action="import-all"]' ).on( 'click', function() {
        jQuery( '[data-action="import"]' ).each( function() { jQuery( this ).click() } );
	});
	jQuery( '#single-lead-import' ).each( tc_init_single_lead_import );
	jQuery( 'form[action="create_export_job"]' ).each( tc_init_export_job_form );
	jQuery( 'form[action="create_saved_query"]' ).each( tc_init_export_job_form );
	jQuery( 'form[action="filter_lead_list"]' ).each( tc_init_export_job_form );
	jQuery( 'form[action="filter_lead_list"]' ).each( function() {
		jQuery( this ).on( 'submit', function() {
			jQuery( '#lead-list-panel input' ).prop( 'disabled', true );
			jQuery( '#lead-list-panel select' ).prop( 'disabled', true );
			jQuery( '#lead-list-panel textarea' ).prop( 'disabled', true );
			jQuery( '#lead-list' ).html( '<tr><td colspan="8"><div class="alert alert-info text-center">Loading. Please Wait.</div></td></tr>' );
		});
	} );
	jQuery( '#export-jobs-table tr' ).each( tc_init_export_job_manager );
	tc_init_streamer();
	tc_init_homepage();
	jQuery( '#sysinfoswitch' ).on( 'click', tc_handle_sysinfoswitch );
	jQuery( '#side-menu-toggle' ).on( 'click', tc_handle_side_menu_toggle );
	jQuery( '#loadsavedquery' ).chosen({ width: '100%', search_contains: false, display_disabled_options: false } );
	jQuery( '#save-default-map-query' ).on( 'click', tc_handle_save_default_map_query );
    jQuery( '#loadsavedquery-dash' ).chosen({ width: '100%', search_contains: false, display_disabled_options: false } ).on( 'change', tc_handle_homepage_graph_change );
    setTimeout( function() {
    	var val = parseInt( getCookie( 'tcldbdashdefaultmap' ) );
		jQuery( '#loadsavedquery-dash' ).val( val );
		jQuery( '#loadsavedquery-dash' ).change();
		jQuery( '#loadsavedquery-dash' ).trigger( 'chosen:updated' );
    }, 1000 );
    //setTimeout( function() {
    //}, 1000 );
	jQuery( '[data-page-jumper]' ).chosen({ width: '100%' } );
	jQuery( '#load-saved-query' ).on( 'click', tc_handle_loading_saved_query );
	jQuery( '.with-tool-tip' ).tooltip();
	jQuery( '#delete-saved-query' ).on( 'click', tc_handle_deleting_saved_query );
	jQuery( '.btn-expand-panel' ).on( 'click', tc_handle_expanding_panel );
	jQuery( '[data-form-target]' ).on( 'click', tc_handle_external_form_submit );
	jQuery( '[data-update-target]' ).each( tc_init_data_update_target );
	try { PNotify.prototype.options.styling = "fontawesome"; } catch ( err ) { console.error( err ) }
	tc_consume_feedback();
	tc_consume_errors();
	if ( 'login' !== tcd.template ) {
		setInterval( tc_do_heardbeat, 30000 );
	}
	jQuery( '[data-tutorial]' ).on( 'click', tc_handle_tutorial_request );
	jQuery( '.check-memcache' ).on( 'click', tc_handle_memcached_check );
	jQuery( 'select.searchable' ).each( function() {
		var chsn = jQuery( this );
		var row = chsn.parent();
		if ( 1 == chsn.attr( 'data-can-add' ) ) {
			chsn.chosen({ width: '100%', no_results_text: 'Press Enter to add new entry:' });
			var chosen = chsn.data('chosen');
			row.find('li.search-field input').on('keyup', function(e) {
				if (e.which == 13 && chosen.dropdown.find('li.no-results').length > 0) {
					var option = jQuery("<option>").val(this.value).text(this.value);
					chsn.prepend(option);
					chsn.find(option).prop('selected', true);
					chsn.trigger("chosen:updated");
			    }
			});
		}
		else {
			chsn.chosen({ width: '100%', search_contains: false });
		}
	});
	jQuery( '#close-terminal' ).on( 'click', tc_handle_close_terminal );
	jQuery( '.open-import-log' ).on( 'click', tc_handle_open_terminal );
	set_current_time();
	setInterval( set_current_time, 1000 );
	jQuery( window ).on( 'resize', tc_handle_resize_event );
	if ( isRunningStandalone() ) {
		tc_make_all_links_use_js_redirect();
	}
	document.addEventListener('touchmove', tc_handle_touch_move, false);
	jQuery( '[data-clipboard-text]' ).each( tc_init_copy_clipboard_text );
	setWindowResizeEvents();
});

function tc_window_resized( e ) {
	for( var chart in graphs ) {
		if ( 'object' == typeof( graphs[ chart ] ) ) {
			graphs[ chart ].redraw( true );
		}
	}
}

function setWindowResizeEvents() {
	var supportsOrientationChange = 'onorientationchange' in window,
    orientationEvent = supportsOrientationChange ? 'orientationchange' : 'resize';
    if ( 'resize' !== orientationEvent ) {
    	window.addEventListener( orientationEvent, tc_window_resized );
    }
}

function tc_handle_touch_move( e ) {
	if ( ! jQuery( '#content' ).is( ':hover' ) && ! jQuery( '#import-jobs-widget' ).is( ':hover' ) ) {
		e.preventDefault();
	}
}

function tc_make_all_links_use_js_redirect() {
	jQuery( 'a[href]' ).each( function() {
		var link = jQuery( this );
		var url = link.attr( 'href' );
		if ( url.length > 0 && '#' !== url && 'javascript:void(0);' !== url && 'javascript:void(0)' !== url ) {
			link.on( 'click', function( e ) {
				if ( 'object' == typeof( e ) ) {
					e.preventDefault();
				}
				if ( ! link.hasClass( 'disabled' ) ) {
					window.location.href = url;
				}
			} );
		}
	});
}

function tc_handle_resize_event( e ) {
	jQuery( '.chosen-container' ).each( function() {
		var obj = jQuery( this ).prev( 'select' );
		obj.trigger( 'chosen:updated' );
	})
}

function set_current_time() {
	var ts = sprintf( '%s UTC', moment().utc().format( 'HH:mm:ss' ) );
	jQuery( '.current-time' ).text( ts );
}

function tc_handle_homepage_graph_change( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	tc_handle_set_value_from_saved_query( e );
}

function tc_handle_save_default_map_query( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	setCookie( 'tcldbdashdefaultmap', jQuery( '#loadsavedquery-dash' ).val() );
	tc_notify_success( 'Set Default Map' );
}

function tc_handle_open_terminal( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	jQuery( '#terminal-wrapper' ).css({opacity:0,display:'block'});
	jQuery( '#terminal-wrapper' ).animate({opacity:1},300);
}

function tc_handle_close_terminal( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	jQuery( '#terminal-wrapper' ).fadeOut( 300, function() {
		jQuery( this ).css({display:'none',});
	});
}

function tc_handle_query_debug( ps ) {
	jQuery( '#saved-query-debug-feedback' ).html( ps );
}

function tc_get_next_tab_index() {
	var ret = currentTabIndex;
	currentTabIndex ++;
	return ret;
}

function tc_handle_memcached_check( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	tc_ajax(
		'get_memcached_status',
		{},
		function( data ) {
			tc_notify_success( data );
		},
		function( data ) {
			tc_notify_error( data );
		}
	);
}

function tc_handle_tutorial_request( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	tc_ajax(
		'get_tutorial_data',
		{ tutorial: obj.attr( 'data-tutorial' ) },
		function( data ) {
			tc_notify_error( 'Nothing Here Yet' );
		},
		function( data ) {
			tc_notify_error( data );
		},
		function() {
			tc_notify_success( 'Loading Tutorial. Please wait.' );
		}
	);
}

function tc_do_heardbeat() {
	tc_ajax(
		'heartbeat',
		{},
		function( data ) {
			//tc_notify_success( data );
		},
		function( data ) {
			tc_notify_error( data );
		}
	);
}

function tc_consume_errors() {
	var oldfunction = console.error;
	console.error = function( msg ) {
		tc_notify_error( msg );
		oldfunction( msg );
	}
}

function tc_consume_feedback() {
	if ( _alert ) return;
    _alert = window.alert;
    window.alert = function( message ) {
    	tc_notify_alert( message, 'Alert' );
    }
}

function tc_add_leads_to_list( data ) {
	jQuery( '#lead-list-panel input' ).prop( 'disabled', false );
	jQuery( '#lead-list-panel select' ).prop( 'disabled', false );
	jQuery( '#lead-list-panel textarea' ).prop( 'disabled', false );
	jQuery( '#lead-list-panel select' ).trigger( 'chosen:updated' );
	for ( var key in data ) {
		if ( 'page' == key ) {
			data[key] ++;
		}
		var jo = '';
		var pid = ( data.page >= 500 ) ? data.page - 500 : 0;
		var max = pid + 1000 - 1;
		while( pid < data.totalpages && pid <= max ) {
			jo += sprintf( '<option value="%d" %s>%d</option>' + "\r\n", pid, ( ( pid + 1 ) == data.page ? 'selected' : '' ), ( pid + 1 ) );
			pid ++;
		}
		jQuery( '[data-page-jumper]' ).off( 'targetupdated' );
		jQuery( '[data-page-jumper]' ).html( jo );
		jQuery( '[data-page-jumper]' ).trigger( 'chosen:updated' );
		jQuery( '[data-page-jumper]' ).on( 'targetupdated', function() {
			jQuery( 'form' ).submit();
		} );

		jQuery( sprintf( '[data-info-key="%s"]', key ) ).html( data[key] );
		if ( 'leads' == key ) {
			var html = '';
			for (var i = 0; i < data[key].length; i++) {
				var l = data[key][i];
				if ( i > 0 && i % 5 == 0 ) {
					html += '<tr><th>&nbsp;</th><th><strong>ID</strong></th><th><strong>Name</strong></th><th><strong>Email Address</strong></th><th><strong>Phone Number</strong></th><th><strong>Country</strong></th><th><strong>Source</strong></th><th><strong>Import Time</strong></th></tr>' + "\r\n";
				}
				html += sprintf(
					'<tr><td><a href="/leads/view/%d" class="btn btn-warning btn-block btn-xs"><span class="glyphicon fa-no-margin glyphicon-open-file"></span></a></td><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>' + "\r\n",
					( null == l.id ) ? '' : l.id,
					( null == l.id ) ? '' : l.id,
					( null == l.name ) ? '' : l.name,
					( null == l.email ) ? '' : l.email,
					( null == l.phone ) ? '' : l.phone,
					( null == l.country ) ? '' : l.country,
					( null == l.source ) ? '' : l.source,
					( null == l.importtime ) ? '' : l.importtime
				);
			}
			jQuery( '#lead-list' ).html( html );
		}
	}
}

function tc_init_data_update_target() {
	var obj = jQuery( this );
	var target = obj.attr( 'data-update-target' );
	obj.on( 'change', function() {
		jQuery( sprintf( '[name="%s"]', target ) ).val( obj.val() );
		obj.trigger( 'targetupdated' );
	});
	obj.on( 'keyup', function() {
		jQuery( sprintf( '[name="%s"]', target ) ).val( obj.val() );
		obj.trigger( 'targetupdated' );
	});
}

function tc_handle_external_form_submit( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var btn = jQuery( this );
	var form = jQuery( sprintf( 'form[action="%s"]', btn.attr( 'data-form-target' ) ) );
	form.submit();
}

function tc_handle_expanding_panel( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var btn = jQuery( this );
	var form = btn.closest( 'form' );
	if ( form.find( '.panel-body' ).is( ':visible' ) ) {
		form.find( '.panel-body' ).fadeOut( 300, function() {
			form.find( '.panel-body' ).css({ display: 'none' } );
		} );
		form.find( '.table-responsive' ).fadeOut( 300, function() {
			form.find( '.table-responsive' ).css({ display: 'none' } );
		} );
		form.find( '.table' ).fadeOut( 300, function() {
			form.find( '.table' ).css({ display: 'none' } );
		} );
		form.find( '.panel-footer' ).fadeOut( 300, function() {
			form.find( '.panel-footer' ).css({ display: 'none' } );
		} );
		btn.find( '.fa' ).removeClass( 'fa-chevron-up' );
		btn.find( '.fa' ).addClass( 'fa-chevron-down' );
		btn.find( '.text' ).html( 'Show' );
	}
	else {
		form.find( '.panel-body' ).css({ display: 'block' } );
		form.find( '.panel-body' ).animate({ opacity: 1 }, 300 );
		form.find( '.table-responsive' ).css({ display: 'block' } );
		form.find( '.table-responsive' ).animate({ opacity: 1 }, 300 );
		form.find( '.table' ).css({ display: 'table' } );
		form.find( '.table' ).animate({ opacity: 1 }, 300 );
		form.find( '.panel-footer' ).css({ display: 'block' } );
		form.find( '.panel-footer' ).animate({ opacity: 1 }, 300 );
		btn.find( '.fa' ).removeClass( 'fa-chevron-down' );
		btn.find( '.fa' ).addClass( 'fa-chevron-up' );
		btn.find( '.text' ).html( 'Hide' );
	}
}

function tc_handle_deleting_saved_query( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var btn = jQuery( this );
	var obj = btn.closest( 'form' );
	var id = btn.attr( 'data-saved-query-id' );
	tc_ajax(
		'delete_saved_query',
		{ quid: id },
		function( data, redirect ) {
			if ( true !== redirect ) {
				obj.find( 'input[type="submit"]' ).prop( 'disabled', false );
				obj.find( 'input[type="submit"]' ).prop( 'readonly', false );
				obj.find( 'button' ).prop( 'disabled', false );
				obj.find( 'button' ).prop( 'readonly', false );
				obj.find( 'a' ).prop( 'disabled', false );
				obj.find( 'a' ).prop( 'readonly', false );
				obj.find( 'select' ).trigger( 'chosen:updated' );
			}
			obj.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			obj.find( '.progress>div' ).addClass( 'progress-bar-success' );
			var callback = window[obj.attr( 'data-callback' )];
			if ( 'function' == typeof( callback ) ) {
				obj.find( '.progress>div>.percent' ).html( 'Success' );
				callback( data );
			}
			else {
				obj.find( '.progress>div>.percent' ).html( data );
			}
		},
		function( data ) {
			obj.find( 'input[type="submit"]' ).prop( 'disabled', false );
			obj.find( 'input[type="submit"]' ).prop( 'readonly', false );
			obj.find( 'button' ).prop( 'disabled', false );
			obj.find( 'button' ).prop( 'readonly', false );
			obj.find( 'a' ).prop( 'disabled', false );
			obj.find( 'a' ).prop( 'readonly', false );
			obj.find( 'select' ).trigger( 'chosen:updated' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			obj.find( '.progress>div' ).addClass( 'progress-bar-danger' );
			obj.find( '.progress>div>.percent' ).html( data );
			tc_notify_error( data, 'Error' );
			console.warn( data );
		},
		function() {
			obj.find( 'input[type="submit"]' ).prop( 'disabled', true );
			obj.find( 'input[type="submit"]' ).prop( 'readonly', true );
			obj.find( 'button' ).prop( 'disabled', true );
			obj.find( 'button' ).prop( 'readonly', true );
			obj.find( 'a' ).prop( 'disabled', true );
			obj.find( 'a' ).prop( 'readonly', true );
			obj.find( 'select' ).trigger( 'chosen:updated' );
			obj.find( '.ajax-response' ).html( '<div class="progress"><div class="progress-bar progress-bar-danger " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
		},
		function( percent ) {
			if ( percent >= 1 ) {
				percent = 1;
			}
			obj.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
			obj.find( '.progress>div' ).css({
				width: ( percent * 100 ) + '%',
			});
			obj.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		}
	);
}

function tc_handle_loading_saved_query( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var btn = jQuery( this );
	var form = btn.closest( 'form' );
	var select = jQuery( '#loadsavedquery' );
	form.find( 'tr[data-condition-id]' ).remove();
	tc_populate_saved_query_fields( select.val(), form );
}

function tc_handle_side_menu_toggle() {
	if ( jQuery( '#left-nav-secondary' ).is( ':visible' ) ) {
		jQuery( '#left-nav-secondary' ).css({ display: 'none' });
		jQuery( '#content' ).css({ left: 50 });
		jQuery( 'footer' ).css({ left: 50 });
	}
	else {
		jQuery( '#left-nav-secondary' ).css({ display: 'block' });
		jQuery( '#content' ).css({ left: 250 });
		jQuery( 'footer' ).css({ left: 250 });
	}
	tc_window_resized();
}

function tc_handle_sysinfoswitch() {
	if ( jQuery( '#sysinfowrapper' ).is( ':visible' ) ) {
		jQuery( '#sysinfowrapper' ).fadeOut( 200 );
		jQuery( '#sysinfo-action' ).html( 'View' );
	}
	else {
		jQuery( '#sysinfowrapper' ).fadeIn( 200 );
		jQuery( '#sysinfo-action' ).html( 'Hide' );
	}
}

function tc_init_homepage() {
	if ( jQuery( '#graph-cpu' ).length > 0 ) {
		graphs['cpu'] = Highcharts.chart( 'graph-cpu', {
			chart: {
				zoomType: 'x',
			},
			colors: [
				'rgba( 255,255,255,0.5 )'
			],
			title: {
				text: '',
			},
			xAxis: {
				type: 'datetime',
			},
			yAxis: {
				title: {
					text: 'CPU Utilization Percentage',
				}
			},
			legend: {
				enabled: false,
			},
			credits: {
				enabled: false,
			},
			plotOptions: {
				areaspline: {
					fillColor: {
						linearGradient: {
							x1: 0,
							y1: 0,
							x2: 0,
							y2: 1,
						},
						stops: [
							[0, 'rgba( 255,255,255,0.35 )'],
                        	[1, 'rgba( 255,255,255,0.35 )'],
						],
					},
					marker: {
						radius: 2,
					},
					lineWidth: 1,
					states: {
						hover: {
							lineWidth: 1,
						}
					},
					threshold: null,
				}
			},
			series:[{
				type: 'areaspline',
				name: 'CPU Utilization Percentage',
				data: [],
			}]
		} );
	}
	if ( jQuery( '#graph-memory' ).length > 0 ) {
		graphs['memory'] = Highcharts.chart( 'graph-memory', {
			chart: {
				zoomType: 'x',
			},
			colors: [
				'#2660a4'
			],
			title: {
				text: '',
			},
			xAxis: {
				type: 'datetime',
			},
			yAxis: {
				title: {
					text: 'Memory Utilization Percentage',
				}
			},
			legend: {
				enabled: false,
			},
			credits: {
				enabled: false,
			},
			plotOptions: {
				areaspline: {
					fillColor: {
						linearGradient: {
							x1: 0,
							y1: 0,
							x2: 0,
							y2: 1,
						},
						stops: [
							[0, 'rgba( 37, 96, 164, 0.5 )'],
                        	[1, 'rgba( 37, 96, 164, 0.5 )'],
						],
					},
					marker: {
						radius: 2,
					},
					lineWidth: 1,
					states: {
						hover: {
							lineWidth: 1,
						}
					},
					threshold: null,
				}
			},
			series:[{
				type: 'areaspline',
				name: 'Memory Utilization Percentage',
				data: [],
			}]
		} );
	}
	if ( jQuery( '#graph-threads' ).length > 0 ) {
		graphs['threads'] = Highcharts.chart( 'graph-threads', {
			chart: {
				zoomType: 'x',
			},
			colors: [
				'#2660a4'
			],
			title: {
				text: '',
			},
			xAxis: {
				type: 'datetime',
			},
			yAxis: {
				title: {
					text: 'Concurrent Threads',
				}
			},
			legend: {
				enabled: false,
			},
			credits: {
				enabled: false,
			},
			plotOptions: {
				areaspline: {
					fillColor: {
						linearGradient: {
							x1: 0,
							y1: 0,
							x2: 0,
							y2: 1,
						},
						stops: [
							[0, 'rgba( 37, 96, 164, 0.5 )'],
                        	[1, 'rgba( 37, 96, 164, 0.5 )'],
						],
					},
					marker: {
						radius: 2,
					},
					lineWidth: 1,
					states: {
						hover: {
							lineWidth: 1,
						}
					},
					threshold: null,
				}
			},
			series:[{
				type: 'areaspline',
				name: 'Concurrent Threads',
				data: [],
			}]
		} );
	}
	if ( jQuery( '#graph-leads' ).length > 0 ) {
		graphs['leads'] = Highcharts.chart( 'graph-leads', {
			chart: {
				zoomType: 'x',
				backgroundColor: 'rgba( 0,0,0,0 )',
				spacing: [0,0,0,0],
			},
			colors: [
				'rgba( 255,255,255,0.5 )'
			],
			title: {
				text: '',
			},
			xAxis: {
				gridLineWidth: 0,
				minorGridLineWidth: 0,
				type: 'datetime',
				labels: {
	                enabled: false
	            },
				title: {
					text: null,
				},
				lineColor: 'transparent',
				plotLines:[{ value: function() {} } ],
				color: 'transparent',
			},
			yAxis: {
				gridLineWidth: 0,
				minorGridLineWidth: 0,
				labels: {
	                enabled: false
	            },
				title: {
					text: null,
				},
				lineColor: 'transparent',
				plotLines:[{ value: function() {} } ],
				color: 'transparent',
			},
			legend: {
				enabled: false,
			},
			credits: {
				enabled: false,
			},
			plotOptions: {
				areaspline: {
					fillColor: {
						linearGradient: {
							x1: 0,
							y1: 0,
							x2: 0,
							y2: 1,
						},
						stops: [
							[0, 'rgba( 255,255,255,0.3 )'],
                        	[1, 'rgba( 255,255,255,0.3 )'],
						],
					},
					marker: {
						radius: 2,
					},
					lineWidth: 1,
					states: {
						hover: {
							lineWidth: 1,
						}
					},
					threshold: null,
				}
			},
			series:[{
				type: 'areaspline',
				name: 'Total Lead Records',
				data: [],
			}]
		} );
		//for (var lg = 0; lg < tcd.leadgraphs.length; lg++) {
		//	var gd = tcd.leadgraphs[lg];
		//	graphs['leads'].addSeries( gd );
		//}
	}
	if ( jQuery( '#graph-lpc' ).length > 0 ) {
		graphs['lpc'] = Highcharts.chart( 'graph-lpc', {
			chart: {
				plotBackgroundColor: null,
				plotBorderWidth: null,
				plotShadow: false,
				type: 'pie'
			},
			colors: [
				'#739f3d',
				'#2660a4',
				'#ffbb00',
				'#fa6e59',
				'#4c3f54',
			],
			title: {
				text: '',
			},
			tooltip: {},
			legend: {
				enabled: true,
			},
			credits: {
				enabled: false,
			},
			plotOptions: {
				pie: {
					allowPointSelect: true,
					cursor: 'pointer',
				}
			},
			series:[{
				colorByPoint: true,
				name: 'Leads',
				data: [],
			}]
		} );
	}
	if ( jQuery( '#graph-leads-by-country' ).length > 0 ) {
		graphs['leadsbycountry'] = Highcharts.mapChart( 'graph-leads-by-country', {
			chart: {
				backgroundColor: 'rgba( 0,0,0,0 )',
			},
			title: {
				text: '',
			},
			 colors: [
			 	'#4c3f54',
			 	'#739f3d',
			 	'#2660a4',
			 	'#ffbb00',
			 	'#fa6e59',
			 	'#4c3f54',
			 ],
			colorAxis: {
				tickPixelInterval: 1000,
				dataClasses:[
					{ color: 'rgba(255,255,255,0.3)', to: 1000 },
					{ color: 'rgba(255,255,255,0.4)', from: 1001, to: 5000 },
					{ color: 'rgba(255,255,255,0.5)', from: 5001, to: 10000 },
					{ color: 'rgba(255,255,255,0.6)', from: 10001, to: 50000 },
					{ color: 'rgba(255,255,255,0.8)', from: 50001, to: 100000 },
					{ color: 'rgba(255,255,255,1)', from: 100001 },
				],
			},
			credits: {
				enabled: false,
			},
			mapNavigation: {
				enabled: false,
			},
			plotOptions: {
				map: {
					joinBy: ['iso-a2', 'code'],
					mapData: Highcharts.maps["custom/world-highres3"],
					nullColor: 'rgba( 255,255,255,0.1 )',
					animation: true,
					dataLabels: {
						enabled: true,
						formatter: function () {
							if (this.point.properties && this.point.properties.labelrank.toString() < 5) {
								if ( this.point.value !== null && this.point.value > 100000 ) {
									return numberWithCommas( this.point.value );
								}
							}
						},
						color: '#fff',
						format: null,
						style: {
							fontWeight: 'bold',
						},
					}
				}
			},
			series:[{
				name: 'Leads',
			}],
			legend: {
				title: {
					text: 'Leads per Country',
					color: '#FFF',
					style: {
						color: '#FFF',
					}
				},
				align: 'left',
				verticalAlign: 'bottom',
				floating: 'true',
				layout: 'vertical',
				valueDecimals: 0,
				symbolRadius: 0,
				symbolHeight: 14,
				color: '#fff',
				itemStyle: {
					color: '#fff',
				}
			}
		} );
	}
	if ( jQuery( '#graph-rpm' ).length > 0 ) {
		graphs['rpm'] = Highcharts.chart( 'graph-rpm', {
			chart: {
				zoomType: 'x',
				backgroundColor: 'rgba( 0,0,0,0 )',
				events: {
					load: function() {
						for (var i = 0; i < this.series[0].points.length; i++) {
							var p = this.series[0].points[i];
							this.tooltip.refresh( p );
						}
					}
				}
			},
			colors: [
				'rgba( 255,255,255,0.65 )'
			],
			title: {
				text: '',
			},
			xAxis: {
				gridLineWidth: 0,
				minorGridLineWidth: 0,
				type: 'datetime',
				labels: {
	                enabled: false
	            },
				title: {
					text: null,
				},
				lineColor: 'transparent',
				plotLines:[{ value: function() {} } ],
				color: 'transparent',
				tickColor: 'transparent',
			},
			yAxis: {
				gridLineWidth: 0,
				minorGridLineWidth: 0,
				labels: {
	                enabled: true,
	                style: {
	                	color: 'rgba( 255,255,255,0.65 )',
	                }
	            },
				title: {
					text: null,
				},
				lineColor: 'transparent',
				plotLines:[{ value: function() {} } ],
				color: 'transparent',
				min: 0.5,
				startOnTick: false,
			},
			legend: {
				enabled: false,
			},
			credits: {
				enabled: false,
			},
			tooltip: {
				enabled: true,
				backgroundColor: 'rgba( 0,0,0,0 )',
				padding: 2,
				style: {
					color: '#FFF',
				},
				formatter: function() {
					return sprintf( '%d', this.y );
				}
			},
			plotOptions: {
				areaspline: {
					fillColor: {
						linearGradient: {
							x1: 0,
							y1: 0,
							x2: 0,
							y2: 1,
						},
						stops: [
							[0, 'rgba( 255,255,255,0.35 )'],
                        	[1, 'rgba( 255,255,255,0.35 )'],
						],
					},
					marker: {
						radius: 2,
					},
					lineWidth: 1,
					states: {
						hover: {
							lineWidth: 1,
						}
					},
					threshold: null,
				}
			},
			series:[{
				type: 'areaspline',
				name: 'Rows imported per Minute',
				data: [],
			}]
		} );
	}
	if ( jQuery( '#callCenterReadyPercentage' ).length > 0 ) {
		graphs['callCenterReadyPercentage'] = Highcharts.chart( 'callCenterReadyPercentage', {
			chart: {
				type: 'solidgauge',
				backgroundColor: 'rgba( 0,0,0,0 )',
			},
			title: {
				text: '',
			},
			pane: {
				startAngle: 0,
				endAngle: 360,
				background:[{
					outerRadius: '100%',
					innerRadius: '95%',
					backgroundColor: 'rgba(0,0,0,0.5)',
					borderWidth: 0,
				}]
			},
			yAxis: {
				min: 0,
				max: 100,
				lineWidth: 0,
				tickPositions: [],
			},
			plotOptions: {
				solidgauge: {
					dataLabels: {
						enabled: false,
					},
					linecap: 'round',
					stickyTracking: false,
					rounded: true,
				},
			},
			tooltip: {
				enabled: false,
			},
			series:[{
				name: '',
				data:[{
					color: 'rgba( 255,255,255,0.7 )',
					radius: '100%;',
					innerRadius: '95%',
					y: 0,
				}]
			}],
			credits: {
				enabled: false,
			}
		});
	}
	if ( jQuery( '#emailMarketingReadyPercentage' ).length > 0 ) {
		graphs['emailMarketingReadyPercentage'] = Highcharts.chart( 'emailMarketingReadyPercentage', {
			chart: {
				type: 'solidgauge',
				backgroundColor: 'rgba( 0,0,0,0 )',
			},
			title: {
				text: '',
			},
			pane: {
				startAngle: 0,
				endAngle: 360,
				background:[{
					outerRadius: '100%',
					innerRadius: '95%',
					backgroundColor: 'rgba(0,0,0,0.5)',
					borderWidth: 0,
				}]
			},
			yAxis: {
				min: 0,
				max: 100,
				lineWidth: 0,
				tickPositions: [],
			},
			plotOptions: {
				solidgauge: {
					dataLabels: {
						enabled: false,
					},
					linecap: 'round',
					stickyTracking: false,
					rounded: true,
				},
			},
			tooltip: {
				enabled: false,
			},
			series:[{
				name: '',
				data:[{
					color: 'rgba( 255,255,255,0.7 )',
					radius: '100%;',
					innerRadius: '95%',
					y: 0,
				}]
			}],
			credits: {
				enabled: false,
			}
		});
	}
	if ( jQuery( '#smsReadyPercentage' ).length > 0 ) {
		graphs['smsReadyPercentage'] = Highcharts.chart( 'smsReadyPercentage', {
			chart: {
				type: 'solidgauge',
				backgroundColor: 'rgba( 0,0,0,0 )',
			},
			title: {
				text: '',
			},
			pane: {
				startAngle: 0,
				endAngle: 360,
				background:[{
					outerRadius: '100%',
					innerRadius: '95%',
					backgroundColor: 'rgba(0,0,0,0.5)',
					borderWidth: 0,
				}]
			},
			yAxis: {
				min: 0,
				max: 100,
				lineWidth: 0,
				tickPositions: [],
			},
			plotOptions: {
				solidgauge: {
					dataLabels: {
						enabled: false,
					},
					linecap: 'round',
					stickyTracking: false,
					rounded: true,
				},
			},
			tooltip: {
				enabled: false,
			},
			series:[{
				name: '',
				data:[{
					color: 'rgba( 255,255,255,0.7 )',
					radius: '100%;',
					innerRadius: '95%',
					y: 0,
				}]
			}],
			credits: {
				enabled: false,
			}
		});
	}
	if ( jQuery( '#graph-realtime-processes' ).length > 0 ) {
		graphs['graph-realtime-processes'] = Highcharts.chart( 'graph-realtime-processes', {
			chart: {
				zoomType: 'x',
				backgroundColor: 'rgba( 0,0,0,0 )',
			},
			colors: [
				'rgba( 255,255,255,0.5 )',
			],
			title: {
				text: '',
			},
			xAxis: {
				gridLineWidth: 0,
				minorGridLineWidth: 0,
				type: 'datetime',
				labels: {
	                enabled: true
	            },
				title: {
					text: null,
				},
				lineColor: 'transparent',
				plotLines:[{ value: function() {} } ],
				color: 'transparent',
			},
			yAxis: {
				gridLineWidth: 0,
				minorGridLineWidth: 0,
				labels: {
	                enabled: true
	            },
				title: {
					text: null,
				},
				lineColor: 'transparent',
				plotLines:[{ value: function() {} } ],
				color: 'transparent',
			},
			legend: {
				enabled: false,
			},
			credits: {
				enabled: false,
			},
			plotOptions: {
				areaspline: {
					fillColor: {
						linearGradient: {
							x1: 0,
							y1: 0,
							x2: 0,
							y2: 1,
						},
						stops: [
							[0, 'rgba( 255,255,255,0.5 )'],
                        	[1, 'rgba( 255,255,255,0.5 )'],
						],
					},
					marker: {
						radius: 0,
						enabled: false,
					},
					lineWidth: 1,
					states: {
						hover: {
							lineWidth: 1,
						}
					},
					threshold: null,
				}
			},
			tooltip: {
				enabled: false,
			},
			series:[{
				type: 'areaspline',
				name: 'Running Processes',
				data: [],
			}]
		} );
	}
}

function tc_init_streamer() {
	s = tcldwstreamer({
		host: tcd.websockethost,
		update: function( data ) {
			//console.log( data );
		},
		error: function( msg ) {
			tc_notify_error( msg );
		}
	});
	s.addHandler( 'phpthreads', function( data ) {
		if ( 'undefined' !== typeof( graphs['graph-realtime-processes'] ) ) {
			graphs['graph-realtime-processes'].series[0].setData( data );
			//var point = [getJsTimestamp(), parseInt( data )];
			//graphs['graph-realtime-processes'].series[0].addPoint( point );
			//if ( graphs['graph-realtime-processes'].series[0].data.length > 50 ) {
			//	graphs['graph-realtime-processes'].series[0].removePoint();
			//}
		}
	});
	s.addHandler( 'livefeed', function( data ) {
		var html = '';
		for (var i = 0; i < data.length; i++) {
			html += data[i] + "\r\n";
		}
		jQuery( '#terminal' ).html( html );
		jQuery( '.open-import-log' ).addClass( 'updated' );
		setTimeout( function() { jQuery( '.open-import-log' ).removeClass( 'updated' ) }, 500 );
	});
	s.addHandler( 'import-job-finished', function( data ) {
		tc_notify_success( data.msg );
		jQuery( '[data-file="%s"]', data.file ).fadeOut( 300, function() {
			jQuery( this ).remove();
		});
	});
	s.addHandler( 'lead-stats', function( data ) {
		for ( var key in data ) {
			jQuery( sprintf( '[data-leadinfo-key="%s"]', key ) ).html( numberWithCommas( data[key] ) );
			if ( 'allLeadCount' == key ) {
				jQuery( '[data-leadquery-beanid="0"]' ).html( numberWithCommas( data[key] ) );
			}
		}
		if ( jQuery( '[data-leadquery-beanid="0"]' ).length > 0 ) {
			var total = parseInt( jQuery( '[data-leadquery-beanid="0"]' ).html().replace( /,/g, '' ) );
			var callCenterReady = parseInt( jQuery( '[data-leadinfo-key="callCenterReady"]' ).html().replace( /,/g, '' ) );
			var callCenterReadyPercent = 100 * parseFloat( callCenterReady / total ).toFixed( 2 );
			var emailMarketingReady = parseInt( jQuery( '[data-leadinfo-key="emailMarketingReady"]' ).html().replace( /,/g, '' ) );
			var emailMarketingReadyPercent = 100 * parseFloat( emailMarketingReady / total ).toFixed( 2 );
			var smsMarketingReady = parseInt( jQuery( '[data-leadinfo-key="smsMarketingReady"]' ).html().replace( /,/g, '' ) );
			var smsMarketingReadyPercent = 100 * parseFloat( smsMarketingReady / total ).toFixed( 2 );
			if ( 'undefined' !== typeof( callCenterReadyPercent ) ) {
				if ( 'undefined' !== typeof( graphs['callCenterReadyPercentage'] ) ) {
					graphs['callCenterReadyPercentage'].series[0].setData([{
						color: 'rgba( 255,255,255,0.7 )',
						radius: '100%;',
						innerRadius: '95%',
						y: callCenterReadyPercent,
					}]);
				}
				jQuery( '#callCenterReadyPercentageValue' ).text( sprintf( '%d%s', callCenterReadyPercent, '%' ) );
			}

			if ( 'undefined' !== typeof( emailMarketingReadyPercent ) ) {
				if ( 'undefined' !== typeof( graphs['emailMarketingReadyPercentage'] ) ) {
					graphs['emailMarketingReadyPercentage'].series[0].setData([{
						color: 'rgba( 255,255,255,0.7 )',
						radius: '100%;',
						innerRadius: '95%',
						y: emailMarketingReadyPercent,
					}]);
				}
				jQuery( '#emailMarketingReadyPercentageValue' ).text( sprintf( '%d%s', emailMarketingReadyPercent, '%' ) );
			}

			if ( 'undefined' !== typeof( smsMarketingReadyPercent ) ) {
				if ( 'undefined' !== typeof( graphs['smsReadyPercentage'] ) ) {
					graphs['smsReadyPercentage'].series[0].setData([{
						color: 'rgba( 255,255,255,0.7 )',
						radius: '100%;',
						innerRadius: '95%',
						y: smsMarketingReadyPercent,
					}]);
				}
				jQuery( '#smsReadyPercentageValue' ).text( sprintf( '%d%s', smsMarketingReadyPercent, '%' ) );
			}
		}

		if ( 'object' == typeof( graphs.leads ) ) {
			var ss = graphs.leads.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.addPoint([ parseInt( moment().utc().format( 'x' ) ), data.allLeadCount ] );
				if ( s.data.length >= 10 ) {
					s.removePoint( 0 );
				}
			}
		}
		if ( 'object' == typeof( graphs.source ) ) {
			var ss = graphs.source.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.setData( data.leadsBySources );
			}
		}
		if ( 'object' == typeof( graphs.lpc ) ) {
			var ss = graphs.lpc.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.setData( data.leadsByCountry );
			}
		}
		if ( 'object' == typeof( graphs.plt ) ) {
			var ss = graphs.plt.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.setData( data.phonesByType );
			}
		}
		if ( 'object' == typeof( graphs.elv ) ) {
			var ss = graphs.elv.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.setData( data.emailByValidity );
			}
		}
	});
	s.addHandler( 'system-stats', function( data ) {
		for ( var key in data ) {
			jQuery( sprintf( '[data-sysinfo-key="%s"]', key ) ).html( data[key] );
		}
		if ( 'object' == typeof( graphs.rpm ) ) {
			var ss = graphs.rpm.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.addPoint([ parseInt( moment().utc().format( 'x' ) ), data.rlm ] );
				if ( s.data.length >= 10 ) {
					s.removePoint( 0 );
				}
			}
		}
		if ( 'object' == typeof( graphs.cpu ) ) {
			var ss = graphs.cpu.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.addPoint([ parseInt( moment().utc().format( 'x' ) ), data.cpu ] );
				if ( s.data.length >= 10 ) {
					s.removePoint( 0 );
				}
			}
		}
		if ( 'object' == typeof( graphs.memory ) ) {
			var ss = graphs.memory.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.addPoint([ parseInt( moment().utc().format( 'x' ) ), data.memory ] );
				if ( s.data.length >= 10 ) {
					s.removePoint( 0 );
				}
			}
		}
		if ( 'object' == typeof( graphs.threads ) ) {
			var ss = graphs.threads.series;
			if ( 'object' == typeof( ss ) && ss.length > 0 ) {
				var s = ss[0];
				s.addPoint([ parseInt( moment().format( 'x' ) ), data.threads ] );
				if ( s.data.length >= 10 ) {
					s.removePoint( 0 );
				}
			}
		}
	});
	s.addHandler( 'import-stats', function( data ) {
		var html = '<h2 class="tile-title">Importing Files</h2>';
		var ijh = '';
		jQuery( '#import-jobs-widget' ).html( '' );
		jQuery( '#import-jobs-table tbody' ).html( '' );
		for ( var file in data ) {
			if ( data[file].progress > 0 && data[file].progress < 100 ) {
				var add = sprintf(
					'<div class="s-widget-body"><div class="side-border"><small>%s</small><div class="progress progress-small"><a href="#" data-toggle="tooltip" title="" class="progress-bar tooltips progress-bar-success" style="width: %s;" data-original-title="%s"></a></div></div></div>',
					file,
					sprintf( '%s%s', data[file].progress, '%' ),
					sprintf( '%s%s', data[file].progress, '%' )
				);
				html += add;
			}
			var ijha = sprintf(
				'<tr data-file="%s"><td>&nbsp;</td><td><div class="btn-group" role="group"><button disabled class="btn btn-xs btn-primary btn-file-action" data-action="import">Import</button><button disabled class="btn btn-xs btn-danger btn-file-action" data-action="cancel">Cancel</button></div></td><td>%s</td><td data-info="total"><small><i>Pending</i></small></td></td><td data-info="valid"><small><i>Pending</i></small></td></td><td data-info="incomplete"><small><i>Pending</i></small></td></td><td data-info="duplicate"><small><i>Pending</i></small></td></td><td data-info="invalid"><small><i>Pending</i></small></td></td><td class="row-progress-cell">&nbsp;</td></tr>',
				file,
				file
			);
			ijh = ijha;
			jQuery( '#import-jobs-table tbody' ).append( ijh );
			var nr = jQuery( sprintf( 'tr[data-file="%s"]', file ) );
			nr.each( tc_init_file_row );
			// Gotta Keep em Seperated //
			var row = jQuery( sprintf( 'tr[data-file="%s"]', file ) );
			for( var key in data[file] ) {
				row.find( sprintf( '[data-info="%s"]', key ) ).html( null == data[file][key] ? '<code>Unknown</code>' : sprintf( '<span class="label label-default">%s</span>', data[file][key] ) );
			}
			if ( 0 == row.find( 'td:last-child .progress>div' ).length ) {
				var pcell = row.find( '.row-progress-cell' );
				pcell.html( '<div class="progress"><div class="progress-bar progress-bar-success " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
			}
			row.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			row.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			row.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			row.find( '.progress>div' ).removeClass( 'progress-bar-info' );
			row.find( '.progress>div' ).addClass( 'progress-bar-success' );
			row.find( 'td:last-child .progress>div' ).attr( 'aria-valuenow', parseFloat( data[file].progress ) );
			row.find( 'td:last-child .progress>div .percent' ).html( parseFloat( data[file].progress ) + '%' );
			row.find( 'td:last-child .progress>div' ).css({
				width: parseFloat( data[file].progress ) + '%',
			});
			if ( 'filtering' == data[file].status ) {
				row.find( 'td:nth-child(2)' ).html( '<div class="btn-group" role="group"><button disabled class="btn btn-xs btn-warning btn-file-action" data-action="reset">Reset Import Job</button><button disabled class="btn btn-xs btn-danger btn-file-action" data-action="stop">Stop</button></div>' );
			}
			else if ( 'pending' == data[file].status ) {
				row.find( 'td:nth-child(2)' ).html( '<div class="btn-group" role="group"><button disabled class="btn btn-xs btn-warning btn-file-action" data-action="reset">Reset Import Job</button></div>' );
			}
			else if ( 'new' == data[file].status ) {
				row.find( 'td:nth-child(2)' ).html( '<div class="btn-group" role="group"><button disabled class="btn btn-xs btn-warning btn-file-action" data-action="reset">Reset Import Job</button><button disabled class="btn btn-xs btn-danger btn-file-action" data-action="cancel">Cancel</button></div>' );
			}
			else {
				row.find( 'td:nth-child(2)' ).html( '<div class="btn-group" role="group"><button disabled class="btn btn-xs btn-primary btn-file-action" data-action="import">Import</button><button disabled class="btn btn-xs btn-danger btn-file-action" data-action="cancel">Cancel</button></div>' );
			}
			row.each( tc_init_file_row );
		}
		jQuery( '#import-jobs-widget' ).append( html );
	});
	s.addHandler( 'export-stats', function( data ) {
		for ( var i = 0; i < data.length; i++ ) {
			var job = data[i];
			var row = jQuery( sprintf( 'tr[data-job-id="%d"]', job.id ) );
			row.find( '.label.label-default' ).html( job.total_rows );
			row.find( '.label.label-success' ).html( job.printed_rows );
			row.find( 'td:last-child .progress>div' ).attr( 'aria-valuenow', parseFloat( job.progress ) );
			row.find( 'td:last-child .progress>div' ).css({
				width: parseFloat( job.progress ) + '%',
			});
			row.find( 'td:last-child .progress>div>.percent' ).html( parseFloat( job.progress ) + '%' );
			if ( parseInt( job.total_rows ) == parseInt( job.printed_rows ) ) {
				row.find( 'td:first-child' ).html( sprintf( '<a href="/leads/export/%d" class="btn btn-success btn-block" data-job-action="download">Download File</a>', job.id ) );
			}
		}
	});
	s.addHandler( 'graphdata', function( data ) {
		for ( var key in data ) {
			if ( 'string' == typeof( key ) && 'object' == typeof( graphs[ key ] ) ) {
				var ss = graphs[ key ].series;
				if ( 'object' == typeof( ss ) && ss.length > 0 ) {
					var s = ss[0];
					s.setData( data[key] );
				}
			}
		}
	});
	s.addHandler( 'leads-per-country', function( data ) {
		if ( 'object' == typeof( graphs['leadsbycountry'] ) ) {
			var s = graphs['leadsbycountry'].series[0];
			var q_id = jQuery('#loadsavedquery-dash').val();
			if ( null == q_id || 0 == q_id ) {
				s.setData( data );
				tc_make_leads_by_country_table( data );
			}
		}
		if ( 'object' == typeof( tcd.savedqueryresults ) ) {
			tcd.savedqueryresults['0'] = {
				query_id: 0,
				query_name: 'Unfiltered',
				query_series: data,
			};
		}
	});
    s.addHandler( 'saved-query-results', function( data ) {
        if ( 'object' == typeof( graphs['leadsbycountry'] ) ) {
            var s = graphs['leadsbycountry'].series[0];
            var q_id = jQuery('#loadsavedquery-dash').val();
            for (var i = 0; i < data.length; i++) {
            	var sid = data[i].query_id;
            	tcd.savedqueryresults[ sid ] = data[i];

            }
            if ( q_id > 0 ) {
                var d = data[q_id - 1];
                if ( 'object' == typeof( d ) && 'undefined' !== typeof( d.query_series ) ) {
                	s.setData( d.query_series );
                	tc_make_leads_by_country_table( d.query_series );
                }
            }
        }
    });
	s.addHandler( 'export-queries', function( data ) {
		for ( var beanid in data ) {
			var id = beanid.replace( 'bean_', '' );
			id = parseInt( id );
			var ds = jQuery( sprintf( '[data-leadquery-beanid="%d"]', id ) );
			var l = data[beanid].length - 1;
			var ld = data[beanid][l][1];
			ds.html( numberWithCommas( ld ) );
		}
	});
}

function tc_handle_set_value_from_saved_query( e ) {
    var q_id = jQuery('#loadsavedquery-dash').val();
    var chosen_text = jQuery('#loadsavedquery-dash').children( 'option:selected' ).text();
    jQuery( '.title-leadsbycountry' ).text( sprintf( '%s Leads By Country', chosen_text ) );
    var data = tcd.savedqueryresults[ q_id ];
	if ( 'object' == typeof( data ) && 'undefined' !== typeof( data ) ) {
		series = data['query_series'];
		tc_make_leads_by_country_table( data['query_series'] );
		if ( 'object' == typeof( graphs.leadsbycountry ) ) {
		    var ss = graphs.leadsbycountry.series;
		    if ( 'object' == typeof( ss ) && ss.length > 0 ) {
		        var s = ss[0];
		        s.setData(series);
		    }
		}
	}
	else {
		if ( true == firstloaded ) {
			tc_notify_error( 'Invalid or Missing Map Data' );
		}
		else {
			firstloaded = true;
		}
	}
}

function tc_make_leads_by_country_table( data ) {
	var html = '';
	for (var i = 0; i < data.length; i++) {
		var iso = ( 'undefined' !== typeof( data[i].code ) ) ? data[i].code : data[i]['iso-a2'];
		if ( 'undefined' !== typeof( iso ) && 'undefined' !== typeof( tcd.countries[ iso ] ) && 'undefined' !== typeof( tcd.countries[ iso ].name ) ) {
			html += sprintf(
				'<tr><td width="50" class="text-center"><span class="flag-icon flag-icon-%s"></span></td><td>%s</td><td>%s</td>',
				iso.toLowerCase(),
				tcd.countries[ iso ].name,
				( 'undefined' == typeof( data[i].value ) || null == data[i].value || '' == data[i].value ) ? 0 : data[i].value
			);
		}
	}
	jQuery( '#list-leads-by-country' ).html( html );
}

function tc_get_sysinfo() {
	tc_ajax(
		'get_sysinfo',
		{},
		function( data ) {
			for( var key in data ) {
				jQuery( sprintf( '#sysinfo span[data-sysinfo-key="%s"]', key ) ).html( data[key] );
			}
		},
		function( data ) {
			tc_notify_error( data, 'Error' );
			console.warn( data );
		}
	);
}

function tc_get_export_job_stats() {
	if ( _tc_shown_export_jobs.length > 0 ) {
		tc_ajax(
			'get_export_job_status',
			{ jobs: _tc_shown_export_jobs },
			function( data ) {
				for ( var i = 0; i < data.length; i++ ) {
					var job = data[i];
					var row = jQuery( sprintf( 'tr[data-job-id="%d"]', job.id ) );
					row.find( '.label.label-default' ).html( job.total_rows );
					row.find( '.label.label-success' ).html( job.printed_rows );
					row.find( 'td:last-child .progress>div' ).attr( 'aria-valuenow', parseFloat( job.progress ) );
					row.find( 'td:last-child .progress>div' ).css({
						width: parseFloat( job.progress ) + '%',
					});
					row.find( 'td:last-child .progress>div>.percent' ).html( parseFloat( job.progress ) + '%' );
					if ( parseInt( job.total_rows ) == parseInt( job.printed_rows ) ) {
						row.find( 'td:first-child' ).html( sprintf( '<a href="/leads/export/%d" class="btn btn-success btn-block" data-job-action="download">Download File</a>', job.id ) );
					}
				}
			},
			function( data ) {
				tc_notify_error( data, 'Error' );
				console.warn( data );
			}
		);
	}
}

function tc_add_export_job_to_list( data ) {
	var rbtpl = '<a href="javascript:void(0);" class="btn btn-warning btn-block" data-job-action="reset">Reset Job</a>';
	var dbtpl = '<a href="/leads/export/%d" class="btn btn-success btn-block" data-job-action="download">Download File</a>';
	var rtpl = '<tr><td colspan="7">%s</td></tr><tr data-job-id="%d"><td>%s</td><td><span>%d</span></td><td><span>%s</span></td><td><span>%s</span></td><td><span class="label label-default">%d</span></td><td><span class="label label-success">%d</span></td><td><div class="progress"><div class="progress-bar progress-bar-success " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: %d%s"><span class="percent">%d%s</span></div></div></td></tr>';
	var html = sprintf(
		rtpl,
		data.description,
		parseInt( data.id ),
		( parseInt( data.total_rows ) !== parseInt( data.printed_rows ) ) ? rbtpl : sprintf( dbtpl, parseInt( data.id ) ),
		parseInt( data.id ),
		data.request_time,
		data.status.toUpperCase(),
		parseInt( data.total_rows ),
		parseInt( data.printed_rows ),
		0,
		'%',
		0,
		'%'
	);
	jQuery( '#export-jobs-table tbody' ).prepend( html );
	jQuery( sprintf( 'tr[data-job-id="%d"]', data.id ) ).each( tc_init_export_job_manager );
}

function tc_init_export_job_manager() {
	var row = jQuery( this );
	_tc_shown_export_jobs.push( row.attr( 'data-job-id' ) );
	var feedbackCell = row.find( 'td:first-child' );
	row.find( '[data-job-action="reset"]' ).on( 'click', function( e ) {
		e.preventDefault();
		tc_init_export_job_reset_button( row, feedbackCell );
	});
}

function tc_init_export_job_reset_button( row, feedbackCell ) {
	tc_ajax(
		'reset_export_job',
		{ job: row.attr( 'data-job-id' ) },
		function( data ) {
			feedbackCell.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			feedbackCell.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			feedbackCell.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			feedbackCell.find( '.progress>div' ).removeClass( 'progress-bar-info' );
			feedbackCell.find( '.progress>div' ).addClass( 'progress-bar-primary' );
			feedbackCell.find( '.progress>div>.percent' ).html( data );
			setTimeout( function() {
				var totalRows = parseInt( row.find( 'span.label.label-default' ).html() );
				var currentRows = parseInt( row.find( 'span.label.label-success' ).html() );
				if ( totalRows == currentRows ) {
					feedbackCell.html( sprintf( '<a href="/leads/export/%d" class="btn btn-success btn-block" data-job-action="download">Download File</a>', row.attr( 'data-job-id' ) ) );
				}
				else {
					feedbackCell.html( '<a href="javascript:void(0);" class="btn btn-warning btn-block" data-job-action="reset">Reset Job</a>' );
					row.find( '[data-job-action="reset"]' ).on( 'click', function( e ) {
						e.preventDefault();
						tc_init_export_job_reset_button( row, feedbackCell );
					});
				}
			}, 3000 );
			row.find( 'a.btn' ).prop( 'disabled', false );
			row.find( 'input' ).prop( 'disabled', false );
			row.find( 'input' ).prop( 'readonly', false );
			row.find( 'button' ).prop( 'disabled', false );
			row.find( 'select' ).prop( 'disabled', false );
			row.find( 'a.btn' ).removeClass( 'disabled' );
		},
		function( data ) {
			tc_notify_error( data, 'Error' );
			console.warn( data );
			feedbackCell.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			feedbackCell.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			feedbackCell.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			feedbackCell.find( '.progress>div' ).removeClass( 'progress-bar-info' );
			feedbackCell.find( '.progress>div' ).addClass( 'progress-bar-danger' );
			feedbackCell.find( '.progress>div>.percent' ).html( data );
			setTimeout( function() {
				var totalRows = parseInt( row.find( 'span.label.label-default' ).html() );
				var currentRows = parseInt( row.find( 'span.label.label-success' ).html() );
				if ( totalRows == currentRows ) {
					feedbackCell.html( sprintf( '<a href="/leads/export/%d" class="btn btn-success btn-block" data-job-action="download">Download File</a>', row.attr( 'data-job-id' ) ) );
				}
				else {
					feedbackCell.html( '<a href="javascript:void(0);" class="btn btn-warning btn-block" data-job-action="reset">Reset Job</a>' );
					row.find( '[data-job-action="reset"]' ).on( 'click', function( e ) {
						e.preventDefault();
						tc_init_export_job_reset_button( row, feedbackCell );
					});
				}
			}, 3000 );
			row.find( 'a.btn' ).prop( 'disabled', false );
			row.find( 'input' ).prop( 'disabled', false );
			row.find( 'input' ).prop( 'readonly', false );
			row.find( 'button' ).prop( 'disabled', false );
			row.find( 'select' ).prop( 'disabled', false );
			row.find( 'a.btn' ).removeClass( 'disabled' );
		},
		function() {
			feedbackCell.html( '<div class="progress"><div class="progress-bar progress-bar-info " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
			row.find( 'a.btn' ).prop( 'disabled', true );
			row.find( 'a.btn' ).addClass( 'disabled' );
			row.find( 'button' ).prop( 'disabled', true );
			row.find( 'input' ).prop( 'disabled', true );
			row.find( 'input' ).prop( 'readonly', true );
			row.find( 'select' ).prop( 'disabled', true );
		},
		function( percent ) {
			if ( percent >= 1 ) {
				percent = 1;
			}
			feedbackCell.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
			feedbackCell.find( '.progress>div' ).css({
				width: ( percent * 100 ) + '%',
			});
			feedbackCell.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		}
	);
}

function tc_init_export_job_form() {
	var form = jQuery( this );
	if ( 0 == _tc_existing_columns.length ) {
		tc_ajax(
			'get_existing_columns',	//ajax_get_existing_columns
			{ forfilter: true },
			function( data ) {
				_tc_existing_columns = data;
				form.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				form.find( '.progress>div' ).addClass( 'progress-bar-primary' );
				form.find( '.progress>div>.percent' ).html( 'Loaded System Columns' );
				// this is where we load the relevant saved query fields
				if ( 'undefined' !== typeof( form.attr( 'data-saved-query-id' ) ) ) {
					tc_populate_saved_query_fields( form.attr( 'data-saved-query-id' ), form );
				}
				// populate saved query based on "select" value
				// end of loading saved query fields
				form.find( 'a.btn' ).prop( 'disabled', false );
				form.find( 'input' ).prop( 'disabled', false );
				form.find( 'input' ).prop( 'readonly', false );
				form.find( 'button' ).prop( 'disabled', false );
				form.find( 'select' ).prop( 'disabled', false );
				form.find( 'select' ).trigger( 'chosen:updated' );
				form.find( 'a.btn' ).removeClass( 'disabled' );
				form.find( '#exportfields' ).trigger( 'chosen:updated' );
			},
			function( data ) {
				form.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				form.find( '.progress>div' ).addClass( 'progress-bar-danger' );
				form.find( '.progress>div>.percent' ).html( data );
			},
			function() {
				form.find('.ajax-response').html( '<div class="progress"><div class="progress-bar progress-bar-info " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
				form.find( 'a.btn' ).prop( 'disabled', true );
				form.find( 'a.btn' ).addClass( 'disabled' );
				form.find( 'button' ).prop( 'disabled', true );
				form.find( 'input' ).prop( 'disabled', true );
				form.find( 'input' ).prop( 'readonly', true );
				form.find( 'select' ).prop( 'disabled', true );
			},
			function ( percent ) {
				if ( percent >= 1 ) {
					percent = 1;
				}
				form.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
				form.find( '.progress>div' ).css({
					width: ( percent * 100 ) + '%',
				});
				form.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
			}
		);
	}
	form.find( '.tc-add-filter-row' ).on( 'click', function() {
		var highestConditionId = 0;
		form.find( 'tbody>tr[data-condition-id]' ).each( function() {
			var r = jQuery( this );
			if ( parseInt( r.attr( 'data-condition-id' ) ) > highestConditionId ) {
				highestConditionId = parseInt( r.attr( 'data-condition-id' ) );
			}
		});
		var newConditionId = highestConditionId + 1;
		form.find( 'tbody' ).append( sprintf(
			'<tr data-condition-id="%d"><td><button data-button-role="remove" role="button" class="no-action btn btn-danger btn-block">Remove</button></td><td><code>%d</code></td><td>%s</td><td></td><td></td><td></td></tr>',
			newConditionId,
			newConditionId,
			tc_generate_filter_selecthtml( newConditionId )
		) );
		var row = form.find( sprintf( 'tr[data-condition-id="%d"]', newConditionId ) );
		row.find( 'button[data-button-role="remove"]' ).on( 'click', function() {
			row.remove();
		});
		row.find( sprintf( 'select[name="conditions[%s][field]"]', newConditionId ) ).on( 'change', function() {
			var field = jQuery( this );
			tc_generate_filter_row( row, field, newConditionId );
		});
		row.find( 'select' ).each( function() {
			var chsn = jQuery( this );
			if ( 1 == chsn.attr( 'data-can-add' ) ) {
				chsn.chosen({ width: '100%', no_results_text: 'Press Enter to add new entry:' });
				var chosen = chsn.data('chosen');
				row.find('li.search-field input').on('keyup', function(e) {
					if (e.which == 13 && chosen.dropdown.find('li.no-results').length > 0) {
						var option = jQuery("<option>").val(this.value).text(this.value);
						chsn.prepend(option);
						chsn.find(option).prop('selected', true);
						chsn.trigger("chosen:updated");
				    }
				});
			}
			else {
				chsn.chosen({ width: '100%', search_contains: false });
			}
		});
	});
	form.find( '.tc-preview-export' ).on( 'click', function() {
		tc_ajax(
			'preview_export_job',
			form.serialize(),
			function( data ) {
				jQuery( '#leadcount' ).html( data );
				form.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				form.find( '.progress>div' ).addClass( 'progress-bar-primary' );
				form.find( 'a.btn' ).prop( 'disabled', false );
				form.find( 'input' ).prop( 'disabled', false );
				form.find( 'input' ).prop( 'readonly', false );
				form.find( 'button' ).prop( 'disabled', false );
				form.find( 'select' ).prop( 'disabled', false );
				form.find( 'a.btn' ).removeClass( 'disabled' );
				form.find( '.progress>div>.percent' ).html( sprintf( 'Found %d Leads matching your filters', data ) );
				form.find( '#exportfields' ).trigger( 'chosen:updated' );
			},
			function( data ) {
				form.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				form.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				form.find( '.progress>div' ).addClass( 'progress-bar-danger' );
				form.find( 'a.btn' ).prop( 'disabled', false );
				form.find( 'input' ).prop( 'disabled', false );
				form.find( 'input' ).prop( 'readonly', false );
				form.find( 'button' ).prop( 'disabled', false );
				form.find( 'select' ).prop( 'disabled', false );
				form.find( 'a.btn' ).removeClass( 'disabled' );
				form.find( '.progress>div>.percent' ).html( data );
				tc_notify_error( data, 'Error' );
				console.warn( data );
			},
			function() {
				form.find('.ajax-response').html( '<div class="progress"><div class="progress-bar progress-bar-primary " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
				form.find( 'a.btn' ).prop( 'disabled', true );
				form.find( 'a.btn' ).addClass( 'disabled' );
				form.find( 'button' ).prop( 'disabled', true );
				form.find( 'input' ).prop( 'disabled', true );
				form.find( 'input' ).prop( 'readonly', true );
				form.find( 'select' ).prop( 'disabled', true );
				form.find( '#exportfields' ).trigger( 'chosen:updated' );
			},
			function( percent ) {
				if ( percent >= 1 ) {
					percent = 1;
				}
				form.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
				form.find( '.progress>div' ).css({
					width: ( percent * 100 ) + '%',
				});
				if ( 1 !== percent ) {
					form.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
				}
				else {
					form.find( '.progress>div>.percent' ).html( 'Processing Filters. Please Wait.' );
				}
			}
		);
	});
	form.find( '#exportfields' ).chosen({ width: '100%', search_contains: false });
}

function tc_populate_saved_query_fields( sqid, form ) {
	tc_ajax(
		'get_saved_query_filters',
		{ quid: sqid },
		function( data ) {
			form.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			form.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			form.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			form.find( '.progress>div' ).removeClass( 'progress-bar-info' );
			form.find( '.progress>div' ).addClass( 'progress-bar-primary' );
			form.find( '.progress>div>.percent' ).html( 'Loading Saved Query' );
			tc_notify_success( 'Please wait until all fields have a remove button before continuing.', 'Loading Saved Query' );
			form.find( 'a.btn' ).prop( 'disabled', false );
			form.find( 'input' ).prop( 'disabled', false );
			form.find( 'input' ).prop( 'readonly', false );
			form.find( 'button' ).prop( 'disabled', false );
			form.find( 'select' ).prop( 'disabled', false );
			form.find( 'a.btn' ).removeClass( 'disabled' );
			form.find( '#exportfields' ).trigger( 'chosen:updated' );
			for ( var i in data ) {
				form.find( 'tbody' ).append( sprintf(
					'<tr data-condition-id="%d"><td><button data-button-role="remove" role="button" class="no-action btn btn-danger btn-block">Remove</button></td><td><code>%d</code></td><td>%s</td><td></td><td></td><td></td></tr>',
					i,
					i,
					tc_generate_filter_selecthtml( i, data[i].field )
				) );
				var row = form.find( sprintf( 'tr[data-condition-id="%d"]', i ) );
				row.find( 'button[data-button-role="remove"]' ).on( 'click', function() {
					row.remove();
				});
				row.find( sprintf( 'select[name="conditions[%s][field]"]', i ) ).on( 'change', function() {
					var field = jQuery( this );
					tc_generate_filter_row( row, field, i );
				});
				row.find( 'select' ).each( function() {
					var chsn = jQuery( this );
					if ( 1 == chsn.attr( 'data-can-add' ) ) {
						chsn.chosen({ width: '100%', no_results_text: 'Press Enter to add new entry:' });
						var chosen = chsn.data('chosen');
						row.find('li.search-field input').on('keyup', function(e) {
							if (e.which == 13 && chosen.dropdown.find('li.no-results').length > 0) {
								var option = jQuery("<option>").val(this.value).text(this.value);
								chsn.prepend(option);
								chsn.find(option).prop('selected', true);
								chsn.trigger("chosen:updated");
						    }
						});
					}
					else {
						chsn.chosen({ width: '100%', search_contains: false });
					}
				});
			}
			form.find( 'tr[data-condition-id]' ).each( function() {
				var tr = jQuery( this );
				var id = parseInt( tr.attr( 'data-condition-id' ) );
				var field = tr.find( sprintf( 'select[name="conditions[%s][field]"]', id ) );
				tc_generate_filter_row( tr, field, id, function( ro, fi, ni ) {
					var attr = ro.find( sprintf( '*[name="conditions[%s][attribute]"]', ni ) );
					attr.val( data[ ni ].attribute );
					attr.trigger( 'chosen:updated' );
					tc_generate_filter_row( ro, fi, ni, function( row, fie, nid ) {
						var cond = row.find( sprintf( '*[name="conditions[%s][condition]"]', nid ) );
						cond.val( data[ nid ].condition );
						cond.trigger( 'chosen:updated' );
						tc_generate_filter_row( row, fie, nid, function( frow, ffie, fnie ) {
							if ( 'string' == typeof( data[fnie].filter ) ) {
								var filter = frow.find( sprintf( '*[name="conditions[%s][filter]"]', fnie ) );
								filter.val( data[fnie].filter );
							}
							else if ( 'object' == typeof( data[fnie].filter ) ) {
								var filter = frow.find( sprintf( '*[name="conditions[%s][filter][]"]', fnie ) );
								for ( var fvi = 0; fvi < data[fnie].filter.length; fvi++ ) {
									var val = data[fnie].filter[fvi];
									filter.find( sprintf( 'option[value="%s"]', val ) ).each( function() {
										var opt = jQuery( this );
										opt.prop( 'selected', true );
									})
								}
								filter.trigger( 'chosen:updated' );
							}
							var ffield = frow.find( sprintf( 'select[name="conditions[%s][field]"]', fnie ) );
							var fattri = frow.find( sprintf( 'select[name="conditions[%s][attribute]"]', fnie ) );
							var fcondi = frow.find( sprintf( 'select[name="conditions[%s][condition]"]', fnie ) );
							ffield.on( 'change', function() {
								tc_generate_filter_row( frow, ffie, fnie );
							});
							fattri.on( 'change', function() {
								tc_generate_filter_row( frow, ffie, fnie );
							});
							fcondi.on( 'change', function() {
								tc_generate_filter_row( frow, ffie, fnie );
							});
						} );
					});
				});
			})
		},
		function( data ) {
			form.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			form.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			form.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			form.find( '.progress>div' ).removeClass( 'progress-bar-info' );
			form.find( '.progress>div' ).addClass( 'progress-bar-danger' );
			form.find( '.progress>div>.percent' ).html( data );
			form.find( 'a.btn' ).prop( 'disabled', false );
			form.find( 'input' ).prop( 'disabled', false );
			form.find( 'input' ).prop( 'readonly', false );
			form.find( 'button' ).prop( 'disabled', false );
			form.find( 'select' ).prop( 'disabled', false );
			form.find( 'a.btn' ).removeClass( 'disabled' );
			form.find( '#exportfields' ).trigger( 'chosen:updated' );
			tc_notify_error( data, 'Error' );
			console.warn( data );
		},
		function() {
			form.find('.ajax-response').html( '<div class="progress"><div class="progress-bar progress-bar-info " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">Loading Saved Query</span></div></div>' );
			form.find( 'a.btn' ).prop( 'disabled', true );
			form.find( 'a.btn' ).addClass( 'disabled' );
			form.find( 'button' ).prop( 'disabled', true );
			form.find( 'input' ).prop( 'disabled', true );
			form.find( 'input' ).prop( 'readonly', true );
			form.find( 'select' ).prop( 'disabled', true );
		},
		function( percent ) {
			if ( percent >= 1 ) {
				percent = 1;
			}
			form.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
			form.find( '.progress>div' ).css({
				width: ( percent * 100 ) + '%',
			});
			form.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		}
	);
	tc_ajax(
		'get_saved_query_grouping',
		{ quid: sqid },
		function( data ) {
			form.find( '#filtergrouping' ).val( data );
		},
		function( data ) {
			tc_notify_error( data, 'Error' );
			console.warn( data );
		}
	);
}

function tc_generate_filter_row( row, field, newConditionId, after ) {
		tc_ajax(
			'get_filter_options_for_field',	//ajax_get_filter_options_for_field
			{
				cid: newConditionId,
				field: field.val(),
				attr: row.find( sprintf( '*[name="conditions[%s][attribute]"]', newConditionId ) ).val(),
				condition: row.find( sprintf( '*[name="conditions[%s][condition]"]', newConditionId ) ).val(),
				filter: row.find( sprintf( '*[name="conditions[%s][filter]"]', newConditionId ) ).val(),
			},
			function( data ) {
				row.find( 'td:first-child' ).html( '<button data-button-role="remove" role="button" class="no-action btn btn-danger btn-block">Remove</button>' );
				row.find( 'button[data-button-role="remove"]' ).on( 'click', function() {
					row.remove();
				});
				row.find( 'td:nth-child(4)' ).html( data.ahtml );
				row.find( 'td:nth-child(5)' ).html( data.chtml );
				row.find( 'td:nth-child(6)' ).html( data.fieldhtml );
				row.find( sprintf( '*[name="conditions[%s][attribute]"]', newConditionId ) ).on( 'change', function() {
					tc_generate_filter_row( row, field, newConditionId, function( r ) {
						var to = r.find( sprintf( '*[name="conditions[%s][attribute]"]', newConditionId ) );
						to.trigger( 'afterchange' );
					} );
				});
				row.find( sprintf( '*[name="conditions[%s][condition]"]', newConditionId ) ).on( 'change', function() {
					tc_generate_filter_row( row, field, newConditionId, function( r ) {
						var to = r.find( sprintf( '*[name="conditions[%s][condition]"]', newConditionId ) );
						to.trigger( 'afterchange' );
					} );
				});
				row.find( 'select' ).each( function() {
					var chsn = jQuery( this );
					if ( 1 == chsn.attr( 'data-can-add' ) ) {
						chsn.chosen({ width: '100%', no_results_text: 'Press Enter to add new entry:' });
						var chosen = chsn.data('chosen');
						row.find('li.search-field input').on('keyup', function(e) {
							if (e.which == 13 && chosen.dropdown.find('li.no-results').length > 0) {
	    						var option = jQuery("<option>").val(this.value).text(this.value);
	    						chsn.prepend(option);
	    						chsn.find(option).prop('selected', true);
	    						chsn.trigger("chosen:updated");
						    }
						});
					}
					else {
						chsn.chosen({ width: '100%', search_contains: false });
					}
				});
				row.find( 'select' ).trigger( 'chosen:updated' );
				if ( 'function' == typeof( after ) ) {
					after( row, field, newConditionId );
				}
			},
			function( data ) {
				row.find( 'td:first-child' ).find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				row.find( 'td:first-child' ).find( '.progress>div' ).removeClass( 'progress-bar-success' );
				row.find( 'td:first-child' ).find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				row.find( 'td:first-child' ).find( '.progress>div' ).removeClass( 'progress-bar-info' );
				row.find( 'td:first-child' ).find( '.progress>div' ).addClass( 'progress-bar-danger' );
				row.find( 'td:first-child' ).find( '.progress>div>.percent' ).html( data );
				tc_notify_error( data, 'Error' );
				console.warn( data );
			},
			function() {
				row.find( 'td:first-child' ).html( '<div class="progress progress-lg"><div class="progress-bar progress-bar-info " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
			},
			function( percent ) {
				if ( percent >= 1 ) {
					percent = 1;
				}
				row.find( 'td:first-child' ).find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
				row.find( 'td:first-child' ).find( '.progress>div' ).css({
					width: ( percent * 100 ) + '%',
				});
				row.find( 'td:first-child' ).find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
			}
		);
}

function tc_generate_filter_selecthtml( index, fieldmap ) {
	var html = '';
	html += sprintf( '<select name="conditions[%s][field]" class="form-control fieldmap-select input-lg" data-ci="%s">' + "\r\n", index, index );
	html += '<option selected disabled>Choose a Field</option>' + "\r\n";
	if ( 'string' == typeof( fieldmap ) || null == fieldmap ) {
	for( var key in _tc_existing_columns ) {
	if ( 'name' !== key ) {
	html += sprintf( '	<option value="%s" %s>%s</option>' + "\r\n", key, ( fieldmap == key ) ? 'selected' : '', _tc_existing_columns[ key ] );
	}
	}
	}
	html += sprintf( '</select>' + "\r\n" );
	return html;
}

function tc_preview_single_lead( data ) {
	jQuery( '#preview-results' ).html( data );
	var totaltime = endtime - starttime;
	tc_notify_success( sprintf( 'Took %d ms', totaltime ) );
}

function tc_init_single_lead_import() {
	var obj = jQuery( this );
	var panel = obj.closest( 'form' );
	if ( 0 == _tc_existing_columns.length ) {
		tc_ajax(
			'get_existing_columns',
			{},
			function( data ) {
				_tc_existing_columns = data;
				panel.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				panel.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				panel.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				panel.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				panel.find( '.progress>div' ).addClass( 'progress-bar-primary' );
				panel.find( '.progress>div>.percent' ).html( 'Loaded System Columns' );
				panel.find( 'a.btn' ).prop( 'disabled', false );
				panel.find( 'input' ).prop( 'disabled', false );
				panel.find( 'input' ).prop( 'readonly', false );
				panel.find( 'a.btn' ).removeClass( 'disabled' );
				tc_init_add_field_button( panel );
			},
			function( data ) {
				panel.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				panel.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				panel.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				panel.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				panel.find( '.progress>div' ).addClass( 'progress-bar-danger' );
				panel.find( '.progress>div>.percent' ).html( data );
				tc_notify_error( data, 'Error' );
				console.warn( data );
			},
			function() {
				panel.find('.ajax-response').html( '<div class="progress"><div class="progress-bar progress-bar-info " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
				panel.find( 'a.btn' ).prop( 'disabled', true );
				panel.find( 'a.btn' ).addClass( 'disabled' );
				panel.find( 'input' ).prop( 'disabled', true );
				panel.find( 'input' ).prop( 'readonly', true );
				panel.find( 'select' ).prop( 'disabled', true );
			},
			function ( percent ) {
				if ( percent >= 1 ) {
					percent = 1;
				}
				panel.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
				panel.find( '.progress>div' ).css({
					width: ( percent * 100 ) + '%',
				});
				panel.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
			}
		);
	}
	else {
		tc_init_add_field_button( panel );
	}
}

function tc_init_add_field_button( form ) {
	form.find( '.tc-add-field-row' ).on( 'click', function( e ) {
		e.preventDefault();
		var highestRow = 0;
		var tpl = '';
		tpl += '<tr data-index="%d">' + "\r\n";
		tpl += '	<td><a href="javascript:void(0);" tabindex="-1" class="btn btn-sm btn-danger tc-remove-row">Remove</a></td>' + "\r\n";
		tpl += '	<td>%s</td>' + "\r\n";
		tpl += '	<td><input type="text" name="column[%d][newname]" class="form-control input-sm newname" disabled readonly /></td>' + "\r\n";
		tpl += '	<td><input type="text" name="column[%d][value]" class="form-control input-sm" /></td>' + "\r\n";
		tpl += '</tr>' + "\r\n";
		form.find( 'tr[data-index]' ).each( function() {
			if ( parseInt( jQuery( this ).attr( 'data-index' ) ) > highestRow ) {
				highestRow = parseInt( jQuery( this ).attr( 'data-index' ) );
			}
		});
		var newindex = highestRow + 1;
		var html = sprintf(
			tpl,
			newindex,
			tc_generate_fieldmap_selecthtml( newindex ),
			newindex,
			newindex
		);
		form.find( 'tbody' ).append( html );
		var row = form.find( sprintf( 'tr[data-index="%d"]', newindex ) );
		row.find( 'select' ).on( 'change', function( e ) {
			if ( jQuery( this ).val() !== 'new' ) {
				row.find( 'input.newname' ).prop( 'disabled', true );
				row.find( 'input.newname' ).prop( 'readonly', true );
			}
			else {
				row.find( 'input.newname' ).prop( 'disabled', false );
				row.find( 'input.newname' ).prop( 'readonly', false );
				row.find( 'input.newname' ).focus();
			}
		} );
		row.find( 'select' ).focus();
		row.find( 'a.tc-remove-row' ).on( 'click', function( e ) {
			e.preventDefault();
			row.remove();
		});
	});
}

function tc_generate_fieldmap_selecthtml( index, fieldmap, tabindex ) {
	if ( 'undefined' == typeof( tabindex ) ) {
		tabindex = 0;
	}
	var html = '';
	var isBlank = ( 'undefined' == typeof( fieldmap ) || '' == fieldmap || 'None' == fieldmap || null == fieldmap );
	var isNewField = ( false == isBlank && ( 'undefined' == typeof( _tc_existing_columns[ fieldmap ] ) || 'new' == fieldmap ) );
	html += sprintf( '<select tabindex="%d" name="column[%s][fieldmap]" class="form-control input-sm fieldmap-select" data-ci="%s">' + "\r\n", tabindex, index, index );
	html += sprintf( '	<option value="None">Not Mapped</option>' + "\r\n" );
	html += sprintf( '	<option value="new" %s>New Field Key</option>' + "\r\n", ( isNewField ) ? 'selected' : '' );
	if ( 'string' == typeof( fieldmap ) || null == fieldmap ) {
	for( var key in _tc_existing_columns ) {
	html += sprintf( '	<option value="%s" %s>%s</option>' + "\r\n", key, ( fieldmap == key ) ? 'selected' : '', _tc_existing_columns[ key ] );
	}
	}
	html += sprintf( '</select>' + "\r\n" );
	return html;
}

function tc_init_file_row_controls( button, file, row ) {
	var action = button.attr( 'data-action' );
	button.prop( 'disabled', false );
	button.on( 'click', function() {
		tc_ajax(
			'file_row_action',
			{
				file: file,
				action: action,
			},
			function( data ) {
				row.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				row.find( '.progress>div' ).addClass( 'progress-bar-success' );
				row.find( '.progress>div>.percent' ).html( data );
			},
			function( data ) {
				row.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				row.find( '.progress>div' ).addClass( 'progress-bar-danger' );
				row.find( '.progress>div>.percent' ).html( data );
				tc_notify_error( data, 'Error' );
				console.warn( data );
			},
			function() {
				row.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-success' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
				row.find( '.progress>div' ).removeClass( 'progress-bar-info' );
				row.find( '.progress>div' ).addClass( 'progress-bar-primary' );
				row.find( '.progress>div' ).attr( 'aria-valuenow', 0 );
				row.find( '.progress>div' ).css({
					width: ( 0 ) + '%',
				});
				row.find( '.progress>div>.percent' ).html( ( 0 ) + '%' );
			},
			function( percent ) {
				if ( percent >= 1 ) {
					percent = 1;
				}
				row.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
				row.find( '.progress>div' ).css({
					width: ( percent * 100 ) + '%',
				});
				row.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
			}
		);
	});
}

function tc_init_file_row() {
	var obj = jQuery( this );
	var file = obj.attr( 'data-file' );
	_tc_shown_import_files.push( file );
	var pcell = obj.find( '.row-progress-cell' );
	obj.find( 'button.btn-file-action' ).each( function() {
		tc_init_file_row_controls( jQuery( this ), file, obj );
	});
}

function tc_handle_new_column_request() {
	add_column( tc_generate_random_id(), null, null, {}, null );
}

function add_column( index, fieldmap, key, preview, deval, norefresh ) {
	var i = index;
	if ( 'tag' == fieldmap ) {
		return;
	}
	if ( null == index || 'null' == index ) {
		index = tc_generate_random_id();
	}
	var tdtpl = sprintf( '<td data-column-index="%s">%s</td>', index, '%s' );
	var thtpl = sprintf( '<th data-column-index="%s">%s</th>', index, '%s' );
	var newfieldkey = ( 'undefined' !== typeof( _tc_existing_columns[ fieldmap ] ) || null == fieldmap || 'new' == fieldmap ) ? '' : fieldmap;
	var defaultvalue = ( 'undefined' !== typeof( deval ) && null !== deval ) ? deval : '';
	if ( 'None' == newfieldkey ) {
		newfieldkey = '';
	}
	var needsnewkeyfield = ( newfieldkey.length > 0 );
	var map = {
		1: sprintf( thtpl, ( is_numeric( index ) ? sprintf( '<p class="text-center"><strong>%d</strong></p>', index ) : '<p class="text-center"><strong>NEW</strong></p>' ) ),
		2: sprintf( thtpl, tc_generate_fieldmap_selecthtml( index, fieldmap, tc_get_next_tab_index() ) ),
		3: sprintf( thtpl, sprintf( '<input tabindex="%d" type="text" name="column[%s][newkey]" data-ci="%s" class="form-control input-sm" %s placeholder="New Field Key" value="%s" />', tc_get_next_tab_index(), index, index, ( true !== needsnewkeyfield ) ? 'readonly disabled' : 'required', newfieldkey ) ),
		4: sprintf( thtpl, sprintf( '<input tabindex="%d" type="text" name="column[%s][default]" data-ci="%s" class="form-control input-sm" %s placeholder="Default Value" value="%s" />', tc_get_next_tab_index(), index, index, is_numeric( index ) ? '' : 'required', defaultvalue ) ),
		5: sprintf( thtpl, ( ! is_numeric( i ) ) ? '<button class="no-action btn btn-danger btn-block btn-xs remove-column-button"><span class="fa fa-trash"></span>Remove</button>' : '&nbsp;' ),
		6: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[0] ) ),
		7: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[1] ) ),
		8: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[2] ) ),
		9: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[3] ) ),
		10: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[4] ) ),
		11: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[5] ) ),
		12: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[6] ) ),
		13: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[7] ) ),
		14: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[8] ) ),
		15: sprintf( tdtpl, tc_filter_preview( ( 'object' !== typeof( preview ) ) ? null : preview[9] ) ),
	}
	var rows = jQuery( sprintf( '#field-map-form *[data-column-index="%s"]', index ) );
	rc = 0;
	jQuery( '#field-map-form tr' ).each( function() {
		var obj = jQuery( this );
		//obj.find( '[data-clipboard-text]' ).each( tc_init_copy_clipboard_text );
		var existing = ( obj.find( sprintf( '[data-column-index="%s"]', index ) ).length > 0 );
		if ( false == existing && 'string' == typeof( map[ rc ] ) ) {
			obj.append( map[rc] );
			obj.find( sprintf( '[data-column-index="%s"] .remove-column-button', index ) ).on( 'click', function() {
				remove_column_by_index( index );
			});
			obj.find( sprintf( '[data-column-index="%s"] .fieldmap-select', index ) ).on( 'change', tc_handle_select_change );
			obj.find( sprintf( 'input[name="column[%s][default]"]', index ) ).on( 'change', tc_handle_select_change );
			//setTimeout( function() {
			//	obj.find( sprintf( '[data-column-index="%s"] .fieldmap-select', index ) ).each( function() {
			//		jQuery( this ).change();
			//	});
			//}, 100 );
		}
		//obj.find( 'select' ).each( function() {
		//	var chsn = jQuery( this );
		//	var row = chsn.parent();
		//	if ( 1 == chsn.attr( 'data-can-add' ) ) {
		//		chsn.chosen({ width: '100%', no_results_text: 'Press Enter to add new entry:' });
		//		var chosen = chsn.data('chosen');
		//		row.find('li.search-field input').on('keyup', function(e) {
		//			if (e.which == 13 && chosen.dropdown.find('li.no-results').length > 0) {
		//				var option = jQuery("<option>").val(this.value).text(this.value);
		//				chsn.prepend(option);
		//				chsn.find(option).prop('selected', true);
		//				chsn.trigger("chosen:updated");
		//		    }
		//		});
		//	}
		//	else {
		//		chsn.chosen({ width: '100%', search_contains: false });
		//	}
		//});
		rc ++;
	});
}

function tc_handle_select_change( e, autofocus ) {
	var obj = jQuery( this );
	var index = obj.attr( 'data-ci' );
	var tdtpl = sprintf( '<td data-column-index="%s">%s</td>', index, '%s' );
	var fm = jQuery( sprintf( '[name="column[%s][fieldmap]"]', index ) );
	if ( 'new' == fm.val() ) {
		jQuery( sprintf( '[name="column[%s][newkey]"]', index ) ).prop( 'disabled', false );
		jQuery( sprintf( '[name="column[%s][newkey]"]', index ) ).prop( 'readonly', false );
	}
	else {
		jQuery( sprintf( '[name="column[%s][newkey]"]', index ) ).prop( 'disabled', true );
		jQuery( sprintf( '[name="column[%s][newkey]"]', index ) ).prop( 'readonly', true );
		jQuery( sprintf( '[name="column[%s][newkey]"]', index ) ).val( '' );
	}
	tc_ajax(
		'get_preview_for_column',
		{
			index: index,
			fieldmap: fm.val(),
			newkey: jQuery( sprintf( '[name="column[%s][newkey]"]', index ) ).val(),
			default: jQuery( sprintf( '[name="column[%s][default]"]', index ) ).val(),
			file: jQuery( 'input[name="file"]' ).val(),
			delimiter: jQuery( 'input[name="delimiter"]' ).val(),
			encapsulation: jQuery( 'input[name="encapsulation"]' ).val(),
			headerrow: jQuery( 'input[name="headerrow"]' ).val(),
		},
		function( data, redirect ) {
			var map = {
				6: sprintf( tdtpl, tc_filter_preview( data[0] ) ),
				7: sprintf( tdtpl, tc_filter_preview( data[1] ) ),
				8: sprintf( tdtpl, tc_filter_preview( data[2] ) ),
				9: sprintf( tdtpl, tc_filter_preview( data[3] ) ),
				10: sprintf( tdtpl, tc_filter_preview( data[4] ) ),
				11: sprintf( tdtpl, tc_filter_preview( data[5] ) ),
				12: sprintf( tdtpl, tc_filter_preview( data[6] ) ),
				13: sprintf( tdtpl, tc_filter_preview( data[7] ) ),
				14: sprintf( tdtpl, tc_filter_preview( data[8] ) ),
				15: sprintf( tdtpl, tc_filter_preview( data[9] ) ),
			}
			rc = 0;
			jQuery( '#field-map-form tr' ).each( function() {
				var o = jQuery( this );
				var row = o.find( sprintf( '[data-column-index="%s"]', index ) );
				if ( 'string' == typeof( map[ rc ] ) ) {
					row.html( map[ rc ] );
				}
				rc ++;
			});
		},
		function( data, redirect ) {
			tc_notify_error( data, 'Error' );
			console.warn( data );
		},
		function() {},
		function( percent ) {
			//console.log( percent );
		}
	);
}

function tc_filter_preview( input ) {
	if ( 'string' !== typeof( input ) || input.length == 0 || '' == input || null == input ) {
		return '<code>NULL</code>';
	}
	if ( input === true || input.toLowerCase() == 'true' ) {
		return '<code>TRUE</code>';
	}
	if ( input === false || input.toLowerCase() == 'false' ) {
		return '<code>FALSE</code>';
	}
	return sprintf( '<small data-clipboard-text="%s"><i>%s</i></small>', input, input );
}

function tc_init_copy_clipboard_text() {
	// clips
	// data-clipboard-text
	var obj = jQuery( this );
	var c = new Clipboard( this );
	c.on( 'success', function( e ) {
		tc_notify_success( sprintf( 'Copied text:<br />"%s"</br />to clipboard.', e.text ) );
		e.clearSelection();
	});
	c.on( 'error', function( e ) {
		tc_notify_error( sprintf( 'Failed to copy text to clipboard' ) );
	});
}

function remove_column_by_index( index ) {
	jQuery( sprintf( '[data-column-index="%s"]', index ) ).remove();
}

function tc_handle_ajax_form_submit( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	tc_ajax(
		obj.attr( 'action' ),
		obj.serialize(),
		function( data, redirect ) {
			if ( true !== redirect ) {
				obj.find( 'input[type="submit"]' ).prop( 'disabled', false );
				obj.find( 'input[type="submit"]' ).prop( 'readonly', false );
				obj.find( 'button' ).prop( 'disabled', false );
				obj.find( 'button' ).prop( 'readonly', false );
				obj.find( 'a' ).prop( 'disabled', false );
				obj.find( 'a' ).prop( 'readonly', false );
				obj.find( 'select' ).trigger( 'chosen:updated' );
			}
			obj.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			obj.find( '.progress>div' ).addClass( 'progress-bar-success' );
			var callback = window[obj.attr( 'data-callback' )];
			if ( 'function' == typeof( callback ) ) {
				obj.find( '.progress>div>.percent' ).html( 'Success' );
				callback( data );
			}
			else {
				obj.find( '.progress>div>.percent' ).html( data );
			}
		},
		function( data ) {
			obj.find( 'input[type="submit"]' ).prop( 'disabled', false );
			obj.find( 'input[type="submit"]' ).prop( 'readonly', false );
			obj.find( 'button' ).prop( 'disabled', false );
			obj.find( 'button' ).prop( 'readonly', false );
			obj.find( 'a' ).prop( 'disabled', false );
			obj.find( 'a' ).prop( 'readonly', false );
			obj.find( 'select' ).trigger( 'chosen:updated' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			obj.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			obj.find( '.progress>div' ).addClass( 'progress-bar-danger' );
			obj.find( '.progress>div>.percent' ).html( data );
			tc_notify_error( data, 'Error' );
		},
		function() {
			obj.find( 'input[type="submit"]' ).prop( 'disabled', true );
			obj.find( 'input[type="submit"]' ).prop( 'readonly', true );
			obj.find( 'button' ).prop( 'disabled', true );
			obj.find( 'button' ).prop( 'readonly', true );
			obj.find( 'a' ).prop( 'disabled', true );
			obj.find( 'a' ).prop( 'readonly', true );
			obj.find( 'select' ).trigger( 'chosen:updated' );
			obj.find( '.ajax-response' ).html( '<div class="progress"><div class="progress-bar progress-bar-primary " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
			var callback = window[obj.attr( 'data-on-progress' )];
			if ( 'function' == typeof( callback ) ) {
				callback( obj );
			}
		},
		function( percent ) {
			if ( percent >= 1 ) {
				percent = 1;
			}
			obj.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
			obj.find( '.progress>div' ).css({
				width: ( percent * 100 ) + '%',
			});
			obj.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		}
	);
}

function tc_handle_logout_request( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	var parent = obj.parent();
	tc_ajax(
		'logout',
		{},
		function( data ) {
			parent.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			parent.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			parent.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			parent.find( '.progress>div' ).addClass( 'progress-bar-success' );
			parent.find( '.progress>div>.percent' ).html( data );
		},
		function( data ) {
			parent.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			parent.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			parent.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			parent.find( '.progress>div' ).addClass( 'progress-bar-danger' );
			parent.find( '.progress>div>.percent' ).html( data );
			tc_notify_error( data );
		},
		function() {
			parent.html( '<div class="progress logout-progress"><div class="progress-bar progress-bar-primary " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
		},
		function( percent ) {
			if ( percent >= 1 ) {
				percent = 1;
			}
			percent = 1 * 100;
			parent.find( '.progress>div' ).attr( 'aria-valuenow', percent  );
			parent.find( '.progress>div' ).css({
				width: ( percent  ) + '%',
			});
			parent.find( '.progress>div>.percent' ).html( (percent  ) + '%' );
		}
	);
}

function tc_handle_reset_cache_request( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	var parent = obj.parent();
	tc_ajax(
		'reset-progress',
		{},
		function( data ) {
			parent.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			parent.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			parent.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			parent.find( '.progress>div' ).addClass( 'progress-bar-success' );
			parent.find( '.progress>div>.percent' ).html( data );
			tc_notify_success( data );
			setTimeout( function() {
				parent.html( '<a href="javascript:void(0);" class="reset-cache"><span class="fa fa-refresh"></span> Clear Cache</a>' );
				parent.find( '.reset-cache' ).on( 'click', tc_handle_reset_cache_request );
			}, 3000 );
		},
		function( data ) {
			parent.find( '.progress>div' ).removeClass( 'progress-bar-primary' );
			parent.find( '.progress>div' ).removeClass( 'progress-bar-success' );
			parent.find( '.progress>div' ).removeClass( 'progress-bar-danger' );
			parent.find( '.progress>div' ).addClass( 'progress-bar-danger' );
			parent.find( '.progress>div>.percent' ).html( data );
			tc_notify_error( data );
			setTimeout( function() {
				parent.html( '<a href="javascript:void(0);" class="reset-cache"><span class="fa fa-refresh"></span> Clear Cache</a>' );
				parent.find( '.reset-cache' ).on( 'click', tc_handle_reset_cache_request );
			}, 10000 );
		},
		function() {
			parent.html( '<div class="progress logout-progress"><div class="progress-bar progress-bar-primary " role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%"><span class="percent">0%</span></div></div>' );
		},
		function( percent ) {
			if ( percent >= 1 ) {
				percent = 1;
			}
			percent = 1 * 100;
			parent.find( '.progress>div' ).attr( 'aria-valuenow', percent  );
			parent.find( '.progress>div' ).css({
				width: ( percent  ) + '%',
			});
			parent.find( '.progress>div>.percent' ).html( (percent  ) + '%' );
		}
	);
}

function tc_run_when_loaded( f, interval, passthrough ) {
	if ( 'number' !== typeof( interval ) ) {
		intverval = 100;
	}
	if ( 'function' == typeof( window[ f ] ) ) {
		window[ f ]( passthrough );
		return true;
	}
	else {
		setTimeout( function() {
			run_when_loaded( f, interval, passthrough );
		}, interval );
	}
}

function tc_ajax( action, data, success, error, pending, progress ) {
	if ( 'undefined' == typeof( action ) ) {
		action = 'none';
	}
	if ( 'undefined' == typeof( data ) ) {
		data = {};
	}
	if ( 'undefined' == typeof( success ) ) {
		success = function(){};
	}
	if ( 'undefined' == typeof( error ) ) {
		error = function(){};
	}
	if ( 'undefined' == typeof( pending ) ) {
		pending = function(){};
	}
	if ( 'undefined' == typeof( progress ) ) {
		progress = function( decimal ){};
	}
	jQuery.ajax({
		async: true,
		beforeSend: function() {
			starttime = parseInt( moment().format( 'x' ) );
			pending();
		},
		cache: false,
		crossDomain: false,
		data: {
			'ajax-action': action,
			data: data,
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			endtime = parseInt( moment().format( 'x' ) );
			error( sprintf( 'AJAX Error: %s', errorThrown ) );
		},
		method: 'POST',
		success: function( redata ) {
			endtime = parseInt( moment().format( 'x' ) );
			if ( 'object' !== typeof( redata ) ) {
				error( 'Invalid AJAX Feedback' );
			}
			if ( 'undefined' == typeof( redata.status ) ) {
				error( 'Invalid AJAX Feedback' );
			}
			if ( false == redata.status ) {
				if ( 'undefined' !== typeof( redata.redirect ) && false !== redata.redirect ) {
					setTimeout( function() {
						if ( 'string' == typeof( redata.data ) ) {
							error( redata.data );
						}
						else {
							error( 'An unknown error has occured with your request' );
							console.error( redata.data );
						}
					}, 300 );
					setTimeout( function() {
						window.location.href = redata.redirect;
					}, 1500 );
				}
				else {
					if ( 'Unauthorized' == redata.data ) {
						window.location.href = '/login/';
					}
					if ( 'string' == typeof( redata.data ) ) {
						error( redata.data );
					}
					else {
						error( 'An unknown error has occured with your request' );
						console.error( redata.data );
					}
				}
			}
			if ( true == redata.status ) {
				if ( 'Unauthorized' == redata.data ) {
					window.location.href = '/login/';
				}
				if ( 'undefined' !== typeof( redata.redirect ) && false !== redata.redirect ) {
					setTimeout( function() {
						success( redata.data, true );
					}, 300 );
					setTimeout( function() {
						window.location.href = redata.redirect;
					}, 1500 );
				}
				else {
					success( redata.data );
				}
			}
		},
		xhr: function() {
			var xhr = new window.XMLHttpRequest();
			var completeDouble = 0;
			xhr.upload.addEventListener("progress", function(evt) {
				if (evt.lengthComputable) {
					var decComplete = evt.loaded / evt.total;
					completeDouble = completeDouble + decComplete
					progress( completeDouble );
				}
			}, false );
			xhr.addEventListener("progress", function(evt) {
				if (evt.lengthComputable) {
					var decComplete = evt.loaded / evt.total;
					completeDouble = completeDouble + decComplete
					progress( completeDouble );
				}
			}, false );
			return xhr;
		},
		url: '/ajax/',
	});
}

function tc_init_uploader( divid, add, progress, fail, success, afterstop ) {
	var formobj = jQuery( sprintf( '#%s', divid ) );
	if ( 'undefined' == typeof( formobj.attr( 'action' ) ) || '' == formobj.attr( 'action' ) ) {
		formobj.attr( 'action', '/ajax/' );
	}
	if ( 0 == formobj.length ) {
		tc_notify_error( sprintf( 'No object with id "#%s" found in DOM', divid ), 'Error' );
		console.warn( sprintf );
		return false;
	}
	if ( 'function' !== typeof( add ) ) {
		add = function( id, filename, filesize, stopfunction, afterstop ) {
			console.log( sprintf( 'Added File "%s" (%s bytes) with ID %s', filename, filesize, id ) );
		}
	}
	if ( 'function' !== typeof( fail ) ) {
		fail = function( id, filename, filesize, msg ) {
			tc_notify_error( sprintf( 'File "%s" with ID %s failed with message: %s', filename, id, msg ), 'Error' );
			console.warn( sprintf );
		}
	}
	if ( 'function' !== typeof( success ) ) {
		success = function( id, filename, filesize, msg ) {
			console.log( sprintf( 'File "%s" with ID %s completed successfully with message: %s', filename, id, msg ) );
		}
	}
	if ( 'function' !== typeof( progress ) ) {
		progress = function( id, filename, filesize, percent ) {
			console.log( sprintf( 'File "%s" is %d%s uploaded', filename, percent, '%' ) );
		}
	}
	var xhrs = {};
	formobj.find( 'a.upload-button' ).on( 'click', function( e ) {
		e.preventDefault();
		formobj.find( 'input[type="file"]' ).click();
	});
	formobj.find( 'button.upload-button' ).on( 'click', function( e ) {
		e.preventDefault();
		formobj.find( 'input[type="file"]' ).click();
	});
	formobj.fileupload({
		url: formobj.attr( 'action' ),
		type: 'POST',
		add: function( e, data ) {
			var id = tc_generate_random_id();
			data.id = id;
			var od = data;
			add( id, data.files[0].name, data.files[0].size, function( e ) {
				if ( 'undefined' !== typeof( xhrs[id] ) ) {
					xhrs[id].abort();
					if ( 'function' == typeof( afterstop ) ) {
						afterstop( id );
					}
				}
			} );
			xhrs[id] = data.submit();
			xhrs[id].complete( function( result, textStatus, data ) {
				if ( 'undefined' == typeof( result.responseJSON ) ) {
					var status = false;
					var msg = 'Unknown Feedback';
				}
				else {
					var status = result.responseJSON.status;
					if ( 'object' == typeof( result.responseJSON.data ) ) {
						var msg = ( true === status ) ? 'Upload Successful' : 'Upload Failed';
					}
					else {
						var msg = result.responseJSON.data;
					}
				}
				if ( false === status ) {
					fail( id, od.files[0].name, od.files[0].size, msg );
				}
				else {
					success( id, od.files[0].name, od.files[0].size, msg );
				}
			});
		},
		progress: function( e, data ) {
			var perc = parseInt( data.loaded / data.total * 100, 10 );
			progress( data.id, data.files[0].name, data.files[0].size, perc );
		},
		fail: function( e, data ) {
			fail( data.id, data.files[0].name, data.files[0].size, data.errorThrown );
		}
	});
}

function tc_generate_random_id() {
	return sprintf( 'tmp_%s', moment().format( 'x' ) );
}

if ( 'function' !== typeof( can_loop ) ) {
	function can_loop( object ) {
		return ( 'object' == typeof( object ) && null !== object );
	}
}

if ( 'function' !== typeof( is_numeric ) ) {
	function is_numeric( val ) {
		return ( 'number' == typeof( val ) || parseFloat( val ) == val );
	}
}

function tc_notification( message, type, title ) {
	switch( type ) {
		case 'error':
			var ico = 'fa fa-exclamation-triangle';
			break;

		case 'success':
			var ico = 'fa fa-check-square ';
			break;

		default:
			var ico = 'fa fa-info-circle';
			break;
	}
	var opts = {
		title: title,
		text: message,
		type: type,
		desktop: {
			desktop: true,
		},
		buttons: {
			closer: true,
			sticker: true,
		},
		addclass: 'custom',
		opacity: 0.8,
		nonblock: {
			nonblock: true,
		},
		icon: ico,
	};
	try { var notice = new PNotify( opts ) } catch ( err ) { console.error( err ) };
	if ( 'object' == typeof( notice ) ) {
		notice.get().click( function() {
			notice.remove();
		});
	}
}

function tc_notify_error( message, title ) {
	console.warn( message );
	tc_notification( message, 'error', title );
}

function tc_notify_success( message, title ) {
	tc_notification( message, 'success', title );
}

function tc_notify_alert( message, title ) {
	tc_notification( message, 'info', title );
}

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function setCookie(name,value,days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    setCookie(name,"",-1);
}

function getJsTimestamp() {
	return ( Date.now() ) / 1000;
}

function isRunningStandalone() {
    return tcd.webapp;
}