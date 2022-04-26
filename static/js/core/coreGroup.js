import * as API from './api.js';
import * as UI from './ui.js';
import { langPack } from './langpack.js';

export function load() {
	UI.createLeftPanel([{id: "gendesc", title: langPack.core.iface.genDescTitle, }]);
	UI.createRightPanel([{id: "members", title: langPack.core.iface.groups.membersTitle, preLoad: reloadMembers, buttons: UI.addButton({id: "btnAddUGM", title: "ADD", icon: "fas fa-plus", onClick: btnUserpAdd_click})},
					     {id: "acls", title: langPack.core.iface.groups.aclRulesTitle, preLoad: reloadACLs, buttons: UI.addButton({id: "btnAddACL", title: "ADD ACL", icon: "fas fa-plus", onClick: btnAclAdd_click})},
					     ]);
	
	reloadGenDesc();

}

function reloadGenDesc() {
	API.exec("GET","core/userGroup/"+UI.parceURL().id,{},function(json) {
			$("div.widget#gendesc").empty();
			UI.addFieldGroup([
				UI.addField({title: langPack.core.iface.title, val: json.data.name}),
				UI.addField({title: langPack.core.iface.groups.isList, val: json.data.isList?langPack.core.iface.yes:langPack.core.iface.no}),
			]).appendTo("div.widget#gendesc")
	});
}

function reloadACLs() {
	$("div.widget.acls").empty();
	API.exec("GET","core/userGroup/"+UI.parceURL().id+"/acls",{},function(json) {
			let tx = $("<table>",{class: "datatable sel"});
			$("<th>",{class: "idx", text: "#"}).appendTo(tx);
			$("<th>",{class: "", text: langPack.core.iface.module}).appendTo(tx);
			$("<th>",{class: "", text: langPack.core.iface.groups.aclRule}).appendTo(tx);
			$("<th>",{class: "button", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);
			tx.appendTo("div.widget.acls");

			let i=0;

			$.each(json.data, function(mKey, mVal) {

				$.each(mVal, function(aKey, aVal) {

					i++;
					let row=$("<tr>",{}).bind('contextmenu', aclContextMenuOpen).addClass("contextMenu").attr("xMod",mKey).attr("xRule",aVal);
					$("<td>",{class: "idx", text: i}).appendTo(row);
					$("<td>",{class: "xModule", text: mKey}).appendTo(row);
					$("<td>",{class: "xRule", text: aVal}).appendTo(row);
					$("<td>",{class: "button", append: $("<i>",{class: "fas fa-ellipsis-h"})})
					.click(aclContextMenuOpen)
					.appendTo(row);	
					
					
					$("<tbody>",{append: row}).appendTo(tx);
					
				});
			})
	});
}

function reloadMembers() {
	$("div.widget.members").empty();
	API.exec("POST","core/userGroupMembership/search",{groupId: UI.parceURL().id},function(json) {
		
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.invCode}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.fullName}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.active}).appendTo(tx);
		$("<th>",{class: "button", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);
		tx.appendTo("div.widget.members");
		
		let i=0;
		$.each(json.data, function(idx, ugm) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', usmContextMenuOpen).addClass("contextMenu").attr("userId",ugm.user.id);
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "", text: UI.formatInvCode(ugm.user.invCode)}).appendTo(row);
			$("<td>",{class: "xFullName", text: ugm.user.fullName}).appendTo(row);
			$("<td>",{class: "", text: ugm.user.active}).appendTo(row);
			$("<td>",{class: "button", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(usmContextMenuOpen)
			.appendTo(row);	
			
			
			$("<tbody>",{append: row}).appendTo(tx);
		});

	})
}

function usmContextMenuOpen(el) {

	let userId=$(el.target).closest("tr").attr("userId");
	let userFullName=$(el.target).closest("tr").find("td.xFullName").text();
	UI.contextMenuOpen(el,[
		{title: langPack.core.iface.delete, onClick: function(el) {
			btnUserpDel_click(userId, userFullName);
		}}],userFullName);
	
	return false;
}

function aclContextMenuOpen(el) {

	var mod=$(el.target).closest("tr").attr("xMod");
	let rule=$(el.target).closest("tr").attr("xRule");
	UI.contextMenuOpen(el,[
		{title: langPack.core.iface.delete, onClick: function(el) {
			btnAclDel_click(mod, rule);
		}}],mod+":"+rule);
	
	return false;
}

