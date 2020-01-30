import { LitElement, html } from "../../node_modules/lit-element/lit-element.js";
import { register_module } from '../../main.js';
export class module_text extends LitElement {
  static get properties() {
    return {
      text_val: {
        type: String
      }
    };
  }

  constructor() {
    super();
    this.text_val = "PLACEHOLDER";
  }

  render() {
    return html`<p>${this.text_val}<p>`;
  }

  update_value(value) {
    this.text_val = value.data;
  }

}
customElements.define('module-text', module_text);
$().ready(function () {
  register_module({
    name: "text",
    update: function (container_elem, value) {
      console.log(container_elem);
      this.text_val = value.data;
    }
  });
});