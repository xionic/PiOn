import {html, LitElement} from 'lit-element';
import {render} from 'lit-html';
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
		method: "GET",
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
	let obj = {html: "<module-" + name + " class='itemmodule' id='module_container_" + module_id_counter + "' item_name='" + item_name + "'></module-" + name + ">", 
	/*let l = "number";
	let obj = {html: html`<module-number></module-number>`, */
	id: "module_container_" + module_id_counter
	};
	module_id_counter++;
	return obj;
}

var websocket;
var ws_promise;
var subscribers = {} // subscribers - item_name => [event_name => [element, ...], ...] 

//make sure everything is loaded before we start pissing with litelement
$().ready(function(){	
	setTimeout(function(){// MEGA HACK. modules must register before we can do this, but we have no way of knowing when this is complete.
		$("#rooms").html("<pion-main></pion-main>");
	},500);

	ws_connect();

	ws_promise.then(function () {
		setInterval(function(){
			let text_state = "";
			switch(websocket.readyState){
				case 0:
				text_state = "connecting";
					break;
				case 1:
				text_state = "connected";
					break;
				case 2:
				text_state = "closing";
					break;
				case 3:
				text_state = "closed";
					break;
			}
			var current_date = new Date(); 
			let hours = "" + current_date.getHours();
			let mins = "" + current_date.getMinutes();
			let secs = "" + current_date.getSeconds();
			text_state = "Websocket is " + text_state + "@" + hours.padStart(2, "0") + ":" + mins.padStart(2, "0") + ":" + secs.padStart(2, "0");
			$("#websocket_status").text(text_state);		
		}, 1000);
	});

	$("#websocket_status").click(function(){
		alert("Reconnecting websocket");
		ws_reconnect();
	});
	//For testing purposes (right click)
	$("#websocket_status").contextmenu(function(){
		alert("Disconnecting websocket");
		websocket.close();
	});
});

function ws_connect(old_subscribers) {
	//reset vars	
	subscribers = [];
	ws_promise = null;

	//setup wetsocket
	console.debug("Opening websocket");
	websocket = new WebSocket("ws://" + config.websocket_url);
	ws_promise = new Promise(function (resolve) {
		// Connection opened
		websocket.onopen = function (event) {
			resolve(websocket);
			//subscribe to all required items	
			let items = {};
			for (const room in sitemap) {
				sitemap[room].forEach(elem => {
					items[elem.item_name] = ["ITEM_VALUE_CHANGED"];
				})
			}
			if (typeof old_subscribers === 'object') { // if subscribers is passed this is a reconnect and we need to re-subscribe
				console.debug("WS: Resubscribing on new connection");

				//rearrange old_subscribers to item => {item_name: [event_name]}
				//let new_subs = {};
				for (const item_name in old_subscribers){	
					//new_subs[item_name] = [];
					for (const event_name in old_subscribers[item_name]) {						
						//new_subs[item_name].push(event_name)
						let resub = {};
						resub[item_name] = [event_name];
						old_subscribers[item_name][event_name].forEach(function(elem){
							ws_subscribe(resub, elem, SubscribeMessage.REQUEST_VALUES);
						});
					}
				}
			}
		}
	});
	//console.log(ws_promise);

	websocket.onclose = function (event) {
		console.error("WebSocket close observed:", event);
		//alert("WebSocket Closed - see console");
		ws_reconnect();
	}
	websocket.onerror = function (event) {
		console.error("WebSocket error observed:", event);
		//alert("WebSocket Error - see console");
		//ws_reconnect();
	}

	// Listen for messages
	websocket.onmessage = function (event) {
		let rest_message = RestMessage.from_json(event.data);
		console.log('Message from WebSocket ', rest_message);

		switch (rest_message.context) {
			case RestMessage.REST_CONTEXT_ITEM:
				rest_message.payload = [rest_message.payload];
			case RestMessage.REST_CONTEXT_ITEMS:
				rest_message.payload.forEach(function (item) {
					let item_message = ItemMessage.from_obj(item);
					console.debug("WS: Received value for item: ", item_message.item_name, " with data: ", item_message.value.data);
					/*let elems = document.querySelectorAll("span.itemvalue *[item_name='" + item_message.item_name + "']");
					if(elems != null){
						elems.forEach(function(elem){
							elem.set_value(item_message.value);
						});
					}*/
					subscribers[item_message.item_name][ItemEvent.events.ITEM_VALUE_CHANGED].forEach(function(elem){
						elem._set_value(item_message.value)
					});
				});
				break;
			case RestMessage.REST_CONTEXT_ERROR:
				console.log("WS: Received ERROR message: " + rest_message.payload);
				//alert("ERROR: " + rest_message.payload);
		}
	}
}

