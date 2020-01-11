import {LitElement, html}  from 'lit-element';
import '@polymer/paper-toggle-button/paper-toggle-button.js';
import {register_module, item_updated} from '../../main.js';

export class module_switch extends LitElement {
	static get properties() {
		return { 
			name: { type: String },
			state: { type: Boolean}
		};
	}

	constructor() {
		super();
		/*this.addEventListener('change', function(){
		alert("COSNT CHANGE");
		});*/
		this.state = false; //default

	}

	elem_changed(s){
		this.state = this.shadowRoot.querySelector("paper-toggle-button").checked;
		item_updated(this.parentNode.dataset.item_name, this.state);
	}

	update_value(data){
		this.state = data.value?true:false;
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

