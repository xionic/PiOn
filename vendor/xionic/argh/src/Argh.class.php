<?php
namespace xionic\Argh;

require_once("ValidationException.class.php");
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
	 * array			- 	must be array
	 * func			- 	provided Closure must return true. 
	 * lbound arg	-	must not be below arg (e.g. "lbound 2")
	 * ubound arg	- 	must not be above arg (e.g. "ubound 600")
	 * regex arg		- 	must match regex given be arg
	 * obj		- 	Must be an object (accordign to is_object())
	 * class name		- 	must be instanceof given class
	 * ?class name		- 	must be instanceof given class or null
	*/

	public static function validate($argArray, array $argDesc, Callable $callback = null): bool {		
		return (new Argh())->_validate($argArray, $argDesc, $callback);
	}
		
	private function _validate($argArray, $argDesc, $callback): bool{
		
		if($argArray == null)
			throw new \Exception ("object|array to be checked cannot be null");
		if($argArray == null)
			throw new \Exception ("c to be checked cannot be null");
		
		$this->argArray = $argArray;
		$this->argDesc = $argDesc;
		$this->callback = $callback;

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
				if($tc instanceof Closure ) // don't try to trim closures
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
		
			if(!$this->checkArgExists($arg))
			{
				$this->handleValidationFail("Missing argument: ". $arg, $arg, null);
				continue;
			}
			//get the current arg value - multi-level support
			$curValue = $this->getArg($arg);
			
			foreach($constraints as $c)
			{	//echo substr($c["constraint"], 0,1) . " " . $c["constraint"] ."\n";
				//apply contraints which cannot be done using a switch
				if($c instanceof Closure)
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

	//get an arg from the array to validate - to support subarrays etc e.g. /var1/subvar1 /var1/subvar2
	private function getArg($path){
		return $this->_getArg($path);
	}

	//check an arg at the specifiec path in the argArray exists - to support subarrays etc e.g. /var1/subvar1 /var1/subvar2
	private function checkArgExists($path){
		return $this->_getArg($path,true);
	}
	
	private function handleValidationFail(String $reason, String $offendingArg, $offendingValue){
		if($this->callback != null){
			call_user_func($this->callback,$reason, $offendingArg, $offendingValue);
		} else {
			throw new ValidationException($reason, $offendingArg, $offendingValue);
		}
	}

	// function to do the work of both resolving an array "path" to a value, or checking if it's set
	private function _getArg($path, $justCheckIsSet = false){
		//shortcut
		if(substr($path,0,1) != "/"){ // whether we have a path starting with / if not it's a normal single level argument
			if($justCheckIsSet)
				if(is_object($this->argArray)){
					return property_exists($this->argArray, $path);
				} else {
					return array_key_exists($path, $this->argArray);	
				}
			else
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
			if($justCheckIsSet){
				if(is_object($this->argArray)){
						return property_exists($returnVal, $c);
					} else {
						return array_key_exists($c, $returnVal);	
					}
			}
			$returnVal = $this->retrievePathValue($c, $returnVal);
		}		
		return $returnVal;
	}
	
	private function retrievePathValue(String $elemName, $argList){ // $argList array or object
		if(is_object($argList)){
			return $argList->$elemName;
		} else {
			return $argList[$elemName];
		}
	}

	//expand wildcards in the argDesc array e.g. /test/* -> /test/1 /test/2 /test/3 ... /test/[array length]
	private function expandDescWildcards(){
		$keys = array_keys($this->argDesc);
		for($i = 0; $i < count($keys); $i++){
			//do we have a wildcard?
			if(($pos = strpos($keys[$i],"/*/")) !== false){ // note: this is NOT regex - it is literally /*/
				//get the array at this layer
				$pathComponents = explode("/",substr($keys[$i],0,$pos)); // path up to the *
				$pathComponents = array_slice($pathComponents,1); //discard the first empty element
				$curVal = $this->argArray;
				foreach($pathComponents as $c){
					$curVal = $curVal[$c];
				}
				//add a new elem to argDesc for each elem in the array - could be hairy for large arrays :/
				for($elemCounter = 0; $elemCounter < count($curVal); $elemCounter++){
					//append onto the argDesc array with expanded path. Use the same checks e.g. /test/1/restOfarray, /test/2/restOfArray ...
					$newKey = substr($keys[$i],0,$pos) . "/" . $elemCounter . "/" . substr($keys[$i],$pos+3);
					$keys[] = $newKey; //push new key on so it too is processed by the super loop
					$this->argDesc[$newKey] = $this->argDesc[$keys[$i]];
				}
				//finally remove the wildcard entry
				unset($this->argDesc[$keys[$i]]);
			}
		}
	}
	
	public function getVersion()
	{
		return $this->version;
	}
}

?>
