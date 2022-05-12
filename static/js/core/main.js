import * as API from './api.js';
import * as UI from './ui.js';
import { lpInit } from './langpack.js';
import { foxMenu } from './ui.menu.js';

var popState_Installed=false;

$(document).ready(function() {
	load();	
});

export async function load() {
	await API.settings.load();
	await API.session.load();
	await lpInit();
	boot();
}

export function boot(xlite) {
	if (xlite==undefined) { xlite=false; }
	if (!xlite) {
		UI.setTitle(API.settings.get("title"));
		UI.loadCSS("/static/theme/"+API.settings.get("theme")+"/main.css");
	}
	UI.contextMenuClose();
	
	if (sessionStorage.getItem("session")===null) {
		import('./login.js').then(function(mod) {
			mod.load();
		})

	} else {
		if (UI.parceURL().module =='auth') {
			UI.setLocation("/");
		}
		if (!xlite) {
			UI.initBody();
			foxMenu.drawMenu(menuClickCallback);
		} else {
			$("#mainframe").empty();
			UI.hideError();
			UI.breadcrumbsUpdate("");
		}
		let req = UI.parceURL();
		let xmod=req.module;
		if (xmod==undefined) { xmod = API.settings.get("defaultModule");}
		
		if (API.session.getModuleByInstance(xmod)==undefined) {
			UI.showError(404);
			return;
		}
		
		let xmfile;
		
		if (xmod=="core") {
			xmfile="./coreModule.js";
		} else {
			xmfile="/static/js/"+API.session.getModuleByInstance(xmod)+"/main.js";
		}
		import(xmfile).then(function(mod) {
			let xselector=xmod+"_"+mod.menuSelector[req.function];
			foxMenu.menuSelect(xselector);
			try {
				if (!mod.load()) {
					UI.showError(400);
				}
			} catch (e) {
				UI.showError(e.message);
			}
		})
		
		if (!popState_Installed) {
			$(window).on('popstate', function(e) {
				boot(true);
			});
			popState_Installed=true;
		}
	}
}

export function smartClick(ref) {
	if(typeof(ref)=="string") {
		UI.setLocation(ref);
	} else {
		UI.setLocation($(ref.currentTarget).prop("href"));
	}	
	boot(true);
	return false;	
}

function menuClickCallback(ref) {
	let xmod=ref.attr("xmod");
	let xfn=ref.attr("xfn");
	let xhref=ref.attr("xhref");
	if (xhref=="logout") {
		API.exec("DELETE","auth/session",{},function(){});
		API.session.close();
		UI.setLocation("/"); 
	} else if (xhref !=undefined) {
		UI.setLocation(xhref);	
	} else {
		UI.setLocation("/"+xmod+"/"+xfn);	
	}
	boot(true);	
}


