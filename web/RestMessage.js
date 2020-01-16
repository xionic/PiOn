export class RestMessage {

	type; //req or resp (set requests are answered by resp's)
	sending_node;
	target_node; //probably only populated for req's
	target_port; // same
	payload; // object passed as args to the get/set handler function as type appropriate
	
	constructor(type, sending_node, target_node, target_port, payload){
		this.type = type;
		this.sending_node = sending_node;
		this.target_node = target_node;
		this.target_port = target_port;
		this.payload = payload;
	}
	//"types"
	static get REQ() {
		return "req";
	}
	static get RESP() {
		return "resp";
	}
	
	to_json(){
		return JSON.stringify({
			type: this.type,
			sending_node: this.sending_node,
			target_node: this.target_node,
			target_port: this.target_port,
			payload: this.payload
		});
	}
}