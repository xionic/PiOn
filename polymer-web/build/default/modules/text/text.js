import { html, css } from "../../node_modules/lit-element/lit-element.js";
import { pion_base } from '../pion_base.js';
import { register_module } from '../../main.js';
export class module_text extends pion_base {
  static get properties() {
    return {
      val: {
        type: Object
      }
    };
  }

  constructor() {
    super();
    this.val = {
      data: "LOADING"
    };
  }

  pion_render() {
    return html`<span>${this.val.data}</span>`;
  }

  static get styles() {
    return [super.styles];
  }

  set_value(value) {
    this.val = value;
  }

  get_value() {
    return this.val;
  }

}
customElements.define('module-text', module_text);
$().ready(function () {
  register_module({
    name: "text"
  });
});