function ws_reconnect(){
	console.debug("Websocket attempting to reconnect");
	ws_connect(subscribers);
}

/**
 * 
 * @param {pion_base} elem 
 * @param {array} items {item_name: [event_name1, ...]}
 * @param {Boolean} request_values whether to retresh values for all subscribed items
 */
export function ws_subscribe(items, elem, request_values){
	let handler =  function(items, elem, request_values){
	
		ws_promise.then(function(){
			console.log("WS: Subscribing to items: ", items, " get_values: " + request_values);
			let sub_obj = {}; // object to pass to SubscribeMessage - doesn't have element refs
			for(const item in items){
				if(item === 'undefined'){
					console.log("SHI*T");
				}
				sub_obj[item] = [];
				if (!subscribers.hasOwnProperty(item)) {
					subscribers[item] = {};
				}		

				items[item].forEach(function(event){
					sub_obj[item].push(event);
					if (!subscribers[item].hasOwnProperty(event)) {				
						subscribers[item][event] = [];
					}
					subscribers[item][event].push(elem);
				});

				let sub_msg = new SubscribeMessage(sub_obj, SubscribeMessage.SUBSCRIBE, request_values);
				let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_SUBSCRIBE, 'client', config.host, null, sub_msg);
				//console.log(websocket);
				websocket.send(rest_message.to_json());
			}
		});
	}
	handler.call(null, items,elem,request_values);
}

export class Main extends LitElement {
	
	constructor(){
		super();
	}

	createRenderRoot() {
		return this;
	}
	
	render(){	
		//build menu from sitemap
		$(Object.keys(sitemap)).each(function(k, room){
			var roomli = $("<li>", {class: "roomrow"}).append($("<h3>",{text:room, class:"roomname"}));
			var roomul = $("<ul>");
			
			//build UI from sitemap.json
			$(sitemap[room]).each(function(key, item){

				let params = new URLSearchParams(document.location.search.substring(1));
				if (item.auth && params.get("auth") == null) return; //dev hack

				var itemli = $("<li>");
				$(itemli).addClass("item");
				let item_name_span = $("<span>", { class: "itemname", text: item.item_name });
				item_name_span.click(function(){
					this.parentElement.querySelector("span.itemvalue .itemmodule").open_dialog();
				})
				itemli.append(item_name_span);

				var item_value_span = $("<span>",{class: "itemvalue", "data-item_name":item.item_name, "data-type": item.type});

				let type_args = {};
				if(item.hasOwnProperty("type_args")){
					type_args = item.type_args;
				}
				
				try{
					let module_spec = get_module_html(item.type, item.item_name);
					let item_module = $(module_spec.html);
					item_module[0].type_args = type_args
			
					render(html`${item_module}`, item_value_span[0]);
					//item_value_span.append(.append($("<module-switch>"));
				//	item_value_span.append(module_spec.html.getTemplateElement());				
					
					itemli.append(item_value_span);
					
					//request current value
					let item_message = new ItemMessage(item.item_name, ItemMessage.GET, null)
					let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_ITEM, "client", null, null, item_message)

					roomul.append(itemli);
				
				} catch(e){
					if(e instanceof InvalidModuleExeption)
						return; //jquery .each equiv of continue
					else throw e;
				}
			});
			roomli.append(roomul);
			$("#rooms").append(roomli);		
			console.log("Finished room setup for: " + room);
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


