<?php

/**
 * 		@file 	ksc-BOW-ComputeAssignment-Sash.php
 * 		@brief 	Compute Assignment for KeyPoints Using Sash.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Jul 2013.
 */

// *** Update Jul 10
// --> Adding code for checking existing files to ensure the number of keyframes of one video = the number of lines in label.lst file
// --> Heavy because raw feature files must be loaded

// *** Update Jul 09
// Customize for tvsin2012
// --> IMPORTANT
// vlfeat 0.9.14 changes the output of vl_phow, from 3 to 4 values
// --> output values for one keypoints = 128 + 1
// --> Check FeatureOutputDir for load balancing

/**
 * *********** STEPS FOR BOW MODEL ***************
 * STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV10.php --> select keypoints from devel pat
 * STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV10.php --> do clustering using VLFEAT vl_kmeans, L2 distance
 * STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
 * ===> STEP 4: nsc-ComputeAssignmentSash-TV10/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
 * STEP 5: nsc-ComputeSoftBOW-Grid-TV10/-SGE.php --> compute soft assignment for grid image
 */

// Update Aug 07
// Adding log file

// Update Aug 04
// !!! NEW Aug04 2011 --> to prevent wrong assignment EVEN the codebook does not exist.
// checking CODEBOOK exists
/*
 * // Soft-500-VL2.tv2011.devel-nist.nsc.raw.phow8.sift.Centroids-c0-b0.sash $szFPSashCodeBookFN = sprintf("%s/%s-c0-b0.sash", $szSashCentroidDir, $szSashCentroidName); if(!file_exists($szFPSashCodeBookFN)) { printf("### Serious Error - Codebook not found [%s]\n", $szFPSashCodeBookFN); exit(); }
 */

// Update Aug 01
// Customize for tv2011

// /////////////////////////////////////////////////

// Adding param
// nKNNSize = 4;

// $fScaleFactor = 4.0; // increase accuracy

// ************** Update Feb 27 **************
// Global param to skip existing files,
// $gSkippExistingFiles = 1;

// ************* Update Feb 22 *************
// Be careful with empty keypoints (appearing when using PHOW and DENSESIFT) --> already checked in matlab code
// --> loose checking
// OLD: if($nNumKeyPoints+2 != sizeof($arRawList))
// NEW: if($nNumKeyPoints+2 < sizeof($arRawList))

// ************ Update Feb 21 ************
// "Soft-500-VL2z" --> FAILED because cluster centers should be int val if using vlfeat ikmeans (only work on int val)
// Back to $szTrialName = "Soft-500-VL2"; // --> V: VLFEAT, L2: L2 distance for clustering and word assignment

// ************ Update Feb 15 ************
// $szTrialName = "Soft-500-VL2z"; // = Soft-500-VL2, but cluster centers are float val, not intval returned by VLFEAT

// ************ Update Feb 15 ************
// Must be sure sashKeyPointTool use the same distance with VLFEAT (L2)
// $szTrialName = "Soft-500-VL2"; // --> V: VLFEAT, L2: L2 distance for clustering and word assignment

// ************ Update Feb 08 ************
// Modified sashKeyPointTool to have sashKeyPointTool-nsc-BOW. This exec file is used with nsc-BOW-ComputeAssignment-Sash-TV10.php
// $szSashKeypointToolApp = sprintf("/net/per900b/raid0/ledduy/kaori-core/cpp/sashKeyPointTool/sashKeyPointTool-nsc-BOW");

// fScaleFactor is set to 4 --> $fScaleFactor = 4.0; // increase accuracy (ZERO is invalid)
// Increasing this value increases query accuracy and execution time.
// Lowering the value is also possible!

// Change to Soft-500-VE (500d codebook, kmeans-VLFEAT, sashExactSearch

// ************ Update Feb 01 ***********
// Changed to Soft-500

// ************ Update Jan 22 ************
// Adding features harlap, heslap, haraff
// This script is invoked after running nsc-BOW-ComputeSashForCentroids-TV10.php

// ************ Update Jan 07 ************
// sash dir is data (not part-0.1)
// Each output of this exec file is one line and is added to the output file by a+t mode
// $szAnn = sprintf("%s %s %s", $szPrefixAnn, $szVideoID, $szKeyFrameID);
// $szCmdLine = sprintf("%s --findApproxNN %s %s %s dvf %s %s '%s' %s", $szSashKeypointToolApp,

// ************ Update Dec 22 ************
// Remove the part buiding sash
// Assume part-0.1 is the dir for sash

