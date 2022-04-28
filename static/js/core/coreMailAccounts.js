export function load() {
	UI.breadcrumbsUpdate(langPack.core.breadcrumbs.modTitle+" / "+langPack.core.breadcrumbs.mailAccounts);
	
	UI.createTabsPanel({
		accounts:{"title":langPack.core.iface.mailAccounts.allTitle, preLoad: reloadList,buttons: UI.addButton({id: "btn_acctadd",icon: "fas fa-plus",title: langPack.core.iface.mailAccounts.addButtonTitle, onClick: btnAccAdd_click})},

	})
}

function btnAccAdd_click(ref) {
	var buttons={};
	buttons[langPack.core.iface.add]=async function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.register.errors,
					requestType: "PUT", 
					data: {
						address: fdata.address.val,
						rxURL: fdata.rxUrl.val,
						txURL: fdata.txUrl.val,
						login: fdata.login.val,
						password: fdata.passwd.val,
						rxFolder: fdata.rxInbox.val,
						rxArchiveFolder: fdata.rxArchive.val,
					},
					method: "core/mailAccount",
					onSuccess: function(json) {
						UI.closeDialog('addgrp');
						reloadList();
					},
				});
				
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp');}
	
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({title: langPack.core.iface.mailAccounts.address, type: "input", item: "acc_address", reqx:true}),
			UI.addField({title: langPack.core.iface.mailAccounts.login, type: "input", item: "acc_login",reqx:false}),
			UI.addField({title: langPack.core.iface.password, type: "passwordNew", item: "acc_passwd",reqx:false}),
			UI.addField({title: langPack.core.iface.mailAccounts.rxURL, type: "input", item: "acc_rxUrl",reqx:true}),
			UI.addField({title: langPack.core.iface.mailAccounts.txURL, type: "input", item: "acc_txUrl",reqx:true}),
			UI.addField({title: langPack.core.iface.mailAccounts.rxFolder, type: "input", item: "reg_rxInbox",reqx: true, val: "INBOX" }),
			UI.addField({title: langPack.core.iface.mailAccounts.archiveFolder, type: "input", item: "reg_rxArchive",reqx:true, val: "Archive"}),
			
		]),
	langPack.core.iface.add, 
	buttons,
	515,1,'addgrp');
	
	UI.openDialog('addgrp');
}

function reloadList() {
	$("div.widget.accounts").empty();
	API.exec("GET","core/mailAccount/list",{},function(json) {
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.mailAccounts.address}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.mailAccounts.login}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.mailAccounts.module}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.mailAccounts.default}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);
		
		tx.appendTo("div.widget.accounts");
		
		let i=0;
		$.each(json.data, function(idx, acc) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', mliContextMenuOpen).addClass("contextMenu").attr("accId",acc.id).attr("accAddr",acc.address);
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "", text: acc.address}).appendTo(row);
			$("<td>",{class: "", text: acc.login}).appendTo(row);
			$("<td>",{class: "", text: acc.module}).appendTo(row);
			$("<td>",{class: "", text: acc.default?langPack.core.iface.yes:langPack.core.iface.no}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(mliContextMenuOpen)
			.appendTo(row);	
			$("<tbody>",{append: row}).appendTo(tx);
		});

	})
}

function mliContextMenuOpen(el) {
	UI.contextMenuOpen(el,[
		{title: langPack.core.iface.mailAccounts.setDefault, onClick: function() {
			accDefault_Click(el);
		}},
		{title: langPack.core.iface.edit, onClick: function() {
			accEdit_Click(el);
		}},
		
		{title: langPack.core.iface.delete, onClick: function() {
			accDelete_Click(el);
		}},

		],$(el.target).closest("tr").attr("accaddr"));
	return false;
}

function accDelete_Click(ref) {
	var acid=($(ref.target).closest("tr").attr("accid"));
	var buttons={};
	buttons[langPack.core.iface.dialodDelButton]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "DELETE",
			method: "core/mailAccount/"+acid,
			onSuccess: function(json) {
				reloadList();
			},
			errDict: langPack.core.iface.mailAccounts.errors,
			});
		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog(langPack.core.iface.mailAccounts.delDialogText+" #"+acid+" "+$(ref.target).closest("tr").attr("accAddr"),langPack.core.iface.delete,buttons);
}

function accDefault_Click(ref) {
	var acid=($(ref.target).closest("tr").attr("accid"));
	var buttons={};
	buttons[langPack.core.iface.set]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "GET",
			method: "core/mailAccount/"+acid+"/setDefault",
			onSuccess: function(json) {
				reloadList();
			},
			errDict: langPack.core.iface.mailAccounts.errors,
			});
		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog(langPack.core.iface.mailAccounts.setDefaultDialogText+" #"+acid+" "+$(ref.target).closest("tr").attr("accAddr"),langPack.core.iface.set,buttons);
}

function accEdit_Click(ref) {
	var buttons={};
	var acid=($(ref.target).closest("tr").attr("accid"));

	buttons[langPack.core.iface.add]=async function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.register.errors,
					requestType: "PATCH", 
					data: {
						address: fdata.address.val,
						rxURL: fdata.rxUrl.val,
						txURL: fdata.txUrl.val,
						login: fdata.login.val,
						password: fdata.passwd.val,
						rxFolder: fdata.rxInbox.val,
						rxArchiveFolder: fdata.rxArchive.val,
					},
					method: "core/mailAccount/"+acid,
					onSuccess: function(json) {
						UI.closeDialog('addgrp');
						reloadList();
					},
				});
				
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp');}
	
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({title: langPack.core.iface.mailAccounts.address, type: "input", item: "acc_address", reqx:true}),
			UI.addField({title: langPack.core.iface.mailAccounts.login, type: "input", item: "acc_login",reqx:false}),
			UI.addField({title: langPack.core.iface.password, type: "passwordNew", item: "acc_passwd",reqx:false}),
			UI.addField({title: langPack.core.iface.mailAccounts.rxURL, type: "input", item: "acc_rxUrl",reqx:true}),
			UI.addField({title: langPack.core.iface.mailAccounts.txURL, type: "input", item: "acc_txUrl",reqx:true}),
			UI.addField({title: langPack.core.iface.mailAccounts.rxFolder, type: "input", item: "reg_rxInbox",reqx: true, val: "INBOX" }),
			UI.addField({title: langPack.core.iface.mailAccounts.archiveFolder, type: "input", item: "reg_rxArchive",reqx:true, val: "Archive"}),
			
		]),
	langPack.core.iface.add, 
	buttons,
	515,1,'addgrp');
	
	API.exec({
		errDict: langPack.core.iface.register.errors,
		requestType: "GET", 
		method: "core/mailAccount/"+acid,
		onSuccess: function(json) {
			$("#acc_address").val(json.data.address);
			$("#acc_login").val(json.data.login);
			$("#acc_rxUrl").val(json.data.rxProto+(json.data.rxSSL?"s":"")+"://"+json.data.rxServer+":"+json.data.rxPort);
			$("#acc_txUrl").val(json.data.txProto+(json.data.txSSL?"s":"")+"://"+json.data.txServer+":"+json.data.txPort);
			$("#reg_rxInbox").val(json.data.rxFolder);
			$("#reg_rxArchive").val(json.data.rxArchiveFolder);
			return false;
		},
	});

	UI.openDialog('addgrp');
}

