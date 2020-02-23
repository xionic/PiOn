import {html, LitElement} from 'lit-element';
import {RestMessage} from './RestMessage.js';
import {ItemMessage} from './ItemMessage.js';
import {SubscribeMessage} from './SubscribeMessage.js';
import {ItemEvent} from './ItemEvent.js';
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
	console.debug(`Sending update to server for item '${item_name}' and item_message:`, item_message);
	$.ajax({
		url: config.api_url,
		data: {
			data: rest_message.to_json()
		}
	}).done(function(data){
		let resp_rest_message = RestMessage.from_obj(data);
		let resp_item_message = RestMessage.from_obj(resp_rest_message.payload);
		switch(resp_rest_message.context){
			case RestMessage.REST_CONTEXT_ERROR:
				console.log("REST Received ERROR message: " + rest_message.payload);
				alert("ERROR: " + rest_message.payload);
			break;
			default: 
				console.debug(`Value update for '${item_name}' was successful with data: `, data);		
		}
	}).fail(function(data){
		console.error(data);
	});
}

//recursively selectors through shadow roots using the selector "shadow" to indicate a shadow root
export function shadow_selector(root, selector_str){
	let selArray = selector_str.split("shadow");
	let selected = root;
	while(selArray.length > 0){
		let selector = selArray.shift();
		if(selector == "")
			continue;
		selected = selected.shadowRoot.querySelector(selector);
	}
	return selected;
}
	
var module_id_counter = 0; //hack, to give each module element it's own id because I can't work out how to reference the object from the element.
function get_module_html(name, item_name){
	if(!modules.hasOwnProperty(name)){
		console.error("Invalid module: " + name);
		throw new InvalidModuleExeption("Invalid module '" + name + "'");
	}	
	let obj = {html: '<module-' + name + " class='itemmodule' id='module_container_" + module_id_counter + "' item_name='" + item_name + "'></module-" + name + ">", 
	id: "module_container_" + module_id_counter
	};
	module_id_counter++;
	return obj;
}

var item_elem_list = []; //List of all htmlelements for each module instantiated. [{item_name:..., "id": ...} ...]

var websocket;
var ws_promise;
var subscribers = {} // subscribers.item_name = array of (pion_base) element objects
//make sure everything is loaded before we start pissing with litelement
$().ready(function(){
	setTimeout(function(){// MEGA HACK. modules must register before we can do this, but we have no way of knowing when this is complete.
		$("#rooms").html("<pion-main></pion-main>");
	},500);
	
	//setup wetsocket
	websocket = new WebSocket("ws://" + config.websocket_url);
	ws_promise = new Promise(function(resolve){
		// Connection opened
		websocket.addEventListener('open', function (event) {
			resolve(websocket);
			//subscribe to all required items	
			let items = {};
			for (const room in sitemap) {
				sitemap[room].forEach(elem => {
					items[elem.item_name] = ["ITEM_VALUE_CHANGED"];
				})
			}
		});
	});

	websocket.addEventListener('error', function(event) {
		console.error("WebSocket error observed:", event);
		alert("WebSocket Error - see console");
	});

	
	

	// Listen for messages
	websocket.addEventListener('message', function (event) {		
		let rest_message = RestMessage.from_json(event.data);
		console.log('Message from WebSocket ', rest_message);

		switch(rest_message.context){
			case RestMessage.REST_CONTEXT_ITEM:
			rest_message.payload = [rest_message.payload];
			case RestMessage.REST_CONTEXT_ITEMS:
				rest_message.payload.forEach(function(item){
					let item_message = ItemMessage.from_obj(item);
					console.debug("WS: Received value for item: ", item_message.item_name, " with data: ", item_message.value.data);
					/*let elems = document.querySelectorAll("span.itemvalue *[item_name='" + item_message.item_name + "']");
					if(elems != null){
						elems.forEach(function(elem){
							elem.set_value(item_message.value);
						});
					}*/
					subscribers[item_message.item_name].forEach(function(elem){
						elem.set_value(item_message.value)
					});
				});
				break;
			case RestMessage.REST_CONTEXT_ERROR:
				console.log("WS: Received ERROR message: " + rest_message.payload);
				alert("ERROR: " + rest_message.payload);
		}
	});
});

/**
 * 
 * @param {pion_base} elem 
 * @param {array} items {item_name: [event_name1, ...]}
 * @param {Boolean} request_values whether to retresh values for all subscribed items
 */
export function ws_subscribe(elem, items, request_values){
	ws_promise.then(function(){
		console.log("WS: Subscribing to items: ", items, " get_values: " + request_values);
		for(const item in items){
			if(!subscribers.hasOwnProperty(item)){			
				subscribers[item] = [];
			}
			subscribers[item].push(elem);
		}
		let sub_msg = new SubscribeMessage(items, SubscribeMessage.SUBSCRIBE, request_values);
		let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_SUBSCRIBE, 'client', config.host, null, sub_msg);
		websocket.send(rest_message.to_json());
	});
}

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
				$(itemli).addClass("item");
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

					//websocket.send(rest_message.to_json());
					/*
					$.ajax({
						url: config.api_url,
						data: { data: rest_message.to_json()}
					}).done(function(data, text_status, jqxhr){
						//console.log(data);
						var payload = data.payload;
						console.debug("Successfully retrieved value for item: ", item, " with data: ", data, " from: ", config.api_url);
						//console.log(item_value_span[0]);
						item_value_span[0].querySelector(".itemmodule").set_value(payload.value);
						
					}).fail(function(jqXHR, textStatus, errorThrown){
						console.error("AJAX FAILED. status: '" + textStatus + "' error:'" + errorThrown + "'", jqXHR);
					});*/
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


