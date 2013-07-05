<?php

/**
 * 		@file 	ksc-Tool-ExtractKeyFrames-SGE.php
 * 		@brief 	Generate jobs for SGE to extract keyframes.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */


//////////////////////////////////////////////////
// Update Jan 05
// szLogFN = /dev/null
// Use nNumJobsPerPat instead of nNumVideosPerHost

// Update Aug 21
// for consistency with ExtractBaselineFeature
// $szLogDir = sprintf("%s/%s", $szRootLogDir, $szVideoPath);

// Update Aug 20
// Specify max videos per pat, $arMaxVideosPerPatList
// check existence of one pat

// Update Aug 18
// OK for re-run with 5KFs/shot

////////////////////////////////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////
$szProjectCodeName = "kaori-secode-tvsin12"; // *** CHANGED ***

$szCoreScriptName = "ksc-Tool-ExtractKeyFrames";

$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

//--> name of list of videos, i.e, metadata/keyframe-5/<pat-name.lst> = metadata/keyframe-5/tv2012.devel.lst
$arPat2PathList = array(
		"tv2012.test" => "tv2012/test");  // *** CHANGED ***

$nNumPats = sizeof($arPat2PathList);

// this part is for SGE - Feature Extraction
//--> dir name + path containing keyframes, i.e, keyframe-5/<path-name> = keyframe-5/tv2012/devel
$arVideoPathList = array(
		"tv2012/test" // *** CHANGED ***
);

$arMaxVideosPerPatList = array(
		"tv2012.test" => 10000); // Precise: 8,263

$nMaxHostsPerPat = 300; // use 300 cores for extracting keyframes of one partition // *** CHANGED ***

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////

$arRunFileList = array();
$szScriptOutputDir = $szRootScriptOutputDir;
makeDir($szScriptOutputDir);

foreach($arPat2PathList as $szFPPatName => $szVideoPath)
{
	$nMaxVideosPerPat = $arMaxVideosPerPatList[$szFPPatName];
	$nNumVideosPerHost = max(1, intval($nMaxVideosPerPat/$nMaxHostsPerPat)); // Oct 19

	$arCmdLineList =  array();
	for($j=0; $j<$nMaxVideosPerPat; $j+=$nNumVideosPerHost)
	{
		$nStart = $j;
		$nEnd = $nStart+$nNumVideosPerHost;

		$szFPLogFN = "/dev/null";
			
		// <FPPatName> <VideoPath> <Start> <End>
		$szParam = sprintf("%s %s %s %s",
				$szFPPatName, $szVideoPath, $nStart, $nEnd);

		$szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);

		$arCmdLineList[] = $szCmdLine;
	}


	$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szFPPatName); // specific for one set of data
	if(sizeof($arCmdLineList) > 0 )
	{
		saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
		$arRunFileList[] = $szFPOutputFN;
	}

}

$szFPOutputFN = sprintf("%s/runme.qsub.%s.all.sh", $szScriptOutputDir, $szCoreScriptName);
saveDataFromMem2File($arRunFileList, $szFPOutputFN, "wt");

?>
