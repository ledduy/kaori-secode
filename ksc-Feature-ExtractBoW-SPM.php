<?php

/**
 * 		@file 	ksc-Feature-ExtractBOW-SPM.php
* 		@brief 	Extract BoW Features - AllInOne Step.
*		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
*
* 		Copyright (C) 2010-2013 Duy-Dinh Le.
* 		All rights reserved.
* 		Last update	: 30 Aug 2013.
*/

// !!! IMPORTATNT !!!
// $szSashKeypointToolApp = sprintf("sashKeyPointTool/sashKeyPointTool-nsc-BOW-L2");

// *** Update Jul 16, 2012
// --> Adding WARNING for zero file size
// --> Adding code for checking existing files to ensure the number of keyframes of one video = the number of lines in label.lst file

// When running on the grid engine, take a lot of time for reading data (keypoint assignment)
// --> Compute features for ALL grids ONCE to reduce processing time (mainly reading & transferring data)
// **** Processing Time ********
// --> Load label list file for one video program --> each line --> one keyframe
// --> Load raw keypoint file for each keyframe to get the location of each keypoint

// !!! IMPORTANT !!!!
// --> do not use FrameWidth and FrameHeight as params
// .prgx --> has information on width and height, computed by nsc-BOW-GetKeyFrameSize
// $szFPKeyFrameListFN = sprintf("%s/%s/%s.prgx", $szRootMetaDataDir, $szVideoPath, $szVideoID);

// Update Aug 07
// Adding log file

// ///// THIS PART MUST BE SYNC WITH ComputeSoftBOW
// $gnHavingResized = 1;
// $gnMaxFrameWidth = 350;
// $gnMaxFrameHeight = 350;
// $gszResizeOption = sprintf("-resize '%sx%s>'", $gnMaxFrameWidth, $gnMaxFrameHeight); // to ensure 352 is the width after shrinking
// ////////////////////////////////////////////////

// //////////////////////////////////////////////////////
/*
 * // Format of raw SIFT feature file // First row: NumDims // Second row: NumKeyPoints // Third row ...: x y -1 -1 -1 V1 V2 ... V_NumDims // x, y is used for SoftBOW-Grid --> location of the keypoint
 */

/**
 * *********** STEPS FOR BOW MODEL ***************
 * STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV10.php --> select keypoints from devel pat
 * STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV10.php --> do clustering using VLFEAT vl_kmeans, L2 distance
 * STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
 * STEP 4: nsc-ComputeAssignmentSash-TV10/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
 * STEP 5: nsc-ComputeSoftBOW-Grid-TV10/-SGE.php --> compute soft assignment for grid image
 */
// MaxCodeBookSize = 600

// ************** Update Feb 27 **************
// Global param to skip existing files,
// $gSkippExistingFiles = 1;

// **************** Update Feb 21 ****************
// $nMaxCodeBookSize = 600; (for using 500-d codebook of 1x1 grid)

// **************** Update Feb 02 ****************
// changed to Soft-500
// to ensure starting label > 0 --> $nLabelPlus = $nLabel+1;

// **************** Update Jan 21 - IMPORTANT ****************
// Change the value sim(j,t) in soft weight, instead of raw Euclidean distance returned by SashAssignment, using normalized value
// fNorm = exp(-gamma*D), where D is the raw distance (i.e. Euclidean distance) return by SashAssignment, gamma = 0.0625 (1/16)
// VIREO used cosine distance, whose value ranges from [0, 1], the higher score, the closer
// the higher D (i.e. the further in Euclidean distance), the smaller fNorm

// Change the feature ext to norm1x3, norm2x2
// $szOutputFeatureExt = sprintf("%s.norm%dx%d", $szInputFeatureExt, $nNumRows, $nNumCols);

// Fixed bugs in rect - found when running on 1x3 grid

// **************** Update Jan 17 ****************
// Copied from nsc-BOW-ComputeSoftBOW-TV10.php
// Load both files: labels (assigned for each keypoint) and raw (having coord info)
// This code requires correct frame size. If incorrect one is found, the rect is assigned to zero-index

/*
 * General description 1. For each image, extract raw SIFT, for example, nsc.raw.harhes.sift 2. Compute codebook from raw feature, for example, SimpleSoft-1.tv2010.devel-nist (tv2010.devel-nist is used to select keypoints for building the codebook) 3. Compute label assignment, for example, nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist 4. Compute soft-BOW using the labels assignment in nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist (1x1 grid), output is the same name (i.e. nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist) 5. Compute soft-BOW with grid using the label assignment in nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist, output is in nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist.mxn (m is #row, n is #cols)
 */

// //////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

// ////////////////// THIS PART FOR CUSTOMIZATION ////////////////////
$szSashKeypointToolApp = sprintf("sashKeyPointTool/sashKeyPointTool-nsc-BOW-L2");

// $szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

$gSkippExistingFiles = 1;
$gKeyFrameImgExt = "jpg";

// ////////////////// END FOR CUSTOMIZATION ////////////////////

// /////////////////////////// MAIN ////////////////////////////////

