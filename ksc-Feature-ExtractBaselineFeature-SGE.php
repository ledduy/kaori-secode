<?php
/**
 * 		Generate jobs for SGE to generate features.
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 08 Oct 2014.
 */

//--> Update 31 May 2013
// introduce new var $gszKeyFramePathDir for script output dir

/////////////////// IMPORTANT PARAMS /////////////////////////

// Only some good configs are selected
// LBP: g4q30, g4q59
// EOH: g4q36, g5q36
// CM: YCrCb, RGB@g6q3
// CH: HSV, Luv@g5q8

/////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

/*
 * 	Params:
* 		+ Feature
* 		+ Video Path
*/

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////
$szProjectCodeName = "kaori-secode-vsd2014"; // CHANGED FOR VSD14
$szCoreScriptName = "ksc-Feature-ExtractBaselineFeature"; // *** CHANGED ***

$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

// !!! IMPORTANT
$szFeatureConfigDir = $gszFeatureConfigDir; //"BaselineFeatureConfig";
$szFeatureConfigDir = sprintf("%s/%s", $szSGEScriptDir, $szFeatureConfigDir);  // use the same script dir

global $gszKeyFramePathDir; // keyframe-5
$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName, $gszKeyFramePathDir);
makeDir($szRootScriptOutputDir);

//////////////////// END FOR CUSTOMIZATION ////////////////////

// Only some good configs are selected
// LBP: g4q30, g4q59
// EOH: g4q36, g5q36
// CM: YCrCb, RGB@g6q3
// CH: HSV, Luv@g5q8

$arFeatureList = array();
$arFeatureList['g_lbp'] = array(
//"nsc.cCV_GRAY.g2.q10.g_lbp",
//"nsc.cCV_GRAY.g3.q10.g_lbp",
//"nsc.cCV_GRAY.g4.q10.g_lbp",
//"nsc.cCV_GRAY.g5.q10.g_lbp",
//"nsc.cCV_GRAY.g6.q10.g_lbp",
//"nsc.cCV_GRAY.g2.q30.g_lbp",
//"nsc.cCV_GRAY.g3.q30.g_lbp",
//		"nsc.cCV_GRAY.g4.q30.g_lbp",
		//"nsc.cCV_GRAY.g5.q30.g_lbp",
//"nsc.cCV_GRAY.g6.q30.g_lbp",
//"nsc.cCV_GRAY.g2.q59.g_lbp",
//"nsc.cCV_GRAY.g3.q59.g_lbp",
		"nsc.cCV_GRAY.g4.q59.g_lbp",
		//"nsc.cCV_GRAY.g5.q59.g_lbp",
//"nsc.cCV_GRAY.g6.q59.g_lbp"
);

$arFeatureList['g_cm'] = array(
//"nsc.cCV_HSV.g2.q3.g_cm",
//"nsc.cCV_HSV.g3.q3.g_cm",
 "nsc.cCV_HSV.g4.q3.g_cm",
// "nsc.cCV_HSV.g5.q3.g_cm",
//"nsc.cCV_HSV.g6.q3.g_cm",
//"nsc.cCV_RGB.g2.q3.g_cm",
//"nsc.cCV_RGB.g3.q3.g_cm",
"nsc.cCV_RGB.g4.q3.g_cm",
//"nsc.cCV_RGB.g5.q3.g_cm",
//		"nsc.cCV_RGB.g6.q3.g_cm",
		//"nsc.cCV_YCrCb.g2.q3.g_cm",
//"nsc.cCV_YCrCb.g3.q3.g_cm",
"nsc.cCV_YCrCb.g4.q3.g_cm",
//"nsc.cCV_YCrCb.g5.q3.g_cm",
//		"nsc.cCV_YCrCb.g6.q3.g_cm",
		//"nsc.cCV_Luv.g2.q3.g_cm",
//"nsc.cCV_Luv.g3.q3.g_cm",
"nsc.cCV_Luv.g4.q3.g_cm",
//"nsc.cCV_Luv.g5.q3.g_cm",
//"nsc.cCV_Luv.g6.q3.g_cm"
);
$arFeatureList['g_ch'] = array(
//"nsc.cCV_HSV.g2.q8.g_ch",
//"nsc.cCV_HSV.g3.q8.g_ch",
"nsc.cCV_HSV.g4.q8.g_ch",
//		"nsc.cCV_HSV.g5.q8.g_ch",
		//"nsc.cCV_HSV.g6.q8.g_ch",
//"nsc.cCV_RGB.g2.q8.g_ch",
//"nsc.cCV_RGB.g3.q8.g_ch",
"nsc.cCV_RGB.g4.q8.g_ch",
//"nsc.cCV_RGB.g5.q8.g_ch",
//"nsc.cCV_RGB.g6.q8.g_ch",
//"nsc.cCV_YCrCb.g2.q8.g_ch",
//"nsc.cCV_YCrCb.g3.q8.g_ch",
"nsc.cCV_YCrCb.g4.q8.g_ch",
//"nsc.cCV_YCrCb.g5.q8.g_ch",
//"nsc.cCV_YCrCb.g6.q8.g_ch",
//"nsc.cCV_Luv.g2.q8.g_ch",
//"nsc.cCV_Luv.g3.q8.g_ch",
"nsc.cCV_Luv.g4.q8.g_ch",
//		"nsc.cCV_Luv.g5.q8.g_ch",
		//"nsc.cCV_Luv.g6.q8.g_ch"
);

