import { html, css } from "../../node_modules/lit-element/lit-element.js";
import { pion_base } from '../pion_base.js';
import { register_module } from '../../main.js';
export class module_number extends pion_base {
  static get properties() {
    return {
      val: {
        type: Number
      }
    };
  }

  constructor() {
    super();
    this.spinner_created = false; //this.container = document.createElement("INPUT");
  }

  updated() {
    if (this.hasReceivedvalue && !this.spinner_created) {
      self = this;
      this.spinner = $(this.querySelector("input")).spinner();
      this.spinner.on("spinchange", function (ev, ui) {
        self.on_change(ev);
      });
      this.spinner.on("spin", function (ev, ui) {
        self.val = ui.value;
        self.on_change(ev);
      });
      this.spinner_created = true;
    }
  }

  pion_render() {
    return html`<input @change="${this.on_change}" .value="${this.val}" type="number" step="0.5">`; //return html`${this.container}`;
  }

  set_value(value) {
    this.val = value.data;
  }

  get_value() {
    return this.val;
  }

}
customElements.define('module-number', module_number);
$().ready(function () {
  register_module({
    name: "number"
  });
});