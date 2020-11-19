import { html, LitElement } from "./node_modules/lit-element/lit-element.js";
/**
 * `pion-element`
 * Home Automation
 *
 * @customElement
 * @polymer
 * @demo demo/index.html
 */

class PionElement extends LitElement {
  static get template() {
    return html`
      <style>
        :host {
          display: block;
        }
      </style>
      <h2>Hello [[prop1]]!</h2>
    `;
  }

  static get properties() {
    return {
      prop1: {
        type: String,
        value: 'pion-element'
      }
    };
  }

}

window.customElements.define('pion-element', PionElement);