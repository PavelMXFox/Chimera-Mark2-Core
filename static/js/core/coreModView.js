import * as API from './api.js';
import * as UI from './ui.js';
import { langPack } from './langpack.js';

export function load() {
	UI.createLeftPanel([{id: "gendesc", title: langPack.core.iface.genDescTitle, }]);
	UI.createRightPanel([{id: "aclRules", title: langPack.core.iface.groups.aclRule},
					     {id: "features", title: langPack.core.iface.modules.features, preLoad: reloadFeatures },
					     {id: "configKeys", title: langPack.core.iface.modules.settings, preLoad: reloadConfig },
					     ]);
	
	reloadGenDesc();

}

function reloadGenDesc() {
	API.exec("GET","core/moduleInfo/"+UI.parceURL().id,{},function(json) {
			$("div.widget#gendesc").empty();
			UI.addFieldGroup([
				/*
				
				>> ACLRules >> tab
				>> features >> tab
				>> configKeys >> tab

				
				
				*/
				UI.addField({title: langPack.core.iface.modules.globalAccessKey, val: json.data.globalAccessKey}),
				UI.addField({title: langPack.core.iface.installed, val: json.data.installDate}),
				UI.addField({title: langPack.core.iface.modules.instanceOf, val: json.data.instanceOf}),
				UI.addField({title: langPack.core.iface.language, val: json.data.languages}),
				UI.addField({title: langPack.core.iface.modules.priority, val: json.data.modPriority}),
				UI.addField({title: langPack.core.iface.version, val: json.data.modVersion}),
				UI.addField({title: langPack.core.iface.title, val: json.data.name}),
				UI.addField({title: langPack.core.iface.modules.namespace, val: json.data.namespace}),
				UI.addField({title: langPack.core.iface.desc, val: json.data.title}),
				UI.addField({title: langPack.core.iface.updated, val: json.data.updateDate}),
				
				UI.addField({title: langPack.core.iface.modules.authRequired, val: json.data.authRequired?langPack.core.iface.yes:langPack.core.iface.no}),
				UI.addField({title: langPack.core.iface.active, val: json.data.enabled?langPack.core.iface.yes:langPack.core.iface.no}),
				UI.addField({title: langPack.core.iface.template, val: json.data.isTemplate?langPack.core.iface.yes:langPack.core.iface.no}),
				UI.addField({title: langPack.core.iface.modules.multiInstanceAllowed, val: json.data.singleInstanceOnly?langPack.core.iface.no:langPack.core.iface.yes}),
			]).appendTo("div.widget#gendesc")
			
			$("div.widget.aclRules").empty();
			let tx = $("<table>",{class: "datatable sel"});
			$("<th>",{class: "idx", text: "#"}).appendTo(tx);
			$("<th>",{class: "", text: langPack.core.iface.title}).appendTo(tx);
			$("<th>",{class: "", text: langPack.core.iface.desc}).appendTo(tx);

			tx.appendTo("div.widget.aclRules");
			
			let i=0;
			$.each(json.data.ACLRules, function(idx, mod) {
				i++;
				let row=$("<tr>",{});//.bind('contextmenu', mliContextMenuOpen).addClass("contextMenu").attr("modName",mod.name).attr("modTitle",mod.title);
				$("<td>",{class: "idx", text: i}).appendTo(row);
				$("<td>",{class: "", text: idx}).appendTo(row);
				$("<td>",{class: "", text: mod}).appendTo(row);
								
				
				$("<tbody>",{append: row}).appendTo(tx);
			});
			
	});
}

function reloadConfig () {
	API.exec("GET","core/moduleInfo/"+UI.parceURL().id+"/config",{},function(json) {
		$("div.widget.configKeys").empty();
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.title}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.desc}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.value}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);
	
		tx.appendTo("div.widget.configKeys");
		
		let resConfig=json.data.keys;
		$.each(json.data.values,function(key,val) {
			if (resConfig[key]==undefined) { resConfig[key]=key; }
		});
		
		let i=0;
		$.each(resConfig, function(idx, mod) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', mlcContextMenuOpen).addClass("contextMenu").attr("xKey",idx).attr("xSet",isset(json.data.values[idx]));
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "", text: idx}).appendTo(row);
			$("<td>",{class: "", text: mod}).appendTo(row);
			$("<td>",{class: "", text: json.data.values[idx]}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(mlcContextMenuOpen)	
			.appendTo(row);
			
			$("<tbody>",{append: row}).appendTo(tx);
		});
	});
}