$szPatName = "devel2012";
$szInputRawFeatureExt = "nsc.raw.harlap6mul.sift";
$szTargetPatName = "test2012"; // or devel2012
$nStartID = 0; // 0
$nEndID = 1; // 1

if ($argc != 6)
{
    printf("Usage: %s <SrcPatName> <TargetPatName> <RawFeatureExt> <Start> <End>\n", $argv[0]);
    printf("Usage: %s %s %s %s %s %s\n", $argv[0], $szPatName, $szTargetPatName, $szInputRawFeatureExt, $nStartID, $nEndID);
    exit();
}

$szPatName = $argv[1]; // tv2007.devel
$szTargetPatName = $argv[2];
$szInputRawFeatureExt = $argv[3];
$nStartID = intval($argv[4]); // 0
$nEndID = intval($argv[5]); // 1

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
$szFPLogFN = sprintf("%s-%s.log", $szScriptBaseName, $szInputRawFeatureExt); // *** CHANGED ***
                                                                             
// *** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szInputRawFeatureExt); // *** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);
$szRootFeatureOutputDir = $szRootFeatureDir;

$szLocalTmpDir = $gszTmpDir; // defined in ksc-AppConfig

$szTmpDir = sprintf("%s/%s/%s/%s-%s-%d-%d", $szLocalTmpDir, $szScriptBaseName, $szPatName, $szTargetPatName, $szInputRawFeatureExt, $nStartID, $nEndID);
makeDir($szTmpDir);

// !!! IMPORTANT
// source sash data
$szRootCentroidDir1 = sprintf("%s/bow.codebook.%s.%s/%s", $szRootFeatureDir, $szTrialName, $szPatName, $szInputRawFeatureExt);
$szSashCentroidDir1 = $szRootCentroidDir1;

// copy sash data to /local/ledduy
$szRootCentroidDir = sprintf("%s", $szTmpDir);
$szSashCentroidDir = sprintf("%s/sash-%d-%d", $szRootCentroidDir, $nStartID, $nEndID);
makeDir($szSashCentroidDir);

// / !!! IMPORTANT
$szCmdLine = sprintf("cp %s/data/*Centroids* %s", $szSashCentroidDir1, $szSashCentroidDir);
execSysCmd($szCmdLine);

$szSashCentroidName = sprintf("%s.%s.%s.Centroids", $szTrialName, $szPatName, $szInputRawFeatureExt);

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

$szPrefixAnn = $szTargetPatName;

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

$szBOWFeatureExt = sprintf("%s.%s.%s", str_replace("raw", "bow", $szInputRawFeatureExt), $szTrialName, $szPatName);

// $szFeatureConfigParam = "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor sift";
$szFeatureConfigParam = "--detector harrislaplace --ds_scales 1.2+2.0 --descriptor sift";

computeSoftBOWHistogramWithGridForOnePat($szTmpDir, $szFeatureConfigParam, $szRootFeatureOutputDir, $szRootKeyFrameDir, $szRootMetaDataDir, $szSashCentroidDir, $szSashCentroidName, $szPrefixAnn, $szTargetPatName, $szInputRawFeatureExt, $szBOWFeatureExt, $nMaxCodeBookSize, $nStartID, $nEndID);

// clean up
$szCmdLine = sprintf("rm -rf %s", $szSashCentroidDir);
execSysCmd($szCmdLine);

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $szFinishTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// ///////////////////////////// FUNCTIONS //////////////////////////////
function computeSoftBOWHistogramWithGridForOnePat($szLocalDir, $szFeatureConfigParam, $szRootFeatureOutputDir, $szRootKeyFrameDir, $szRootMetaDataDir, $szSashCentroidDir, $szSashCentroidName, $szPrefixAnn, $szPatName, $szInputRawFeatureExt, $szBOWFeatureExt, $nMaxCodeBookSize = 2000, $nStartVideoID = -1, $nEndVideoID = -1)
{
    $szFPVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szPatName);
    $arVideoPathList = array();
    if (! file_exists($szFPVideoListFN))
    {
        printf("File [%s]  not found\n", $szFPVideoListFN);
        exit();
    }
    loadListFile($arRawList, $szFPVideoListFN);
    
    foreach ($arRawList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        $szVideoID = trim($arTmp[0]);
        $szVideoPath = trim($arTmp[2]);
        $arVideoPathList[$szVideoID] = $szVideoPath;
    }
    
    $nNumVideos = sizeof($arVideoPathList);
    if ($nStartVideoID < 0)
    {
        $nStartVideoID = 0;
    }
    
    if ($nEndVideoID < 0 || $nEndVideoID > $nNumVideos)
    {
        $nEndVideoID = $nNumVideos;
    }
    
    $arVideoList = array_keys($arVideoPathList);
    
    for ($i = $nStartVideoID; $i < $nEndVideoID; $i ++)
    {
        $szVideoID = $arVideoList[$i];
        printf("###%d. Processing video [%s] ...\n", $i, $szVideoID);
        
        $szVideoPath = $arVideoPathList[$szVideoID];
        
        // !!! IMPORTANT !!!
        $szFPKeyFrameListFN = sprintf("%s/%s/%s.prgx", $szRootMetaDataDir, $szVideoPath, $szVideoID);
        
        // specific for one video program
        $szLocalKeyFrameDir = sprintf("%s/%s/keyframe/%s/%s", $szLocalDir, $szBOWFeatureExt, $szVideoPath, $szVideoID);
        makeDir($szLocalKeyFrameDir);
        
        $szLocalFeatureDir = sprintf("%s/%s/feature/%s/%s", $szLocalDir, $szBOWFeatureExt, $szVideoPath, $szVideoID);
        makeDir($szLocalFeatureDir);
        
        extractRawSIFTFeatureForOneVideoProgram($szLocalKeyFrameDir, $szLocalFeatureDir, $szRootKeyFrameDir, $szRootMetaDataDir, $szPatName, $szVideoPath, $szVideoID, $szInputRawFeatureExt, $szFeatureConfigParam);
        
        // exit("Checkpoint 1 - Raw feature extraction\n");
        // TO BE UPDATE
        computeSoftBOWHistogramWithGridForOneVideoProgram($szLocalFeatureDir, $szRootFeatureOutputDir, $szFPKeyFrameListFN, $szSashCentroidDir, $szSashCentroidName, $szPrefixAnn, $szVideoPath, $szVideoID, $szInputRawFeatureExt, $szBOWFeatureExt, $nMaxCodeBookSize);
        
        // clean up
        $szCmdLine = sprintf("rm -rf %s", $szLocalKeyFrameDir);
        execSysCmd($szCmdLine);
        
        $szCmdLine = sprintf("rm -rf %s", $szLocalFeatureDir);
        execSysCmd($szCmdLine);
    }
}

