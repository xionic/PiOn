
import React from 'react';
import ModuleBase from '../ModuleBase.js';
import TextField from '@material-ui/core/TextField';
import Button from '@material-ui/core/Button';
import Value from '../../Value.js';
import './ModuleSetpoint.css';


class ModuleSetpoint extends ModuleBase {


    constructor(props) {
        super(props);
        this.handleChange = this.handleChange.bind(this);
        this.handlePlus = this.handlePlus.bind(this);
        this.handleMinus = this.handleMinus.bind(this);

        this.increment = 0.5;
    }

    handleChange(e) {
        console.log("CHANGE", e.target.value);
        let newVal = Value.fromData(e.target.value);
        this.sendNewValue(newVal);
    }

    handlePlus(e) {
        let num = parseFloat(this.props.value.data) + this.increment;
        let newVal = Value.fromData(num);
        this.sendNewValue(newVal);
    }

    handleMinus(e) {
        let num = parseFloat(this.props.value.data) - this.increment;
        let newVal = Value.fromData(num);
        this.sendNewValue(newVal);
    }

    sendNewValue(v) {
        this.props.onValueChange(this.props.item_name, v);
    }

    pionRender() {
        return <span>
            <TextField onChange={this.handleChange} size="small" type="number" value={this.props.value.data} classes={{ root: "pion-modulesetpoint" }} inputProps={{ step: this.increment }} />
            <Button onClick={this.handlePlus}>+</Button>
            <Button onClick={this.handleMinus}>-</Button>
        </span>;
    }
}

export default ModuleSetpoint;
/*

*/