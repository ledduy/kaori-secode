<?php

/**
 * 		@file 	ksc-ProcessOneRun-Rank-New.php
 * 		@brief 	Rank One Run and Compute AP.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 03 Sep 2013.
 */

// *** Update Sep 03, 2013
// --> Adding evaluation

// Modification
// --> generateRankList --> keep info of videoID instead of inferring from keyframeID
// --> use arLUT[$szKeyFrameID] --> $szVideoID + szVideoPath

// Update Aug 07
// Customize for tv2011

// //////////////////////////////////////////////////////////////////////
// Update Dec 01
// Copied from nsc-ProcessOneRun-Eval-TV10.php --> split Eval into 2 tasks: Rank and Eval
// Remove parts of Eval task

// Update Oct 27
// tv2010 use sample_eval.pl
// qrel file: tv2010.feature.qrels.txt --> having one more column (stratum) in new method xInfAP

// Update Oct 22
// New place of qrel file
// annotation/nist.hlf-tv2005
// Check sys_id for consistency with file name --> USE the core name, so sys_id is UNUSED
// Save intermediate results

// Update Oct 04
// Must provide params for running script

// Update Oct 02
// Adding one more param in hlf-tv200z.cfg --> eval_qrel ~ groundtruth file

/*
 * Read all retrieved results information from trec_top_file. Read text tuples from trec_top_file of the form 030 Q0 ZF08-175-870 0 4238 prise1 qid iter docno rank sim run_id giving TREC document numbers (a string) retrieved by query qid (a string) with similarity sim (a float). The other fields are ignored, with the exception that the run_id field of the last line is kept and output. In particular, note that the rank field is ignored here; internally ranks are assigned by sorting by the sim field with ties broken determinstically (using docno). Sim is assumed to be higher for the docs to be retrieved first. File may contain no NULL characters. Any field following run_id is ignored.
 */

// //////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";
require_once "ksc-Tool-EvalMAP.php";

// /////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
$szFPLogFN = sprintf("%s.log", $szScriptBaseName); // *** CHANGED ***
                                                   
// ////////////////// END FOR CUSTOMIZATION ////////////////////
                                                   
// //////////////////////////////// START ////////////////////////
$szExpName = "imageclef2012-PhotoAnnFlickr";
$szFPRunConfigFN = "/net/sfv215/export/raid6/ledduy/ImageCLEF/2012/PhotoAnnFlickr/experiments/imageclef2012-PhotoAnnFlickr/runlist/imageclef2012-PhotoAnnFlickr.nsc.bow.dense6mul.rgbsift.Soft-500.devel2012.L1norm1x1.ksc.imageclef2012.R1.cfg";
$nStartConceptID = 0;
$nEndConceptID = 60;

if ($argc != 5)
{
    printf("Usage: %s <ExpName> <RunConfigFN> <StartConceptID> <EndConceptID>\n", $argv[0]);
    printf("Usage: %s %s %s %s %s\n", $argv[0], $szExpConfig, $szFPRunConfigFN, $nStartConceptID, $nEndConceptID);
    exit();
}

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]", $szStartTime, $argv[1], $argv[2], $argv[3], $argv[4]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

$szExpName = $argv[1]; // "hlf-tv2011";
$szFPRunConfigFN = $argv[2];
$nStartConceptID = intval($argv[3]);
$nEndConceptID = intval($argv[4]);

$szRootExpDir = sprintf("%s", $gszRootBenchmarkExpDir); // New Jul 18

$szFPConfigFN = sprintf("%s/experiments/%s/%s.cfg", $szRootExpDir, $szExpName, $szExpName);

$szSysID = basename($szFPRunConfigFN, ".cfg");

$arConfig = loadExperimentConfig($szFPConfigFN);
$arRunConfig = loadExperimentConfig($szFPRunConfigFN);

// trecvid/experiments/hlf-tv2010/annotation/nist.hlf-tv2010/tv2010.feature.qrels.txt
$szFPQRelFN = sprintf("%s/%s/%s/nist.%s/%s", $arConfig['exp_dir'], $szExpName, $arConfig['ann_dir'], $szExpName, $arConfig['eval_qrel']);

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
    // printf("%s\n", $arRunConfig['test_pat']); exit();
    $szTestVideoList = $arRunConfig['test_pat'];
}

$szFPTestVideoListFN = sprintf("%s/%s", $szExpMetaDataDir, $szTestVideoList); // trecvid.video.tv2007.devel.lst

$szFPConceptListFN = sprintf("%s/%s.lst", $szExpAnnDir, $arConfig['concept_list']);

if ($szSysID != $arRunConfig['sys_id'])
{
    $arRunConfig['sys_id'] = $szSysID;
}

$szExpModelRunDir = sprintf("%s/%s", $szExpModelDir, $arRunConfig['sys_id']);
$szExpResultRunDir = sprintf("%s/%s", $szExpResultDir, $arRunConfig['sys_id']);

$nNumConcepts = loadListFile($arConceptList, $szFPConceptListFN);

if ($nEndConceptID > $nNumConcepts)
{
    $nEndConceptID = $nNumConcepts;
}

