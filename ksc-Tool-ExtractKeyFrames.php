<?php

/**
 * 		@file 	ksc-Tool-ExtractKeyFrames.php
 * 		@brief 	Extract Keyframes Using FFMPEG-PHP.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 *		@bug 	Force duration in one shot must have at least 3 keyframes
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */


// Update Jul 04, 2012
//--> $nMaxKFPerShot = 5 !!! IMPORTANT !!!  --> must sync with ksc-Tool-ExtractKeyFrames-Inconsistency.php

//--> change params
// $szFPPatName = "tv2012.devel";
// $szVideoPath = "tv2012/devel";

//--> Fix bug to enforce no duplicate, maxKF 

//--> 	$nFrameEnd = $nFrameStart + $nDuration -1; // to avoid keyframe at boundary

//--> After running this script, run ksc-Tool-CheckKeyFrameExtraction.php

/// !!! FOR VIDEOS THAT ARE INCONSISTENCY DUE TO FRAMERATE OF FFMPEG
// --> Another tool is used for treatment
// IMPORTANT --> $nFrameID >= 1 && $nFrameID < $nFrameCount
/*
 if(!$nFrameID)
 {
continue;
}
///
if($nFrameEnd>$nFrameCount)
{
$nFrameEnd = $nFrameCount;
}
*/

/////////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

// Checking ffmpeg-php tool
$extension = "ffmpeg";
$extension_soname = $extension . "." . PHP_SHLIB_SUFFIX;
$extension_fullname = PHP_EXTENSION_DIR . "/" . $extension_soname;

// load extension
if (!extension_loaded($extension)) {
	dl($extension_soname) or die("Can't load extension $extension_fullname\n");
}

$gszVideoExt = "mp4";  // *** CHANGED ***

// List of videos that are inconsistency between .sb and .lig.sb files
// --> the list is generated by ksc-Tool-ParseShotBoundaryXML.php
$arBlackList = array();

$szFPPatName = "test.iacc.2.A";
$szVideoPath = "tv2013/test.iacc.2.A";
$nStart = 0;
$nEnd = 1;


$arCode = array(
		2013 => "iacc.2.A",
		2014 => "iacc.2.B",
		2015 => "iacc.2.C");

///////////////////// START ///////////////

if($argc != 5)
{
	printf("Usage: %s <FPPatName> <VideoPath> <Start> <End>\n", $argv[0]);
	printf("Usage: %s %s %s %s %s\n", $argv[0], $szFPPatName, $szVideoPath, $nStart, $nEnd);
	exit();
}

$szFPPatName = $argv[1];
$szVideoPath = $argv[2];
$nStart = intval($argv[3]);
$nEnd = intval($argv[4]);

//////////////////////////////// THIS PART FOR CUSTOMIZATION //////////////////

$szRootDir = $gszRootBenchmarkDir; // "/net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012";

$szRootMetaDataDir =sprintf("%s/metadata/keyframe-5", $szRootDir);
makeDir($szRootMetaDataDir);
$szRootVideoDir =sprintf("%s/video", $szRootDir);
$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);

$nMaxKFPerShot = 5;  // *** CHANGED ***

/////////////////////////////////////////////////////////////
// .lst files must be copied to metadata dir first
// tv2007.devel.lst
$szFPVideoListFN = sprintf("%s/%s.lst",
		$szRootMetaDataDir, $szFPPatName);
$szMetaDataDir = sprintf("%s/%s",
		$szRootMetaDataDir, $szVideoPath);
makeDir($szMetaDataDir);

// load data for black list
$szFPBlackListFN = sprintf("%s/ErrInconsistency.%s.csv", $szRootMetaDataDir, $szFPPatName);
loadListFile($arRawList, $szFPBlackListFN);
foreach($arRawList as $szLine)
{
	$arTmp = explode("#$#", $szLine);
	$szVideoID = trim($arTmp[0]);
	$szVideoName = trim($arTmp[1]);
	
	$arBlackList[$szVideoID] = $szVideoName;
}
print_r($arBlackList);

$szFPLogFN = $szFPErrLogFN = "ksc-Tool-ExtractKeyFrames.log";
$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]",
		$szStartTime, $argv[1], $argv[2], $argv[3], $argv[4]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

