<?php
/**
 * Created by PhpStorm.
 * User: master
 * Date: Dec 29, 2010
 * Time: 2:54:47 PM
 */
 
if (!function_exists('array_replace_recursive')){


	function _array_replace_recursive_recurse($array, $array1)    {
	  foreach ($array1 as $key => $value)      {
	    // create new key in $array, if it is empty or not an array
	    if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))        {
	      $array[$key] = array();
	    }

	    // overwrite the value in the base array
	    if (is_array($value))        {
	      $value = _array_replace_recursive_recurse($array[$key], $value);
	    }
	    $array[$key] = $value;
	  }
	  return $array;
	}

  function array_replace_recursive($array, $array1)  {

    // handle the arguments, merge one by one
    $args = func_get_args();
    $array = $args[0];
    if (!is_array($array))    {
      return $array;
    }
    for ($i = 1; $i < count($args); $i++)    {
      if (is_array($args[$i]))      {
        $array = _array_replace_recursive_recurse($array, $args[$i]);
      }
    }
    return $array;
  }
}


if (!function_exists('array_replace')){

	function array_replace(array &$arr1, array &$arr2){
		$arrays = func_get_args();
		$result = array();
		$i = 0;
		foreach ($arrays as $array){
			if (is_array($array)){
				foreach ($array as $key=>$value){
					$result[$key] = $value;
				}
			} else {
        trigger_error( __FUNCTION__ . '(): Argument #' . ($i+1) . ' is not an array', E_USER_WARNING );
        return NULL;
			}
			$i++;
		}
		return $result;
	}

}