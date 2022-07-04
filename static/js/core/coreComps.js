export async function load() {

UI.breadcrumbsUpdate($("<span>",{text: langPack.core.breadcrumbs.modTitle+" / "+langPack.core.iface.companies}));
	UI.createTabsPanel([
		{id: "comps", title: langPack.core.iface.companies , preLoad: reloadCompanies, buttons: UI.addButton({id: "btnCompAdd", icon: "fas fa-plus", title: langPack.core.iface.add, onClick: btnCmpAdd_Click})}
	]);
}

async function reloadCompanies() {
	await API.exec("GET", "core/company/list", {}, function(json,_textStatus) {
		$("#comps").empty();
	
		let dt=$("<table>",{class: "datatable sel"}).appendTo("#comps");
		let trh=$("<tr>").appendTo(dt);
		
		$("<th>",{class: "idx", text: "#"}).appendTo(trh);
		$("<th>",{class: "code", text: langPack.core.iface.uid}).appendTo(trh);
		$("<th>",{text: langPack.core.iface.type}).appendTo(trh);
		$("<th>",{text: langPack.core.iface.title}).appendTo(trh);
		$("<th>",{text: langPack.core.iface.comps.qName}).appendTo(trh);
		$("<th>",{text: langPack.core.iface.desc}).appendTo(trh);
		$("<th>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})}).appendTo(trh);
		
		$.each(json.data.result,function(key,val) {
			let trd=$("<tr>").appendTo(dt).bind('contextmenu', usmContextMenuOpen).addClass("contextMenu").attr("compId",val.id).attr("compUid",val.invCode);
			
			$("<td>",{class: "idx", text: key}).appendTo(trd);
			$("<td>",{class: "code", text: UI.formatInvCode(val.invCode)}).appendTo(trd);
			$("<td>",{text: langPack.core.iface.comps[val.type]}).appendTo(trd);
			$("<td>",{text: val.name}).appendTo(trd);
			$("<td>",{text: val.qName}).appendTo(trd);
			$("<td>",{text: val.description}).appendTo(trd);
			
			$("<td>",{class: "button", style: "text-align: center", append: $("<i>",{class: "fas fa-ellipsis-h"})})
			.click(usmContextMenuOpen)
			.appendTo(trd);	

		});
	},false);		
}

function usmContextMenuOpen(el) {

	let uid=$(el.target).closest("tr").attr("compUid");
	UI.contextMenuOpen(el,[
		{title: langPack.core.iface.edit, onClick: function() {
			compEdit_Click(el);	
		}},
		{title: langPack.core.iface.delete, onClick: function() {
			compDel_Click(el);
		}},

		],"#"+UI.formatInvCode(uid));
	
	return false;
}

function compDel_Click(ref) {
	var id=($(ref.target).closest("tr").attr("compId"));
	var uid=($(ref.target).closest("tr").attr("compUid"));
	var buttons={};
	buttons[langPack.core.iface.dialodDelButton]=function() { 
		$("#dialogInfo").dialog("close");
		API.exec({
			requestType: "DELETE",
			method: "core/company/"+id,
			onSuccess: function(json) {
				reloadCompanies();
			},
		});
		
	};
	
	buttons[langPack.core.iface.dialodCloseButton]=function() {  $("#dialogInfo").dialog("close"); }

	UI.showInfoDialog(langPack.core.iface.groups.delButtonTitle+" #"+uid+" "+$(ref.target).closest("tr").attr("xname"),langPack.core.iface.groups.delButtonTitle,buttons);
}

async function compEdit_Click(el) {
	let id=$(el.currentTarget).closest("tr").attr("compId");
	var buttons={};
	buttons[langPack.core.iface.dialodAddButton]=function() {
			
			let data=UI.collectForm("ItemAdd", true,false, false,true);

			if (data.validateErrCount > 0) { 
				return;
			}
			
			let vals=data.getVals();
			API.exec("PATCH",UI.parceURL().module+"/company/"+id,vals, function onAjaxSuccess(json,_textStatus) {
				UI.closeDialog('ItemAdd');
				reloadCompanies();
			});
			
		};
		
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('ItemAdd'); }
	
	compAddEditDialog_Create(langPack.core.iface.edit, buttons);
		
	await API.exec("GET",UI.parceURL().module+"/company/"+id,{}, function onAjaxSuccess(json,_textStatus) {
		$("#cao_name").val(json.data.name);
		$("#cao_qName").val(json.data.qName);
		$("#cao_description").val(json.data.description);
		$("#cao_type").val(json.data.type);
		
	});

	UI.openDialog('ItemAdd');
}

function compAddEditDialog_Create(title, buttons) {
		UI.createDialog(
		UI.addFieldGroup([
			UI.addField({title: langPack.core.iface.title, item: "cao_name", type: "input",reqx: true}),
			UI.addField({title: langPack.core.iface.comps.qName, item: "cao_qName", type: "input",reqx: true}),
			UI.addField({title: langPack.core.iface.desc, item: "cao_description", type: "input"}),
			UI.addField({title: langPack.core.iface.type, item: "cao_type", type: "select", reqx: true,  args: [
				{val:"",title: ""},
				{val:"company",title: langPack.core.iface.comps.company},
				{val:"client",title: langPack.core.iface.comps.client},
				{val:"supplier",title: langPack.core.iface.comps.supplier},
				{val:"parner",title: langPack.core.iface.comps.partner},
			]}),
		]),
		title, 
		buttons,
		350,1,'ItemAdd'
	);
}
function btnCmpAdd_Click(ref) {
	var buttons={};
	buttons[langPack.core.iface.dialodAddButton]=function() {
			
			let data=UI.collectForm("ItemAdd", true,false, false,true);
			if (data.validateErrCount > 0) { 
				return;
			}
			
			let vals=data.getVals();
			API.exec("PUT",UI.parceURL().module+"/company",vals, function onAjaxSuccess(json,_textStatus) {
				UI.closeDialog('ItemAdd');
				reloadCompanies();
			});
			
		};
		
	buttons[langPack.core.iface.dialodCloseButton]=function() { UI.closeDialog('ItemAdd'); }
	
	compAddEditDialog_Create(langPack.core.iface.add, buttons);
		
	UI.openDialog('ItemAdd');
}