function computeSoftBOWHistogramWithGridForOneVideoProgram($szLocalFeatureDir, $szRootFeatureOutputDir, $szFPKeyFrameListFN, $szSashCentroidDir, $szSashCentroidName, $szPrefixAnn, $szVideoPath, $szVideoID, $szInputRawFeatureExt, $szBOWFeatureExt, $nMaxCodeBookSize = 2000)
{
    $arGridList = array(
        // 4 => 4,
        3 => 1,
        // 2 => 2,
        // 1 => 3,
        1 => 1
    );
    
    $szCoreName = sprintf("%s.%s", $szVideoID, $szBOWFeatureExt);
    
    $szFPLabelNoExtFN = sprintf("%s/%s", $szLocalFeatureDir, $szCoreName);
    
    // sash tool auto add .label.lst to $szFPLocalOutputFN --> i.e = $szFPLocalInputFN
    computeAssignmentSash($szLocalFeatureDir, $szFPLabelNoExtFN, $szFPKeyFrameListFN, $szSashCentroidDir, $szSashCentroidName, $szPrefixAnn, $szVideoPath, $szVideoID, $szInputRawFeatureExt);
    
    // this file is share by grid config
    $szFPLocalInputFN = sprintf("%s.label.lst", $szFPLabelNoExtFN);
    
    foreach ($arGridList as $nNumRows => $nNumCols)
    {
        // adding grid info (mxn)
        // Changed 20 Jan --> 1x3 --> norm1x3
        $szOutputFeatureExt = sprintf("%s.norm%dx%d", $szBOWFeatureExt, $nNumRows, $nNumCols);
        
        $szOutputDir = sprintf("%s/%s/%s", $szRootFeatureOutputDir, $szOutputFeatureExt, $szVideoPath);
        makeDir($szOutputDir);
        
        $szOutputCoreName = sprintf("%s.%s", $szVideoID, $szOutputFeatureExt);
        /*
         * global $gSkippExistingFiles; if ($gSkippExistingFiles) { $szFPOutputFN = sprintf("%s/%s.tar.gz", $szOutputDir, $szOutputCoreName); if (file_exists($szFPOutputFN) && filesize($szFPOutputFN)) { // get number of keyframes $nNumKeyFrames = loadListFile($arKeyFrameList, $szFPKeyFrameListFN); // get number of lines (each line <--> one keyframe) $szCmdLine = sprintf("tar -xvf %s -C %s", $szFPOutputFN, $szLocalFeatureDir); execSysCmd($szCmdLine); $szFPLocalTmpzzFN = sprintf("%s/%s", $szLocalFeatureDir, $szOutputCoreName); $nNumLines = loadListFile($arCountLineList, $szFPLocalTmpzzFN); deleteFile($szFPLocalTmpzzFN); if ($nNumLines == $nNumKeyFrames + 1) // first row --> annotation { printf("###File [%s] found. Skipping ... \n", $szFPOutputFN); $szLog = sprintf("###WARNING!!! %s. File [%s] found. Checked OK --> Skipping ... \n", date("m.d.Y - H:i:s"), $szFPOutputFN); $arLogListz = array(); $arLogListz[] = $szLog; global $szFPLogFN; saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t"); continue; } else { $szLog = sprintf("###WARNING!!! %s. File [%s] found. But not enough KF (Jul 14) [%s Lines - %s KF], re-running ... \n", date("m.d.Y - H:i:s"), $szFPOutputFN, $nNumLines - 1, $nNumKeyFrames); $arLogListz = array(); $arLogListz[] = $szLog; global $szFPLogFN; saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t"); } } }
         */
        $szFPLocalOutputFN = sprintf("%s/%s", $szLocalFeatureDir, $szOutputCoreName);
        
        computeSoftWeightingHistogramWithGrid($szFPKeyFrameListFN, $szFPLocalOutputFN, $szFPLocalInputFN, $szLocalFeatureDir, $szInputRawFeatureExt, $nNumRows, $nNumCols, $nMaxCodeBookSize);
        
        $szFPTarLocalOutputFN = sprintf("%s.tar.gz", $szFPLocalOutputFN);
        $szCmdLine = sprintf("tar -cvzf %s -C %s %s", $szFPTarLocalOutputFN, $szLocalFeatureDir, $szOutputCoreName);
        execSysCmd($szCmdLine);
        
        $szFPOutputFN = sprintf("%s/%s.tar.gz", $szOutputDir, $szOutputCoreName);
        $szCmdLine = sprintf("mv -f %s %s", $szFPTarLocalOutputFN, $szFPOutputFN);
        execSysCmd($szCmdLine);
        
        deleteFile($szFPLocalOutputFN);
    }
    deleteFile($szFPLocalInputFN);
}

