<?php

/**
 * 		@file 	ksc-ProcessOneRun-Rank-SGE.php
 * 		@brief 	Generate jobs for SGE to process ranking one run.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 10 Jul 2013.
 */

//*** Update Jul 25, 2012
// Customize for tvsin12

// Update Aug 07
// Customize for tv2011

///////////////////////////////////////////////////////////
// Update Dec 22
// Run list dir --> fusion

// Update Dec 01
// Copied from nsc-ProcessOneRun-Eval-TV10-SGE --> split Eval into 2 tasks: Rank and Eval

// Update Nov 30
// Copied from nsc-ProcessOneRun-Train-TV10-SGE 

///////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

////////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////
$szProjectCodeName = "kaori-secode-bow-test"; // *** CHANGED ***
$szCoreScriptName = "ksc-ProcessOneRun-Rank";

//$szSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-TVSIN11";
$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szExpName = "imageclef2012-PhotoAnnFlickr"; // *** CHANGED ***

$nMaxConcepts = 100; // *** CHANGED ***
$nNumConceptsPerHost = 1; // *** CHANGED ***

//////////////////// END FOR CUSTOMIZATION ////////////////////

////////////////////////////////// START ////////////////////////

if($argc!=4)
{
	printf("Usage %s <ExpName> <Max Concepts> <Num Concepts Per Host>\n", $argv[0]);
	exit();
}
$szExpName = $argv[1];
$nMaxConcepts = $argv[2];
$nNumConceptsPerHost = $argv[3]; //

$szScriptOutputDir = sprintf("%s/%s",
$szRootScriptOutputDir, $szExpName);
makeDir($szScriptOutputDir);

$szRunListConfigDir = sprintf("%s/experiments/%s/runlist/fusion", $szRootDir, $szExpName);
$arRunList = collectFilesInOneDir($szRunListConfigDir, $szExpName, ".cfg");
sort($arRunList);

$arRunFileList = array();
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

	}

	$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szRunID); // specific for one set of data
	if(sizeof($arCmdLineList) > 0 )
	{
		saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
		$arRunFileList[] = $szFPOutputFN;
	}
}

$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szExpName); // specific for one set of data
saveDataFromMem2File($arRunFileList, $szFPOutputFN, "wt");

?>
