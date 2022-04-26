export function load() {
	if (UI.parceURL().args["code"]==undefined) {
		showValidationForm();
	} else {
		doValidation(UI.parceURL().args["code"]);
	}
}

function showValidationForm() {
	var buttons={};
	
	buttons[langPack.core.iface.dialodAddButton]=function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				doValidation(fdata.code.val);
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp'); }
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({item: "dag_code", title: langPack.core.iface.confCode, type: "input",reqx: "true", regx: "^[0-9]{4}$", regxTitle: langPack.core.iface.confCodeFmtErr}),
		]),
	langPack.core.iface.dialodAddButton, 
	buttons,
	185,1,'addgrp');
	
	
	UI.openDialog('addgrp')
}

function doValidation(code) {
	API.exec({
		errDict: langPack.core.iface.users.errors,
		requestType: "POST", 
		data: {
			code: code,
		},
		method: "core/user/validateEMailCode",
		errDict: langPack.core.iface.users.errors,
		onSuccess: function(json) {
			UI.closeDialog('addgrp');
			
			let buttons={};
			buttons[langPack.core.iface.dialodCloseButton]=function() { 
				UI.setLocation("/");
				import('./main.js').then(function(mod) {
					mod.load();
				})
				return false;
			};
			
			UI.showInfoDialog(langPack.core.iface.ok0,langPack.core.iface.dialodInfoTitle,buttons);
			return false;
		}
	});
}