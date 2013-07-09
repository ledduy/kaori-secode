<?php

/**
 * 		@file 	ksc-ProcessOneRun-Train-New-SGE.php
 * 		@brief 	Train One Run.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */

//*** Update Jul 18, 2012
// Customize for tvsin12  --> Search for *** CHANGED ***

// Update Jun 27, 2012
// Customize for imageclef2012
//--> Adding filters to organize scripts into different batches, e.g. only dense4
//--> Be careful on number of jobs per script file. Should be lower than 200.

///////////////////////////////////////////
// Update 23 Nov
// Change the dir for scanning run configs --> runlist

// Update 22 Nov
// $szFPLogFN = "/dev/null";

// Update 22 Oct
// Auto collect run configs - no longer use run list file.

// Update 05 Oct
// Move params of svm to config file

// Update 03 Oct
// Add ExpName as param
// Check max concepts and num concepts per host

// Update 02 Oct
// Large dataset requires long training time and predicting time (due to large #sv)
// Find optimal dataset size --> contribution

// Update 01 Oct
// Params:
//	+ $szExpName = "hlf-tv2005"; --> general
// + $szFPRunListFN = sprintf("%s/%s.run-trial.lst", $szRunListConfigDir, $szExpName); --> list of runs

// Update 27 Aug
// Config params:
// szExpName --> hlf-tv2005
// szFPRunListFN

// change dir from raid4 --> raid6

//////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

///////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////
$szProjectCodeName = "kaori-secode-tvsin13"; // *** CHANGED ***
$szCoreScriptName = "ksc-ProcessOneRun-Train-New"; // *** CHANGED ***

//$szSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-TVSIN11";
$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

//$szScriptOutputDir = sprintf("/net/per900b/raid0/ledduy/bin/%s/%s/%s", $szProjectCodeName, $szCoreScriptName, $szExpName);
$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootExpDir = sprintf("%s/experiments", $gszRootBenchmarkExpDir); // New Jul 18

$szExpName = "hlf-tv2013"; // *** CHANGED ***

$nMaxConcepts = 60; // *** CHANGED ***
$nNumConceptsPerHost = 1; // *** CHANGED ***

$szConfigDir = "basic";

$arFilterList = array(
		".dense4.",
		".dense6.",
		".dense8.",
		".phow8.",
		".phow10.",
		".phow12.",
		".dense4mul.csift",
		".dense4mul.rgbsift",
		".dense4mul.oppsift",
		".dense6mul.csift",
		".dense6mul.rgbsift",
		".harlap6mul.rgbsift",
		".dense6mul.oppsift",
		".g_cm.",
		".g_ch.",
		".g_eoh.",
		".g_lbp."
		
); // *** CHANGED ***
//////////////////// END FOR CUSTOMIZATION ////////////////////

////////////////////////////////// START ////////////////////////
if($argc!=5)
{
	printf("Usage %s <ExpName> <Max Concepts> <Num Concepts Per Host> <ConfigDir>\n", $argv[0]);
	printf("Usage %s %s %s %s %s\n", $argv[0], $szExpName, $nMaxConcepts, $nNumConceptsPerHost, $szConfigDir);
	exit();
}
$szExpName = $argv[1];
$nMaxConcepts = $argv[2];
$nNumConceptsPerHost = $argv[3];
$szConfigDir = $argv[4];

$szScriptOutputDir = sprintf("%s/%s", $szRootScriptOutputDir, $szExpName);
makeDir($szScriptOutputDir);

$szRunListConfigDir = sprintf("%s/%s/runlist/%s", $szRootExpDir, $szExpName, $szConfigDir);
$arRunList = collectFilesInOneDir($szRunListConfigDir, $szExpName, ".cfg");
sort($arRunList);

$arRunFileList = array();
$arRunFileFilterList = array();
foreach($arRunList as $szRunID)
{
	// --> !!! IMPORTANT param

	$arCmdLineList = array();
	for($j=0; $j<$nMaxConcepts; $j+=$nNumConceptsPerHost)
	{
		$nStart = $j;
		$nEnd = $nStart+$nNumConceptsPerHost;

		$szFPLogFN = "/dev/null";

		$szFPRunIDConfig = sprintf("%s/%s.cfg", $szRunListConfigDir, $szRunID);
		// Usage: %s <RunID> <Start> <End>
		$szParam = sprintf("%s %s %s %s",
				$szExpName, $szFPRunIDConfig, $nStart, $nEnd);
		$szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);

		$arCmdLineList[] = $szCmdLine;

		$szCmdLine = sprintf("sleep 30s");
		$arCmdLineList[] = $szCmdLine;
	}

	$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szRunID); // specific for one set of data
	if(sizeof($arCmdLineList) > 0 )
	{
		saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
		$arRunFileList[] = $szFPOutputFN;
		
		foreach($arFilterList as $szFilter)
		{
			if(strstr($szRunID, $szFilter))
			{
				$arRunFileFilterList[$szFilter][] = $szFPOutputFN;
			}
		}
	}
}

$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szExpName); // specific for one set of data
saveDataFromMem2File($arRunFileList, $szFPOutputFN, "wt");

foreach($arFilterList as $szFilter)
{
	$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.F%s.sh", $szScriptOutputDir, $szCoreScriptName, $szExpName, trim($szFilter, ".")); // specific for one set of data
	if(sizeof($arRunFileFilterList[$szFilter]))
	{
		saveDataFromMem2File($arRunFileFilterList[$szFilter], $szFPOutputFN, "wt");
	}
}

?>
