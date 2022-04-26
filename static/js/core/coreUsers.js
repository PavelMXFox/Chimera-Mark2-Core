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
		tx.appendTo("div.widget.users");
		
		let i=0;
		$.each(json.data, function(idx, user) {
			i++;
			let row=$("<tr>",{});
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "", text: UI.formatInvCode(user.invCode)}).appendTo(row);
			$("<td>",{class: "", text: user.login}).appendTo(row);
			$("<td>",{class: "", text: user.eMail}).appendTo(row);
			$("<td>",{class: "", text: user.fullName}).appendTo(row);
			$("<td>",{class: "", text: user.eMailConfirmed}).appendTo(row);
			$("<td>",{class: "", text: user.active}).appendTo(row);
			
			
			$("<tbody>",{append: row}).appendTo(tx);
		});

	})
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