$arFeatureList['g_eoh'] = array(
//"nsc.cCV_GRAY.g2.q12.g_eoh",
//"nsc.cCV_GRAY.g3.q12.g_eoh",
//"nsc.cCV_GRAY.g4.q12.g_eoh",
//"nsc.cCV_GRAY.g5.q12.g_eoh",
//"nsc.cCV_GRAY.g6.q12.g_eoh",
//"nsc.cCV_GRAY.g2.q18.g_eoh",
//"nsc.cCV_GRAY.g3.q18.g_eoh",
//"nsc.cCV_GRAY.g4.q18.g_eoh",
//"nsc.cCV_GRAY.g5.q18.g_eoh",
//"nsc.cCV_GRAY.g6.q18.g_eoh",
//"nsc.cCV_GRAY.g2.q36.g_eoh",
//"nsc.cCV_GRAY.g3.q36.g_eoh",
		"nsc.cCV_GRAY.g4.q36.g_eoh",
//		"nsc.cCV_GRAY.g5.q36.g_eoh",
		//"nsc.cCV_GRAY.g6.q36.g_eoh"
//"nsc.cCV_GRAY.g2.q72.g_eoh",
//"nsc.cCV_GRAY.g3.q72.g_eoh",
//"nsc.cCV_GRAY.g4.q72.g_eoh",
//"nsc.cCV_GRAY.g5.q72.g_eoh",
//"nsc.cCV_GRAY.g6.q72.g_eoh"
);


$arPat2PathList = array(
	"devel2011-new" => "devel2011-new", 
	"test2011-new" => "test2011-new", 
	"test2012-new" => "test2012-new", 
	"test2013-new" => "test2013-new",
	"test2014-new" => "test2014-new"		
);
//////////////////////////////// START //////////////////////////////

$arAllRunFileList = array();
foreach($arFeatureList as $szFeatureType => $arFeatureExt)
{
	$arFeatureTypeRunFileList = array();
	foreach($arFeatureExt as $szFeatureExt)
	{
		$szFPFeatureConfigFN = sprintf("%s/ConfigFile.%s.txt", $szFeatureConfigDir, $szFeatureExt);

		$szScriptOutputDir = sprintf("%s/%s", $szRootScriptOutputDir, $szFeatureExt);
		makeDir($szScriptOutputDir);

		$arRunFileList = array();

		// $arPat2PathList --> "tv2012.devel-nistNew" => "tv2012/devel-nistNew",
		foreach($arPat2PathList as $szFPPatName => $szVideoPath)
		{
			$arCmdLineList =  array();

			// $arMaxVideosPerPatList --> "tv2012.devel-nistNew" => 200, // Precise:
			$nMaxVideosPerPat = $arMaxVideosPerPatList[$szFPPatName];
			$nMaxHostsPerPat = $arMaxHostsPerPatList[$szFPPatName];
			$nNumVideosPerHost = max(1, intval($nMaxVideosPerPat/$nMaxHostsPerPat)); // Oct 19

			printf("DB-%s - %s\n", $nMaxVideosPerPat, $nNumVideosPerHost);

			for($j=0; $j<$nMaxVideosPerPat; $j+=$nNumVideosPerHost)
			{
				$nStartVideoID = $j;
				$nEndVideoID = $nStartVideoID + $nNumVideosPerHost;

				$szFPLogFN = "/dev/null";

				// <FeatureExt> <FeatureConfigFile> <PatName> <VideoPath> <Start> <End>
				$szParam = sprintf("%s %s %s %s %s %s",
						$szFeatureExt, $szFPFeatureConfigFN,
						$szFPPatName,
						$szVideoPath, $nStartVideoID, $nEndVideoID);
				$szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);

				$arCmdLineList[] = $szCmdLine;
			}

			$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szFeatureExt, $szFPPatName); // specific for one set of data
			if(sizeof($arCmdLineList) > 0 )
			{
				saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
				$arRunFileList[] = $szFPOutputFN;
				$arAllRunFileList[] = $szFPOutputFN;
					
				$arFeatureTypeRunFileList[] = $szFPOutputFN;
			}
		}
		$szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.all.sh", $szScriptOutputDir, $szCoreScriptName, $szFeatureExt); // specific for one set of data
		saveDataFromMem2File($arRunFileList, $szFPOutputFN, "wt");
	}
	$szFPOutputFNz = sprintf("%s/runme.qsub.%s.%s.all.sh", $szRootScriptOutputDir, $szCoreScriptName, $szFeatureType); // all - just one click for all
	saveDataFromMem2File($arFeatureTypeRunFileList, $szFPOutputFNz, "wt");
}

$szFPOutputFN = sprintf("%s/runme.qsub.%s.all.sh", $szRootScriptOutputDir, $szCoreScriptName); // all - just one click for all
saveDataFromMem2File($arAllRunFileList, $szFPOutputFN, "wt");

////////////////////////////////////// HISTORY //////////////////////////////
// Update Aug 21
// make similar with ExtractRawAffCovSIFTFeature-SGE
// feature config dir: $szFeatureConfigDir = "/net/per900b/raid0/ledduy/kaori-secode/php/BaselineFeatureConfig";
// Specify max videos per pat, $arMaxVideosPerPatList
// log name: CoreScriptName.FeatureExt.FPPatName.Start-End
// $szFPLogFN = sprintf("%s/%s.%s.%s-%d-%d.log", $szLogDir, $szCoreScriptName,
//				$szFeatureExt, $szFPPatName, $nStart, $nEnd);
// log dir: FeatureExt/VideoPath
// $szLogDir = sprintf("%s/%s/%s", $szRootLogDir, $szFeatureExt, $szVideoPath);
// $szScriptOutputDir = sprintf("%s/%s", $szRootScriptOutputDir, $szFeatureExt);

////////////////////////////////////////////

?>
