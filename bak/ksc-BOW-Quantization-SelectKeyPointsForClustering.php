<?php

/**
 * 		@file 	ksc-BOW-Quantization-SelectKeyPointsForClustering.php
 * 		@brief 	Selecting keypoints for clustering.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Jul 2013.
 */


//*** Update Jul 08, 2012
//--> Check FeatureOutputDir for load balancing
//--> Print stats on selection of keyframes in each video
//--> Decided to use non-TV method that was used in imageclef12

// We need VideoID to find Path. For TRECVID, is is encoded in KeyFrameID
// TRECVID2005_101.shot101_1.RKF_0.Frame_4 --> TRECVID2005_101
// We need to modify this part for applying other set such as imageCLEF
/*
 // TRECVID2005_101.shot101_1.RKF_0.Frame_4
$arTmp = explode(".", $szKeyFrameID);
$szVideoID = trim($arTmp[0]);
$szVideoPath = $arVideoList[$szVideoID];

$szInputDir = sprintf("%s/%s/%s/%s",
		$szRootFeatureDir, $szFeatureExt, $szVideoPath, $szVideoID);
$szCoreName = sprintf("%s.%s", $szKeyFrameID, $szFeatureExt);
$szFPTarKeyPointFN = sprintf("%s/%s.tar.gz",
		$szInputDir, $szCoreName);
*/


/************* STEPS FOR BOW MODEL ***************
 * 	===> STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV10.php --> select keypoints from devel pat
* 	STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV10.php --> do clustering using VLFEAT vl_kmeans, L2 distance
* 	STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
* 	STEP 4: nsc-ComputeAssignmentSash-TV10/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
* 	STEP 5: nsc-ComputeSoftBOW-Grid-TV10/-SGE.php --> compute soft assignment for grid image
*/


/*  --> IMPORTANT PARAMS

$nMaxKeyPoints = intval(1500000.0);  // 1.5 M - max keypoints for clustering

$nAveKeyPointsPerKF = 1000; // average number of keypoints per key frame
$fKeyPointSamplingRate = 0.70; // percentage of keypoints of one image will be selected
$nMaxKeyFrames = intval($nMaxKeyPoints/($nAveKeyPointsPerKF*$fKeyPointSamplingRate)); // max keyframes to be selected for picking keypoints

$fVideoSamplingRate = 0.50; // percentage of videos of the set will be selected, for ImageNet, this value should be 1.0
$fShotSamplingRate = 0.2; // lower this value if we want more videos, percentage of shots of one video will be selected
$fKeyFrameSamplingRate = 0.0001; // i.e. 1KF/shot - $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFsPerShot));

$nMaxBlocksPerChunk=1; // only one chunk
$nMaxSamplesPerBlock=2000000; // larger than maxKP to ensure all keypoints in 1 chunk-block
*/

// Update Jul 08
// Customize for tvsin2012


// Update Jun 27
// Customize for imageclef2012

////////////////////////////////////////////////////
// Update Jun 17
// We need VideoID to find Path. For TRECVID, is is encoded in KeyFrameID
// TRECVID2005_101.shot101_1.RKF_0.Frame_4 --> TRECVID2005_101
// We need to modify this part for applying other set such as imageCLEF
/*
 // TRECVID2005_101.shot101_1.RKF_0.Frame_4
 $arTmp = explode(".", $szKeyFrameID);
 $szVideoID = trim($arTmp[0]);
 $szVideoPath = $arVideoList[$szVideoID];

 $szInputDir = sprintf("%s/%s/%s/%s",
 $szRootFeatureDir, $szFeatureExt, $szVideoPath, $szVideoID);
 $szCoreName = sprintf("%s.%s", $szKeyFrameID, $szFeatureExt);
 $szFPTarKeyPointFN = sprintf("%s/%s.tar.gz",
 $szInputDir, $szCoreName);
 */

// Update May 20
// Adding dense & phow

