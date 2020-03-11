<?php
namespace xionic\Argh;

require_once("ValidationException.class.php");
require_once("InvalidPropertyException.class.php");
/**
* Class to validate arguments to functions
*/
class Argh{
	
	private $argArray, $argDesc, $errCallback;
	
	private $version = "Argh Version 0.2";
	
	private function __construct(){
	}
	
	/**
	 * function to validate GET args for the rest API and return an array of results. it supports validation constraints for each argument.
	 * Supported Constraints:
	 * int			-	must be an integer (implies 'numeric')
	 * numeric		-	must be numeric
	 * notzero		-	must not be zero (implies 'numeric')
	 * notblank		-	string must not be blank (implies 'string')
	 * string		-	must be string
	 * bool|boolean			-	must be a boolean accouring to is_bool
	 * array			- 	must be array
	 * func			- 	provided Closure must return true. 
	 * lbound arg	-	must not be below arg (e.g. "lbound 2")
	 * ubound arg	- 	must not be above arg (e.g. "ubound 600")
	 * regex arg		- 	must match regex given be arg
	 * obj		- 	Must be an object (accordign to is_object())
	 * class name		- 	must be instanceof given class
	 * ?class name		- 	must be instanceof given class or null
	*/

	public static function validate($argArray, array $argDesc, Callable $callback = null, $debug = false): bool {		
		return (new Argh())->_validate($argArray, $argDesc, $callback, $debug);
	}
		
	private function _validate($argArray, $argDesc, $callback, $debug): bool{
		
		if($argArray == null)
			throw new \Exception ("object|array to be checked cannot be null");
		if($argArray == null)
			throw new \Exception ("c to be checked cannot be null");
		
		$this->argArray = $argArray;
		$this->argDesc = $argDesc;
		$this->callback = $callback;
		$this->debug = $debug;

		//expand wildcards :S
		$this->expandDescWildcards();

		//loop through each argument to be validated
		foreach($this->argDesc as $arg => $constraintArr)
		{	
			if(!is_array($constraintArr)) // ensure constraints are provided as an array
			{
				throw new \Exception("Constraints must be an array");
				return false;
			}
			//preprocess constraints to trim and apply optional constraint
			$constraints = array();
			foreach($constraintArr as $tc)
			{	
				$newtc = null;
				if($tc instanceof \Closure ) // don't try to trim closures
				{
					$newtc = $tc;
				}
				else
				{
					$temptc = explode(" ", $tc, 2); //split out constraint and arguments to constraint if applicable e.g. "lbound 1"
					
					$newtc["constraint"] = trim($temptc[0]);	
					if(count($temptc) > 1) // if constaint has an argument
					{
						$newtc["constraintArg"] = $temptc[1];	
					}
					
					if($newtc["constraint"] == "optional" && !$this->checkArgExists($arg)) // check for optional arg
					{
						continue 2; // ignore constraints if optional arg is present
					}
				}
				$constraints[] = $newtc;

			}		
			
			//get the current arg value - multi-level support
			try {
				$curValue = $this->getArg($arg);
			} catch(InvalidPropertyException $ipq){
				$this->handleValidationFail("Missing argument: ". $arg, $arg, null);
			}
			
			foreach($constraints as $c)
			{	//echo substr($c["constraint"], 0,1) . " " . $c["constraint"] ."\n";
				//apply contraints which cannot be done using a switch
				if($c instanceof \Closure)
				{
					$this->checkUserFunc($c,$curValue,$arg);
				} else {
					if (substr($c["constraint"], 0, 1) == "?") {
						if($curValue == null)
							return true;
						else
							$c["constraint"] = substr($c["constraint"], 1);
					}					
					
					//apply the constraints
					switch($c["constraint"])
					{	
						case "string": 
							$this->checkIsString($curValue, $arg);
							break;
							
						case "numeric":
							$this->checkIsNumeric($curValue, $arg);
							break;	
							
						case "int" :
							$this->checkIsInt($curValue, $arg);
							break;
							
						case "notzero" :
							$this->checkNotZero($curValue, $arg);
							break;
							
						case "notblank" :
							$this->checkNotBlank($curValue, $arg);
							break;

						case "bool":
						case "boolean" :
							$this->checkBoolean($curValue, $arg);
							break;
							
						case "array":
							$this->checkIsArray($curValue, $arg);
							break;
							
						case "lbound":
							$this->checkLbound($c["constraintArg"],$curValue,$arg);
							break;
						
						case "ubound":
							$this->checkUbound($c["constraintArg"],$curValue,$arg);
							break;
						
						case "regex":
							$this->checkRegex($c["constraintArg"],$curValue,$arg);
							break;
							
						case "obj":
							$this->checkObject($curValue,$arg);
							break;	
							
						case "class":
							$this->checkClass($c["constraintArg"],$curValue,$arg);
							break;						
							
						case "optional"; // handled above - needed here to prevent exception
							break;
							
						default:
							throw new \Exception("Constraint ". htmlentities($c["constraint"]) . " is unsupported");
							break;
						
					}
					$cArg = isset($c["constraintArg"]) ? $c["constraintArg"] : "";
					$this->debug("Constraint: " . htmlentities($c["constraint"]) . " with args: '" .$cArg. "' passed for key: " . $arg . " Value: ". var_export($curValue, true));
				}
			}
		}
		return true;
	}	
	
