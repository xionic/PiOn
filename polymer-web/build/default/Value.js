export class Value {
  /*
  	Class fields not supported by FF android(plus others) yet.
  
  	data;
  	has_error;
  	error_message;
  	timestamp;
  	certainty; // null for set requests
  */
  constructor(data, has_error, error_msg, timestamp, certainty) {
    this.data = data;
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