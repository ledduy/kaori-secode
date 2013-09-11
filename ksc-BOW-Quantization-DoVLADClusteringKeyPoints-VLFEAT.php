<?php

/**
 * 		@file 	ksc-BOW-Quantization-ksc_FV_DoVLADClusteringKeyPoints_VLFEAT.php
 * 		@brief 	Do clustering for keypoints using kmeans+kdtree for VLAD encoding.
 *		Used with ksc_FV_DoVLADClusteringKeyPoints_VLFEAT.m
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 07 Sep 2013.
 */

// Only available since vlfeat-0.9.17 
// $nMaxSamples=300000;

// Aug 14, 2013 - For 1.5M keypoints of dense6mul.rgbsift --> 8.7GB RAM ==> 54GB for running k-means

// For 1.5M keypoints of dense6mul.oppsift --> 15GB RAM for running k-means

//*** Update Jul 08, 2012
//--> Check FeatureOutputDir for load balancing


///////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

///////////////////////////// THIS PART FOR CUSTOMIZATION /////////////////
//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

/// !!! IMPORTANT

$szSrcPatName = "devel2012";
$szRawFeatureExt = "nsc.raw.dense6mul.rgbsift";
$nNumFVClusters = 256;
//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////

if($argc != 4)
{
	printf("Usage: %s <SrcPatName> <RawFeatureExt> <NumFVClusters>\n", $argv[0]);
	printf("Usage: %s %s %s %s\n", $argv[0], $szSrcPatName, $szRawFeatureExt, $nNumFVClusters);
	exit();
}

$szSrcPatName = $argv[1];
$szRawFeatureExt = $argv[2];
$nNumFVClusters = intval($argv[3]);

/// !!! IMPORTANT
//$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szRawFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootOutputDir);
makeDir($szRootFeatureDir);

$szTrialName = sprintf("VLAD-%d", $nNumFVClusters);
$szInputDir = sprintf("%s/bow.codebook.%s.%s/%s", $szRootFeatureDir, $szTrialName, $szSrcPatName, $szRawFeatureExt);
$szDataName = sprintf("%s.%s.%s", $szTrialName, $szSrcPatName, $szRawFeatureExt);
$szDataExt = "dvf";

$szFPInputFN = sprintf("%s/data/%s-c0-b0.%s", $szInputDir, $szDataName, $szDataExt);
$szFPOutputFN = sprintf("%s/data/%s-c0-b0.csv", $szInputDir, $szDataName);

$nMaxSamples=1000000;
convertDvf2CSVFormat($szFPOutputFN, $szFPInputFN, $nMaxSamples);

global $garAppConfig;
$szVLFEATDir = $garAppConfig["RAW_VLFEAT_DIR"];
// there are 2 commands, so we need to create a tmp .sh file and run it
// the temp .sh file is generated so that a unique name is guaranteed
$arCmdLine = array();
$szCmdLine = sprintf("cd %s; pwd;", $szVLFEATDir); //
$arCmdLine[] = $szCmdLine;

$szFPCSVInputFN = $szFPOutputFN;

// VLAD-128.devel2012.nsc.raw.dense6mul.rgbsift.VLADodel.mat 
$szFPVLADOutputFN = sprintf("%s/data/%s.%s.%s.VLADModel.mat", $szInputDir, $szTrialName, $szSrcPatName, $szRawFeatureExt);

// call A(param1, param2) instead of A param1 param2
$szParam = sprintf("'%s', '%s', %d", $szFPVLADOutputFN, $szFPCSVInputFN, $nNumFVClusters);
$szCmdLine = sprintf("matlab -nodisplay -r \"ksc_FV_DoVLADClusteringKeyPoints_VLFEAT(%s)\" ", $szParam);
printf("Command: [%s]\n", $szCmdLine);
$arCmdLine[] = $szCmdLine;

$szPrefix = sprintf("ksc_FV_DoVLADClusteringKeyPoints_VLFEAT_%s_%s", $szSrcPatName, $szRawFeatureExt);
$szFPCmdFN = tempnam("/tmp", $szPrefix);
saveDataFromMem2File($arCmdLine, $szFPCmdFN);
$szCmdLine = sprintf("chmod +x %s", $szFPCmdFN);
system($szCmdLine);

system($szFPCmdFN);
deleteFile($szFPCmdFN);

// delete file
// deleteFile($szFPCSVInputFN);

return; // only one pat, one feature at one time

///////////////////////////// FUNCTIONS /////////////////////////////
function convertDvf2CSVFormat($szFPOutputFN, $szFPInputFN, $nMaxSamples=300000)
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
		
		if($i>=$nMaxSamples)
		{
		    break;
		}
	}
	printf(".]. Finish converting!\n");
	
    saveDataFromMem2File($arOutput, $szFPOutputFN, "a+t");
	$arOutput = array();
}

?>