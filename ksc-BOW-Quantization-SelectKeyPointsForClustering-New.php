<?php

/**
 * 		@file 	ksc-BOW-Quantization-SelectKeyPointsForClustering.php
 * 		@brief 	Selecting keypoints for clustering.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 03 Sep 2013.
 */

// *** Update Sep 03, 2013
// --> CHANGE: Extract raw features on the fly (i.e no need to extract raw features of ALL keyframes)

// *** Update Aug 14, 2013
// --> just copied from kaori-sin13
// --> changed: $arPatList = array("subtest2012-new");

// ////////////// HOW TO CUSTOMIZE ////////////////

// Fixed param:
// $nMaxKeyPoints = intval(1500000.0); // 1.5 M - max keypoints for clustering
// $nAveKeyPointsPerKF = 1000; // average number of keypoints per key frame
// $fKeyPointSamplingRate = 0.70; // percentage of keypoints of one image will be selected
// $fVideoSamplingRate = 1.0; // to ensure all videos are used for selection
// $fKeyFrameSamplingRate = 0.000001; // to ensure only 1 KF/shot

// --> Only ONE param: $fShotSamplingRate
// Input: Number of videos, Number of shots per video - If no shot case, it is the Number of KFs
// Estimation
// Max KeyFrames = 1.5M / (1000 * 0.7) = 2K KF
// Number of videos = 200 --> Number of KF per video ($fVideoSamplingRate = 1.0) = 2K / 200 = 10 (QUOTA)
// Number of shots per video (by parsing .RKF) ~ 400K (of devel set 2012) /200 (videos - new organization) = 2K
// Number of KF per shot ~ 1KF
// --> if ($fShotSamplingRate = 0.01) --> 2K * 0.01 = 20 (10 (QUOTA))

// ////////////////////////////////////////////////

// ///////////// IMPORTANT PARAMS ////////////////////

// FIX Max Keypoints Per Frame is 1,000

/*
 * $nMaxKeyPoints = intval(1500000.0); // 1.5 M - max keypoints for clustering $nAveKeyPointsPerKF = 1000; // average number of keypoints per key frame $fKeyPointSamplingRate = 0.70; // percentage of keypoints of one image will be selected $nMaxKeyFrames = intval($nMaxKeyPoints/($nAveKeyPointsPerKF*$fKeyPointSamplingRate)); // max keyframes to be selected for picking keypoints $fVideoSamplingRate = 0.50; // percentage of videos of the set will be selected, for ImageNet, this value should be 1.0 $fShotSamplingRate = 0.2; // lower this value if we want more videos, percentage of shots of one video will be selected $fKeyFrameSamplingRate = 0.0001; // i.e. 1KF/shot - $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFsPerShot)); $nMaxBlocksPerChunk=1; // only one chunk $nMaxSamplesPerBlock=2000000; // larger than maxKP to ensure all keypoints in 1 chunk-block
 */
// /////////////////////////////////////////////////////

/**
 * *********** STEPS FOR BOW MODEL ***************
 * ===> STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV10.php --> select keypoints from devel pat
 * STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV10.php --> do clustering using VLFEAT vl_kmeans, L2 distance
 * STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
 * STEP 4: nsc-ComputeAssignmentSash-TV10/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
 * STEP 5: nsc-ComputeSoftBOW-Grid-TV10/-SGE.php --> compute soft assignment for grid image
 */

// ///////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

// /////////////////////////// THIS PART FOR CUSTOMIZATION /////////////////
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
    
    "nsc.raw.dense6mul3.rgbsift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0+3.2 --descriptor rgbsift",
);

$gnUseTarFileForKeyFrame = 0;
$gKeyFrameImgExt = "jpg";

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

$szSrcPatName = "devel2012"; // must be a member of $arPatList
$szRawFeatureExt = "nsc.raw.dense6mul.rgbsift";

// / !!! IMPORTANT
//$nMaxKeyPoints = intval(10000.0);  --> for Debug only
$nMaxKeyPoints = intval(1500000.0); // 1.5 M - max keypoints for clustering
                                    
// average number of keypoints per key frame --> used in function loadOneRawSIFTFile
$nAveKeyPointsPerKF = 1000;
$fKeyPointSamplingRate = 0.70; // percentage of keypoints of one image will be selected
                               
