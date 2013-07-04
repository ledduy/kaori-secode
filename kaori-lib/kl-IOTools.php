<?php

/**
 * 		Tools for I/O.	
 * 
 * 		Copyright (C) 2011 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 23 Nov 2011.
 */
 
require_once "kl-AppConfig.php";
require_once "kl-MiscTools.php";

/**
 * 	Convert to suitable delimiter for current OS.
 * 	Linux: /
 * 	Windows: \\. Note: / is ok for some cases (not work for creating directory). 
 */
function convert2OSFormat(&$szPath, $szOSType="Linux")
{
	if($szOSType == "Linux")
	{
		$szPath = str_replace("\\", "/", $szPath);
	}
	else
	{
		$szPath = str_replace("/", "\\", $szPath);
	}
}

/**
 * 	Delete a file using rm command.
 */
function deleteFile($szFPInputFN, $nLogging=0)
{
	$szOSType = getOSType();
	convert2OSFormat($szFPInputFN, $szOSType);

	$szCmdLine = "";
	if(file_exists($szFPInputFN))
	{
		$szCmdLine = sprintf("rm -f %s", $szFPInputFN);
		if($nLogging)
		{
			doLogging($szCmdLine);
		}
		system($szCmdLine);
	}
	
	return $szCmdLine;
}

/**
 * 	Create a directory using mkdir command.
 */
function makeDir($szFPDirName, $nLogging=0)
{
	$szOSType = getOSType();
	convert2OSFormat($szFPDirName, $szOSType);
	
	$szCmdLine = "";
	
	if(!file_exists($szFPDirName))
	{
		if($szOSType == "Linux")
		{
			// Linux creates parent dir with -p option
			$szCmdLine = sprintf("mkdir -p %s", $szFPDirName);
		}
		else
		{
			// Windows auto creates parent dir if needed
			$szCmdLine = sprintf("mkdir %s", $szFPDirName);
		}
		
		if($nLogging)
		{
			doLogging($szCmdLine);
		}
		system($szCmdLine);
	}
	
	return $szCmdLine;
}

/**
 *	Input: List file, each row --> one string.
 *	Output: Array of rows.
 *	Note: There is no empty line. In the other word, the empty line will cause stopping reading the remaining lines.
 */
function loadListFile(&$arList, $szFPInputFN, $nLogging=0)
{
	global $gnNumSamplesPerDot;  // from AppConfig.php
	
	if($szFPInputFN == "")
	{
		terminatePrg("File does not exist! File path is EMPTY");				
	}
	
	if(!file_exists($szFPInputFN))
	{
		$szErr = sprintf("File does not exist! Check the file path [%s]!", $szFPInputFN);
		terminatePrg($szErr);		
	}

	$hInput = fopen($szFPInputFN, "rt");
	if(!$hInput)
	{
		$szErr = sprintf("Could not open file to read [%s]!", $szFPInputFN);
		terminatePrg($szErr);		
	}
	
	$szLogStr = sprintf("<!-- Loading file %s ... ", $szFPInputFN);
	
	if($nLogging)
	{
		doLogging($szLogStr);
	}
	else
	{
		printf($szLogStr);
	}
	
	if(!$hInput)
	{
		$szErr = sprintf("Error in openning file [%s]!", $szFPInputFN);
		terminatePrg($szErr);		
	}
	
	$nCount  = 0;
	$arList = array();
	printProgressStart();

	while(!feof($hInput))
	{
		$szLine = fgets($hInput);
		
		if($szLine === false)
		{
			break;
		}

		$szLine = trim($szLine);
		
		if($szLine == "")
		{
			//break;
			continue;
		}
			
		$arList[$nCount] = $szLine;
		$nCount++;
	
		printProgress($nCount, -1, $gnNumSamplesPerDot);
	}
	printProgressFinish();
	fclose($hInput);
	
	$szLogStr = sprintf("Finish loading file %s. %d rows are loaded. -->\n", $szFPInputFN, $nCount);
	if($nLogging)
	{
		doLogging($szLogStr);
	}
	else
	{
		printf($szLogStr);
	}
	
	return sizeof($arList);
}

/**
 *	Save data from the memory to the output file.
 */

function saveDataFromMem2File(&$arData, $szFPOutputFN, $szMode="wt", $nLogging=0)
{
	global $gnNumSamplesPerDot;
	
	if($szFPOutputFN == "")
	{
		terminatePrg("Invalid file  name! File path is EMPTY");				
	}
	
	$hOutput = fopen($szFPOutputFN, $szMode);

	if(!$hOutput)
	{
		$szErr = sprintf("Could not open file to save [%s]!", $szFPOutputFN);
		terminatePrg($szErr);		
	}
	$nNumRows = sizeof($arData);

/*
	if(!$hOutput)
	{
		terminatePrg("<!--Error in opening file for saving [{$szFPOutputFN}]!-->");
	}
*/
	$szLogStr = sprintf("<!-- Saving %d rows from mem to file %s ...", $nNumRows, $szFPOutputFN);
	if($nLogging)
	{
		doLogging($szLogStr);
	}
	else
	{
		printf($szLogStr);
	}
	
	$nNumSamplesPerDot = $gnNumSamplesPerDot;
	if($nNumRows > 10000)
	{
		$nNumSamplesPerDot = 1000;
	}
	
	printProgressStart();
	$arKeys = array_keys($arData);
	for($i=0; $i<$nNumRows; $i++)
	{
		$szKey = $arKeys[$i];
		fputs($hOutput, $arData[$szKey] . "\n");
		
		printProgress($i, $nNumRows, $nNumSamplesPerDot);
	} 
	fclose($hOutput);
	$szLogStr = sprintf("Finish adding %d rows to file %s! -->\n", $nNumRows, $szFPOutputFN);
	
	if($nLogging)
	{
		doLogging($szLogStr);
	}
	else
	{
		printf($szLogStr);
	}
}

