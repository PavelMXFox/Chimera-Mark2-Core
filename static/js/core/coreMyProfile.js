import * as API from './api.js';
import * as UI from './ui.js';
import { langPack } from './langpack.js';

const userSettings={
	language: {
		type: "select",
		source: function() {
			let rv={};
			$.each(API.settings.get("coreLanguages"), function() {
				rv[this]=(langPack.core.languages && langPack.core.languages[this])?langPack.core.languages[this]:this;
			})	
			return rv;
		},
		reqx: true,
	},
	pageSize: {
		type: "input",
		regx: "^[0-9]{2}$",
		reqx: true,
	},
	theme: {
		type: "select",
		source: API.settings.get("themes"),
		reqx: true,
	}
}


export function load() {
	UI.breadcrumbsUpdate(langPack.core.breadcrumbs.modTitle+" / "+langPack.core.breadcrumbs.myprofile);
	UI.createLeftPanel([
		{id: "gendesc", title: langPack.core.iface.genDescTitle,disabled: false, buttons: [
			UI.addButton({
				id: "btnGdEdit",
				icon: "fas fa-edit",
				onClick: btnGdEdit_click,
			}),
		]},
		{id: "config", title: langPack.core.iface.configTitle,disabled: false, buttons: [
			UI.addButton({
				id: "btnStEdit",
				icon: "fas fa-edit",
				onClick: btnStEdit_click,
			}),
		]},
	]);

	UI.createRightPanel([

		{id: "mail", title: langPack.core.iface.messagesTitle,disabled: false},
		{id: "agreements", title: langPack.core.iface.agreementsTitle,disabled: true},
		{id: "messengers", title: langPack.core.iface.messengersTitle,disabled: true},
		{id: "log", title: langPack.core.iface.logTitle,disabled: true},
		
	])
	API.session.reload(reloadGenDesc);
}

function btnStEdit_click(_ref) {
	UI.blankerShow();
	API.session.reload(function() {
		UI.blankerHide();
		let buttons={};
		let dx;
		buttons[langPack.core.iface.dialodSaveButton]=function() {
			dialogStEdit_Save_Click(dx);
		}
		buttons[langPack.core.iface.dialodCloseButton]=function() {
			UI.closeDialog(dx);
		}

		let dxflds=[];

		$.each(userSettings, function(key) {
			let fld={
				type: this.type,
				item: key,
				title: langPack.core.userSettings[key]?langPack.core.userSettings[key]:key,
				reqx: this.reqx,
				regx: this.regx,
			};

			if (this.source) {
				if (typeof(this.source)=="function") {
					fld.args=this.source();
				} else {
					fld.args=this.source;
				}
			}

			fld.val=API.session.getConfigItem(key);

			dxflds.push(UI.addField(fld));
		});

		dx=UI.createDialog(
			UI.addFieldGroup(dxflds),
			langPack.core.iface.edit+": "+langPack.core.iface.configTitle, 
			buttons);
		UI.openDialog(dx);	
	});
}

function btnGdEdit_click(_ref) {
	UI.blankerShow();
	API.session.reload(function() {
		UI.blankerHide();
		let buttons={};
		let dx;
		buttons[langPack.core.iface.dialodSaveButton]=function() {
			dialogGdEdit_Save_Click(dx);
		}
		buttons[langPack.core.iface.dialodCloseButton]=function() {
			UI.closeDialog(dx);
		}
		dx=UI.createDialog(
			UI.addFieldGroup([
				UI.addField({
					title: langPack.core.iface.uid,
					val: UI.formatInvCode(API.session.get("user").invCode),
				}),
				UI.addField({
					title: langPack.core.iface.users.login,
					val: API.session.get("user").login,
				}),
				UI.addField({
					item: "dx_fullName",
					type: "input",
					title: langPack.core.iface.users.fullName,
					val: API.session.get("user").fullName,
					reqx: true,

				}),
				UI.addField({
					item: "dx_eMail",
					type: "input",
					title: langPack.core.iface.users.email,
					val: API.session.get("user").eMail,
					regx: "(^$)|(^[0-9a-zA-Z._-]+\@[0-9a-zA-Z._-]+\.[a-zA_Z]+$)",
					regxTitle: langPack.core.iface.users.errors.WREML,
				}),
				UI.addField({
					title: langPack.core.iface.password, 
					type: "passwordNew", 
					item: "dx_password",
					reqx:false, 
					regx: "(^$)|(.{6,})",
					disabled: false, 
					newPasswdGenCallback: function(passwd) {
						$("#dx_password2").val(passwd).addClass("changed").prop("type","input"); 
					}
				}),
				UI.addField({
					title: langPack.core.iface.passConfirm, 
					type: "password", 
					item: "dx_password2",
					reqx:false, 
					disabled: false
				}),
				]),
		langPack.core.iface.edit+": "+langPack.core.iface.genDescTitle, 
		buttons);
	
		UI.openDialog(dx);	
	});	
}

