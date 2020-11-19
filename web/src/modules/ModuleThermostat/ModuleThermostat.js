
import React from 'react';
import ModuleBase from '../ModuleBase.js';
import ModuleSetpoint from '../ModuleSetpoint/ModuleSetpoint.js';
import ModuleTemperature from '../ModuleTemperature/ModuleTemperature.js';
import ModuleToggleSwitch from '../ModuleToggleSwitch/ModuleToggleSwitch.js';


class ModuleThermostat extends ModuleBase {

    constructor(props) {
        super(props);
        this.handleChange = this.handleChange.bind(this);
    }

    handleChange(item_name, value){
        this.props.onValueChange(item_name, value);
    }

    pionRender() {
        this.setpoint = this.props.value.data.setpoint;
        this.heater_switch = this.props.value.data.heater_switch;
        this.state_switch = this.props.value.data.state_switch;
        this.temp_item = this.props.value.data.temp_item;

        if (!this.props.all_values.hasOwnProperty(this.setpoint)){
            throw Error("Thermostat setpoint item has no value.Is it the correct item?");
        }
        if (!this.props.all_values.hasOwnProperty(this.heater_switch)) {
            throw Error("Thermostat heater_switch item has no value.Is it the correct item?");
        }
        if (!this.props.all_values.hasOwnProperty(this.state_switch)) {
            throw Error("Thermostat state_switch item has no value.Is it the correct item?");
        }
        if (!this.props.all_values.hasOwnProperty(this.temp_item)) {
            throw Error("Thermostat temp_item item has no value.Is it the correct item?");
        }

        return <span>
            <ModuleToggleSwitch item_name={this.state_switch} value={this.props.all_values[this.state_switch]} all_values={this.props.all_values} onValueChange={this.handleChange}></ModuleToggleSwitch>

            <ModuleSetpoint item_name={this.setpoint} value={this.props.all_values[this.setpoint]} all_values={this.props.all_values} onValueChange={this.handleChange}></ModuleSetpoint>

            <ModuleTemperature item_name={this.temp_item} value={this.props.all_values[this.temp_item]} all_values={this.props.all_values}></ModuleTemperature>

            <ModuleToggleSwitch item_name={this.heater_switch} value={this.props.all_values[this.heater_switch]} all_values={this.props.all_values} disabled={true}></ModuleToggleSwitch>
            </span>
    }
}

export default ModuleThermostat;
/*

*/