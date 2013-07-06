<?php

/**
 * 		@file 	ksc-BOW-ComputeSoftBOW-GridAll.php
 * 		@brief 	Compute soft assignment BOW with spatial setting (i.e. GRID).
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Jul 2013.
 */

//************ Jul 16, 2012 ***************
// PROBLEM: local dir (/local/ledduy) --> not enough space
// 		// only keep top $nMaxCacheFilesToKeep files into local dir
//		if(sizeof($arCacheLocalFileList) >= $nMaxCacheFilesToKeep)
//		{
//			deleteFile($szFPLocalRawSIFTFN);
//		}




//*** Update Jul 16, 2012
//--> Adding WARNING for zero file size
//--> Adding code for checking existing files to ensure the number of keyframes of one video = the number of lines in label.lst file

// When running on the grid engine, take a lot of time for reading data (keypoint assignment)
//--> Compute features for ALL grids ONCE to reduce processing time (mainly reading & transferring data)
//**** Processing Time ********
//--> Load label list file for one video program --> each line --> one keyframe
//--> Load raw keypoint file for each keyframe to get the location of each keypoint


// !!! IMPORTANT !!!!
// --> do not use FrameWidth and FrameHeight as params
// .prgx --> has information on width and height, computed by nsc-BOW-GetKeyFrameSize
// $szFPKeyFrameListFN = sprintf("%s/%s/%s.prgx", $szRootMetaDataDir, $szVideoPath, $szVideoID);


// Update Aug 07
// Adding log file

/////// THIS PART MUST BE SYNC WITH ComputeSoftBOW
//$gnHavingResized = 1;
//$gnMaxFrameWidth = 350;
//$gnMaxFrameHeight = 350;
//$gszResizeOption = sprintf("-resize '%sx%s>'", $gnMaxFrameWidth, $gnMaxFrameHeight); // to ensure 352 is the width after shrinking
//////////////////////////////////////////////////

////////////////////////////////////////////////////////
/*
 * // Format of raw SIFT feature file
 * // First row: NumDims
 * // Second row: NumKeyPoints
 * // Third row ...: x y -1 -1 -1 V1 V2 ... V_NumDims
 * // x, y is used for SoftBOW-Grid --> location of the keypoint
 */

/************* STEPS FOR BOW MODEL ***************
 * 	STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV10.php --> select keypoints from devel pat
 * 	STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV10.php --> do clustering using VLFEAT vl_kmeans, L2 distance
 * 	STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
 * 	STEP 4: nsc-ComputeAssignmentSash-TV10/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
 * 	===> STEP 5: nsc-ComputeSoftBOW-Grid-TV10/-SGE.php --> compute soft assignment for grid image
 */
// MaxCodeBookSize = 600


// ************** Update Feb 27 **************
// Global param to skip existing files,
// $gSkippExistingFiles = 1;

// **************** Update Feb 21 ****************
// $nMaxCodeBookSize = 600;  (for using 500-d codebook of 1x1 grid)

// **************** Update Feb 02 ****************
// changed to Soft-500
// to ensure starting label > 0 --> 	$nLabelPlus = $nLabel+1;

// ****************  Update Jan 21 - IMPORTANT ****************
// Change the value sim(j,t) in soft weight, instead of raw Euclidean distance returned by SashAssignment, using normalized value
// fNorm = exp(-gamma*D), where D is the raw distance (i.e. Euclidean distance) return by SashAssignment, gamma = 0.0625 (1/16)
// VIREO used cosine distance, whose value ranges from [0, 1], the higher score, the closer
// the higher D (i.e. the further in Euclidean distance), the smaller fNorm

// Change the feature ext to norm1x3, norm2x2
//	$szOutputFeatureExt = sprintf("%s.norm%dx%d", $szInputFeatureExt, $nNumRows, $nNumCols);

// Fixed bugs in rect - found when running on 1x3 grid


// ****************  Update Jan 17 ****************
// Copied from nsc-BOW-ComputeSoftBOW-TV10.php
// Load both files: labels (assigned for each keypoint) and raw (having coord info)
// This code requires correct frame size. If incorrect one is found, the rect is assigned to zero-index

