import Value from './Value.js';

export default class ItemMessage {
/*
	Class fields not supported by FF android(plus others) yet.

	item_name;
	value;
	action;
	action_success;
	error_msg;
*/
	constructor(item_name, action, value){
		if(typeof item_name == 'undefined')
			console.error("Item name must be supplied");
		if(typeof action == 'undefined')
			console.error("Action must be supplied");
		if(typeof value == 'undefined')
			console.error("Value must be supplied");
		
		this.item_name = item_name;
		this.value = value;
		this.action = action;
	}
	
	static get GET(){
		return "GET";
	}
	static get SET(){
		return "SET";
	}

	static from_json(json){
		let obj = JSON.parse();
		return this.from_obj(obj);
	}

	static from_obj(obj){
		let v = obj.value;
		return new ItemMessage(obj.item_name, obj.action, new Value(v.data, v.has_error, v.error_message, v.timestamp, v.certainty));
	}
	
	to_json(){
		return JSON.stringify({
			item_name: this.item_name,
			value: this.value,
			action: this.action
		});
	}
	
}