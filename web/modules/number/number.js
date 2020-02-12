import {html,css}  from 'lit-element';
import {pion_base} from '../pion_base.js';
import {register_module} from '../../main.js';


export class module_number extends pion_base {
	static get properties() {
		return { 
			val: { type: Number }
		};
	}

	constructor() {
		super();
		this.val = "999";
	}	

	render() {
		return html`<input @change="${this.on_change}" .value="${this.val}" type="number">`;
	}

	static get styles(){
		return css`
			input {
				width:100%;
			}			
		`}
	
	set_value(value){
		this.val = value.data;	  
	}
	
	get_value(){
		return this.shadowRoot.querySelector("input").value;
	}
}

customElements.define('module-number', module_number);

$().ready(function(){
	register_module({
		name:"number"
	});
});

