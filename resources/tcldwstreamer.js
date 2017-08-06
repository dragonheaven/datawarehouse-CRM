/**
 * Streamer Function for Datawarehouse Streamer
 * Usage:
 * new tcldwstreamer( {
 * 	host: null,									// the ratestream host
 * 	update: function( data ) {},				// choose what to do with the updates from the data streamer
 * 	defaultUpdateHandler: function( key, data ) {},	// chose what to do for updates which do not have a specific handler
 * 	connect: function() {},						// action to perform once the streamer is connected
 * 	reconnect: function() {},					// action to perform once the streamer reconnects
 * 	error: function( msg ) {},					// action to perform when there is an error with the streamer
 * 	rejection: function( err ) {},				// action to perform when the streamer rejects the connection
 * 	transports: []								// an array of the transports to use with socket.io to connect to streamer
 * } )
 */

var tcldwstreamer = function( opts ) {
	if ( 'object' !== typeof( opts ) ) {
		opts = {};
	}
	if ( 'object' !== typeof( opts ) ) {
		opts = {};
	}
	var defaults = {
		update: function( data ) {},
		defaultUpdateHandler: function( key, data ) {},
		connect: function() {},
		reconnect: function() {},
		error: function( err ) {
			console.warn( err );
		},
		rejection: function( err ) {
			console.warn( err );
		},
		transports: [
			'websocket',
			'polling'
		],
		host: '',
		items: [ 'time' ],
	};
	var socket;
	var handlers = {};
	for ( key in defaults ) {
		if ( typeof( defaults[ key ] ) !== typeof( opts[ key ] ) ) {
			opts[ key ] = defaults[ key ];
		}
	}
	var init = function() {
		if ( '' !== opts.host ) {
			socket = io( opts.host, {
				transports: opts.transports,
			} );
		}
		else {
			socket = io( '/socket.io/', {
				transports: opts.transports,
			});
		}
		socket.on( 'connect', function() {
			socket.emit( 'add', opts.items );
			opts.connect();
		});
		socket.on( 'reconnect', function() {
			socket.emit( 'add', opts.items );
			opts.reconnect();
		});
		socket.on( 'error', function( err ) {
			opts.error( err );
		});
		socket.on( 'update', function( data ) {
			if ( 'object' == typeof( data ) ) {
				for ( key in data ) {
					if ( 'function' == typeof( handlers[ key ] ) ) {
						handlers[ key ]( data[key ] );
					}
					else {
						opts.defaultUpdateHandler( key, data[key] );
					}
				}
			}
			opts.update( data );
		});
		socket.on( 'rejection', function( msg ) {
			var m = 'origin "' + msg.origin + '" was rejected with error: ' + msg.reason;
			opts.rejection( m );
		} )
	}
	if ( 'undefined' == typeof( io ) ) {
		var d = document;
		script = d.createElement('script');
		script.type = 'text/javascript';
		script.async = true;
		script.onload = function(){
			init();
		};
		if ( '' !== opts.host ) {
			script.src = opts.host + '/socket.io/socket.io.js';
		}
		else {
			script.src = '//cdnjs.cloudflare.com/ajax/libs/socket.io/1.4.4/socket.io.min.js';
		}
		d.getElementsByTagName('head')[0].appendChild(script);
	}
	else {
		init();
	}
	this.addItem = function( item ) {
		opts.items.push( item );
		if ( 'object' == typeof( socket ) ) {
			socket.emit( opts.items );
		}
	}
	this.addHandler = function( key, funct ) {
		if ( 'function' == typeof( funct ) ) {
			handlers[ key ] = funct;
		}
	}
	this.requestItem = function( item, funct ) {
		this.addHandler( item, funct );
		this.addItem( item );
	}
	return this;
}