// ************* Update May 10 *************
// Adding phowhsv8, phow6 and dense3 --> luu y la giu nguyen so luong point/frame la 1000, trong thuc te thi phow6 so luong la 9344

// ************* Update Feb 21 *************
// Be careful with empty keypoints (appearing when using PHOW and DENSESIFT)  --> already checked in matlab code
// --> loose checking
// OLD: if($nNumKeyPoints+2 != sizeof($arRawList))
	// NEW:	if($nNumKeyPoints+2 < sizeof($arRawList))


		// ************* Update Feb 13 *************
		// Adding params to control the loop
		// Adding dog, dense6 and phow10

		// ************* Update Feb 13 *************
		// Select keypoints for clustering using VLFEAT
		// szTrialName = Soft-500-VL2  --> V: VLFEAT, L2: L2 distance for clustering and word assignment
		// NumKPS = 1.5M

		//  ************* Update Jan 31 *************
		// New configuration for max 500 codewords since 1000 codewords --> computational heavy
		// Changes in config
		// Max 1M keypoints --> ~1,500 keyframes
		// $fVideoSamplingRate = 0.50; // percentage of videos of the set will be selected
		// Adding shuffle function after selecting videos and shots by array_rand
		// Limit max keypoints per keyframe = 1000;

		//  ************* Update Jan 22 *************
		// Adding features harlap, heslap, haraff

		//  ************* Update Jan 06 *************
		// Prepare data for new experiments using soft assignment as described in VIREO374
		// Run for tv2005, tv2007, and tv2010; and for harhes and hesaff.

		// ************* Update Dec 22 *************
		// Modify the path of clus dir

		//  ************* Update Nov 30 *************
		// Change $szFPInputListFN = sprintf("%s/BoW.SelKeyFrame.%s.%s.%s.lst", $szOutputDir, $szTrialName, $szPatName, $szFeatureExt);
		// Adjust params for reducinig the number of chunks: 5 blocks/chunk, each block 500K points --> each chunk -> 2.5M points
		// Running time: ~ 100K/min --> 20M --> 200 mins ~ 3.5 hours

		//  ************* Update Nov 25 *************
		// Make changes for scalability, output data is converted to dvf format that is ready for clustering
		// Previous version saves all data in one file, which is not scalable when the number of samples reaches several tens of mils
		// Estimation:
		// 	+ Number of chunks --> 1/4 of available main memory, if using per900b --> max 128 GB
		//	+ Number of blocks/chunk --> 1/10 chunk size.
		// This version
		//	+ --> 1 chunk ~ 1 millions of points.
		//	+ --> 1 block ~ 100,000 points.
		// 	+ --> 10 blocks/chunk

		// ************* Update 03 Oct *************
		// Rename to nsc-BOW-SelectKeyPointsForClustering
		//

		/////////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

///////////////////////////// THIS PART FOR CUSTOMIZATION /////////////////
//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

// training pat
$arPatList = array("devel-nistNew"); //*** CHANGED ***

$arFeatureList = array("nsc.raw.harhes.sift",
		"nsc.raw.harlap.sift",
		"nsc.raw.heslap.sift",
		//						"nsc.raw.hesaff.sift",
		"nsc.raw.haraff.sift",
		"nsc.raw.dense4.sift",
		"nsc.raw.dense6.sift",
		"nsc.raw.dense8.sift",
		//						"nsc.raw.dense10.sift",
		"nsc.raw.phow6.sift",
		"nsc.raw.phow8.sift",
		"nsc.raw.phow10.sift",
		"nsc.raw.phow12.sift",
		//						"nsc.raw.phow14.sift",
		"nsc.raw.dense6mul.oppsift",
		"nsc.raw.dense6mul.sift",
		"nsc.raw.dense6mul.rgsift",
		"nsc.raw.dense6mul.rgbsift",
		"nsc.raw.dense6mul.csift",

		"nsc.raw.harlap6mul.rgbsift",
		
		"nsc.raw.dense4mul.oppsift",
		"nsc.raw.dense4mul.sift",
		"nsc.raw.dense4mul.rgsift",
		"nsc.raw.dense4mul.rgbsift",
		"nsc.raw.dense4mul.csift",
);

