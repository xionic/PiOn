
export default class RestMessage {
/*
	Class fields not supported by FF android(plus others) yet.

	type; //req or resp (set requests are answered by resp's)
	context; //currently "ITEM" or "EVENT"
	sending_node;
	target_node; //probably only populated for req's
	target_port; // same
	payload; // object passed as args to the get/set handler function as type appropriate
*/
	static REST_MESSAGE_COUNTER;
	constructor(type, context, sending_node, target_node, target_port, payload){
		this.type = type;
		this.context = context;
		this.sending_node = sending_node;
		this.target_node = target_node;
		this.target_port = target_port;
		this.payload = payload;
		this.id = this.REST_MESSAGE_COUNTER++;
	}

	//"types"
	static get REQ() {
		return "REQ";
	}
	static get RESP() {
		return "RESP";
	}

	//contexts
	static get REST_CONTEXT_EVENT() {
		return "REST_CONTEXT_EVENT";
	}
	static get REST_CONTEXT_ITEM() {
		return "REST_CONTEXT_ITEM";
	}
	static get REST_CONTEXT_ITEMS() {
		return "REST_CONTEXT_ITEMS";
	}
	static get REST_CONTEXT_SUBSCRIBE() {
		return "REST_CONTEXT_SUBSCRIBE";
	}
	static get REST_CONTEXT_ERROR() {
		return "REST_CONTEXT_ERROR";
	}

	

	static from_obj(obj){
		return new RestMessage(
			obj.type,
			obj.context,
			obj.sending_node,
			obj.target_node,
			obj.target_port,
			obj.payload
		)
	}

	static from_json(json){
		let obj = JSON.parse(json);
		return RestMessage.from_obj(obj);
	}

	static withSendDefaults(type, context, payload){
		return new RestMessage(type, context, "client", window.config.host, window.config.port, payload);
	}
	
	to_json(){
		return JSON.stringify({
			type: this.type,
			context: this.context,
			sending_node: this.sending_node,
			target_node: this.target_node,
			target_port: this.target_port,
			payload: this.payload,
			id: this.id
		});
	}
}