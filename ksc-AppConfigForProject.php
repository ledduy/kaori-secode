<?php

 /**
 * 		@file 	ksc-AppConfigForProject.php
 * 		@brief 	Configuration file for a specific project.
 * 		Some variables will be overrided.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 04 Jul 2013.
 */


////////////////// HOW TO CUSTOMIZE /////////////////////////

// $szExpName = "mediaeval-vsd2012"; /// *** CHANGED ***
// Changes for $arPat2PathList, etc
// function getRootDirForFeatureExtraction($szFeatureExt)

//////////////////////////////////////////////////////////////

/////////////////// IMPORTANT PARAMS /////////////////////////

//--> for load balancing purpose, features might be stored in different servers
// function getRootDirForFeatureExtraction($szFeatureExt)


/////////////////////////////////////////////////////////////////


// SVM configs
$gszSVMTrainApp = sprintf("libsvm291/svm-train");
$gszSVMPredictScoreApp = sprintf("libsvm291/svm-predict-score");
$gszGridSearchApp = sprintf("libsvm291/grid.py");
$gszSVMSelectSubSetApp = sprintf("libsvm291/subset.py");
$gszSVMScaleApp = sprintf("libsvm291/svm-scale");

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

// The information below is mainly for feature extraction using SGE

$szExpName = "hlf-tv2013"; // *** CHANGED ***
$szExpConfig = $szExpName; 

$szProjectCodeName = "kaori-secode-sin213"; // *** CHANGED ***

//--> name of list of videos, i.e, metadata/keyframe-5/<pat-name.lst> = metadata/keyframe-5/tv2012.devel.lst
$arPat2PathList = array(
		"devel-nistNew" => "tv2012/devel-nistNew",
		"test.iacc.2.ANew" => "tv2013/test.iacc.2.ANew", // iacc.2.A
		"test.iacc.2.BNew" => "tv2014/test.iacc.2.BNew", // iacc.2.A
		"test.iacc.2.CNew" => "tv2015/test.iacc.2.CNew", // iacc.2.A
		);  // *** CHANGED ***

$nNumPats = sizeof($arPat2PathList);

// this part is for SGE - Feature Extraction
//--> dir name + path containing keyframes, i.e, keyframe-5/<path-name> = keyframe-5/tv2012/devel
$arVideoPathList = array(
		"devel-nistNew",
		"test.iacc.2.ANew", // *** CHANGED ***
		"test.iacc.2.BNew", // *** CHANGED ***
		"test.iacc.2.CNew", // *** CHANGED ***
);

$arMaxVideosPerPatList = array(
		"devel-nistNew" => 200,
		"test.iacc.2.ANew" => 300, // *** CHANGED ***
		"test.iacc.2.BNew" => 300, // *** CHANGED ***
		"test.iacc.2.CNew" => 300, // *** CHANGED ***
		); // Precise: N/A

$arMaxHostsPerPatList = array(
		"devel-nistNew" => 100,
		"test.iacc.2.ANew" => 100, // *** CHANGED ***
		"test.iacc.2.BNew" => 100, // *** CHANGED ***
		"test.iacc.2.CNew" => 100, // *** CHANGED ***
		); // Precise: N/A

// these params are used in extracting raw local features. 
// normally, one keyframe --> one raw feature file
// therefore, we need to specify a subset of KF of one video program for one job.
/*
for($jk=0; $jk<$nMaxKFPerVideo; $jk+=$nNumKFPerJob)
			{
				$nStartKFID = $jk;
				$nEndKFID = $nStartKFID + $nNumKFPerJob;
*/

// usually set this number to SUPER MAX keyframes per video
$nMaxKFPerVideo = 100000; // *** CHANGED ***

// usually set this number to $nMaxKFPerVideo if all KF is processed in one job 
$nNumKFPerJob = $nMaxKFPerVideo; // *** CHANGED ***

// this param is used for ksc-BOW-Quantization-SelectKeyPointsForClustering.php
// if no shot case (e.g, imageclef, imagenet), it is the ave number of keyframes per video.
$nAveShotPerVideo = 1000; // *** CHANGED ***

// set for training --> used to find cluster centers
$arBOWDevPatList = array("devel-nistNew");

$szSysID = "hlf-tv2013"; // *** CHANGED ***
$szSysDesc = "Experiments for TRECVID-SIN-2013"; // *** CHANGED ***

// used for codeword assignment
$arBOWTargetPatList = array(
		"devel-nistNew",
		"test.iacc.2.ANew", // *** CHANGED ***
		"test.iacc.2.BNew", // *** CHANGED ***
		"test.iacc.2.CNew", // *** CHANGED ***
		);

$szConfigDir = "basic";

$arFilterList = array(
		".dense4.",
		".dense6.",
		".dense8.",
		".phow6.",
		".phow8.",
		".phow10.",
		".phow12.",
		".dense4mul.csift.",
		".dense4mul.rgbsift.",
		".dense4mul.oppsift.",
		".dense6mul.csift.",
		".dense6mul.rgbsift.",
		".dense6mul.oppsift.",
		".g_cm.",
		".g_ch.",
		".g_eoh.",
		".g_lbp."
);

