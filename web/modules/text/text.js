import {html}  from 'lit-html';
import {pion_base} from '../pion_base.js';
import {register_module} from '../../main.js';

export class module_text extends pion_base {
	static get properties() {
		return { 
			val: { type: String }
		};
	}

	constructor() {
		super();
		this.val = "PLACEHOLDER";
	}

	render() {
		return html`<span>${this.val}</span>`;
	}
	
	set_value(value){
		this.val = value.data;	  
	}
	
	get_value(){
		return this.val;
	}
}





customElements.define('module-text', module_text);

$().ready(function(){
	register_module({
		name:"text"
	});
});