function dialogGdEdit_Save_Click(dx) {
	let data = UI.collectForm(UI.getId(dx),true,true,false,true);
	if(data.validateErrCount) { return };

	if (data.password.changed && data.password.val != data.password2.val) {
		$("#dx_password").addClass("alert");
		$("#dx_password2").addClass("alert");
		return;
	}
	
	API.exec({
		requestType: "PATCH",
		method: "core/user/"+API.session.get("user").id,
		data: data.getVals(),
		onSuccess: function(json) {
			UI.closeDialog(dx);
			API.session.reload(reloadGenDesc);
		}
	})
}

function dialogStEdit_Save_Click(dx) {
	let data = UI.collectForm(UI.getId(dx),true,true,false,true);
	if(data.validateErrCount) { return };

	API.exec({
		requestType: "PATCH",
		method: "core/user/"+API.session.get("user").id+"/settings",
		data: data.getVals(),
		onSuccess: function(json) {
			UI.closeDialog(dx);
			API.session.reload(reloadGenDesc);
			if (data.theme.changed) {
				console.log("Theme changed");
				window.location.reload(true);
			}
		}
	})
}

function reloadGenDesc() {
	let gdg=UI.addFieldGroup().appendTo($("#gendesc").empty());

	UI.addField({title: langPack.core.iface.uid, val: UI.formatInvCode(API.session.get("user").invCode)}).appendTo(gdg);
	UI.addField({title: langPack.core.iface.users.login, val: API.session.get("user").login}).appendTo(gdg);
	UI.addField({title: langPack.core.iface.users.fullName, val: API.session.get("user").fullName}).appendTo(gdg);
	UI.addField({title: langPack.core.iface.users.email, val: API.session.get("user").eMail}).appendTo(gdg);
	let emailConfirmationOnContextMenu;
	if (!API.session.get("user").eMailConfirmed && API.session.get("user").eMail) {
		emailConfirmationOnContextMenu= function(el) {
			UI.contextMenuOpen(el,[
				{
					title: langPack.core.iface.users.resendConfirmationCode,
					onClick: function() {
						API.exec({
							requestType: "GET",
							method: "core/user/sendEMailConfirmation",
						})
					}
				}
			]);
			return false;
		};
	}

	UI.addField({
		title: "eMail Confirmed", 
		val: API.session.get("user").eMailConfirmed?langPack.core.iface.yes:langPack.core.iface.no,
		onContextMenu: emailConfirmationOnContextMenu,
	}).appendTo(gdg);

	let cfg=UI.addFieldGroup().appendTo($("#config").empty());
	$.each(userSettings, function(key) {
		let val=API.session.getConfigItem(key);
		
		if (userSettings[key] && userSettings[key].type=="select" && userSettings[key].source) {
			let opts={};
			if (typeof(userSettings[key].source)=="function") {
				opts=userSettings[key].source();
			} else {
				opts=userSettings[key].source;
			}

			if (opts[val]) {
				val=opts[val];
			}
		}

		UI.addField({
			title: langPack.core.userSettings[key], 
			val: val
		}).appendTo(cfg);
	});
}

