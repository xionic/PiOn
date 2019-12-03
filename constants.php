<?php

//logging
define("DEBUG",3);
define("VERBOSE",2);
define("ERROR",0);

//TESTZ
if(gethostname() == "xealot")
	define("NODE_NAME", "xealot_server");
else
	define("NODE_NAME", "pi1");
?>