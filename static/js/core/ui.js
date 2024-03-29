import { langPack } from './langpack.js';
import * as API from './api.js';
import { smartClick } from './main.js';
 
var codes={
    "400":"Bad request",
    "401":"Unauthorized",
    "403":"Forbidden",
    "404":"Not found",
    "405":"Method Not Allowed",
    "500":"Internal server error",
    "501":"Not Implemented",
};

export function showError(code, message) {
	if (message==undefined) { message=codes[code]}
	if (message==undefined) { message="Internal server error"}
	$("<div>", { class: "error blanker bgerror"}).appendTo("body");
	$("<div>", { class: "error error_banner",html: "ERROR "+code+"<br/>"+message}).appendTo("body");
}

export function hideError() {
	$("body div.error").remove();
}

export function click (ref) {
	return smartClick(ref);
}
 
export function addButton(id, icon, title, color, onClick, cssClass, style) {
	var opts;
	if (typeof(id)=='object') {
		opts=id;
		id=opts.id;
		icon=opts.icon;
		title=opts.title;
		color=opts.color;
		onClick=opts.onClick;
		cssClass=opts.cssClass;
		style=opts.style;
	} else {
		opts={};
	}
	
	if (cssClass===undefined) { cssClass = "short super";}
	let btn=$("<div>", {
		class: "button "+cssClass+" "+color, 
		id: id, 
		append: $("<i>", {class: icon }),
		style: style,
	}).attr("title", title);
	
	$.each(opts.attr, function(key,val) {
		btn.attr(key,val);	
	});
	
	if (style!==undefined) {
		btn.style=style;
	}
	
	if (typeof(onClick) == 'function')
	{
		btn.click(function(ref) { onClick(ref); return false;});
	}
	
	return btn;
}

