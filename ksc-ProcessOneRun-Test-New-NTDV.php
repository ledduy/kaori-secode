<?php

/**
 * 		@file 	ksc-ProcessOneRun-Test-New.php
 * 		@brief 	Test One Run.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 02 Sep 2014.
 */

// Update Sep 01, 2014 --> revise for VSD2014
// 1. Remove unused options

/*** Example of config file --- mediaeval-vsd-2014.devel2013-new.cfg -----
model_name #$# mediaeval-vsd-2014.devel2013-new #$# runs for mediaeval-vsd-2014, using devel2013-new partition as devel
test_pat #$# test2013-new #$# test2013
*/


// ///////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";

// /////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////

// $szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;

$gnUseProbOutput = 1; // to normalize scores for fusion later

$gszFeatureFormat = "dvf";
$szFeatureFormat = $gszFeatureFormat;

// Update Jan 02, 2011
$nSkipExistingScores = 1; // *** CHANGED ***

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
$szFPLogFN = sprintf("%s.log", $szScriptBaseName); // *** CHANGED ***
                                                   // ////////////////// END FOR CUSTOMIZATION ////////////////////
                                                   
// //////////////////////////////// START ////////////////////////

$szTestConfigName = "mediaeval-vsd-2014.devel2013-new.test2013-new"; // association of devel-pat & test-pat 
$szModelFeatureConfig = "nsc.bow.dense6mul.rgbsift.Soft-1000.devel2011-new.L1norm1x1.shotMAX.R11"; // feature_ext + training params

$nStartConcept = 0;
$nEndConcept = 1;
$nStartPrg = 0;
$nEndPrg = 1;

if ($argc != 7)
{
    printf("Number of params [%s] is incorrect [7]\n", $argc);
    printf("Usage %s <TestConfigName> <ModelFeatureConfig> <StartConcept> <EndConcept> <StartPrg> <EndPrg>\n", $argv[0]);
    printf("Usage %s %s %s %s %s %s %s\n", $argv[0], $szTestConfigName, $szModelFeatureConfig, $nStartConcept, $nEndConcept, $nStartPrg, $nEndPrg);
    exit();
}

$szTestConfigName = $argv[1];
$szModelFeatureConfig = $argv[2];

$nStartConcept = intval($argv[3]);
$nEndConcept = intval($argv[4]);

$nStartPrg = intval($argv[5]);
$nEndPrg = intval($argv[6]);

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
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

$szExpAnnDir = sprintf("%s/annotation/keyframe-5/%s", $szRootExpDir, $arModelConfig['ann_dir']); // annotation --> get concept list  
//$szExpAnnDir = sprintf("%s/%s", $szRootExpDir, $arModelConfig['ann_dir']); // annotation --> get concept list

$szExpMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootExpDir); // --> get test-pat

$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootExpDir); // --> get feature

$gnPerformDataScaling = 1; // default --> OLD ONE
if (isset($arModelFeatureConfig['svm_scaling']))
{
    $gnPerformDataScaling = $arModelFeatureConfig['svm_scaling'];
}

$szTestVideoList = sprintf("%s.lst", $arTestConfig['test_pat']);

$szFPTestVideoListFN = sprintf("%s/%s", $szExpMetaDataDir, $szTestVideoList); 

$szFPConceptListFN = sprintf("%s/%s.lst", $szExpAnnDir, $arModelConfig['concept_list']);

$szExpModelRunDir = sprintf("%s/%s", $szRootModelDir, $szSysID); // dir containing model (.model)

$szExpResultRunDir = sprintf("%s/%s", $szRootResultDir, $szSysID); // dir containing prediction result (.res)

// new
$gnUseProbOutput = $arModelFeatureConfig['svm_train_use_prob_output'];