function btnUserpDel_click(userId, fullName) {
	var buttons={};
	buttons[langPack.core.iface.dialodDelButton]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "DELETE",
			method: "core/userGroupMembership",
			data: {groupId: UI.parceURL().id, userId: userId},
			onSuccess: function(json) {
				reloadMembers();
			},
			errDict: langPack.core.iface.groups.errors,
			});
		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog({message: langPack.core.iface.groups.deleteUserFromGroupDialog+"</br> #"+userId+" "+fullName,buttons: buttons});

}

function btnAclDel_click(mod, rule) {
	console.log(mod,rule);
	var buttons={};
	buttons[langPack.core.iface.dialodDelButton]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "DELETE",
			method: "core/userGroup/acl",
			data: {groupId: UI.parceURL().id,module: mod, rule: rule},
			onSuccess: function(json) {
				reloadACLs();
			},
			errDict: langPack.core.iface.groups.errors,
			});
		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog({message: langPack.core.iface.delete+"</br> #"+mod+":"+rule,buttons: buttons});

}


function btnUserpAdd_click() {
	var buttons={};
	var xid=UI.parceURL().id;
	
	buttons[langPack.core.iface.dialodAddButton]=function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.groups.errors,
					requestType: "PUT", 
					data: {
						userId: fdata.name_id.val,
						groupId: xid
					},
					method: "core/userGroupMembership",
					onSuccess: function(json) {
						UI.closeDialog('addgrp');
						reloadMembers();
					}
				});
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp'); }
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({item: "dag_name", title: langPack.core.iface.title, type: "autocomplete", reqx: "true",acFunction: function(request,response) {

				API.exec({
					requestType: "POST",
					method: "core/user/search",
					data: {pattern: request.term, pageSize: 10},
					onSuccess: function(json) {
						let rv=[];
						$.each(json.data,function(key,val) {
							rv.push({id: val.id, value: val.fullName});
						});
						response(rv);
						return false;
					},
					errDict: langPack.core.iface.groups.errors,
				});
			}}),
		]),
	langPack.core.iface.add, 
	buttons,
	185,1,'addgrp');
	
	
	UI.openDialog('addgrp')
}

function btnAclAdd_click() {
	var buttons={};
	var xid=UI.parceURL().id;
	var modules=[];
	
	buttons[langPack.core.iface.dialodAddButton]=function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.groups.errors,
					requestType: "PUT", 
					data: {
						groupId: xid,
						module: fdata.module.val,
						rule: fdata.rule.val,
						forAll: fdata.all.val
					},
					method: "core/userGroup/acl",
					onSuccess: function(json) {
						UI.closeDialog('addgrp');
						reloadACLs();
					}
				});
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp'); }
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({item: "acl_module", title: langPack.core.iface.module, type: "select", reqx: "true",args: {"":"--- "+langPack.core.iface.groups.selectModule+" ---"}}),
			UI.addField({item: "acl_rule", title: langPack.core.iface.groups.aclRule, type: "select", reqx: "true"}),
			UI.addField({item: "acl_all", title: langPack.core.iface.groups.allModules, type: "select",args: {0: langPack.core.iface.no, 1: langPack.core.iface.yes}}),
		]),

	langPack.core.iface.add, 
	buttons,
	300,1,'addgrp');

	$("#acl_module").change(function(ref) {
		let mod=($(ref.currentTarget).val());
		$("#acl_rule").empty();
		if (mod=="") {
			return;
		}
		
		$.each(modules[mod].ACLRules, function(key,val) {
			$("<option>",{val: key, text: val}).appendTo("#acl_rule");
		});
	});
	
	API.exec({
		requestType: "GET",
		method: "core/modules/installed",
		onSuccess: function(json) {
			modules=json.data;
			let modList=$("#acl_module").empty();
			$("<option>",{val: "", text: "--- "+langPack.core.iface.groups.selectModule+" ---"}).appendTo(modList);
			/* $("<option>",{val: "<all>", text: langPack.core.iface.groups.allModules}).appendTo(modList); */
			$.each(modules, function(mod, val) {
				
				if (Object.keys(val.ACLRules).length>0) {
					$("<option>",{val: val.name, text: val.name+" ("+val.title+")"}).appendTo(modList);
				}
			});
			return false;
		},
		errDict: langPack.core.iface.groups.errors,
	});

	
	UI.openDialog('addgrp')
}