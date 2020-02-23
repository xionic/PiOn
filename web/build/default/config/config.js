var config = {
  host: "xealot",
  port: "28081"
};
config.backend_url = config.host + ":" + config.port + "/";
config.websocket_url = config.backend_url + "websocket/";
config.api_url = "http://" + config.backend_url + "api/";