jQuery( function() {
	tc_init_all_home_page_static_info();
});

function tc_init_all_home_page_static_info() {
	if ( 'undefined' !== typeof( jQuery( '#list-leads-by-country' ) ) ) {
		//tc_ajax(
		//	'get-list-leads-by-country',
		//	{},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-country' );
		//		obj.find( 'tr.loader' ).remove();
		//		obj.find( 'tr' ).remove();
		//		obj.append( tc_make_html_rows_for_leads_by_country( data ) );
		//	},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-country' );
		//		tc_notify_error( data );
		//	},
		//	function() {
		//		var obj = jQuery( '#list-leads-by-country' );
		//	},
		//	function( percent ) {
		//		var obj = jQuery( '#list-leads-by-country' );
		//		if ( percent >= 1 ) {
		//			percent = 1;
		//		}
		//		obj.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
		//		obj.find( '.progress>div' ).css({
		//			width: ( percent * 100 ) + '%',
		//		});
		//		obj.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		//	}
		//);
	}
	if ( 'undefined' !== typeof( jQuery( '#list-leads-by-language' ) ) ) {
		//tc_ajax(
		//	'get-list-leads-by-language',
		//	{},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-language' );
		//		obj.find( 'tr.loader' ).remove();
		//		obj.find( 'tr' ).remove();
		//		obj.append( tc_make_html_rows_for_leads_by_info( data ) );
		//	},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-language' );
		//		tc_notify_error( data );
		//	},
		//	function() {
		//		var obj = jQuery( '#list-leads-by-language' );
		//	},
		//	function( percent ) {
		//		var obj = jQuery( '#list-leads-by-language' );
		//		if ( percent >= 1 ) {
		//			percent = 1;
		//		}
		//		obj.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
		//		obj.find( '.progress>div' ).css({
		//			width: ( percent * 100 ) + '%',
		//		});
		//		obj.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		//	}
		//);
	}
	if ( 'undefined' !== typeof( jQuery( '#list-leads-by-source' ) ) ) {
		//tc_ajax(
		//	'get-list-leads-by-source',
		//	{},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-source' );
		//		obj.find( 'tr.loader' ).remove();
		//		obj.find( 'tr' ).remove();
		//		obj.append( tc_make_html_rows_for_leads_by_info( data ) );
		//	},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-source' );
		//		tc_notify_error( data );
		//	},
		//	function() {
		//		var obj = jQuery( '#list-leads-by-source' );
		//	},
		//	function( percent ) {
		//		var obj = jQuery( '#list-leads-by-source' );
		//		if ( percent >= 1 ) {
		//			percent = 1;
		//		}
		//		obj.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
		//		obj.find( '.progress>div' ).css({
		//			width: ( percent * 100 ) + '%',
		//		});
		//		obj.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		//	}
		//);
	}
	if ( 'undefined' !== typeof( jQuery( '#list-leads-by-tag' ) ) ) {
		//tc_ajax(
		//	'get-list-leads-by-tag',
		//	{},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-tag' );
		//		obj.find( 'tr.loader' ).remove();
		//		obj.find( 'tr' ).remove();
		//		obj.append( tc_make_html_rows_for_leads_by_info( data ) );
		//	},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-tag' );
		//		tc_notify_error( data );
		//	},
		//	function() {
		//		var obj = jQuery( '#list-leads-by-tag' );
		//	},
		//	function( percent ) {
		//		var obj = jQuery( '#list-leads-by-tag' );
		//		if ( percent >= 1 ) {
		//			percent = 1;
		//		}
		//		obj.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
		//		obj.find( '.progress>div' ).css({
		//			width: ( percent * 100 ) + '%',
		//		});
		//		obj.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		//	}
		//);
	}
	if ( 'undefined' !== typeof( jQuery( '#list-leads-by-meta' ) ) ) {
		//tc_ajax(
		//	'get-list-leads-by-meta',
		//	{},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-meta' );
		//		obj.find( 'tr.loader' ).remove();
		//		obj.find( 'tr' ).remove();
		//		obj.append( tc_make_html_rows_for_leads_by_info( data ) );
		//	},
		//	function( data ) {
		//		var obj = jQuery( '#list-leads-by-meta' );
		//		tc_notify_error( data );
		//	},
		//	function() {
		//		var obj = jQuery( '#list-leads-by-meta' );
		//	},
		//	function( percent ) {
		//		var obj = jQuery( '#list-leads-by-meta' );
		//		if ( percent >= 1 ) {
		//			percent = 1;
		//		}
		//		obj.find( '.progress>div' ).attr( 'aria-valuenow', percent * 100 );
		//		obj.find( '.progress>div' ).css({
		//			width: ( percent * 100 ) + '%',
		//		});
		//		obj.find( '.progress>div>.percent' ).html( (percent * 100 ) + '%' );
		//	}
		//);
	}
}

function tc_make_html_rows_for_leads_by_country( data ) {
	var html = '';
	for (var i = 0; i < data.length; i++) {
		html += '<tr>';
		html += sprintf( '<td width="50" class="text-center"><span class="flag-icon flag-icon-%s"></span></td>', data[i].smalliso );
		html += sprintf( '<td>%s</td>', data[i].name );
		html += sprintf( '<td>%s</td>', numberWithCommas( data[i].value ) );
		html += '</tr>';
	}
	return html;
}

function tc_make_html_rows_for_leads_by_info( data ) {
	var html = '';
	for (var i = 0; i < data.length; i++) {
		html += '<tr>';
		html += sprintf( '<td>%s</td>', data[i].name );
		html += sprintf( '<td>%s</td>', numberWithCommas( data[i].value ) );
		html += '</tr>';
	}
	return html;
}