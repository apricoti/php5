<?php

// SAFE MYSQL API FOR PHP
// COPYRIGHT (C) 2009 Buhacoff Information Assurance

$hostname='my.server.com';
$username='username';
$password='password';
$dbname='mydb';


function dbopen() {
	global $hostname,$username,$password,$dbname;
	$link = mysql_connect($hostname, $username, $password);
	if(!$link) { error_log("cannot connect to database: ". mysql_error()); }
	$db_selected = mysql_select_db($dbname, $link);
	if(!$db_selected) { error_log("cannot use ".$dbname.": ". mysql_error()); }
	return $link;
}



$sql_fields = array();
$sql_types = array();

function db_select($sql) {
	global $sql_fields, $sql_types;
	$sql_fields = array();
	$sql_types = array();
	$db_connection = dbopen();
	$result = mysql_query($sql, $db_connection);
	$data = array();
	while( $row = mysql_fetch_assoc($result) ) {
		$data[] = $row;
	}
	mysql_free_result($result);
	return $data;
}

function fill_params($sql,$data) {
	global $sql_fields, $sql_types;
	$sql_fields = array();
	$sql_types = array();
	// turn query with named parameters into query with mysql placeholders and populate the sql_fields and sql_types arrays for the php bind_param statement
	$sql_with_question_marks = preg_replace_callback('/(:\w+)\[([sidb])\]/','process_named_parameter',$sql);
	$values = array();
	foreach( $sql_fields as $f ) {
		$values[] = $data[$f];
	}
	$types = join("",$sql_types);
	$bind_params = array_merge( array($types), $values );
	
	// now since we don't have prepared statements on this server (mysqli not installed), we need to replace the question marks with the values directly
	error_log("sql statement with question marks: " . $sql_with_question_marks );
	
	$question_marks = array();
	$quoted_values = array();
	foreach( $sql_fields as $f ) {
		$question_marks[] = "?";
		$quoted_values[] = "\"" . addslashes($data[$f]) . "\"";
	}	
	
	$final_sql = str_replace( $question_marks, $quoted_values, $sql_with_question_marks );
	error_log("sql statement after replacing qmarks: " . $final_sql );
	return $final_sql;
}

// example usage:
// $pet = array();
// $pet["pet_name"] = "sparky";    //  :pet_name[s]   s means string
// $pet["pet_age"] = 2;            //  :pet_name[i]   i means integer
// $pet["pet_weight"] = 15.2;      //  :pet_name[d]   d means double or float
// db_select_param("select name,age,weight from pets where name=:pet_name[s] and age=:pet_age[i] and weight=:pet_weight[d]", $pet);
function db_select_param($sql, $data) {
	$db_connection = dbopen();
	$final_sql = fill_params($sql,$data);
	$result = mysql_query( $final_sql, $db_connection );

	$data = array();
	while( $row = mysql_fetch_assoc($result) ) {
		$data[] = $row;
	}
	mysql_free_result($result);
	return $data;
			
}

function db_insert($sql, $data) {
	$db_connection = dbopen();
	$final_sql = fill_params($sql,$data);
	$result = mysql_query( $final_sql, $db_connection );
	if( !$result ) {
		error_log("mysql statement failed: " . $final_sql . " -- " . mysql_error());
	}	
	$newid = mysql_insert_id($db_connection);
	mysql_free_result($result);
	return $newid;
}

function db_update($sql, $data) {
	$db_connection = dbopen();
	$final_sql = fill_params($sql,$data);
	$result = mysql_query( $final_sql, $db_connection );
	if( !$result ) {
		error_log("mysql statement failed: " . $final_sql . " -- " . mysql_error());
	}	
	mysql_free_result($result);
	return;
}

function db_delete($sql, $data) {
	$db_connection = dbopen();
	$final_sql = fill_params($sql,$data);
	$result = mysql_query( $final_sql, $db_connection );
	if( !$result ) {
		error_log("mysql statement failed: " . $final_sql . " -- " . mysql_error());
	}	
	mysql_free_result($result);
	return;
}


function process_named_parameter($matches) {
	global $sql_fields, $sql_types;
	$sql_fields[] = substr($matches[1],1);
	$sql_types[] = $matches[2];
	return "?";
}

?>
