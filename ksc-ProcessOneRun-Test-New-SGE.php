<?php

/**
 * 		@file 	ksc-ProcessOneRun-Test-New-SGE.php
 * 		@brief 	Train One Run.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */

// Update Jun 28, 2012
// Customize for imageclef12
// --> Adding filters to organize scripts into different batches, e.g. only dense4
// --> Be careful on number of jobs per script file. Should be lower than 200.

// /////////////////////////////////////////////////////////
// Update 23 Nov
// Change the dir for scanning run configs --> runlist

// Update 22 Oct
// Auto collect run configs - no longer use run list file.

// Update 03 Oct
// Add ExpName as param
// Check max concepts and num concepts per host
// Check max videos and num videos per host

// Update 01 Oct
// Params:
// + $szExpName = "hlf-tv2005"; --> general
// + $szFPRunListFN = sprintf("%s/%s.run-trial.lst", $szRunListConfigDir, $szExpName); --> list of runs

// ///////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

// //////////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////
$szProjectCodeName = "kaori-secode-bow-test"; // *** CHANGED ***
$szCoreScriptName = "ksc-ProcessOneRun-Test-New"; // *** CHANGED ***
                                                  
// $szSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-TVSIN11";
$szSGEScriptDir = $gszSGEScriptDir; // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;
$szRootExpDir = sprintf("%s/experiments", $gszRootBenchmarkExpDir); // New Jul 18

$szExpName = "imageclef2012-PhotoAnnFlickr"; // *** CHANGED ***

$nMaxConcepts = 100; // *** CHANGED ***
$nNumConceptsPerHost = 1; // *** CHANGED ***
                          
// --> Be careful on number of jobs per script file. Should be lower than 200.
$nMaxVideos = 300; // max video programs of the test partition // *** CHANGED ***
$nNumVideosPerHost = 30; // *** CHANGED ***

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
)
; // *** CHANGED ***
   
// ////////////////// END FOR CUSTOMIZATION ////////////////////
   
// //////////////////////////////// START ////////////////////////

if ($argc != 7)
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

$szScriptOutputDir = sprintf("%s/%s", $szRootScriptOutputDir, $szExpName);
makeDir($szScriptOutputDir);

$szRunListConfigDir = sprintf("%s/%s/runlist/%s", $szRootExpDir, $szExpName, $szConfigDir); // New Jul 18
$arRunList = collectFilesInOneDir($szRunListConfigDir, $szExpName, ".cfg");
sort($arRunList);

$arRunFileList = array();
$arRunFileFilterList = array();
foreach ($arRunList as $szRunID)
{
    $arCmdLineList = array();
    for ($j = 0; $j < $nMaxConcepts; $j += $nNumConceptsPerHost)
    {
        $nStartConcept = $j;
        $nEndConcept = $nStartConcept + $nNumConceptsPerHost;
        
        for ($k = 0; $k < $nMaxVideos; $k += $nNumVideosPerHost)
        {
            $nStartPrg = $k;
            $nEndPrg = $nStartPrg + $nNumVideosPerHost;
            
            $szFPLogFN = "/dev/null";
            
            // Usage: %s <RunID Config> <StartConcept> <EndConcept> <StartPrg> <EndPrg>
            $szFPRunIDConfig = sprintf("%s/%s.cfg", $szRunListConfigDir, $szRunID);
            $szParam = sprintf("%s %s %s %s %s %s", $szExpName, $szFPRunIDConfig, $nStartConcept, $nEndConcept, $nStartPrg, $nEndPrg);
            $szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
            
            $arCmdLineList[] = $szCmdLine;
            
            $szCmdLine = "sleep 1s";
            $arCmdLineList[] = $szCmdLine;
        }
    }
    
    $szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szRunID); // specific for one set of data
    if (sizeof($arCmdLineList) > 0)
    {
        saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
        $arRunFileList[] = $szFPOutputFN;
        
        foreach ($arFilterList as $szFilter)
        {
            if (strstr($szRunID, $szFilter))
            {
                $arRunFileFilterList[$szFilter][] = $szFPOutputFN;
            }
        }
    }
}

$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szExpName); // specific for one set of data
saveDataFromMem2File($arRunFileList, $szFPOutputFN, "wt");

foreach ($arFilterList as $szFilter)
{
    $szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.F%s.sh", $szScriptOutputDir, $szCoreScriptName, $szExpName, trim($szFilter, ".")); // specific for one set of data
    if (sizeof($arRunFileFilterList[$szFilter]))
    {
        saveDataFromMem2File($arRunFileFilterList[$szFilter], $szFPOutputFN, "wt");
    }
}

?>
