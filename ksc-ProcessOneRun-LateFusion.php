<?php

/**
 * 		@file 	ksc-ProcessOneRun-LateFusion.php
 * 		@brief 	Late Fusion by Combining Scores.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 10 Jul 2013.
 */

// *** Update Jul 25, 2012
// Customize for tvsin12
//

// Aug 16 , 2011
// Stil use /=$fWeight because some configs are not available at fusion time
// $gnMakeAverage = 1;

// !!! IMPORTANT !!!
// --> $arFusedScoreList[$szKeyFrameID] /= $fWeight; --> do not divide to number of fusion sources

// Update 10 Jul

// Adding one more threshold for fusion

// Update May 30
// Check existing score

// Update Mar 10
// Check fusion extension in RunID

// Update Oct 19
// Allow specify whether scores are normalized for fusion or not
// Info for each fused run
// run_id_001 #$# hlf-tv2005.run000001
// run_id_001_feature #$# nsc.cCV_HSV.g5.q3.g_cm
// run_id_001_weight #$# 1
// run_id_001_normscore #$# 1 #$# require normalize score before fusion

// Update 03 Oct
// No longer use normalization because of using probability output

// Aug 07 - version for TV2010

// ////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";

// /////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////

$gnMakeAverage = 1;
$gSkippExistingFiles = 0;
// only skip existing files not earlier than 10 Jul 2013
$nDateLimit = mktime(0, 0, 0, 7, 10, 2013);

// $szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootExpDir = sprintf("%s", $gszRootBenchmarkExpDir); // New Jul 18

$szFPLogFN = "ksc-ProcessOneRun-LateFusion.log";

$szExpName = "hlf-tv2013";

$szFPRunConfigFN = "/net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/experiments/hlf-tv2013/runlist/hlf-tv2013.nsc.bow.dense6mul.rgbsift.Soft-500.devel-nistNew.norm1x1.ksc.tvsin13.fusion.cfg";
$nStartConceptID = 0;
$nEndConceptID = 1;

$nStartVideo = 0;
$nEndVideo = 1;

// ////////////////// END FOR CUSTOMIZATION ////////////////////

// //////////////////////////////// START ////////////////////////

if ($argc != 7)
{
    printf("Usage: %s <ExpName> <RunConfigFN> <StartConcept> <EndConcept> <StartVideo> <EndVideo>\n", $argv[0]);
    printf("Usage: %s %s %s %s %s %s %s \n", $argv[0], $szExpName, $szFPRunConfigFN, $nStartConceptID, $nEndConceptID, $nStartVideo, $nEndVideo);
    exit();
}

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

$szExpName = $argv[1];
$szFPRunConfigFN = $argv[2];
$nStart = intval($argv[3]);
$nEnd = intval($argv[4]);

$nStartVideo = intval($argv[5]);
$nEndVideo = intval($argv[6]);

if (! strstr($szFPRunConfigFN, "fusion"))
{
    printf("Run Name [%s] does not have 'fusion'\n", basename($szFPRunConfigFN));
    exit();
}

// $szRootDir = "U:";

$szFPConfigFN = sprintf("%s/experiments/%s/%s.cfg", $szRootExpDir, $szExpName, $szExpName);

$szSysID = basename($szFPRunConfigFN, ".cfg");

$arConfig = loadExperimentConfig($szFPConfigFN);
$arRunConfig = loadExperimentConfig($szFPRunConfigFN);

if ($szSysID != $arRunConfig['sys_id'])
{
    $arRunConfig['sys_id'] = $szSysID;
}

$szFeatureExt = $arRunConfig['feature_ext']; // "nsc.cCV_HSV.g5.q3.g_cm";

$szRootExpDir = sprintf("%s/%s", $arConfig['exp_dir'], $arConfig['exp_name']);
$szExpAnnDir = sprintf("%s/%s", $szRootExpDir, $arConfig['ann_dir']); // annotation
$szExpMetaDataDir = sprintf("%s/%s", $szRootExpDir, $arConfig['metadata_dir']); // annotation
$szExpModelDir = sprintf("%s/%s", $szRootExpDir, $arConfig['model_dir']); // annotation
$szExpResultDir = sprintf("%s/%s", $szRootExpDir, $arConfig['result_dir']); // annotation
$szRootMetaDataKFDir = $arConfig['root_metadata_kf_dir'];
$szRootFeatureDir = $arConfig['root_feature_dir'];

$szTestVideoList = $arConfig['test_pat'];
// Update Jun 14, 2011
if (isset($arRunConfig['test_pat']))
{
    $szTestVideoList = $arRunConfig['test_pat'];
}

$szFPTestVideoListFN = sprintf("%s/%s", $szRootMetaDataKFDir, $szTestVideoList); // trecvid.video.tv2007.devel.lst

