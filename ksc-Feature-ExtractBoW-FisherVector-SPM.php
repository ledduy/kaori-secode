<?php

/**
 * 		@file 	ksc-Feature-ExtractBoW-FisherVector-SPM.php
* 		@brief 	Extract BoW-FisherVector Features - AllInOne Step.
*		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
*
* 		Copyright (C) 2010-2013 Duy-Dinh Le.
* 		All rights reserved.
* 		Last update	: 07 Sep 2013.
*/

// Update Sep 07, 2013
// VERY SLOW version, only support 1x1

// !!! IMPORTATNT !!!
// $nUseTarFileForKeyFrame --> defined in ksc-AppConfig.php
// $nMaxDim = 400; // 384 for RGBSIFT
// $nMaxCodeBookSize = 2*$nMaxDim*$nNumFVClusters;

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
$gnUseTarFileForKeyFrame = 0;

// $szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

$gSkippExistingFiles = 1;
$gKeyFrameImgExt = "jpg";

$arNormMethod = array(
    1 => "L2",
    2 => "Sqrt",
    3 => "Improved",
    4 => "Fast"
);

$arFeatureParamConfigList = array(
    "nsc.raw.dense6mul.sift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor sift", // dense sampling, multi scale
    "nsc.raw.dense6mul.csift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor csift",
    "nsc.raw.dense6mul.rgsift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor rgsift",
    "nsc.raw.dense6mul.rgbsift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor rgbsift",
    "nsc.raw.dense6mul.oppsift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor opponentsift",
    
    "nsc.raw.dense4mul.sift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor sift", // dense sampling, multi scale
    "nsc.raw.dense4mul.csift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor csift",
    "nsc.raw.dense4mul.rgsift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor rgsift",
    "nsc.raw.dense4mul.rgbsift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor rgbsift",
    "nsc.raw.dense4mul.oppsift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor opponentsift",
    
    "nsc.raw.harlap6mul.rgbsift" => "--detector harrislaplace --descriptor rgbsift",
    "nsc.raw.harlap6mul.sift" => "--detector harrislaplace --descriptor sift",
    "nsc.raw.harlap6mul.csift" => "--detector harrislaplace --descriptor csift",
    "nsc.raw.harlap6mul.oppsift" => "--detector harrislaplace --descriptor oppsift",
    
    "nsc.raw.dense6mul3.rgbsift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0+3.2 --descriptor rgbsift"
);

// ////////////////// END FOR CUSTOMIZATION ////////////////////

// /////////////////////////// MAIN ////////////////////////////////

$szPatName = "devel2012";
$szInputRawFeatureExt = "nsc.raw.harlap6mul.sift";
$szTargetPatName = "test2012"; // or devel2012
$nNumFVClusters = 128;
$nStartID = 0; // 0
$nEndID = 1; // 1

$nNormMethod = 1;
if ($argc != 8)
{
    printf("Usage: %s <SrcPatName> <TargetPatName> <RawFeatureExt> <NumFVCluster> <NormMethod> <Start> <End>\n", $argv[0]);
    printf("Usage: %s %s %s %s %s %s %s %s\n", $argv[0], $szPatName, $szTargetPatName, $szInputRawFeatureExt, $nNumFVClusters, $nNormMethod, $nStartID, $nEndID);
    exit();
}

$szPatName = $argv[1]; // tv2007.devel
$szTargetPatName = $argv[2];
$szInputRawFeatureExt = $argv[3];
$nNumFVClusters = intval($argv[4]);

$nNormMethod = intval($argv[5]);
$nNormMethod = 3; // Improved method for normalization

$nStartID = intval($argv[6]); // 0
$nEndID = intval($argv[7]); // 1

$nMaxDim = 400; // 384 for RGBSIFT
$nMaxCodeBookSize = 2 * $nMaxDim * $nNumFVClusters;

$szTrialName = sprintf("GMMFV-%d", $nNumFVClusters);
if (! isset($arFeatureParamConfigList[$szInputRawFeatureExt]))
{
    print_r($arFeatureParamConfigList);
    exit("Feature ext is not supported. Check arFeatureParamConfigList\n");
}

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
$szFPLogFN = sprintf("%s-%s-%s.log", $szScriptBaseName, $szInputRawFeatureExt, $arNormMethod[$nNormMethod]); // *** CHANGED ***
                                                                                                             
// *** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szInputRawFeatureExt); // *** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);
$szRootFeatureOutputDir = $szRootFeatureDir;

$szLocalTmpDir = $gszTmpDir; // defined in ksc-AppConfig