// new param for feature format - Dec 26
if (isset($arModelFeatureConfig['feature_format']))
{
    $gszFeatureFormat = $arModelFeatureConfig['feature_format'];
} else
{
    // bow --> svf
    if (strstr($szFeatureExt, "bow"))
    {
        $gszFeatureFormat = "svf";
    } else
    {
        $gszFeatureFormat = "dvf";
    }
}
$szFeatureFormat = $gszFeatureFormat;
printf("###Feature format: %s\n", $gszFeatureFormat);

$nNumConcepts = loadListFile($arConceptList, $szFPConceptListFN);

if ($nEndConcept > $nNumConcepts)
{
    $nEndConcept = $nNumConcepts;
}

for ($i = $nStartConcept; $i < $nEndConcept; $i ++)
{
    $szLine = $arConceptList[$i];
    // 0 #$# subviolence
    $arTmp = explode("#$#", $szLine);
    $szConceptName = trim($arTmp[1]);
    
    printf("###%d. Processing concept [%s] ...\n", $i, $szConceptName);
    $szExpModelRunConceptDir = sprintf("%s/%s", $szExpModelRunDir, $szConceptName);
    $szExpScoreRunConceptDir = sprintf("%s/%s", $szExpResultRunDir, $szConceptName);
    makeDir($szExpScoreRunConceptDir);
    
    $szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
    $szLocalTmpDir = $gszTmpDir; // defined in ksc-AppConfig

	$szTmpDir = sprintf("%s/%s/%s/%s/%s", $szLocalTmpDir, $szScriptBaseName, $szTestConfigName, $szSysID, $szConceptName);
	makeDir($szTmpDir);
    
    $nNumVideos = loadListFile($arVideoList, $szFPTestVideoListFN);
    
    $szTrainDataName = sprintf("%s.%s.svm", $szConceptName, $szFeatureExt);
    $nCount = 1;
    // foreach($arVideoList as $szLine)
    if ($nEndPrg > $nNumVideos)
    {
        $nEndPrg = $nNumVideos;
    }
    
    // !!! IMPORTANT --> move here (Sep 07) since it depends on $nEndPrg
    // tmp model dir used for predicting video programs from nStartPrg to nEndPrg
    $szTmpDir = sprintf("%s/model-%d-%d", $szTmpDir, $nStartPrg, $nEndPrg);
    makeDir($szTmpDir);
    
    $szTmpModelDir = $szTmpDir;
    
    // Modify Dec 02 --> Move the part of copying model file to tmp dir out of the loop
    $szTestDataDir = $szTmpDir; // store in tmp format
    
    $szModelDir = $szExpModelRunConceptDir;
    
    // copy .model to $szTmpModelDir
    $szFPModelFN = sprintf("%s/%s.model", $szModelDir, $szTrainDataName);
    
    $szFPModelTarFN = sprintf("%s/%s.model.tar.gz", $szModelDir, $szTrainDataName);
    if (! file_exists($szFPModelTarFN))
    {
        printf("Model file [%s] does not exist!\n", $szFPModelTarFN);
        continue;
    }
    
    $szCmdLine = sprintf("cp %s.tar.gz %s", $szFPModelFN, $szTmpModelDir);
    execSysCmd($szCmdLine);
    
    if ($gnPerformDataScaling)
    {
        
        // data for normalization
        $szFPModelNormDataFN = sprintf("%s/%s.normdat", $szModelDir, $szTrainDataName);
        $szCmdLine = sprintf("cp %s %s", $szFPModelNormDataFN, $szTmpModelDir);
        execSysCmd($szCmdLine);
    }
    
    // extract, i.e. decompress
    $szFPLocalModelFN = sprintf("%s/%s.model", $szTmpModelDir, $szTrainDataName);
    $szCmdLine = sprintf("tar -xvf %s.tar.gz -C %s", $szFPLocalModelFN, $szTmpModelDir);
    execSysCmd($szCmdLine);
    
    $szScoreDir = $szExpScoreRunConceptDir;
    
    $szFeatureDir = sprintf("%s/%s", $szRootFeatureDir, $szFeatureExt);
    
    // End Modify Dec 02
    
    for ($kk = $nStartPrg; $kk < $nEndPrg; $kk ++)
    {
        $szLine = $arVideoList[$kk];
        
        $arTmp = explode("#$#", $szLine);
        $szVideoID = trim($arTmp[0]);
        $szVideoPath = trim($arTmp[2]);
		
		//print_r($arTmp);exit();
        
        printf("###%d. Processing video [%s] ...\n", $nCount, $szVideoID);
        $nCount ++;
		
		printf("DEBUG1");
        
        $szTestDataName = sprintf("%s.%s.svm", $szVideoID, $szFeatureExt);
        
        // $szFeatureDir = sprintf("%s/baseline.%s", $szRootFeatureDir, $szFeatureExt);
        
        $szFPFeatureInputFN = sprintf("%s/%s/%s.%s", $szFeatureDir, $szVideoPath, $szVideoID, $szFeatureExt);
        
        $szFPFeatureOutputFN = sprintf("%s/%s", $szTestDataDir, $szTestDataName);
        
        // / !!! IMPORTANT
        $szFPScoreAnnFN = sprintf("%s/%s.res", $szScoreDir, $szTestDataName);
        
		printf("DEBUG2");
		
		$nSkipExistingScores = 0;
        if ($nSkipExistingScores)
        {
            // file size != 0
            if (file_exists($szFPScoreAnnFN) && filesize($szFPScoreAnnFN))
            {
                printf("File [%s] already existed!\n", $szFPScoreAnnFN);
                continue;
            }
        }
        // for convenience in processing score and ann association in prediction stage
        $szFPAnnOutputFN = sprintf("%s.ann", $szFPFeatureOutputFN);
        
        // if the feature file does not exist
        $szFPTarFeatureInputFNzz = sprintf("%s.tar.gz", $szFPFeatureInputFN);
        if (!file_exists($szFPTarFeatureInputFNzz))
        {
            printf("File not found [%s.tar.gz]\n", $szFPFeatureInputFN);

			continue;
        }
		
		//printf("DEBUG3: %s\n", $szFPTarFeatureInputFNzz);exit();
        if ($szFeatureFormat == "svf")
        {
            convertSvfData2LibSVMFormat($szTmpDir, $szFPFeatureInputFN, $szFPFeatureOutputFN, $szFPAnnOutputFN);
        } else // default
        {
            convertDvfData2LibSVMFormat($szTmpDir, $szFPFeatureInputFN, $szFPFeatureOutputFN, $szFPAnnOutputFN);
        }
        // doPredictionAssociation($szTrainDataName, $szTestDataName,
        // $szTestDataDir, $szModelDir, $szScoreDir);
        
        // .model is copied to local tmp dir
        doPredictionAssociation($szTrainDataName, $szTestDataName, $szTestDataDir, $szTmpModelDir, $szScoreDir, $gnUseProbOutput);
        
        // !!! IMPORTANT -- Updated Sep 07 - Vu found this!
        if ($szTestDataName != $szTrainDataName)
        {
            $szCmdLine = sprintf("rm -rf %s/*%s*", $szTmpDir, $szTestDataName);
            execSysCmd($szCmdLine);
        }
    }
    $szCmdLine = sprintf("rm -rf %s/*%s*", $szTmpDir, $szTrainDataName);
    //execSysCmd($szCmdLine);
}

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $szFinishTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// ///////////////////////////// FUNCTIONS ////////////////////////////////////
function convertDvfData2LibSVMFormat($szTmpDir, $szFPFeatureInputFN, $szFPFeatureOutputFN, $szFPAnnOutputFN)
{
    $nLabel = 1;
    
    // $arFeatureList = loadOneDvfFeatureFile($szFPFeatureInputFN, $nKFIndex=2);
    
    // $arFeatureList = loadOneTarGZDvfFeatureFile($szTmpDir, $szFPFeatureInputFN, $nKFIndex=2);
    
    // Modified Jul 18, 2012
    $arFeatureList = loadOneTarGZDvfFeatureFileNew($szTmpDir, $szFPFeatureInputFN, $nKFIndex = 2);
    
    if ($arFeatureList === false)
    {
        global $szFPLogFN;
        $arLogzz = array();
        $szErrorLog = sprintf("###SERIOUS ERR!!! Empty feature list [%s]!\n", $szFPFeatureInputFN);
        $arLogzz[] = $szErrorLog;
        saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
        
        // continue;
        exit(); // to rerun next time --> Update Jul 18
    }
    
    $nNumKeyFrames = sizeof($arFeatureList);
    $arKeyFrameList = array_keys($arFeatureList);
    for ($k = 0; $k < $nNumKeyFrames; $k ++)
    {
        $szKeyFrameID = $arKeyFrameList[$k];
        
        $arAnnFeatureList[$szKeyFrameID] = convertFeatureVector2LibSVMFormat($arFeatureList[$szKeyFrameID], $nLabel);
    }
    
    saveDataFromMem2File($arAnnFeatureList, $szFPFeatureOutputFN, "wt");
    
    $arAllKeyFrameList = array_keys($arAnnFeatureList);
    saveDataFromMem2File($arAllKeyFrameList, $szFPAnnOutputFN, "wt");
}

