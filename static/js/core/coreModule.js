import * as API from './api.js';
import * as UI from './ui.js';

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

export var modSelector={
	"myprofile": "./coreMyProfile.js",
	"modules": "./coreModules.js",
	"module": "./coreModView.js",
	"groups": "./coreGroups.js",
	"group": "./coreGroup.js",
	"users": "./coreUsers.js",
	"userEmailConfirm": "./userMailConfirm.js",
	"mailAccounts": "./coreMailAccounts.js",
	"oauth": "./coreOAuthProfiles.js",
	"comps": "./coreComps.js"
}

export function load() {
	let ref=UI.parceURL();
	
	if (isset(modSelector[ref.function])) {
		API.loadModule(modSelector[ref.function]);
	} else if (!isset(ref.function)) {
		// TODO: Implement default start page for core module
		return true;
	} else {
		throw new Error(404);
	}
	return true;
}