// NEW!!! -->No longer use nFrameWidth & nFrameHeight as params --> use mapping in .prgx
// No longer use the feature file due to heavy load, instead using .loc file (only containing 5 params)
// $szFPInputLabelFN --> labels of keypoints --> each line is for one keyframe
// $szFPInputRawSIFTFN --> raw info of keypoints (including x y a b c 128-dim SIFT) --> each file is for one keyframe
function computeSoftWeightingHistogramWithGrid($szFPKeyFrameListFN, $szFPOutputFN, $szFPInputLabelFN, $szLocalFeatureDir, $szInputRawFeatureExt, $nNumRows = 2, $nNumCols = 2, $nMaxCodeBookSize = 2000) // for concatenating feature vectors of sub regions
{
    // load mapping KeyFrame and WxH
    $arKeyFrameSizeLUT = loadKeyFrameSize($szFPKeyFrameListFN);
    
    $nNumLines = loadListFile($arRawList, $szFPInputLabelFN);
    
    $nNumCommentLines = countNumCommentLines($arRawList);
    
    $arOutput = array();
    for ($i = 0; $i < $nNumCommentLines; $i ++)
    {
        $arOutput[] = $arRawList[$i];
    }
    
    for ($i = $nNumCommentLines; $i < $nNumLines; $i ++)
    {
        // svf format: NumDims Pos0 Val0 Pos1 Val
        $szLine = &$arRawList[$i];
        
        $arTmp = explode("%", $szLine);
        $szFeature = trim($arTmp[0]);
        $szAnn = trim($arTmp[1]);
        
        // load keypoint info
        $arTmpz = explode(" ", $szAnn);
        $nKeyFrameIndex = 2; // default
        $szKeyFrameID = trim($arTmpz[2]);
        
        if (! isset($arKeyFrameSizeLUT[$szKeyFrameID]))
        {
            printf("### Serious error in KeyFrameSize LUT\n");
            exit();
        }
        
        // !!! IMPORTANT
        $nFrameWidthz = $arKeyFrameSizeLUT[$szKeyFrameID]['width'];
        $nFrameHeightz = $arKeyFrameSizeLUT[$szKeyFrameID]['height'];
        
        global $gnHavingResized;
        global $gnMaxFrameWidth, $gnMaxFrameHeight;
        if ($gnHavingResized)
        {
            $nMaxFrameWidth = $gnMaxFrameWidth;
            $nMaxFrameHeight = $gnMaxFrameHeight;
            $arTmpzz = getResizedFrameSize($nFrameWidthz, $nFrameHeightz, $nMaxFrameWidth, $nMaxFrameHeight);
            $nFrameWidth = $arTmpzz['width'];
            $nFrameHeight = $arTmpzz['height'];
            
            printf("### Resizing image from  [%dx%d] to [%dx%d]\n", $nFrameWidthz, $nFrameHeightz, $nFrameWidth, $nFrameHeight);
        }
        
        $szFPLocalRawSIFTFN = sprintf("%s/%s.%s.loc", $szLocalFeatureDir, $szKeyFrameID, $szInputRawFeatureExt);
        
        // parse raw file to get coord info
        $arPointLUT = parseOneRawSIFTFile2Grid($szFPLocalRawSIFTFN, $nNumRows, $nNumCols, $nFrameWidth, $nFrameHeight);
        
        $arTmp = explode(" ", $szFeature);
        $nNumDims = intval($arTmp[0]);
        $nNumNNs = intval($arTmp[1]);
        
        $nSize = sizeof($arTmp);
        // Each row: NumDims nNumNNs Label1 Val1 ....
        if (($nSize != $nNumDims * 2 * $nNumNNs + 2) || ($nNumNNs != 4))
        {
            printf("Data error [Line-%d-Data-%s]\n[NumDims-%d # NumNNs-%d # NumSize-%d]!\n", $i, $szLine, $nNumDims, $nNumNNs, $nSize);
            exit();
        }
        
        $arTmpHist = array();
        
        $nStepSize = $nNumNNs * 2;
        $nPointIndex = 0;
        for ($j = 2; $j < $nSize; $j += $nStepSize)
        {
            $nRectIndex = $arPointLUT[$nPointIndex];
            for ($jj1 = 0; $jj1 < $nNumNNs; $jj1 ++)
            {
                $nLabel = intval($arTmp[$j + 2 * $jj1]);
                $fVal = floatval($arTmp[$j + 2 * $jj1 + 1]);
                
                if (isset($arTmpHist[$nRectIndex][$nLabel][$jj1]))
                {
                    // $arTmpHist[$nRectIndex][$nLabel][$jj1] += $fVal;
                    
                    $arTmpHist[$nRectIndex][$nLabel][$jj1] += normalizeWeight($fVal);
                } else
                {
                    // $arTmpHist[$nRectIndex][$nLabel][$jj1] = $fVal;
                    $arTmpHist[$nRectIndex][$nLabel][$jj1] = normalizeWeight($fVal);
                }
            }
            $nPointIndex ++;
        }
        
        // computing weights
        $arHist = array();
        
        ksort($arTmpHist); // sort by rect index
        foreach ($arTmpHist as $nRectIndex => $arTmpRectHist)
        {
            foreach ($arTmpRectHist as $nLabel => $arRankDist)
            {
                $fVal = 0;
                foreach ($arRankDist as $nRank => $fSum)
                {
                    $fVal += 1.0 * $fSum / pow(2, $nRank);
                }
                
                // / !IMPORTANT
                $nGlobalLabel = $nLabel + $nRectIndex * $nMaxCodeBookSize;
                $arHist[$nGlobalLabel] = $fVal;
            }
        }
        
        ksort($arHist); // important
        $szOutput = sprintf("%s", sizeof($arHist));
        foreach ($arHist as $nLabel => $fVal)
        {
            // to ensure starting label > 0
            $nLabelPlus = $nLabel + 1;
            $szOutput = $szOutput . " " . $nLabelPlus . " " . $fVal;
        }
        $szOutput = $szOutput . " % " . $szAnn;
        
        // printf("%s\n", $szOutput);
        
        $arOutput[] = $szOutput;
    }
    saveDataFromMem2File($arOutput, $szFPOutputFN);
}

