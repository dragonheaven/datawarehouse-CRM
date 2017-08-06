jQuery( function() {
	jQuery( '#advanced-search-link' ).on( 'click', tc_handle_show_advanced_search );
	jQuery( 'form[action="get_lead_search_results"]' ).each( tc_init_export_job_form );
	tc_init_leadpanel_when_ready();
	jQuery( '.lead-panel select' ).chosen({width:'100%'});
	setTimeout( function() {
		jQuery( 'form[action="get_lead_search_results"]' ).each( tc_init_search_results );
	},500);
	jQuery( 'html' ).on( 'click', tc_close_open_context_menu );
});

function tc_handle_show_advanced_search( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	if ( jQuery( '#advanced-search' ).is( ':visible' ) ) {
		jQuery( '#advanced-search' ).css({display:'none'});
		jQuery( '#advanced-search-link' ).text( 'Advanced' );
	}
	else {
		jQuery( '#advanced-search' ).css({display:'block'});
		jQuery( '#advanced-search-link' ).text( 'Hide' );
	}
}

function tc_init_search_results() {
	var obj = jQuery( this );
	obj.find( 'input[name="orderby"]' ).val( jQuery( '#orderby' ).val() );
	obj.find( 'input[name="order"]' ).val( jQuery( '#order' ).val() );
	obj.submit();
	jQuery( '#orderby' ).on( 'change', function( e ) {
		obj.find( 'input[name="orderby"]' ).val( jQuery( this ).val() );
	} );
	jQuery( '#order' ).on( 'change', function( e ) {
		obj.find( 'input[name="order"]' ).val( jQuery( this ).val() );
	} );
}

function tc_init_lead_panel() {
	var obj = jQuery( this );
	if ( tc_has_existing_columns() ) {
		var html = '';
		for ( var val in tcd.sortablecolumns ) {
			var display = tcd.sortablecolumns[ val ];
			html += sprintf( '<option value="%s" %s>%s</option>', val, ( val == 'id' ) ? 'selected' : '', display );
		}
		obj.find( '#orderby' ).html( html );
		obj.find( '#orderby' ).trigger( 'chosen:updated' );
		obj.find( '#re-order' ).on( 'click', function( e ) {
			jQuery( 'form[action="get_lead_search_results"]' ).submit();
		});
	}
}

function tc_has_existing_columns() {
	return ( 'object' == typeof( tcd.sortablecolumns ) && 'undefined' !== typeof( tcd.sortablecolumns['id'] ) );
}

function tc_init_leadpanel_when_ready() {
	if ( false == tc_has_existing_columns() ) {
		setTimeout( tc_init_leadpanel_when_ready, 500 );
	}
	else {
		jQuery( '.lead-panel' ).each( tc_init_lead_panel );
	}
}

function tc_generate_lead_cards_from_lead_search_results( d ) {
	var html = '';
	for (var i = 0; i < d.leads.length; i++) {
		var l = d.leads[i];
		html += sprintf(
			'<tr data-lead-id="%d"><td>&nbsp;</td><td>%d</td><td><img src="%s" class="inline-lead-image" /> %s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
			l.id,
			l.id,
			l.profile,
			( 'undefined' !== typeof( l.name ) && null !== l.name && l.name.length > 0 ) ? l.name : '',
			( 'undefined' !== typeof( l.email ) && null !== l.email && l.email.length > 0 ) ? l.email : '',
			( 'undefined' !== typeof( l.phone ) && null !== l.phone && l.phone.length > 0 ) ? l.phone : '',
			( 'undefined' !== typeof( l.country ) && null !== l.country && l.country.length > 0 ) ? l.country : '',
			( 'undefined' !== typeof( l.source ) && null !== l.source && l.source.length > 0 ) ? l.source : '',
			( 'undefined' !== typeof( l.importtime ) && null !== l.importtime && l.importtime.length > 0 ) ? l.importtime : ''
		);
	}
	jQuery( '.lead-panel' ).find( 'tbody' ).html( html );
	jQuery( 'tr[data-lead-id]' ).on( 'click', tc_open_lead_card );
	jQuery( 'tr[data-lead-id]' ).on( 'mousedown', tc_open_lead_card_tab );
	jQuery( 'tr[data-lead-id]' ).on( 'contextmenu', tc_open_lead_context_menu );
	jQuery( '#totalLeads' ).text( numberWithCommas( d.total ) );
	var frow = ( d.page * 15 ) + 1;
	var lrow = frow + 14;
	jQuery( '.pagin-value' ).text( sprintf( 'Rows %s - %s | Page %s of %s ', numberWithCommas( frow ), numberWithCommas( lrow ), numberWithCommas( d.page + 1 ), numberWithCommas( d.totalpages ) ) );
	jQuery( '[data-page-id]' ).off( 'click' );
	jQuery( '.total-page-count' ).text( numberWithCommas( d.totalpages ) );
	jQuery( '#jump' ).attr( 'max', d.totalpages );
	jQuery( '#jump' ).val( d.page + 1 );
	if ( d.page > 0 ) {
		var btn = jQuery( '[data-direction="prev"]' );
		btn.attr( 'data-page-id', d.page - 1 );
		btn.on( 'click', tc_handle_lead_change_page );
		btn.prop( 'disabled', false );
		btn.removeClass( 'disabled' );
	}
	else {
		var btn = jQuery( '[data-direction="prev"]' );
		btn.attr( 'data-page-id', 0 );
		btn.prop( 'disabled', true );
		btn.addClass( 'disabled' );
	}
	if ( d.page < d.totalpages ) {
		var btn = jQuery( '[data-direction="next"]' );
		btn.attr( 'data-page-id', d.page + 1 );
		btn.on( 'click', tc_handle_lead_change_page );
		btn.prop( 'disabled', false );
		btn.removeClass( 'disabled' );
	}
	else {
		var btn = jQuery( '[data-direction="next"]' );
		btn.attr( 'data-page-id', d.totalpages - 1 );
		btn.prop( 'disabled', true );
		btn.addClass( 'disabled' );
	}
	var fbtn = jQuery( '[data-direction="first"]' );
	fbtn.attr( 'data-page-id', 0 );
	if ( d.page > 0 ) {
		fbtn.on( 'click', tc_handle_lead_change_page );
	}
	var lbtn = jQuery( '[data-direction="last"]' );
	lbtn.attr( 'data-page-id', d.totalpages - 1 );
	if ( d.page < d.totalpages ) {
		lbtn.on( 'click', tc_handle_lead_change_page );
	}
	jQuery( '#jump' ).on( 'keyup', tc_handle_jumper_page_change );
	jQuery( '#jump' ).on( 'change', tc_handle_jumper_page_change );
	console.log( d );
}