function reloadFeatures () {
	API.exec("GET","core/moduleInfo/"+UI.parceURL().id+"/features",{},function(json) {
		$("div.widget.features").empty();
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.title}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.active}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);
	
		tx.appendTo("div.widget.features");
		
		let i=0;
		$.each(json.data, function(idx, mod) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', mlfContextMenuOpen).addClass("contextMenu").attr("xFeature",idx).attr("xActive",mod);
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "", text: idx}).appendTo(row);
			$("<td>",{class: "", text: mod}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(mlfContextMenuOpen)
			.appendTo(row);
							
			
			$("<tbody>",{append: row}).appendTo(tx);
		});
	});
}

function mlfContextMenuOpen(el) {
	let feature=$(el.target).closest("tr").attr("xFeature");
    let active=$(el.target).closest("tr").attr("xActive")=="true";
    
    let menuItems=[];
    
    
    if (active) {
		menuItems.push({title: langPack.core.iface.delete, onClick: function() {
		let buttons={};
		buttons[langPack.core.iface.dialodDelButton]=function() { 
			$("#dialogInfo").dialog("close");
			API.exec({
				requestType: "DELETE",
				method: "core/moduleInfo/"+UI.parceURL().id+"/features",
				data: {feature: feature},
				onSuccess: function(json) {
					reloadFeatures();
				},
				errDict: langPack.core.iface.modules.errors,
				});
			
		};
		
		buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }
	
		UI.showInfoDialog(langPack.core.iface.modules.delFeatureDialogText+" #"+feature+"?",langPack.core.iface.dialodDelButton,buttons);
		}},
		);
    } else {
		menuItems.push({title: langPack.core.iface.add, onClick: function() {
		let buttons={};
		buttons[langPack.core.iface.dialodAddButton]=function() { 
			$("#dialogInfo").dialog("close");
			API.exec({
				requestType: "PUT",
				method: "core/moduleInfo/"+UI.parceURL().id+"/features",
				data: {feature: feature},
				onSuccess: function(json) {
					reloadFeatures();
				},
				errDict: langPack.core.iface.modules.errors,
				});
			
		};
		
		buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }
	
		UI.showInfoDialog(langPack.core.iface.modules.addFeatureDialogText+" #"+feature+"?",langPack.core.iface.dialodDelButton,buttons);
		}},
		);
	}
    
	UI.contextMenuOpen(el,menuItems,feature);
	
	return false;
}

function mlcContextMenuOpen(el) {
	let key=$(el.target).closest("tr").attr("xKey");
	let active=$(el.target).closest("tr").attr("xSet")=="true";
	
	let menuItems=[];
	if (active) {
		menuItems.push({title: langPack.core.iface.delete, onClick: function() {
			let buttons={};
		buttons[langPack.core.iface.dialodDelButton]=function() { 
			$("#dialogInfo").dialog("close");
			API.exec({
				requestType: "DELETE",
				method: "core/moduleInfo/"+UI.parceURL().id+"/config",
				data: {key: key},
				onSuccess: function(json) {
					reloadConfig();
				},
				errDict: langPack.core.iface.modules.errors,
				});
			
		};
		
		buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }
	
		UI.showInfoDialog(langPack.core.iface.modules.delConfigDialogText+" #"+key+"?",langPack.core.iface.dialodDelButton,buttons);
		}});
	}
	
	menuItems.push({title: langPack.core.iface.set, onClick: function() {
		var buttons={};
	
		buttons[langPack.core.iface.dialodAddButton]=function() {
				let fdata=UI.collectForm("addcfg", true,false, false,true);
				if (fdata.validateErrCount==0) {
					API.exec({
						errDict: langPack.core.iface.groups.errors,
						requestType: "PUT", 
						data: {
							value: fdata.val.val,
							key: key,
						},
						method: "core/moduleInfo/"+UI.parceURL().id+"/config",
						onSuccess: function(json) {
							UI.closeDialog('addcfg');
							reloadConfig();
						}
					});
				}
			};
		buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addcfg'); }
		
		UI.createDialog(
			UI.addFieldGroup([
				UI.addField({title: langPack.core.iface.title, val: key}),
				UI.addField({item: "cfg_val", title: langPack.core.iface.title, type: "input", reqx: "true"}),
			]),
		langPack.core.iface.dialodAddButton, 
		buttons,
		245,1,'addcfg');
		
		
		UI.openDialog('addcfg')
	}});
	

	    
    
	UI.contextMenuOpen(el,menuItems,key);
	
	return false;
}