/*
 * 	General description
 *
 * 	1. For each image, extract raw SIFT, for example, nsc.raw.harhes.sift
 *
 *  2. Compute codebook from raw feature, for example, SimpleSoft-1.tv2010.devel-nist (tv2010.devel-nist is used to select keypoints for building the codebook)
 *
 *  3. Compute label assignment, for example, nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist
 *
 *  4. Compute soft-BOW using the labels assignment in nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist (1x1 grid),
 *  output is the same name (i.e. nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist)
 *
 *  5. Compute soft-BOW with grid using the label assignment in nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist,
 *  output is in nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist.mxn (m is #row, n is #cols)
 *
 */

// THIS code is 5.

////////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

//$szRootFeatureInputDir = sprintf("%s/feature/keyframe-5", $szRootDir);

$gSkippExistingFiles = 1;

//$szFPLogFN = "ksc-BOW-ComputeSoftBOW-Grid.log";

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////

$szPatName = $argv[1]; // tv2007.devel
$szInputFeatureExt = "nsc.bow.dense6mul.rgbsift.Soft500-VL2.devel-nistNew";
$szInputRawFeatureExt = "nsc.raw.dense6mul.rgbsift";
$nStartID = 0; // 0
$nEndID = 1; // 1

if($argc != 6)
{
	printf("Usage: %s <SrcPatName> <BOWFeatureExt> <RawFeatureExt> <Start> <End>\n", $argv[0]);
	printf("Usage: %s %s %s %s %s %s\n", $argv[0], $szPatName, $szInputFeatureExt, $szInputRawFeatureExt, $nStartID, $nEndID);
	exit();
}

$szPatName = $argv[1]; // tv2007.devel
$szInputFeatureExt = $argv[2];
$szInputRawFeatureExt = $argv[3];
$nStartID = intval($argv[4]); // 0
$nEndID = intval($argv[5]); // 1

$szFPLogFN = sprintf("ksc-BOW-ComputeSoftBOW-GridAll-%s.log", $szInputRawFeatureExt); // *** CHANGED ***
//$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szInputRawFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);
$szRootFeatureInputDir = $szRootFeatureDir;

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");

$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig

$szTmpDir = sprintf("%s/%s.bow.computeSoftHistWithGridAll/%s/%s-%s-%d-%d", $szLocalTmpDir,  $szScriptBaseName,
		$szPatName, $szInputFeatureExt, $szInputRawFeatureExt, $nStartID, $nEndID);
makeDir($szTmpDir);

$szFPVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szPatName);

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]-[%s]",
		$szStartTime,
		$argv[1], $argv[2], $argv[3], $argv[4], $argv[5]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

computeSoftBOWHistogramWithGridForOnePat($szTmpDir,
		$szRootFeatureInputDir,
		$szRootMetaDataDir, $szFPVideoListFN,
		$szInputFeatureExt, $szInputRawFeatureExt,
		$nMaxCodeBookSize,
		$nStartID, $nEndID);

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]",
		$szStartTime, $szFinishTime,
		$argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

/////////////////////////////// FUNCTIONS //////////////////////////////

function getResizedFrameSize($nFrameWidth, $nFrameHeight, $nMaxFrameWidth=350, $nMaxFrameHeight=350)
{
	$fScaleX = 	$nMaxFrameWidth*1.0/$nFrameWidth;
	$fScaleY = 	$nMaxFrameHeight*1.0/$nFrameHeight;

	$nNewFrameWidth = $nFrameWidth;
	$nNewFrameHeight = $nFrameHeight;
	if(($nFrameWidth > $nMaxFrameWidth) || ($nFrameHeight > $nMaxFrameHeight))
	{
		// try scale X
		$nNewFrameWidth = round($nFrameWidth*$fScaleX);
		$nNewFrameHeight = round($nFrameHeight*$fScaleX);

		if(($nNewFrameWidth > $nMaxFrameWidth) || ($nNewFrameHeight > $nMaxFrameHeight))
		{
			// try scale Y
			$nNewFrameWidth = round($nFrameWidth*$fScaleY);
			$nNewFrameHeight = round($nFrameHeight*$fScaleY);
		}
	}

	$arOutput = array();
	$arOutput['width'] = $nNewFrameWidth;
	$arOutput['height'] = $nNewFrameHeight;

	return $arOutput;
}

