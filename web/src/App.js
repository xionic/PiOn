
import React, { Component } from 'react';
import Value from './Value.js';
import RestMessage from './RestMessage.js';
import ItemMessage from './ItemMessage.js';
import SubscribeMessage from './SubscribeMessage.js';
import ItemEvent from './ItemEvent.js';

import WebSocketStatus from './WebSocketStatus.js';
import PiOnModule from './modules/PiOnModule.js';

import ModuleToggleSwitch from './modules/ModuleToggleSwitch/ModuleToggleSwitch.js';
import ModuleText from './modules/ModuleText/ModuleText.js';
import ModuleTemperature from './modules/ModuleTemperature/ModuleTemperature.js';
import ModuleHumidity from './modules/ModuleHumidity/ModuleHumidity.js';
import ModuleThermostat from './modules/ModuleThermostat/ModuleThermostat.js';
import ModuleSetpoint from './modules/ModuleSetpoint/ModuleSetpoint.js';

import './App.css';

window.config = null;

class App extends Component {

  constructor(props) {
    super(props);
    this.state = {
      moduleValues: {},
      webSocket: null,
      errorMsg: {},
      sitemap: {},
      configLoadProm: null,
      sitemapLoadProm: null
    };

    //bind this to methods
    this.handleModuleValueChange = this.handleModuleValueChange.bind(this);
    this.sendValueUpdate = this.sendValueUpdate.bind(this);
    this.connectWebsocket = this.connectWebsocket.bind(this);

    //defined map of PiOn modules to local component names
    this.modules = {
      "switch": ModuleToggleSwitch,
      "text": ModuleText,
      "temperature": ModuleTemperature,
      "humidity": ModuleHumidity,
      "thermostat": ModuleThermostat,
      "setpoint": ModuleSetpoint
    }

    this.modules_inited = false;
    this.mountedProm = new Promise((resolve) => {
      this.mountedPromResolver = resolve;
    });
    this.errorMsgCounter = 0;

    //fetch config
    this.configLoadProm = new Promise((resolve) => {
      fetch("config.json")
        .then(res => res.json())
        .then(res_json => { //config loaded
          window.config = res_json;
          console.debug("config.json loaded", window.config);
          if (window.config.host === null) {
            window.config.host = window.location.hostname; //default to current hostname - for config simplicity
          }
          window.config.websocket_url = "ws://" + window.config.host + ":" + window.config.port + window.config.websocket_path;
          window.config.api_url = "http://" + window.config.host + ":" + window.config.port + "/api/";
          resolve();
        });
    });

    this.sitemapLoadProm = new Promise((resolve) => {
      fetch("sitemap.json")
        .then(res => res.json())
        .then(res_json => { //sitemap loaded       
          const res_sitemap = res_json;
          console.debug("sitemap.json loaded", res_sitemap);
          //init module values
          let init_mod_vals = {};
          for (const room in res_sitemap) {
            res_sitemap[room].forEach((item) => {
              init_mod_vals[item.item_name] = Value.getUninitialised();
            });
          }
          this.mountedProm.then(function () {
            this.setState({ moduleValues: init_mod_vals, sitemap: res_sitemap }, () => {
              this.modules_inited = true;
              resolve();
            });
          }.bind(this));
        });
    });


  }

  componentDidMount() {
    this.mountedPromResolver();
    this.configLoadProm.then(() => {
      this.sitemapLoadProm.then(() => {
        this.connectWebsocket();
      });
    });
  }

  handleModuleValueChange(item_name, value) {
    if (value.constructor.name !== "Value") {
      throw Error("value passed is not an instance of Value");
    }
    this.sendValueUpdate(item_name, value);

    /*setmoduleValues((prev_state) => {
      prev_state.[item_name] = value;
      return prev_state;
    });*/
  }; //end handleModuleValueChange

  sendValueUpdate(item_name, value) {
    let item_message = new ItemMessage(item_name, ItemMessage.SET, value);
    let rest_message = RestMessage.withSendDefaults(RestMessage.REQ, RestMessage.REST_CONTEXT_ITEM, item_message);
    // console.log(rest_message);
    console.debug(`Sending update to server for item '${item_name}' and item_message:`, item_message);
    let full_url = window.config.api_url + "?data=" + encodeURIComponent(rest_message.to_json());
    console.debug(`Full URL: ${full_url}`);
    fetch(full_url)
      .then((retval) => {
        console.debug("SET request result:", retval);
      });

  } //end sendValueUpdate