function convertSvfData2LibSVMFormat($szTmpDir, $szFPFeatureInputFN, $szFPFeatureOutputFN, $szFPAnnOutputFN)
{
    $nLabel = 1;
    
    // $arFeatureList = loadOneSvfFeatureFile($szFPFeatureInputFN, $nKFIndex=2);
    
    // $arFeatureList = loadOneTarGZSvfFeatureFile($szTmpDir, $szFPFeatureInputFN, $nKFIndex=2);
    
    // Modified Jul 18, 2012
    $arFeatureList = loadOneTarGZSvfFeatureFileNew($szTmpDir, $szFPFeatureInputFN, $nKFIndex = 2);
    
    if ($arFeatureList === false)
    {
        global $szFPLogFN;
        $arLogzz = array();
        $szErrorLog = sprintf("###SERIOUS ERR!!! Empty feature list [%s]!\n", $szFPFeatureInputFN);
        $arLogzz[] = $szErrorLog;
        saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
        
        // continue;
        exit(); // to rerun next time --> Update Jul 18
    }
    
    $nNumKeyFrames = sizeof($arFeatureList);
    $arKeyFrameList = array_keys($arFeatureList);
    for ($k = 0; $k < $nNumKeyFrames; $k ++)
    {
        $szKeyFrameID = $arKeyFrameList[$k];
        
        $arAnnFeatureList[$szKeyFrameID] = convertFeatureVector2LibSVMFormat($arFeatureList[$szKeyFrameID], $nLabel);
    }
    
    saveDataFromMem2File($arAnnFeatureList, $szFPFeatureOutputFN, "wt");
    
    $arAllKeyFrameList = array_keys($arAnnFeatureList);
    saveDataFromMem2File($arAllKeyFrameList, $szFPAnnOutputFN, "wt");
}

