import * as UI from './ui.js';
import { langPack } from './langpack.js';

// errDict
export function exec(requestType, method , data, onSuccess,noblank,onError,version)
{
	
	if (typeof(requestType)=='object') {
		var ref=requestType;
		requestType=ref.requestType;
		method=ref.method;
		data=ref.data;
		onSuccess=ref.onSuccess;
		noblank=ref.noblank;
		onError=ref.onError;
		version=ref.version;
		var xoss=true;
	} else {
		var xoss=false;
		var ref={};
	}
	if (noblank==undefined || noblank==false) { UI.blankerShow(); }
	if (requestType==undefined) { requestType="GET" }
	if (requestType=="GET") { data=undefined; }
	if (version==undefined) { version=2; }
	if (!isset(method)) {
		throw("Empty method not allowed");
	} 

	let headers={};
	let token=localStorage.getItem("token");
	if (token!==null) {
		headers.Authorization="Token "+token;	
	}
	
	var transationStamp=(new Date()).getTime();

	return $.ajax({
  		url: "/api/v"+String(version)+"/"+method,
  		data: JSON.stringify(data),
  		type: requestType,
  		headers: headers,
		
		complete: function(data,textStatus,request) {
			if (data.getResponseHeader('X-Fox-Token-Renew') !=null) {
				session.updateToken(data.getResponseHeader('X-Fox-Token-Renew'));					
			}

			let rcode=data.status;
			let rtext=data.statusText;
			let rdata=data.responseText;
			try {
				var jdata=JSON.parse(rdata);
			} catch(err) {
				console.log("Unable to parce reply",rdata);
				rcode=599;
				rtext="Reply parce failure";
				jdata=undefined;
			}
			
			var rv={status: {success: (rcode>=200 && rcode <300), code: rcode, message: rtext} , data: jdata};
			if (rv.status.success) {
				UI.blankerHide();
				
				let ossrv=undefined;
				if (typeof(onSuccess) == 'function')
				{
					ossrv=onSuccess(rv, rtext);
				} 
				
				if (xoss && (ossrv!==false)) {
					UI.showInfoDialog(langPack.core.iface.ok0);
				}
				
				
			} else {
				UI.blankerHide();
				if (typeof(onError) == 'function')
				{
					onError(rv, rtext);
				} else {
					if (isset(jdata) && isset(jdata.error) && isset(jdata.error.xCode)) {
						var xCode=jdata.error.xCode;
						if (isset(ref.errDict) && isset(ref.errDict[xCode])) {
							rtext=ref.errDict[jdata.error.xCode];
						} else {
							rtext=xCode+" "+rtext;
						}
					} else {
						var xCode=rcode;
						rtext=xCode+" "+rtext;
					}
					
					UI.showInfoDialog({message: rtext, title: langPack.core.iface.err0,dialogName: "apiExecStatus"+transationStamp, closeCallback: ref.onFinal});
				}
			}
		},
		dataType: "html"
	});	
}

export class settings {
	static async load() {
		if (sessionStorage.getItem("baseSettings")==undefined) {
			await exec("GET","meta/settings",{},function(json) {
				sessionStorage.setItem("baseSettings",JSON.stringify(json.data));
			},true)
		}
	}
	
	
	static get(key) {
		let settings={};
		let userSettings={};
		if (sessionStorage.getItem("baseSettings")!=undefined) {
			settings=JSON.parse(sessionStorage.getItem("baseSettings"));
		}
		
		if (sessionStorage.getItem("userSettings")!=undefined) {
			settings=JSON.parse(sessionStorage.getItem("userSettings"));
		}
		
		if (userSettings["key"] != undefined) { 
			return userSettings[key]
		} else {
			return settings[key]
		}
		
		
	}

}

export class auth {
	static async login(login, password, callback, meta) {
		if (login==undefined || password==undefined) {
			throw "Empty credentials not allowed here";
		}
		
		let payload={};
		if (meta!=undefined) {
			payload=meta;
		}
		
		payload.login=login;
		payload.password=password;
		payload.type="WEB";
		
		API.exec("POST", "auth/login",payload, function onSuccess(json) {
			session.open(json.data)
			if (typeof(callback) == 'function')
			{
				callback(json.status.code);
			}
	 	},false,function(json) {
			if (typeof(callback) == 'function')
			{
				callback(json.status.code);
			}
		});
		
	}
	
}

export class session {
	static close() {
 		localStorage.removeItem("token");
 		localStorage.removeItem("tokenExpire");
 		sessionStorage.removeItem("session");
	}
	
	static open(data) {
		
 		localStorage.setItem("token",data.token);
 		localStorage.setItem("tokenExpire",data.expire.stamp);
 		sessionStorage.setItem("session",data.session);

	}	
	
	static updateToken(token) {
		localStorage.setItem("token",token);
	}
	
	static get(key) {
		if (sessionStorage.getItem("session")==undefined) {
			return undefined;
		}
		return JSON.parse(sessionStorage.getItem("session"))[key];
	}
	
	static getMenu() {
		if (session.get("menu") == undefined) {
			let menu={};
			$.each(session.get("modules"),function(mkey,mod) {
				$.each(mod.menu,function(nkey,nmenu) {
					nmenu.xmodkey=mod.name;
					menu[mod.name+"_"+nkey]=nmenu;
				});
			});
			return menu;
		} else {
			return session.get("menu");
		}
	}
	
	static getModInstances() {
		if (session.get("modInstances") == undefined) {
			let instances={};
			$.each(session.get("modules"),function(key,val) {
				instances[val.name]=val.instanceOf;
			});
			session.set("modInstances",instances);
			return instances;
		} else {
			return session.get("modInstances");
		}
	}
	
	static getModuleByInstance(instance) {
		return session.getModInstances()[instance];
	}
	
	static set(key,val) {
		if (sessionStorage.getItem("session")==undefined) {
			var xsession={};
		} else {
			var xsession=JSON.parse(sessionStorage.getItem("session"));
		}
		
		xsession[key]=val;
		sessionStorage.setItem("session",JSON.stringify(xsession));
	}
	
	static async load(callback) {
		let token=localStorage.getItem("token");
		if (token===null) {
			// session not found
			session.close();
			console.log("Session token not found");
			return;
		}
		
		let stamp=new Date();
		if ((sessionStorage.getItem("session")!=undefined) && (((stamp.getTime()/1000)-this.get("updated"))<settings.get("sessionRenewInterval"))) {
			return;
		}
		
		
		try {
			await exec("GET","auth/session",{},function(json) {
				sessionStorage.setItem("session",JSON.stringify(
					json.data
				));

				if (typeof(callback) == 'function')
				{
					callback();
				}
			},true,function(json) {
				if (json.status.code==401) {
					console.log("Session expired");
					session.close();
				}
			})
		} catch (e) {
			if (e.status !== 401) {
				throw e;
			}
		}
	};
}