extractKeyFrameForOneList($szFPVideoListFN,
		$szMetaDataDir, $szRootVideoDir, $szRootKeyFrameDir,
		$nMaxKFPerShot, $nStart, $nEnd);

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]",
		$szStartTime, $szFinishTime, $argv[1], $argv[2], $argv[3], $argv[4]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");


/////////////////////////////////////////// FUNCTIONS ///////////////////////////////////////////

function extractKeyFramesForOneVideo(
		$szFPShotBoundaryFN,
		$szVideoDir,
		$szMetaDataDir,
		$szKeyFrameDir,
		$nMaxKFPerShot=5)
{

	global $arBlackList;

	global $gszVideoExt;
	global $szFPErrLogFN;

	$nQuality = 100; // 100 --> Best

	$nNumRows = loadListFile($arList, $szFPShotBoundaryFN);
	/*
	 00001-Fujimoristas_invade_Amnisty_International_marathon._-o-_.MOV00010_64kb_512kb
	TRECVID2011_11645
	TRECVID2011_11645.shot11645_1 #$# 0 #$# 243
	TRECVID2011_11645.shot11645_2 #$# 243 #$# 3
	TRECVID2011_11645.shot11645_3 #$# 246 #$# 186
	*/
	$szVideoName = trim($arList[0]);
	$szVideoID = trim($arList[1]);

	if(isset($arBlackList[$szVideoID]))
	{
		$arLog = array();
		$szErr = sprintf("Skipping video [%s-%s]", $szVideoID, $arBlackList[$szVideoID]);
		$arLog[] = $szErr;
		saveDataFromMem2File($arLog, $szFPErrLogFN, "a+t");
		return;
	}

	$szFPMetaDataOutputFN = sprintf("%s/%s.prg", $szMetaDataDir, $szVideoID);
	if(file_exists($szFPMetaDataOutputFN))
	{
		printf("###Skipping [%s]\n", $szVideoID);
		return;
	}
	$szFPVideoFN = sprintf("%s/%s.%s", $szVideoDir, $szVideoName, $gszVideoExt);
	printf("### Processing video [%s]\n", $szFPVideoFN);

	$objVideo = new ffmpeg_movie ($szFPVideoFN);
	if(!is_object($objVideo))
	{
		$arLog = array();
		$szErr = sprintf("Error in opening video [%s]", $szFPVideoFN);
		$arLog[] = $szErr;
		saveDataFromMem2File($arLog, $szFPErrLogFN, "a+t");
		exit($szErr);
	}

	$nFrameCount = $objVideo->getFrameCount();
	printf("### Frame count = %s\n", $nFrameCount);

	$arMetaDataList = array();
	//print_r($arList);
	for($iz=2; $iz<$nNumRows; $iz++)
	{
		printf("### Parsing [%s]\n", $arList[$iz]);

		// TRECVID2005_141.shot141_1 #$# 0 #$# 75
		$arTmp = explode("#$#", $arList[$iz]);
		$szShotID = trim($arTmp[0]);
		$nFrameStart = intval($arTmp[1]);
		$nDuration = intval($arTmp[2]);
		
		if($nDuration <= 2)
		{
			continue; // too short shot
		}

		$nFrameEnd = $nFrameStart + $nDuration -1; // to avoid keyframe at boundary

		if($nFrameEnd>$nFrameCount)
		{
			$nFrameEnd = $nFrameCount;
		}

		$nFrameInterval = max(1, 1.0*$nDuration/$nMaxKFPerShot);  // float val

		// TRECVID2005_276.shot276_1.RKF_0.Frame_27
		$arKFList = array();
		$nMiddleFrame = intval($nFrameStart + 0.5*$nDuration);

		// from middle to right
		$nCount = 0;
		$nMaxKFPerHalfShot = round($nMaxKFPerShot*0.5);
		printf("Half - %d - Middle: %d - Interval: %f\n", $nMaxKFPerHalfShot, $nMiddleFrame, $nFrameInterval);
		for($nFrameID=$nMiddleFrame; $nFrameID<$nFrameEnd; $nFrameID+=$nFrameInterval)
		{
			$arKFList[$nFrameID] = 1;  // to enforce no duplicate
			$nCount++;
			if($nCount > $nMaxKFPerHalfShot)
			{
				break;
			}
		}

		// from middle to left
		for($nFrameID=$nMiddleFrame-$nFrameInterval; $nFrameID>=$nFrameStart; $nFrameID-=$nFrameInterval)
		{
			$arKFList[$nFrameID] = 1;  // to enforce no duplicate
			$nCount++;
			if($nCount >= $nMaxKFPerShot)
			{
				break;
			}
		}
		ksort($arKFList);
		$nKFIndex = 0;
		print_r($arKFList);
		foreach($arKFList as $nFrameID => $nTzzz)
		{
			$szKeyFrameID = sprintf("%s.RKF_%d.Frame_%d", $szShotID, $nKFIndex, $nFrameID);

			// IMPORTANT --> $nFrameID >= 1
			if(!$nFrameID)
			{
				continue;
			}

			$objFrame = $objVideo->getFrame($nFrameID);
			if(!is_object($objFrame))
			{
				$arLog = array();
				$szErr = sprintf("Error in seeking to frame [%s]", $nFrameID);
				$arLog[] = $szErr;
				saveDataFromMem2File($arLog, $szFPErrLogFN, "a+t");
				continue;
			}

			$objGDFrame = $objFrame->toGDImage();

			$szFPOutputFN = sprintf("%s/%s.jpg", $szKeyFrameDir, $szKeyFrameID);
			printf("--> Saving Frame [%s]\n", $szKeyFrameID);
			$nRet = imagejpeg($objGDFrame, $szFPOutputFN, $nQuality);
			if(!$nRet)
			{
				$arLog = array();
				$szErr = sprintf("Error in saving frame output image [%s]", $nFrameID);
				$arLog[] = $szErr;
				saveDataFromMem2File($arLog, $szFPErrLogFN, "a+t");
				continue;
			}

			$arMetaDatarList[] = $szKeyFrameID;

			$nKFIndex++;
			if($nKFIndex > $nMaxKFPerShot)
			{
				break;
			}
		}
		if(!$nKFIndex)
		{
			$arLog = array();
			$arLog[] = sprintf("No keyframe for [%s]", $arList[$iz]);
			saveDataFromMem2File($arLog, $szFPErrLogFN, "a+t");
		}
	}

	$szFPMetaDataOutputFN = sprintf("%s/%s.prg", $szMetaDataDir, $szVideoID);
	saveDataFromMem2File($arMetaDatarList, $szFPMetaDataOutputFN);
}

