import {LitElement, html}  from 'lit-element';
import {register_module} from '../../main.js';

export class module_thermostat extends LitElement {
	static get properties() {
		return { 
			text_val: { type: String }
		};
	}

	constructor() {
		super();
		this.text_val = "PLACEHOLDER";
	}

	render() {
		return html`<p>${this.text_val}<p>`;
	}
	
	update_value(value){
		console.log(value);
		this.text_val = value.data;	  
	}
}

customElements.define('module-thermostat', module_thermostat);

$().ready(function(){
	register_module({
		name:"thermostat",
		update: function(container_elem, value){
			this.text_val = value.data;
		}
	});
});

