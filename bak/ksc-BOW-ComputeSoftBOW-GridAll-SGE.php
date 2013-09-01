<?php

/**
 * 		@file 	ksc-BOW-ComputeSoftBOW-GridAll-SGE.php
 * 		@brief 	Compute soft assignment BOW with spatial setting (i.e. GRID).
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Jul 2013.
 */

//*** Update Jul 10, 2012
//--> Compute ALL grid ONCE

/************* STEPS FOR BOW MODEL ***************
 * 	STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV10.php --> select keypoints from devel pat
* 	STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV10.php --> do clustering using VLFEAT vl_kmeans, L2 distance
* 	STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
* 	STEP 4: nsc-ComputeAssignmentSash-TV10/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
* 	===> STEP 5: nsc-ComputeSoftBOW-Grid-TV10/-SGE.php --> compute soft assignment for grid image
*/

// Update Aug 01
// Customize for tv2011


//////////////////////////////////////////////////////////////

// Update May 20
// Adding dense & phow

// ************* Update May 10 *************
// Adding phowhsv8, phow6 and dense3

// ************ Update Feb 21 ************
// "Soft-500-VL2z"  --> FAILED because cluster centers should be int val if using vlfeat ikmeans (only work on int val)
// Back to $szTrialName = "Soft-500-VL2";  //  --> V: VLFEAT, L2: L2 distance for clustering and word assignment

// ************ Update Feb 20 ************
// $szTrialName = "Soft-500-VL2z";  //  = Soft-500-VL2, but cluster centers are float val, not intval returned by VLFEAT

// ************ Update Feb 15 ************
// Must be sure sashKeyPointTool use the same distance with VLFEAT (L2)
// $szTrialName = "Soft-500-VL2";  //  --> V: VLFEAT, L2: L2 distance for clustering and word assignment

// ************ Update Feb 08 ************
// Changed to Soft-500-VE

// ************ Update Jan 23 ************
// Adding features harlap, heslap, haraff

// ************ Update Jan 21 ************
// Include 1x1 grid  --> no longer use nsc-BOW-ComputeSoftAssignment-TV10.php

// ************ Update Jan 17 ************
// For SimpleSoft-1

////////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

$szProjectCodeName = "kaori-secode-tvsin13"; // *** CHANGED ***
$szCoreScriptName = "ksc-BOW-ComputeSoftBOW-GridAll"; // *** CHANGED ***

//$szSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-TVSIN11";
$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$arSourcePatList = array("devel-nistNew");

$arDestPatList = array(
		"devel-nistNew",
		"test.iacc.2.ANew", 
		"test.iacc.2.BNew", 
		"test.iacc.2.CNew", 
);


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
		"nsc.raw.dense4mul.oppsift",
		"nsc.raw.dense4mul.sift",
		"nsc.raw.dense4mul.rgsift",
		"nsc.raw.dense4mul.rgbsift",
		"nsc.raw.dense4mul.csift",

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
		"nsc.raw.dense4mul.csift",);

$szInputSourcePatName = "devel-nistNew";
$szInputDestPat = "test.iacc.2.ANew";

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////

if($argc != 3)
{
	printf("Usage: %s <SourcePatName> <DestPath>\n", $argv[0]);
	printf("Usage: %s %s %s\n", $argv[0], $szInputSourcePatName, $szInputDestPat);
	exit();
}

$szInputSourcePatName = $argv[1];
$szInputDestPat = $argv[2];

foreach($arSourcePatList as $szSourcePatName)
{
	if($szSourcePatName != $szInputSourcePatName)
	{
		printf("### Skipping [%s] ...\n", $szSourcePatName);
		continue;
	}

	foreach($arDestPatList as $szDestPatName)
	{
		if($szDestPatName != $szInputDestPat)
		{
			printf("### Skipping [%s] ...\n", $szDestPatName);
			continue;
		}
		$szFPPatName = $szDestPatName;

		$arCmdLineList =  array();

		$nMaxVideosPerPat = $arMaxVideosPerPatList[$szFPPatName];
		$nNumVideosPerHost = max(1, intval($nMaxVideosPerPat/$nMaxHostsPerPat)); // Oct 19

		printf("### Found pair (%s, %s)! [%s-%s]\n", $szSourcePatName, $szDestPatName, $nMaxVideosPerPat, $nNumVideosPerHost);

		foreach($arFeatureList as $szFeatureExt)
		{
			$szScriptOutputDir = sprintf("%s/bow.%s.%s/%s",
					$szRootScriptOutputDir, $szTrialName, $szSourcePatName, $szFeatureExt);
			makeDir($szScriptOutputDir);

			$arCmdLineList =  array();

			for($j=0; $j<$nMaxVideosPerPat; $j+=$nNumVideosPerHost)
			{
				$nStart = $j;
				$nEnd = $nStart+$nNumVideosPerHost;

				// override if no use log file
				$szFPLogFN = "/dev/null";

				// nsc.bow.harhes.sift.SimpleSoft-1.tv2010.devel-nist
				$szInputFeatureExt1 = str_replace("raw", "bow", $szFeatureExt);
				$szInputFeatureExt = sprintf("%s.%s.%s", $szInputFeatureExt1, $szTrialName, $szSourcePatName);
				// 5 params
				// printf("Usage: %s <PatName> <FeatureExt> <RawFeatureExt>
				// <Start> <End>\n", $argv[0]);
				$szParam = sprintf("%s %s %s %s %s",
						$szFPPatName, $szInputFeatureExt, $szFeatureExt, $nStart, $nEnd);

				$szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);

				$arCmdLineList[] = $szCmdLine;
				
				$szCmdLine = "sleep 2s;";
				$arCmdLineList[] = $szCmdLine;
				
			}
			$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.%s.sh",
					$szScriptOutputDir, $szCoreScriptName, $szFPPatName, $szFeatureExt); // specific for one set of data
			if(sizeof($arCmdLineList) > 0 )
			{
				saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
				$arRunFileList[] = $szFPOutputFN;
			}
		}
	}
}

?>