function doPredictionAssociation($szTrainDataName, $szTestDataName, $szTestDataDir, $szModelDir, $szScoreDir, $nUseProbOutput = 0)
{
    $szFPScoreOutputFN = runPredictClassifier($szTrainDataName, $szTestDataName, $szTestDataDir, $szModelDir, $szScoreDir, $nUseProbOutput);
    
    // associate ann and score
    $nNumSamples1 = loadListFile($arScoreList, $szFPScoreOutputFN);
    
    $szFPAnnFN = sprintf("%s/%s.ann", $szTestDataDir, $szTestDataName);
    $nNumSamples = loadListFile($arAnnList, $szFPAnnFN);
    
    $arOutputScoreList = array();
    
    // !!! IMPORTANT - special treatment for different -b option
    if ($nUseProbOutput == 0)
    {
        if ($nNumSamples1 != $nNumSamples)
        {
            printf("Error in result file!\n");
            exit();
        }
        
        for ($i = 0; $i < $nNumSamples; $i ++)
        {
            $szKey = $arAnnList[$i];
            $fScore = $arScoreList[$i];
            $arOutputScoreList[$szKey] = $fScore; // for fusion
        }
    } else
    {
        // one row different
        if ($nNumSamples1 != $nNumSamples + 1)
        {
            printf("Error in result file!\n");
            exit();
        }
        
        // labels 1 -1
        $szAnnRowz = $arScoreList[0];
        $arTmpzz = explode(" ", $szAnnRowz);
        $nPosIndex = 1; // default
        for ($i = 1; $i < sizeof($arTmpzz); $i ++)
        {
            if (intval($arTmpzz) == 1)
            {
                $nPosIndex = $i; // index for positive label
                break;
            }
        }
        
        for ($i = 0; $i < $nNumSamples; $i ++)
        {
            $szKey = $arAnnList[$i];
            
            $szScorez = $arScoreList[$i + 1]; // due to first row --> labels 1 -1
            $arTmpzz = explode(" ", $szScorez);
            $fScore = floatval($arTmpzz[$nPosIndex]);
            
            $arOutputScoreList[$szKey] = $fScore; // for fusion
        }
    }
    
    $szFPScoreAnnFN = sprintf("%s/%s.res", $szScoreDir, $szTestDataName);
    $arOutput = array();
    foreach ($arOutputScoreList as $szKey => $fScore)
    {
        $arOutput[] = sprintf("%s #$# %s", $szKey, $fScore);
    }
    saveDataFromMem2File($arOutput, $szFPScoreAnnFN);
}

