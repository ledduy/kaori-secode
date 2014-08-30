<?php

/**
 * 		@file 	ksc-BOW-Quantization-DoClusteringKeyPoints-VLFEAT.php
 * 		@brief 	Do clustering for keypoints using VLFeat-Elkan+Matlab.
 *		Used with ksc_BOW_DoClusteringKeyPoints_VLFEAT.m
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 30 Aug 2014.
 */



/**
 * *********** STEPS FOR BOW MODEL (30 Aug, 2014) ***************
 * ===> STEP 1: Build codebook
 * STEP 1.1: ksc-BOW-SelectKeyPointsForClustering.php --> select keypoints from devel pat
 * (It does not require to extract raw features of ALL keyframes as previous version.
 * Instead, it will select keyframes and extract raw features on the FLY.)
 * ===> STEP 1.2: ksc-BOW-DoClusteringKeyPoints-VLFEAT.php --> do clustering using VLFEAT vl_kmeans, L2 distance
 * STEP 1.3: ksc-ComputeSashForCentroids.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
 * STEP 2: ksc-Feature-ExtractBoW-SPM/-SGE.php --> compute assignment with SPM, using approx search (scale factor - 4)
 */

/** ksc_BOW_DoClusteringKeyPoints_VLFEAT.m --> matlab code, placed in vlfeat0.9.17
 *  Using vl_kmeans (not vl_ikmeans) --> elkan method, kmeans++ initialization (default by vlfeat).
 * 
% Do clustering using VLFEAT-matlab
% This script must be put in vlfeat dir
% szFPInputFN - input file name in csv format
% szFPCentroidOutputFN - centroids of output clusters
% szFPIMemOutputFN - cluster assignment
% run('toolbox/vl_setup'); --> must call to init env
% 1.5M keypoints --> 60GB memory
% Written by Duy-Dinh Le
% Last update: Feb 21, 2011
% Using k-means, (not integer kmeans as before)
% Last update: Feb 08, 2011
% Using integer k-means

function ksc_BOW_DoClusteringKeyPoints_VLFEAT(szFPCentroidOutputFN, szFPIMemOutputFN, szFPInputFN, nNumClusters, szMethod)
	
	run('toolbox/vl_setup'); % init env
	
	fprintf(1, 'Loading csv data file ...\n');
	data = csvread(szFPInputFN);

	fprintf(1, 'Performing k-means [%u] clusters with method [%s]...\n', nNumClusters, szMethod);
	
	% [C,A] = vl_ikmeans(uint8(data'),nNumClusters,'method', szMethod) ; % convert to unit8, and transpose
	[C,A] = vl_kmeans(data',nNumClusters,'algorithm', szMethod) ; % transpose
	
	fprintf(1, 'Saving output data...\n');
	dlmwrite(szFPCentroidOutputFN, C'); % transpose C
 	dlmwrite(szFPIMemOutputFN, A');
 	quit;  % quit matlab since it is used to run within PHP 	
end
 
 */

//*** Update Aug 14 2013

/**
    Elkan [4] (VlKMeansElkan). 
    This is a variation of [8] that uses the triangular inequality to avoid many distance calculations 
    when assigning points to clusters and is typically much faster than [8] . 
    However, it uses storage proportional to the square of the number of clusters, 
    which makes it unpractical for a very large number of clusters. * 

    Lloyd [8] (VlKMeansLloyd). This is the standard k-means algorithm, 
    alternating the estimation of the point-to-cluster memebrship and of the cluster centers 
    (means in the Euclidean case). Estimating membership requires computing the distance 
    of each point to all cluster centers, which can be extremely slow. 
 */

// Aug 14, 2013 - For 1.5M keypoints of dense6mul.rgbsift ==> 60GB for running k-means

//*** Update Jul 08, 2012
//--> Check FeatureOutputDir for load balancing

// Update Aug 01
// Customize for tv2011

/////////////////////////////////////////////////////////
// Update May 20
// Adding dense & phow

// ************* Update May 10 *************
// Adding phowhsv8, phow6 and dense3 --> luu y la giu nguyen so luong point/frame la 1000, trong thuc te thi phow6 so luong la 9344

// ************ Update Feb 21 ************
// Minor updates of output file <TrialName>-OutputVLEAT
// Use vl_kmeans (instead of vl_ikmeans - integer kmeans)
// [C,A] = vl_kmeans(data',nNumClusters,'method', szMethod) ; % transpose
// Adding dog, dense6, and phow10
// vl_kmeans (basic kmeans) --> 1.5M keypoints --> 8.0 GB RAM, 3 hours (@per900b)

// ************ Update Feb 13 ************
// !!!IMPORTANT: Must be sure L2 is used for VLFEAT - kmeans
// szTrialName = Soft-500-VL2  --> V: VLFEAT, L2: L2 distance for clustering and word assignment
// vl_ikmeans (integer kmeans -- FAST) --> 1.5M keypoints --> 4.2GB RAM, xx hours (@per900b)  --> ~3-4 hours

// ************ Update Feb 08 ************
// Invoke matlab function nsc_BOW_DoClusteringKeypoints_VLFEAT_TV10.m
// call A(param1, param2) instead of A param1 param2
// 1M keypoints (128dim-SIFT) --> 3GB mem, 3 hours

