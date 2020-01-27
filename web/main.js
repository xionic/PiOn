import {html} from 'lit-element';
import {RestMessage} from './RestMessage.js';
import {ItemMessage} from './ItemMessage.js';
import {Value} from './Value.js';

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

export function item_updated(item_name, value){
	console.debug("Item update from: " + item_name);
	let item_message = new ItemMessage(item_name, ItemMessage.SET, value, null, null);
	let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_ITEM, null, null, null, item_message.to_json());
	$.ajax({
		url: "http://xealot:28080/",
		data: {
			data: rest_message.to_json()
		}
	}).done(function(data){
		console.debug("Value update for " + item_name + " was successful with data: " + data);		
	});
}

$().ready(function(){
	setTimeout(function(){ //Let modules load first
		//build menu from sitemap
		$(Object.keys(sitemap)).each(function(k, room){
			var roomli = $("<li>", {class: "roomrow"}).append($("<h3>",{text:room, class:"roomname"}));
			var roomul = $("<ul>");
			
			$(sitemap[room]).each(function(key, item){
				var itemli = $("<li>");
				itemli.append($("<span>",{class: "itemname", text: item.item_name}));
				var item_value_span = $("<span>",{class: "itemvalue", "data-item_name":item.item_name, "data-type": item.type});
				item_value_span.html(get_module_html(item.type));
				itemli.append(item_value_span);
				//request current value
				let item_message = new ItemMessage(item.item_name, ItemMessage.GET, null)
				let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_ITEM, "client", null, null, item_message.to_json())
				$.ajax({
					url: "http://xealot:28080/?data=" + rest_message.to_json()
				}).done(function(data, text_status, jqxhr){
					//console.log(data);
					var payload = JSON.parse(data.payload);
					console.debug("Successfully updated value for item: " + item.item_name + " with value: " + data.payload);
					$(item_value_span)[0].querySelector(".itemmodule").update_value(payload.value.data);
					
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