function findRectIndex(&$arRect, $fX, $fY)
{
	foreach($arRect as $nRectIndex => $rRect)
	{
		if($fX>=$rRect['left'] && $fX<=$rRect['right']
				&& $fY>=$rRect['top'] && $fY<=$rRect['bottom'])
		{
			return $nRectIndex;
		}
	}

	printf("Error in finding rect ...\n");
	print_r($arRect);
	printf("\n [%s, %s]\n", $fX, $fY);

	return 0;  // to avoid trouble of incorrect frame size
}

// return rect index for each point
function parseOneRawSIFTFile2Grid($szFPSIFTDataFN, $nNumRows=2, $nNumCols=2, $nFrameWidth=320, $nFrameHeight=240)
{
	//  build rect info
	// size of one rect (i.e. sub region)
	$nRectWidth = intval($nFrameWidth/$nNumCols);
	$nRectHeight = intval($nFrameHeight/$nNumRows);

	// for 2x2 grid --> 0 -> 2 -> 1 -> 3 (aligned left)
	$nRectIndex = 0;
	for($i=0; $i<$nNumRows; $i++)
	{
		for($j=0; $j<$nNumCols; $j++)
		{
			$arRect[$nRectIndex]['left'] = $nRectWidth*$j;
			$arRect[$nRectIndex]['top'] = $nRectHeight*$i;
			$arRect[$nRectIndex]['right'] = $nRectWidth*$j+($nRectWidth-1);
			$arRect[$nRectIndex]['bottom'] = $nRectHeight*$i + ($nRectHeight-1);
			$nRectIndex++;
		}
	}


	// find rect index for each keypoint
	loadListFile($arRawList, $szFPSIFTDataFN);

	$nCount = 0;
	$arOutput = array();
	$nPointIndex = 0;
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

			//			if($nNumKeyPoints+2 != sizeof($arRawList))
			if($nNumKeyPoints+2 < sizeof($arRawList))
			{
				printf("Error in SIFT data file\n");
				exit();
			}

			$nCount++;
			continue;
		}

		$arTmp = explode(" ", $szLine);
		// 5 first values - x y a b c
		if(sizeof($arTmp) != $nNumDims + 5)
		{
			printf("Error in SIFT data file\n");
			exit();
		}

		// convert to int (from float)
		$fX = intval($arTmp[0]);
		$fY = intval($arTmp[1]);

		$arOutput[$nPointIndex] = findRectIndex($arRect, $fX, $fY);
		$nPointIndex++;
	}

	return $arOutput;
}

// fNorm = exp(-fGamma*fRawScore)
// fGamma is set empirically, 1/1024 = 0.0009765625
function normalizeWeight($fRawScore, $fGamma = 0.0009765625)
{
	return (exp(-$fGamma*$fRawScore));
}