// max keyframes to be selected for picking keypoints
                               // use weight = 1.5 to pick more number of keyframes to ensure min selected KP = $nMaxKeyPoints
                               // some keyframes --> no keypoints (ie. blank/black frames)
$nMaxKeyFrames = intval(1.5 * $nMaxKeyPoints / ($nAveKeyPointsPerKF * $fKeyPointSamplingRate)) + 1;

// shot information can not be inferred from keyframeID --> one shot = one keyframes
$fVideoSamplingRate = 1.0; // percentage of videos of the set will be selected
$fKeyFrameSamplingRate = 0.00001; // i.e. 1KF/shot - $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFsPerShot));
                                  
// *** CHANGED ***
$nAveShotPerVideo = 100; // *** CHANGED ***

$nMaxBlocksPerChunk = 1; // only one chunk
$nMaxSamplesPerBlock = $nMaxKeyPoints * 2; // larger than maxKP to ensure all keypoints in 1 chunk-block
                                           
// ////////////////// END FOR CUSTOMIZATION ////////////////////
                                           
// /////////////////////////// MAIN ////////////////////////////////

if ($argc != 3)
{
    printf("Usage: %s <SrcPatName> <RawFeatureExt>\n", $argv[0]);
    printf("Usage: %s %s %s\n", $argv[0], $szSrcPatName, $szRawFeatureExt);
    exit();
}

$szSrcPatName = $argv[1];
$szRawFeatureExt = $argv[2];

if(!isset($arFeatureParamConfigList[$szRawFeatureExt]))
{
    print_r($arFeatureParamConfigList);
    exit("Feature ext is not supported. Check arFeatureParamConfigList\n");
}

// Re-calculate $fShotSamplingRate
$nMaxVideos = $arMaxVideosPerPatList[$szSrcPatName];
$nMaxKFPerVideo = intval($nMaxKeyFrames / $nMaxVideos) + 1;
// if we set 1KF/shot --> $nMaxKFPerVideo = $nMaxShotPerVideo
$fShotSamplingRate = $nMaxKFPerVideo / $nAveShotPerVideo;
printf("### Shot sampling rate: %f\n", $fShotSamplingRate);

$szFPLogFN = sprintf("ksc-BOW-Quantization-SelectKeypointsForClustering-%s.log", $szRawFeatureExt); // *** CHANGED ***

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]", $szStartTime, $argv[1], $argv[2]);

$arLog[] = sprintf("###Max KeyFrames to Select: [%s] - Max Videos: [%s]- Max KF Per Video: [%s] - Shot Sampling Rate: [%s]", $nMaxKeyFrames, $nMaxVideos, $nMaxKFPerVideo, $fShotSamplingRate);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// / !!! IMPORTANT
// $szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
// *** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szRawFeatureExt); // *** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);

// Update Nov 25, 2011
$szLocalTmpDir = $gszTmpDir; // defined in ksc-AppConfig
$szTmpDir = sprintf("%s/SelectKeyPointForClustering/%s", $szLocalTmpDir, $szRawFeatureExt);
makeDir($szTmpDir);

$szFPVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szSrcPatName);

$szOutputDir = sprintf("%s/bow.codebook.%s.%s/%s/data", $szRootFeatureDir, $szTrialName, $szSrcPatName, $szRawFeatureExt);
makeDir($szOutputDir);
$szDataPrefix = sprintf("%s.%s.%s", $szTrialName, $szSrcPatName, $szRawFeatureExt);
$szDataExt = "dvf";

$arAllKeyFrameList = selectKeyFrames($nMaxKeyFrames, $fVideoSamplingRate, $fShotSamplingRate, $fKeyFrameSamplingRate, $szFPVideoListFN, $szRootMetaDataDir, $szRawFeatureExt);
//print_r($arAllKeyFrameList); exit();

$szLocalTmpDir = sprintf("%s/%s", $szTmpDir, $szSrcPatName);
makeDir($szLocalTmpDir);

$szFPInputListFN = sprintf("%s/BoW.SelKeyFrame.%s.%s.%s.lst", $szOutputDir, $szTrialName, $szSrcPatName, $szRawFeatureExt);
// saveDataFromMem2File(array_keys($arAllKeyFrameList), $szFPInputListFN);

// if not use shuffle_assoc, keyframes in the bottom list might not be selected due to limit of max keypoints
shuffle_assoc($arAllKeyFrameList);
saveDataFromMem2File($arAllKeyFrameList, $szFPInputListFN);

