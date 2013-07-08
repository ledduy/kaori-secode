<?php

/**
 * 		@file 	ksc-BOW-GetKeyFrameSize.php
 * 		@brief 	Get keyframe size (Width, Height) used in BOW-SoftGrid.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Jul 2013.
 */

//*** Update Jul 09, 2012
// Customize for tvsin2012
//--> New scheme: keyframes are packed in some tar files, we need to unpack before getting the size

//*** Update Jun 26, 2012
// Customize for imageclef2012

////////////////////////////////////

// Update Jun 17
// No comment

//////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";


//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

$szPatName = "devel-nistNew"; // testNew
$szTVYear = "tv2012";

//$szPatName = "test.iacc.2.ANew"; // testNew
//$szTVYear = "tv2013";

$arInvCode = array(
		"devel-nistNew" => 2012,
		"test.iacc.2.ANew" => 2013,
		"test.iacc.2.BNew" => 2014,
		"test.iacc.2.CNew" => 2015);

$nStartVideoID = 0;
$nEndVideoID = 500;  // tv2012.devel-nistNew ~ 200 videos, tv2012.testNew ~ 400 videos

//////////////////// END FOR CUSTOMIZATION ////////////////////

///////////////////////////// MAIN ////////////////////////////////

if($argc != 4)
{
	printf("Usage: %s <PatName> <StartVideoID> <EndVideoID>\n", $argv[0]);
	printf("Usage: %s %s %s %s\n", $argv[0], $szPatName, $nStartVideoID, $nEndVideoID);
	exit();
}

$szPatName = $argv[1]; 
$nStartVideoID = intval($argv[2]);
$nEndVideoID = intval($argv[3]);  // tv2012.devel-nistNew ~ 200 videos, tv2012.testNew ~ 400 videos
$szTVYear = sprintf("tv%d", $arInvCode[$szPatName]);

$szMetaDataDir = sprintf("%s/%s/%s", $szRootMetaDataDir, $szTVYear, $szPatName);

// .lst files must be copied to metadata dir first
// tv2012.devel-nistNew.lst
$szFPVideoListFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szPatName);

getKeyFrameSizeForOneList($szFPVideoListFN,
$szMetaDataDir, $szRootKeyFrameDir, $nStartVideoID, $nEndVideoID);

//////////////////////////////////// FUNCTIONS ///////////////////////////////////
function getKeyFrameSizeForOneVideoProgram($szFPOutputFN, $szFPPrgInputFN, $szServerKeyFrameDir)
{
	global $gszTmpDir;
	
	//<-- Modified Jul 06, 2012
	// !!! IMPORTANT
	$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");

	$szVideoID = basename($szServerKeyFrameDir);
	$szLocalKeyFrameDir = sprintf("%s/%s/%s", $gszTmpDir, $szScriptBaseName, $szVideoID);
	makeDir($szLocalKeyFrameDir);
	
	// download and extract ALL .tar files from the server to the local dir
	$arTarFileList = collectFilesInOneDir($szServerKeyFrameDir, "", ".tar");
	
	foreach($arTarFileList as $szTarFileName)
	{
		$szCmdLine = sprintf("tar -xvf %s/%s.tar -C %s", $szServerKeyFrameDir, $szTarFileName, $szLocalKeyFrameDir);
		execSysCmd($szCmdLine);
	}
	
	loadListFile($arKFList, $szFPPrgInputFN);

	$arOutput = array();
	foreach($arKFList as $szKeyFrameID)
	{
		$szFPKeyFrameFN = sprintf("%s/%s.jpg", $szLocalKeyFrameDir, $szKeyFrameID);  // get file from local dir
		if(file_exists($szFPKeyFrameFN))
		{
			$arInfo = getimagesize($szFPKeyFrameFN);
			$nWidth = $arInfo[0];
			$nHeight = $arInfo[1];
			
			$arOutput[] = sprintf("%s #$# %s #$# %s", $szKeyFrameID, $nWidth, $nHeight);
			
		}
		else
		{
			printf("File not found [%s]\n", $szFPKeyFrameFN);
		}
	}

	saveDataFromMem2File($arOutput, $szFPOutputFN);
	
	// clean up
	$szCmdLine = sprintf("rm -rf %s", $szLocalKeyFrameDir);
	execSysCmd($szCmdLine);
}

function getKeyFrameSizeForOneList($szFPVideoListFN,
$szRootMetaDataDir, $szRootKeyFrameDir, $nStart=0, $nEnd=1)
{
	global $gszDelim;

	$nNumVideoProgs = loadListFile($arVideoProgList, $szFPVideoListFN);

	if($nEnd>$nNumVideoProgs)
	{
		$nEnd = $nNumVideoProgs;
	}

	printf("### Getting keyframe size for videos [%d-%d)\n", $nStart, $nEnd);
	for($i=$nStart; $i<$nEnd; $i++)
	{
		// TRECVID2005_1 #$# 20041116_110000_CCTV4_NEWS3_CHN #$# tv2005/test
		$szLine = $arVideoProgList[$i];

		$arTmp = explode($gszDelim, $szLine);

		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);

		$szProgInputDir = $szRootMetaDataDir;
		$szFPPrgInputFN = sprintf("%s/%s.prg", $szProgInputDir, $szVideoID);

		$szKFInputDir = sprintf("%s/%s/%s", $szRootKeyFrameDir, $szVideoPath, $szVideoID);

		$szFPOutputFN = sprintf("%s/%s.prgx", $szProgInputDir, $szVideoID);  // extension of .prg
		getKeyFrameSizeForOneVideoProgram($szFPOutputFN, $szFPPrgInputFN, $szKFInputDir);
	}
}

?>