	private function checkIsInt($value, $arg)
	{
		if(!$this->checkIsNumeric($value, $arg) || !is_int($value+0))
		{
			$this->handleValidationFail("Argument is not an integer: ". $arg, $arg, $value);
			return false;
		}
		return true;
	}
	private function checkIsNumeric($value, $arg)
	{
		if(!is_numeric($value))
		{
			$this->handleValidationFail("Argument is not numeric: ". $arg, $arg, $value);
			return false;
		}
		return true;
	}
	
	private function checkNotZero($value, $arg)
	{	
		if(!$this->checkIsNumeric($value, $arg) || $value == 0)
		{
			$this->handleValidationFail("Argument is zero: ". $arg, $arg, $value);
			return false;
		}
		return true;
	
	}
	
	private function checkNotBlank($value, $arg)
	{
		if(!$this->checkIsString($value, $arg) || $value == "")
		{
			$this->handleValidationFail("Argument is a blank string: ". $arg, $arg, $value);
			return false;
		}
		return true;
	}

	private function checkBoolean($value, $arg)
	{
		if(!is_bool($value) && $value != 0 && $value != 1)
		{
			$this->handleValidationFail("Argument is not a boolean ". $arg, $arg, $value);
			return false;
		}
		return true;
	}
	
	private function checkIsString($value, $arg)
	{
		if(!is_string($value))
		{
			$this->handleValidationFail("Argument is not a string: ". $arg, $arg, $value);
			return false;
		}
		return true;
	}
	
	private function checkIsArray($value, $arg)
	{
		if(!is_array($value))
		{
			$this->handleValidationFail("Argument is not an array: ". $arg, $arg, $value);
			return false;
		}
		return true;
	}
	
	private function checkUserFunc($func, $value, $arg)
	{
		if(call_user_func($func,$value) !== true)
		{
			$this->handleValidationFail("Argument failed user function validation: ". $arg, $arg, $value);
			return false;
		}
		return true;
	}
	
	private function checkLbound($lbound, $value, $arg)
	{
		$lbound = (float) $lbound;
		if(!is_numeric($lbound))
		{
			$this->handleValidationFail("Argument to lbound must be numeric: ". $arg, $arg, $value);
			return false;
		} else {
			if(!$this->checkIsNumeric($value, $arg) || $value < $lbound)
			{
				$this->handleValidationFail("Argument is below lbound(".$lbound."): ". $arg, $arg, $value);
				return false;
			}
			return true;
		}
	}
	
	private function checkUbound($ubound, $value, $arg)
	{
		$ubound = (float) $ubound;
		if(!is_numeric($ubound))
		{
			$this->handleValidationFail("Argument to ubound must be numeric: ". $arg, $arg, $value);
			return false;
		} else {
			if(!$this->checkIsNumeric($value, $arg) || $value > $ubound)
			{
				$this->handleValidationFail("Argument is below ubound(".$ubound."): ". $arg, $arg, $value);
				return false;
			}
			return true;
		}
	}
	
	private function checkRegex($regex, $value, $arg)
	{		
		if((preg_match($regex,$value)) !== 1)
		{
			$this->handleValidationFail("Argument is does not match regex(".$regex."): ". $arg, $arg, $value);
			return false;
		}
		return true;
	}
	
	private function checkObject($value, String $arg)
	{
		if (!is_object($value)){
			$this->handleValidationFail("Argument is not an object", $arg, $value);
			return false;
		}
		return true;		
	}
	
	private function checkClass(String $classSpec, $value, String $arg)
	{
		if (!$value instanceof $classSpec){
			$this->handleValidationFail("Argument is not of class type: '$classSpec'", $arg, $value);
			return false;
		}
		return true;		
	}

	//check an arg at the specifiec path in the argArray exists - to support subarrays etc e.g. /var1/subvar1 /var1/subvar2
	private function checkArgExists($path){
		try {
			$this->getArg($path);
		} catch(InvalidPropertyException $ipe){
			return false;
		}
		return true;
	}
	
	private function handleValidationFail(String $reason, String $offendingArg, $offendingValue){
		if($this->callback != null){
			call_user_func($this->callback,$reason, $offendingArg, $offendingValue);
		} else {
			throw new ValidationException($reason, $offendingArg, $offendingValue);
		}
	}

