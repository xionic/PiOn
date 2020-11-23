class Value {
	
/*
	Class fields not supported by FF android(plus others) yet.

	data;
	has_error;
	error_message;
	timestamp;
	certainty; // null for set requests
	initialised; // false when no value is yet available
*/
	
	constructor(data, has_error, error_msg, timestamp, certainty){
		this.data = data;
		this.has_error = has_error;
		this.error_message = error_msg;
		this.timestamp = timestamp;
		this.certainty = certainty;
		this.initialised = true;
	}

	/*
		Simple function to generate a value object with defaults just from data
	*/
	static fromData(data){
		return new Value(data, false, "", Math.floor(Date.now() / 1000), Value.CERTAIN);
	}

	static getUninitialised(){
		let v = new Value(null,null,null,null,null);
		v.initialised = false;
		return v;
	}

	static checkValid(value){
		if(
			value.hasOwnProperty("data")
			&& value.hasOwnProperty("has_error")
			&& value.hasOwnProperty("error_message")
			&& value.hasOwnProperty("timestamp")
			&& value.hasOwnProperty("certainty")
			&& value.hasOwnProperty("initialised")
		) {
			return true;
		} else {
			return false;
		}
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

export default Value;