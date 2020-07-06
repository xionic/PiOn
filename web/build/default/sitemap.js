/*
* Group => [{Itemdef}, ...]
*/
var sitemap = {
  "Nick Room": [{
    "item_name": "date test",
    "type": "text"
  }, {
    "item_name": "GPIO test",
    "type": "switch"
  }, {
    "item_name": "Text test",
    "type": "text"
  }, {
    "item_name": "Nick Room Temp",
    "type": "temperature",
    "auth": true
  }, {
    "item_name": "Nick Thermo",
    "type": "thermostat",
    "type_args": {
      "heater_switch": {
        "type": "simple_toggle"
      }
    },
    "auth": true
  }, {
    "item_name": "Nick Location",
    "type": "text"
  }, {
    "item_name": "Nick Heater",
    "type": "switch",
    "auth": true
  }, {
    "item_name": "Nick Bed Lights",
    "type": "switch"
  }, {
    "item_name": "Nick Fan",
    "type": "switch"
  }, {
    "item_name": "tplink plug 3",
    "type": "switch"
  }, {
    "item_name": "tplink plug 4",
    "type": "switch"
  }],
  "Living Room": [{
    "item_name": "Squares Lamp",
    "type": "switch"
  }, {
    "item_name": "TV Light",
    "type": "switch"
  }, {
    "item_name": "Table Lamp",
    "type": "switch"
  }, {
    "item_name": "Xico Power",
    "type": "switch"
  }, {
    "item_name": "Living Room Fan",
    "type": "switch"
  }]
};