// this param is used in BOW
$arFeatureList = array(
		"nsc.raw.harhes.sift",
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

		"nsc.raw.dense4mul.oppsift",
		"nsc.raw.dense4mul.sift",
		"nsc.raw.dense4mul.rgsift",
		"nsc.raw.dense4mul.rgbsift",
		"nsc.raw.dense4mul.csift",);

$arFeatureListConfig = array(
		"nsc.raw.dense4.sift" => "vlfeat, 1, 4", // CodeName, KPDetector, SamplingStep  
		"nsc.raw.dense6.sift" => "vlfeat, 1, 6", 
		"nsc.raw.dense8.sift" => "vlfeat, 1, 8", 
		"nsc.raw.dense10.sift" => "vlfeat, 1, 10", 
		"nsc.raw.phow6.sift" => "vlfeat, 2, 6",
		"nsc.raw.phow8.sift" => "vlfeat, 2, 8", 
		"nsc.raw.phow10.sift" => "vlfeat, 2, 10",  
		"nsc.raw.phow12.sift" => "vlfeat, 2, 12",  
		"nsc.raw.phow14.sift" => "vlfeat, 2, 14",  
		"nsc.raw.phowhsv8.sift" => "vlfeat, 3, 8",  // phow + color
		"nsc.raw.dog.sift" => "vlfeat, 0,-1"
);  // -1 = UNUSED

//////////////////// END FOR CUSTOMIZATION ////////////////////

function getRootBenchmarkMetaDataDir($szFeatureExt)
{
	global $gszRootBenchmarkDir;
	
	$szOutputDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
	return $szOutputDir;
	
}

function getRootBenchmarkFeatureDir($szFeatureExt)
{
	$szOutputDir = sprintf("%s/feature/keyframe-5", getRootDirForFeatureExtraction($szFeatureExt));
	return $szOutputDir;
}

// *** CHANGED ***
function getRootDirForFeatureExtraction($szFeatureExt)
{
	global $gszRootBenchmarkDir;
	
	$szOutputDir = $gszRootBenchmarkDir; // DEFAULT 

	/*
	$szRootDir1 = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2012";
	makeDir($szRootDir1);
	
	$szRootDir2 = "/net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012";
	makeDir($szRootDir2);
	*/
	
	$szRootDir1 = $szRootDir2 = $szOutputDir;
	if(strstr($szFeatureExt, ".dense4."))
	{
		$szOutputDir = $szRootDir2; 
	}

	if(strstr($szFeatureExt, ".dense6."))
	{
		$szOutputDir = $szRootDir2;
	}

	if(strstr($szFeatureExt, ".dense8."))
	{
		$szOutputDir = $szRootDir2; 
	}
	
	if(strstr($szFeatureExt, ".phow8."))
	{
		$szOutputDir = $szRootDir2;
		
	}
	
	if(strstr($szFeatureExt, ".phow10."))
	{
		$szOutputDir = $szRootDir2;
		
	}
	
	if(strstr($szFeatureExt, ".phow12."))
	{
		$szOutputDir = $szRootDir2;
		
	}
	
	if(strstr($szFeatureExt, ".dense6mul.csift"))
	{
	
		$szOutputDir = $szRootDir1; 
	}
	
	if(strstr($szFeatureExt, ".dense6mul.rgbsift"))
	{
		$szOutputDir = $szRootDir1;
		
	}

	if(strstr($szFeatureExt, ".dense6mul.oppsift"))
	{
	
		$szOutputDir = $szRootDir1; 
	}

	if(strstr($szFeatureExt, ".dense4mul.csift"))
	{
	
		$szOutputDir = $szRootDir1; 
	}
	
	if(strstr($szFeatureExt, ".dense4mul.rgbsift"))
	{
		$szOutputDir = $szRootDir1;
		
	}

	if(strstr($szFeatureExt, ".dense4mul.oppsift"))
	{
	
		$szOutputDir = $szRootDir1; 
	}
	
	return $szOutputDir;
}


///////////////////////////// HISTORY /////////////////////////
// AUG 11, 2012 --> starting date for MEDIAEVAL 2012
//--> Look for *** CHANGED *** and make appropriate changes
// $szExpName = "mediaeval-vsd2012"; /// *** CHANGED ***
// Changes for $arPat2PathList, etc
// function getRootDirForFeatureExtraction($szFeatureExt)


//------------------------------------------------------------------------------------

//*** Update Jul 03, 2012
// Customize for TRECVID-SIN12
//--> CHANGE $szExpName, e.g, hlf-tv2012
//--> CHANGE maxW x maxH for resized keyframes.
//--> Adding more features (dense4mul, phow6)


//$gszTmpDir = "/local/ledduy";

?>
