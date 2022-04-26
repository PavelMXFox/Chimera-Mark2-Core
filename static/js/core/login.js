import * as API from './api.js';
import * as UI from './ui.js';
import { langPack, lpCacheDrop } from './langpack.js';

export function load() {
	$("body").empty();

	if (UI.parceURL().module=='auth' && UI.parceURL().function=='oauth') {
		let hash = UI.parceURL().id;
		let code=UI.parceURL().args.code;
		
		UI.setLocation("/");
		
		let fprd=sessionStorage.getItem("foxPreRegisterData")
		if (fprd) {
			fprd=JSON.parse(fprd);
			// register
			API.exec({
				errDict: langPack.core.iface.register.errors,
				requestType: "POST", 
				data: {
					email: fprd.email,
					regCode: fprd.regCode,
					authType: fprd.authType,
					oAuthCode: code,
					oAuthHash: hash,
				},
				method: "auth/register/register",
				onSuccess: function(json) {
					loginSuccessCallback(json);
					return false;
				},
				onFinal: function() {
					doRegister();
				}
			});
			
		} else {
			// auth
			API.exec("POST", "auth/oauth",{hash: hash, code: code},loginSuccessCallback,false,function onFail(json) {
				console.log(json);
				if (json.status.code==401 && json.data.error.xCode=="UNR") {
					let buttons={};
					buttons["OK"]=function() {
						UI.closeDialog("dialogInfo");
						load();
					}
					UI.showInfoDialog(langPack.core.iface.oauthLoginFailedUNR,langPack.core.iface.loginFailedDialogTitle,buttons);
				} else {
					console.log("Error:",json);
				}
			});
		}		
		
		return;
	} else if (UI.parceURL().module=='auth' && UI.parceURL().function=='register') {
		let code=UI.parceURL().args.code;
		UI.setLocation("/");
		doRegister(code);
		return;
	} else if (UI.parceURL().module=='auth' && UI.parceURL().function=='recover') {
		doRecover();
		return;
	}
	
	sessionStorage.removeItem("foxPreRegisterData");
	sessionStorage.setItem("foxAuthReturnUrl",document.location.href);
	
	$("<div>",{ id: "loginForm", class: "login",	style: "padding: 0px;",
		append: $("<h2>", { class: "login first", style: "display: inline-block; float: left; margin-top: 4px", text: "Авторизация"})
		.add(UI.addButton("btnLogin","fas fa-sign-in-alt","Login","", doLogin,undefined,"float: right; margin-right: 0"))
		.add("<div>",{class: "widget",
			append: UI.addFieldGroup([
				UI.addField({title: "Login", type: "input", item: "login_username"}).attr("reqx","true"),
				UI.addField({title: "Password", type: "password", item: "login_password"}).attr("reqx","true")
			])
		})
		.add("<div>",{class: "widget",
			append: $("<span>", { class: "linkButton", id: "lbl_restore", text: "Восстановить", click: doRecover})
			.add("<span>",{text: " или "})
			.add($("<span>", { class: "linkButton", id: "lbt_register", text: "Зарегистрироваться", click: doRegister}))
		})
	}).appendTo("body");
	
	let oap=API.settings.get("oauthProfiles");
	if (oap.length>0) {
		let oad=$("<div>",{ class: "widget", id: "divAuthWith" });
		let width=((300/oap.length)-20).toFixed();
		if (width>80) { width=80; }
		
		$.each(oap,function(key,val) {
			UI.addButton({
				id: "btnOauth_"+val.id,
				title: langPack.core.iface.loginWith+" "+val.name,
				style: "width: "+width+"px",
				icon: val.icon,
				onClick: btnLoginWith_Click,
			}).prop("prId",val.id).appendTo(oad);
		})
		oad.appendTo("#loginForm");
	}
	
	$(".i").keyup(function(event) {
    if (event.keyCode === 13) {
        $("#btnLogin").click();
    }
});
}

async function btnLoginWith_Click(ref) {
	let prid=$(ref.currentTarget).prop("prId");
	UI.blankerShow();
	let url=await getOAuthURL(prid,true);
	document.location.href=url;
}

function doLogin() {
	let data=UI.collectForm("loginForm",true,true,false,true);
	if (data.validateErrCount > 0) {
		return;
	}
	
	API.exec("POST", "auth/login",{login: data.username.val, password:data.password.val,type:"WEB"}, loginSuccessCallback,false,function(json) {
		if (json.status.code==401) {
			UI.showInfoDialog(langPack.core.iface.loginFailed401text,langPack.core.iface.loginFailedDialogTitle);
		} else {
			console.log("Error:",json);
		}
	});
}

