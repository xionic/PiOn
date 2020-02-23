import {LitElement, html}  from 'lit-element';
import { register_module, send_update, ws_subscribe} from '../../main.js';
import {Value} from '../../Value.js';
import { SubscribeMessage } from '../SubscribeMessage.js';

export class pion_base extends LitElement {
	
	constructor(){
		super();		
	}

	firstUpdated(props){
		let items = {};
		items[this.getAttribute("item_name")] = ["ITEM_VALUE_CHANGED"];
		ws_subscribe(this, items, SubscribeMessage.REQUEST_VALUES);
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
	set_value(value){
		throw new Error("set_value must be overridden");
	}
	
	//should return a value from the actual DOM if possible
	get_value(){
		throw new Error("get_value must be overridden");
	}
	
}