export default class SubscribeMessage {
/*
	Class fields not supported by FF android(plus others) yet.

	subscriptions;
*/
    
	constructor(subscriptions, type, get_now){ //get_now causes the current values to be sent after subscribing
		if(typeof subscriptions == 'undefined')
			console.error("Item name must be supplied");
		
		this.subscriptions = subscriptions;
        this.type = type;
        this.get_now = get_now
	}
	
	static get SUBSCRIBE(){
		return "SUBSCRIBE";
	}
	static get REFRESH_ALL(){
		return "REFRESH_ALL";
	}
	static get REQUEST_VALUES() {
		return "REQUEST_VALUES";
	}
	
	to_json(){
		return JSON.stringify({
			subscriptions: this.subscriptions,
			type: this.type,
			get_now: this.get_now
		});
	}
	
}