/*
 *
 *  1. $cd /net/per900b/raid0/ledduy/bin/vlfeat-0.9.8
 *  2. $run('toolbox/vl_setup')
 *  3. Import to var 'data'
 *  4. K=500;
 *  5. [C,A] = vl_ikmeans(uint8(data'),K,'method', 'elkan') ; // convert to unit8, and transpose
 *  6. dlmwrite('output.csv', C'); // transpose C
 *  7. dlmwrite('output.idx', A');
 */

///////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

///////////////////////////// THIS PART FOR CUSTOMIZATION /////////////////
//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

/// !!! IMPORTANT
//$nNumClusters = 500;
//$szKMeansMethod = 'elkan';

$szSrcPatName = "devel2011-new";
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

$szInputDir = sprintf("%s/bow.codebook.%s.%s/%s", $szRootFeatureDir, $szTrialName, $szSrcPatName, $szRawFeatureExt);
$szDataName = sprintf("%s.%s.%s", $szTrialName, $szSrcPatName, $szRawFeatureExt);
$szDataExt = "dvf";

$szFPInputFN = sprintf("%s/data/%s-c0-b0.%s", $szInputDir, $szDataName, $szDataExt);
$szFPOutputFN = sprintf("%s/data/%s-c0-b0.csv", $szInputDir, $szDataName);
convertDvf2CSVFormat($szFPOutputFN, $szFPInputFN);

// invoke matlab function of VLFEAT
// ksc_BOW_DoClusteringKeyPoints_VLFEAT(szFPCentroidOutputFN, szFPIMemOutputFN, szFPInputFN, nNumClusters, szMethod)

global $garAppConfig;
$szVLFEATDir = $garAppConfig["RAW_VLFEAT_DIR"];
// there are 2 commands, so we need to create a tmp .sh file and run it
// the temp .sh file is generated so that a unique name is guaranteed
$arCmdLine = array();
$szCmdLine = sprintf("cd %s; pwd;", $szVLFEATDir); //
$arCmdLine[] = $szCmdLine;

$szFPCSVInputFN = $szFPOutputFN;
$szFPCentroidOutputFN = sprintf("%s/data/%s-OutputVLFEAT.csv", $szInputDir, $szTrialName);
$szFPIMemOutputFN = sprintf("%s/data/%s-OutputVLFEAT.idx", $szInputDir, $szTrialName);

// call A(param1, param2) instead of A param1 param2
$szParam = sprintf("'%s', '%s', '%s', %d, '%s'", $szFPCentroidOutputFN, $szFPIMemOutputFN, $szFPCSVInputFN, $nNumClusters, $szKMeansMethod);
$szCmdLine = sprintf("matlab -nodisplay -r \"ksc_BOW_DoClusteringKeyPoints_VLFEAT(%s)\" ", $szParam);
printf("Command: [%s]\n", $szCmdLine);
$arCmdLine[] = $szCmdLine;

$szPrefix = sprintf("ksc_BOW_DoClusteringKeyPoints_VLFEAT_%s_%s", $szSrcPatName, $szRawFeatureExt);
$szFPCmdFN = tempnam("/tmp", $szPrefix);
saveDataFromMem2File($arCmdLine, $szFPCmdFN);
$szCmdLine = sprintf("chmod +x %s", $szFPCmdFN);
system($szCmdLine);

system($szFPCmdFN);
// deleteFile($szFPCmdFN);

// delete file
// deleteFile($szFPCSVInputFN);

return; // only one pat, one feature at one time

///////////////////////////// FUNCTIONS /////////////////////////////
function convertDvf2CSVFormat($szFPOutputFN, $szFPInputFN)
{
	$nNumRows = loadListFile($arRawList, $szFPInputFN);

	$nNumCommentLines = countNumCommentLines($arRawList);
	$nNumSamples = $arRawList[$nNumCommentLines];

	if($nNumRows != $nNumSamples+1+$nNumCommentLines)
	{
		printf("Data error!\n");
		exit();
	}

	$arOutput = array();
	saveDataFromMem2File($arOutput, $szFPOutputFN, "wt");
	printf("Starting converting ..[");
	for($i=$nNumCommentLines+1; $i<$nNumRows; $i++)
	{
		$szLine = &$arRawList[$i];
		$arTmp = explode(" ", $szLine);
		$nNumDims = intval($arTmp[0]);

		//print_r($arTmp);
		$szOutput = "";
		for($j=1; $j<=$nNumDims; $j++)
		{
			if($j<$nNumDims)
			{
				$szOutput = $szOutput . trim($arTmp[$j]) . ", ";
			}
			else
			{
				$szOutput = $szOutput . trim($arTmp[$j]);
			}

		}

		$arOutput[] = $szOutput;
		unset($arRawList[$i]);
		$arRawList[$i] = array();

		//printf($szLine); exit();
		if(($i % 100000) == 0)
		{
			printf(".");
			saveDataFromMem2File($arOutput, $szFPOutputFN, "a+t");
			$arOutput = array();
		}
	}
	printf(".]. Finish converting!\n");
	saveDataFromMem2File($arOutput, $szFPOutputFN, "a+t");
	$arOutput = array();
}


?>