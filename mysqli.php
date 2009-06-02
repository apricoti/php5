<?php
// SAFE MYSQL API FOR PHP
// COPYRIGHT (C) 2009 Buhacoff Information Assurance

$hostname='my.server.com';
$username='username';
$password='password';
$dbname='mydb';

// reference: http://www.php.net/manual/en/ref.mysql.php

function dbopen() {
	global $hostname,$username,$password,$dbname;
	$link = new mysqli($hostname, $username, $password, $dbname);
	return $link;
}



$sql_fields = array();
$sql_types = array();

function db_select($sql) {
	global $sql_fields, $sql_types;
	$sql_fields = array();
	$sql_types = array();
	$db_connection = dbopen();
	$statement = $db_connection->query($sql);
	$data = array();
	while( $row = $statement->fetch_assoc() ) {
		$data[] = $row;
	}
	$statement->free_result();
	return $data;
}

// example usage:
// $pet = array();
// $pet["pet_name"] = "sparky";    //  :pet_name[s]   s means string
// $pet["pet_age"] = 2;            //  :pet_name[i]   i means integer
// $pet["pet_weight"] = 15.2;      //  :pet_name[d]   d means double or float
// db_select_param("select name,age,weight from pets where name=:pet_name[s] and age=:pet_age[i] and weight=:pet_weight[d]", $pet);
function db_select_param($sql, $data) {
	global $sql_fields, $sql_types;
	$sql_fields = array();
	$sql_types = array();
	$db_connection = dbopen();
	// turn query with named parameters into query with mysql placeholders and populate the sql_fields and sql_types arrays for the php bind_param statement
	$sql_with_question_marks = preg_replace_callback('/(:\w+)\[([sidb])\]/','process_named_parameter',$sql);
	$values = array();
	foreach( $sql_fields as $f ) {
		$values[] = $data[$f];
	}
	$types = join("",$sql_types);
	$bind_params = array_merge( array($types), $values );
	$statement = $db_connection->prepare($sql_with_question_marks);
	
	// XXX for debugging only!
	if( $statement == null ) {
		$result = array();
		$result["error"] = $db_connection->error;
		$result["sql"] = $sql_with_question_marks;
		echo json_encode($result);
		exit;
	}
	
	call_user_func_array( array($statement,'bind_param') , $bind_params );
	$statement->execute();
	// for some reason php doesn't implement the "fetch_assoc" function for result sets of prepared statements, so we have to do this in order to get the same effect:
	$result = array();  // each time we call statement->fetch the result will be in this variable
	$bind_results = array();
	$meta = $statement->result_metadata();
	while($field = $meta->fetch_field()) {
		$bind_results[] = &$result[$field->name];
	}
	call_user_func_array( array($statement,'bind_result'), $bind_results );	
	$data = array();
	while( $statement->fetch() ) {
		$row = array();
		foreach( $result as $key => $value ) {
			$row[$key] = $value;
		}
		$data[] = $row;
	}
	$statement->free_result();
	return $data;
}

function db_insert($sql, $data) {
	global $sql_fields, $sql_types;
	$sql_fields = array();
	$sql_types = array();
	$db_connection = dbopen();
	// turn query with named parameters into query with mysql placeholders and populate the sql_fields and sql_types arrays for the php bind_param statement
	$sql_with_question_marks = preg_replace_callback('/(:\w+)\[([sidb])\]/','process_named_parameter',$sql);
	$values = array();
	foreach( $sql_fields as $f ) {
		$values[] = $data[$f];
	}
	$types = join("",$sql_types);
	$bind_params = array_merge( array($types), $values );
	$statement = $db_connection->prepare($sql_with_question_marks);
	call_user_func_array( array($statement,'bind_param') , $bind_params );
	$statement->execute();
	$newid = $db_connection->insert_id;
	$statement->free_result();
	return $newid;
}

function db_update($sql, $data) {
	global $sql_fields, $sql_types;
	$sql_fields = array();
	$sql_types = array();
	$db_connection = dbopen();
	// turn query with named parameters into query with mysql placeholders and populate the sql_fields and sql_types arrays for the php bind_param statement
	$sql_with_question_marks = preg_replace_callback('/(:\w+)\[([sidb])\]/','process_named_parameter',$sql);
	$values = array();
	foreach( $sql_fields as $f ) {
		$values[] = $data[$f];
	}
	$types = join("",$sql_types);
	$bind_params = array_merge( array($types), $values );
	$statement = $db_connection->prepare($sql_with_question_marks);
	call_user_func_array( array($statement,'bind_param') , $bind_params );
	$statement->execute();
	$statement->free_result();
	return;
}

function db_delete($sql, $data) {
	global $sql_fields, $sql_types;
	$sql_fields = array();
	$sql_types = array();
	$db_connection = dbopen();
	// turn query with named parameters into query with mysql placeholders and populate the sql_fields and sql_types arrays for the php bind_param statement
	$sql_with_question_marks = preg_replace_callback('/(:\w+)\[([sidb])\]/','process_named_parameter',$sql);
	$values = array();
	foreach( $sql_fields as $f ) {
		$values[] = $data[$f];
	}
	$types = join("",$sql_types);
	$bind_params = array_merge( array($types), $values );
	$statement = $db_connection->prepare($sql_with_question_marks);
	call_user_func_array( array($statement,'bind_param') , $bind_params );
	$statement->execute();
	$statement->free_result();
	return;
}


function process_named_parameter($matches) {
	global $sql_fields, $sql_types;
	$sql_fields[] = substr($matches[1],1);
	$sql_types[] = $matches[2];
	return "?";
}

?>
