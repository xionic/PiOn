import {LitElement, html}  from 'lit-element';
import '@polymer/paper-toggle-button/paper-toggle-button.js';
import {register_module, item_updated} from '../../main.js';
import {Value} from '../../Value.js';

export class module_switch extends LitElement {
	static get properties() {
		return { 
			state: { type: Boolean}
		};
	}

	constructor() {
		super();
		/*this.addEventListener('change', function(){
		alert("COSNT CHANGE");
		});*/
		this.state = false //default

	}

	elem_changed(){
		var checked_state = this.shadowRoot.querySelector("paper-toggle-button").checked;
		var new_val = new Value(checked_state ? 1 : 0);
		//this.state = checked_state;
		item_updated(this.parentNode.dataset.item_name, new_val);
	}

	update_value(value){
		//console.log(data, this.state);
		this.state = value.data ? true : false;
		//console.log(data, this.state);
	}

	render() {
		return html`<paper-toggle-button @change="${this.elem_changed}" ?checked="${this.state}"></paper-toggle-button>`;
	}
}

customElements.define('module-switch', module_switch);

$().ready(function(){
	register_module({
		name:"switch",
		update: function(container_elem, data){
			this.state = data.state?true:false;
		}
	});
});