// print stats
global $arStatVideoList;
$nCountzz = 1;
ksort($arStatVideoList);
$arOutput = array();
foreach ($arStatVideoList as $szVideoID => $arKFList)
{
    $arOutput[] = sprintf("###%d. %s, %s", $nCountzz, $szVideoID, sizeof($arKFList));
    $nCountzz ++;
}
$szFPOutputStatFN = sprintf("%s.csv", $szFPInputListFN);
saveDataFromMem2File($arOutput, $szFPOutputStatFN);

selectKeyPointsFromKeyFrameList($szOutputDir, $szDataPrefix, $szDataExt, $szFPInputListFN, $szFPVideoListFN, $szRawFeatureExt, $szRootKeyFrameDir, $szRootFeatureDir, $szLocalTmpDir, $fKeyPointSamplingRate, $nMaxKeyPoints, $nMaxBlocksPerChunk, $nMaxSamplesPerBlock);

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]", $szStartTime, $szFinishTime, $argv[1], $argv[2]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// //////////////////////////////////// FUNCTIONS //////////////////////////
/**
 * Select keypoints from a set of images for clustering (to form codebook).
 *
 * Selection params:
 * + nMaxKeyFrames (default 2,000):
 * + fVideoSamplingRate (default 1.0): percentage of videos of the set will be selected
 * + fShotSamplingRate (default 1.0): percentage of shots of one video will be selected
 * ==> if no shot (such as imageclef, imagenet) --> one shot = one keyframe
 * ==> if KeyFrameID does not have .RKF --> consider as no shot.
 * + $fKeyFrameSamplingRate (default 1/50): percentage of keyframes per shot
 * ==> if no shot --> only 1 KF/shot is picked no matter what $fKeyFrameSamplingRate
 * + fKeyPointSamplingRate (default: 0.75): percentage of keypoints of one image will be selected
 */

// szFPVideoListFN --> arVideoList[videoID] = videoPath
// RootMetaData + videoPath + /videoID.prg
// RootFeatureDir + FeatureExt + videoPath + /videoID.featureExt (.tar.gz)
function selectKeyFrames($nMaxKeyFrames, $fVideoSamplingRate = 1.0, $fShotSamplingRate = 1.0, $fKeyFrameSamplingRate, $szFPVideoListFN, $szRootMetaDataDir, $szFeatureExt)
{
    global $arStatVideoList;
    $arStatVideoList = array(); // for statistics
                                
    // load video list
    loadListFile($arRawList, $szFPVideoListFN);
    $arVideoList = array();
    foreach ($arRawList as $szLine)
    {
        // TRECVID2005_141 #$# 20041030_133100_MSNBC_MSNBCNEWS13_ENG #$# tv2005/devel
        $arTmp = explode("#$#", $szLine);
        $szVideoID = trim($arTmp[0]);
        $szVideoPath = trim($arTmp[2]);
        $arVideoList[$szVideoID] = $szVideoPath;
    }
    
    $nTotalVideos = sizeof($arVideoList);
    $nNumSelVideos = intval(max(1, $fVideoSamplingRate * $nTotalVideos));
    
    $arAllKeyFrameList = array();
    
    $arSelVideoList = array();
    if ($nNumSelVideos < 2)
    {
        $arSelVideoList[] = array_rand($arVideoList, $nNumSelVideos);
    } else
    {
        $arSelVideoList = array_rand($arVideoList, $nNumSelVideos);
    }
    
    shuffle($arSelVideoList);
    print_r($arSelVideoList);
    $nFinish = 0;
    foreach ($arSelVideoList as $szVideoID)
    {
        $szVideoPath = $arVideoList[$szVideoID];
        
        $szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootMetaDataDir, $szVideoPath, $szVideoID);
        
        if (! file_exists($szFPKeyFrameListFN))
        {
            printf("#@@@# File [%s] not found!", $szFPKeyFrameListFN);
            continue;
        }
        
        loadListFile($arKFRawList, $szFPKeyFrameListFN);
        
        $arShotList = array();
        foreach ($arKFRawList as $szKeyFrameID)
        {
            $arTmp = explode(".RKF", $szKeyFrameID);
            $szShotID = trim($arTmp[0]);
            
            // If there is no .RKF --> $szShotID = $szKeyFrameID
            $arShotList[$szShotID][$szKeyFrameID] = 1;
        }
        $nNumShots = sizeof($arShotList);
        $nNumSelShots = intval(max(1, $fShotSamplingRate * $nNumShots));
        
        $arSelShotList = array();
        if ($nNumSelShots < 2)
        {
            $arSelShotList[] = array_rand($arShotList, $nNumSelShots);
        } else
        {
            $arSelShotList = array_rand($arShotList, $nNumSelShots);
        }
        
        shuffle($arSelShotList);
        print_r($arSelShotList);
        
        foreach ($arSelShotList as $szShotID)
        {
            $arKeyFrameList = $arShotList[$szShotID];
            $nNumKFs = sizeof($arKeyFrameList);
            
            $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate * $nNumKFs));
            $arSelKeyFrameList = array();
            if ($nNumSelKFs < 2)
            {
                $arSelKeyFrameList[] = array_rand($arKeyFrameList, $nNumSelKFs);
            } else
            {
                $arSelKeyFrameList = array_rand($arKeyFrameList, $nNumSelKFs);
            }
            
            shuffle($arSelKeyFrameList);
            // print_r($arSelKeyFrameList); exit();
            foreach ($arSelKeyFrameList as $szKeyFrameID)
            {
                // printf("###. %s\n", $szKeyFrameID);
                // $arAllKeyFrameList[$szKeyFrameID] = 1;
                
                // *** Changed for IMAGENET
                $arAllKeyFrameList[$szKeyFrameID] = sprintf("%s #$# %s #$# %s", $szKeyFrameID, $szVideoID, $szShotID);
                // *** Changed for IMAGENET
                $arStatVideoList[$szVideoID][$szKeyFrameID] = 1;
                
                if (sizeof($arAllKeyFrameList) >= $nMaxKeyFrames)
                {
                    $nFinish = 1;
                    break; // keyframe selection
                }
            }
            if ($nFinish)
            {
                break; // shot selection
            }
        }
        
        if ($nFinish)
        {
            break; // video selection
        }
    }
    
    // print_r($arAllKeyFrameList); exit();
    return $arAllKeyFrameList;
}

