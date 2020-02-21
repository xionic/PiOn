export class SubscribeMessage {
/*
	Class fields not supported by FF android(plus others) yet.

	item_names;
*/
    
	constructor(item_names, type, get_now){ //get_now causes the current values to be sent after subscribing
		if(typeof item_names == 'undefined')
			console.error("Item name must be supplied");
		
		this.item_names = item_names;
        this.type = type;
        this.get_now = get_now
	}
	
	static get SUBSCRIBE(){
		return "SUBSCRIBE";
	}
	static get REFRESH_ALL(){
		return "REFRESH_ALL";
	}
	
	to_json(){
		return JSON.stringify({
			item_names: this.item_names,
			type: this.type,
			get_now: this.get_now
		});
	}
	
}