$szTargetPatName = "devel-nistNew";
$szTargetFeatureExt = "nsc.raw.dense6mul.rgbsift";

/// !!! IMPORTANT
$nMaxKeyPoints = intval(1500000.0);  // 1.5 M - max keypoints for clustering

// average number of keypoints per key frame --> used in function loadOneRawSIFTFile
$nAveKeyPointsPerKF = 1000; 
$fKeyPointSamplingRate = 0.70; // percentage of keypoints of one image will be selected

// max keyframes to be selected for picking keypoints
// use weight = 1.5 to pick more number of keyframes to ensure min selected KP = $nMaxKeyPoints
// some keyframes --> no keypoints (ie. blank/black frames)
$nMaxKeyFrames = intval(1.5 * $nMaxKeyPoints/($nAveKeyPointsPerKF*$fKeyPointSamplingRate))+1; 

// for trecvid2012 --> the number of videos is REDUCED to 200
// shot information can not be inferred from keyframeID --> one shot = one keyframes
$fVideoSamplingRate = 1.0; // percentage of videos of the set will be selected
$fShotSamplingRate = 0.01; // lower this value if we want more videos, percentage of shots of one video will be selected
$fKeyFrameSamplingRate = 0.0001; // i.e. 1KF/shot - $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFsPerShot));

// Estimation
// Max KeyFrames = 1.5M / (1000 * 0.7) = 2K KF
// Number of videos = 200 --> Number of KF per video ($fVideoSamplingRate = 1.0) = 2K / 200 = 10 (QUOTA)
// Number of shots per video (by parsing .RKF) ~ 400K (of devel set 2012) /200 (videos - new organization) = 2K
// Number of KF per shot ~ 1KF
// --> if ($fShotSamplingRate = 0.01) --> 2K * 0.01 = 20 (10 (QUOTA))

$nMaxBlocksPerChunk=1; // only one chunk
$nMaxSamplesPerBlock=2000000; // larger than maxKP to ensure all keypoints in 1 chunk-block

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////

if($argc != 3)
{
	printf("Usage: %s <DevPatName> <RawFeatureExt>\n", $argv[0]);
	printf("Usage: %s %s %s\n", $argv[0], $szTargetPatName, $szTargetFeatureExt);
	exit();
}

$szTargetPatName = $argv[1];
$szTargetFeatureExt = $argv[2];


/// !!! IMPORTANT
//$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szTargetFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);

// Update Nov 25, 2011
$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig
$szTmpDir = sprintf("%s/SelectKeyPointForClustering", $szLocalTmpDir);
makeDir($szTmpDir);