	// function to do the work of both resolving an array "path" to a value, or checking if it's set
	private function getArg($path){	
		//shortcut
		if(substr($path,0,1) != "/"){ // whether we have a path starting with / if not it's a normal single level argument			
			return $this->retrievePathValue($path, $this->argArray);
		}	

		$pathComponents = explode("/",$path);
		//discard the first empty element
		$pathComponents = array_slice($pathComponents,1);
		//and the last if it's empty i.e. a traiing /
		if($pathComponents[count($pathComponents)-1] == ""){
			$pathComponents = array_slice($pathComponents,0,count($pathComponents)-1);
		}
		$returnVal = $this->argArray;
		foreach($pathComponents as $c){			
			$returnVal = $this->retrievePathValue($c, $returnVal);
		}
		//echo "GETARG: $path\n";
		//var_dump($returnVal);
		return $returnVal;
	}
	
	private function retrievePathValue(String $elemName, $argList){ // $argList array or object
		//var_dump($elemName, $argList);
		if(is_object($argList)){
			if(!property_exists($argList, $elemName))
				throw new InvalidPropertyException($elemName);
			return $argList->$elemName;
		} else {
			if(!array_key_exists($elemName, $argList))
				throw new InvalidPropertyException($elemName);
			return $argList[$elemName];
		}
	}

	//expand wildcards in the argDesc array e.g. /test/* -> /test/1 /test/2 /test/3 ... /test/[array length]
	private function expandDescWildcards(){
		$keys = array_keys($this->argDesc);
		for($counter = 0; $counter < count($keys); $counter++){ // need a trad for because length changes
			$key = $keys[$counter];
			$keyDesc = $this->argDesc[$key];
			//do we have a wildcard?
			if(strpos($key,"*") === false){
				//no wildcard, nothing to expand
				//echo "COTNINUE\n";
				continue;
			}
			//get the array at this layer
			$stripped_key = preg_replace("/\/$/", "", $key);
			//echo "\nKEY: $key\n";
			$pathComponents = explode("/", $stripped_key); 
			$pathComponents = array_slice($pathComponents,1); //discard the first empty element			
			//var_dump($key, $pathComponents);
			$curVal = $this->argArray;
			$curPath = "";
			//var_dump($this->argArray);
		
			for($i = 0; $i < count($pathComponents); $i++){
				$c = $pathComponents[$i];				
				//echo "PATHCOMP: $c CURPATH: $curPath\n";
				if($c == "*"){		
					/*$first = $this->getFirstElementName($curVal);						
					$curVal = $this->retrievePathValue($first, $curVal);
					var_dump($curVal);*/
					//add a new elem to argDesc for each elem in the array - could be hairy for large arrays :/
					
					if(is_object($curVal)){
						$length = count(get_object_vars($curVal));
					} else if(is_array($curVal)) {
						$length = count($curVal);
					} else {
						$length = 1;
					}
					//echo "LENGTH $length\n";
					$remainingPath = "";
					$remainingPath = implode("/", array_slice($pathComponents, $i+1));
					if($remainingPath != "")
						$remainingPath = "/" . $remainingPath;

					//echo "REMAINING: $remainingPath\n";
					for($elemCounter = 0; $elemCounter < $length; $elemCounter++){
						//append onto the argDesc array with expanded path. Use the same checks e.g. /test/1/restOfarray, /test/2/restOfArray ...
						if(is_object($curVal)){
							//var_dump(array_keys(get_object_vars($curVal))[$elemCounter]);
							$keyName = array_keys(get_object_vars($curVal))[$elemCounter];
							//echo "KEYNAME: $keyName\n";
							$newKey = $curPath . "/" . $keyName . $remainingPath;
						} else {							
							$newKey = $curPath . "/" . $elemCounter . $remainingPath;
						}
						//echo "ADDING NEW KEY: $newKey - '$curPath '/$elemCounter' '$remainingPath' \n";
						$keys[] = $newKey; //push new key on so it too is processed by the super loop
						$this->argDesc[$newKey] = $keyDesc;
					}
					continue 2; // we've expanded this * and added the rest back on the $keys "queue" to be further expanded if required
				} else {
					$curPath .= "/$c";				
					$curVal = $this->retrievePathValue($c, $curVal);
					//echo "ADDING NEW KEY NO WILDCARD: $curPath/$c \n";
					//$keys[] = "$curPath/$c";
				}
				//var_dump($curVal);
				//finally remove the wildcard entry
				unset($this->argDesc[$key]);
			}
			
		}
		//var_dump(implode("\n", array_keys($this->argDesc)));
	}

	/*private function expandDescWildcards(){
		$keys = array_keys($this->argDesc);

	}*/

	//gets the first element name from an array or object;
	private function getFirstElementName($val): String{
		if(is_object($val)){
			return get_object_vars($val)[0];
		} else {
			return 0;
		}
	}
	
	public function getVersion()
	{
		return $this->version;
	}

	private function debug($text){
		if($this->debug){
			echo "$text\n";
		}
	}
}

?>
