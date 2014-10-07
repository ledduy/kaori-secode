<?php

/**
 * 		@file 	ksc-ProcessOneRun-Eval-New.php
 * 		@brief 	Eval One Run and Compute AP.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 02 Sep 2014.
 */

// //////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";

// /////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
$szFPLogFN = sprintf("%s.log", $szScriptBaseName); // *** CHANGED ***
                                                   
// ////////////////// END FOR CUSTOMIZATION ////////////////////
                                                   
// //////////////////////////////// START ////////////////////////

$szTestConfigName = "mediaeval-vsd-2014.devel2013-new.test2013-new"; // association of devel-pat & test-pat
$szModelFeatureConfig = "nsc.bow.dense6mul.rgbsift.Soft-1000.devel2011-new.L1norm1x1.shotMAX.R11"; // feature_ext + training params

$nStartConcept = 0;
$nEndConcept = 1;

if ($argc != 5)
{
	printf("Number of params [%s] is incorrect [5]\n", $argc);
	printf("Usage %s <TestConfigName> <ModelFeatureConfig> <StartConcept> <EndConcept>\n", $argv[0]);
	printf("Usage %s %s %s %s %s\n", $argv[0], $szTestConfigName, $szModelFeatureConfig, $nStartConcept, $nEndConcept);
	exit();
}

$szTestConfigName = $argv[1];
$szModelFeatureConfig = $argv[2];

$nStartConcept = intval($argv[3]);
$nEndConcept = intval($argv[4]);


$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]", $szStartTime, $argv[1], $argv[2], $argv[3], $argv[4]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

//$szRootExpDir  = $szRootDir
$szRootExpDir = sprintf("%s", $gszRootBenchmarkExpDir);

$szRootResultDir = sprintf("%s/result/keyframe-5/%s", $szRootExpDir, $szTestConfigName); // dir containing prediction result of RUNs
makeDir($szRootResultDir);

$szFPTestConfigFN = sprintf("%s/%s.cfg", $szRootResultDir, $szTestConfigName);

$arTestConfig = loadExperimentConfig($szFPTestConfigFN); // to get model_name & test_pat

$szModelConfigName = $arTestConfig['model_name'];

$szRootModelDir = sprintf("%s/model/keyframe-5/%s", $szRootExpDir, $szModelConfigName); // dir containing model used for prediction

$szFPModelConfigFN = sprintf("%s/%s.cfg", $szRootModelDir, $szModelConfigName);

$arModelConfig = loadExperimentConfig($szFPModelConfigFN);

$szRootModelConfigDir = sprintf("%s/config", $szRootModelDir); // dir containing configs of training params and feature_ext

$szFPModelFeatureConfigFN = sprintf("%s/%s.cfg", $szRootModelConfigDir, $szModelFeatureConfig);
$arModelFeatureConfig = loadExperimentConfig($szFPModelFeatureConfigFN);

// pick the [file name] as sysID
$szSysID = basename($szFPModelFeatureConfigFN, ".cfg");

$szFeatureExt =  $arModelFeatureConfig['feature_ext']; //"nsc.cCV_HSV.g5.q3.g_cm";
$gszFeatureDirFromSysID = $szFeatureExt; // used in loadFeatureTGz --> naming LocalTmpDir

//$szExpAnnDir = sprintf("%s/annotation/keyframe-5/%s", $szRootExpDir, $arModelConfig['ann_dir']); // annotation --> get concept list  --> BUG???
$szExpAnnDir = sprintf("%s/%s", $szRootExpDir, $arModelConfig['ann_dir']); // annotation --> get concept list

$szExpMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootExpDir); // --> get test-pat

$szExpResultRunDir = sprintf("%s/%s", $szRootResultDir, $szSysID); // dir containing prediction result (.res)

$arTestConfig['test_pat'] = "test2013-new";
$szTestVideoList = sprintf("%s.lst", $arTestConfig['test_pat']);

$szFPTestVideoListFN = sprintf("%s/%s", $szExpMetaDataDir, $szTestVideoList); 

$szFPConceptListFN = sprintf("%s/%s.lst", $szExpAnnDir, $arModelConfig['concept_list']);

$nNumConcepts = loadListFile($arConceptList, $szFPConceptListFN);

if ($nEndConceptID > $nNumConcepts)
{
    $nEndConceptID = $nNumConcepts;
}

$nMaxScores = $nMaxDocs = 100000;

for ($i = $nStartConcept; $i < $nEndConcept; $i ++)
{
    $szLine = $arConceptList[$i];
    // new format
    
    $arTmp = explode("#$#", $szLine);
    $szConceptName = trim($arTmp[1]);
      
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
    	$szRunSysID = sprintf("%s.%s", $szTestConfigName, $szSysID);
        $arOutput[] = sprintf("%s 0 %s %s %s %s", $szConceptName, $szShotID, $nRank, $fScore, $szRunSysID);
        $nRank ++;
    }
    
    saveDataFromMem2File($arOutput, $szFPRankListFN);
   
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
?>