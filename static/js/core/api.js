import * as UI from './ui.js';
import { langPack } from './langpack.js';

var waitTokenRenew=false;
var tokenCheckSheduled=false;
var clockOffset=0;

export function loadModule(modPath) {
	import(modPath).then(function(mod) {
		mod.load();
	})
}
export async function sleepMs(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
}

export function getCorrectedTime() {
	let localTime=Date.now() / 1000 | 0;
	let offset = localStorage.getItem("clockOffset");
	if (offset==undefined) {
		offset=0;
	}
	return localTime+Number(offset);
}

// errDict
export async function exec(requestType, method , data, onSuccess,noblank,onError,version)
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
		var skipSessionCheck=ref.skipSessionCheck;
		var xoss=true;
	} else {
		var xoss=false;
		var ref={};
	}
	if (noblank==undefined || noblank==false) { UI.blankerShow(); }
	if (requestType==undefined) { requestType="GET" }
	if (requestType=="GET") { data=undefined; }
	if (version==undefined) { version=2; }
	if (skipSessionCheck==undefined) { skipSessionCheck=false }
	if (!isset(method)) {
		throw("Empty method not allowed");
	} 

	var transationStamp=(new Date()).getTime();

	while (waitTokenRenew && !skipSessionCheck) {
		await sleepMs(100);
	}

	if (!skipSessionCheck) {
		await session.check();
	}

	let headers={};
	let token=localStorage.getItem("token");
	if (token!==null) {
		headers.Authorization="Token "+token;	
	}
	
	
	//console.log("API Call started: "+requestType+" "+method + " : "+ transationStamp);
	//console.log("Call token: "+token);
	return $.ajax({
  		url: "/api/v"+String(version)+"/"+method,
  		data: JSON.stringify(data),
  		type: requestType,
  		headers: headers,
		async: ref.async==undefined?true:ref.async,
		complete: function(data,textStatus,request) {
			let serverTime=UI.date2stamp(data.getResponseHeader('Date'));
			let localTime=Date.now() / 1000 | 0;
			clockOffset=serverTime-localTime;
			localStorage.setItem("clockOffset", clockOffset);

			var transationEndStamp=(new Date()).getTime();
	//		console.log("API Call completed: "+requestType+" "+method + " : " + transationStamp + " : " + (transationEndStamp-transationStamp) + " ms");
			if (data.getResponseHeader('X-Fox-Token-Renew') !=null) {
				session.updateToken(data.getResponseHeader('X-Fox-Token-Renew'),data.getResponseHeader('X-Fox-Token-Expire'),data.getResponseHeader('X-Fox-JWT'));					
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
				let rtext2;

				if (isset(jdata) && isset(jdata.error) && isset(jdata.error.xCode)) {
					var xCode=jdata.error.xCode;
					if (isset(ref.errDict) && isset(ref.errDict[xCode])) {
						rtext=ref.errDict[jdata.error.xCode];
						rtext2=rtext;
					} else {
						rtext2=rtext;
						rtext=xCode+" "+rtext;
					}
				} else {
					var xCode=rcode;
					rtext2=rtext;
					rtext=xCode+" "+rtext;	
				}

				if (typeof(onError) == 'function')
				{
					onError(rv, {code: xCode, message: rtext2});
				} else {
					if (onError=="blanker") {
						UI.showError(xCode, rtext2);
					} else {
						UI.showInfoDialog({message: rtext, title: langPack.core.iface.err0,dialogName: "apiExecStatus"+transationStamp, closeCallback: ref.onFinal});
					}
				}
			}
		},
		dataType: "html"
	});	
}

export function getFileByToken(token, fileName) {
    let  url='/api/v2/core/file/'+token;
    
    const a = document.createElement('a')
    a.href = url
    if (fileName != undefined) {
        a.download=fileName;
    }
    
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a);
}

function  uploadFileToken(file, token){ 
    let formData = new FormData();
    formData.append(token, file);
    fetch('/api/v2/core/file', {
        method: "POST", body: formData
    });
}