export function parceURL() {
	
	let pattern = /([^:]{1,}):\/\/([^\/#?]{1,})(\/([^\/#?]{1,})){0,1}(\/([^\/#?]{1,})){0,1}(\/([^\/#?]{1,})){0,1}/i;
	let pattern2 = /([#](.*)){0,1}$/i;
	let px = document.location.href.match(pattern);
	let px2=document.location.href.match(pattern2);
	let rv={
		proto: px[1],
		host: px[2],
		module: px[4],
		function: px[6],
		id: px[8],
		marker: px2[2]
	};
	if (rv.id=='') { rv.id=undefined; }

	let args=document.location.href.split('?')[1];
	let rva=[];
	if (typeof(args)=='string') {
		args=args.split('&');
	} else {
		args=[];
	}
	
	$.each(args,function(key,val) {
		rva[val.split("=")[0]]=val.split("=")[1];	
	});
	
	rv.args=rva;
	
	return rv; 
	
}

export function empty() {
	$(".t_main #mainframe").empty();
}

/*
	panels is array
		
	panel_desc= [{
		id, 
		title,
		buttons [
			addButton() (.....)
		]
	}]

*/

export function createLeftPanel(panels, config) {
	
	if (config==undefined) { config={};}
	
	
	let ref;
	if (config.ref) {
		ref=config.ref;
	} else {
		if (!ref) { ref =$(".t_main #mainframe div.widget_panel_left"); }
	}
	
	if (ref.length==0) {
		ref=$("<div>", { class: "widget_panel_left" }).appendTo(".t_main #mainframe");
	} else {
		ref.empty();
	}
	
	var divAccord=$("<div>",{
		id: "accordion",
		class: "accordion"
	}).appendTo(ref);


	$.each(panels,function (_index,panel) {
		let xpdiv=$("<div>", { class: "widget lock c_contacts", id: panel.id, text: langPack.core.iface.dataLoading, style: panel.disabled?"display: none":""});
		if (panel.attrs) {
			$.each(panel.attrs, function (key, val) {
			xpdiv.attr(key,val);				
			});
		}
		
		let px = $("<h3>",{text: panel.title, id: panel.id+"_title", append: panel.buttons, style: panel.disabled?"display: none":"" })
		.add(xpdiv);
		px.appendTo(divAccord);
	});
	
	let accConfig={
		heightStyle: "content",
	};
	if (typeof(config.beforeActivate)=="function") {
		accConfig.beforeActivate=config.beforeActivate;
	}

	if (typeof(config.activate)=="function") {
		accConfig.activate=config.activate;
	}

	if (typeof(config.create)=="function") {
		accConfig.create=config.create;
	}


	$("#accordion" ).accordion(accConfig);

}

function panelBeforeActivate(panel, callback) {
	$("#tab_buttons .button_block").hide();

	let fx=callback[panel.prop("id").replace(/^[^\-\_]*[\-\_]/,"")];
	
	if (typeof(fx) == 'function')
	{
		$("div.widget."+panel.prop("id").replace(/^[^\-\_]*[\-\_]/,"")).empty();;
		fx();
	}
}

function panelActivate(panel, callback) {
	$(panel.prop("id").replace(/^[^\-\_]*[\-\_]/,"#buttons_")).show();
	$(panel.prop("id").replace(/^[^\-\_]*[\-\_]/,"#buttons_")+" .x_status .hideme").hide();
}

export function createRightPanel(panels) {
	let ref=($(".t_main #mainframe div.widget_panel_right"));
	if (ref.length==0) {
		ref=$("<div>", { class: "widget_panel_right" }).appendTo(".t_main #mainframe");
	}
	createTabsPanel(panels,ref);
}

export function tabPanelRenameTab(tabId, text) {
	$("#a-tab-"+tabId).text(text);
}
export function tabPanelHideTab(tabId) {
	$( "#item_tabs").tabs("disable","#tab-"+tabId);
}
export function tabPanelShowTab(tabId) {
	$( "#item_tabs").tabs("enable","#tab-"+tabId);
}
export function tabPanelActivateTab(tabId) {
	$("#item_tabs").tabs({active: Number($("#a-tab-"+tabId).attr("idx"))});
}

export function createTabsPanel(panels,ref) {
	if (ref===undefined) {
		ref=$(".t_main #mainframe");
	} else {
		ref.empty();
	}
	
	var callback={};
	let divTabs=$("<div>",{
		id: "item_tabs",
		class: "ui-tabs-main-div",
		append: $("<div>",{ 
			class: "ui-tabs-title-line",
			append: $("<div>",{ 
				style: "display: inline-block; float: left;",
				append: $("<ul>", { id: "item_tabs_ul_list"
					
				})
			})
			.add($("<div>", { 
				id: "tab_buttons", 
				style: "display: inline-block; float: right",
			}))
		})
	}).appendTo(ref);
				

	$.each(panels,function (index,panel) {
		if (panel.id==undefined) {panel.id=index;}

		$("<li>",{append: $("<a>",{href: "#tab-"+panel.id, id: "a-tab-"+panel.id, text: panel.title}).attr("idx",index)})
		.appendTo("#item_tabs_ul_list");
		$("<div>", { 
			id: "tab-"+panel.id, 
			buttons: "buttons_"+panel.id,
			append: $("<div>", {id: panel.id, class: "widget "+panel.id, text: langPack.core.iface.dataLoading})
		}).appendTo(divTabs);

		$("<span>", { 
			class: "button_block", 
			id: "buttons_"+panel.id,
			append: panel.buttons
		}).appendTo("#tab_buttons").hide();
				
		if (typeof(panel.preLoad) == 'function')
		{
			callback[panel.id] = panel.preLoad;
		}

	});

	
	
	$( "#item_tabs").tabs({
		create: function( event, ui ) {
			panelBeforeActivate(ui.panel,callback);
			panelActivate(ui.panel);
		},
		activate: function( event, ui ) {
			panelActivate(ui.newPanel);
		},
		beforeActivate: function( event, ui ) {
			panelBeforeActivate(ui.newPanel,callback);
		}
	});
	
	$.each(panels,function (_index,panel) {
		if (panel.disabled) {
			$( "#item_tabs").tabs("disable","#tab-"+panel.id);
		}
	});
}



export function addFieldGroup(items)
{
	return $("<div>",{
		class: "crm_entity_block_group",
		append: items
	});
}

/*	- autocomplete: ref.acFunction: function(request,response) {
 *		response([{value: "TEST1111", id: "1"},{value: "TEST2222", id: "2"}]);
 *	  }
 */

export function addField(ref)//title, item, blockstyle, fieldstyle, type, args, onChange, onClick, onContextMenu)
{
	var item=undefined;
	if (ref.type === undefined || (ref.item===undefined && ref.type != "href")) { 
		var type = 'label'; 
	} else {
		var type=ref.type;
	}
	
	if (typeof(ref.item)=="string" || ref.item===undefined)
	{
			
		if (ref.item===undefined) { 
			var name=undefined;
		} else {
			var name = ref.item.replace(/^[a-z]*\_/,'');
		}

		let item_id=ref.item;
		let args = ref.args;
		if (ref.args==undefined && ref.val != undefined) {
			args = ref.val;
		} else {
			args={};	
		}
		switch(type) {
  			case 'password':				
	    		item = $("<input>", {class: "i", id: ref.item, name: name, type:'password',width: 'calc(100% - 44px)'})
	    		.add($("<div>",{class: "button short", style: "width: 25px; margin-right: 0; margin-left: 2; padding: 0; padding-top: 1; font-size: 13px;",append: $("<i>",{class: 'far fa-eye'})
	    		})
	    		.click(function() {
					if ($("#"+item_id).prop('type')==='password' && (!$("#"+item_id).prop("disabled"))) {
						$(this).addClass("active");
						$("#"+item_id).prop('type', 'input');
					} else {
						$(this).removeClass("active");
						$("#"+item_id).prop('type', 'password');
					}
	    		}));
	    		break
  			case 'passwordNew':
				if (args.chars==undefined) {
					switch(args.type) {
						case "num":
							args.chars="0123456789";
					}
				}

  				item = $("<input>", {type: ref.noHide?"input":"password", class: "i", id: ref.item, name: name, width: 'calc(100% - 44px)'})
	    		.add($("<div>",{
	    			class: "button short", 
	    			style: "width: 25px; margin-right: 0; margin-left: 2; padding: 0; padding-top: 1; font-size: 13px;",
	    			append: $("<i>",{class: 'fas fa-retweet'}),
    			})
    			.click(function() {
					if ($("#"+item_id).prop("disabled")) {
						return;
					}
    				let password=genPassword(args.chars, args.length);
					$("#"+item_id).val(password);
					$("#"+item_id).prop("type","input");

					$("#"+item_id).change();
					if (typeof(ref.newPasswdGenCallback)=="function") {
						ref.newPasswdGenCallback(password);
					}

    			}));
    			
	    		break
	    	case 'select':
	    		item= $("<select>",	{class: "i", id: ref.item, name: name});
	    		if (ref.args !== undefined) {
	    			$.each(ref.args, function(arg,val) {
						if (typeof(val)=='array' || typeof(val)=='object') {
							item.append($("<option>",{value: val.val, text: val.title, selected: val.val==ref.val}));
						} else {
							item.append($("<option>",{value: arg, text: val, selected: arg==ref.val}));
						}
	    				
	    			});
	    		}
	    		break;

    		case 'datetime':
    			if (typeof(args)!=='object') { args={curr: args}; }
    			if (args.step===undefined) { args.step = 10; }
    			if (args.format===undefined) { args.format = "Y-m-d H:i"; }
    			if (args.mask === undefined) { args.mask=true; }
    			
    			item=$("<input>", {class: "i", id: ref.item, name: name});
    			
    			item.datetimepicker(args);
    			item.val(args.curr);

    			break;

			case "file":
				item = $("<input>",{ 
					type: "file", 
					id: ref.item, 
					class: "ifile", 
					multiple: ref.multiple==true,
					change: function(ref) {
						let files=ref.currentTarget.files;
						let label=$("#"+ref.currentTarget.id+"__labelTitle");
						
						$("#"+ref.currentTarget.id+"__label").addClass("changed");
						if (files.length==0) {
							label.text(langPack.core.iface.filesClickForSelect);
						} else if (files.length==1) {
							label.text(langPack.core.iface.filesSelected+": 1 "+langPack.core.iface.filesFile);
						} else {
							label.text(langPack.core.iface.filesSelected+": "+files.length+" "+langPack.core.iface.filesFiles);
						}
					},
				})
				.add($("<label>", {
					id: ref.item+"__label",
					for: ref.item, 
					append: $("<i>",{ class: "far fa-save", title: langPack.core.iface.filesClickForSelect})
						.add($("<span>",{style: "margin-left: 10px;", id: ref.item+"__labelTitle",text: langPack.core.iface.filesClickForSelect}))
					,
				}))

				break;

			case "label":
				item = $("<span>", {id: ref.item, class: "i", html: ref.val});
				break;

			case "href":
				item = $("<a>", {href: ref.href, id: ref.item, class: "i", html: ref.val, click: smartClick});
				break;
				
			case "multiline":
	    		item = $("<textarea>", {class: "i", id: ref.item, name: name, value: ref.val, rows: ref.args.rows});
				break;
				
			case "hidden":
				item = $("<input>", {class: "i", type: "hidden", id: ref.item, name: name, value: ref.val});
	    		return item;
			
			case "autocomplete":
				let acitem=$("<input>", {class: "i", id: ref.item, name: name, value: ref.val})
				item = ($("<input>", { type: "hidden", id: ref.item+"_bk", value: ref.val}))
				.add($("<input>", { class: "i", type: "hidden", name: name+"_id", id: ref.item+"_idx", value: ref.val}))
				.add(acitem);
										
				if (isset(ref.acValues)) {
				   acitem.autocomplete({
			     		source: ref.acValues
				    });				
				}
				
				if (isset(ref.acFunction) && (typeof(ref.acFunction)=='function')) {
					acitem.autocomplete({
					  source: ref.acFunction,
				      minLength: 2,
				      select: function( event, ui ) {
				       	$("#"+ref.item+"_idx").val(ui.item.id).addClass("changed");
				       	$("#"+ref.item).val(ui.item.label).addClass("changed");
						$("#"+ref.item+"_bk").val(ui.item.label).addClass("changed");
				              	
				      }
				    })
					.focusout(function() {
						if ($("#"+ref.item+"_bk").val() != $("#"+ref.item).val())
						{
							if ($("#"+ref.item).val() == "")
							{
						    	$("#"+ref.item+"_idx").val("").removeClass("changed");
			   					$("#"+ref.item+"_bk").val("").removeClass("changed");
							} else {
								$("#"+ref.item).val($("#"+ref.item+"_bk").val()).addClass("changed");
				
							}
						}
					})
				}
				
				break;
  			
  			default:
	    		item = $("<input>", {class: "i", id: ref.item, name: name, value: ref.val});
	    		break
    	}
    	if (ref.disabled) { item.attr("disabled","true")}
	} else {
		item = ref.item;
	}
	
	item.change(function() {$(this).addClass('changed');})
	if (ref.attrs && ref.attrs.disabled) {
		item.attr("disabled",ref.attrs.disabled)
	}
	if (typeof(ref.onChange)=='function') {
		item.change(ref.onChange);
	}

    let title=$("<span>",{html: ref.title});
    
	if (isset(ref.reqx)) {
		title.append($("<span>",{ class: "dialogFieldTitle_Reqx",text: "*", title: langPack.core.iface.errReqx }));
	}	
	
	let rvv=$("<div>",{
				class: "crm_entity_field_value",
				append: item,
				style: ref.fieldstyle
			});
			
	let rv=$("<div>",{
			class: "crm_entity_field_block",
			style: ref.blockstyle,
			append: $("<div>",{
				class: "crm_entity_field_title",
				
				append: title
		
			})
			.add(rvv)
		});
		
		if (type=="label") {
			if (typeof(ref.onClick)=="function") {
				rv.click(ref.onClick);
				rv.addClass("clickable");
				
			}
		}
		
		if (type=="label" || type=="href") {
			if (typeof(ref.onContextMenu)=="function") {
				rv.bind('contextmenu', ref.onContextMenu);
				rv.addClass("withContextMenu");
				rvv.append($("<i>",{style: "width: 50px; text-align: right; padding-right: 10px;", class: "fas fa-ellipsis-v"}).bind('contextmenu click', ref.onContextMenu))
			}
		}

		$.each(ref.attrs, function(key, val) {
			rv.attr(key,val);
		});
		
		if (isset(ref.reqx)) {
			rv.attr("reqx",ref.reqx);
		}

		if (isset(ref.regx)) {
			rv.attr("regx",ref.regx);
		}

		if (isset(ref.regxTitle)) {
			rv.attr("regxTitle",ref.regxTitle);
		}
		
		return rv;	
}

export function breadcrumbsUpdate(text) {
	if (typeof(text)=="string") {
		$("#breadcrumbs_label").text(text);	
	} else {
		$("#breadcrumbs_label").empty();
		$("#breadcrumbs_label").append(text);
	}
	
}

export function breadcrumbsUpdateSuffix(text) {
	breadcrumbsUpdate($("#breadcrumbs_label").text().replace(/ \/[^/]*$/,' / '+text))
}

export function getUnixTime(offset) {
	if (offset==undefined) { offset=0; }
	return (new Date().getTime()/1000)+offset;
}

export function date2stamp(dateString) {
	return (new Date(dateString).getTime()/1000);
}

export function stamp2isodateq(stamp) {
	var xdate = new Date(stamp*1000);
	var rv=xdate.getFullYear().pad(4)+"-"+String((xdate.getMonth()+1).pad(2))+"-"+String(xdate.getDate().pad(2));
	return rv;
}


export function stamp2isodatens(stamp) {
	var xdate = new Date(stamp*1000);
	var rv=stamp2isodateq(stamp)+" "+xdate.getHours().pad(2)+":"+xdate.getMinutes().pad(2);
	return rv;
}


export function stamp2isodate(stamp) {
	var xdate = new Date(stamp*1000);
	var rv=stamp2isodatens(stamp)+":"+xdate.getSeconds().pad(2);
	return rv;
}

export function stamp2date(stamp, shortMon) {
    if (stamp==undefined) { return ""};
	if (shortMon) {
		var mons=langPack.core.date.monQ;	
	} else {
		var mons=langPack.core.date.months;
	}
	var xdate = new Date(stamp*1000);
	var rv=String(xdate.getDate().pad(2)) + " " + String(mons[xdate.getMonth()+1])+" "+xdate.getFullYear().pad(4)+" "+langPack.core.date.yearQ;
	return rv;
}

export function stamp2datetime(stamp, shortMon) {
	if (stamp==undefined) { return ""};
	var xdate = new Date(stamp*1000);
	var rv=stamp2date(stamp,shortMon)+" "+xdate.getHours().pad(2)+":"+xdate.getMinutes().pad(2);
	return rv;
}

export function stamp2datetimeSec(stamp, shortMon) {
	if (stamp==undefined) { return ""};
	var xdate = new Date(stamp*1000);
	var rv=stamp2datetime(stamp,shortMon)+":"+xdate.getSeconds().pad(2);
	return rv;
}

export function formatInvCode(code) {
	if ((typeof(code) != 'string') ||code.length==0) {
		return langPack.core.iface.emptyInvCode;	
	} else {
		return code.substr(0,4)+"-"+code.substr(4,4)+"-"+code.substr(8);
	}
}

export function genPassword(chars, passwordLength) {
	if (chars==undefined) { chars = "0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*()ABCDEFGHIJKLMNOPQRSTUVWXYZ"; };
	if (passwordLength==undefined) { passwordLength = 16; };
	let password = "";
	
	for (let i = 0; i <= passwordLength; i++) {
	   let randomNumber = Math.floor(Math.random() * chars.length);
	   password += chars.substring(randomNumber, randomNumber +1);
	}
	
	return password;
}

export function getId(ref) {
	if (ref.id) {
		return ref.id;
	} else if (ref[0] && ref[0].id) {
		return ref[0].id;
	}

}

export function collectForm(formid, getall, withIDS, withREF, validate)
{
	if (typeof(formid)=='object') {
		var ref=formid;
		formid=ref.formId;
		getall=ref.getAll;
		withIDS=ref.withIDS;
		withREF=ref.withREF;
		validate=ref.validate;
	} else {
		var ref={};
	}
	
	// создадим пустой объект
	var data = {};
	var ctr=0;
	var cctr=0;
	var cerr=0;
	var key;

	$('#'+formid+' .i'+((getall==true)?"":'.changed')).each(function() {
	  if (isset(this.name)) {key = this.name} else {key = this.id}
	  if(!isset(key)) { return; }
 	  if (getall) { 
		
		var reqx="false";
		var regx=".*";
		var rext="false";
		var px = $(this).closest('.crm_entity_field_block');
		var regxTitle;
		if (px.length == 1) {
			if ($(px[0]).attr("regx") !== undefined) { regx = $(px[0]).attr("regx"); }
			if ($(px[0]).attr("regxTitle") !== undefined) { regxTitle = $(px[0]).attr("regxTitle"); }
			if ($(px[0]).attr("reqx") !== undefined) { reqx = $(px[0]).attr("reqx"); }
			if ($(px[0]).attr("rext") !== undefined) { rext = $(px[0]).attr("rext"); }
		}
		
		if ($(this).prop("disabled")) { reqx="false";};
		data[key] = {val: $(this).val(), changed: $(this).hasClass("changed")};
		data[key].rext=rext;

		if (withIDS==true) {
			data[key].id=$(this).prop("id");
			data[key].regx=regx;
			data[key].reqx=reqx;
		}

		if (withREF==true) {
			data[key].refx=this;
		}

		if (validate==true) {
			var reqxOK = reqx!="true" || (isset(data[key].val));
			var regxOK = (!isset(data[key].val)) ||  data[key].val.match(new RegExp(regx, 'g' )) !== null;
			
			if (withIDS==true) {
				data[key].reqxOK = reqxOK;
				data[key].regxOK = regxOK;
			}	
			
			data[key].valOK = reqxOK && regxOK;
			
			if ((reqxOK !== true || regxOK !== true )) {
				$(this).addClass("alert");
				var ttlx ="";
				if (reqxOK !== true) { ttlx += langPack.core.iface.errReqx};
				if (regxOK !== true) { ttlx += langPack.core.iface.errRegx+" - " + (regxTitle==undefined?regx:regxTitle); };
				$(this).attr("title",ttlx);
				cerr++;
				
			} else {
				$(this).removeClass("alert");
				$(this).attr("title","");
			}
		}

		
		} else { data[key] = $(this).val(); }
		
		
	   if ($(this).hasClass("changed")) { cctr++;}
	  ctr++;
	});
	data["elCount"]=ctr;
	data["changedCount"]=cctr;
	data["validateErrCount"]=cerr;
	try {
		data["id"] = id;
		} catch {
			
		}
	data.getVals=function() {
		let rv={};
		$.each(data,function(idx,val) {
			if (typeof(val) =='object') {
				rv[idx]=val.val;
			}
		});
		
		return rv;
	}
	return data;
}


export function createDialog(body, title, buttons, height, columns, id) {

	if (id==undefined) {id = 'dialogForm'; }
	if (columns=='undefined') { columns=1; }
	
	let dx=$("<div>",{
		id: id,
		title: title,
		style: "overflow: hidden;"
	})
	.prop("dxColumns",columns)
	.appendTo("body")
	.dialog({
		autoOpen: false,
 		height: height,
      	width: (350*columns),
		modal: true,
		position: {my: "center",at: "center",of: window},
        close: function () {
    		$("#"+id).dialog("destroy").remove();
    	},
		buttons: buttons
	})
	.append(body);

	return dx;
	
}

export function openDialog(id) {
	if (id==undefined) {id = 'dialogForm'; }
	let dx;
	if (typeof(id)=="object") {
		dx=$(id);
		id=dx.id;
	} else {
		dx=$("#"+id);
	}
	
	$("#"+id+' .i').on("change",function() {on_valChanged($(this)); });
	dx.dialog("open");

	let height=$("#"+id).dialog("option","height");	

	if (!height) {

		let summHeight=0;
		let maxHeight=0;
		let columns = dx.prop("dxColumns");

		$.each(dx.find(".crm_entity_block_group"), function(_key,val) {
			summHeight +=val.clientHeight;
			if (val.clientHeight>maxHeight) { maxHeight=val.clientHeight; }
		})
	
		let halfHeight=Math.ceil(summHeight/columns);
		let xHeight=(maxHeight>halfHeight)?maxHeight:halfHeight;
	
		dx.dialog("option","height", xHeight+180);
	}
}

export function closeDialog(id) {
	if (id==undefined) {id = 'dialogForm'; }
	let dx;
	if (typeof(id)=="object") {
		dx=$(id);
	} else {
		dx=$("#"+id);
	}
	dx.dialog("close");
	dx.remove();
}


export function copySelText(selText) {
	if (navigator.clipboard) {
		navigator.clipboard.writeText(selText);
		return true;
	} else {
		return false;
	}
}

export function getClipboard(onSuccess, onFail) {
		console.log(typeof(onFail));
		console.log(typeof(onSuccess));

	navigator.clipboard.readText().then(text => {
		console.log('Clipboard content is: ', text);
		if (typeof(onSuccess)=="function") {
			onSuccess(text);
		}
	})
	.catch(err => {
		console.error('Failed to read clipboard contents: ', err);
		if (typeof(onFail)=="function") {
			
			onFail(err);
		}
	})
}

export function getSelectionText() {
    var text = "";
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text;
}

export function on_valChanged(t_this) { 
	t_this.addClass('changed');
}

export function progressBar_init()
 {
	$('.progressbar').each(function() {
	   	let el = $(this);
	   	let val = parseInt(el.attr('value'));
	
	   	el.progressbar({
	   		value: 0
	   	});
	   progressBar_update(el, val);

	});
}	

export function progressBar_update(el, val)
{
	el.attr("value",val);
	el.progressbar( "option", "value", val);
		if (val >= 90) {
   		el.children(".ui-widget-header").css({ 'background': 'var(--mxs-red)' });
   	} else if (val >= 80) {
   		el.children(".ui-widget-header").css({ 'background': 'var(--mxs-yellow)' });
   	} else {
   		el.children(".ui-widget-header").css({ 'background': 'var(--mxs-blue)' });
   	}
}



export function showInfoDialog(message, title, buttons, height) {	
	if (typeof(message)=='object') {
		var ref=message;
		message=ref.message;
		title=ref.title;
		buttons=ref.buttons;
		height=ref.height;
		var dialogName=ref.dialogName;
	} else {
		var ref={};
		var dialogName=undefined;
	}
	
	let msg = $("<span>",{class: "widget", style: "padding: 8px;height: "+height+";", id: "dialogInfoSpan", });
	if (typeof(message)=="string")
	{
		msg.html(message);
	} else {
		msg.append(message);
	}
	
	if (dialogName==undefined) { dialogName="dialogInfo"; }
	if (title===undefined) { title=langPack.core.iface.dialodInfoTitle; };
	if (buttons===undefined) {
		buttons = {};
		buttons[langPack.core.iface.dialodCloseButton]=function() {
		   if (typeof(ref.closeCallback)=='function') {
				ref.closeCallback();
		   }
           dialogInfo.dialog("close");
        }
	}
	let dialogInfo = $("<div>",{
		id: dialogName,
		title: title,
	})
	.appendTo("body")
	.dialog({
		autoOpen: true,
 		height: 250,
      	width: 403,
		modal: true,
		position: {my: "center",at: "center",of: window},
        close: function () {
    		dialogInfo.dialog("destroy").remove();
    	},
		buttons: buttons
	})
	.append(msg);
	dialogInfo.dialog("option","height", $("#dialogInfoSpan").height()+150);

}

export function blankerShow(text) {
	if (text==undefined) {text=langPack.core.iface.blankerText};
	$("<div>", { class: "blanker bggray"})
	.append($("<div>", {class: "bl_infobox"})
		.append($("<img>", {src: "/static/theme/core/img/ajax.gif"}))
		.append($("<h1>", {id: "blanker_text", text: text}))
	)
	.appendTo("body");
}

export function blankerHide() {
	$(".blanker").remove();
}

export function setTitle(title, desc)
{
	if (document.title != title) {
	    document.title = title;
	}
	$('meta[name="description"]').attr("content", desc);
}

export function initBody() {
	$("html").removeClass("login");
	$("body").empty().removeClass("login");
	$("<div>",{id: "contextMenu", class: "contextMenu"}).appendTo("body").hide();
	$("<div>", { class: "t_global"})
	.appendTo("body")
	.append($("<div>", { class: "header"})
		.append($("<div>", { class: "logo_menu",click: xmenuToggle})
			.append($("<i>", { class: "fas fa-bars"}))
		.add($("<div>", { class: "header_logo", text: API.settings.get("title")}))
		)
	)
	.append($("<div>", { class: "t_navy_main clicktohide xmenu_hidden xmenu_hidable"})
		.append($("<div>", { class: "title breadcrumbs", })
			.append($("<div>", { text: langPack.core.iface.mainMenuTitle}))
		)
		.append($("<div>", { id: "menu_base_1", style: "margin: 0; padding: 0;"}))
	)
	.append($("<div>",{ class: "t_main clicktohide"})
		.append($("<div>", { class: "breadcrumbs"})
			.append($("<div>", { style: "display: inline-block; float: right; padding-right: 10px;"})
				.append($("<a>",  { href: "/core/myprofile",  text: API.session.get("user").fullName, click: smartClick})
					.append($("<i>", { class: "far fa-user", style: "margin-left: 10px; font-size: 15px;"}))
				)
			)
			.append($("<div>", { style: "display: inline-block; float: right; padding-right: 10px;", id: "newMsgAlert"})
				.append($("<i>",  {class: "fas fa-bell panel inactive"}))
				.append($("<span>", { id: "coreMsgCount", text: "-"}))
				.hide()
			)
			.append($("<div>", { style: "display: inline-block; float: right; padding-right: 10px;", id: "barcodeScannerStatus"})
				.append($("<i>",  {class: "fas fa-qrcode panel inactive"}))
				.attr("title","Barcode Scanner Off-Line")
				.hide()
			)
			.append($("<div>", { style: "display: inline-block; float: left;  position: absolute; max-height: 18px; overflow: hidden;"})
				.append($("<i>", { class: "far fa-lightbulb", style: "margin-right: 10px; font-size: 15px;"}))
				.append($("<span>", { id: "breadcrumbs_label", text: ""}))
			)
			
		)
		.append($("<div>",{id: "mainframe"}))
	)
	
	
	window.onresize = function(event) {
    	$("div.t_main").css("height", (event.target.innerHeight)+"px");
    	$("html").css("height", (event.target.innerHeight)+"px");
    	
	};
	
	$(window).resize();
}

export function loadCSS(url) {
	if (document.createStyleSheet)
	{
	    document.createStyleSheet(url);
	}
	else
	{
	    $('<link rel="stylesheet" type="text/css" href="' + url + '" />').appendTo('head'); 
	}	
}

function xmenuToggle(el) {
	
	
	$(document).unbind('keyup', xmenuHide);
	$("body").unbind('click', xmenuHide);
	
	let xmenu = $(".xmenu_hidable");
	if (xmenu.hasClass("xmenu_hidden")) {
		xmenu.removeClass("xmenu_hidden");
		
		$(document).bind('keyup', xmenuHide);
		$("body").bind('contextmenu click', xmenuHide);
	} else {
		xmenuHide();
	}
	return false;
}

export function xmenuHide(el) {
	
	
	if (el != undefined && el.type=='keyup') {
		if(el.keyCode !== 27) {
			return;
		}
	}

	if (el != undefined && el.type=='click') {
		if($(el.target).closest("div.t_navy_main").length>0) {
			return;
		}
	}

	$(document).unbind('keyup', xmenuHide);
	$("body").unbind('click', xmenuHide);

	let xmenu = $(".xmenu_hidable");
	if (!xmenu.hasClass("xmenu_hidden")) {
		xmenu.addClass("xmenu_hidden");
	}
}

export function contextMenuOpen(event, itemsList, title) {
	$("body").unbind('contextmenu click', contextMenuClose);
	let selText = getSelectionText();
	$("div#contextMenu").empty();
	if (isset(title)) {	$("<p>",{class: "title cmTitle",  text: title}).appendTo("div#contextMenu"); }
	let items=$("<div>",{class: "items"}).appendTo("div#contextMenu");

	if (isset(selText)) {
		$("<p>",{class: "item", text: langPack.core.iface.contextMenuCopySelected}).appendTo(items).click(function() {
			copySelText(selText);
			contextMenuClose();
		});	
	}
	
	$.each(itemsList, function(key,val) {
		let item=$("<p>",{class: "item"});
		if (isset(val.href)) {
			$("<a>",{href: val.href, html: val.title}).appendTo(item);
			item.click(function() {
				window.location.href=val.href;
				contextMenuClose();
				return false;
			})
		} else {
			item.html(val.title);
			if (isset(val.onClick)) {
				item.click(function(el) { val.onClick(el); contextMenuClose();return false;});
			}
		}

		
		
		item.appendTo(items);
	});
	
	$("div#contextMenu").show();
	if (($(window).scrollLeft()+event.clientX+350) < ($(window).scrollLeft()+$(window).width())) {
		$("div#contextMenu").offset({top:$(window).scrollTop()+event.clientY, left:$(window).scrollLeft()+event.clientX});
	} else {
		$("div#contextMenu").offset({top:$(window).scrollTop()+event.clientY, left:$(window).scrollLeft()+event.clientX-338});
	}
	$("body").bind('contextmenu click', contextMenuClose);
	$(document).bind('keyup', contextMenuClose);
}

export function contextMenuClose(el) {
	if (el != undefined && $(el.target).closest("#contextMenu").length > 0) {
		return;
	}

	if (el != undefined && el.type=='keyup') {
		if(el.keyCode !== 27) {
			return;
		}
	}

	
	$(document).unbind('keyup', contextMenuClose);
	$("body").unbind('contextmenu click', contextMenuClose);
	$("div#contextMenu").hide().unbind('contextmenu click');
}

export function setLocation(curLoc){
    try {
      history.pushState(null, null, curLoc);
      return;
    } catch(e) {}
    location.hash = '#' + curLoc;    
}

export function xClick(ref) {
	return smartClick(ref);
};

Number.prototype.pad = function(size) {
    var s = String(this);
    while (s.length < (size || 2)) {s = "0" + s;}
    return s;
};

(function($){
 	$.fn.extend({ 
		
 		foxPager: function(cmd, value) {
			if (cmd===undefined) { cmd = {}; }
			var mode = undefined;
			if (typeof(cmd) == 'object' && value===undefined) {
				// initialize
				mode='init';
				 
				var defaults={
					page: undefined,
					pages: 0,
					prefix: '',
					callback: function() {},
				}
				
				var options =  $.extend(defaults, cmd);
				
				
				if (!options.page) {
					options.page = (!sessionStorage.getItem(options.prefix+"pager") || sessionStorage.getItem(options.prefix+"pager").replace(/[^0-9]/g,'')=='')?1:sessionStorage.getItem(options.prefix+"pager");
				}
				if (options.page > options.pages && options.pages >0) { options.page = options.pages; }
				sessionStorage.setItem(options.prefix+"pager", options.page);
				
			} else {

				switch (cmd) {
					case "clear":
						mode='remove';
						break;
					case "getPage":
						mode='getPage';
						break;
					case "update":
						mode = 'update';
						if (value.page > value.pages) { value.page = value.pages; }
						var options = value;
						break;	
					default:
						return;
					
				}
			}							
			
			if (mode=='getPage') {
				return sessionStorage.getItem($(this).prop('foxPager_prefix')+"pager");
			}
    		this.each(function(rid,ref) {
				
				if (mode!='remove') {
					if (options.page!==undefined) {
						$(ref).prop('foxPager_page', options.page);
					} else {
						options.page = $(ref).prop('foxPager_page');
					}
					
					if (options.pages!==undefined) {
						$(ref).prop('foxPager_pages', options.pages); 
					} else {
						options.page = $(ref).prop('foxPager_pages');
					}				
					
					
					if (mode =='init') {
						$(ref).empty();
						$(ref).prop('foxPager_prefix', options.prefix);
						$(ref).addClass('foxPager_'+options.prefix);
						$(ref).addClass('foxPagerMain');
						
						$("<i>",{class: "fas fa-angle-double-left", css: {	padding: "0 10 0 10", cursor: 'hand' }}).click(function() {
							options.page = parseInt($(ref).prop('foxPager_page'));
							options.pages = parseInt($(ref).prop('foxPager_pages'));
							if (options.pages==0) { return; }
							if (options.page > 1) {
								options.page=1;
								sessionStorage.setItem(options.prefix+"pager", options.page);
								$(".foxPager_"+options.prefix).find(".foxPager_label").text("Стр: "+options.page+" из "+options.pages);
								$(".foxPager_"+options.prefix).prop('foxPager_page', options.page);
								options.callback({prefix: options.prefix, page: options.page});
							}
						})
						.appendTo(ref);
						$("<i>",{class: "fas fa-angle-left", css: { padding: "0 10 0 10", cursor: 'hand'} }).click(function() {
							options.page = parseInt($(ref).prop('foxPager_page'));
							options.pages = parseInt($(ref).prop('foxPager_pages'));
							if (options.pages==0) { return; }

							if (options.page > 1) {
								options.page=options.page-1;
								sessionStorage.setItem(options.prefix+"pager", options.page);
								$(".foxPager_"+options.prefix).find(".foxPager_label").text("Стр: "+options.page+" из "+options.pages);
								$(".foxPager_"+options.prefix).prop('foxPager_page', options.page);
								options.callback({prefix: options.prefix, page: options.page});
							}
						}).appendTo(ref);
						
						$("<span>",{text: "Стр: "+options.page+" из "+options.pages, class: 'foxPager_label', css: {	padding: "0 10 0 10" }}).appendTo(ref);
						
						$("<i>",{class: "fas fa-angle-right", css: {	padding: "0 10 0 10", cursor: 'hand' } }).click(function() {
							options.page = parseInt($(ref).prop('foxPager_page'));
							options.pages = parseInt($(ref).prop('foxPager_pages'));
							if (options.pages==0) { return; }

							if (options.page < options.pages) {
								options.page=options.page+1;
								sessionStorage.setItem(options.prefix+"pager", options.page);
								$(".foxPager_"+options.prefix).find(".foxPager_label").text("Стр: "+options.page+" из "+options.pages);
								$(".foxPager_"+options.prefix).prop('foxPager_page', options.page);
								options.callback({prefix: options.prefix, page: options.page});
							}
						}).appendTo(ref);
						$("<i>",{class: "fas fa-angle-double-right", css: { padding: "0 10 0 10", cursor: 'hand'	} }).click(function() {
							options.page = parseInt($(ref).prop('foxPager_page'));
							options.pages = parseInt($(ref).prop('foxPager_pages'));
							if (options.pages==0) { return; }

							if (options.page != options.pages) {
								options.page=options.pages;
								sessionStorage.setItem(options.prefix+"pager", options.page);
								$(".foxPager_"+options.prefix).find(".foxPager_label").text("Стр: "+options.page+" из "+options.pages);
								$(".foxPager_"+options.prefix).prop('foxPager_page', options.page);
								options.callback({prefix: options.prefix, page: options.page});
							}
						}).appendTo(ref);
						
					} else if (mode=='update') {
						let prefix = $(ref).prop("foxPager_prefix");
						sessionStorage.setItem(prefix+"pager", options.page);
						$(ref).prop('foxPager_page',options.page);
						$(ref).prop('foxPager_pages',options.pages);
						$(ref).find(".foxPager_label").text("Стр: "+options.page+" из "+options.pages);
						
						return;
					}
					
					
				} else {
					$(ref).empty();
				}
			});

		},

		foxClick: function(href) {
			if (typeof(href) != 'object') {
				href={href: href};
			}
			if (href.external==true) {
				this.click(function() { 
					if (getSelectionText()) { return; }
					document.location.href=href.href; return false;
				});
			} else { 
				this.prop("href",href.href).click(function(ref) {
					if (getSelectionText()) { return; }
					xClick(ref); 
				});
			}
			this.addClass("clickable");
			$.each(this.find("td"),function(key,val) {
				if($(val).children().length == 0) {
					let item=$("<a>",{href: href.href, html: $(val).html() });
					$(val).empty().append(item);
				}
			});				
			return this;
		},
		
		onEnter: function(callback) {
			if (typeof(callback)=="function") {
				this.on('keyup', function (e) {
					if (e.key === 'Enter' || e.keyCode === 13) {
						callback(this);
					}
				})
			}
		} 
 	})})(jQuery)