<?php

/**
 * 		@file 	ksc-BOW-Quantization-ComputeSashForCentroids.php
 * 		@brief 	Compute SASH for Centroids to improve the speed of assignment.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 16 Aug 2013.
 */

//*** Update Jul 08, 2012
//--> Check FeatureOutputDir for load balancing

/************* STEPS FOR BOW MODEL ***************
 * 	STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV10.php --> select keypoints from devel pat
* 	STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV10.php --> do clustering using VLFEAT vl_kmeans, L2 distance
* 	===> STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
* 	STEP 4: nsc-ComputeAssignmentSash-TV10/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
* 	STEP 5: nsc-ComputeSoftBOW-Grid-TV10/-SGE.php --> compute soft assignment for grid image
*/

// Update Aug 01
// Customize for tv2011

///////////////////////////////////////////////////
// Update May 20
// Adding dense & phow

// ************* Update May 10 *************
// Adding phowhsv8, phow6 and dense3
// Change sashKeyPointTool-nsc-BOW to sashKeyPointTool-nsc-BOW-L2 (just change the name)

// ************ Update Feb 21 ************
// "Soft-500-VL2z"  --> FAILED because cluster centers should be int val if using vlfeat ikmeans (only work on int val)
// Back to $szTrialName = "Soft-500-VL2";  //  --> V: VLFEAT, L2: L2 distance for clustering and word assignment
// Adding dog, dense6 and phow10

// ************ Update Feb 20 ************
// $szTrialName = "Soft-500-VL2z";  //  = Soft-500-VL2, but cluster centers are float val, not intval returned by VLFEAT

// ************ Update Feb 15 ************
// Must be sure sashKeyPointTool use the same distance with VLFEAT (L2)
// $szTrialName = "Soft-500-VL2";  //  --> V: VLFEAT, L2: L2 distance for clustering and word assignment

// ************ Update Feb 08 ************
// Changed to Soft-500-VE
// centroids data: outputVLFEAT.csv

// Use $szSashKeypointToolApp = sprintf("/net/per900b/raid0/ledduy/kaori-core/cpp/sashKeyPointTool/sashKeyPointTool-nsc-BOW");

// ************ Update Jan 22 ************
// Adding features harlap, heslap, haraff
// This script is invoked after running nsc-BOW-ComputeCentroids-TV10.php + matlab-kmeans
// The output of previous step is output.csv, containing centroids for codewords, located in dir 'data'

// ************ Update Jan 07 ************
// Adding function to convert csv format to dvf format
// data is the dir for storing clustering result (not part-0.1)

// ************ Update Dec 22 ************
// Pick the part of nsc-BOW-ComputeAssignment-Sash-TV10.php

///////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";
require_once "kaori-lib/kl-GreedyRSCClusteringTool.php";

///////////////////////////// THIS PART FOR CUSTOMIZATION /////////////////

//$szSashKeypointToolApp = sprintf("/net/per900b/raid0/ledduy/kaori-core/cpp/sashKeyPointTool/sashKeyPointTool-nsc-BOW-L2");
$szSashKeypointToolApp = $garAppConfig["SASH_KEYPOINT_TOOL_BOW_L2_APP"];

//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);


$szSrcPatName = "subtest2012-new";
$szRawFeatureExt = "nsc.raw.dense6mul.rgbsift";
//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////

if($argc != 3)
{
	printf("Usage: %s <SrcPatName> <RawFeatureExt>\n", $argv[0]);
	printf("Usage: %s %s %s\n", $argv[0], $szSrcPatName, $szRawFeatureExt);
	exit();
}

$szSrcPatName = $argv[1];
$szRawFeatureExt = $argv[2];

/// !!! IMPORTANT
//$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szRawFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);

$szCentroidDir = sprintf("%s/bow.codebook.%s.%s/%s/data", $szRootFeatureDir, $szTrialName, $szSrcPatName, $szRawFeatureExt);

$szDataPrefix = sprintf("%s.%s.%s.Centroids", $szTrialName, $szSrcPatName, $szRawFeatureExt);
$szDataExt = "dvf";

$szInputDir = $szCentroidDir;
$szOutputDir = $szCentroidDir;

$szFPInputFN = sprintf("%s/%s-OutputVLFEAT.csv", $szOutputDir, $szTrialName);
$szFPOutputFN = sprintf("%s/%s-c0-b0.dvf", $szOutputDir, $szDataPrefix, $szDataExt);
convert2DvfFormat($szFPInputFN, $szFPOutputFN);

// "Usage: %s --buidSash <SashOutputDir> <DataInputDir> <DataPrefixName> <FeatureExt>\n",
$szCmdLine = sprintf("%s --buildSash %s %s %s dvf", $szSashKeypointToolApp, $szOutputDir, $szInputDir, $szDataPrefix);
execSysCmd($szCmdLine);

///////////////////////////////// FUNCTIONS ///////////////////////////////
function convert2DvfFormat($szFPInputFN, $szFPOutputFN)
{
	$nNumRows = loadListFile($arRawList, $szFPInputFN);

	$arOutput = array();
	$arOutput[] = sprintf("%% %s", $szFPInputFN);
	$arOutput[] = sprintf("%d", $nNumRows);
	for($i=0; $i<$nNumRows; $i++)
	{
		$szLine = &$arRawList[$i];
		$arTmp = explode(",", $szLine);

		$nNumDims = sizeof($arTmp);

		$szOutput = sprintf("%s", $nNumDims);
		for($j=0; $j<$nNumDims; $j++)
		{
			$szOutput = $szOutput . " ". $arTmp[$j];
		}
		$arOutput[] = $szOutput;
	}
	saveDataFromMem2File($arOutput, $szFPOutputFN);
}

?>