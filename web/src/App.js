
import React, { Component } from 'react';
import config from './config.js';
import Value from './Value.js';
import RestMessage from './RestMessage.js';
import ItemMessage from './ItemMessage.js';
import SubscribeMessage from './SubscribeMessage.js';
import ItemEvent from './ItemEvent.js';
import ToggleSwitch from './modules/ToggleSwitch/ToggleSwitch';
import './App.css';


var websocket;
var config;

class App extends Component {

  constructor(props) {
    super(props);
    this.state = { sitemap: [], module_values: {} };

    //bind this
    this.handleModuleValueChange = this.handleModuleValueChange.bind(this);

    //load condig
    this.config_load_prom = fetch("config.json")
      .then(res => res.json())
      .then(resjson => { //sitemap loaded
        config = resjson;
        console.debug("config.json loaded");
        if (config.host === null) {
          config.host = window.location.hostname; //default to current hostname - for config simplicity
        }
      }
      );

    //defined map of PiOn modules to local component names
    this.modules = {
      "switch": ToggleSwitch
    }

    //load sitemap
    this.sitemap_loaded = false;
    fetch("sitemap.json")
      .then(res => res.json())
      .then(resjson => { //sitemap loaded

        this.setState({ sitemap: resjson }, () => {
          this.sitemap_loaded = true;
          console.debug("sitemap.json loaded");
          //init state for each item
          for (const room in this.state.sitemap) {
            this.state.sitemap[room].forEach((item) => {
              this.setState(prev_state => {
                prev_state.module_values[item.item_name] = Value.getUninitialised();
                return prev_state;
              });
            });

            //setup websocket
            this.config_load_prom.then(() => {
              this.ws_connect();
            });
          } //end setState callback
        }); //end setState

        

      }
      );

    //defined map of PiOn modules to local component names
    this.modules = {
      "switch": ToggleSwitch
    }
  } //end constructor

  handleModuleValueChange(item_name, value) {
    this.sendValueUpdate(item_name, value);

    this.setState((prev_state) => {     
      let new_state = Object.assign({}, prev_state);
      new_state.module_values[item_name] = value;
      return new_state;
    });
  } //end handleModuleValueChange

  sendValueUpdate(item_name, value){
    let item_message = new ItemMessage(item_name, ItemMessage.SET, value);
    let rest_message = RestMessage.withSendDefaults(RestMessage.REQ, RestMessage.REST_CONTEXT_ITEM, item_message);
    console.log(rest_message);
  } //end sendValueUpdate

  ws_connect() {
    let ws_prom = new Promise((resolve, reject) => {
      let ws_url = "ws://" + config.host + ":" + config.port + config.websocket_path;

      if (typeof (websocket) !== 'undefined') return // avoid reconnecting on React Render cycle

      websocket = new WebSocket(ws_url);
      websocket.onopen = () => {
        console.log("Websocket connection established to:", ws_url);
        resolve(websocket);

        //subscribe to items
        let subscribe_items = {};
        for (const room in this.state.sitemap) {
          this.state.sitemap[room].forEach(item => {
            subscribe_items[item.item_name] = [ItemEvent.ITEM_VALUE_CHANGED];

          })
        }

        //build subscribe message with all items and events we're subing to
        let sub_message = new SubscribeMessage(subscribe_items, SubscribeMessage.SUBSCRIBE, SubscribeMessage.REQUEST_VALUES);

        //build the rest message to be sent over the WS
        let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_SUBSCRIBE, 'client', this.host, this.port, sub_message);

        console.log("WS: Sending RestMessage: ", rest_message);
        websocket.send(rest_message.to_json());

      } // end onopen

      // Listen for messages
      websocket.onmessage = function (event) {
        let rest_message = RestMessage.from_json(event.data);
        console.log('Message from WebSocket ', rest_message);

        switch (rest_message.context) {
          case RestMessage.REST_CONTEXT_ITEM:
            rest_message.payload = [rest_message.payload];
            // eslint-disable-next-line no-fallthrough
          case RestMessage.REST_CONTEXT_ITEMS:
            rest_message.payload.forEach(function (item) {
              let item_message = ItemMessage.from_obj(item);
              console.debug("WS: Received value for item: ", item_message.item_name, " with data: ", item_message.value.data);

              this.setState((prev_state) => {
                prev_state.module_values[item_message.item_name] = item_message.value;
                return prev_state
              });
              
            }, this);
            break;
          case RestMessage.REST_CONTEXT_ERROR:
            console.log("WS: Received ERROR message: " + rest_message.payload);
            break;
          default:
            console.log("WS: Received unknown rest message context: " + rest_message.context);

          //alert("ERROR: " + rest_message.payload);
        }
      }.bind(this) //end onmessage
    });
  }

  render() {
    if (this.sitemap_loaded) {
      //console.log(this.state.sitemap);
      let rooms = [];
      for (const room in this.state.sitemap) {
        // console.log(room, this.state.sitemap[room]);
        let items = []
        this.state.sitemap[room].forEach((item) => {

          //
          if (item.type !== "switch") { return }; //temp dev hack
          //console.log("MODULES", item, this.modules[item.type]);
          const CustomTag = this.modules[item.type];
          items.push(
            <li className="item" key={item.item_name}>
              <span className="itemname">{item.item_name}</span>
              <span className="itemvalue">
                <CustomTag item_name={item.item_name} value={this.state.module_values[item.item_name]} onValueChange={this.handleModuleValueChange}></CustomTag>
              </span>
            </li>
          );
        });
        rooms.push(
          <li className="roomrow" key={room}>
            <h3 className="roomname">{room}</h3
            ><ul>{items}</ul>
          </li>)

      }
      return <div><ul>{rooms}</ul></div>;

    } else { //sitemap not loaded yet
      return <span>Loading sitemap...</span>
    }
  }
}

export default App;
/*

*/