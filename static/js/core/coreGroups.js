import * as API from './api.js';
import * as UI from './ui.js';
import { langPack } from './langpack.js';

export function load() {
	UI.breadcrumbsUpdate(langPack.core.breadcrumbs.modTitle+" / "+langPack.core.breadcrumbs.groups);
	
	UI.createTabsPanel({
		groups:{"title":langPack.core.iface.groups.allTitle, preLoad: reloadGroups,buttons: UI.addButton({id: "btn_groupadd",icon: "fas fa-plus",title: langPack.core.iface.groups.addButtonTitle, onClick: btnGroupAdd_click})},

	})
}

function reloadGroups() {
	$("div.widget.groups").empty();
	API.exec("GET","core/userGroup/list",{},function(json) {
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.title}).appendTo(tx);
		$("<th>",{class: "icon", text: langPack.core.iface.groups.isList}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);	
		
		tx.appendTo("div.widget.groups");
		
		let i=0;
		$.each(json.data, function(idx, group) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', usmContextMenuOpen).addClass("contextMenu").attr("groupId",group.id).attr("xname",group.name);
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "xGroupName", text: group.name}).appendTo(row);
			$("<td>",{class: "icon", text: group.isList}).appendTo(row);
			//$("<td>",{class: "button", append: UI.addButton({id: "btn_group_del_"+group.id,title: langPack.core.iface.groups.delButtonTitle, icon: "fas fa-trash",onClick: btnGroupDel_click, attr: {groupId: group.id, xname: group.name}})}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(usmContextMenuOpen)
			.appendTo(row);	
			row.foxClick("/"+UI.parceURL().module+"/group/"+group.id);
			
			$("<tbody>",{append: row}).appendTo(tx);
		});
		

	})
	
	
}

function usmContextMenuOpen(el) {

	let groupName=$(el.target).closest("tr").find("td.xGroupName").text();
	UI.contextMenuOpen(el,[
		{title: langPack.core.iface.open, onClick: function() {
			$(el.currentTarget).closest("tr").click();
		}},
				{title: langPack.core.iface.delete, onClick: function() {
			btnGroupDel_click(el);
		}},

		],groupName);
	
	return false;
}


function btnGroupAdd_click() {
	var buttons={};
	
	buttons[langPack.core.iface.dialodAddButton]=function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.groups.errors,
					requestType: "PUT", 
					data: {
						name: fdata.name.val,
						isList: fdata.list.val
					},
					method: "core/userGroup",
					onSuccess: function(json) {
						UI.closeDialog('addgrp');
						reloadGroups();
					}
				});
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp'); }
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({item: "dag_name", title: langPack.core.iface.title, type: "input", reqx: "true"}),
			UI.addField({item: "dag_list", title: langPack.core.iface.groups.isList, type: "select",args: {"0":"ACL", "1": langPack.core.iface.groups.isList}}),
		]),
	langPack.core.iface.dialodAddButton, 
	buttons,
	245,1,'addgrp');
	
	
	UI.openDialog('addgrp')
}

function btnGroupDel_click(ref) {
	var gid=($(ref.currentTarget).attr("groupId"));
		var buttons={};
		buttons[langPack.core.iface.dialodDelButton]=function() { 
			$("#dialogInfo").dialog("close");
			API.exec({
				requestType: "DELETE",
				method: "core/userGroup/"+gid,
				onSuccess: function(json) {
					reloadGroups();
				},
				errDict: langPack.core.iface.groups.errors,
				});
			
		};
		
		buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }
	
		UI.showInfoDialog(langPack.core.iface.groups.delButtonTitle+" #"+gid+" "+$(ref.currentTarget).attr("xname"),langPack.core.iface.groups.delButtonTitle,buttons);
}