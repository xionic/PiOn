
import React, { Component } from 'react';
import Value from '../Value.js';

/* 
*    Abstract base class
*/
class ModuleBase extends Component {

    constructor(props){
        super(props);
        if (!props.hasOwnProperty("value") || !Value.checkValid(props.value)){
            throw Error("No or invalid value given to module");
        }
    }

    render() {
        if (!this.props.value.initialised) {
            return <span>No Value yet</span>;
        } else {
            return this.pionRender();
        }
    }
}

export default ModuleBase;
/*

*/