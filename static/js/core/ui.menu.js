import * as API from './api.js';
import * as UI from './ui.js';

var xClickCallback;

export class foxMenu {
		
	static drawMenu(click_callback) {
		xClickCallback=click_callback;
		let items=API.session.getMenu();
		items.xlogout={
			title: {
				en: "Logout",
				ru: "Завершить сеанс"
			},
			href: "logout"
		};
		foxMenu.drawMenuNode(items,$("#menu_base_1"));
	}
	
	static drawMenuNode(items,ref,deep,lang, xmodkey) {
		if (lang==undefined) { lang=API.settings.get("language");}
		if (deep==undefined) { deep=0; }
		let xref=$("<ul>")
		.appendTo(ref);
		
		$.each(items, function(key, val) {

			if (val.xmodkey!=undefined) { xmodkey=val.xmodkey; }

			let title=val.title[lang];
			if (title==undefined) { title=Object.values(val.title).shift();};
			let xlabel;
			if (val.href!=undefined) {
				xlabel=$("<a>", {text: title, href: val.href})
			} else if (val.function==undefined) {
				xlabel=$("<span>", {text: title})
			} else {
				xlabel=$("<a>", {text: title, href: "/"+xmodkey+"/"+val.function})
			}
			let xkey=val.pageKey;
			if (xkey==undefined) { xkey=key; };
			
			let yref=$("<li>",{id: "li_"+xmodkey+"_"+xkey, class: "xmenu xmenu_d"+deep})
			.attr("xdeep",deep)
			.attr("xmod",xmodkey)
			.attr("xkey",xkey)
			.attr("xfn",val.function)
			.attr("xhref", val.href)
			.append($("<p>",{ id: "lip_"+xmodkey+"_"+xkey, click: foxMenu.menuItemClick})
			.append($("<i>",{ class: "fas fa-plus plus"}))
			.append($("<i>",{ class: "fas fa-minus minus"}))
			.append(xlabel)
			)
			
			.appendTo(xref)
			
			if (deep==0) {
				yref.addClass("xmenu_root");
			} else {
				yref.addClass("xmenu_dx");
			}
			
			if (val.items) {
				foxMenu.drawMenuNode(val.items,yref,deep+1,lang, xmodkey);
			} else {
				yref.addClass("xmenu_last");
			}
		})
		
	}
	
	static menuItemClick(ref,noExec) {
		
		let xref=$(ref.target).closest("li");
		let xchilds=xref.children("ul").children("li");
		let xparents=xref.parents("li");
		let xroot=xref.parents("li.xmenu_root");
		
		$(".xmenu_dx").hide();
		
		$(".xmenu").removeClass("xmenu_open")
				   .removeClass("xmenu_active")
				   .removeClass("xmenu_axtree")
				   .addClass("xmenu_closed");
	
		xref.addClass("xmenu_open")
			.addClass("xmenu_active")
			.removeClass("xmenu_closed")
			.show();
			
		xchilds.show();		   
			   
		xparents.addClass("xmenu_open")
				.removeClass("xmenu_closed")
				.addClass("xmenu_axtree")
				.show();
		
	
		xroot.find(".xmenu:not(.xmenu_closed .xmenu)").show();
		if (xchilds.length==0) {
			xref.addClass("xmenu_last").show();
		}
		
		
		if (!noExec) {
			if (typeof(xClickCallback)=="function" && (xref.attr("xfn")!=undefined || xref.attr("xhref") != undefined)) {
				UI.xmenuHide();
				xClickCallback(xref);
				return false;
			}
		}
	}
	
	static menuSelect(refId) {
		let ref={target: $("#lip_"+refId)}
		foxMenu.menuItemClick(ref,true);
	}
}