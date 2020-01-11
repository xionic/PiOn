import {html} from 'lit-element';


var module_id_counter = 0; //hack, to give each module element it's own id because I can't work out how to reference the object from the element.
var modules = new Object();
export function register_module(props){
	console.debug("Registering module: " + props.name);
	modules[props.name] = new Object();
	modules[props.name].update = props.update;
	
} 

function get_module_html(name){
	if(!modules.hasOwnProperty(name)){
		console.error("Invalid module: " + name);
		return "";
	}	
	return '<module-' + name + " class='itemmodule' id='module_container_" + module_id_counter++ + "'></module-" + name + ">";
}

export function item_updated(item_name, value_obj){
	console.debug("Item update from: " + item_name);
	$.ajax({
		url: "http://xealot:28080/",
		data: {
			action: "set",
			item_name: item_name,
			value: value_obj
		}
	}).done(function(data){
		console.debug("Value update for" + item_name + " was successful");
		
		
	});
}

$().ready(function(){
	setTimeout(function(){ //Let modules load first
		//build menu from sitemap
		$(Object.keys(sitemap)).each(function(k, room){
			var roomli = $("<li>", {class: "roomrow"}).append($("<h3>",{text:room, class:"roomname"}));
			var roomul = $("<ul>");
			
			$(sitemap[room]).each(function(key, value){
				var itemli = $("<li>");
				itemli.append($("<span>",{class: "itemname", text: value.item_name}));
				var item_value_span = $("<span>",{class: "itemvalue", "data-item_name":value.item_name, "data-type": value.type});
				item_value_span.html(get_module_html(value.type));
				itemli.append(item_value_span);
				$.ajax({
					url: "http://xealot:28080/?action=get&item_name="+value.item_name
				}).done(function(data){
					//console.log(data);
					$(item_value_span)[0].querySelector(".itemmodule").update_value(data);
					
				});
				//itemli.append();
				roomul.append(itemli);
			});
			roomli.append(roomul);
			$("#rooms").append(roomli);		
			console.log("Finished li setup");
		},);	
	
		//create UI elements	
		//setTimeout(function(){
			//$().flipswitch({"height":25, width:100, type: "click"});
		//},500);
		
	},1000);
});

