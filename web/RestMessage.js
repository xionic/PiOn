export class RestMessage {

	type; //req or resp (set requests are answered by resp's)
	context; //currently "ITEM" or "EVENT"
	sending_node;
	target_node; //probably only populated for req's
	target_port; // same
	payload; // object passed as args to the get/set handler function as type appropriate
	
	constructor(type, context, sending_node, target_node, target_port, payload){
		this.type = type;
		this.context = context;
		this.sending_node = sending_node;
		this.target_node = target_node;
		this.target_port = target_port;
		this.payload = payload;
	}
	//"types"
	static get REQ() {
		return "REQ";
	}
	static get RESP() {
		return "RESP";
	}
	static get REST_CONTEXT_EVENT() {
		return "REST_CONTEXT_EVENT";
	}
	static get REST_CONTEXT_ITEM() {
		return "REST_CONTEXT_ITEM";
	}
	
	to_json(){
		return JSON.stringify({
			type: this.type,
			context: this.context,
			sending_node: this.sending_node,
			target_node: this.target_node,
			target_port: this.target_port,
			payload: this.payload
		});
	}
}