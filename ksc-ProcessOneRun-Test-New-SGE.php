<?php

/**
 * 		@file 	ksc-ProcessOneRun-Train-New-SGE.php
 * 		@brief 	Test One Run.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Sep 2014.
 */


// ////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";

// /////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////
$szProjectCodeName = "kaori-secode-vsd2014"; // *** CHANGED ***
$szCoreScriptName = "ksc-ProcessOneRun-Test-New"; // *** CHANGED ***
                                                   
$szSGEScriptDir = $gszSGEScriptDir; // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

// $szScriptOutputDir = sprintf("/net/per900b/raid0/ledduy/bin/%s/%s/%s", $szProjectCodeName, $szCoreScriptName, $szExpName);
$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;

$szTestConfigName = "mediaeval-vsd-2014.devel2013-new.test2013-new";// *** CHANGED ***

$nMaxConcepts = 20; // *** CHANGED ***
$nNumConceptsPerHost = 1; // *** CHANGED ***

// ////////////////// END FOR CUSTOMIZATION ////////////////////
   
// //////////////////////////////// START ////////////////////////
if ($argc != 4)
{
    printf("Usage %s <TestConfigName> <Max Concepts> <Num Concepts Per Host>\n", $argv[0]);
    printf("Usage %s %s %s %s\n", $argv[0], $szTestConfigName, $nMaxConcepts, $nNumConceptsPerHost);
    exit();
}
$szTestConfigName = $argv[1];
$nMaxConcepts = $argv[2];
$nNumConceptsPerHost = $argv[3];

$szScriptOutputDir = sprintf("%s/%s", $szRootScriptOutputDir, $szTestConfigName);
makeDir($szScriptOutputDir);

$szRootResultDir = sprintf("%s/result/keyframe-5/%s", $szRootDir, $szTestConfigName); // dir containing prediction result of RUNs

$szFPTestConfigFN = sprintf("%s/%s.cfg", $szRootResultDir, $szTestConfigName);

$arTestConfig = loadExperimentConfig($szFPTestConfigFN); // to get model_name & test_pat

$szModelConfigName = $arTestConfig['model_name'];

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
        
        $nNumTestVideos = $arMaxVideosPerPatList[$arTestConfig['test_pat']];
        $nNumHosts = min(10, intval($arMaxHostsPerPatList[$arTestConfig['test_pat']]/$nMaxConcepts));
        
        $nNumVideosPerHost = max(1, intval($nNumTestVideos/$nNumHosts), 50);
        for($kz=0; $kz<$nNumTestVideos; $kz+=$nNumVideosPerHost)
        {
        	$nStartVideo = $kz;
        	$nEndVideo = $kz+$nNumVideosPerHost;
	        // Usage: %s <RunID> <StartConcept> <EndConcept> <StartVideo> <EndVideo>
	        $szParam = sprintf("%s %s %s %s %s %s", $szTestConfigName, $szRunID, $nStart, $nEnd, $nStartVideo, $nEndVideo);
	        $szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPErrFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
	        
	        $arCmdLineList[] = $szCmdLine;
        }
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
		
			$nStartVideo = 0;
			$nEndVideo = 400;
			$szParam = sprintf("%s %s %s %s %s %s", $szTestConfigName, $szRunID, $nStartConcept, $nEndConcept, $nStartVideo, $nEndVideo);
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