/*
 * $szLocalFeatureDir
 */
function computeAssignmentSash($szLocalFeatureDir, $szFPLocalOutputFN, $szFPKeyFrameListFN, $szSashCentroidDir, $szSashCentroidName, $szPrefixAnn, $szVideoPath, $szVideoID, $szInputRawFeatureExt)
{
    global $fScaleFactor;
    global $nKNNSize;
    
    // $szSashKeypointToolApp = sprintf("sashKeyPointTool/sashKeyPointTool-nsc-BOW-L2");
    
    global $szSashKeypointToolApp;
    
    $fScaleFactor = 1.0; // increase accuracy, eg. 4.0
    $nKNNSize = 4;
    $nSamplingInterval = 1;
    
    $nNumKeyFrames = loadListFile($arKeyFrameList, $szFPKeyFrameListFN);
    
    $arOutput = array();
    $arOutput[] = sprintf("%% BoW-tf feature, %d keyframes", $nNumKeyFrames);
    
    $szFPLocalLabelOutputFN = sprintf("%s.label.lst", $szFPLocalOutputFN);
    saveDataFromMem2File($arOutput, $szFPLocalLabelOutputFN, "wt");
    
    for ($i = 0; $i < $nNumKeyFrames; $i += $nSamplingInterval)
    {
        
        $szLine = $arKeyFrameList[$i];
        
        $arTmpzz = explode("#$#", $szLine);
        $szKeyFrameID = trim($arTmpzz[0]);
        
        $szCoreName = sprintf("%s.%s", $szKeyFrameID, $szInputRawFeatureExt);
        
        $szFPSIFTDataFN = sprintf("%s/%s", $szLocalFeatureDir, $szCoreName);
        
        if (! file_exists($szFPSIFTDataFN))
        {
            printf("File [%s] not found \n", $szFPSIFTDataFN);
            
            $szLog = sprintf("###WARNING!!! %s. File raw feature [%s] not found. \n", date("m.d.Y - H:i:s"), $szFPSIFTDataFN);
            
            $arLogListz = array();
            $arLogListz[] = $szLog;
            
            global $szFPLogFN;
            saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t");
        }
        
        $szFPSIFTDataDvfFN = sprintf("%s/%s-c0-b0.dvf", $szLocalFeatureDir, $szCoreName);
        
        // parseOneRawSIFTFile2Dvf($szFPSIFTDataDvfFN, $szFPSIFTDataFN);
        parseFastOneRawSIFTFile2Dvf($szFPSIFTDataDvfFN, $szFPSIFTDataFN);
        
        // "Usage: %s --findApproxNN <FPOutputFN> <SashInputDir> <SashPrefixName> <FeatureExt> <QueryInputDir> <QueryPrefixName> <szAnn> <fScalFactor> <nNumNN>\n",
        
        // !IMPORTANT --> using new version findApproxNN
        // Each output of this exec file is one line and is added to the output file by a+t mode
        // .label.lst is auto added to
        $szAnn = sprintf("%s %s %s", $szPrefixAnn, $szVideoID, $szKeyFrameID);
        $szCmdLine = sprintf("%s --findApproxNN %s %s %s dvf %s %s '%s' %s %s", $szSashKeypointToolApp, $szFPLocalOutputFN, $szSashCentroidDir, $szSashCentroidName, $szLocalFeatureDir, $szCoreName, $szAnn, $fScaleFactor, $nKNNSize);
        execSysCmd($szCmdLine);
        
        deleteFile($szFPSIFTDataFN);
        deleteFile($szFPSIFTDataDvfFN);
        
        $time_end = microtime(true);
    }
}

