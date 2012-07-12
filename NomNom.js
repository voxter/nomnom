#!/usr/local/bin/node

var txtblk = String.fromCharCode(0x1B)+'[0;30m';
var txtred = String.fromCharCode(0x1B)+'[0;31m';
var txtgrn = String.fromCharCode(0x1B)+'[0;32m';
var txtylw = String.fromCharCode(0x1B)+'[0;33m';
var txtblu = String.fromCharCode(0x1B)+'[0;34m';
var txtpur = String.fromCharCode(0x1B)+'[0;35m';
var txtcyn = String.fromCharCode(0x1B)+'[0;36m';
var txtwht = String.fromCharCode(0x1B)+'[0;37m';

var bldblk = String.fromCharCode(0x1B)+'[1;30m';
var bldred = String.fromCharCode(0x1B)+'[1;31m';
var bldgrn = String.fromCharCode(0x1B)+'[1;32m';
var bldylw = String.fromCharCode(0x1B)+'[1;33m';
var bldblu = String.fromCharCode(0x1B)+'[1;34m';
var bldpur = String.fromCharCode(0x1B)+'[1;35m';
var bldcyn = String.fromCharCode(0x1B)+'[1;36m';
var bldwht = String.fromCharCode(0x1B)+'[1;37m';

var undblk = String.fromCharCode(0x1B)+'[4;30m';
var undred = String.fromCharCode(0x1B)+'[4;31m';
var undgrn = String.fromCharCode(0x1B)+'[4;32m';
var undylw = String.fromCharCode(0x1B)+'[4;33m';
var undblu = String.fromCharCode(0x1B)+'[4;34m';
var undpur = String.fromCharCode(0x1B)+'[4;35m';
var undcyn = String.fromCharCode(0x1B)+'[4;36m';
var undwht = String.fromCharCode(0x1B)+'[4;37m';


var bakblk = String.fromCharCode(0x1B)+'[40m';
var bakred = String.fromCharCode(0x1B)+'[41m';
var bakgrn = String.fromCharCode(0x1B)+'[42m';
var bakylw = String.fromCharCode(0x1B)+'[43m';
var bakblu = String.fromCharCode(0x1B)+'[44m';
var bakpur = String.fromCharCode(0x1B)+'[45m';
var bakcyn = String.fromCharCode(0x1B)+'[46m';
var bakwht = String.fromCharCode(0x1B)+'[47m';

var txtrst = String.fromCharCode(0x1B)+'[0m';


var my_eof = String.fromCharCode(0x0D)+String.fromCharCode(0x0A)+String.fromCharCode(0x0D)+String.fromCharCode(0x0A);
var my_eol = String.fromCharCode(0x0D)+String.fromCharCode(0x0A);


var net = require('net');
var amqp = require('amqp');

var version = '1.0';
var listen_ip = '0.0.0.0';
var listen_tcp_port = 5060;
var socket_timeout_in_ms = 7000;

var exchange = null;
var exchange_name = 'vqmonitor';



var connection = amqp.createConnection({ host: 'localhost' });



connection.addListener('ready', function() {
	console.log("connected to "+connection.serverProperties.product);
	connection.exchange(exchange_name, {type: 'fanout', durable: true }, function (ex) { exchange = ex; });
});



console.log(txtblu+'NomNom v'+version+' '+listen_ip+':'+listen_tcp_port+txtrst);
var options = { allowHalfOpen: false };
var server = net.createServer(function (socket,options) {
	
	console.log(txtcyn+'Incoming connection from '+socket.remoteAddress+txtrst);
	console.log(txtcyn+'Current Connections: '+server.connections+txtrst);


	socket.setTimeout(7000,function(){console.log(txtred+'Timeout callback'+txtrst);socket.end('BURP\r\n'+my_eof);});


	socket.on('end',function() { console.log(txtred+'END CONNECTION'+txtrst); });
	socket.on('error',function() { console.log(txtred+'ERROR OUT'+txtrst); });
	socket.on('timeout',function() { console.log(txtred+'TIME OUT'+txtrst); });


	//recieve logic
	socket.on('data',function( thedata ) { 

		var strdata = thedata.toString();

		console.log(txtpur+thedata.toString()+txtrst); 
		//aastra is the routing key but it's un important since the consumers I've build currently collect on all keys=*
		exchange.publish('aastra',thedata.toString()+"ReportIP:"+socket.remoteAddress+"\r\n");

	});


	console.log(txtgrn+'NomNom/'+version+'\r\n'+txtrst);
	socket.write('NomNom/'+version+'\r\n');

});


server.listen(listen_tcp_port, listen_ip);