function extractKeyFrameForOneList($szFPVideoListFN,
		$szMetaDataDir, $szRootVideoDir, $szRootKeyFrameDir,
		$nMaxKFPerShot=5, $nStart=0, $nEnd=1)
{
	global $gszDelim;

	$nNumVideoProgs = loadListFile($arVideoProgList, $szFPVideoListFN);

	if($nEnd>$nNumVideoProgs)
	{
		$nEnd = $nNumVideoProgs;
	}

	printf("### Extracting keyframes for videos [%d-%d)\n", $nStart, $nEnd);
	for($i=$nStart; $i<$nEnd; $i++)
	{
		// TRECVID2005_1 #$# 20041116_110000_CCTV4_NEWS3_CHN #$# tv2005/test
		$szLine = $arVideoProgList[$i];

		$arTmp = explode($gszDelim, $szLine);

		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);

		$szKFOutputDir = sprintf("%s/%s/%s", $szRootKeyFrameDir, $szVideoPath, $szVideoID);
		makeDir($szKFOutputDir);

		$szVideoDir = sprintf("%s/%s", $szRootVideoDir, $szVideoPath);

		$szShotInfoDir = $szMetaDataDir;
		$szFPShotBoundaryFN = sprintf("%s/%s.sb", $szShotInfoDir, $szVideoID);
		//printf("%s\n", $szFPShotBoundaryFN);

		extractKeyFramesForOneVideo(
				$szFPShotBoundaryFN,
				$szVideoDir,
				$szMetaDataDir,
				$szKFOutputDir,
				$nMaxKFPerShot);
	}

}

?>
