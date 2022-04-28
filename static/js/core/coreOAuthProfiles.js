export function load() {
	UI.breadcrumbsUpdate(langPack.core.breadcrumbs.modTitle+" / "+langPack.core.breadcrumbs.oauth);
	
	UI.createTabsPanel({
		methods:{"title":langPack.core.iface.oauth.allTitle, preLoad: reloadList,buttons: UI.addButton({id: "btn_acctadd",icon: "fas fa-plus",title: langPack.core.iface.oauth.addButtonTitle, onClick: btnAdd_click})},

	})
}

function reloadList(ref) {
		$("div.widget.methods").empty();
	API.exec("GET","core/oAuthProfile/list",{},function(json) {
		let tx = $("<table>",{class: "datatable sel"});
		$("<th>",{class: "idx", text: "#"}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.title}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.url}).appendTo(tx);
		$("<th>",{class: "", text: langPack.core.iface.active}).appendTo(tx);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(tx);
		
		tx.appendTo("div.widget.methods");
		
		let i=0;
		$.each(json.data, function(idx, acc) {
			i++;
			let row=$("<tr>",{}).bind('contextmenu', mliContextMenuOpen).addClass("contextMenu").attr("accActive",acc.enabled?1:0).attr("accHash",acc.hash).attr("accId",acc.id).attr("accName",acc.name);
			$("<td>",{class: "idx", text: i}).appendTo(row);
			$("<td>",{class: "", text: acc.name}).appendTo(row);
			$("<td>",{class: "", text: acc.url}).appendTo(row);
			$("<td>",{class: "", text: acc.enabled?langPack.core.iface.yes:langPack.core.iface.no}).appendTo(row);
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(mliContextMenuOpen)
			.appendTo(row);	
			$("<tbody>",{append: row}).appendTo(tx);
		});

	})
}

function mliContextMenuOpen(el) {
	
	let menuItems=[];
	let menuOnOff;
	if ($(el.target).closest("tr").attr("accActive")==1) {
		menuOnOff= {title: langPack.core.iface.disable, onClick: function() { profileDisable_Click(el) ; }};
	} else {
		menuOnOff= {title: langPack.core.iface.enable, onClick: function()  { profileEnable_Click(el);}};
	}
	menuItems.push(
		{title: "CopyURL", onClick: function() { UI.copySelText(getCallbackUrl($(el.target).closest("tr").attr("accHash")));}},
		menuOnOff,
		{title: langPack.core.iface.edit, onClick: function() { profileEdit_Click(el); }},
		{title: langPack.core.iface.delete, onClick: function() { profileDelete_Click(el);}},
		);
	UI.contextMenuOpen(el,menuItems,$(el.target).closest("tr").attr("accName"));
	return false;
}

function profileDisable_Click(ref) {
	var acid=($(ref.target).closest("tr").attr("accId"));
	var buttons={};
	buttons[langPack.core.iface.disable]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "GET",
			method: "core/oAuthProfile/"+acid+"/disable",
			onSuccess: function(json) {
				reloadList();
			},
			errDict: langPack.core.iface.oauth.errors,
			});
		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog(langPack.core.iface.disable+" #"+acid+" "+$(ref.target).closest("tr").attr("accName"),langPack.core.iface.disable,buttons);

}

function profileEnable_Click(ref) {
	var acid=($(ref.target).closest("tr").attr("accid"));
	var buttons={};
	buttons[langPack.core.iface.enable]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "GET",
			method: "core/oAuthProfile/"+acid+"/enable",
			onSuccess: function(json) {
				reloadList();
			},
			errDict: langPack.core.iface.oauth.errors,
			});
		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog(langPack.core.iface.enable+" #"+acid+" "+$(ref.target).closest("tr").attr("accName"),langPack.core.iface.enable,buttons);

}

