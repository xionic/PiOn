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
	
	static get styles(){
		return css`
			.itemmodule {
				/*float: left;*/
				display: inline-block;				
			}
			module-number {
				width:45px;
			}
			* {
				padding: 0 5px;
			}
			module-switch[therm_module="state"] {
				padding-right:2px;
				position: relative;
				left:10px;
			}
			module-text[therm_module="temp"] {
				padding-right:0px;
			}
	`}
	
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

	render() {
		if(!this.updateReceived){
			return html`<span>LOADING...</span>`;
		}
		return html`
			<module-switch item_name="${this.state_switch}" class="itemmodule" therm_module="state"></module-switch>

			<module-temperature item_name="${this.temp_item}" class="itemmodule" therm_module="temp"></module-temperature>

			<module-number item_name="${this.setpoint}" class="itemmodule" therm_module="setpoint"></module-number>			
			
			<module-switch item_name="${this.heater_switch}" disabled class="itemmodule" therm_module="heater_state"></module-switch>
		`;
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

