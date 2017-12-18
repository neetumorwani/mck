<?php

function jsonSuccess($data, $meta = null) {
	$response = [
	  'status' => 'success',
	  'data' => $data
	];

	if ($meta) {
		$response['meta'] = $meta;
	}

	return response()->json($response);
}

function jsonError($data, $error_code) {
	$response = [
	  'status' => 'error',
	  'data' => $data
	];

	return response()->json($response, $error_code);
}

/**
  * Function to get variable value stored via drupal config
  * @param String $variable variable name
  * @return String
  */
function getVariable($variable)
{
	$data = '';

  $get_variable = DB::table('variable as v')
	        ->where('v.name', $variable)
	        ->get(['v.value as value']);
          
	if($get_variable) {
		$data = array_shift($get_variable);
		$data = fgets($data->value);
		$data = unserialize($data);
	}
	return $data;
}
