import {LitElement, html, css}  from 'lit-element';
import {pion_base} from '../pion_base.js';
import {register_module, getTimestamp, send_update} from '../../main.js';
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
	}
	
	switch_changed(){
		alert("here");
	}
	
	static get styles(){
		return css`
			.itemmodule {
				float: left;
				display: inline;
				border: red solid 2px;				
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
			return html`<p></p>`;
		} else {
			console.log(this.state);
			return html`				
				<module-text noupdate class="itemmodule" therm_module="temp" val="${this.current_temp}"></module-text>
				
				<module-number noupdate class="itemmodule" therm_module="setpoint"  @pion_change="${this.onpion_change}"  val="${this.setpoint}"></module-number>
				
				<module-switch noupdate class="itemmodule" therm_module="state"  @pion_change="${this.onpion_change}" val="${this.state}"></module-switch>
				
				<module-switch noupdate class="itemmodule" therm_module="heater_state"   val="${this.heater_state}"></module-switch>
			`;
		}
	}
	
	set_value(value){
		this.current_temp = value.data.current_temp.data;
		this.setpoint = value.data.setpoint.data;
		this.state = value.data.state.data ? true : false;
		this.heater_state = value.data.heater_state.data;
		this.has_received_first_update = true;
	}
	
	/*get_value(){
		
	}*/
}

customElements.define('module-thermostat', module_thermostat);

$().ready(function(){
	register_module({
		name:"thermostat"
	});
});