foreach($arPatList as $szPatName)
{
	if($szTargetPatName != $szPatName)
	{
		printf("Skipping [%s] ...\n", $szPatName);
		continue;
	}
	$szFPVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szPatName);

	foreach($arFeatureList as $szFeatureExt)
	{
		if($szTargetFeatureExt != $szFeatureExt)
		{
			printf("Skipping [%s] ...\n", $szFeatureExt);
			continue;
		}
		$szOutputDir = sprintf("%s/bow.codebook.%s.%s/%s/data", $szRootFeatureDir, $szTrialName, $szPatName, $szFeatureExt);
		makeDir($szOutputDir);
		$szDataPrefix = sprintf("%s.%s.%s", $szTrialName, $szPatName, $szFeatureExt);
		$szDataExt = "dvf";

		$arAllKeyFrameList = selectKeyFrames($nMaxKeyFrames,
				$fVideoSamplingRate, $fShotSamplingRate,
				$fKeyFrameSamplingRate,
				$szFPVideoListFN, $szRootMetaDataDir, $szFeatureExt);
			
		$szLocalTmpDir = sprintf("%s/%s", $szTmpDir, $szPatName);
		makeDir($szLocalTmpDir);

		$szFPInputListFN = sprintf("%s/BoW.SelKeyFrame.%s.%s.%s.lst", $szOutputDir, $szTrialName, $szPatName, $szFeatureExt);
//		saveDataFromMem2File(array_keys($arAllKeyFrameList), $szFPInputListFN);

		//*** Changed for IMAGENET
		saveDataFromMem2File($arAllKeyFrameList, $szFPInputListFN);
		//*** Changed for IMAGENET

		// print stats
		global $arStatVideoList;
		$nCountzz = 1;
		ksort($arStatVideoList);
		$arOutput = array();
		foreach($arStatVideoList as $szVideoID => $arKFList)
		{
			$arOutput[] = sprintf("###%d. %s, %s", $nCountzz, $szVideoID, sizeof($arKFList));
			$nCountzz++;
		}
		$szFPOutputStatFN = sprintf("%s.csv", $szFPInputListFN);
		saveDataFromMem2File($arOutput, $szFPOutputStatFN);
		
		
		selectKeyPointsFromKeyFrameList($szOutputDir, $szDataPrefix, $szDataExt,
				$szFPInputListFN, $szFPVideoListFN,
				$szFeatureExt, $szRootFeatureDir, $szLocalTmpDir, $fKeyPointSamplingRate, $nMaxKeyPoints,
				$nMaxBlocksPerChunk, $nMaxSamplesPerBlock);
	}
}

////////////////////////////////////// FUNCTIONS //////////////////////////
/**
 * 	Select keypoints from a set of images for clustering (to form codebook).
 *
 * 	Selection params:
 * 		+ nMaxKeyFrames (default 2,000):
 * 		+ fVideoSamplingRate (default 1.0): percentage of videos of the set will be selected
 * 		+ fShotSamplingRate (default 1.0): percentage of shots of one video will be selected
 * 		+ $fKeyFrameSamplingRate (default 1/50): percentage of keyframes per shot
 * 		+ fKeyPointSamplingRate (default: 0.75): percentage of keypoints of one image will be selected
 */

