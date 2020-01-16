export class ItemMessage {
	
	item_name;
	value;
	action;
	action_success;
	error_msg;
	
	constructor(item_name, value, action, action_success, error_msg){
		this.item_name = item_name;
		this.value = value;
		this.action = action;
		this.action_success = action_success;
		this.error_msg = error_msg;
	}
	
	static get GET(){
		return "get";
	}
	static get SET(){
		return "set";
	}
	
	to_json(){
		return JSON.stringify({
			item_name: this.item_name,
			value: this.value,
			action: this.action,
			action_success: this.action_success,
			error_msg: this.error_msg
		});
	}
	
}