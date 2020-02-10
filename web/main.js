import {html, LitElement} from 'lit-element';
import {RestMessage} from './RestMessage.js';
import {ItemMessage} from './ItemMessage.js';
import {Value} from './Value.js';
import {InvalidModuleExeption} from './exceptions.js';

/*
*stufff that should be in classes but my OOp and tolerance for JS is terrible
*/
var modules = new Object();// list of registered type modules (from modules dir)
export function register_module(props){
	console.debug("Registering module: " + props.name);
	modules[props.name] = new Object();
	modules[props.name].update = props.update;
} 

export function getTimestamp(){
	return (new Date).getTime()/1000;
}

export function send_update(elem, item_name, value){
		let item_message = new ItemMessage(item_name, ItemMessage.SET, value, null, null);
		let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_ITEM, null, null, null, item_message);
		console.debug("Sending update to server for item ", item_name, " and rest_message:", rest_message);
		$.ajax({
			url: config.backend_url,
			data: {
				data: rest_message.to_json()
			}
		}).done(function(data){
			console.debug("Value update for '", item_name, "' was successful with data: ", data);		
		});
	}
	
var module_id_counter = 0; //hack, to give each module element it's own id because I can't work out how to reference the object from the element.
function get_module_html(name, item_name){
	if(!modules.hasOwnProperty(name)){
		console.error("Invalid module: " + name);
		throw new InvalidModuleExeption("Invalid module '" + name + "'");
	}	
	let obj = {html: '<module-' + name + " class='itemmodule' id='module_container_" + 			module_id_counter + "' item_name='" + item_name + "'></module-" + name + ">",
			id: "module_container_" + module_id_counter
	};
	module_id_counter++;
	return obj;
}

var item_elem_list = []; //List of all htmlelements for each module instantiated. [{item_name:..., "id": ...} ...]

//make sure everything is loaded before we start pissing with litelement
 
$().ready(function(){
	setTimeout(function(){// MEGA HACK. modules must register before we can do this, but we have no way of knowing when this is complete.
		$("#rooms").html("<pion-main></pion-main>");
	},500);
});

export class Main extends LitElement {
	
	constructor(){
		super();
	}
	
	render(){	
		//build menu from sitemap
		$(Object.keys(sitemap)).each(function(k, room){
			var roomli = $("<li>", {class: "roomrow"}).append($("<h3>",{text:room, class:"roomname"}));
			var roomul = $("<ul>");
			
			//build UI from sitemap.json
			$(sitemap[room]).each(function(key, item){
				var itemli = $("<li>");
				itemli.append($("<span>",{class: "itemname", text: item.item_name}));
				var item_value_span = $("<span>",{class: "itemvalue", "data-item_name":item.item_name, "data-type": item.type});
				
				try{
					let module_spec = get_module_html(item.type, item.item_name);
					item_elem_list.push(module_spec);
					item_value_span.html(module_spec.html);					
					
					itemli.append(item_value_span);
					
					//request current value
					let item_message = new ItemMessage(item.item_name, ItemMessage.GET, null)
					let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_ITEM, "client", null, null, item_message)
					
					$.ajax({
						url: config.backend_url,
						data: { data: rest_message.to_json()}
					}).done(function(data, text_status, jqxhr){
						//console.log(data);
						var payload = data.payload;
						console.debug("Successfully retrieved value for item: ", item, " with data: ", data, " from: ", config.backend_url);
						//console.log(item_value_span[0]);
						item_value_span[0].querySelector(".itemmodule").set_value(payload.value);
						
					});				
					//itemli.append();
					roomul.append(itemli);
				
				} catch(e){
					if(e instanceof InvalidModuleExeption)
						return; //jquery .each equiv of continue
					else throw e;
				}
			});
			roomli.append(roomul);
			$("#rooms").append(roomli);		
			console.log("Finished li setup");
		});	
		/*
		//back through and add event handlers now that they've been added to the dom
		$(Object.keys(sitemap)).each(function(k, room){
			$(sitemap[room]).each(function(kk, item){
				$(item_elem_list).each(function(key, value){
					let elem = document.getElementById(value.id);
					(async function (){
						let result = await elem.updateComplete;
						console.log("elem", elem);
						$(elem).change(function(){
						console.log("inchange", elem.getAttribute("item-name"), elem.get_value())
							item_updated(elem.getAttribute("item-name"), elem.get_value());
						});
						return true;
					}.call());
				});
			});
		});*/	
	}
}
customElements.define('pion-main', Main);