function loadOneRawSIFTFile($szFPSIFTDataFN, $fKPSamplingRate = 0.5, $szAnnPrefix = "")
{
    global $nAveKeyPointsPerKF;
    $nMaxKeyPointsPerKF = $nAveKeyPointsPerKF; // 1000
    
    loadListFile($arRawList, $szFPSIFTDataFN);
    
    $nCount = 0;
    // print_r($arRawList);
    $arOutput = array();
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
            
            $nNumSelKeyPoints = min($nMaxKeyPointsPerKF, intval($fKPSamplingRate * $nNumKeyPoints));
            
            $arIndexList = range(0, $nNumKeyPoints - 1);
            $arSelIndexList = array_rand($arIndexList, $nNumSelKeyPoints);
            
            // if($nNumKeyPoints+2 != sizeof($arRawList))
            if ($nNumKeyPoints + 2 < sizeof($arRawList))
            {
                printf("Error in SIFT data file. Size different [%d KPs - %d Rows]\n", $nNumKeyPoints, sizeof($arRawList) - 2);
                exit();
            }
            
            $nCount ++;
            continue;
        }
        
        if (! in_array($nCount, $arSelIndexList))
        {
            $nCount ++;
            continue;
        }
        $arTmp = explode(" ", $szLine);
        // 5 first values - x y a b c
        if (sizeof($arTmp) != $nNumDims + 5)
        {
            printf("Error in SIFT data file. Feature value different [%d Dims - %d Vals]\n", $nNumDims, sizeof($arTmp) - 5);
            print_r($arTmp);
            exit();
        }
        
        $szOutput = sprintf("%s", $nNumDims);
        for ($i = 0; $i < $nNumDims; $i ++)
        {
            $nIndex = $i + 5;
            
            $szOutput = $szOutput . " " . trim($arTmp[$nIndex]);
        }
        $szAnn = sprintf("%s-KP-%06d", $szAnnPrefix, $nCount - 2);
        $arOutput[] = $szOutput . " % " . $szAnn;
        $nCount ++;
    }
    
    return $arOutput;
}

