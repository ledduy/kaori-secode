<?php

/**
 * 		Misc Tools.
 *
 * 		Copyright (C) 2011 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 23 Nov 2011.
 */

require_once "kl-AppConfig.php";

/**
 *	Print progress of a process using dot (.).
 */
function printProgress($nCurrent, $nMax=-1, $nInterval=-1)
{
	// $nCurrent < 0 --> start progress
	// $nMax < 0 --> unknown max
	// $nMax = 0 --> end progress

	global $gnNumSamplesPerDot;

	if($nInterval == -1)
	{
		$nInterval = $gnNumSamplesPerDot;
	}

	if($nCurrent < 0)
	{
		printf("[");
		return;
	}

	if($nMax == 0)
	{
		printf("]. ");
		return;
	}

	if(($nMax > 0) && ($nCurrent >= $nMax-1))
	{
		printf("]. ");
		return;
	}

	if(($nCurrent+1) % $nInterval == 0)
	{
		printf(".");
		return;
	}

}

/**
 *	Print '['.
 */
function printProgressStart()
{
	printProgress(-1, 0);
}

/**
 *	Print ']'.
 */
function printProgressFinish()
{
	printProgress(1, 0);
}

/**
 * 	Only for PHP 4
 */
function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

/**
 * 	For tracking traffic
 */
function runCounter($szFPOutputFN)
{
	$arLog = array();
	$arLog[] = sprintf("%s, %s", $_SERVER['REMOTE_ADDR'], date(DATE_RFC822,$_SERVER['REQUEST_TIME']));
	//	$szFPOutputFN = sprintf("%s/tv2007.devel.30-concepts.log", $gszRootTmpDir);
	saveDataFromMem2File($arLog, $szFPOutputFN, "a+t");
}

function getScriptName(&$szFPScriptName)
{
	$szScriptNameTmp = str_replace("\\", "/", $szFPScriptName); // conver to Linux path format
	$arTmp = explode("/", $szScriptNameTmp);
	$szScriptName = $arTmp[sizeof($arTmp)-1]; // pick the last one

	return $szScriptName;
}

/**
 * 	log tags ared rounded by [], e.g. sessionID
 * 	specific tags have indicators such as ###, +++, ***
 *
 * 	$szScriptName = str_replace(".php", ".log.csv", $szScriptName);
 */

$gszRootLogDir = ".";  // directory for log files.
$gszScriptName = "";
$gTimeStart = 0; // starting time
$gTimeEnd = 0; // ending time

function startLogging()
{
	global $gszRootLogDir;
	global $gTimeStart, $gTimeEnd;
	global $gszScriptName;
	global $argc, $argv;

	session_start();

	$szLogDir = $gszRootLogDir;

	$gszScriptName = getScriptName($argv[0]);

	$szScriptName = $gszScriptName;

	$szScriptName = str_replace(".php", ".log.csv", $szScriptName);

	$szFPLogFN = sprintf("%s/%s", $szLogDir, $szScriptName);

	$szSessionID = session_id();

	$arOutput = array();
	$szOutput = sprintf("\n\n###, [%s], Starting time, [%s], Host, [%s]", $szSessionID, getDateTime(), getMachineName());

	printf("%s\n", $szOutput);

	$arOutput[] = $szOutput;

	saveDataFromMem2File($arOutput, $szFPLogFN, "a+t", 0); // no logging

	$gTimeStart = microtime_float();
}

/**
 * 	$szOutput = sprintf("+++, [%s], [%s], %s", $szSessionID, getDateTime(), $szStr);
 * 	$szScriptName = str_replace(".php", ".log.csv", $szScriptName);
 */
function doLogging($szStr)
{
	global $gszScriptName;
	global $gszRootLogDir;
	global $gTimeStart, $gTimeEnd;

	$szLogDir = $gszRootLogDir;

	$szScriptName = $gszScriptName;

	if($szScriptName == "")
	{
		return;
	}

	$szScriptName = str_replace(".php", ".log.csv", $szScriptName);

	$szSessionID = session_id();

	$szFPLogFN = sprintf("%s/%s", $szLogDir, $szScriptName);

	$arOutput = array();

	$szOutput = sprintf("+++, [%s], [%s], %s", $szSessionID, getDateTime(), $szStr);

	printf("%s\n", $szOutput);

	$arOutput[] = $szOutput;

	if(file_exists($szFPLogFN))
	{
		saveDataFromMem2File($arOutput, $szFPLogFN, "a+t", 0); // no logging
	}
}

/**
 * 	$szScriptName = str_replace(".php", ".log.csv", $szScriptName);
 */
function doLoggingArray(&$arDataList)
{
	$arKeys = array_keys($arDataList);
	$nNumKeys = sizeof($arKeys);

	$szLogStr = sprintf("@##@, Param, [%s]", var_name($arDataList));
	for($i=0; $i<$nNumKeys; $i++)
	{
		$szKey = $arKeys[$i];
		$szLogStr = sprintf("@##@, [%s], [%s]", $szKey, $arDataList[$szKey]);

		doLogging($szLogStr);
	}
}

/**
 * 	$szOutput = sprintf("***, [%s], Exec time (secs), [%0.2f]", $szSessionID, $gTimeEnd - $gTimeStart);
 * 	$szScriptName = str_replace(".php", ".log.csv", $szScriptName);
 */
function endLogging()
{
	global $gszScriptName;
	global $gszRootLogDir;
	global $gTimeStart, $gTimeEnd;

	global $argc, $argv;

	$szLogDir = $gszRootLogDir;

	$szScriptName = $gszScriptName;

	if($szScriptName == "")
	{
		return;
	}

	$szScriptName = str_replace(".php", ".log.csv", $szScriptName);

	$szFPLogFN = sprintf("%s/%s", $szLogDir, $szScriptName);

	$gTimeEnd = microtime_float();

	$szSessionID = session_id();

	$arOutput = array();
	$szOutput = sprintf("***, [%s], Exec time (secs), [%0.2f]", $szSessionID, $gTimeEnd - $gTimeStart);
	$arOutput[] = $szOutput;
	printf("%s\n", $szOutput);

	$arOutput[] = sprintf("@@@, [%s], Ending time, [%s]", $szSessionID, getDateTime());

	if(file_exists($szFPLogFN))
	{
		saveDataFromMem2File($arOutput, $szFPLogFN, "a+t", 0); // no logging
	}
}

function var_name(&$var, $scope=false, $prefix='unique', $suffix='value')
{
	if($scope) $vals = $scope;
	else      $vals = $GLOBALS;
	$old = $var;
	$var = $new = $prefix.rand().$suffix;
	$vname = FALSE;
	foreach($vals as $key => $val) {
		if($val === $new) $vname = $key;
	}
	$var = $old;
	return $vname;
}

/**
 * 	Stop program and print error message.
 */
function terminatePrg($szErr="N/A")
{
	global $gnAppErrCode;

	$szErrMsg = sprintf("<!-- Program terminated due to error: [%s] -->\n", $szErr);
	printf($szErrMsg);
	doLogging($szErrMsg);

	exit($gnAppErrCode);
}

function shuffle_assoc(&$array)
{
	$keys = array_keys($array);

	shuffle($keys);

	foreach($keys as $key) {
		$new[$key] = $array[$key];
	}

	$array = $new;

	return true;
}

?>
