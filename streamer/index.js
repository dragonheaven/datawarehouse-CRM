var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var cp = require ('child_process');
var spawn = cp.spawn;
var uh = {};
var graphdata = {};
var lastfiftylines = [];
var phpthreads = 0;
var lastfiftyphpthreads = [];

/** Run the service */
app.all('/*', function(req, res, next) {
  res.header("Access-Control-Allow-Origin", "*");
  res.header("Access-Control-Allow-Headers", "X-Requested-With");
  next();
});

app.get('/', function(req, res){
  res.sendFile( __dirname + '/index.html' );
});

http.listen(9090, function(){
	console.log( 'Broadcasting on port 9090' );
});

var child = spawn( 'tail', [ '-f', '/tmp/tcldwlogs/lead_import-row_command.log' ] );
child.stdout.on( 'data', function( data ) {
	var arr = data.toString().split( "\r\n" );
	for (var i = 0; i < arr.length; i++) {
		lastfiftylines.push( arr[i] );
		while ( lastfiftylines.length > 50 ) {
			lastfiftylines.shift();
		}
	}
});

setInterval( function() {
	var c = spawn( 'pgrep', [ '-c', 'php' ] );
	c.stdout.on( 'data', function( data ) {
		phpthreads = parseInt( data );
		lastfiftyphpthreads.push([getJsTimestamp(),phpthreads]);
		if ( lastfiftyphpthreads.length > 50 ) {
			lastfiftyphpthreads.shift();
		}
	});
},100);

io.on( 'connection', function( socket ) {
	//console.log( 'New connection with ID ' + socket.id );
	setInterval( function() {
		socket.emit( 'update', { phpthreads: lastfiftyphpthreads } );
	},1000);
	for( key in uh ) {
		var pu = {};
		pu[ key ] = uh[ key ];
		socket.emit( 'update', pu );
	}
	socket.emit( 'update', { graphdata: graphdata } );
	socket.emit( 'update', { livefeed: lastfiftylines } );
	child.stdout.on( 'data', function( data ) {
		socket.emit( 'update', { livefeed: lastfiftylines } );
	});
	//socket.on('signal_recieved', function (data) {
	//	console.log( data );
	//	socket.broadcast.emit('signal', data);
	//});
	socket.on('test', function (data) {
		console.log( 'Test Arrived:' );
		console.log( data );
	});
	socket.on('import-job-finished', function (data) {
		socket.broadcast.emit( 'update', { 'import-job-finished': data } );
	});
	socket.on('system-stats', function (data) {
		socket.broadcast.emit( 'update', { 'system-stats': data } );
		uh['system-stats'] = data;
		addGraphPoint( 'cpu', data.cpu );
		addGraphPoint( 'memory', data.memory );
		addGraphPoint( 'threads', data.threads );
		addGraphPoint( 'rpm', data.rlm );
	});
	socket.on('import-stats', function (data) {
		socket.broadcast.emit( 'update', { 'import-stats': data } );
		uh['import-stats'] = data;
		addGraphPoint( 'leads', data.allLeadCount );
	});
	socket.on('lead-stats', function (data) {
		socket.broadcast.emit( 'update', { 'lead-stats': data } );
		uh['lead-stats'] = data;
		addGraphPoint( 'leads', data.allLeadCount );
	});
	socket.on('export-stats', function (data) {
		socket.broadcast.emit( 'update', { 'export-stats': data } );
		uh['export-stats'] = data;
	});
	socket.on('export-queries', function (data) {
		for ( var beanid in data ) {
			if ( 'object' !== typeof( uh['export-queries'] ) ) {
				uh['export-queries'] = {};
			}
			if ( 'object' !== typeof( uh['export-queries'][ beanid ] ) ) {
				uh['export-queries'][ beanid ] = [];
			}
			uh['export-queries'][ beanid ].push( data[beanid] );
			if ( uh['export-queries'][ beanid ].length >= 10 ) {
				uh['export-queries'][ beanid ].shift();
			}
			socket.broadcast.emit( 'update', { 'export-queries': uh['export-queries'] } );
		}
	});
	socket.on('leads-per-country', function (data) {
		socket.broadcast.emit( 'update', { 'leads-per-country': data } );
		uh['leads-per-country'] = data;
	});
    socket.on('saved-query-results', function (data) {
        socket.broadcast.emit( 'update', { 'saved-query-results': data } );
        uh['saved-query-results'] = data;
    });
});

/**
 * Misc Functions
 */

function getJsTimestamp() {
	return Date.now();
}

function addGraphPoint( graph, value ) {
	if ( 'undefined' == typeof( graphdata[ graph ] ) ) {
		graphdata[ graph ] = [];
	}
	if ( graphdata[ graph ].length >= 10 ) {
		graphdata[ graph ].shift();
	}
	graphdata[ graph ].push([ getJsTimestamp(), parseFloat( value ) ] );
	console.log( graphdata );
}