  reconnectWebsocket() {
    //reset item values to uninitialised
    this.setState(function(prev){
      let newState = {...prev};
      for (const item in newState.moduleValues){
        newState.moduleValues[item] = Value.getUninitialised();
      }
    }, function(){
      this.connectWebsocket();
    }.bind(this));

  }

  removeErrorMsg(id){
    this.setState( (prev) => {
      let newState = {...prev};
      delete  newState.errorMsg[id];
      return newState;
    });
  }

  //connect websocket
  connectWebsocket() {
    //after config and sitemap are loaded, setup websocket
    new Promise(function (resolve) {
      const res_websocket = new WebSocket(window.config.websocket_url);
      res_websocket.onopen = function () {
        console.log("Websocket connection established to:", window.config.websocket_url);
        this.setState({ webSocket: res_websocket }, () => {
          resolve(res_websocket);
        })

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
        let rest_message = new RestMessage(RestMessage.REQ, RestMessage.REST_CONTEXT_SUBSCRIBE, 'client', window.config.host, window.config.port, sub_message);

        console.log("WS: Sending RestMessage: ", rest_message);
        res_websocket.send(rest_message.to_json());

      }.bind(this) // end onopen

      // Listen for messages
      res_websocket.onmessage = function (event) {
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
              this.setState((prev) => {
                const new_state = { ...prev };
                new_state.moduleValues[item_message.item_name] = item_message.value;
                return new_state;
              });
            }.bind(this));

            break;

          case RestMessage.REST_CONTEXT_ERROR:
            console.log("WS: Received ERROR message: " + rest_message.payload);
            const errMsgId = this.errorMsgCounter++;
            this.setState(function(prev) {
              let new_state = { ...prev };
              new_state.errorMsg[errMsgId] = rest_message.payload;
              setTimeout(function(){
                this.removeErrorMsg(errMsgId);
              }.bind(this), 5000);
              return new_state;
            }.bind(this));
            break;

          default:
            console.log("WS: Received unknown rest message context: " + rest_message.context);

          //alert("ERROR: " + rest_message.payload);
        }
      }.bind(this) //end onmessage

      res_websocket.onerror = function () {
        this.reconnectWebsocket();
      }.bind(this);
      res_websocket.onclose = function () {
        this.reconnectWebsocket();
      }.bind(this);

      this.setState({ websocket: res_websocket });
    }.bind(this));
  }

  render() {
    //render
    if (window.config !== null && this.state.sitemap !== null && this.modules_inited !== false) {
      let rooms = [];
      for (const room in this.state.sitemap) {
        let items = [];
        this.state.sitemap[room].forEach((item) => {

          let params = new URLSearchParams(document.location.search.substring(1));
          if (item.auth && params.get("auth") == null) return; //dev hack

          const CustomTag = this.modules[item.type];
          const cur_value = this.state.moduleValues[item.item_name];
          
          items.push(
            <li className="item" key={item.item_name}>
              <PiOnModule 
                type={CustomTag}
                item_name={item.item_name}
                value={cur_value}
                onValueChange={this.handleModuleValueChange}
                all_values={this.state.moduleValues}
                ></PiOnModule>
            </li>
          );
        });
        rooms.push(
          <li className="roomrow" key={room}>
            <h3 className="roomname">{room}</h3
            ><ul>{items}</ul>
          </li>)

      }

      let errorElem = [];
      if (this.state.errorMsg !== "") {
        for (const msgId in this.state.errorMsg) {
          errorElem.push(<div key={msgId} class="rest_errorMsg">{this.state.errorMsg[msgId]}</div>);
        }
      }

      return <div id="outercont">
        {errorElem}
        <div>
          <WebSocketStatus websocket={this.state.webSocket}></WebSocketStatus>
        </div>
        <div id="tablecont">
          <ul>{rooms}</ul>
        </div>
      </div>;


    } else { //sitemap not loaded yet
      return <span>Loading config and sitemap...</span>;
    }
  }
}
export default App;
