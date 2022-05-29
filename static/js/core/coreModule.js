import * as API from './api.js';
import * as UI from './ui.js';
import { langPack } from './langpack.js';



export var menuSelector={
	"groups":"adminGrous",
	"modules":"adminModules",
	"module":"adminModules",
	"users":"adminUsers",
	"group":"adminGrous",
	mailAccounts:"mailAccounts",
	oauth: "oauth",
	comps: "adminComps"
};

export function load() {
	let ref=UI.parceURL();
	switch (ref.function) {
		case undefined:
			break;
		
		case "myprofile":
			import("./coreMyProfile.js").then(function(mod) {
				mod.load();
			})
			break;
		
		case "modules":
			import("./coreModules.js").then(function(mod) {
				mod.load();
			})
			break;
			
		case "module":
			import("./coreModView.js").then(function(mod) {
				mod.load();
			})
			break;

		case "groups":
			import("./coreGroups.js").then(function(mod) {
				mod.load();
			})
			break;
			
		case "group":
			import("./coreGroup.js").then(function(mod) {
				mod.load();
			})
			break;

		case "users":
			import("./coreUsers.js").then(function(mod) {
				mod.load();
			})
			break;
			
		case "userEmailConfirm":
			import("./userMailConfirm.js").then(function(mod) {
				mod.load();
			})
			break;
		case "mailAccounts":
			import("./coreMailAccounts.js").then(function(mod) {
				mod.load();
			})
			break;
		case "oauth":
			import("./coreOAuthProfiles.js").then(function(mod) {
				mod.load();
			})
			break;

		case "comps":
			import("./coreComps.js").then(function(mod) {
				mod.load();
			})
			break;
		

		default:
			throw new Error(404);
	}
	return true;
}