// Updated Jun 16
function loadKeyFrameSize($szFPKeyFrameListFN)
{
	loadListFile($arRawList, $szFPKeyFrameListFN);

	$arOutput = array();

	foreach($arRawList as $szLine)
	{
		$arTmp = explode("#$#", $szLine);
		if(sizeof($arTmp) !=3)
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

// NEW!!! -->No longer use nFrameWidth & nFrameHeight as params --> use mapping in .prgx
// $szFPInputLabelFN --> labels of keypoints --> each line is for one keyframe
// $szFPInputRawSIFTFN --> raw info of keypoints (including x y a b c 128-dim SIFT) --> each file is for one keyframe
function computeSoftWeightingHistogramWithGrid(
		$szFPKeyFrameListFN,
		$szFPOutputFN,
		$szFPInputLabelFN,
		$szInputRawFeatureDir, $szInputRawFeatureExt,
		$szLocalDir,
		$nNumRows=2, $nNumCols=2,
		$nMaxCodeBookSize = 2000 // for concatenating feature vectors of sub regions
)
{
	// load mapping KeyFrame and WxH
	$arKeyFrameSizeLUT = loadKeyFrameSize($szFPKeyFrameListFN);

	$nNumLines = loadListFile($arRawList, $szFPInputLabelFN);

	$nNumCommentLines = countNumCommentLines($arRawList);

	$arOutput = array();
	for($i=0; $i<$nNumCommentLines; $i++)
	{
		$arOutput[] = $arRawList[$i];
	}


	// this list to store a list of files to be kept in local dir
	// we will flush out if the cache is FULL to prevent /local/ledduy dir is FULL
	$arCacheLocalFileList = array();

	// 1 file --> 4MBx3 (after extraction)
	// --> 12 threads --> 4MBx3x12 = 144MB in local
	$nMaxCacheFilesToKeep = 400;  // ~ 60GB --> bc2hosts/bc5hosts OK

	for($i=$nNumCommentLines; $i<$nNumLines; $i++)
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

		if(!isset($arKeyFrameSizeLUT[$szKeyFrameID]))
		{
			printf("### Serious error in KeyFrameSize LUT\n");
			exit();
		}

		// !!! IMPORTANT
		$nFrameWidthz = $arKeyFrameSizeLUT[$szKeyFrameID]['width'];
		$nFrameHeightz = $arKeyFrameSizeLUT[$szKeyFrameID]['height'];

		global $gnHavingResized;
		global $gnMaxFrameWidth, $gnMaxFrameHeight;
		if($gnHavingResized)
		{
			$nMaxFrameWidth = $gnMaxFrameWidth;
			$nMaxFrameHeight = $gnMaxFrameHeight;
			$arTmpzz = getResizedFrameSize($nFrameWidthz, $nFrameHeightz, $nMaxFrameWidth, $nMaxFrameHeight);
			$nFrameWidth = $arTmpzz['width'];
			$nFrameHeight = $arTmpzz['height'];

			printf("### Resizing image from  [%dx%d] to [%dx%d]\n", $nFrameWidthz, $nFrameHeightz, $nFrameWidth, $nFrameHeight);
		}

		$szFPInputRawSIFTFN = sprintf("%s/%s.%s.tar.gz", $szInputRawFeatureDir, $szKeyFrameID, $szInputRawFeatureExt);

		// download to local dir

		// Modified Jul 06
		$szFPLocalRawSIFTFN = sprintf("%s/%s.%s", $szLocalDir, $szKeyFrameID, $szInputRawFeatureExt);

		// only download if no existing file --> try to re-use for ALL grids
		if(!file_exists($szFPLocalRawSIFTFN) || !filesize($szFPLocalRawSIFTFN))
		{
			$szCmdLine = sprintf("tar -xvf %s -C %s", $szFPInputRawSIFTFN, $szLocalDir);
			execSysCmd($szCmdLine);

			if(sizeof($arCacheLocalFileList) < $nMaxCacheFilesToKeep)
			{
				$arCacheLocalFileList[] = $szFPLocalRawSIFTFN;
			}
		}

		// parse raw file to get coord info
		$arPointLUT = parseOneRawSIFTFile2Grid($szFPLocalRawSIFTFN, $nNumRows, $nNumCols, $nFrameWidth, $nFrameHeight);

		// only keep top $nMaxCacheFilesToKeep files into local dir
		if(sizeof($arCacheLocalFileList) >= $nMaxCacheFilesToKeep)
		{
			deleteFile($szFPLocalRawSIFTFN);
		}

		$arTmp = explode(" ", $szFeature);
		$nNumDims = intval($arTmp[0]);
		$nNumNNs = intval($arTmp[1]);

		$nSize = sizeof($arTmp);
		// Each row: NumDims nNumNNs Label1 Val1 ....
		if( ($nSize != $nNumDims*2*$nNumNNs+2) || ($nNumNNs!=4))
		{
			printf("Data error [Line-%d-Data-%s]\n[NumDims-%d # NumNNs-%d # NumSize-%d]!\n",
					$i, $szLine, $nNumDims, $nNumNNs, $nSize);
			exit();
		}

		$arTmpHist = array();

		$nStepSize = $nNumNNs*2;
		$nPointIndex = 0;
		for($j=2; $j<$nSize; $j+=$nStepSize)
		{
			$nRectIndex = $arPointLUT[$nPointIndex];
			for($jj1=0; $jj1<$nNumNNs; $jj1++)
			{
				$nLabel = intval($arTmp[$j+2*$jj1]);
				$fVal = floatval($arTmp[$j+2*$jj1+1]);

				if(isset($arTmpHist[$nRectIndex][$nLabel][$jj1]))
				{
					//$arTmpHist[$nRectIndex][$nLabel][$jj1]  += $fVal;

					$arTmpHist[$nRectIndex][$nLabel][$jj1]  += normalizeWeight($fVal);
				}
				else
				{
					// $arTmpHist[$nRectIndex][$nLabel][$jj1]  = $fVal;
					$arTmpHist[$nRectIndex][$nLabel][$jj1]  = normalizeWeight($fVal);
				}
			}
			$nPointIndex++;
		}

		// computing weights
		$arHist = array();

		ksort($arTmpHist); // sort by rect index
		foreach($arTmpHist as $nRectIndex => $arTmpRectHist)
		{
			foreach($arTmpRectHist as $nLabel => $arRankDist)
			{
				$fVal = 0;
				foreach($arRankDist as $nRank => $fSum)
				{
					$fVal += 1.0*$fSum/pow(2, $nRank);
				}

				/// !IMPORTANT
				$nGlobalLabel = $nLabel + $nRectIndex*$nMaxCodeBookSize;
				$arHist[$nGlobalLabel] = $fVal;
			}
		}

		ksort($arHist); // important
		$szOutput = sprintf("%s", sizeof($arHist));
		foreach($arHist as $nLabel => $fVal)
		{
			// to ensure starting label > 0
			$nLabelPlus = $nLabel+1;
			$szOutput = $szOutput . " " . $nLabelPlus . " " . $fVal;
		}
		$szOutput = $szOutput . " % " . $szAnn;

		// printf("%s\n", $szOutput);

		$arOutput[] = $szOutput;
	}
	saveDataFromMem2File($arOutput, $szFPOutputFN);
}


function computeSoftBOWHistogramWithGridForOneVideoProgram($szLocalDir,
		$szFPKeyFrameListFN,
		$szRootFeatureInputDir, $szVideoPath, $szVideoID,
		$szInputFeatureExt, $szInputRawFeatureExt,
		$nMaxCodeBookSize = 2000)
{
	$arGridList = array(
//			4 => 4,
			3 => 1,
//			2 => 2,
//			1 => 3,
			1 => 1, );

	$szRootFeatureOutputDir = $szRootFeatureInputDir;
	$szInputDir = sprintf("%s/%s/%s", $szRootFeatureInputDir, $szInputFeatureExt, $szVideoPath);

	$szCoreName = sprintf("%s.%s", $szVideoID, $szInputFeatureExt);

	$szFPTarLabelFN = sprintf("%s/%s.label.lst.tar.gz", $szInputDir, $szCoreName);

	if(!file_exists($szFPTarLabelFN))
	{
		printf("File [%s] not found \n", $szFPTarLabelFN);
		$szLog = sprintf("###ERROR!!! %s. File [%s] not found. \n",
				date("m.d.Y - H:i:s"), $szFPTarLabelFN);

		$arLogListz = array();
		$arLogListz[] = $szLog;

		global $szFPLogFN;
		saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t");
		return;
	}

	$szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTarLabelFN, $szLocalDir);
	execSysCmd($szCmdLine);

	$szFPLocalInputFN = sprintf("%s/%s.label.lst", $szLocalDir, $szCoreName);

	$szInputRawFeatureDir = sprintf("%s/%s/%s/%s", $szRootFeatureInputDir, $szInputRawFeatureExt, $szVideoPath, $szVideoID);

	foreach($arGridList as $nNumRows => $nNumCols)
	{
		// adding grid info (mxn)
		// $szOutputFeatureExt = sprintf("%s.%dx%d", $szInputFeatureExt, $nNumRows, $nNumCols);
		// Changed 20 Jan  --> 1x3 --> norm1x3
		$szOutputFeatureExt = sprintf("%s.norm%dx%d", $szInputFeatureExt, $nNumRows, $nNumCols);

		$szOutputDir = sprintf("%s/%s/%s", $szRootFeatureOutputDir, $szOutputFeatureExt, $szVideoPath);
		makeDir($szOutputDir);

		$szOutputCoreName = sprintf("%s.%s", $szVideoID, $szOutputFeatureExt);

		global $gSkippExistingFiles;

		if($gSkippExistingFiles)
		{
			$szFPOutputFN = sprintf("%s/%s.tar.gz", $szOutputDir, $szOutputCoreName);
			if(file_exists($szFPOutputFN) && filesize($szFPOutputFN))
			{
				// get number of keyframes
				$nNumKeyFrames = loadListFile($arKeyFrameList, $szFPKeyFrameListFN);
				// get number of lines (each line <--> one keyframe)
					
				$szCmdLine = sprintf("tar -xvf %s -C %s", $szFPOutputFN, $szLocalDir);
				execSysCmd($szCmdLine);
					
				$szFPLocalTmpzzFN = sprintf("%s/%s", $szLocalDir, $szOutputCoreName);
					
				$nNumLines = loadListFile($arCountLineList, $szFPLocalTmpzzFN);
				deleteFile($szFPLocalTmpzzFN);
					
				if($nNumLines == $nNumKeyFrames+1) // first row --> annotation
				{
					printf("###File [%s] found. Skipping ... \n", $szFPOutputFN);

					$szLog = sprintf("###WARNING!!! %s. File [%s] found. Checked OK --> Skipping ... \n",
							date("m.d.Y - H:i:s"), $szFPOutputFN);

					$arLogListz = array();
					$arLogListz[] = $szLog;

					global $szFPLogFN;
					saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t");
						
					continue;
				}
					
				else
				{
					$szLog = sprintf("###WARNING!!! %s. File [%s] found. But not enough KF (Jul 14) [%s Lines - %s KF], re-running ... \n",
							date("m.d.Y - H:i:s"), $szFPOutputFN, $nNumLines-1, $nNumKeyFrames);

					$arLogListz = array();
					$arLogListz[] = $szLog;

					global $szFPLogFN;
					saveDataFromMem2File($arLogListz, $szFPLogFN, "a+t");
				}
			}
		}

		$szFPLocalOutputFN = sprintf("%s/%s", $szLocalDir, $szOutputCoreName);

		computeSoftWeightingHistogramWithGrid(
				$szFPKeyFrameListFN,
				$szFPLocalOutputFN,
				$szFPLocalInputFN,
				$szInputRawFeatureDir, $szInputRawFeatureExt,
				$szLocalDir,
				$nNumRows, $nNumCols,
				$nMaxCodeBookSize);

		$szFPTarLocalOutputFN = sprintf("%s.tar.gz", $szFPLocalOutputFN);
		$szCmdLine = sprintf("tar -cvzf %s -C %s %s", $szFPTarLocalOutputFN,
				$szLocalDir, $szOutputCoreName);
		execSysCmd($szCmdLine);

		$szFPOutputFN = sprintf("%s/%s.tar.gz", $szOutputDir, $szOutputCoreName);
		$szCmdLine = sprintf("mv -f %s %s", $szFPTarLocalOutputFN, $szFPOutputFN);
		execSysCmd($szCmdLine);

		deleteFile($szFPLocalOutputFN);
	}
	deleteFile($szFPLocalInputFN);
}