/**
 * 	Scan to get all files and dirs in the input directory.
 */
function scandirx($szDirName)
{
	$dh  = opendir($szDirName);
	while (false !== ($filename = readdir($dh))) 
	{
    	$files[] = $filename;
	}
	return $files;
}

/**
 * 	Return a list of dirs that are sub-dirs of the input dir.
 *
 * 	szFilter is used to filter dirs by strstr
 */
function collectDirsInOneDir($szInputDirName, $szFilter="")
{
	printf("<!--Processing dir [%s]-->\n", $szInputDirName);

	if(!is_dir($szInputDirName))
	{
		return;
	}

	$arDirList = scandirx($szInputDirName);

	$nNumDirs = sizeof($arDirList);

	// print_r($arDirList);
	//print "Processing {$nNumDirs} dirs ...\n";
	$arOutputDirList = array();
	for($i=0; $i<$nNumDirs; $i++)
	{
		$szSubDirName = $arDirList[$i];
		
		if($szSubDirName == "." || $szSubDirName == "..")
		{
			continue;
		}

		$szFPSubDirName = sprintf("%s/%s", $szInputDirName, $szSubDirName);
		
		// check whether a file or a dir
		if(is_file($szFPSubDirName))
		{
			continue;
		}
		
		if($szFilter != "")
		{
			if(stristr($szSubDirName, $szFilter)) // case-insensitive search
			{
				$arOutputDirList[] = $szSubDirName;
			}
		}
		else
		{
			$arOutputDirList[] = $szSubDirName;
		}
		
	}
	
	printf("<!--Finish processing. Num dirs: %d. -->\n", sizeof($arOutputDirList));
	
	return $arOutputDirList;
}

/**
 *  Collect files in one dir having ext $szExt and body having szFilter.
 *  Output is list of files (no path) trimmed by szExt.
 *  
 * 	@param $szInputDirName
 * 	@param $szFilter
 * 	@param $szExt  --> including dot (.)
 */
function collectFilesInOneDir($szInputDirName, $szFilter="", $szExt=".jpg")
{
	printf("<!--Processing dir [%s]-->\n", $szInputDirName);

	if(!is_dir($szInputDirName))
		return;

	$arDirList = scandirx($szInputDirName);
	
//	print_r($arDirList);

	$nNumDirs = sizeof($arDirList);

	$arFileList = array();
	for($i=0; $i<$nNumDirs; $i++)
	{
		$szSubDirName = $arDirList[$i];
		if($szSubDirName == "." || $szSubDirName == "..")
		{
			continue;
		}

		$szFPSubDirName = sprintf("%s/%s", $szInputDirName, $szSubDirName);

		if($szFilter != "")
		{
			$nCheckFilter = stristr($szSubDirName, $szFilter);
		}
		else
		{
			$nCheckFilter = 1;
		}
		
		if(is_file($szFPSubDirName) && $nCheckFilter)
		{
			$nExtLen = strlen($szExt);
			$szExt1 = substr($szSubDirName, -$nExtLen, $nExtLen);
			
			if($szExt1 == $szExt)
			{
			    $nLen = strlen($szSubDirName);
   
    			$nShortenLen = $nLen - $nExtLen; // remove ext
				$arFileList[] = substr($szSubDirName, 0, $nShortenLen);
			}
				
		}
	}
	
	printf("<!--Finish processing. Num files: %d. -->\n", sizeof($arFileList));
	
	return $arFileList; 
}

/**
 * Collect all files, output is full file name (without path).
 * @param $szInputDirName
 */
function collectAllFilesInOneDir($szInputDirName)
{
	printf("<!--Processing dir [%s]-->\n", $szInputDirName);

	if(!is_dir($szInputDirName))
		return;

	$arDirList = scandirx($szInputDirName);
	
//	print_r($arDirList);

	$nNumDirs = sizeof($arDirList);

	$arFileList = array();
	for($i=0; $i<$nNumDirs; $i++)
	{
		$szSubDirName = $arDirList[$i];
		if($szSubDirName == "." || $szSubDirName == "..")
		{
			continue;
		}

		$szFPSubDirName = sprintf("%s/%s", $szInputDirName, $szSubDirName);

		if(is_file($szFPSubDirName))
		{
			$arFileList[] = $szSubDirName;
		}
	}
	
	printf("<!--Finish processing. Num files: %d. -->\n", sizeof($arFileList));
	
	return $arFileList; 
}

function execSysCmd($szCmdLine, $nExitIfFailed=1)
{
	printf("Command: [%s]\n", $szCmdLine);
	$szRet = system($szCmdLine);
	
	if($szRet === false)
	{
		printf("Failed to exec command [%s]\n", $szCmdLine);
		
		if($nExitIfFailed)
		{
			exit();
		}
	}
}

?>
