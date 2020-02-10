import { html } from "../../node_modules/lit-html/lit-html.js";
import "../../node_modules/@polymer/paper-toggle-button/paper-toggle-button.js";
import { pion_base } from '../pion_base.js';
import { register_module } from '../../main.js';
import { Value } from '../../Value.js';
/*
* Values for the attribute val MUST be boolean, not 1|0
*/

export class module_switch extends pion_base {
  static get properties() {
    return {
      val: {
        type: Boolean
      }
    };
  }

  constructor() {
    super();
    this.val = false; //default			
  }

  get_value() {
    //console.log("getval this", this.shadowRoot.c;
    return this.shadowRoot.querySelector("paper-toggle-button").checked ? 1 : 0;
  }

  set_value(value) {
    //console.log(data, this.val);
    this.val = value.data ? true : false; //console.log(data, this.val);
  }

  render() {
    var disabled = this.getAttribute("disabled") === null ? false : true;
    this.val = this.val ? true : false;
    return html`<paper-toggle-button ?disabled='${disabled}' @change="${this.on_change}" ?checked="${this.val}"></paper-toggle-button>`;
  }

}
customElements.define('module-switch', module_switch);
$().ready(function () {
  register_module({
    name: "switch"
  });
});