function profileDelete_Click(ref) {
	var acid=($(ref.target).closest("tr").attr("accid"));
	var buttons={};
	buttons[langPack.core.iface.delete]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "DELETE",
			method: "core/oAuthProfile/"+acid,
			onSuccess: function(json) {
				reloadList();
			},
			errDict: langPack.core.iface.oauth.errors,
			});
		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog(langPack.core.iface.delete+" #"+acid+" "+$(ref.target).closest("tr").attr("accName"),langPack.core.iface.delete,buttons);

}

function btnAdd_click(ref) {
	var buttons={};
	var hash=UI.genPassword("0123456789abcdef", 64);
	buttons[langPack.core.iface.add]=async function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.register.errors,
					requestType: "PUT", 
					data: {
						name: fdata.name.val,
						url: fdata.url.val,
						clientId: fdata.clientId.val,
						clientKey: fdata.clientKey.val,
						config: fdata.type.val,
						hash: hash,
					},
					method: "core/oAuthProfile",
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
			UI.addField({title: langPack.core.iface.title, type: "input", item: "acc_name", reqx:true}),
			UI.addField({title: langPack.core.iface.url, type: "input", item: "acc_url",reqx:false}),
			UI.addField({title: langPack.core.iface.template, type: "select", item: "acc_type",reqx:true, args: {"":"--"+langPack.core.iface.oauth.selectPreset+"--","vk":"VK","yandex":"Yandex", "gitea":"Gitea", "gitlab":"Gitlab"}}),
			UI.addField({title: langPack.core.iface.oauth.callback, type: "multiline", args: {rows: 4}, item: "acc_callbackurl", disabled: true, val: "callback"}),
			UI.addField({title: langPack.core.iface.oauth.clientId, type: "input", item: "acc_clientId",reqx:true}),
			UI.addField({title: langPack.core.iface.oauth.clientKey, type: "input", item: "acc_clientKey",reqx:true}),
		]),
	langPack.core.iface.add, 
	buttons,
	515,1,'addgrp');
	$("#acc_callbackurl").val(getCallbackUrl(hash));
	UI.openDialog('addgrp');
}

function getCallbackUrl(hash) {
	return API.settings.get("sitePrefix")+"/auth/oauth/"+hash;
}

function profileEdit_Click(ref) {
	var acid=($(ref.target).closest("tr").attr("accid"));
	var buttons={};
	buttons[langPack.core.iface.edit]=async function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				API.exec({
					errDict: langPack.core.iface.register.errors,
					requestType: "PATCH", 
					data: {
						name: fdata.name.val,
						url: fdata.url.val,
						clientId: fdata.clientId.val,
						clientKey: fdata.clientKey.val,
						config: fdata.type.val,
					},
					method: "core/oAuthProfile/"+acid,
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
			UI.addField({title: langPack.core.iface.title, type: "input", item: "acc_name", reqx:true}),
			UI.addField({title: langPack.core.iface.url, type: "input", item: "acc_url",reqx:false}),
			UI.addField({title: langPack.core.iface.template, type: "select", item: "acc_type",reqx:true, args: {"":"--"+langPack.core.iface.oauth.selectPreset+"--","vk":"VK","yandex":"Yandex", "gitea":"Gitea", "gitlab":"Gitlab"}}),
			UI.addField({title: langPack.core.iface.oauth.clientId, type: "input", item: "acc_clientId",reqx:true}),
			UI.addField({title: langPack.core.iface.oauth.clientKey, type: "input", item: "acc_clientKey",reqx:false}),
		]),
	langPack.core.iface.add, 
	buttons,
	385,1,'addgrp');
	API.exec({
		errDict: langPack.core.iface.register.errors,
		requestType: "GET", 
		method: "core/oAuthProfile/"+acid,
		onSuccess: function(json) {
			$("#acc_name").val(json.data.name);
			$("#acc_url").val(json.data.url);
			$("#acc_type").val(json.data.config);
			$("#acc_clientId").val(json.data.clientId);
			return false;
		},
	});
	
	UI.openDialog('addgrp');
	
}

