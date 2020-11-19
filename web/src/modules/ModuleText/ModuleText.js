
import React from 'react';
import ModuleBase from '../ModuleBase.js';


class ModuleText extends ModuleBase {

    constructor(props) {
        super(props);
        this.text_prefix = "";
        this.text_postfix = "";
    }

    pionRender() {
        this.updatePrefix();
        this.updatePostfix();
        return <span>{this.text_prefix}{this.props.value.data}{this.text_postfix}</span>
    }

    /*
    * "Abstract" - Called during render - override to update subclass's prefix
    */
    updatePrefix() {

    }

    /*
    * "Abstract" - Called during render - override to update subclass's postfix
    */
    updatePostfix() {

    }
}

export default ModuleText;
/*

*/