function getResizedFrameSize($nFrameWidth, $nFrameHeight, $nMaxFrameWidth = 350, $nMaxFrameHeight = 350)
{
    $fScaleX = $nMaxFrameWidth * 1.0 / $nFrameWidth;
    $fScaleY = $nMaxFrameHeight * 1.0 / $nFrameHeight;
    
    $nNewFrameWidth = $nFrameWidth;
    $nNewFrameHeight = $nFrameHeight;
    if (($nFrameWidth > $nMaxFrameWidth) || ($nFrameHeight > $nMaxFrameHeight))
    {
        // try scale X
        $nNewFrameWidth = round($nFrameWidth * $fScaleX);
        $nNewFrameHeight = round($nFrameHeight * $fScaleX);
        
        if (($nNewFrameWidth > $nMaxFrameWidth) || ($nNewFrameHeight > $nMaxFrameHeight))
        {
            // try scale Y
            $nNewFrameWidth = round($nFrameWidth * $fScaleY);
            $nNewFrameHeight = round($nFrameHeight * $fScaleY);
        }
    }
    
    $arOutput = array();
    $arOutput['width'] = $nNewFrameWidth;
    $arOutput['height'] = $nNewFrameHeight;
    
    return $arOutput;
}

function findRectIndex(&$arRect, $fX, $fY)
{
    foreach ($arRect as $nRectIndex => $rRect)
    {
        if ($fX >= $rRect['left'] && $fX <= $rRect['right'] && $fY >= $rRect['top'] && $fY <= $rRect['bottom'])
        {
            return $nRectIndex;
        }
    }
    
    printf("Error in finding rect ...\n");
    print_r($arRect);
    printf("\n [%s, %s]\n", $fX, $fY);
    
    return 0; // to avoid trouble of incorrect frame size
}

// return rect index for each point
function parseOneRawSIFTFile2Grid($szFPSIFTDataFN, $nNumRows = 2, $nNumCols = 2, $nFrameWidth = 320, $nFrameHeight = 240)
{
    // build rect info
    // size of one rect (i.e. sub region)
    $nRectWidth = intval($nFrameWidth / $nNumCols);
    $nRectHeight = intval($nFrameHeight / $nNumRows);
    
    // for 2x2 grid --> 0 -> 2 -> 1 -> 3 (aligned left)
    $nRectIndex = 0;
    for ($i = 0; $i < $nNumRows; $i ++)
    {
        for ($j = 0; $j < $nNumCols; $j ++)
        {
            $arRect[$nRectIndex]['left'] = $nRectWidth * $j;
            $arRect[$nRectIndex]['top'] = $nRectHeight * $i;
            $arRect[$nRectIndex]['right'] = $nRectWidth * $j + ($nRectWidth - 1);
            $arRect[$nRectIndex]['bottom'] = $nRectHeight * $i + ($nRectHeight - 1);
            $nRectIndex ++;
        }
    }
    
    // find rect index for each keypoint
    loadListFile($arRawList, $szFPSIFTDataFN);
    
    $nCount = 0;
    $arOutput = array();
    $nPointIndex = 0;
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
        // if(sizeof($arTmp) != $nNumDims + 5)
        // Changed for IMAGENET --> only use .loc file
        
        if (sizeof($arTmp) != 5)
        
        {
            printf("Error in SIFT data file\n");
            exit();
        }
        
        // convert to int (from float)
        $fX = intval($arTmp[0]);
        $fY = intval($arTmp[1]);
        
        $arOutput[$nPointIndex] = findRectIndex($arRect, $fX, $fY);
        $nPointIndex ++;
    }
    
    return $arOutput;
}

// fNorm = exp(-fGamma*fRawScore)
// fGamma is set empirically, 1/1024 = 0.0009765625
function normalizeWeight($fRawScore, $fGamma = 0.0009765625)
{
    return (exp(- $fGamma * $fRawScore));
}