function loginSuccessCallback(json) {
	localStorage.setItem("token",json.data.token);
	localStorage.setItem("tokenExpire",json.data.expire.stamp);
	lpCacheDrop();
	UI.setLocation(sessionStorage.getItem("foxAuthReturnUrl"));
	sessionStorage.removeItem("foxAuthReturnUrl");
	import("./main.js").then(function(mod) {
		mod.load();		
	});
}

function doRecover() {
	var buttons={};
	buttons[langPack.core.iface.dialogRecoveryButton]=async function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);		
			
			if (fdata.validateErrCount==0) {
				if (fdata.code.val.length==0) {
					API.exec({
						errDict: langPack.core.iface.register.errors,
						requestType: "POST", 
						data: {
							email: fdata.email.val,
						},
						method: "auth/register/recovery",
						onSuccess: function(json) {
							UI.showInfoDialog(langPack.core.iface.users.recoverSentSucces);
							return false;
						},
						onFinal: function() {
							//doRegister();
						},
						noblabk: false
					});
				} else {
					API.exec({
						errDict: langPack.core.iface.register.errors,
						requestType: "POST", 
						data: {
							email: fdata.email.val,
							code: fdata.code.val,
						},
						method: "auth/register/validateRecovery",
						onSuccess: function(json) {
							UI.closeDialog('addgrp');
							doRecoverStage2(fdata.email.val,fdata.code.val);
							return false;
						},
						onFinal: function() {
							//doRegister();
						}
					});					
				}
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp'); UI.setLocation("/"); load(); }
		
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({val: langPack.core.iface.users.recoveryFormText}),
			UI.addField({title: langPack.core.iface.users.email, type: "input", item: "reg_email", reqx:true}),
			UI.addField({title: langPack.core.iface.confCode, type: "input", item: "reg_code",reqx:false,regx: "^[0-9]{4}$", regxTitle: langPack.core.iface.confCodeFmtErr}),			
		]),
	langPack.core.iface.dialogReсoveryTitle, 
	buttons,
	365,1,'addgrp');
	
	if (UI.parceURL().args.address != undefined && UI.parceURL().args.code != undefined) {
		$("#reg_email").val(decodeURI(UI.parceURL().args.address));
		$("#reg_code").val(UI.parceURL().args.code);
		UI.setLocation("/");
	}
	
	UI.openDialog('addgrp');
}

function doRecoverStage2(address,code) {
	var buttons={};
	buttons[langPack.core.iface.dialogRecoveryButton]=async function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);		
			
			if (fdata.validateErrCount==0) {
				if (fdata.pass1.val != fdata.pass2.val) {
					$("#rev_pass1").addClass("alert").attr("title",langPack.core.iface.passNotMatch);
					return;	
				}
				API.exec({
					errDict: langPack.core.iface.register.errors,
					requestType: "POST", 
					data: {
						email: address,
						code: code,
						newPasswd: fdata.pass1.val,
					},
					method: "auth/register/setNewPassword",
					onSuccess: function(json) {
						loginSuccessCallback(json);
						return false;
					},
					onFinal: function() {
						//doRegister();
					},
					noblabk: false
				});

			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp'); UI.setLocation("/"); load(); }
		
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({val: langPack.core.iface.users.enterNewPasswordText}),
			UI.addField({title: langPack.core.iface.password, type: "passwordNew", item: "rev_pass1",reqx:true, disabled: false, newPasswdGenCallback: function(passwd) {$("#rev_pass2").val(passwd).addClass("changed"); }}),
			UI.addField({title: langPack.core.iface.passConfirm, type: "password", item: "rev_pass2",reqx:true, disabled: false}),
		]),
	langPack.core.iface.dialogReсoveryTitle, 
	buttons,
	275,1,'addgrp');
	
	UI.openDialog('addgrp');
}

async function validatePreReg(fdata) {
	
	let rStatus=false;
	try {
		await API.exec({
		errDict: langPack.core.iface.register.errors,
		requestType: "POST", 
		data: {
			email: fdata.email.val,
			regCode: fdata.code.val,
			login: fdata.login.val,
			authType: fdata.authtype.val,
		},
		method: "auth/register/preCheck",
		onSuccess: function(json) {
			rStatus=true;
			return false;
		}
	});
	} catch (e) {}

	return rStatus;
}

