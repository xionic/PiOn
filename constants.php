<?php

//logging
define("DEBUG",3);
define("VERBOSE",2);
define("INFO",1);
define("ERROR",0);
define("FATAL",-1);

//TESTZ
if(gethostname() == "xealot")
	define("NODE_NAME", "xealot_server");
else
	define("NODE_NAME", "pi1");
?>