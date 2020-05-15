import { LitElement, html, css } from "../node_modules/lit-element/lit-element.js";
import { register_module, send_update, ws_subscribe } from '../../main.js';
import { Value } from '../../Value.js';
import { SubscribeMessage } from '../SubscribeMessage.js';
import { ItemEvent } from '../ItemEvent.js';
export class pion_base extends LitElement {
  static get properties() {
    return {
      "val": {}
    };
  }

  constructor() {
    super();
    this.hasFirstUpdated = false;
    this.hasReceivedvalue = false;

    if (!this.hasOwnProperty("type_args")) {
      this.type_args = {};
    }

    this.module_name = this.constructor.name.substring(7);
    let link = document.createElement("LINK");
    link.rel = "stylesheet";
    link.type = "text/css";
    link.href = "modules/" + this.module_name + "/" + this.module_name + ".css";
    document.head.appendChild(link);
  }

  firstUpdated() {
    this.hasFirstUpdated = true;
    let items = {};
    items[this.getAttribute("item_name")] = [ItemEvent.events.ITEM_VALUE_CHANGED];
    ws_subscribe(items, this, SubscribeMessage.REQUEST_VALUES);
  }

  pion_render() {
    throw new Error("pion_render must be overridden");
  }

  render() {
    if (!this.hasReceivedvalue) {
      return html`<img class="loading" src="img/loading.png">`;
    } else if (this.val.has_error) {
      return html`<img class="loading" src="img/alert.png" @click=${this.open_dialog}>`;
    } else {
      return this.pion_render();
    }
  }

  get item_name() {
    return this.getAttribute("item_name");
  }

  createRenderRoot() {
    return this;
  }

  open_dialog() {
    let div = document.createElement("DIV");
    div.innerText = this.val.error_message;
    $(div).dialog();
  }
  /*
  * All modules should fire a 'pion_change' event on change, since change events do not go through the shadow dom
  */


  on_change(ev) {
    this.val = this.get_value(); // hack to get around change events not bubbling through the shadow dom

    this.dispatchEvent(new CustomEvent('pion_change', {
      bubbles: true,
      composed: true
    }));

    if (!this.hasAttribute("noupdate")) {
      //Send value to server		
      send_update(this, this.getAttribute("item_name"), new Value(this.val));
    }
  } //sets the value of this item from an external update


  _set_value(value) {
    this.hasReceivedvalue = true;

    if (value.has_error == true) {
      console.log("Value for " + this.item_name + " has an error", value); //this.classList.add("failed");

      this.val = value;
      this.hasReceivedvalue = true;
      return;
    }

    if (this.val != value) {
      this.val = value;

      if (typeof this.set_value === 'function') {
        this.set_value(value);
      }

      this._requestUpdate();
    }
  } //should return a value from the actual DOM if possible


  _get_value() {
    throw new Error("get_value must be overridden");
  }

}