// szFPVideoListFN --> arVideoList[videoID] = videoPath
// RootMetaData + videoPath + /videoID.prg
// RootFeatureDir + FeatureExt + videoPath + /videoID.featureExt (.tar.gz)
function selectKeyFrames($nMaxKeyFrames,
		$fVideoSamplingRate, $fShotSamplingRate,
		$fKeyFrameSamplingRate,
		$szFPVideoListFN, $szRootMetaDataDir, $szFeatureExt)
{
	
	global $arStatVideoList;
	$arStatVideoList = array(); // for statistics 
	
	// load video list
	loadListFile($arRawList, $szFPVideoListFN);
	$arVideoList = array();
	foreach($arRawList as $szLine)
	{
		// TRECVID2005_141 #$# 20041030_133100_MSNBC_MSNBCNEWS13_ENG #$# tv2005/devel
		$arTmp = explode("#$#", $szLine);
		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);
		$arVideoList[$szVideoID] = $szVideoPath;
	}

	$nTotalVideos = sizeof($arVideoList);
	$nNumSelVideos = intval(max(1, $fVideoSamplingRate*$nTotalVideos));

	$arAllKeyFrameList = array();

	$arSelVideoList = array();
	if($nNumSelVideos < 2)
	{
		$arSelVideoList[] = array_rand($arVideoList, $nNumSelVideos);
	}
	else
	{
		$arSelVideoList = array_rand($arVideoList, $nNumSelVideos);
	}

	shuffle($arSelVideoList);
	print_r($arSelVideoList);
	$nFinish = 0;
	foreach($arSelVideoList as $szVideoID)
	{
		$szVideoPath = $arVideoList[$szVideoID];

		$szFPKeyFrameListFN = sprintf("%s/%s/%s.prg",
				$szRootMetaDataDir, $szVideoPath, $szVideoID);

		if(!file_exists($szFPKeyFrameListFN))
		{
			printf("#@@@# File [%s] not found!", $szFPKeyFrameListFN);
			continue;
		}

		loadListFile($arKFRawList, $szFPKeyFrameListFN);

		// TV case: TRECVID2005_101.shot101_1.RKF_0.Frame_4
		// Non-TV case: xxxxImagexxxx
		$arShotList = array();
		foreach($arKFRawList as $szKeyFrameID)
		{
			$arTmp = explode(".RKF", $szKeyFrameID);
			$szShotID = trim($arTmp[0]);

			// *** Changed for IMAGENET
			if(!strstr($szShotID, "shot"))
			{
				$szShotID = sprintf("shotNA"); // --> all keyframes in ONE shot
			}
			// *** Changed for IMAGENET
			$arShotList[$szShotID][$szKeyFrameID] = 1;
		}
		$nNumShots = sizeof($arShotList);
		$nNumSelShots = intval(max(1, $fShotSamplingRate*$nNumShots));

		$arSelShotList = array();
		if($nNumSelShots<2)
		{
			$arSelShotList[] = array_rand($arShotList, $nNumSelShots);
		}
		else
		{
			$arSelShotList = array_rand($arShotList, $nNumSelShots);
		}

		shuffle($arSelShotList);
		print_r($arSelShotList);

		foreach($arSelShotList as $szShotID)
		{
			$arKeyFrameList = $arShotList[$szShotID];
			$nNumKFs = sizeof($arKeyFrameList);

			$nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFs));
			$arSelKeyFrameList = array();
			if($nNumSelKFs < 2)
			{
				$arSelKeyFrameList[] = array_rand($arKeyFrameList, $nNumSelKFs);
			}
			else
			{
				$arSelKeyFrameList = array_rand($arKeyFrameList, $nNumSelKFs);
			}
				
			shuffle($arSelKeyFrameList);
			// print_r($arSelKeyFrameList); exit();
			foreach($arSelKeyFrameList as $szKeyFrameID)
			{
				// printf("###. %s\n", $szKeyFrameID);
			// $arAllKeyFrameList[$szKeyFrameID] = 1;
				
				//*** Changed for IMAGENET
				$arAllKeyFrameList[$szKeyFrameID] = 
					sprintf("%s #$# %s #$# %s", $szKeyFrameID, $szVideoID, $szShotID);
				//*** Changed for IMAGENET
				$arStatVideoList[$szVideoID][$szKeyFrameID] = 1;
		
				if(sizeof($arAllKeyFrameList) >= $nMaxKeyFrames )
				{
					$nFinish = 1;
					break; // keyframe selection
				}
			}
			if($nFinish)
			{
				break; // shot selection
			}
		}

		if($nFinish)
		{
			break; // video selection
		}
	}
	
	// print_r($arAllKeyFrameList); exit();
	return $arAllKeyFrameList;
}


