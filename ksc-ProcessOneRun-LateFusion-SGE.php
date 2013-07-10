<?php

/**
 * 		@file 	ksc-ProcessOneRun-LateFusion-SGE.php
 * 		@brief 	Generate jobs for SGE to process late fusion.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 10 Jul 2013.
 */

//*** Update Jul 25, 2012
//--> Customize for tvsin12

////////////////////////////////////////////////
// Update Nov 30
// Copied from nsc-ProcessOneRun-Test-TV10-SGE.php 

/////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

////////////////////////// THIS PART IS FOR CUSTOMIZATION //////////////////////
$szProjectCodeName = "kaori-secode-tvsin13";
$szCoreScriptName = "ksc-ProcessOneRun-LateFusion";

//$szSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-TVSIN11";
$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootExpDir = sprintf("%s/experiments", $gszRootBenchmarkExpDir); // New Jul 18

$szExpName = "hlf-tv2013";

$nMaxConcepts = 60;
$nNumConceptsPerHost = 1; //

//--> Be careful on number of jobs per script file. Should be lower than 200.
$nMaxVideos = 300; // max video programs of the test partition
$nNumVideosPerHost = 300; //

$szConfigDir = "fusion";
//////////////////// END FOR CUSTOMIZATION ////////////////////

////////////////////////////////// START ////////////////////////
if($argc!=7)
{
	printf("Usage %s <ExpName> <Max Concepts> <Num Concepts Per Host> <Max Video Progs> <Num Video Progs Per Host> <ConfigDir>\n", $argv[0]);
	printf("Usage %s %s %s %s %s %s %s\n", $argv[0], $szExpName, $nMaxConcepts, $nNumConceptsPerHost, $nMaxVideos, $nNumVideosPerHost, $szConfigDir);
	exit();
}

$szExpName = $argv[1];
$nMaxConcepts = $argv[2];
$nNumConceptsPerHost = $argv[3]; //
$nMaxVideos = $argv[4]; // max video programs of the test partition
$nNumVideosPerHost = $argv[5]; //
$szConfigDir = $argv[6];

$szScriptOutputDir = sprintf("%s/%s",
$szRootScriptOutputDir, $szExpName);
makeDir($szScriptOutputDir);

$szRunListConfigDir = sprintf("%s/%s/runlist/basic/%s", $szRootExpDir, $szExpName, $szConfigDir);
$arRunList = collectFilesInOneDir($szRunListConfigDir, $szExpName, ".cfg");
sort($arRunList);

$arRunFileList = array();
foreach($arRunList as $szRunID)
{
	if(!strstr($szRunID, "fusion"))
	{
		printf("### Skipping [%] ...\n", $szRunID);
		continue;
	}
	
	$arCmdLineList = array();
	for($j=0; $j<$nMaxConcepts; $j+=$nNumConceptsPerHost)
	{
		$nStartConcept = $j;
		$nEndConcept = $nStartConcept+$nNumConceptsPerHost;

		for($k=0; $k<$nMaxVideos; $k+=$nNumVideosPerHost)
		{
			$nStartPrg = $k;
			$nEndPrg = $nStartPrg+$nNumVideosPerHost;

			// each thread --> one log file
			$szFPLogFN = "/dev/null";
			
			// Usage: %s <RunID Config> <StartConcept> <EndConcept> <StartPrg> <EndPrg>
			$szFPRunIDConfig = sprintf("%s/%s.cfg", $szRunListConfigDir, $szRunID);
			$szParam = sprintf("%s %s %s %s %s %s",
			$szExpName, $szFPRunIDConfig, $nStartConcept, $nEndConcept, $nStartPrg, $nEndPrg);
			$szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);

			$arCmdLineList[] = $szCmdLine;
				
			//			$szCmdLine = "sleep 1s";
			//			$arCmdLineList[] = $szCmdLine;
		}
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
