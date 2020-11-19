import { LitElement, html } from "../node_modules/lit-element/lit-element.js";
import { IronCheckedElementBehavior, IronCheckedElementBehaviorImpl } from "../node_modules/@polymer/iron-checked-element-behavior/iron-checked-element-behavior.js";
export class simple_toggle extends LitElement {
  constructor() {
    super();
    this.value = false;
  }

  static get properties() {
    return {
      value: {
        type: Boolean
      }
    };
  }

  render() {
    let state = this.value;

    if (state == true) {
      return html`<p>ON</p>`;
    } else {
      return html`<p>OFF</p>`;
    }
  }

}
customElements.define('simple-toggle', simple_toggle);