function loadOneRawSIFTFile($szFPSIFTDataFN, $fKPSamplingRate=0.5, $szAnnPrefix = "")
{
	global $nAveKeyPointsPerKF;
	$nMaxKeyPointsPerKF = $nAveKeyPointsPerKF;  // 1000

	loadListFile($arRawList, $szFPSIFTDataFN);

	$nCount = 0;
	// print_r($arRawList);
	$arOutput = array();
	foreach($arRawList as $szLine)
	{
		// printf("%s\n", $szLine);
		// first row - numDims 128
		if($nCount == 0)
		{
			$nNumDims = intval($szLine);
			$nCount++;
			continue;
		}

		// second row  - numKPs
		if($nCount == 1)
		{
			$nNumKeyPoints = intval($szLine);

			$nNumSelKeyPoints = min($nMaxKeyPointsPerKF, intval($fKPSamplingRate*$nNumKeyPoints));
				
			$arIndexList = range(0, $nNumKeyPoints-1);
			$arSelIndexList = array_rand($arIndexList, $nNumSelKeyPoints);

			//if($nNumKeyPoints+2 != sizeof($arRawList))
			if($nNumKeyPoints+2 < sizeof($arRawList))
			{
				printf("Error in SIFT data file. Size different [%d KPs - %d Rows]\n", $nNumKeyPoints, sizeof($arRawList)-2);
				exit();
			}

			$nCount++;
			continue;
		}

		if(!in_array($nCount, $arSelIndexList))
		{
			$nCount++;
			continue;
		}
		$arTmp = explode(" ", $szLine);
		// 5 first values - x y a b c
		if(sizeof($arTmp) != $nNumDims + 5)
		{
			printf("Error in SIFT data file. Feature value different [%d Dims - %d Vals]\n", $nNumDims, sizeof($arTmp)-5);
			print_r($arTmp);
			exit();
		}

		$szOutput = sprintf("%s", $nNumDims);
		for($i=0; $i<$nNumDims; $i++)
		{
			$nIndex = $i+5;

			$szOutput = $szOutput . " " . trim($arTmp[$nIndex]);

		}
		$szAnn = sprintf("%s-KP-%06d", $szAnnPrefix, $nCount-2);
		$arOutput [] = $szOutput . " % " . $szAnn;
		$nCount++;
	}

	return $arOutput;
}

/*  OLD VERSION --> Save all samples in 1 file
 // load and organize into arList[videoID]
function selectKeyPointsFromKeyFrameList($szFPOutputFN,
		$szFPInputListFN, $szFPVideoListFN,
		$szFeatureExt, $szRootFeatureDir, $szLocalDir, $fKPSamplingRate=0.5, $nMaxKeyPoints=1e6)
{
// load video list
loadListFile($arRawList, $szFPVideoListFN);
$arVideoList = array();
foreach($arRawList as $szLine)
{
// TRECVID2005_141 #$# 20041030_133100_MSNBC_MSNBCNEWS13_ENG #$# tv2005/devel
$arTmp = explode("#$#", $szLine);
$szVideoID = trim($arTmp[0]);
$szVideoPath = trim($arTmp[2]);
$arVideoList[$szVideoID] = $szVideoPath;
}

loadListFile($arKeyFrameList, $szFPInputListFN);

$nBlockID = 0;
$nChunkID = 0;

$arKeyPointFeatureList = array();
$arKeyPointFeatureList[] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
saveDataFromMem2File($arKeyPointFeatureList, $szFPOutputFN, "wt");

// adding after cache reaches 10,000 features
$arKeyPointFeatureList = array();
$nNumKPs = 0;
foreach($arKeyFrameList as $szKeyFrameID)
{
// TRECVID2005_101.shot101_1.RKF_0.Frame_4
$arTmp = explode(".", $szKeyFrameID);
$szVideoID = trim($arTmp[0]);
$szVideoPath = $arVideoList[$szVideoID];

$szInputDir = sprintf("%s/%s/%s/%s",
		$szRootFeatureDir, $szFeatureExt, $szVideoPath, $szVideoID);
$szCoreName = sprintf("%s.%s", $szKeyFrameID, $szFeatureExt);
$szFPTarKeyPointFN = sprintf("%s/%s.tar.gz",
		$szInputDir, $szCoreName);
if(file_exists($szFPTarKeyPointFN))
{
//printf("[%s]. OK\n", $szFPTarKeyPointFN);

$szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTarKeyPointFN, $szLocalDir);
execSysCmd($szCmdLine);

$szFPSIFTDataFN = sprintf("%s/%s", $szLocalDir, $szCoreName);

$szAnnPrefix = sprintf("NA %s %s", $szKeyFrameID, $szKeyFrameID);
$arOutput = loadOneRawSIFTFile($szFPSIFTDataFN, $fKPSamplingRate, $szAnnPrefix);

//		print_r($arOutput);
//		break;

$arKeyPointFeatureList = array_merge($arKeyPointFeatureList, $arOutput);

deleteFile($szFPSIFTDataFN);

$nNumKPs += sizeof($arOutput);
printf("### Total keypoints [%s] collected after adding [%s] keypoints\n", $nNumKPs, sizeof($arOutput));
if($nNumKPs > $nMaxKeyPoints)
{
printf("### Reach the limit [%s]. Break\n", $nMaxKeyPoints);
break;
}

if(sizeof($arKeyPointFeatureList) > 10000)
{
printf("Writing output ...\n");
saveDataFromMem2File($arKeyPointFeatureList, $szFPOutputFN, "a+t");
$arKeyPointFeatureList = array();
}
}
else
{
printf("[%s]. NO OK\n", $szFPTarKeyPointFN);
}
}
saveDataFromMem2File($arKeyPointFeatureList, $szFPOutputFN, "a+t");
}
*/