function tc_handle_jumper_page_change( e ) {
	var obj = jQuery( this );
	jQuery( '#jump-action' ).attr( 'data-page-id', obj.val() - 1 );
	jQuery( '#jump-action' ).off( 'click' );
	jQuery( '#jump-action' ).on( 'click', tc_handle_lead_change_page );
}

function tc_handle_lead_change_page( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	var p = obj.attr( 'data-page-id' );
	var form = jQuery( 'form[action="get_lead_search_results"]' );
	form.find( '[name="page"]' ).val( p );
	form.submit();
}

function tc_open_lead_card( e ) {
	var obj = jQuery( this );
	var card = obj.attr( 'data-lead-id' );
	var url = sprintf( '/leads/view/%d', card );
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
		if( e.which == 2 ) {
			var win = window.open( url, '_blank' );
			win.blur();
			window.focus();
		}
		else {
			window.location.href = url;
		}
	}
	else {
		window.location.href = url;
	}
}

function tc_open_lead_card_tab( e ) {
	var obj = jQuery( this );
	var card = obj.attr( 'data-lead-id' );
	var url = sprintf( '/leads/view/%d', card );
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
		if( e.which == 2 ) {
			var win = window.open( url, '_blank' );
			win.blur();
			window.focus();
		}
	}
}

function tc_open_lead_context_menu( e ) {
	if ( 'object' == typeof( e ) ) {
		e.preventDefault();
	}
	var obj = jQuery( this );
	var card = obj.attr( 'data-lead-id' );
	var url = sprintf( '/leads/view/%d', card );
	jQuery( '.lead-context-menu' ).menu( 'destroy' );
	jQuery( '.lead-context-menu' ).remove();
	var id = tc_generate_random_id();
	var menuhtml = sprintf(
		'<ul id="%s" class="lead-context-menu" style="width:150px;"><li><div data-open-in="new-tab">Open in New Tab</div></li><li><div data-open-in="new-window">Open in New Window</div></li></ul>',
		id
	);
	jQuery( 'body' ).append( menuhtml );
	var m = jQuery( sprintf( '#%s', id ) );
	var mouseX = e.pageX;
  	var mouseY = e.pageY;
	m.menu({
		position: {
			of: e,
		},
	});
	var mn = jQuery( sprintf( '#%s', id ) );
	mn.css({
		position: 'absolute',
		left: mouseX,
		top: mouseY
	});
	mn.find( '[data-open-in]' ).on( 'click', function( e ) {
		var b = jQuery( this );
		var o = b.attr( 'data-open-in' );
		switch ( o ) {
			case 'new-tab':
				var win = window.open( url, '_blank' );
				win.focus();
				break;

			case 'new-window':
				var win = window.open( sprintf( '%s?card=true', url ), '_blank', sprintf( 'height=%d,width=%d,menubar=yes,resizeable=yes,scrollbars=yes,status=yes', jQuery( window ).height(), jQuery( window ).width() ) );
				win.focus();
				break;
		}
	});
}

function tc_close_open_context_menu( e ) {
	jQuery( '.lead-context-menu' ).menu( 'destroy' );
	jQuery( '.lead-context-menu' ).remove();
}

function tc_generate_lead_list_loader( form ) {
	jQuery( '.lead-panel' ).find( 'tbody' ).html( '<tr><td rowspan="10" colspan="8" class="loading-container"><main><div class="dank-ass-loader"><div class="row"><div class="arrow up outer outer-18"></div><div class="arrow down outer outer-17"></div><div class="arrow up outer outer-16"></div><div class="arrow down outer outer-15"></div><div class="arrow up outer outer-14"></div></div><div class="row"><div class="arrow up outer outer-1"></div><div class="arrow down outer outer-2"></div><div class="arrow up inner inner-6"></div><div class="arrow down inner inner-5"></div><div class="arrow up inner inner-4"></div><div class="arrow down outer outer-13"></div><div class="arrow up outer outer-12"></div></div><div class="row"><div class="arrow down outer outer-3"></div><div class="arrow up outer outer-4"></div><div class="arrow down inner inner-1"></div><div class="arrow up inner inner-2"></div><div class="arrow down inner inner-3"></div><div class="arrow up outer outer-11"></div><div class="arrow down outer outer-10"></div></div><div class="row"><div class="arrow down outer outer-5"></div><div class="arrow up outer outer-6"></div><div class="arrow down outer outer-7"></div><div class="arrow up outer outer-8"></div><div class="arrow down outer outer-9"></div></div></div></main></td></tr>' );
}