// NEW VERSION --> split samples into chunks and blocks in dvf format
// New params: DataExt (dvf), DataPrefix and OutputDir
function selectKeyPointsFromKeyFrameList($szOutputDir, $szDataPrefix, $szDataExt, $szFPInputListFN, $szFPVideoListFN, $szFeatureExt, $szRootKeyFrameDir, $szRootFeatureDir, $szLocalDir, $fKPSamplingRate = 0.7, $nMaxKeyPoints = 1500000, $nMaxBlocksPerChunk = 1, $nMaxSamplesPerBlock = 2000000)
{
    global $garAppConfig;
    
    $szAppName = $garAppConfig["RAW_COLOR_SIFF_APP"];
    
    // load video list
    loadListFile($arRawList, $szFPVideoListFN);
    $arVideoList = array();
    foreach ($arRawList as $szLine)
    {
        // TRECVID2005_141 #$# 20041030_133100_MSNBC_MSNBCNEWS13_ENG #$# tv2005/devel
        $arTmp = explode("#$#", $szLine);
        $szVideoID = trim($arTmp[0]);
        $szVideoPath = trim($arTmp[2]);
        $arVideoList[$szVideoID] = $szVideoPath;
    }
    // print_r($arVideoList); exit();
    
    loadListFile($arKeyFrameList, $szFPInputListFN);
    
    $nBlockID = 0;
    $nChunkID = 0;
    
    $szFPDataOutputFN = sprintf("%s/%s-c%d-b%d.%s", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID, $szDataExt);
    $szFPAnnOutputFN = sprintf("%s/%s-c%d-b%d.ann", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID);
    
    $arKeyPointFeatureList = array();
    $arKeyPointFeatureList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
    $arKeyPointFeatureList[1] = sprintf("%s", $nMaxSamplesPerBlock); // estimated number of samples
    
    $arAnnList = array();
    $arAnnList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
    $arAnnList[1] = sprintf("%s", $nMaxSamplesPerBlock); // estimated number of samples
    
    $nNumKPs = 0;
    
    $arNewKFList = array(); // re-organized into VideoID
    foreach ($arKeyFrameList as $szLine)
    {
        $arTmp = explode("#$#", $szLine);
        $szKeyFrameID = trim($arTmp[0]);
        $szVideoID = trim($arTmp[1]);
        
        $arNewKFList[$szVideoID][$szKeyFrameID] = 1;
    }
    foreach ($arNewKFList as $szVideoID => $arKFListz)
    {
        $szVideoPath = $arVideoList[$szVideoID];
        
        // extract or copy keyframes to local dir
        $szServerKeyFrameDir = sprintf("%s/%s/%s", $szRootKeyFrameDir, $szVideoPath, $szVideoID);
        $szLocalKeyFrameDir = sprintf("%s/keyframe-5/%s/%s", $szLocalDir, $szVideoPath, $szVideoID);
        makeDir($szLocalKeyFrameDir);
        
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
        
        $szInputDir = sprintf("%s/%s/%s/%s", $szRootFeatureDir, $szFeatureExt, $szVideoPath, $szVideoID);
        
        foreach ($arKFListz as $szKeyFrameID => $nTmp)
        {
            $szCoreName = sprintf("%s.%s", $szKeyFrameID, $szFeatureExt);
            
            global $gKeyFrameImgExt;
            $szFPInputImgFN = sprintf("%s/%s.%s", $szLocalKeyFrameDir, $szKeyFrameID, $gKeyFrameImgExt);
            $szFPSIFTDataFN = sprintf("%s/%s", $szLocalDir, $szCoreName);
            
            global $arFeatureParamConfigList;
            $szFeatureConfigParam = $arFeatureParamConfigList[$szFeatureExt];
            
            extractRawSIFTFeatureForOneImage($szAppName, $szFeatureConfigParam, $szFPInputImgFN, $szFPSIFTDataFN, $szLocalDir);
            
            $szAnnPrefix = sprintf("NA %s %s", $szKeyFrameID, $szKeyFrameID);
            $arOutput = loadOneRawSIFTFile($szFPSIFTDataFN, $fKPSamplingRate, $szAnnPrefix);
            
            // print_r($arOutput);
            // break;
            
            // $arKeyPointFeatureList = array_merge($arKeyPointFeatureList, $arOutput);
            
            // split to feature and ann
            foreach ($arOutput as $szLine)
            {
                $arTmpzzz = explode("%", $szLine);
                
                $arKeyPointFeatureList[] = trim($arTmpzzz[0]);
                $arAnnList[] = trim($arTmpzzz[1]);
            }
            
            $nNumSelKPs = sizeof($arOutput);
            $nNumKPs += $nNumSelKPs;
            printf("### Total keypoints [%s] collected after adding [%s] keypoints\n", $nNumKPs, sizeof($arOutput));
            
            // log
            global $szFPLogFN;
            $arLog = array();
            $arLog[] = sprintf("###[%s] - NumKF: %s. Total: %s", $szKeyFrameID, $nNumSelKPs, $nNumKPs);
            
            saveDataFromMem2File($arLog, $szFPLogFN, "a+t");
            
            $arOutput = array();
            deleteFile($szFPSIFTDataFN);
            
            // delete .loc file
            $szFPSIFTDataFN = sprintf("%s.loc", $szFPSIFTDataFN);
            deleteFile($szFPSIFTDataFN);
            
            if ($nNumKPs >= $nMaxKeyPoints)
            {
                printf("### Reach the limit [%s]. Break\n", $nMaxKeyPoints);
                break;
            }
            
            // -2 because 2 rows are for comment line and number of samples
            $nNumSamplesInBlock = sizeof($arKeyPointFeatureList) - 2;
            if ($nNumSamplesInBlock >= $nMaxSamplesPerBlock)
            {
                printf("@@@Writing output ...\n");
                $arKeyPointFeatureList[1] = sprintf("%s", $nNumSamplesInBlock); // update the number of samples of the block
                saveDataFromMem2File($arKeyPointFeatureList, $szFPDataOutputFN, "wt");
                
                $arAnnList[1] = sprintf("%s", $nNumSamplesInBlock);
                saveDataFromMem2File($arAnnList, $szFPAnnOutputFN, "wt");
                
                // prepare for the new chunk-block
                $nBlockID ++;
                if ($nBlockID >= $nMaxBlocksPerChunk)
                {
                    // new chunk
                    $nBlockID = 0;
                    $nChunkID ++;
                }
                
                $szFPDataOutputFN = sprintf("%s/%s-c%d-b%d.%s", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID, $szDataExt);
                $szFPAnnOutputFN = sprintf("%s/%s-c%d-b%d.ann", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID);
                
                $arKeyPointFeatureList = array();
                $arKeyPointFeatureList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
                $arKeyPointFeatureList[1] = sprintf("%s", $nMaxSamplesPerBlock); // estimated number of samples
                
                $arAnnList = array();
                $arAnnList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
                $arAnnList[1] = sprintf("%s", $nMaxSamplesPerBlock); // estimated number of samples
            }
        }
        $szCmdLine = sprintf("rm -rf %s", $szLocalKeyFrameDir);
        execSysCmd($szCmdLine);
    }
    
    $nNumSamplesInBlock = sizeof($arKeyPointFeatureList) - 2;
    if ($nNumSamplesInBlock)
    {
        $arKeyPointFeatureList[1] = sprintf("%s", $nNumSamplesInBlock); // update the number of samples of the block
        saveDataFromMem2File($arKeyPointFeatureList, $szFPDataOutputFN, "wt");
        
        $arAnnList[1] = sprintf("%s", $nNumSamplesInBlock);
        saveDataFromMem2File($arAnnList, $szFPAnnOutputFN, "wt");
    }
}