// These functions are RE-USED in ksc-ProcessOneRun-Test-New.php
function loadOneTarGZSvfFeatureFileNew($szTmpDir, $szFPServerFeatureInputFN, $nKFIndex = 2)
{
    global $gszTmpDir;
    global $gszFeatureDirFromSysID;
    
    $nMaxWaitingCount = 30; // --> total = 10x60 ~ 10 minutes
    $nSleepCycle = 60; // 60 seconds
    
    $szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
    $szLocalTmpDir = $gszTmpDir; // defined in ksc-AppConfig
                                 
    // hlf-tv2012.nsc.bow.dense6mul.rgbsift.Soft-500-VL2.tv2012.devel-nistNew.norm3x1
    
    $szCentralTmpDir = sprintf("%s/%s/%s", $szLocalTmpDir, $szScriptBaseName, $gszFeatureDirFromSysID);
    makeDir($szCentralTmpDir);
    
    // one local dir to store ALL feature files, serve for ALL jobs
    $szLocalDirForAllFeatureFiles4AllJobs = $szCentralTmpDir;
    
    $szLocalName = basename($szFPServerFeatureInputFN);
    
    $szFPTarGzLocalFeatureInputFN = sprintf("%s/%s.tar.gz", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);
    
    $szFPLocalFeatureInputFN = sprintf("%s/%s", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);
    
    $szFPSemaphoreFN = sprintf("%s/%s.flag", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);
    
    global $szFPLogFN;
    if (! file_exists($szFPTarGzLocalFeatureInputFN))
    {
        // if there is no any copying job
        if (! file_exists($szFPSemaphoreFN) || ! filesize($szFPSemaphoreFN))
        {
            $arLogzz = array();
            $szStartTime = date("m.d.Y - H:i:s:u");
            $szErrorLog = sprintf("### INFO - [%s]!!! Start copying - [%s]\n", $szStartTime, $szFPServerFeatureInputFN);
            $arLogzz[] = $szErrorLog;
            saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
            
            // lock the file
            $arLogzz = array();
            $arLogzz[] = sprintf("Locking at: %s", date("m.d.Y - H:i:s"));
            saveDataFromMem2File($arLogzz, $szFPSemaphoreFN);
            
            $szCmdLine = sprintf("cp %s.tar.gz %s", $szFPServerFeatureInputFN, $szLocalDirForAllFeatureFiles4AllJobs);
            execSysCmd($szCmdLine);
            
            // unlock
            deleteFile($szFPSemaphoreFN);
            
            $arLogzz = array();
            $szEndTime = date("m.d.Y - H:i:s:u");
            $szErrorLog = sprintf("### INFO - [%s --> %s]!!! Finish copying - [%s]\n", $szStartTime, $szEndTime, $szFPServerFeatureInputFN);
            $arLogzz[] = $szErrorLog;
            saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
        }         

        // if there is another copying job --> waiting for the job finishes
        else
        {
            $nWaitingCount = 0;
            while ($nWaitingCount < $nMaxWaitingCount)
            {
                if (! file_exists($szFPSemaphoreFN)) // check the job finishes ??
                {
                    if ($nWaitingCount) // if having at least one time of waiting
                    {
                        $arLogzz = array();
                        $szEndTime = date("m.d.Y - H:i:s:u");
                        $szErrorLog = sprintf("### WARNING - CLEARED [%s --> %s] !!! Waiting - Branch 1 - [%d] for copying [%s]\n", $szStartTime, $szEndTime, $nWaitingCount, $szFPServerFeatureInputFN);
                        $arLogzz[] = $szErrorLog;
                        saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                    }
                    break;
                } else // wait
                {
                    printf(">>> Waiting - Branch 1 - %d\n", $nWaitingCount);
                    
                    $arLogzz = array();
                    
                    if ($nWaitingCount == 0)
                    {
                        $szStartTime = date("m.d.Y - H:i:s:u");
                    }
                    
                    $szErrorLog = sprintf("### WARNING [%s] !!! Waiting - Branch 1 - [%d] for copying [%s]\n", date("m.d.Y - H:i:s:u"), $nWaitingCount, $szFPServerFeatureInputFN);
                    $arLogzz[] = $szErrorLog;
                    saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                    
                    sleep($nSleepCycle);
                }
                $nWaitingCount ++;
                
                if ($nWaitingCount >= $nMaxWaitingCount) // no longer waiting --> RESET
                {
                    // delete for reset
                    deleteFile($szFPSemaphoreFN);
                    $szEndTime = date("m.d.Y - H:i:s:u");
                    $szErrorLog = sprintf("### SUPER WARNING [%s --> %s] !!! Delete flag file for RESET [%s]\n", $szStartTime, $szEndTime, $szFPServerFeatureInputFN);
                    $arLogzz[] = $szErrorLog;
                    saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                    
                    sleep($nSleepCycle);
                    break;
                }
            }
        }
    } else
    {
        // if there is another copying job --> waiting for the job finishes
        $nWaitingCount = 0;
        while ($nWaitingCount < $nMaxWaitingCount)
        {
            if (! file_exists($szFPSemaphoreFN)) //
            {
                if ($nWaitingCount) // if having at least one time of waiting
                {
                    $arLogzz = array();
                    $szEndTime = date("m.d.Y - H:i:s:u");
                    $szErrorLog = sprintf("### WARNING - CLEARED [%s --> %s] !!! Waiting - Branch 2 - [%d] for copying [%s]\n", $szStartTime, $szEndTime, $nWaitingCount, $szFPServerFeatureInputFN);
                    $arLogzz[] = $szErrorLog;
                    saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                }
                break;
            } else
            {
                printf(">>> Waiting - Branch 2 - %d\n", $nWaitingCount);
                
                $arLogzz = array();
                
                if ($nWaitingCount == 0)
                {
                    $szStartTime = date("m.d.Y - H:i:s:u");
                }
                
                $szErrorLog = sprintf("### WARNING [%s] !!! Waiting - Branch 2 - [%d] for copying [%s]\n", date("m.d.Y - H:i:s:u"), $nWaitingCount, $szFPServerFeatureInputFN);
                $arLogzz[] = $szErrorLog;
                saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                
                sleep($nSleepCycle);
            }
            
            $nWaitingCount ++;
            if ($nWaitingCount >= $nMaxWaitingCount)
            {
                // delete for reset
                deleteFile($szFPSemaphoreFN);
                $szEndTime = date("m.d.Y - H:i:s:u");
                $szErrorLog = sprintf("### SUPER WARNING [%s --> %s] !!! Delete flag file for RESET [%s]\n", $szStartTime, $szEndTime, $szFPServerFeatureInputFN);
                $arLogzz[] = $szErrorLog;
                saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                
                sleep($nSleepCycle);
                break;
            }
        }
    }
    
    return loadOneTarGZSvfFeatureFile($szTmpDir, $szFPLocalFeatureInputFN, $nKFIndex);
}

