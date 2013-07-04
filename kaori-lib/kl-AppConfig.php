<?php

/**
 * 		General configurations for applications.
 *
 * 		Copyright (C) 2011 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 23 Nov 2011.
 */

// Update 23 Nov 2011
// Adding prefix kl-ZZZ

/////////////////////////////////////////////////////////////////
// GLOBAL VARs
ini_set("error_reporting", "E_ALL");
ini_set("allow_url_fopen", "ON");
ini_set("allow_url_include", "OFF");
ini_set("memory_limit", "-1"); // UNLIMIT
ini_set("max_execution_time", "0"); //set_time_limit (0) ; // UNLIMIT

$gnAppErrCode = -100;  // recognize errors returned by the application
$gnNumSamplesPerDot = 100; // used in indicators of loading and saving data
$gszDelim = "#$#"; // delim string in csv files.

$garEnvConfig = array();

//print_r($_SERVER);
//print get_include_path();

if(version_compare(PHP_VERSION, '5.0.0', '>'))
{
	date_default_timezone_set('Asia/Tokyo'); // only for PHP 5.0
}

///////////////
$garEnvConfig["OS_TYPE"] = "Linux";
if(isset($_SERVER["OS"]))
{
	// $_SERVER["OS"] is only available on Windows
	if(strstr($_SERVER["OS"], "Windows"))  // normally it is Windows_NT
	{
		$garEnvConfig["OS_TYPE"] = "Windows";
	}
}

if(isset($_SERVER["WINDIR"]))
{
	// $_SERVER["WINDIR"] is only available on Windows
	$garEnvConfig["OS_TYPE"] = "Windows";
}

$gszOSType = $garEnvConfig["OS_TYPE"];

///////////////
if(isset($_SERVER["SERVER_NAME"]))  // sometimes, this variable is not available if using cli
{
	$garEnvConfig["SERVER_NAME"] = $_SERVER["SERVER_NAME"];
}
else
{
	$garEnvConfig["SERVER_NAME"] = "Server-Name-N/A";
}

if(isset($_SERVER["SERVER_ADDR"]))
{
	$garEnvConfig["SERVER_ADDR"] = $_SERVER["SERVER_ADDR"];
}
else
{
	$garEnvConfig["SERVER_ADDR"]  = "Server-Addr-N/A";
}

if (isset($_ENV["HOSTNAME"]))
{
    $garEnvConfig["MACHINE_NAME"] = $_ENV["HOSTNAME"];
}
else 
{
	if  (isset($_ENV["COMPUTERNAME"]))
	{
    	$garEnvConfig["MACHINE_NAME"] = $_ENV["COMPUTERNAME"];
	}
	else 
	{
		$garEnvConfig["MACHINE_NAME"] = "Machine-Name-N/A";
	}
}

///////////////
/**
 *	Get OS type: Linux or Windows.
 */
function getOSType()
{
	global $garEnvConfig;

	return $garEnvConfig["OS_TYPE"];
}

/**
 * 	Get server name.
 */
function getServerName()
{
	global $garEnvConfig;

	return $garEnvConfig["SERVER_NAME"];
}

/**
 * Get server address
 */
function getServerAddr()
{
	global $garEnvConfig;

	return $garEnvConfig["SERVER_ADDR"];

}

/**
 * Get machine name
 */
function getMachineName()
{
	global $garEnvConfig;

	return $garEnvConfig["MACHINE_NAME"];
}

function getDateTime()
{
	// 03.01.2009, 15:20:30
	return date("d-m-Y, H:i:s");
}

/*
 phpinfo();
 print_r($_SERVER);
*/
?>

