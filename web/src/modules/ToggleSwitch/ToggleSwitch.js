
import React, { Component } from 'react';
import Switch from '@material-ui/core/Switch';
import Value from '../../Value.js';


class ToggleSwitch extends Component {

    constructor(props) {
        super(props);
        this.handleChange = this.handleChange.bind(this);
    }

    handleChange(e) {
        let newVal = Value.fromData(e.target.checked ? 1 : 0);
        this.props.onValueChange(this.props.item_name, newVal);
    }

    render() {
        if (!this.props.value.initialised) {
            return <span>No Value yet</span>;
        } else {
            const curVal = this.props.value.data === 1;
            return <span><Switch onChange={this.handleChange} checked={curVal}  className="react-switch" /></span>
        }
    }
}

export default ToggleSwitch;
/*

*/