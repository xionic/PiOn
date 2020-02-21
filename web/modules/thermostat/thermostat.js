import {LitElement, html, css}  from 'lit-element';
import {pion_base} from '../pion_base.js';
import {register_module, getTimestamp, send_update, shadow_selector} from '../../main.js';
import {Value} from '../../Value.js';


export class module_thermostat extends pion_base {
	
	static get properties() {
		return { 		
			current_temp: {Number},
			setpoint : {Number},
			state: {Number},
			heater_state: {Number}
		};
	}

	constructor() {
		super();
		this.has_received_first_update = false; // has the element received at least one value 
		this.has_rendered = false;
		
		
	}
	
	switch_changed(){
		alert("here");
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
		if(!this.has_received_first_update){
			return html`<p>LOADING...</p>`;
		} else {
			this.has_rendered = true;
			return html`

				<module-switch noupdate class="itemmodule" therm_module="state"  @pion_change="${this.onpion_change}"></module-switch>

				<module-text noupdate class="itemmodule" therm_module="temp"></module-text>
				&deg;C
				<module-number noupdate class="itemmodule" therm_module="setpoint" @pion_change="${this.onpion_change}"></module-number>			
				
				<module-switch disabled noupdate class="itemmodule" therm_module="heater_state"></module-switch>
			`;
		}
	}
	
	set_value(value){	

		if(value.data.hasOwnProperty("current_temp")){
			this.current_temp = value.data.current_temp.data;
			$(this.shadowRoot).arrive("[therm_module='temp']", {existing: true}, function() {
				// 'this' refers to the newly created element
				this.set_value(value.data.current_temp)
			});
		}

		if(value.data.hasOwnProperty("heater_state")){
			this.heater_state = value.data.heater_state.data;
			$(this.shadowRoot).arrive("[therm_module='heater_state']", {existing: true}, function() {
				// 'this' refers to the newly created element
				this.set_value(value.data.heater_state)
			});
		}

		if(value.data.hasOwnProperty("setpoint")){
			this.setpoint = value.data.setpoint.data;
			$(this.shadowRoot).arrive("[therm_module='setpoint']", {existing: true}, function() {
				// 'this' refers to the newly created element
				this.set_value(value.data.setpoint)
			});
		}

		if(value.data.hasOwnProperty("state")){
			this.state = value.data.state.data ? true : false;
			$(this.shadowRoot).arrive("[therm_module='state']", {existing: true}, function() {
				// 'this' refers to the newly created element
				this.set_value(value.data.state)
			});
		}

		this.has_received_first_update = true;
	}
}

customElements.define('module-thermostat', module_thermostat);

$().ready(function(){
	register_module({
		name:"thermostat"
	});
});

