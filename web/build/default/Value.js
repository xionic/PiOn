export class Value {
	value;
	has_error;
	error_message;
	timestamp;
	certainty; // null for set requests
	
	constructor(value, has_error, error_msg, timestamp, certainty){
		this.data = value;
		this.has_error = has_error;
		this.error_message = error_msg;
		this.timestamp = timestamp;
		this.certainty = certainty;
	}
	
	static get CERTAIN() {
		return "CERTAIN";
	}
	static get UNCERTAIN() {
		return "UNCERTAIN";
	}
	static get UNKNOWN() {
		return "UNKNOWN";
	}
}