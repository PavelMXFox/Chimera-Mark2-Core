import * as API from './api.js';
import * as UI from './ui.js';
import { langPack } from './langpack.js';

export function load() {
	UI.breadcrumbsUpdate(langPack.core.breadcrumbs.modTitle+" / "+langPack.core.breadcrumbs.myprofile);
	
}