// ************ Update Oct 05 ************
// Modify path to run with raid6
// change dir from bow.codebook.tv2010 to bow.codebook.tv2005.devel (up one level)
// change path of sashKeyPointTool to bin/kaori-core

// ************ Update Sep 05 ************
// Modify to use tv2007 bow for tv2009 in INS task
// Each bow feature file --> has 1 associated .label.lst file
// sashKeyPointTool point to source rather than bin/kaori-core
// $szSashKeypointToolApp = sprintf("/net/per900b/raid0/ledduy/kaori-core/cpp/sashKeyPointTool/sashKeyPointTool", $szRootDir);

// codebook is copied to local dir for reducing the cost !!! IMPORTANT

// Aug 09
// using Sash for searching closest cluster

/*
 * STEP 1: Load tar data and untar STEP 2: Convert raw data 2 dvf format, save with name DataName-c0-b0.dvf STEP 3: Perform sashKeyPointTool -findNN --> output file is write in a+t mode --> szAnn must have all info, sashKeyPointTool only add number of keypoints
 */

// ///////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";
require_once "kaori-lib/kl-GreedyRSCClusteringTool.php";

// ////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

// $szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

$gSkippExistingFiles = 1;
$nKNNSize = 4; // default
               
// $szFPLogFN = "ksc-BOW-ComputeAssignmentSash.log";

$szPatName = "devel-nistNew";
$szFeatureExtInput = "nsc.raw.dense6mul.rgbsift";
$szTargetPatName = "test.iacc.2.ANew";
$nSamplingInterval = 1;
$nStartID = 0;
$nEndID = 1;

// ////////////////// END FOR CUSTOMIZATION ////////////////////

// /////////////////////////// MAIN ////////////////////////////////

if ($argc != 7)
{
    printf("Usage: %s <SourcePatName> <InputFeatureExt> <TargetPatName> <SamplingInterval> <Start> <End>\n", $argv[0]);
    printf("Usage: %s %s %s %s %s %s %s\n", $argv[0], $szPatName, $szFeatureExtInput, $szTargetPatName, $nSamplingInterval, $nStartID, $nEndID);
    exit();
}

$szPatName = $argv[1]; // tv2007.devel
$szFeatureExtInput = $argv[2]; // nsc.raw.harhes.sift
$szTargetPatName = $argv[3]; // "tv2007.devel";
$nSamplingInterval = $argv[4]; // 1
$nStartID = intval($argv[5]); // 0
$nEndID = intval($argv[6]); // 1

$szFPLogFN = sprintf("ksc-BOW-ComputeAssignmentSash-%s.log", $szFeatureExtInput); // *** CHANGED ***
                                                                                  // $szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
                                                                                  // *** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szFeatureExtInput); // *** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);

// Update Nov 25, 2011
$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
$szLocalTmpDir = $gszTmpDir; // defined in ksc-AppConfig
$szTmpDir = sprintf("%s/%s/bow.codebook.%s.%s/%s/%s", $szLocalTmpDir, $szScriptBaseName, $szTrialName, $szPatName, $szFeatureExtInput, $szTargetPatName);
makeDir($szTmpDir);

// !!! IMPORTANT
// source sash data
$szRootCentroidDir1 = sprintf("%s/bow.codebook.%s.%s/%s", $szRootFeatureDir, $szTrialName, $szPatName, $szFeatureExtInput);
$szSashCentroidDir1 = $szRootCentroidDir1;

// copy sash data to /local/ledduy
$szRootCentroidDir = sprintf("%s", $szTmpDir);
$szSashCentroidDir = sprintf("%s/sash-%d-%d", $szRootCentroidDir, $nStartID, $nEndID);
makeDir($szSashCentroidDir);

// / !!! IMPORTANT
$szCmdLine = sprintf("cp %s/data/*Centroids* %s", $szSashCentroidDir1, $szSashCentroidDir);
execSysCmd($szCmdLine);

$szSashCentroidName = sprintf("%s.%s.%s.Centroids", $szTrialName, $szPatName, $szFeatureExtInput);

// !!! NEW Aug04 2011 --> to prevent wrong assignment EVEN the codebook does not exist.
// checking CODEBOOK exists
// Soft-500-VL2.tv2011.devel-nist.nsc.raw.phow8.sift.Centroids-c0-b0.sash
$szFPSashCodeBookFN = sprintf("%s/%s-c0-b0.sash", $szSashCentroidDir, $szSashCentroidName);