function computeSoftBOWHistogramWithGridForOnePat($szLocalDir,
		$szRootFeatureInputDir,
		$szRootMetaDataDir, $szFPVideoListFN,
		$szInputFeatureExt, $szInputRawFeatureExt,
		$nMaxCodeBookSize = 2000,
		$nStartID=-1, $nEndID=-1)
{
	$arVideoPathList = array();
	loadListFile($arRawList, $szFPVideoListFN);

	foreach($arRawList as $szLine)
	{
		$arTmp = explode("#$#", $szLine);
		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);
		$arVideoPathList[$szVideoID] = $szVideoPath;
	}

	$nNumVideos = sizeof($arVideoPathList);
	if($nStartID < 0)
	{
		$nStartID = 0;
	}

	if($nEndID <0 || $nEndID>$nNumVideos)
	{
		$nEndID = $nNumVideos;
	}

	$arVideoList = array_keys($arVideoPathList);

	for($i=$nStartID; $i<$nEndID; $i++)
	{
		$szVideoID = $arVideoList[$i];
		printf("###%d. Processing video [%s] ...\n", $i, $szVideoID);

		$szVideoPath = $arVideoPathList[$szVideoID];

		// !!! IMPORTANT !!!
		$szFPKeyFrameListFN = sprintf("%s/%s/%s.prgx", $szRootMetaDataDir, $szVideoPath, $szVideoID);

		// specific for one video program
		$szLocalDir2 = sprintf("%s/%s", $szLocalDir, $szVideoID);
		makeDir($szLocalDir2);

		computeSoftBOWHistogramWithGridForOneVideoProgram($szLocalDir2,
				$szFPKeyFrameListFN,
				$szRootFeatureInputDir, $szVideoPath, $szVideoID,
				$szInputFeatureExt, $szInputRawFeatureExt,
				$nMaxCodeBookSize);

		// clean up
		$szCmdLine = sprintf("rm -rf %s", $szLocalDir2);
		execSysCmd($szCmdLine);
	}
}

?>