export function uploadFiles(method, filesFieldId, extraFields) {
	let data={};
	if (typeof(extraFields)=='object') {
		let data=extraFields;
	}

    let files = document.getElementById(filesFieldId).files;
    $.each(files, function(_key, file) {
        data.fileName=file.name;
        data.fileSize=file.size;

        API.exec({
            requestType: "POST",
            method: method,
            data: data,
            onSuccess: function(json) {
                uploadFileToken(file, json.data.Token);
                return false;
            }
        })
    })
    return;
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
			console.log(json.data)
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
	static getConfigItem(item) {
		let user=this.get("user");
		if (user && user.config && user.config[item]) {
			return user.config[item];
		} else {
			return settings.get(item);	
		}		
	}
	
	static parseJwt () {
		let token=localStorage.getItem("jwt");
		if (!token) {
			return null;
		}
		let base64Url = token.split('.')[1];
		let base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
		let jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
			return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
		}).join(''));
		return JSON.parse(jsonPayload);
	}

	static getJwt() {
		return localStorage.getItem("jwt");
	}
	
	static sheduleCheck() {
		//console.log("ScheduleCheck sheduler started");
		if (!tokenCheckSheduled) {
			//console.log("ScheduleCheck sheduler sheduled");
			tokenCheckSheduled=true;
			let token=session.parseJwt();
			let dtx=token.exp-token.iat;
			waitTokenRenew=false;
			setTimeout(session.check, (dtx*0.25 | 0)*1000, true);
		}
	}

	static async check(sheduled) {
		//console.log((sheduled===true?"Sheduled":"Regular")+" token check executed")
		if (sheduled===true) { 
			tokenCheckSheduled=false;
		}
		if (waitTokenRenew) {
			console.log("Token reneq already in progress");
			return;
		}
		let token = session.parseJwt();
		let now=getCorrectedTime();
		
		if (token) {
			let dtx=token.exp-token.iat;
			let renew = token.iat+(dtx/2 | 0)
			if (renew < now) {
				//console.log ("Token renew started");
				waitTokenRenew=true;
				await API.exec({
					skipSessionCheck: true,
					requestType: "GET",
					method: "auth/renew",
					noblank: true,
					onSuccess: function(json) {
						//console.log("Token renew success")
						localStorage.setItem("token",json.data.token);
						localStorage.setItem("tokenExpire",json.data.expire);
						localStorage.setItem("jwt",json.data.jwt);
						waitTokenRenew=false;
						session.sheduleCheck();
						return false;
					},		
					onError: function(error) {
						console.log("Token renew failes with ERROR", error)
						waitTokenRenew=false;
						return false
					}
				})		
			} else {
				//console.log("Token not ready for renew")
				session.sheduleCheck(); 
			}
		}
	}
	
	static getLang() {
		return this.getConfigItem("language");
	}
	
	static close() {
 		localStorage.removeItem("token");
 		localStorage.removeItem("tokenExpire");
		localStorage.removeItem("jwt");
 		sessionStorage.removeItem("session");
	}
	
	static open(data) {
 		localStorage.setItem("token",data.token);
 		localStorage.setItem("tokenExpire",data.expire);
		localStorage.setItem("jwt",data.jwt);
 		sessionStorage.setItem("session",data.session);

	}	
	
	static updateToken(token, tokenExpire, jwt) {
		localStorage.setItem("token",token);
		localStorage.setItem("tokenExpire",tokenExpire);
		localStorage.setItem("jwt",jwt);
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
	static checkAccess(rule, module) {
		let acls=this.get("acls");
		if (module==undefined) { module="core"; } 
		if (acls[module]!=undefined && Object.values(acls[module]).includes(rule)) { return true; }
		else if (acls["<all>"]!=undefined && acls["<all>"].includes(rule)) { return true; }
		else if (acls["<all>"]!=undefined && acls["<all>"].includes("isRoot")) { return true; }
		else { return false; }
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
	
	static async reload(callback) {
		sessionStorage.removeItem("session");
		this.load(callback);
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