import {html, css}  from 'lit-element';
import '@polymer/paper-toggle-button/paper-toggle-button.js';
import {pion_base} from '../pion_base.js';
import {register_module} from '../../main.js';
import {Value} from '../../Value.js';

/*
* Values for the attribute val MUST be boolean, not 1|0
*/
export class module_switch extends pion_base {
	static get properties() {
		return { 
			state: { type: Boolean}
		};
	}

	constructor() {
		super();
		this.state = false //default
		//console.log("--------type_args:", this.type_args, this);
	}
	
	get_value(){
		//console.log("getval this", this.shadowRoot.c;
		return this.querySelector("paper-toggle-button").checked? 1 : 0;
	}

	set_value(value){
		//console.log(data, this.val);
		this.state = value.data ? true : false;
		//console.log(data, this.val);
	}

	pion_render() {
		/*let on_img = this.getAttribute("on_img");
		let off_img = this.getAttribute("off_img");*/

		let disabled = this.getAttribute("disabled") === null ? false : true
		this.state = this.state ? true : false;

	
		if(this.type_args.hasOwnProperty("type")){
			if (this.type_args.type == "simple_toggle"){
				return html`<simple-toggle ?disabled='${disabled}' @change="${this.on_change}" .value="${this.state}" ></simple-toggle>`;
			}
		}
		
		return html`<paper-toggle-button ?disabled='${disabled}' @change="${this.on_change}" ?checked="${this.state}"></paper-toggle-button>`;		
	}
}

customElements.define('module-switch', module_switch);

$().ready(function(){
	register_module({
		name:"switch"		
	});
});

