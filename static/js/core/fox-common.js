var UI, API, langPack;
import("./ui.js").then(function(mod) { UI=mod; });
import("./api.js").then(function(mod) { API=mod; });    
import('./langpack.js').then(function(mod) { langPack=mod.langPack; });

const blanker="blanker";

function base64_decode( data ) {	// Decodes data encoded with MIME base64
	// 
	// +   original by: Tyler Akins (http://rumkin.com)


	var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var o1, o2, o3, h1, h2, h3, h4, bits, i=0, enc='';

	do {  // unpack four hexets into three octets using index points in b64
		h1 = b64.indexOf(data.charAt(i++));
		h2 = b64.indexOf(data.charAt(i++));
		h3 = b64.indexOf(data.charAt(i++));
		h4 = b64.indexOf(data.charAt(i++));

		bits = h1<<18 | h2<<12 | h3<<6 | h4;

		o1 = bits>>16 & 0xff;
		o2 = bits>>8 & 0xff;
		o3 = bits & 0xff;

		if (h3 == 64)	  enc += String.fromCharCode(o1);
		else if (h4 == 64) enc += String.fromCharCode(o1, o2);
		else			   enc += String.fromCharCode(o1, o2, o3);
	} while (i < data.length);

	return enc;
};

function isset(val)
{
	return !(val === null || val == '' || val === undefined);
};


