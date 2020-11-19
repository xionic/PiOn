import {LitElement, html, css}  from 'lit-element';
import {pion_base} from '../pion_base.js';
import {register_module, getTimestamp, send_update, ws_subscribe} from '../../main.js';
import {Value} from '../../Value.js';


export class module_thermostat extends pion_base {
	
	static get properties() {
		return { 		
			temp_item: {String},
			setpoint : {String},
			state_switch: {String},
			heater_switch: {String}
		};
	}

	constructor() {
		super();
		this.updateReceived = false;		
	}	
	
	onpion_change(ev){		
		switch(ev.originalTarget.getAttribute("therm_module")){			
			case "setpoint":
				this.setpoint = ev.originalTarget.get_value();
				break;
			case "state":
				this.state = ev.originalTarget.get_value();
				break;
		}
		
		send_update(this, this.getAttribute("item_name"), new Value({
				setpoint: new Value(this.setpoint),
				state: new Value(this.state)
			},false, null, getTimestamp(), Value.CERTAIN)
		);
	}	

	pion_render(){

		let elem_state = $(`
			<module-switch item_name="${this.state_switch}" class="itemmodule" therm_module="state"></module-switch>
		`)[0];
		elem_state.type_args = this.type_args.hasOwnProperty("state_switch") ? this.type_args.state_switch:{};
		
		let elem_temp = $(`
			<module-temperature item_name="${this.temp_item}" class="itemmodule" therm_module="temp" .type_args=${this.type_args.temp_item}></module-temperature>
		`)[0];
		elem_temp.type_args = this.type_args.hasOwnProperty("temp_item") ? this.type_args.temp_item : {};

		let elem_setpoint = $(`
			<module-number item_name="${this.setpoint}" class="itemmodule" therm_module="setpoint"></module-number>
		`)[0];
		elem_setpoint.type_args = this.type_args.hasOwnProperty("setpoint") ? this.type_args.setpoint : {};

		let elem_heater = $(`
			<module-switch item_name="${this.heater_switch}" disabled class="itemmodule" therm_module="heater_state"></module-switch>
		`)[0];
		elem_heater.type_args = this.type_args.hasOwnProperty("heater_switch") ? this.type_args.heater_switch : {};

		let container = document.createElement("SPAN");
		container.appendChild(elem_state);
		container.appendChild(elem_temp);
		container.appendChild(elem_setpoint);
		container.appendChild(elem_heater);
		return html`${container}`;
	}
	
	//the "value" of a thermostat item defines the items that comprise it
	set_value(value){	
		this.temp_item = value.data.temp_item;
		this.setpoint = value.data.setpoint;
		this.state_switch = value.data.state_switch;
		this.heater_switch = value.data.heater_switch;
		this.updateReceived = true;
	}
}

customElements.define('module-thermostat', module_thermostat);

$().ready(function(){
	register_module({
		name:"thermostat"
	});
});