$szTmpDir = sprintf("%s/%s/%s/%s-%s-%s-%d-%d", $szLocalTmpDir, $szScriptBaseName, $szPatName, $szTargetPatName, $szInputRawFeatureExt, $arNormMethod[$nNormMethod], $nStartID, $nEndID);
makeDir($szTmpDir);

// !!! IMPORTANT
// source sash data
$szRootCentroidDir1 = sprintf("%s/bow.codebook.%s.%s/%s", $szRootFeatureDir, $szTrialName, $szPatName, $szInputRawFeatureExt);
$szGMMModelDir1 = $szRootCentroidDir1;

// copy GMM data to /local/ledduy
$szRootCentroidDir = sprintf("%s", $szTmpDir);
$szGMMModelDir = sprintf("%s/gmm-%d-%d", $szRootCentroidDir, $nStartID, $nEndID);
makeDir($szGMMModelDir);

// / !!! IMPORTANT
$szCmdLine = sprintf("cp %s/data/*GMM*.mat %s", $szGMMModelDir1, $szGMMModelDir);
execSysCmd($szCmdLine);

$szGMMModelName = sprintf("%s.%s.%s.GMMModel", $szTrialName, $szPatName, $szInputRawFeatureExt);

// !!! NEW Aug04 2011 --> to prevent wrong assignment EVEN the codebook does not exist.
// checking CODEBOOK exists
// Soft-500-VL2.tv2011.devel-nist.nsc.raw.phow8.sift.Centroids-c0-b0.sash
$szFPGMMModelFN = sprintf("%s/%s.mat", $szGMMModelDir, $szGMMModelName);

if (! file_exists($szFPGMMModelFN))
{
    printf("### Serious Error - GMM model not found [%s]\n", $szFPGMMModelFN);
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
$szFeatureConfigParam = $arFeatureParamConfigList[$szInputRawFeatureExt];

computeFisherVectorBOWHistogramWithGridForOnePat($szTmpDir, $szFeatureConfigParam, $szRootFeatureOutputDir, $szRootKeyFrameDir, $szRootMetaDataDir, $szGMMModelDir, $szGMMModelName, $szPrefixAnn, $szTargetPatName, $szInputRawFeatureExt, $szBOWFeatureExt, $nMaxCodeBookSize, $nStartID, $nEndID);

// clean up
$szCmdLine = sprintf("rm -rf %s", $szGMMModelDir);
execSysCmd($szCmdLine);

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $szFinishTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// ///////////////////////////// FUNCTIONS //////////////////////////////
function computeFisherVectorBOWHistogramWithGridForOnePat($szLocalDir, $szFeatureConfigParam, $szRootFeatureOutputDir, $szRootKeyFrameDir, $szRootMetaDataDir, $szGMMModelDir, $szGMMModelName, $szPrefixAnn, $szPatName, $szInputRawFeatureExt, $szBOWFeatureExt, $nMaxCodeBookSize = 2000, $nStartVideoID = -1, $nEndVideoID = -1)
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
        computeFisherVectorBOWHistogramWithGridForOneVideoProgram($szLocalFeatureDir, $szRootFeatureOutputDir, $szFPKeyFrameListFN, $szGMMModelDir, $szGMMModelName, $szPrefixAnn, $szVideoPath, $szVideoID, $szInputRawFeatureExt, $szBOWFeatureExt, $nMaxCodeBookSize);
        
        // clean up
        $szCmdLine = sprintf("rm -rf %s", $szLocalKeyFrameDir);
        execSysCmd($szCmdLine);
        
        $szCmdLine = sprintf("rm -rf %s", $szLocalFeatureDir);
        execSysCmd($szCmdLine);
    }
}

