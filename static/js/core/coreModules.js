import * as API from './api.js';
import * as UI from './ui.js';
import { langPack } from './langpack.js';

export function load() {
	UI.breadcrumbsUpdate(langPack.core.breadcrumbs.modTitle+" / "+langPack.core.breadcrumbs.modules);
	
	UI.createTabsPanel({
		installed:{"title":langPack.core.iface.modules.installedTabTitle, preLoad: reloadInstalled},
		avail:{"title":langPack.core.iface.modules.availTabTitle, preLoad: reloadAvail},
	})
}

function reloadInstalled() {
	$("div.widget.installed").empty();
	API.exec("GET","core/modules/installed",{},function(json) {
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.title}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.active}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.modules.instanceOf}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.version}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.desc}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.installed}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.updated}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);
		tx.appendTo("div.widget.installed");
		
		let i=0;
		$.each(json.data, function(idx, mod) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', mliContextMenuOpen).addClass("contextMenu").attr("modName",mod.name).attr("modTitle",mod.title);
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "", text: mod.name}).appendTo(row);
			$("<td>",{class: "", text: mod.enabled}).appendTo(row);
			$("<td>",{class: "", text: mod.instanceOf}).appendTo(row);
			$("<td>",{class: "", text: mod.modVersion}).appendTo(row);
			$("<td>",{class: "", text: mod.title}).appendTo(row);
			$("<td>",{class: "", text: UI.stamp2date(mod.installDate,true)}).appendTo(row);
			$("<td>",{class: "", text: UI.stamp2date(mod.updateDate,true)}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(mliContextMenuOpen)
			.appendTo(row);	
			row.foxClick("/"+UI.parceURL().module+"/module/"+mod.name);
			
			
			$("<tbody>",{append: row}).appendTo(tx);
		});

	})
}

function mliContextMenuOpen(el) {

	let groupName=$(el.target).closest("tr").find("td.xGroupName").text();
	UI.contextMenuOpen(el,[
		{title: langPack.core.iface.open, onClick: function() {
			$(el.currentTarget).closest("tr").click();
		}},
				{title: langPack.core.iface.delete, onClick: function() {
			moduleDelete_Click(el);
		}},

		],$(el.target).closest("tr").attr("modName"));
	
	return false;
}

function mlaContextMenuOpen(el) {

	let groupName=$(el.target).closest("tr").find("td.xGroupName").text();
	UI.contextMenuOpen(el,[
		{title: langPack.core.iface.install, onClick: function() {
			moduleInstall_Click(el);
		}},

		],$(el.target).closest("tr").attr("modName"));
	
	return false;
}

function moduleInstall_Click(el) {
	var buttons={};
	
	buttons[langPack.core.iface.dialodAddButton]=function() {
			let fdata=UI.collectForm("addmod", true,false, false,true);
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.modules.errors,
					requestType: "PUT", 
					data: {
						module: $(el.target).closest("tr").attr("modName"),
						name: fdata.name.val,
						priority: fdata.priority.val,
					},
					method: "core/modules/installed",
					onSuccess: function(json) {
						UI.closeDialog('addmod');
						reloadInstalled();
						reloadAvail();
					}
				});
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addmod'); }
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({item: "mod_name", title: langPack.core.iface.title, type: "input", reqx: "true", reqx: "true", regx: "^[A-Za-z0-9_-]*$", val: $(el.target).closest("tr").attr("modName")}),
			UI.addField({item: "mod_priority", title: langPack.core.iface.modules.priority, type: "input", reqx: "true", regx: "^[0-9]*$",  val: 100}),
		]),
	langPack.core.iface.dialodAddButton, 
	buttons,
	245,1,'addmod');

	UI.openDialog('addmod')
}

function moduleDelete_Click(ref) {
	var modName=($(ref.target).closest("tr").attr("modName"));
		var buttons={};
		buttons[langPack.core.iface.dialodDelButton]=function() { 
			$("#dialogInfo").dialog("close");
			API.exec({
				requestType: "DELETE",
				method: "core/moduleInfo/"+$(ref.target).closest("tr").attr("modName"),
				onSuccess: function(json) {
					reloadInstalled();
					reloadAvail();
				},
				errDict: langPack.core.iface.modules.errors,
				});
			
		};
		
		buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }
	
		UI.showInfoDialog(langPack.core.iface.modules.delDialogText+" #"+modName+"?",langPack.core.iface.dialodDelButton,buttons);
}

function reloadAvail() {
	$("div.widget.avail").empty();
	API.exec("GET","core/modules/list",{},function(json) {
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.title}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.version}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.desc}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.modules.singleInstanceOnly}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.modules.instancesCount}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);

		tx.appendTo("div.widget.avail");
		let i=0;
		$.each(json.data, function(idx, mod) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', mlaContextMenuOpen).addClass("contextMenu").attr("modName",mod.name).attr("modTitle",mod.title);
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "", text: mod.name}).appendTo(row);
			$("<td>",{class: "", text: mod.modVersion}).appendTo(row);
			$("<td>",{class: "", text: mod.title}).appendTo(row);
			$("<td>",{class: "", html: mod.singleInstanceOnly?'<i class="far fa-check-square"></i>':""}).appendTo(row);
			$("<td>",{class: "", text: mod.instancesCount}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(mlaContextMenuOpen)
			.appendTo(row);	
			
			$("<tbody>",{append: row}).appendTo(tx);
		});
	})
}