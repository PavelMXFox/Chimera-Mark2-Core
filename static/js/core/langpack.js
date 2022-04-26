export var langPack={};
initialize();

function initialize() {
	var lp=sessionStorage.getItem("langPack");
	if (lp !== null) {
		langPack=JSON.parse(lp);	
	} 
}

export function lpCacheDrop() {
	console.log("Langpack cache drop");
	sessionStorage.removeItem("langPack");
}

export async function lpInit () {
	if (sessionStorage.getItem("langPack") !== null){ return ;}
	var API=undefined;
	await import('./api.js').then(function(mod) {
		API = mod;
	});
		
	var lang=API.settings.get("language");
	console.log("load "+lang+" language...");
	
	let modules=[];
	await $.each(API.session.get("modules"),async function(key,mod) {
		modules.push(mod);
	});
	
	if (modules.length==0) {
		modules=[{"instanceOf": "core", "languages":API.settings.get("coreLanguages")}];
	}
	
	await Promise.all(modules.map(async (mod) => {
		if (mod.languages.length>0) {
			let xlang="";
			if (mod.languages.indexOf(lang) >=0) {
				// language found in module
				xlang=lang;
			} else {
				// use default
				xlang=mod.languages[0];
			}
			let langFile="/static/js/"+mod.instanceOf+"/lang_"+xlang+".js";
			let { langItem } = await import (langFile);
			langPack[mod.instanceOf]=langItem;
						
		}
  	}));
  	sessionStorage.setItem("langPack",JSON.stringify(langPack));
}