// Updated Jun 16
function loadKeyFrameSize($szFPKeyFrameListFN)
{
    loadListFile($arRawList, $szFPKeyFrameListFN);
    
    $arOutput = array();
    
    foreach ($arRawList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        if (sizeof($arTmp) != 3)
        {
            printf("### Serious error in prgx file [%s]!\n", $szFPKeyFrameListFN);
            print_r($arTmp);
            exit();
        }
        $szKeyFrameID = trim($arTmp[0]);
        $nFrameWidth = intval($arTmp[1]);
        $nFrameHeight = intval($arTmp[2]);
        
        $arOutput[$szKeyFrameID]['width'] = $nFrameWidth;
        $arOutput[$szKeyFrameID]['height'] = $nFrameHeight;
    }
    return $arOutput;
}

/*
 * function parseOneRawSIFTFile2Dvf($szFPSIFTDataDvfFN, $szFPSIFTDataFN) { loadListFile($arRawList, $szFPSIFTDataFN); $nCount = 0; // print_r($arRawList); $arOutput = array(); $arOutput[0] = sprintf("%% %s", $szFPSIFTDataFN); $arOutput[1] = sizeof($arRawList); foreach($arRawList as $szLine) { // printf("%s\n", $szLine); // first row - numDims 128 if($nCount == 0) { $nNumDims = intval($szLine); $nCount++; continue; } // second row - numKPs if($nCount == 1) { $nNumKeyPoints = intval($szLine); //if($nNumKeyPoints+2 != sizeof($arRawList)) if($nNumKeyPoints+2 < sizeof($arRawList)) { printf("Error in SIFT data file\n"); exit(); } $nCount++; continue; } $arTmp = explode(" ", $szLine); // 5 first values - x y a b c if(sizeof($arTmp) != $nNumDims + 5) { printf("Error in SIFT data file\n"); exit(); } $arFeatureTmp = array(); for($i=0; $i<$nNumDims; $i++) { $nIndex = $i+5; $arFeatureTmp[] = floatval($arTmp[$nIndex]); } $arOutput [] = convertFeatureVector2DvfFormat($arFeatureTmp); $nCount++; } // !IMPORTANT --> Bug of prev version $arOutput[1] = $nCount $arOutput[1] = $nCount-2; // remove 2 lines for numDims and numKPs saveDataFromMem2File($arOutput, $szFPSIFTDataDvfFN); }
 */
// for fast processing
function parseFastOneRawSIFTFile2Dvf($szFPSIFTDataDvfFN, $szFPSIFTDataFN)
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
        /*
         * // Do not use because it is slow for($i=0; $i<$nNumDims; $i++) { $nIndex = $i+5; $szOutput = $szOutput . " " . floatval($arTmp[$nIndex]); }
         */
        $szTmp = $arTmp[0];
        for ($i = 1; $i < 5; $i ++)
        {
            $szTmp = $szTmp . " " . $arTmp[$i];
        }
        
        $szOutput = sprintf("%s", $nNumDims);
        $arOutput[] = str_replace($szTmp, $szOutput, $szLine);
        
        $nCount ++;
        
        // print_r($arOutput); exit(); // debug
    }
    
    // !IMPORTANT --> Bug of prev version $arOutput[1] = $nCount
    $arOutput[1] = $nCount - 2; // remove 2 lines for numDims and numKPs
    
    saveDataFromMem2File($arOutput, $szFPSIFTDataDvfFN);
}

/*
 * $szLocalFeatureDir: Used to save extracted raw SIFT features $szLocalDir: Used for temp files
 */
function extractRawSIFTFeatureForOneVideoProgram($szLocalKeyFrameDir, $szLocalFeatureDir, $szRootKeyFrameDir, $szRootMetaDataDir, $szPatName, $szVideoPath, $szVideoID, $szFeatureExt, $szFeatureConfigParam)
{
    global $garAppConfig; // to access the app name of FeatureExtractor
    
    global $gKeyFrameImgExt; // jpg
    
    $szAppName = $garAppConfig["RAW_COLOR_SIFF_APP"];
    
    $szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootMetaDataDir, $szVideoPath, $szVideoID);
    
    if (! file_exists($szFPKeyFrameListFN))
    {
        printf("$$$ File [%s] does not exist!\n", $szFPKeyFrameListFN);
        continue;
    }
    $nNumKeyFrames = loadListFile($arKeyFrameList, $szFPKeyFrameListFN);
    
    // download and extract ALL .tar files from the server to the local dir
    $szServerKeyFrameDir = sprintf("%s/%s/%s", $szRootKeyFrameDir, $szVideoPath, $szVideoID);
    
    if ($nUseTarFileForKeyFrame)
    {
        $arTarFileList = collectFilesInOneDir($szServerKeyFrameDir, "", ".tar");
        foreach ($arTarFileList as $szTarFileName)
        {
            $szCmdLine = sprintf("tar -xvf %s/%s.tar -C %s", $szServerKeyFrameDir, $szTarFileName, $szLocalKeyFrameDir);
            execSysCmd($szCmdLine);
        }
    }
    else 
    {
        $szCmdLine = sprintf("cp %s/*.jpg %s", $szServerKeyFrameDir, $szLocalKeyFrameDir);
        execSysCmd($szCmdLine);
    }
    
    global $gKeyFrameImgExt;
    for ($jkf = 0; $jkf < $nNumKeyFrames; $jkf ++)
    {
        $szKeyFrameID = $arKeyFrameList[$jkf];
        
        $szFPInputImgFN = sprintf("%s/%s.%s", $szLocalKeyFrameDir, $szKeyFrameID, $gKeyFrameImgExt);
        
        $szFPFeatureOutputFN = sprintf("%s/%s.%s", $szLocalFeatureDir, $szKeyFrameID, $szFeatureExt);
        
        // --> Modified Jul 06, 2012 -- $szLocalTmpFeatureDir
        extractRawSIFTFeatureForOneImage($szAppName, $szFeatureConfigParam, $szFPInputImgFN, $szFPFeatureOutputFN, $szLocalKeyFrameDir);
    }
}