if (! file_exists($szFPSashCodeBookFN))
{
    printf("### Serious Error - Codebook not found [%s]\n", $szFPSashCodeBookFN);
    exit();
}

// $szTargetPatName = "tv2007.devel";
$szFPVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szTargetPatName);

$szFeatureExtOutput = sprintf("%s.%s.%s", str_replace("raw", "bow", $szFeatureExtInput), $szTrialName, $szPatName);

$szPrefixAnn = $szTargetPatName;

// $nStartID = 0;
// $nEndID = 1;

$szRootFeatureOutputDir = $szRootFeatureInputDir = $szRootFeatureDir;

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

computeBoWFeatureForOnePat($szTmpDir, $szRootFeatureOutputDir, $szRootFeatureInputDir, $szSashCentroidDir, $szSashCentroidName, $szRootMetaDataDir, $szFPVideoListFN, $szFeatureExtOutput, $szFeatureExtInput, $nSamplingInterval, $szPrefixAnn, $nStartID, $nEndID);

$szCmdLine = sprintf("rm -rf %s", $szSashCentroidDir);
execSysCmd($szCmdLine);

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $szFinishTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// ////////////////////////// FUNCTIONS //////////////////////////
function parseOneRawSIFTFile2Dvf($szFPSIFTDataDvfFN, $szFPSIFTDataFN)
{
    loadListFile($arRawList, $szFPSIFTDataFN);
    
    $nCount = 0;
    // print_r($arRawList);
    $arOutput = array();
    $arOutput[0] = sprintf("%% %s", $szFPSIFTDataFN);
    $arOutput[1] = sizeof($arRawList);
    foreach ($arRawList as $szLine)
    {
        // printf("%s\n", $szLine);
        // first row - numDims 128
        if ($nCount == 0)
        {
            $nNumDims = intval($szLine);
            $nCount ++;
            continue;
        }
        
        // second row - numKPs
        if ($nCount == 1)
        {
            $nNumKeyPoints = intval($szLine);
            
            // if($nNumKeyPoints+2 != sizeof($arRawList))
            if ($nNumKeyPoints + 2 < sizeof($arRawList))
            {
                printf("Error in SIFT data file\n");
                exit();
            }
            
            $nCount ++;
            continue;
        }
        
        $arTmp = explode(" ", $szLine);
        // 5 first values - x y a b c
        if (sizeof($arTmp) != $nNumDims + 5)
        {
            printf("Error in SIFT data file\n");
            exit();
        }
        
        $arFeatureTmp = array();
        for ($i = 0; $i < $nNumDims; $i ++)
        {
            $nIndex = $i + 5;
            
            $arFeatureTmp[] = floatval($arTmp[$nIndex]);
        }
        
        $arOutput[] = convertFeatureVector2DvfFormat($arFeatureTmp);
        $nCount ++;
    }
    
    // !IMPORTANT --> Bug of prev version $arOutput[1] = $nCount
    $arOutput[1] = $nCount - 2; // remove 2 lines for numDims and numKPs
    
    saveDataFromMem2File($arOutput, $szFPSIFTDataDvfFN);
}

