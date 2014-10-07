<?php

/**
 * 		@file 	ksc-ProcessOneRun-Train-New-SGE.php
 * 		@brief 	Train One Run.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 03 Sep 2014.
 */

// *** Update Jul 18, 2012
// Customize for tvsin12 --> Search for *** CHANGED ***

// Update Jun 27, 2012
// Customize for imageclef2012
// --> Adding filters to organize scripts into different batches, e.g. only dense4
// --> Be careful on number of jobs per script file. Should be lower than 200.

// /////////////////////////////////////////
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
// + $szExpName = "hlf-tv2005"; --> general
// + $szFPRunListFN = sprintf("%s/%s.run-trial.lst", $szRunListConfigDir, $szExpName); --> list of runs

// Update 27 Aug
// Config params:
// szExpName --> hlf-tv2005
// szFPRunListFN

// change dir from raid4 --> raid6

// ////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

// /////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////
$szProjectCodeName = "kaori-secode-vsd2014"; // *** CHANGED ***
$szCoreScriptName = "ksc-ProcessOneRun-Train-New"; // *** CHANGED ***
                                                   
$szSGEScriptDir = $gszSGEScriptDir; // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

// $szScriptOutputDir = sprintf("/net/per900b/raid0/ledduy/bin/%s/%s/%s", $szProjectCodeName, $szCoreScriptName, $szExpName);
$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;

$szModelConfigName = "mediaeval-vsd-2014.devel2013-new";// *** CHANGED ***

$nMaxConcepts = 20; // *** CHANGED ***
$nNumConceptsPerHost = 1; // *** CHANGED ***

   // ////////////////// END FOR CUSTOMIZATION ////////////////////
   
// //////////////////////////////// START ////////////////////////
if ($argc != 4)
{
    printf("Usage %s <ModelConfigName> <Max Concepts> <Num Concepts Per Host>\n", $argv[0]);
    printf("Usage %s %s %s %s\n", $argv[0], $szModelConfigName, $nMaxConcepts, $nNumConceptsPerHost);
    exit();
}
$szModelConfigName = $argv[1];
$nMaxConcepts = $argv[2];
$nNumConceptsPerHost = $argv[3];
$szConfigDir = $argv[4];

$szScriptOutputDir = sprintf("%s/%s", $szRootScriptOutputDir, $szModelConfigName);
makeDir($szScriptOutputDir);

$szModelFeatureConfigDir = sprintf("%s/model/keyframe-5/%s/config", $szRootDir, $szModelConfigName);
$arRunList = collectFilesInOneDir($szModelFeatureConfigDir, "", ".cfg");
sort($arRunList);

$arRunFileList = array();
$arRunFileFilterList = array();
foreach ($arRunList as $szRunID)
{
    // --> !!! IMPORTANT param
    
    $arCmdLineList = array();
    for ($j = 0; $j < $nMaxConcepts; $j += $nNumConceptsPerHost)
    {
        $nStart = $j;
        $nEnd = $nStart + $nNumConceptsPerHost;
        
        $szFPLogFN = "/dev/null";
        $szFPErrFN = sprintf("%s/%s.%s.err", $gszSGEScriptDir, $szCoreScriptName, $szRunID);
        
        // Usage: %s <RunID> <Start> <End>
        $szParam = sprintf("%s %s %s %s", $szModelConfigName, $szRunID, $nStart, $nEnd);
        $szCmdLine = sprintf("qsub -pe localslots 2 -e %s -o %s %s %s", $szFPErrFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
        
        $arCmdLineList[] = $szCmdLine;
    }
    
    $szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szRunID); // specific for one set of data
    if (sizeof($arCmdLineList) > 0)
    {
        saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
        $arRunFileList[] = $szFPOutputFN;
    }
}

$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szExpName); // specific for one set of data
saveDataFromMem2File($arRunFileList, $szFPOutputFN, "wt");

$nNumRuns = sizeof($arRunList);
$arHostList = array(
	"per910a" => 18,
	"per910b" => 18,
	"per910c" => 18,
);

$nCount = 0;
foreach($arHostList as $szHostName => $nMaxJobs)
{
	$nStart = $nCount;
	$nEnd = $nStart+$nMaxJobs;
	$arCmdLineList = array();
	for($i=$nStart; $i<$nEnd; $i++)
	{
		if(!isset($arRunList[$i]))
		{
			break;
		}
		$szRunID = $arRunList[$i];

		//printf($szRunID); exit();
		for ($j = 0; $j < $nMaxConcepts; $j += $nNumConceptsPerHost)
		{
			$nStartConcept = $j;
			$nEndConcept = $nStartConcept + $nNumConceptsPerHost;
		
			//$szRunID = "NUL";
			$szParam = sprintf("%s %s %s %s", $szModelConfigName, $szRunID, $nStartConcept, $nEndConcept);
			//printf($szParam);
			$szCmdLine = sprintf("%s %s &", $szFPSGEScriptName, $szParam);
			//printf($szCmdLine);exit();
			$arCmdLineList[] = $szCmdLine;
		}	
	}
	$nCount = $nEnd;
	$szFPOutputFN = sprintf("%s/runme.%s.%s.sh", $szScriptOutputDir, $szHostName, $szCoreScriptName); // specific for one set of data
	if (sizeof($arCmdLineList) > 0)
	{
		saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
	}
	
}
?>