async function getOAuthURL(prid,noblank) {
	let rvUrl=undefined;
	try {
		if (noblank==undefined) { noblank=true; }
		
		await API.exec("GET", "auth/oauth/"+prid,{}, function onSuccess(json) {
			rvUrl=json.data.url;
		},noblank);
	} catch (e) {}
	return rvUrl;
}


function doRegister(regCode) { 
	var buttons={};
	buttons[langPack.core.iface.dialogRegisterButton]=async function() {
			let fdata=UI.collectForm("addgrp", true,false, false,true);
			if (fdata.validateErrCount==0) {
				if (fdata.authtype.val !== 'password') {
					let preCheckStatus= await validatePreReg(fdata);
					if (!preCheckStatus) { return; }
					sessionStorage.setItem("foxPreRegisterData",JSON.stringify({
						email: fdata.email.val,
						regCode: fdata.code.val,
						login: fdata.login.val,
						authType: fdata.authtype.val,
					}));
					let authType=fdata.authtype.val.split("_")[0];
					let authKey=fdata.authtype.val.split("_")[1];
					if (authType=='oauth') {
						let url=await getOAuthURL(authKey);	
						document.location.href=url;
					}
					
				} else {
					if (fdata.pass1.val != fdata.pass2.val) {
						$("#reg_pass1").addClass("alert").attr("title",langPack.core.iface.passNotMatch);
						return;	
					}
					
					API.exec({
						errDict: langPack.core.iface.register.errors,
						requestType: "POST", 
						data: {
							email: fdata.email.val,
							regCode: fdata.code.val,
							fullName: fdata.fullname.val,
							login: fdata.login.val,
							password: fdata.pass1.val,
							authType: fdata.authtype.val,
						},
						method: "auth/register/register",
						onSuccess: function(json) {
							loginSuccessCallback(json);
							return false;
						},
						onFinal: function() {
							//doRegister();
						}
					});
				}
				
			}
		};
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('addgrp'); UI.setLocation("/"); load(); }
	
	let authProfiles=[{val: '', title: "--"+langPack.core.iface.authTypeSelectorStub+"--"},{val: 'password',title: langPack.core.iface.authTypePasswd}];
	$.each(API.settings.get("oauthProfiles"), function(key,val) {
		authProfiles.push({val: "oauth_"+val.id, title: langPack.core.iface.authTypeExt+" "+val.name});
	});
	
	UI.createDialog(
		UI.addFieldGroup([
			UI.addField({title: langPack.core.iface.users.email, type: "input", item: "reg_email", reqx:true}),
			UI.addField({title: langPack.core.iface.users.regCode, type: "input", item: "reg_code",reqx:false}),
			
			UI.addField({title: langPack.core.iface.loginWith, type: "select", item: "reg_authtype",reqx:true, args: authProfiles, onChange: function(ref) {
				if ($(ref.currentTarget).val()=='password') {
					$("#reg_fullname").prop("disabled",false);
					$("#reg_login").prop("disabled",false);
					$("#reg_pass1").prop("disabled",false);
					$("#reg_pass2").prop("disabled",false);
				} else {
					$("#reg_fullname").prop("disabled",true).val("");
					$("#reg_login").prop("disabled",true).val("");
					$("#reg_pass1").prop("disabled",true).val("");
					$("#reg_pass2").prop("disabled",true).val("");					
				}
			}}),
			
			UI.addField({title: langPack.core.iface.users.fullName, type: "input", item: "reg_fullname",reqx: true, disabled: true}),
			UI.addField({title: langPack.core.iface.users.login, type: "input", item: "reg_login",reqx:true, disabled: true}),
			UI.addField({title: langPack.core.iface.password, type: "passwordNew", item: "reg_pass1",reqx:true, disabled: true, newPasswdGenCallback: function(passwd) {$("#reg_pass2").val(passwd).addClass("changed"); }}),
			UI.addField({title: langPack.core.iface.passConfirm, type: "password", item: "reg_pass2",reqx:true, disabled: true}),
			
		]),
	langPack.core.iface.dialogRegisterTitle, 
	buttons,
	515,1,'addgrp');
	
	let fprd=sessionStorage.getItem("foxPreRegisterData")
	if (fprd) {
		fprd=JSON.parse(fprd);
		$("#reg_email").val(fprd.email);
		$("#reg_code").val(fprd.regCode);
		$("#reg_authtype").val(fprd.authType);
	}	
	
	if (typeof(regCode)=="string") {
		$("#reg_code").val(regCode);
	}
	
	UI.openDialog('addgrp');
}