// One video program consists of a list of keyframe images
// no space in $szPrefixAnn
// $nKeyFrameSamplingInterval --> used to choose a subset of keyframes per shot (naive way)
function computeBoWFeatureForOneVideoProgram($szLocalDir, $szFPKeyFrameListFN, $szSashCentroidDir, $szSashCentroidName, $szRootFeatureOutputDir, $szRootFeatureInputDir, $szVideoPath, $szVideoID, $szFeatureExtInput, $szFeatureExtOutput, $szPrefixAnn, $nSamplingInterval)
{
    // $fScaleFactor = 1.0; // used for approx search with Sash, 0 is exact search, the higher the more precise
    $fScaleFactor = 4.0; // increase accuracy
    global $nKNNSize;
    $nKNNSize = 4;
    
    // $szSashKeypointToolApp = sprintf("/net/per900b/raid0/ledduy/kaori-core/cpp/sashKeyPointTool/sashKeyPointTool-nsc-BOW-L2");
    $szSashKeypointToolApp = sprintf("sashKeyPointTool/sashKeyPointTool-nsc-BOW-L2");
    
    // $szOutputDir = sprintf("%s/baseline.%s/%s", $szRootFeatureOutputDir, $szFeatureExtOutput, $szVideoPath);
    $szOutputDir = sprintf("%s/%s/%s", $szRootFeatureOutputDir, $szFeatureExtOutput, $szVideoPath);
    makeDir($szOutputDir);
    $szFPOutputFN = sprintf("%s/%s.%s", $szOutputDir, $szVideoID, $szFeatureExtOutput);
    
    // TRECVID2007_204.nsc.bow.harhes.sift.Soft-500-VL2.tv2007.devel-nist + .label.lst.tar.gz
    $szFPTarOutputFN = sprintf("%s.label.lst.tar.gz", $szFPOutputFN);
    
    global $gSkippExistingFiles;
    if ($gSkippExistingFiles)
    {
        if (file_exists($szFPTarOutputFN) && filesize($szFPTarOutputFN))
        {
            // Adding Jul 14, 2012
            // Checking whether enough KF
            
            // get number of keyframes
            $nNumKeyFrames = loadListFile($arKeyFrameList, $szFPKeyFrameListFN);
            // get number of lines (each line <--> one keyframe)
            
            $szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTarOutputFN, $szLocalDir);
            execSysCmd($szCmdLine);
            
            $szBaseName = basename($szFPOutputFN);
            $szFPLocalLabelOutputFN = sprintf("%s/%s.label.lst", $szLocalDir, $szBaseName);
            
            $nNumLines = loadListFile($arCountLineList, $szFPLocalLabelOutputFN);
            deleteFile($szFPLocalLabelOutputFN);
            
            if ($nNumLines == $nNumKeyFrames + 1) // first row --> annotation
            {
                printf("###File [%s] found. Skipping ... \n", $szFPTarOutputFN);
                
                $szLog = sprintf("###WARNING!!! %s. File [%s] found. Checked OK --> Skipping ... \n", date("m.d.Y - H:i:s"), $szFPTarOutputFN);
                
                $arLogListz = array();
                $arLogListz[] = $szLog;
                
                global $szFPLogFN;
                saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t");
                
                return;
            } else
            {
                $szLog = sprintf("###WARNING!!! %s. File [%s] found. But not enough KF (Jul 14) [%s Lines - %s KF], re-checking ... \n", date("m.d.Y - H:i:s"), $szFPTarOutputFN, $nNumLines - 1, $nNumKeyFrames);
                
                $arLogListz = array();
                $arLogListz[] = $szLog;
                
                global $szFPLogFN;
                saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t");
            }
        }
    }
    
    $nNumKeyFrames = loadListFile($arKeyFrameList, $szFPKeyFrameListFN);
    
    $arOutput = array();
    $arOutput[] = sprintf("%% BoW-tf feature, %d keyframes", $nNumKeyFrames);
    
    // reset the file
    $szBaseName = basename($szFPOutputFN);
    $szFPLocalOutputFN = sprintf("%s/%s", $szLocalDir, $szBaseName);
    saveDataFromMem2File($arOutput, $szFPLocalOutputFN, "wt");
    
    // for label list
    $szFPLocalLabelOutputFN = sprintf("%s/%s.label.lst", $szLocalDir, $szBaseName);
    saveDataFromMem2File($arOutput, $szFPLocalLabelOutputFN, "wt");
    
    for ($i = 0; $i < $nNumKeyFrames; $i += $nSamplingInterval)
    {
        $szKeyFrameID = $arKeyFrameList[$i];
        
        // $szInputDir = sprintf("%s/baseline.%s/%s/%s",
        // $szRootFeatureInputDir, $szFeatureExtInput, $szVideoPath, $szVideoID);
        
        $szInputDir = sprintf("%s/%s/%s/%s", $szRootFeatureInputDir, $szFeatureExtInput, $szVideoPath, $szVideoID);
        
        $szCoreName = sprintf("%s.%s", $szKeyFrameID, $szFeatureExtInput);
        $szFPTarKeyPointFN = sprintf("%s/%s.tar.gz", $szInputDir, $szCoreName);
        
        if (! file_exists($szFPTarKeyPointFN) || ! filesize($szFPTarKeyPointFN))
        {
            printf("File [%s] not found \n", $szFPTarKeyPointFN);
            
            $szLog = sprintf("###WARNING!!! %s. File raw feature [%s] not found. \n", date("m.d.Y - H:i:s"), $szFPTarKeyPointFN);
            
            $arLogListz = array();
            $arLogListz[] = $szLog;
            
            global $szFPLogFN;
            saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t");
            
            deleteFile($szFPTarKeyPointFN); // in the case file size = zero
                                            
            // continue;
            
            return; // skip the current video program
        }
        
        $szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTarKeyPointFN, $szLocalDir);
        execSysCmd($szCmdLine);
        
        $szFPSIFTDataFN = sprintf("%s/%s", $szLocalDir, $szCoreName);
        $szFPSIFTDataDvfFN = sprintf("%s/%s-c0-b0.dvf", $szLocalDir, $szCoreName);
        
        parseOneRawSIFTFile2Dvf($szFPSIFTDataDvfFN, $szFPSIFTDataFN);
        
        // "Usage: %s --findApproxNN <FPOutputFN> <SashInputDir> <SashPrefixName> <FeatureExt> <QueryInputDir> <QueryPrefixName> <szAnn> <fScalFactor> <nNumNN>\n",
        
        // !IMPORTANT --> using new version findApproxNN
        // Each output of this exec file is one line and is added to the output file by a+t mode
        $szAnn = sprintf("%s %s %s", $szPrefixAnn, $szVideoID, $szKeyFrameID);
        $szCmdLine = sprintf("%s --findApproxNN %s %s %s dvf %s %s '%s' %s %s", $szSashKeypointToolApp, $szFPLocalOutputFN, $szSashCentroidDir, $szSashCentroidName, $szLocalDir, $szCoreName, $szAnn, $fScaleFactor, $nKNNSize);
        execSysCmd($szCmdLine);
        
        deleteFile($szFPSIFTDataFN);
        deleteFile($szFPSIFTDataDvfFN);
    }
    
    // compress output file --> new version does not generate histogram
    // $szFPTarLocalOutputFN = sprintf("%s.tar.gz", $szFPLocalOutputFN);
    // $szCmdLine = sprintf("tar -cvzf %s -C %s %s", $szFPTarLocalOutputFN, $szLocalDir, $szBaseName);
    // execSysCmd($szCmdLine);
    // move to dest
    // $szCmdLine = sprintf("mv -f %s %s.tar.gz", $szFPTarLocalOutputFN, $szFPOutputFN);
    // execSysCmd($szCmdLine);
    
    $szFPTarLocalLabelOutputFN = sprintf("%s.tar.gz", $szFPLocalLabelOutputFN);
    $szCmdLine = sprintf("tar -cvzf %s -C %s %s.label.lst", $szFPTarLocalLabelOutputFN, $szLocalDir, $szBaseName);
    execSysCmd($szCmdLine);
    
    $szCmdLine = sprintf("mv -f %s %s.label.lst.tar.gz", $szFPTarLocalLabelOutputFN, $szFPOutputFN);
    execSysCmd($szCmdLine);
}