function loadOneTarGZDvfFeatureFileNew($szTmpDir, $szFPServerFeatureInputFN, $nKFIndex = 2)
{
    global $gszTmpDir;
    global $gszFeatureDirFromSysID;
    
    $nMaxWaitingCount = 30; // --> total = 10x60 ~ 10 minutes
    $nSleepCycle = 60; // 60 seconds
    
    $szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
    $szLocalTmpDir = $gszTmpDir; // defined in ksc-AppConfig
                                 
    // hlf-tv2012.nsc.bow.dense6mul.rgbsift.Soft-500-VL2.tv2012.devel-nistNew.norm3x1
    
    $szCentralTmpDir = sprintf("%s/%s/%s", $szLocalTmpDir, $szScriptBaseName, $gszFeatureDirFromSysID);
    makeDir($szCentralTmpDir);
    
    // one local dir to store ALL feature files, serve for ALL jobs
    $szLocalDirForAllFeatureFiles4AllJobs = $szCentralTmpDir;
    
    $szLocalName = basename($szFPServerFeatureInputFN);
    
    $szFPTarGzLocalFeatureInputFN = sprintf("%s/%s.tar.gz", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);
    
    $szFPLocalFeatureInputFN = sprintf("%s/%s", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);
    
    $szFPSemaphoreFN = sprintf("%s/%s.flag", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);
    
    global $szFPLogFN;
    if (! file_exists($szFPTarGzLocalFeatureInputFN))
    {
        // if there is no any copying job
        if (! file_exists($szFPSemaphoreFN) || ! filesize($szFPSemaphoreFN))
        {
            $arLogzz = array();
            $szStartTime = date("m.d.Y - H:i:s:u");
            $szErrorLog = sprintf("### INFO - [%s]!!! Start copying - [%s]\n", $szStartTime, $szFPServerFeatureInputFN);
            $arLogzz[] = $szErrorLog;
            saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
            
            // lock the file
            $arLogzz = array();
            $arLogzz[] = sprintf("Locking at: %s", date("m.d.Y - H:i:s"));
            saveDataFromMem2File($arLogzz, $szFPSemaphoreFN);
            
            $szCmdLine = sprintf("cp %s.tar.gz %s", $szFPServerFeatureInputFN, $szLocalDirForAllFeatureFiles4AllJobs);
            execSysCmd($szCmdLine);
            
            // unlock
            deleteFile($szFPSemaphoreFN);
            
            $arLogzz = array();
            $szEndTime = date("m.d.Y - H:i:s:u");
            $szErrorLog = sprintf("### INFO - [%s --> %s]!!! Finish copying - [%s]\n", $szStartTime, $szEndTime, $szFPServerFeatureInputFN);
            $arLogzz[] = $szErrorLog;
            saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
        }         

        // if there is another copying job --> waiting for the job finishes
        else
        {
            $nWaitingCount = 0;
            while ($nWaitingCount < $nMaxWaitingCount)
            {
                if (! file_exists($szFPSemaphoreFN)) // check the job finishes ??
                {
                    if ($nWaitingCount) // if having at least one time of waiting
                    {
                        $arLogzz = array();
                        $szEndTime = date("m.d.Y - H:i:s:u");
                        $szErrorLog = sprintf("### WARNING - CLEARED [%s --> %s] !!! Waiting - Branch 1 - [%d] for copying [%s]\n", $szStartTime, $szEndTime, $nWaitingCount, $szFPServerFeatureInputFN);
                        $arLogzz[] = $szErrorLog;
                        saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                    }
                    break;
                } else // wait
                {
                    printf(">>> Waiting - Branch 1 - %d\n", $nWaitingCount);
                    
                    $arLogzz = array();
                    
                    if ($nWaitingCount == 0)
                    {
                        $szStartTime = date("m.d.Y - H:i:s:u");
                    }
                    
                    $szErrorLog = sprintf("### WARNING [%s] !!! Waiting - Branch 1 - [%d] for copying [%s]\n", date("m.d.Y - H:i:s:u"), $nWaitingCount, $szFPServerFeatureInputFN);
                    $arLogzz[] = $szErrorLog;
                    saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                    
                    sleep($nSleepCycle);
                }
                $nWaitingCount ++;
                
                if ($nWaitingCount >= $nMaxWaitingCount) // no longer waiting --> RESET
                {
                    // delete for reset
                    deleteFile($szFPSemaphoreFN);
                    $szEndTime = date("m.d.Y - H:i:s:u");
                    $szErrorLog = sprintf("### SUPER WARNING [%s --> %s] !!! Delete flag file for RESET [%s]\n", $szStartTime, $szEndTime, $szFPServerFeatureInputFN);
                    $arLogzz[] = $szErrorLog;
                    saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                    
                    sleep($nSleepCycle);
                    break;
                }
            }
        }
    } else
    {
        // if there is another copying job --> waiting for the job finishes
        $nWaitingCount = 0;
        while ($nWaitingCount < $nMaxWaitingCount)
        {
            if (! file_exists($szFPSemaphoreFN)) //
            {
                if ($nWaitingCount) // if having at least one time of waiting
                {
                    $arLogzz = array();
                    $szEndTime = date("m.d.Y - H:i:s:u");
                    $szErrorLog = sprintf("### WARNING - CLEARED [%s --> %s] !!! Waiting - Branch 2 - [%d] for copying [%s]\n", $szStartTime, $szEndTime, $nWaitingCount, $szFPServerFeatureInputFN);
                    $arLogzz[] = $szErrorLog;
                    saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                }
                break;
            } else
            {
                printf(">>> Waiting - Branch 2 - %d\n", $nWaitingCount);
                
                $arLogzz = array();
                
                if ($nWaitingCount == 0)
                {
                    $szStartTime = date("m.d.Y - H:i:s:u");
                }
                
                $szErrorLog = sprintf("### WARNING [%s] !!! Waiting - Branch 2 - [%d] for copying [%s]\n", date("m.d.Y - H:i:s:u"), $nWaitingCount, $szFPServerFeatureInputFN);
                $arLogzz[] = $szErrorLog;
                saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                
                sleep($nSleepCycle);
            }
            
            $nWaitingCount ++;
            if ($nWaitingCount >= $nMaxWaitingCount)
            {
                // delete for reset
                deleteFile($szFPSemaphoreFN);
                $szEndTime = date("m.d.Y - H:i:s:u");
                $szErrorLog = sprintf("### SUPER WARNING [%s --> %s] !!! Delete flag file for RESET [%s]\n", $szStartTime, $szEndTime, $szFPServerFeatureInputFN);
                $arLogzz[] = $szErrorLog;
                saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
                
                sleep($nSleepCycle);
                break;
            }
        }
    }
    
    return loadOneTarGZDvfFeatureFile($szTmpDir, $szFPLocalFeatureInputFN, $nKFIndex);
}

?>