function extractRawSIFTFeatureForOneImage($szAppName, $szFeatureConfigParam, $szFPInputImgFN, $szFPFeatureOutputFN, $szTmpDir)
{
    global $gKeyFrameImgExt; // jpg
                             
    // this code did not work with pgm format --> keep jpg format
    $szBaseInputName = basename($szFPInputImgFN);
    $szFPJPGInputFN = sprintf("%s/%s.%s", $szTmpDir, $szBaseInputName, $gKeyFrameImgExt);
    
    $szFPSimpleFeatureOutputFN = sprintf("%s.loc", $szFPFeatureOutputFN);
    
    //printf("File [%s]\n", $szFPInputImgFN); exit();
    if (filesize($szFPInputImgFN) <= 0)
    {
        printf("File not found [%s]\n", $szFPInputImgFN);
        return - 1;
    }
    
    if (!file_exists($szFPInputImgFN))
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
    
    if(!file_exists($szFPFeatureTmpFN2))
    {
        printf("File not found [%s]\n", $szFPFeatureTmpFN2);
        return -1;
    }
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
    //print_r($arOutput); exit();
    
    $szFPSimpleOutputFN = sprintf("%s.loc", $szFPOutputFN);
    saveDataFromMem2File($arSimpleOutput, $szFPSimpleOutputFN);
}

?>