$szFPConceptListFN = sprintf("%s/%s.lst", $szExpAnnDir, $arConfig['concept_list']);

$szExpResultRunDir = sprintf("%s/%s", $szExpResultDir, $arRunConfig['sys_id']);

$nNumConcepts = loadListFile($arConceptList, $szFPConceptListFN);

// $nStart = 0;
// $nEnd = 200;

if ($nEnd > $nNumConcepts)
{
    $nEnd = $nNumConcepts;
}

$arDirList = array();
$arWeightList = array();
$arNormOptionList = array(); // added Oct 19
$arFeatureExtList = array();

// NEW!!!
$arMinThresholdList = array(); // added Jul 10
/*
 * run_id_001 #$# hlf-tv2005.run000001 run_id_001_feature #$# nsc.cCV_HSV.g5.q3.g_cm run_id_001_weight #$# 1 run_id_001_norm #$# 1 #$# require normalize score before fusion
 */

foreach ($arRunConfig as $szKey => $szVal)
{
    // 3 rows must be grouped and processed once
    if (strstr($szKey, "run_id"))
    {
        if (strstr($szKey, "threshold") || strstr($szKey, "weight") || strstr($szKey, "feature") || strstr($szKey, "normscore")) // these rows already processed
        {
            continue;
        }
        
        $szRunID = $szKey;
        $arDirList[$szRunID] = sprintf("%s/%s", $szExpResultDir, $szVal);
        
        $szWeightKey = sprintf("%s_weight", $szRunID);
        
        if (! isset($arRunConfig[$szWeightKey]))
        {
            printf("Data error [%s] in config file\n", $szWeightKey);
            exit();
        }
        $arWeightList[$szRunID] = $arRunConfig[$szWeightKey];
        
        $szFeatureKey = sprintf("%s_feature", $szRunID);
        if (! isset($arRunConfig[$szFeatureKey]))
        {
            printf("Data error [%s] in config file\n", $szFeatureKey);
            exit();
        }
        $arFeatureExtList[$szRunID] = $arRunConfig[$szFeatureKey];
        
        $szNormScoreKey = sprintf("%s_normscore", $szRunID);
        if (! isset($arRunConfig[$szNormScoreKey]))
        {
            printf("Data error [%s] in config file\n", $szNormScoreKey);
            exit();
        }
        $arNormOptionList[$szRunID] = $arRunConfig[$szNormScoreKey];
        
        $szMinThresholdKey = sprintf("%s_threshold", $szRunID);
        if (! isset($arRunConfig[$szMinThresholdKey]))
        {
            $arMinThresholdList[$szRunID] = 0.0; // default
        } else
        {
            $arMinThresholdList[$szRunID] = $arRunConfig[$szMinThresholdKey];
            $arMinThresholdList[$szRunID] = 0.0; // default
        }
    }
}

$arVideoList = array();
loadListFile($arRawList, $szFPTestVideoListFN);
foreach ($arRawList as $szLine)
{
    $arTmp = explode("#$#", $szLine);
    
    $szVideoID = trim($arTmp[0]);
    
    $arVideoList[$szVideoID] = 1;
}

$arAllOutput = array();
for ($i = $nStart; $i < $nEnd; $i ++)
{
    $szLine = $arConceptList[$i];
    // new format
    // 9003.Airplane #$# Airplane #$# 029 #$# Airplane #$# 218 #$# 003
    $arTmp = explode("#$#", $szLine);
    $szConceptName = trim($arTmp[0]);
    $nConceptID = intval($arTmp[5]) + 9000;
    
    $szExpScoreRunConceptDir = sprintf("%s/%s", $szExpResultRunDir, $szConceptName);
    makeDir($szExpScoreRunConceptDir);
    
    $arConceptDirList = array();
    foreach ($arDirList as $szKey => $szDirName)
    {
        $szFusedRunConceptDir = sprintf("%s/%s", $szDirName, $szConceptName);
        $arConceptDirList[$szKey] = $szFusedRunConceptDir;
    }
    fusionKeyFrameScoresForOneConcept($szExpScoreRunConceptDir, $szFeatureExt, $arVideoList, $arConceptDirList, $arWeightList, $arNormOptionList, $arFeatureExtList, $nStartVideo, $nEndVideo, $arMinThresholdList);
}

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $szFinishTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// php -f nsc-LateFusion-TV2010.php hlf-tv2010.run1001.fusion.run1-6

// /////////////////////////////////////// FUNCTIONS /////////////////////////////////
function normalizeScorez($fScore)
{
    $fNormScore = 1.0 / (1.0 + exp(- $fScore));
    
    return $fNormScore;
}

