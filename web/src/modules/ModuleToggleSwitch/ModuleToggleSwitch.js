
import React from 'react';
import ModuleBase from '../ModuleBase.js';
import Switch from '@material-ui/core/Switch';
import Value from '../../Value.js';


class ModuleToggleSwitch extends ModuleBase {

    constructor(props) {
        super(props);
        this.handleChange = this.handleChange.bind(this);
    }

    handleChange(e) {
        let newVal = Value.fromData(e.target.checked ? 1 : 0);
        this.props.onValueChange(this.props.item_name, newVal);
    }

    pionRender() {      
        const curVal = this.props.value.data === 1;
        return <span><Switch disabled={this.props.hasOwnProperty("disabled")?this.props.disabled:false} onChange={this.handleChange} checked={curVal}  className="react-switch" /></span>
    }
}

export default ModuleToggleSwitch;
/*

*/