function extractRawSIFTFeatureForOneImage($szAppName, $szFeatureConfigParam, $szFPInputImgFN, $szFPFeatureOutputFN, $szTmpDir)
{
    global $gKeyFrameImgExt; // jpg
                             
    // this code did not work with pgm format --> keep jpg format
    $szBaseInputName = basename($szFPInputImgFN);
    $szFPJPGInputFN = sprintf("%s/%s.%s", $szTmpDir, $szBaseInputName, $gKeyFrameImgExt);
    
    $szFPSimpleFeatureOutputFN = sprintf("%s.loc", $szFPFeatureOutputFN);
    
    // printf("File [%s]\n", $szFPInputImgFN);
    if (filesize($szFPInputImgFN) <= 0)
    {
        printf("File not found [%s]\n", $szFPInputImgFN);
        return - 1;
    }
    
    if (! file_exists($szFPInputImgFN))
    {
        printf("File not found [%s]\n", $szFPInputImgFN);
        return - 1;
    }
    
    global $gszResizeOption;
    global $gnHavingResized;
    
    if ($gnHavingResized)
    {
        // convert Frame-5020.jpg -resize '350x350>' test3.jpg
        $szCmdLine = sprintf("convert %s %s %s", $szFPInputImgFN, $gszResizeOption, $szFPJPGInputFN);
    } else
    {
        // just copy
        $szCmdLine = sprintf("cp %s %s", $szFPInputImgFN, $szFPJPGInputFN);
    }
    
    execSysCmd($szCmdLine);
    
    // generate to tmp output
    $szBaseOutputName = basename($szFPFeatureOutputFN);
    $szFPFeatureTmpFN = sprintf("%s/%s", $szTmpDir, $szBaseOutputName);
    $szFPFeatureTmpFN2 = sprintf("%s/%s.raw", $szTmpDir, $szBaseOutputName);
    
    // only param, not including descriptor
    $szFPSimpleFeatureTmpFN = sprintf("%s.loc", $szFPFeatureTmpFN);
    
    // ./colorDescriptor test-tv05.jpg --detector densesampling --descriptor csift --ds_scales 1.2+2.0 --output test-tv05.densesampling.csift.mulscale
    // FeatureConfig = --detector densesampling --descriptor csift --ds_scales 1.2+2.0
    $szCmdLine = sprintf("%s %s %s --output %s", $szAppName, $szFPJPGInputFN, $szFeatureConfigParam, $szFPFeatureTmpFN2);
    execSysCmd($szCmdLine);
    
    convertRawColorSIFT2StandardFormat($szFPFeatureOutputFN, $szFPFeatureTmpFN2);
    
    // delete files
    deleteFile($szFPJPGInputFN);
    deleteFile($szFPFeatureTmpFN2);
    
    return 1;
}

function convertRawColorSIFT2StandardFormat($szFPOutputFN, $szFPInputFN)
{
    $arOutput = array();
    
    // Update Oct 06, 2011
    $arSimpleOutput = array(); // --> only store spatial information, not including SIFT descriptor
    
    $nNumRows = loadListFile($arInput, $szFPInputFN);
    
    // skip the first row - KOEN1
    // keep the next 2 rows --> NumDims and NumKeyPoints
    
    $arOutput[] = $arInput[1];
    $arOutput[] = $arInput[2];
    
    $arSimpleOutput[] = $arInput[1];
    $arSimpleOutput[] = $arInput[2];
    for ($i = 3; $i < $nNumRows; $i ++)
    {
        $szLine = &$arInput[$i];
        $arTmp = explode(";", $szLine);
        // print_r($arTmp);
        $szVal = trim($arTmp[1]);
        
        // $arTmp = explode(" ", $szVal);
        // printf(sizeof($arTmp));exit();
        
        $szTmp = trim($arTmp[0]);
        
        $arTmp = explode("<CIRCLE", $szTmp);
        // print_r($arTmp);
        
        $szTmp = trim($arTmp[1]);
        $arTmp = explode(">", $szTmp);
        // print_r($arTmp); exit();
        $szParam = trim($arTmp[0]);
        
        $arOutput[] = sprintf("%s %s", $szParam, $szVal);
        $arSimpleOutput[] = sprintf("%s", $szParam); // only keep param
    }
    saveDataFromMem2File($arOutput, $szFPOutputFN);
    
    $szFPSimpleOutputFN = sprintf("%s.loc", $szFPOutputFN);
    saveDataFromMem2File($arSimpleOutput, $szFPSimpleOutputFN);
}

?>