// Update Oct 19
// Adding $arNormOptionList as param
function fusionKeyFrameScores($szFPOutputFN, $arFileList, $arWeightList, $arNormOptionList, $arMinThresholdList)
{
    $arScoreList = array();
    
    $arKeyFrameList = array();
    $arNormList = array();
    foreach ($arFileList as $szRunID => $szFPScoreFN)
    {
        if (! file_exists($szFPScoreFN))
        {
            printf("File [%s] not found\n", $szFPScoreFN);
            continue;
        }
        loadListFile($arRawList, $szFPScoreFN);
        
        $fMin = 1e+10;
        $fMax = - 1e+10;
        foreach ($arRawList as $szLine)
        {
            // TRECVID2007_111.shot111_1.RKF_0.Frame_3 #$# -1.00476
            $arTmp = explode("#$#", $szLine);
            $szKeyFrameID = trim($arTmp[0]);
            $fScore = floatval($arTmp[1]);
            
            // NEW!!
            if ($fScore >= $arMinThresholdList[$szRunID])
            {
                $arScoreList[$szRunID][$szKeyFrameID] = $fScore;
                
                $arKeyFrameList[$szKeyFrameID] = 1;
            }
            
            if ($fScore < $fMin)
            {
                $fMin = $fScore;
            }
            if ($fScore > $fMax)
            {
                $fMax = $fScore;
            }
        }
        $arNormList[$szRunID]['min'] = $fMin;
        $arNormList[$szRunID]['max'] = $fMax;
        $arNormList[$szRunID]['range'] = $fMax - $fMin + 1;
    }
    
    $arFusedScore = array();
    foreach ($arKeyFrameList as $szKeyFrameID => $nTmp)
    {
        $arFusedScoreList[$szKeyFrameID] = 0;
        
        $fWeight = 0;
        foreach ($arScoreList as $szRunID => $arKFScore)
        {
            if (isset($arKFScore[$szKeyFrameID]))
            {
                $fScore = $arKFScore[$szKeyFrameID];
                
                if ($arNormOptionList[$szRunID])
                {
                    $fNormScore = normalizeScorez($fScore);
                } else // no normalization
                {
                    $fNormScore = $fScore;
                }
                
                $fFusedScore = $fNormScore * $arWeightList[$szRunID];
                $fWeight += $arWeightList[$szRunID];
                $arFusedScoreList[$szKeyFrameID] += $fFusedScore;
            }
        }
        
        global $gnMakeAverage;
        if ($gnMakeAverage)
        {
            $arFusedScoreList[$szKeyFrameID] /= $fWeight;
        }
    }
    // print_r($arFusedScoreList); exit();
    
    $arOutput = array();
    foreach ($arFusedScoreList as $szKeyFrameID => $fScore)
    {
        $arOutput[] = sprintf("%s #$# %s", $szKeyFrameID, $fScore);
    }
    saveDataFromMem2File($arOutput, $szFPOutputFN);
}

// Adding $arNormOptionList - Oct 19
function fusionKeyFrameScoresForOneConcept($szOutputDir, $szOutputFeatureExt, &$arVideoList, &$arDirList, &$arWeightList, &$arNormOptionList, &$arFeatureExtList, $nStartVideo, $nEndVideo, &$arMinThresholdList)
{
    $nNumVideos = sizeof($arVideoList);
    if ($nEndVideo > $nNumVideos)
    {
        $nEndVideo = $nNumVideos;
    }
    
    $nCount = 0;
    foreach ($arVideoList as $szVideoID => $nTmp)
    {
        if ($nCount < $nStartVideo || $nCount >= $nEndVideo)
        {
            $nCount ++;
            continue;
        }
        $nCount ++;
        $arFileList = array();
        
        foreach ($arDirList as $szRunID => $szDirName)
        {
            $szFeatureExt = $arFeatureExtList[$szRunID];
            $szFPScoreFN = sprintf("%s/%s.%s.svm.res", $szDirName, $szVideoID, $szFeatureExt);
            $arFileList[$szRunID] = $szFPScoreFN;
        }
        $szFPOutputScoreFN = sprintf("%s/%s.%s.svm.res", $szOutputDir, $szVideoID, $szOutputFeatureExt);
        
        global $gSkippExistingFiles;
        
        global $nDateLimit;
        if ($gSkippExistingFiles)
        {
            // file exist AND not out of data --> skip
            if (file_exists($szFPOutputScoreFN))
            {
                $nSkipByOutOfDate = 1;
                if (filemtime($szFPOutputScoreFN) - $nDateLimit > 0)
                {
                    $nSkipByOutOfDate = 0;
                }
                
                if (! $nSkipByOutOfDate)
                {
                    printf("File [%s] existed! Skip ...\n", $szFPOutputScoreFN);
                    continue;
                }
            }
        }
        
        fusionKeyFrameScores($szFPOutputScoreFN, $arFileList, $arWeightList, $arNormOptionList, $arMinThresholdList);
    }
}

?>