$szFPEvalFN = sprintf("%s/%s.eval.csv", $szExpResultRunDir, $szSysID);
$arEvalResult = array();
$nMaxScores = $nMaxDocs = 10000;

for ($i = $nStartConceptID; $i < $nEndConceptID; $i ++)
{
    $szLine = $arConceptList[$i];
    // new format
    // 9003.Airplane #$# Airplane #$# 029 #$# Airplane #$# 218 #$# 003
    
    $arTmp = explode("#$#", $szLine);
    $szConceptName = trim($arTmp[0]);
    $nConceptID = intval($arTmp[5]) + 9000;
    
    // loading ann data
    // $szConceptName --> 9000.timeofday_day
    // $szRawConceptName --> "0 timeofday_day.txt"
    $arTz = explode(".", $szConceptName);
    $szCoreConceptName = trim($arTz[1]);
    $nCoreConceptID = intval($arTz[0]);
    $szRawConceptName = sprintf("%d %s", $nCoreConceptID - 9000, $szCoreConceptName);
    $szFPGroundTruthFN = sprintf("%s/groundtruth/%s.txt", $szExpAnnDir, $szRawConceptName);
    loadListFile($arRawGTList, $szFPGroundTruthFN);
    $arAnnList = array();
    foreach ($arRawGTList as $szLine)
    {
        $szDocID = trim($szLine);
        $arAnnList[$szDocID] = 1;
    }
    
    $szExpScoreRunConceptDir = sprintf("%s/%s", $szExpResultRunDir, $szConceptName);
    
    $szFPRankListFN = sprintf("%s/%s.rank", $szExpResultRunDir, $szConceptName);
    if (file_exists($szFPRankListFN))
    {
        printf("File exist [%s]\n", $szFPRankListFN);
        // continue;
    }
    $arTopScoreList = generateRankList($szFPTestVideoListFN, $szExpScoreRunConceptDir, $szFeatureExt, $nMaxScores);
    
    $arOutput = array();
    $nRank = 1;
    foreach ($arTopScoreList as $szShotID => $fScore)
    {
        $arOutput[] = sprintf("%s 0 %s %s %s %s", $nConceptID, $szShotID, $nRank, $fScore, $arRunConfig['sys_id']);
        $nRank ++;
    }
    
    saveDataFromMem2File($arOutput, $szFPRankListFN);
    
    $arMAPOutput = computeTVAveragePrecision($arAnnList, $arTopScoreList, $nMaxDocs);
    $arEvalResult = array();
    $szOutput = sprintf("%s, %f", $szConceptName, $arMAPOutput["ap"]);
    $arEvalResult[] = $szOutput;
    printf("%s\n", $szOutput);
    saveDataFromMem2File($arEvalResult, $szFPEvalFN, "a+t");
}

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]", $szStartTime, $szFinishTime, $argv[1], $argv[2], $argv[3], $argv[4]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// //////////////////////////// FUNCTIONS //////////////////////////////////
function generateRankList($szFPTestVideoListFN, $szScoreDir, $szFeatureExt, $nMaxScores = 2000)
{
    loadListFile($arVideoList, $szFPTestVideoListFN);
    
    $nCount = 1;
    
    $arAllScoreList = array();
    foreach ($arVideoList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        $szVideoID = trim($arTmp[0]);
        $szVideoPath = trim($arTmp[2]);
        
        printf("###%d. Processing video [%s] ...\n", $nCount, $szVideoID);
        $nCount ++;
        
        $szTestDataName = sprintf("%s.%s.svm", $szVideoID, $szFeatureExt);
        
        $szFPScoreFN = sprintf("%s/%s.res", $szScoreDir, $szTestDataName);
        
        if (! file_exists($szFPScoreFN))
        {
            continue;
        }
        
        loadListFile($arScoreList, $szFPScoreFN);
        foreach ($arScoreList as $szLine)
        {
            $arTmp = explode("#$#", $szLine);
            
            $szKeyFrameID = trim($arTmp[0]);
            $fScore = floatval($arTmp[1]);
            
            $arAllScoreList[$szKeyFrameID] = $fScore;
        }
    }
    
    printf("Sorting [%s] and selecting top [%s] shots ...\n", sizeof($arAllScoreList), $nMaxScores);
    arsort($arAllScoreList);
    
    $arTopScoreList = array();
    // sort by shots.
    $nMaxCount = 0;
    foreach ($arAllScoreList as $szKeyFrameID => $fScore)
    {
        // TRECVID2007_219.shot219_1.RKF_0.Frame_4
        $arTmp = explode(".RKF_", $szKeyFrameID);
        $szShotID = trim($arTmp[0]);
        
        // treatment for cu-vireo374 bow
        if (strstr($szShotID, "_RKF"))
        {
            $szShotID = str_replace("_RKF", "", $szShotID);
        }
        
        if (! isset($arTopScoreList[$szShotID]))
        {
            $arTopScoreList[$szShotID] = sprintf("%s", $fScore);
            $nMaxCount ++;
            
            if ($nMaxCount >= $nMaxScores)
            {
                break;
            }
        }
    }
    
    print_r($arTopScoreList);
    return $arTopScoreList;
}

?>