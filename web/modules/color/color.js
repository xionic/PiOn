import {html,css}  from 'lit-element';
import {pion_base} from '../pion_base.js';
import {register_module} from '../../main.js';

export class module_color extends pion_base {
	static get properties() {
		return { 
			val: { type: Object }
		};
	}

	constructor() {
		super();
		this.val = {data: "LOADING"};
        this.chooser_created = false;
       
	}

    updated(){
        if(this.hasReceivedvalue && !this.spinner_created){
            var self = this;
            $(this.querySelector("input")).spectrum({
                type : "component",
                clickoutFiresChange : true,
                change: function(color){
                    self.set_value(color.toHsv());
                    self.on_change(new Event("change"));
                }
            });
        }
    }

	pion_render() {     
		return html`<input value="ff0000"/>`;
	}

	static get styles() {
		return [
			super.styles
		];
	}
	
	set_value(value){
		this.val = value;
	}
	
	get_value(){
		return this.val;
	}
}





customElements.define('module-color', module_color);

$().ready(function(){
	register_module({
		name:"color"
	});
});