function computeFisherVectorBOWHistogramWithGridForOneVideoProgram($szLocalFeatureDir, $szRootFeatureOutputDir, $szFPKeyFrameListFN, $szGMMModelDir, $szGMMModelName, $szPrefixAnn, $szVideoPath, $szVideoID, $szInputRawFeatureExt, $szBOWFeatureExt, $nMaxCodeBookSize = 2000)
{
    $arGridList = array(
        // 3 => 1,
        // 2 => 2,
        // 1 => 3,
        1 => 1
    );
    
    $szCoreName = sprintf("%s.%s", $szVideoID, $szBOWFeatureExt);
    
    $szFPGMMModelFN = sprintf("%s/%s.mat", $szGMMModelDir, $szGMMModelName);
    
    global $nNormMethod;
    global $arNormMethod;
    foreach ($arGridList as $nNumRows => $nNumCols)
    {
        // adding grid info (mxn)
        // Changed 20 Jan --> 1x3 --> norm1x3
        $szOutputFeatureExt = sprintf("%s.%snorm%dx%d", $szBOWFeatureExt, $arNormMethod[$nNormMethod], $nNumRows, $nNumCols);
        
        $szOutputDir = sprintf("%s/%s/%s", $szRootFeatureOutputDir, $szOutputFeatureExt, $szVideoPath);
        makeDir($szOutputDir);
        
        $szOutputCoreName = sprintf("%s.%s", $szVideoID, $szOutputFeatureExt);
        $szFPLocalOutputFN = sprintf("%s/%s", $szLocalFeatureDir, $szOutputCoreName);
        
        computeSoftWeightingHistogramWithGrid($szFPGMMModelFN, $szFPKeyFrameListFN, $szFPLocalOutputFN, $szLocalFeatureDir, $szInputRawFeatureExt, $nNumRows, $nNumCols, $nMaxCodeBookSize);
        
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
// $szFPInputRawSIFTFN --> raw info of keypoints (including x y a b c 128-dim SIFT) --> each file is for one keyframe
function computeSoftWeightingHistogramWithGrid($szFPGMMModelFN, $szFPKeyFrameListFN, $szFPOutputFN, $szLocalFeatureDir, $szInputRawFeatureExt, $nNumRows = 2, $nNumCols = 2, $nMaxCodeBookSize = 2000) // for concatenating feature vectors of sub regions
{
    global $nNormMethod;
    global $arNormMethod;
    
    // load mapping KeyFrame and WxH
    $arKeyFrameSizeLUT = loadKeyFrameSize($szFPKeyFrameListFN);
    
    $arOutput = array();
    $nNumKeyFrames = sizeof($arKeyFrameSizeLUT);
    $arOutput[] = sprintf("%% Fisher Vector - %d keyframes - %d Norm - %s - Grid %dx%d - CodeBook size %d", 
            $nNumKeyFrames, $arNormMethod[$nNormMethod], $szInputRawFeatureExt, $nNumRows, $nNumCols, $nMaxCodeBookSize);
    
    foreach ($arKeyFrameSizeLUT as $szKeyFrameID => $arSize)
    {
        // !!! IMPORTANT
        $nFrameWidthz = $arSize['width'];
        $nFrameHeightz = $arSize['height'];
        
        $szAnn = sprintf("NA NA %s", $szKeyFrameID);
        
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
        
        $arInvPointLUT = array();
        foreach ($arPointLUT as $nPointIndex => $nRectIndex)
        {
            $arInvPointLUT[$nRectIndex][] = $nPointIndex;
        }
        
        // generate csv file containing feature vectors for each RectIndex
        $szFPLocalRawSIFTFN = sprintf("%s/%s.%s", $szLocalFeatureDir, $szKeyFrameID, $szInputRawFeatureExt);
        
        // each entry = 1 csv string of feature values
        $arCSVFeatureList = parseSIFTFeatureFile2CSV($szFPLocalRawSIFTFN);
        
        $arTmpHist = array();        
        foreach ($arInvPointLUT as $nRectIndex => $arPointList)
        {
            $arCSVOutput = array();
            foreach ($arPointList as $nPointIndex)
            {
                $arCSVOutput[] = $arCSVFeatureList[$nPointIndex];
            }
            $szFPCSVFeatureFN = sprintf("%s-RectID%d.csv", $szFPLocalRawSIFTFN, $nRectIndex);
            saveDataFromMem2File($arCSVOutput, $szFPCSVFeatureFN);
            
            // call matlab function
            global $garAppConfig;
            $szVLFEATDir = $garAppConfig["RAW_VLFEAT_DIR"];
            // there are 2 commands, so we need to create a tmp .sh file and run it
            // the temp .sh file is generated so that a unique name is guaranteed
            $arCmdLine = array();
            $szCmdLine = sprintf("cd %s; pwd;", $szVLFEATDir); //
            $arCmdLine[] = $szCmdLine;
            
            // call A(param1, param2) instead of A param1 param2
            
            $szParam = sprintf("'%s', '%s', %d", $szFPGMMModelFN, $szFPCSVFeatureFN, $nNormMethod);
            $szCmdLine = sprintf("matlab -nodisplay -nojvm -r \"ksc_FV_PerformFisherVectorEncoding_VLFEAT(%s)\" ", $szParam);
            printf("Command: [%s]\n", $szCmdLine);
            $arCmdLine[] = $szCmdLine;
            
            $szPrefix = sprintf("ksc_FV_PerformFisherVectorEncoding_VLFEAT_%s", $szInputRawFeatureExt);
            $szFPCmdFN = tempnam("/tmp", $szPrefix);
            saveDataFromMem2File($arCmdLine, $szFPCmdFN);
            $szCmdLine = sprintf("chmod +x %s", $szFPCmdFN);
            system($szCmdLine);
            
            system($szFPCmdFN);
            deleteFile($szFPCmdFN);
            
            
            $szFPFisherVectorEncFN = sprintf("%s.fve", $szFPCSVFeatureFN);
            
            if (file_exists($szFPFisherVectorEncFN))
            {
                $nNumRows = loadListFile($arRawList, $szFPFisherVectorEncFN);
                
                if($nNumRows !=1)
                {
                    exit("Serious err ZZZ\n");
                }
                $arTmpz1 = explode(",", $arRawList[0]);
                
                foreach($arTmpz1 as $nLabel => $fTmp)
                {
                    $arTmpHist[$nRectIndex][$nLabel] = floatval($fTmp);
                }               
            }
            else 
            {
                printf("File not found [%s]\n", $szFPFisherVectorEncFN);    
            }
            
            deleteFile($szFPCSVFeatureFN);
            deleteFile($szFPFisherVectorEncFN);
        }

        //printf("Debug CheckPoint 2"); exit();
        
        //print_r($arTmpHist);
        // //
        $arHist = array();
        
        ksort($arTmpHist); // sort by rect index
        foreach ($arTmpHist as $nRectIndex => $arTmpRectHist)
        {
            foreach ($arTmpRectHist as $nLabel => $fVal)
            {
                // / !IMPORTANT
                if($fVal)
                {
                    $nGlobalLabel = $nLabel + $nRectIndex * $nMaxCodeBookSize;
                    $arHist[$nGlobalLabel] = $fVal;
                }
            }
        }
        
        ksort($arHist); // !IMPORTANT
        
        $szOutput = sprintf("%s", sizeof($arHist));
        foreach ($arHist as $nLabel => $fVal)
        {
            if ($fVal)
            {
                // to ensure starting label > 0
                $nLabelPlus = $nLabel + 1;
                $szOutput = $szOutput . " " . $nLabelPlus . " " . $fVal;
            }
        }
        $szOutput = $szOutput . " % " . $szAnn;
        
        //printf("%s\n", $szOutput); exit();
        
        $arOutput[] = $szOutput;
    }
    saveDataFromMem2File($arOutput, $szFPOutputFN);
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
    
    global $gnUseTarFileForKeyFrame;
    if ($gnUseTarFileForKeyFrame)
    {
        $arTarFileList = collectFilesInOneDir($szServerKeyFrameDir, "", ".tar");
        foreach ($arTarFileList as $szTarFileName)
        {
            $szCmdLine = sprintf("tar -xvf %s/%s.tar -C %s", $szServerKeyFrameDir, $szTarFileName, $szLocalKeyFrameDir);
            execSysCmd($szCmdLine);
        }
    } else
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
        
        //break; // debug
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

function parseSIFTFeatureFile2CSV($szFPFeatureFN)
{
    $nNumRows = loadListFile($arRawList, $szFPFeatureFN);
    
    $nNumDims = intval($arRawList[0]);
    $nNumKeyFrames = intval($arRawList[1]);
    
    if ($nNumRows != $nNumKeyFrames + 2)
    {
        printf("Serious error in feature file [%s] - Checkpoint 1 [%s] - [%s]\n", $szFPFeatureFN, $nNumRows, $nNumKeyFrames);
        exit();
    }
    
    $arOutput = array();
    for ($i = 2; $i < $nNumRows; $i ++)
    {
        $szLine = $arRawList[$i];
        $arTmp = explode(" ", $szLine);
        $nNumVals = sizeof($arTmp);
        
        // 5 values for keypoint param
        if ($nNumVals != $nNumDims + 5)
        {
            printf("Serious error in feature file [%s] - Checkpoint 2 [%s] - [%s]\n", $szFPFeatureFN, $nNumVals, $nNumDims);
            exit();
        }
        
        $szOutput = "";
        for ($j = 0; $j < 5; $j ++)
        {
            $szOutput = $szOutput . trim($arTmp[$j]) . " ";
        }
        $szFeature = trim(str_replace($szOutput, "", $szLine));
        
        $arOutput[] = $szFeature;
    }
    
    return $arOutput;
}
?>