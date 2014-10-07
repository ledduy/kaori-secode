<?php
/**
 * 		Generate .bat file to extract baseline features.
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 08 Oct 2014.
 */

// !!! IMPORTANT !!!
// Ave Speed: 10KF/second

/*
 New version (Jul 15 2010)
printf("Usage: %s <App Config File> <FP FeatureConfig File> <Pat File Name> <Pat Dir> <Prg Dir> <KeyFrame Dir> <Output Dir> <Start ID> <End ID>\n", argv[0]);
printf("#FP App Config File: File contains configuration of the application (e.g. ext of image file list, sub-path)\n");
printf("#FP Feature Config File: File contains feature configuration (desc, type, grid, color space, historgram)\n");
printf("#Pat File Name: Only name, no ext (usually has .lst ext), no path. This file contains a list of .prg file names (no path, no ext). \n");
printf("#Pat Dir: Directory containing <Pat File Name>. Combined with <Pat File Name> to locate the file!\n");
printf("#Prg Dir: Directory containing *.prg files that are mentioned in <Pat File Name>. Combined with the names in .pat file to locate the file (The file MUST have .prg ext)!\n");
printf("#Root Keyframe Dir: Directory containing keyframe *.jpg. Combined with the names in .prg file to locate the file (Keyframes usually have .jpg ext)!\n");
printf("#Output Dir: Directory containing output feature file. One feature file for one prg file!\n");
*/


///////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

//////////////////// THIS PART FOR CUSTOMIZATION //////////////////// 

// Dir for feature config files
$szFeatureConfigDir = $gszFeatureConfigDir; //"BaselineFeatureConfig";

/* config.tv.txt
 Pat: lst
 Prg: prg
 Video: mpg
 Image: jpg
 Path2KeyFrame:
 */
$gszFPFeatureAppConfigFN = sprintf("%s/config.tv.txt", $szFeatureConfigDir);

//////////////////// END FOR CUSTOMIZATION ////////////////////

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

$gszKeyFramePathDir = "keyframe-5";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootKeyFrameDir = sprintf("%s/%s", $szRootDir, $gszKeyFramePathDir);
$szRootFeatureDir = sprintf("%s/feature/%s", $szRootDir, $gszKeyFramePathDir);
$szRootMetaDataDir = sprintf("%s/metadata/%s", $szRootDir, $gszKeyFramePathDir);
$szRootPatFileDir = $szRootMetaDataDir;
$szRootPrgFileDir = $szRootMetaDataDir;

//////////////////// END FOR CUSTOMIZATION ////////////////////

////////////////////////// MAIN ////////////////////////

if($argc != 7)
{
	printf("Usage: %s <FeatureExt> <FeatureConfigFile> <PatName> <VideoPath> <Start> <End>\n", $argv[0]);
	exit(1);
}

$szFeatureExt = $argv[1];
$szFPFeatureConfigFN = $argv[2];
$szPatName = $argv[3];
$szVideoPath = $argv[4]; // path from archive name to keyframe dir, eg. tv2004/devel
$nStart = intval($argv[5]);
$nEnd = intval($argv[6]);

$szFPLogFN = sprintf("ksc-Feature-ExtractBaselineFeature-%s.log", $szFeatureExt); // *** CHANGED ***

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]", 
$szStartTime, 
$argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// main command
extractBaselineFeature($szRootFeatureDir, $szRootKeyFrameDir, 
$szRootPatFileDir, $szRootPrgFileDir, $szPatName, $szVideoPath, $szFeatureExt,
$szFPFeatureConfigFN, $nStart, $nEnd);

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s"); 
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]", 
$szStartTime, $szFinishTime,
$argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

////////////////////////// FUNCTIONS ////////////////////////
/**
 * 	This function is used if no .prg file (~ keyframe list) exists
 *
 * 	@param $szRootKeyFrameDir
 * 	@param $szPath
 */
function getVideoProgsForOnePat($szRootKeyFrameDir, $szPath="")
{
	// scan to get all video programs - the keyframes of each video program is stored in one dir

	$szKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szPath);

	$arVideoProgList = collectDirsInOneDir($szKeyFrameDir);
	sort($arVideoProgList);

	return $arVideoProgList;
}

/**
 * 	$szVideoPath --> path from video archive name to location of video,
 * 		e.g. trecvid/tv2005/devel
 * 			+ trecvid: archive name,
 * 			+ tv2005/devel: video path
 * 	szRootMetaDataDir, szRootKeyFrameDir, szRootFeatureDir already included archive name, i.e path/trecvid
 *
 * 	Directories for the feature are created!!!
 */

function extractBaselineFeature($szRootFeatureDir, $szRootKeyFrameDir, $szRootPatFileDir, $szRootPrgFileDir,
		$szPatName, $szVideoPath, $szFeatureExt, $szFeatureConfigFile, $nStart=0, $nEnd=100000)
{
	global $garAppConfig; // to access the app name of FeatureExtractor
	global $gszFPFeatureAppConfigFN;

	// .lst is required for feature extraction application
	$szFPInputFN = sprintf("%s/%s.lst", $szRootPatFileDir, $szPatName);

	// $arVideoProgList is UNUSED - just keep the code for reference
	if(!file_exists($szFPInputFN))
	{
		printf("File [%s] does not exist!\n", $szFPInputFN);
		exit(1);
		
		$arVideoProgList = getVideoProgsForOnePat($szRootKeyFrameDir, $szVideoPath);
		saveDataFromMem2File($arVideoProgList, $szFPInputFN, "wt");
	}
	else
	{
		$nNumVideos = loadListFile($arVideoProgList, $szFPInputFN);
	}

	// !!! IMPORTANT !!!
	// video and keyframe share the same path.
	$szKeyFrameDir =  sprintf("%s/%s", $szRootKeyFrameDir, $szVideoPath); 

	// follow the same rule: <feature> + <nsc.etc.g_lbp> + <tv2005/devel>
	$szFeatureDir = sprintf("%s/%s/%s", $szRootFeatureDir, $szFeatureExt, $szVideoPath);
	makeDir($szFeatureDir);

	$szPrgFileDir = sprintf("%s/%s", $szRootPrgFileDir, $szVideoPath);
	makeDir($szPrgFileDir);

	// 	Usage: ./FeatureExtractorCmd <App Config File> <FP FeatureConfig File>
	// <Pat File Name> <Pat Dir> <Prg Dir> <KeyFrame Dir>
	// <Output Dir> <Start ID> <End ID>
	$szCmdLine = sprintf("%s %s %s %s %s %s %s %s %d %d", $garAppConfig["BL_FEATURE_EXTRACT_APP"],
			$gszFPFeatureAppConfigFN, $szFeatureConfigFile,
			$szPatName, $szRootPatFileDir, $szPrgFileDir,
			$szKeyFrameDir, $szFeatureDir, $nStart, $nEnd);

	execSysCmd($szCmdLine);
}

?>