// One video program consists of a list of keyframe images
function computeBoWFeatureForOnePat($szLocalDir, $szRootFeatureOutputDir, $szRootFeatureInputDir, $szSashCentroidDir, $szSashCentroidName, $szRootMetaDataDir, $szFPVideoListFN, $szFeatureExtOutput, $szFeatureExtInput, $nSamplingInterval = 10, $szPrefixAnn = "", $nStartID = -1, $nEndID = -1)
{
    $arVideoPathList = array();
    loadListFile($arRawList, $szFPVideoListFN);
    
    foreach ($arRawList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        $szVideoID = trim($arTmp[0]);
        $szVideoPath = trim($arTmp[2]);
        $arVideoPathList[$szVideoID] = $szVideoPath;
    }
    
    $nNumVideos = sizeof($arVideoPathList);
    if ($nStartID < 0)
    {
        $nStartID = 0;
    }
    
    if ($nEndID < 0 || $nEndID > $nNumVideos)
    {
        $nEndID = $nNumVideos;
    }
    
    $arVideoList = array_keys($arVideoPathList);
    for ($i = $nStartID; $i < $nEndID; $i ++)
    {
        $szVideoID = $arVideoList[$i];
        printf("###%d. Processing video [%s] ...\n", $i, $szVideoID);
        
        $szVideoPath = $arVideoPathList[$szVideoID];
        
        $szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootMetaDataDir, $szVideoPath, $szVideoID);
        
        computeBoWFeatureForOneVideoProgram($szLocalDir, $szFPKeyFrameListFN, $szSashCentroidDir, $szSashCentroidName, $szRootFeatureOutputDir, $szRootFeatureInputDir, $szVideoPath, $szVideoID, $szFeatureExtInput, $szFeatureExtOutput, $szPrefixAnn, $nSamplingInterval);
    }
}

?>