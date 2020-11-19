import {LitElement, html, css}  from 'lit-element';
import {register_module, send_update, ws_subscribe} from '../../main.js';
import {Value} from '../../Value.js';
import { SubscribeMessage } from '../SubscribeMessage.js';
import { ItemEvent } from '../ItemEvent.js';

export class pion_base extends LitElement {

	static get properties(){
		return {
			"val": {}
		};
	}
	
	constructor(){
		super();
		this.hasFirstUpdated = false;
		this.hasReceivedvalue = false;

		if (!this.hasOwnProperty("type_args")) {
			this.type_args = {};
		}

		this.module_name = this.constructor.name.substring(7);
		let link = document.createElement("LINK");
		link.rel = "stylesheet";
		link.type = "text/css";
		link.href = "modules/" + this.module_name + "/" + this.module_name + ".css";
		document.head.appendChild(link);
	}

	firstUpdated(){
		this.hasFirstUpdated = true;
		let items = {};
		items[this.getAttribute("item_name")] = [ItemEvent.events.ITEM_VALUE_CHANGED];
		ws_subscribe(items, this, SubscribeMessage.REQUEST_VALUES);
	}

	pion_render(){
		throw new Error("pion_render must be overridden");
	}

	render(){
		if (!this.hasReceivedvalue) {
			return html`<img class="loading" src="img/loading.png">`;
		} else if (this.val.has_error) {
			return html`<img class="loading" src="img/alert.png" @click=${this.open_dialog}>`;
		} else {
			return this.pion_render();
		}
	}

	get item_name(){
		return this.getAttribute("item_name");
	}

	createRenderRoot() {
		return this;
	}

	open_dialog(){
		let div = document.createElement("DIV");
		let msg = "";

		if (typeof (this.val) !== "undefined"){

			var val_timestamp = new Date(this.val.timestamp * 1000);
			let year = "" + val_timestamp.getFullYear();
			let month = ("" + (val_timestamp.getMonth() + 1)).padStart(2, '0');
			let day = ("" + val_timestamp.getDate()).padStart(2, '0');
			let hours = ("" + val_timestamp.getHours()).padStart(2, '0');
			let mins = ("" + val_timestamp.getMinutes()).padStart(2, '0');
			let secs = ("" + val_timestamp.getSeconds()).padStart(2, '0');
			let ts_string = hours + ":" + mins + ":" + secs + " " + year + "/" + month + "/" + day;

			msg += "<ul class='item_dialog'><li>Name: " + this.getAttribute("item_name") + "</li>";
			msg += "<li>Value data: " + this.val.data + "</li>";
			msg += "<li>Value has_error: " + this.val.has_error + "</li>";
			msg += "<li>Value error_message: " + this.val.error_message + "</li>";
			msg += "<li>Value timestamp: " + ts_string + "</li>";
			msg += "<li>Value certainty: " + this.val.certainty + "</li></ul>";
		} else {
			msg = "No value yet";
		}
		div.innerHTML = msg;
		$(div).dialog({ 
			modal: true,
			width: "80%", 
			open: function (event, ui) {
				$('.ui-widget-overlay').bind('click', function () {
					$(div).dialog('close');
				});
			},
			buttons: [
				{
					text: "OK",
					click: function () {
						$(this).dialog("close");
					},

					// Uncommenting the following line would hide the text,
					// resulting in the label being used as a tooltip
					showText: false
				}
			]
		});
	}

	/*
	* All modules should fire a 'pion_change' event on change, since change events do not go through the shadow dom
	*/	
	on_change(ev){
		this.val =  this.get_value();
		// hack to get around change events not bubbling through the shadow dom
		this.dispatchEvent(new CustomEvent('pion_change', { 
			bubbles: true,
			composed: true
		}));
		
		if(!this.hasAttribute("noupdate")){ //Send value to server		
			send_update(this, this.getAttribute("item_name"), new Value(this.val));			
		}
		
	}
	
	//sets the value of this item from an external update
	_set_value(value){

		this.hasReceivedvalue = true;
		
		if(value.has_error == true){
			console.log("Value for " + this.item_name + " has an error", value);
			//this.classList.add("failed");
			this.val = value;
			this.hasReceivedvalue = true;
			return;
			
		}
		if(this.val != value){
			this.val = value;
			if(typeof this.set_value === 'function'){
				this.set_value(value);
			}
			
			this.requestUpdate();
		}
	}
	
	//should return a value from the actual DOM if possible
	_get_value(){
		throw new Error("get_value must be overridden");
	}
	
}