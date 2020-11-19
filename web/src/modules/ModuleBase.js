
import React, { Component } from 'react';

/* 
*    Abstract base class
*/
class ModuleBase extends Component {

    constructor(props){
        super(props);
        if (!props.hasOwnProperty("value") || props.value.constructor.name !== "Value"){
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