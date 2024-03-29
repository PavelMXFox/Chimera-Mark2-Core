import * as API from './api.js';
import * as UI from './ui.js';
import { langPack } from './langpack.js';

export function load() {
	UI.breadcrumbsUpdate(langPack.core.breadcrumbs.modTitle+" / "+langPack.core.breadcrumbs.users);
	
	UI.createTabsPanel({
		users:{"title":langPack.core.iface.users.allTitle, preLoad: reloadUsers},
		invites:{"title":langPack.core.iface.users.invitesTitle, preLoad: reloadInvites,buttons: UI.addButton({title: langPack.core.iface.users.inviteButtonTitle, icon: "fas fa-plus", onClick: btnInviteUser_Click})},

	})
}

function reloadUsers() {
	$("div.widget.users").empty();
	API.exec("GET","core/user/list",{},function(json) {
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.invCode}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.login}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.email}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.fullName}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.eMainConfirmedQTitle}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.active}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);
		tx.appendTo("div.widget.users");
		
		let i=0;
		$.each(json.data, function(idx, user) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', usmContextMenuOpen).addClass("contextMenu").attr("userId",user.id).attr("xenabled",user.active?1:0).attr("xemconf",user.eMailConfirmed?1:0);
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "xInvCode", text: UI.formatInvCode(user.invCode)}).appendTo(row);
			$("<td>",{class: "", text: user.login}).appendTo(row);
			$("<td>",{class: "", text: user.eMail}).appendTo(row);
			$("<td>",{class: "xFullName", text: user.fullName}).appendTo(row);
			$("<td>",{class: "", text: user.eMailConfirmed}).appendTo(row);
			$("<td>",{class: "", text: user.active}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(usmContextMenuOpen)
			.appendTo(row);	
			//row.foxClick("/"+UI.parceURL().module+"/user/"+user.id);
			
			$("<tbody>",{append: row}).appendTo(tx);
		});

	})
}

function usmContextMenuOpen(el) {
	let userUID=$(el.target).closest("tr").find("td.xInvCode").text();
	let userName=$(el.target).closest("tr").find("td.xFullName").text();
	let userEnabled=$(el.target).closest("tr").attr("xenabled")==1;
	let xemconf=$(el.target).closest("tr").attr("xemconf")==1;
	let userId=$(el.target).closest("tr").attr("userId");

	let menuitems=[];
	if (userEnabled) {
		menuitems.push({title: langPack.core.iface.disable, onClick: function() {
			toggleUserEnabled(userId,false, el)
		}})
	} else {
		menuitems.push({title: langPack.core.iface.enable, onClick: function() {
			toggleUserEnabled(userId,true, el);
		}})
	}

	menuitems.push({title: langPack.core.iface.delete, onClick: function() {
		deleteUser(userId, el);
	}});

	if (!xemconf && userEnabled) {
		menuitems.push({title: langPack.core.iface.users.resendConfirmationCode, onClick: function() {
			API.exec({
				requestType: "GET",
				method: "core/user/"+userId+"/sendEMailConfirmation",
			})
		}});
	}

	UI.contextMenuOpen(el,menuitems,userUID+" "+userName);
	
	return false;
}

function toggleUserEnabled(userId, state, ref) {

	var buttons={};
	buttons[langPack.core.iface.dialodSaveButton]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "PATCH",
			method: "core/user/"+userId,
			data: {"enabled": state==true?1:0 },
			onSuccess: reloadUsers,
			errDict: langPack.core.iface.groups.errors,
		});		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog(langPack.core.iface[state?"enable":"disable"]+" #"+userId+" "+$(ref.target).closest("tr").find("td.xFullName").text()+"?",langPack.core.iface[state?"enable":"disable"],buttons);


}

function deleteUser(userId, ref) {

	var buttons={};
	buttons[langPack.core.iface.dialodDelButton]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "DELETE",
			method: "core/user/"+userId,
			onSuccess: reloadUsers,
			errDict: langPack.core.iface.groups.errors,
		});		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog(langPack.core.iface.delete+" #"+userId+" "+$(ref.target).closest("tr").find("td.xFullName").text()+"?",langPack.core.iface.delete,buttons);



}

function reloadInvites() {
	$("div.widget.invites").empty();
	API.exec("GET","core/userInvitation/list",{},function(json) {
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.regCode}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.email}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.allowMultiUseQTitle}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.invitationExpire}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.users.inviteToGroupTitleQ}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);	
		tx.appendTo("div.widget.invites");
		
		let i=0;
		$.each(json.data, function(idx, user) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', uimContextMenuOpen).addClass("contextMenu").attr("invId",user.id).attr("xname",formatRegCode(user.regCode));
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "code", text: formatRegCode(user.regCode)}).appendTo(row);
			$("<td>",{class: "", text: user.eMail}).appendTo(row);
			$("<td>",{class: "", text: user.allowMultiUse?langPack.core.iface.yes:langPack.core.iface.no}).appendTo(row);
			$("<td>",{class: "", text: UI.stamp2date(user.expireStamp,true)}).appendTo(row);
			$("<td>",{class: "", text: user.joinGroupsId.length>0?langPack.core.iface.yes:langPack.core.iface.no}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(uimContextMenuOpen)
			.appendTo(row);	
			
			$("<tbody>",{append: row}).appendTo(tx);
		});

	})
}

function uimContextMenuOpen(el) {
	let xName=$(el.target).closest("tr").attr("xname");
	let xId=$(el.target).closest("tr").attr("invId");
	UI.contextMenuOpen(el,[
		{title: langPack.core.iface.delete, onClick: function() {
			var buttons={};
			buttons[langPack.core.iface.dialodDelButton]=function() { 
				$("#dialogInfo").dialog("close");
				API.exec({
					requestType: "DELETE",
					method: "core/userInvitation/"+xId,
					onSuccess: function(json) {
						reloadInvites();
					},
					errDict: langPack.core.iface.users.errors,
				});
				
			};
			
			buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }
		
			UI.showInfoDialog(langPack.core.iface.users.delInvDialogTitle+" "+xName,langPack.core.iface.users.delInvDialogTitle,buttons);
		}},
	],xName);
	
	return false;
}

function formatRegCode(code) {
	if ((typeof(code) != 'string') ||code.length==0) {
		return langPack.core.iface.emptyInvCode;	
	} else {
		return code.substr(0,4)+"-"+code.substr(4,4)+"-"+code.substr(8,4)+"-"+code.substr(12);
	}
}

function btnInviteUser_Click(ref) {
	var buttons={};
	
	buttons[langPack.core.iface.dialodAddButton]=function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.users.errors,
					requestType: "PUT", 
					data: {
						eMail: fdata.email.val,
						expireStamp: isset(fdata.expiration.val)?UI.date2stamp(fdata.expiration.val):undefined,
						allowMultiUse: fdata.type.val,
					},
					method: "core/userInvitation",
					onSuccess: function(json) {
						UI.closeDialog('addgrp');
						reloadInvites();
					}
				});
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp'); }
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({item: "dag_email", title: langPack.core.iface.users.email, type: "input"}),
			UI.addField({item: "dag_type", title: langPack.core.iface.users.allowMultiUseTitle, type: "select",args: {"false":langPack.core.iface.no, "true": langPack.core.iface.yes}}),
			UI.addField({item: "dag_expiration", title: langPack.core.iface.users.invitationExpire, type: "datetime", args: {curr: UI.stamp2isodate(UI.getUnixTime(1209600))}}),
		]),
	langPack.core.iface.dialodAddButton, 
	buttons,
	325,1,'addgrp');
	
	
	UI.openDialog('addgrp')
}