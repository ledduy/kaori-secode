<?php

/**
 * 		@file 	ksc-Tool-OrganizeDevelTestData-TV.php
 * 		@brief 	Organize images of devel partition and test partition into subdirs.
 * 				For each input dir, generate one .tar.gz file (pack all keyframes) and then copy to the dest dir
 * 				Copying can be avoided by using tar -cvf FileInDestDir -C SourceDir
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */

//*** Update Jul 05, 2012
// Must run on grid due to huge processing time
// Use tar file to combine keyframes of one video program into ONE file

// !!! IMPORTANT !!!
// Becareful about a+t mode --> MUST delete old files before running

// JOBS
// Create .prg file  --> list of keyframes of one video program
// Create test.lst file --> list of video programs
// One video program ~ 100 images ==> devel.lst --> 150 videos

/////////////////////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

$szRootDir = $gszRootBenchmarkDir; // "/net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012";

// map from src to dest
$arPatList = array(
		"test.iacc.2.A" => "test.iacc.2.ANew", 
		"test.iacc.2.B" => "test.iacc.2.BNew",
		"test.iacc.2.C" => "test.iacc.2.CNew",
); 

$arMaxVideoPerDestPatList = array(
		"test.iacc.2.A" => 300, 
		"test.iacc.2.B" => 300,
		"test.iacc.2.C" => 300,
);

$arIDMapList = array(
		"devel-nist" => 0,
		"test.iacc.2.A" => 1,
		"test.iacc.2.B" => 2,
		"test.iacc.2.C" => 3,		
);

$arInvCode = array(
		"test.iacc.2.A" => 2013, 
		"test.iacc.2.B" => 2014, 
		"test.iacc.2.C" => 2015);


//////////////////////////////////////// START ///////////////////////////////

$szSrcDir = "test.iacc.2.A"; // or test
$nStartBlockID = 0;
$nEndBlockID = 0;

if($argc != 4)
{
	printf("Usage: %s <SrcPatName> <StartBlockID> <EndBlockID>\n", $argv[0]);
	printf("Usage: %s %s %s %s\n", $argv[0], $szSrcDir, $nStartBlockID, $nEndBlockID);
	exit();
}

$szSrcDir = $argv[1];
$nStartBlockID = intval($argv[2]);
$nEndBlockID = intval($argv[3]);

$szDestDir = $arPatList[$szSrcDir];

$nTVYear = $arInvCode[$szSrcDir]; 
$szTVYear = sprintf("tv%s", $nTVYear);

$szRootKeyFrameDir = sprintf("%s/keyframe-5/%s", $szRootDir, $szTVYear);

$szFPSrcDir = sprintf("%s/%s", $szRootKeyFrameDir, $szSrcDir);
makeDir($szFPSrcDir);

$szFPDestDir = sprintf("%s/%s", $szRootKeyFrameDir, $szDestDir);
makeDir($szFPDestDir);

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5/%s", $szRootDir, $szTVYear);
makeDir($szRootMetaDataDir);

$szSrcMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szSrcDir);
makeDir($szSrcMetaDataDir);

$szDestMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szDestDir);
makeDir($szDestMetaDataDir);

$szFPSrcVideoListFN = sprintf("%s/metadata/keyframe-5/%s.lst", $szRootDir, $szSrcDir);
$nNumVideos = loadListFile($arRawList, $szFPSrcVideoListFN);

$nNumVideosPerBlock = intval($nNumVideos/$arMaxVideoPerDestPatList[$szSrcDir]) + 1;
printf("### Num Videos Per Block: %d\n", $nNumVideosPerBlock);
$arNewVideoList = array();
$arCmdLineList = array();
$arSGECmdLineList = array();

for($nBlockID=$nStartBlockID; $nBlockID<$nEndBlockID; $nBlockID++)
{
	// new videoID --> NEWTRECVID2012_0zzz --> devel, NEWTRECVID2012_1zzz --> test
	$szNewVideoID = sprintf("NEWTV%d_%d%03d", $nTVYear-2000, $arIDMapList[$szSrcDir], $nBlockID+1); // ID starting from 1

	$szNewVideoName = $szNewVideoID;
	$szNewVideoPath = sprintf("%s/%s", $szTVYear, $szDestDir); // tv2012/devel-nist2012

	// tv2012/devel-nistNEW + newVideoID
	$szFPDestDir = sprintf("%s/%s/%s", $szRootKeyFrameDir, $szDestDir, $szNewVideoID);
	makeDir($szFPDestDir);

	$szFPTmpDestDir = sprintf("%s/%s/%s", $gszTmpDir, $szDestDir, $szNewVideoID);
	makeDir($szFPTmpDestDir);

	$arCmdLineList = array();

	// combine all keyframes of videos in this block into one .prg file
	$arNewKeyFrameList = array();
	for($j=0; $j<$nNumVideosPerBlock; $j++)
	{
		$nIndex = $nBlockID*$nNumVideosPerBlock+$j;

		if($nIndex>=$nNumVideos)
		{
			break;
		}

		$szLine = $arRawList[$nIndex];

		// TRECVID2011_11645 #$# 00001-Fujimoristas_invade_Amnisty_International_marathon._-o-_.MOV00010_64kb_512kb #$# tv2011/test
		$arTmp = explode("#$#", $szLine);
		$szVideoID = trim($arTmp[0]);
		$szVideoName = trim($arTmp[1]);
		$szVideoPath = trim($arTmp[2]);

		$szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootMetaDataDir, $szSrcDir, $szVideoID);

		// tv2012/devel-nist + videoID
		$szFPSrcDir = sprintf("%s/%s/%s", $szRootKeyFrameDir, $szSrcDir, $szVideoID);

		loadListFile($arFKList, $szFPKeyFrameListFN);
		foreach($arFKList as $szKeyFrameID)
		{
			$szFPSrcKeyFrameFN = sprintf("%s/%s.jpg", $szFPSrcDir, $szKeyFrameID);
				
			$szDestKeyFrameID = $szKeyFrameID;

			$szFPDestKeyFrameFN = sprintf("%s/%s.jpg", $szFPDestDir, $szDestKeyFrameID);

			$arNewKeyFrameList[] = $szDestKeyFrameID;
		}

		$szFPTarFN = sprintf("%s/%s.tar", $szFPDestDir, $szVideoID);

		// Use -C and . for excluding the path
		$szCmdLine = sprintf("tar -cvf %s -C %s .", $szFPTarFN, $szFPSrcDir);
		execSysCmd($szCmdLine);

	}

	$szFPNewKeyFrameListFN = sprintf("%s/%s.prg", $szDestMetaDataDir, $szNewVideoID);
	if(sizeof($arNewKeyFrameList))
	{
		saveDataFromMem2File($arNewKeyFrameList, $szFPNewKeyFrameListFN);
		$arNewVideoList[] = sprintf("%s #$# %s #$# %s", $szNewVideoID, $szNewVideoName, $szNewVideoPath);
	}
}

// tv2012.devel-nistNew.lst
$szFPNewVideoListFN = sprintf("%s/metadata/keyframe-5/%s.lst", $szRootDir, $szDestDir);
saveDataFromMem2File($arNewVideoList, $szFPNewVideoListFN, "a+t");

?>