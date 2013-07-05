<?php

/**
 * 		@file 	ksc-Tool-OrganizeDevelTestData-TV-SGE.php
 * 		@brief 	Organize images of devel partition and test partition into subdirs.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */

//*** Update Jun 25
// This is for optimization of using grid

// JOBS
// Create .prg file  --> list of keyframes of one video program
// Create test.lst file --> list of video programs
// One video program ~ 100 images ==> devel.lst --> 150 videos

/////////////////////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

$szRootDir = $gszRootBenchmarkDir; // "/net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012";

///////////////////////////// SGE JOBS ///////////////////////////////////

$szProjectCodeName = "kaori-secode-tvsin13"; // *** CHANGED ***
$szCoreScriptName = "ksc-Tool-OrganizeDevelTestData-TV";  // *** CHANGED ***

$szSGEScriptDir = $gszSGEScriptDir;  // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

//////////////////////////////////////// START ///////////////////////////////

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


$arMaxHostsPerPatList = array(
		"test.iacc.2.A" => 100,
		"test.iacc.2.B" => 100,
		"test.iacc.2.C" => 100,
		);

foreach($arPatList as $szSrcPatName => $szDestPatName)
{
	// for running on grid, one job --> one block
	$szFPLogFN = "/dev/null";
	$arSGECmdLineList = array();
	// 	printf("Usage: %s <SrcPatName> <StartBlockID> <EndBlockID>\n", $argv[0]);

	$nMaxBlocks = $arMaxVideoPerDestPatList[$szSrcPatName];
	$nNumBlocksPerJob = intval($nMaxBlocks/$arMaxHostsPerPatList[$szSrcPatName]);
	
	for($nBlockID=0; $nBlockID<$nMaxBlocks; $nBlockID+=$nNumBlocksPerJob)
	{
		$nStartBlockID = $nBlockID;
		$nEndBlockID = $nStartBlockID + $nNumBlocksPerJob;
		$szParam = sprintf("%s %s %s", $szSrcPatName, $nStartBlockID, $nEndBlockID);
		$szSGECmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
		$arSGECmdLineList[] = $szSGECmdLine;
		$arSGECmdLineList[] = "sleep 1s";
	}
	
	$szFPOutputFN = sprintf("%s/runme.%s.%s.sh", $szRootScriptOutputDir, $szCoreScriptName, $szSrcPatName);
	saveDataFromMem2File($arSGECmdLineList, $szFPOutputFN);
}

?>