<?php

 /**
 * 		@file 	ksc-AppConfigForProject.php
 * 		@brief 	Parse NIST collection collection.xml file.
 * 				This file contains mapping between videoName, videoID, and pat - partition (devel, test)
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 04 Jul 2013.
 */

/// !!! IMPORTANT !!!
/// .lstx --> extend lst file by adding duration, framecount, frame rate
/// Must be run using /raid0/ledduy/usr.local/bin/php --> for supporting php-ffmpeg

/*
* 	Input is xml file (e.g. iacc.2.A.collection.xml) provided by TRECVID:
* 		+ collection: mapping between videoID and videoName, patName
*
* 	Output is a set of files for NII-SECODE
* 		+ video.trecvid.lst: all in one file, mapping between videoID, videoName, and path
* 		+ tv200x.lst: mapping between videoID, videoName, and path for given year.
* 		+ tv2012.test.lstx --> extend lst file by adding duration, framecount, frame rate
*/

/////////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";


////////////////////////////////////////////////////////////

$szRootDir = $gszRootBenchmarkDir;  

$szRootMetaDataInputDir = sprintf("%s/trecvid-active", $szRootDir); // *** CHANGED ***
$szRootMetaDataOutputDir =sprintf("%s/metadata/keyframe-5", $szRootDir);  
makeDir($szRootMetaDataOutputDir);


printf("!!! ATTENTION !!!! \n");
$extension = "ffmpeg";
$extension_soname = $extension . "." . PHP_SHLIB_SUFFIX;
$extension_fullname = PHP_EXTENSION_DIR . "/" . $extension_soname;

// load extension
if (!extension_loaded($extension)) {
	dl($extension_soname) or die("Can't load extension $extension_fullname\n");
}

$arCode = array(
		2013 => "iacc.2.A", 
		2014 => "iacc.2.B", 
		2015 => "iacc.2.C");

$szRootVideoDir =sprintf("%s/video", $szRootDir);

foreach ($arCode as $nTVYear => $szCode)
{
	$szTVYear = sprintf("tv%s", $nTVYear);
	
	/// generate mapping videoName, videoID, pat
	generateMetadataForOneYearTRECVID($szRootMetaDataOutputDir, $szRootMetaDataInputDir, $nTVYear, $szCode);

	/// scan for frame rate, frame count and duration

	$szFPVideoListFN = sprintf("%s/%s.%s.lst", $szRootMetaDataOutputDir, $szTVYear, $szCode); 
	
	loadListFile($arVideoList, $szFPVideoListFN);
	
	$arOutput = array();
	foreach($arVideoList as $szLine)
	{
		// TRECVID2011_11645 #$# 00001-Fujimoristas_invade_Amnisty_International_marathon._-o-_.MOV00010_64kb_512kb #$# tv2011/test
		$arTmp = explode("#$#", $szLine);
	
		$szVideoID = trim($arTmp[0]);
		$szVideoName = trim($arTmp[1]);
		$szVideoPath = trim($arTmp[2]);
	
		$szFPVideoFN = sprintf("%s/%s/%s.mp4", $szRootVideoDir, $szVideoPath, $szVideoName);
		if(!file_exists($szFPVideoFN))
		{
			exit("File does not exist! [$szFPVideoFN]\n");
		}
		
		$objVideo = new ffmpeg_movie ($szFPVideoFN);
	
		$nDuration = $objVideo->getDuration(); // in seconds
		$nFrameCount = $objVideo->getFrameCount();
		$fFrameRate = $objVideo->getFrameRate();
		$fBitRate = $objVideo->getBitRate();
	
		// #$# frameRate #$# frameCount #$# Duration #$# fBitRate
		$szOutput = sprintf("%s #$# %0.2f #$# %s #$# %0.4f #$# %0.4f",
				$szLine, $fFrameRate, $nFrameCount, $nDuration, $fBitRate);
		$arOutput[] = $szOutput;
		printf("### %s\n", $szOutput);
	}
	
	$szFPOutputFN = sprintf("%sx", $szFPVideoListFN);
	saveDataFromMem2File($arOutput, $szFPOutputFN);
}

////////////////////////////////////////// FUNCTIONS //////////////////////////////////////////////
/**
 * 	Parse iaac.2.A/B/C.collection.xml into kaori-secode format
 * 	Each line: videoID #$# videoName #$# path
 * 	
 * 	@param $szCode --> used in path = tv2013/test.<Code ~ iacc.2.A> - special for tv2013
 */

/**
<VideoFile>
<id>32940</id>
<filename>003FeedReaders._-o-_.003_Feed_Readers_512kb.mp4</filename>
<use>test</use>
<source>http://archive.org/download/003FeedReaders</source>
<filetype>MPEG-4</filetype>
</VideoFile>
 */
function generateMetadataForOneYearTRECVID($szRootMetaDataOutputDir, $szRootMetaDataInputDir, 
		$nTVYear="2013", $szCode="iacc.2.A")
{
	global $gszDelim;
	global $garFrameRateList;

	$szTVYear = sprintf("tv%s", $nTVYear);

	//	$nFrameRate = $garFrameRateList[$szTVYear]; // for shot boundary info

	$szTVVideoIDPrefix = sprintf("TRECVID%s", $nTVYear);

	// Process collection.xml files --> for videoID, videoName mapping
	printf("### Parsing collection file ....\n");

	$szFPCollectionXMLFN = sprintf("%s/%s.collection.xml", $szRootMetaDataInputDir, $szCode);  // iacc2.a.collection.xml

	$arOutput = parseOneCollectionXMLFile($szFPCollectionXMLFN);

	$nNumVideos = sizeof($arOutput);

	$arVideoIDNameMap = array(); // for video mapping

	$arPatVideoIDNameMap = array();
	for($i=0; $i<$nNumVideos; $i++)
	{
		$nVideoID = $arOutput[$i]['video_id'];

		// 1 ==> TRECVID2011_1
		$szVideoID = sprintf("%s_%d", $szTVVideoIDPrefix, $nVideoID); // TRECVID20zz_xxx is the format of .mp7.xml file
		$szVideoName = $arOutput[$i]['video_name'];
		$szPatName = $arOutput[$i]['video_pat']; // always 'test' in all iacc.2.A/B/C

		// videoID #$# videoName #$# path
		$szShortVideoPath = sprintf("%s.%s", $szPatName, $szCode); // test.iacc.2.A ==> Pat and Path must be the SAME
		$szVideoPath = sprintf("%s/%s", $szTVYear, $szShortVideoPath); // tv2013/test.iacc.2.A

		$szVideoIDNameMap = sprintf("%s %s %s %s %s", $szVideoID, $gszDelim, $szVideoName, $gszDelim, $szVideoPath);

		// video programs for each year
		$arVideoIDNameMap[] = $szVideoIDNameMap;

		// video programs for each pat such as devel and test
		$arPatVideoIDNameMap[$szShortVideoPath][] = $szVideoIDNameMap;

	}

	// specific tvYear
	$szFPVideoIDNameFN = sprintf("%s/%s.%s.lst", $szRootMetaDataOutputDir, $szTVYear, $szCode);
	saveDataFromMem2File($arVideoIDNameMap, $szFPVideoIDNameFN, "wt");

	// specific pat
	foreach($arPatVideoIDNameMap as $szPatNamez => $arVideoListzz)
	{
		$szFPVideoIDNameFNz = sprintf("%s/%s.lst", $szRootMetaDataOutputDir, $szPatNamez);
		saveDataFromMem2File($arVideoListzz, $szFPVideoIDNameFNz, "wt");
	}
}

?>