// NEW VERSION --> split samples into chunks and blocks in dvf format
// New params: DataExt (dvf), DataPrefix and OutputDir
function selectKeyPointsFromKeyFrameList($szOutputDir, $szDataPrefix, $szDataExt,
		$szFPInputListFN, $szFPVideoListFN,
		$szFeatureExt, $szRootFeatureDir, $szLocalDir, $fKPSamplingRate=0.5, $nMaxKeyPoints=1e6,
		$nMaxBlocksPerChunk=10, $nMaxSamplesPerBlock=100000)
{
	// load video list
	loadListFile($arRawList, $szFPVideoListFN);
	$arVideoList = array();
	foreach($arRawList as $szLine)
	{
		// TRECVID2005_141 #$# 20041030_133100_MSNBC_MSNBCNEWS13_ENG #$# tv2005/devel
		$arTmp = explode("#$#", $szLine);
		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);
		$arVideoList[$szVideoID] = $szVideoPath;
	}
	//print_r($arVideoList); exit();
	
	loadListFile($arKeyFrameList, $szFPInputListFN);

	$nBlockID = 0;
	$nChunkID = 0;

	$szFPDataOutputFN = sprintf("%s/%s-c%d-b%d.%s", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID, $szDataExt);
	$szFPAnnOutputFN = sprintf("%s/%s-c%d-b%d.ann", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID);

	$arKeyPointFeatureList = array();
	$arKeyPointFeatureList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
	$arKeyPointFeatureList[1] = sprintf("%s", $nMaxSamplesPerBlock); //  estimated number of samples

	$arAnnList = array();
	$arAnnList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
	$arAnnList[1] = sprintf("%s", $nMaxSamplesPerBlock); //  estimated number of samples

	$nNumKPs = 0;
	//foreach($arKeyFrameList as $szKeyFrameID)

	//*** Changed for IMAGENET
	foreach($arKeyFrameList as $szLine)
	//*** Changed for IMAGENET
	{
		// TRECVID2005_101.shot101_1.RKF_0.Frame_4
		//$arTmp = explode(".", $szKeyFrameID);
		//$szVideoID = trim($arTmp[0]);
		//$szVideoPath = $arVideoList[$szVideoID];

		//*** Changed for IMAGENET
		$arTmp = explode("#$#", $szLine);
		$szKeyFrameID = trim($arTmp[0]);
		$szVideoID = trim($arTmp[1]);
		$szVideoPath = $arVideoList[$szVideoID];
		//*** Changed for IMAGENET
		$szInputDir = sprintf("%s/%s/%s/%s",
				$szRootFeatureDir, $szFeatureExt, $szVideoPath, $szVideoID);
		$szCoreName = sprintf("%s.%s", $szKeyFrameID, $szFeatureExt);
		$szFPTarKeyPointFN = sprintf("%s/%s.tar.gz",
				$szInputDir, $szCoreName);
		if(file_exists($szFPTarKeyPointFN))
		{
			//printf("[%s]. OK\n", $szFPTarKeyPointFN);

			$szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTarKeyPointFN, $szLocalDir);
			execSysCmd($szCmdLine);

			$szFPSIFTDataFN = sprintf("%s/%s", $szLocalDir, $szCoreName);

			$szAnnPrefix = sprintf("NA %s %s", $szKeyFrameID, $szKeyFrameID);
			$arOutput = loadOneRawSIFTFile($szFPSIFTDataFN, $fKPSamplingRate, $szAnnPrefix);

			//		print_r($arOutput);
			//		break;

			//$arKeyPointFeatureList = array_merge($arKeyPointFeatureList, $arOutput);
				
			// split to feature and ann
			foreach($arOutput as $szLine)
			{
				$arTmpzzz = explode("%", $szLine);

				$arKeyPointFeatureList[] = trim($arTmpzzz[0]);
				$arAnnList[] = trim($arTmpzzz[1]);
			}

			$nNumKPs += sizeof($arOutput);
			printf("### Total keypoints [%s] collected after adding [%s] keypoints\n", $nNumKPs, sizeof($arOutput));
				
			$arOutput = array();
			deleteFile($szFPSIFTDataFN);
				
			if($nNumKPs >= $nMaxKeyPoints)
			{
				printf("### Reach the limit [%s]. Break\n", $nMaxKeyPoints);
				break;
			}

			// -2 because 2 rows are for comment line and number of samples
			$nNumSamplesInBlock = sizeof($arKeyPointFeatureList)-2;
			if($nNumSamplesInBlock >= $nMaxSamplesPerBlock)
			{
				printf("@@@Writing output ...\n");
				$arKeyPointFeatureList[1] = sprintf("%s", $nNumSamplesInBlock); // update the number of samples of the block
				saveDataFromMem2File($arKeyPointFeatureList, $szFPDataOutputFN, "wt");

				$arAnnList[1] = sprintf("%s", $nNumSamplesInBlock);
				saveDataFromMem2File($arAnnList, $szFPAnnOutputFN, "wt");

				// prepare for the new chunk-block
				$nBlockID++;
				if($nBlockID >= $nMaxBlocksPerChunk)
				{
					// new chunk
					$nBlockID = 0;
					$nChunkID++;
				}

				$szFPDataOutputFN = sprintf("%s/%s-c%d-b%d.%s", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID, $szDataExt);
				$szFPAnnOutputFN = sprintf("%s/%s-c%d-b%d.ann", $szOutputDir, $szDataPrefix, $nChunkID, $nBlockID);

				$arKeyPointFeatureList = array();
				$arKeyPointFeatureList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
				$arKeyPointFeatureList[1] = sprintf("%s", $nMaxSamplesPerBlock); // estimated number of samples

				$arAnnList = array();
				$arAnnList[0] = sprintf("%% Feature: %s - Max Keypoints: %s - List: %s", $szFeatureExt, $nMaxKeyPoints, $szFPInputListFN);
				$arAnnList[1] = sprintf("%s", $nMaxSamplesPerBlock); //  estimated number of samples

			}
		}
		else
		{
			printf("[%s]. NO OK\n", $szFPTarKeyPointFN);
		}
	}

	$nNumSamplesInBlock = sizeof($arKeyPointFeatureList)-2;
	if($nNumSamplesInBlock)
	{
		$arKeyPointFeatureList[1] = sprintf("%s", $nNumSamplesInBlock); // update the number of samples of the block
		saveDataFromMem2File($arKeyPointFeatureList, $szFPDataOutputFN, "wt");

		$arAnnList[1] = sprintf("%s", $nNumSamplesInBlock);
		saveDataFromMem2File($arAnnList, $szFPAnnOutputFN, "wt");
	}
}

?>