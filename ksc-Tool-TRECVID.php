<?php

 /**
 * 		@file 	ksc-Tool-TRECVID.php
 * 		@brief 	Tools for TRECVID.
 * 		Some variables will be overrided.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 04 Jul 2013.
 */

 
// Update Nov 25
// Adding function to load GZSvf --> using function in DataProcessingTools.php
// Moving these functions to DataProcessingTools in kaori-lib
/*
 * 		Convert metadata of trecvid to nii-secode metadata
 *
 * 		1. input: collection.xml  --> output: tvxxx.video.id-name.map --> mapping between video id (keyframe dir) and video name (file mpg)
 * 		2. input: 1.xml  --> output:
 *
 *
 */

/////////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";


/**
<?xml version"1.0" encoding="UTF-8">
<VideoFileList>
<VideoFile>
<id>1</id>
<filename>BG_12460.mpg</filename>
<use>devel</use>
<source>Sound and Vision</source>
<filetype>MPEG-1</filetype>
</VideoFile>
<VideoFileList>

<VideoFile>
<id>1</id>
<filename>StoryboardForWww.hypedbingo.com._-o-_.Movie_0001_512kb.mp4</filename>
<use>devel</use>
<source>http://archive.org/download/StoryboardForWww.hypedbingo.com</source>
<filetype>MPEG-4</filetype>
</VideoFile>
*/

/**
 * @param $szFPCollectionXMLFN
 * Output: videoID #$# videoName #$# path2videoID
 */
function parseOneCollectionXMLFile($szFPCollectionXMLFN)
{
	// adding for processing tv2010
	$arFileExtMapping = array("MPEG-1" => ".mpg",
			"MPEG-4" => ".mp4", "MPEG-2" => ".mpg");
	
	if (file_exists($szFPCollectionXMLFN))
	{
		$xmlRawObj = simplexml_load_file($szFPCollectionXMLFN);
	}
	else
	{
		terminatePrg("Failed to open xml file {$szFPCollectionXMLFN}!");
	}

	$arOutput = array();
	foreach ($xmlRawObj->VideoFile as $videoFile)
	{
		$videoEntry = array();
		$videoEntry['video_id'] = (string)($videoFile->id);
		$videoEntry['video_name'] =  (string)($videoFile->filename);

		$szFileType = (string)($videoFile->filetype);
		if(!isset($arFileExtMapping[$szFileType]))
		{
			printf("File type [%s] does not support!\n", $szFileType);
			exit();
		}
		$szFileExt = $arFileExtMapping[$szFileType];
		
		// $videoEntry['video_name'] = str_replace(".mpg", "", $videoEntry['video_name']);
		$videoEntry['video_name'] = rtrim($videoEntry['video_name'], $szFileExt);
		$videoEntry['video_pat'] = (string)($videoFile->use);

		$arOutput[] = $videoEntry;
	}

	return $arOutput;
}


/**
  http://www-nlpir.nist.gov/projects/tv2003/common.shot.ref/time.elements
  T00:00:42:13F25   --> frame offset
  
  framenumber = (int)( totalseconds * maxFractions + numberOfFractions) / time_unit;
e.g. PT16S4484N30000F   or   T00:00:16:4484F30000
     totalseconds = 16
     numberOfFractions = 4484;
     maxFractions = 30000
     time_unit = 1001
     framenumber = (16 * 30000 + 4484) / 1001 = 484
*/

/**
 * Change from last time which uses $nFrameRate rather than $nMaxFraction & $nTimeUnit
 * @param $szTimePoint
 * @param $nMaxFraction
 * @param $nTimeUnit
 */
function parseTimePoint($szTimePoint, $nMaxFraction, $nTimeUnit)
{
	$arTmp = explode(":", $szTimePoint);
	if(sizeof($arTmp) != 4)
	{
		terminatePrg("Wrong time point format!");
	}

	$arTmp1 = explode("T", trim($arTmp[0]));
	if(sizeof($arTmp1) != 2)
	{
		terminatePrg("Wrong time point format!");
	}
	$nHour = intval($arTmp1[1]);

	$nMin = intval($arTmp[1]);
	$nSec = intval($arTmp[2]);
	
	if($nMin>60 || $nSec>60)
	{
		printf("Data error! Min or sec value is larger than 60. Min: [%d] - Sec: [%d]\n", $nMin, $nSec);
		exit();
	}

	$arTmp1 = explode("F", trim($arTmp[3]));
	if(sizeof($arTmp1) != 2)
	{
		terminatePrg("Wrong time point format!");
	}
	$nFrac = intval($arTmp1[0]); // 13
	$nMaxFrac = intval($arTmp1[1]); // 25

	if($nMaxFrac != $nMaxFraction || $nHour>24 || $nMin>60 || $nSec>60)
	{
		terminatePrg("Wrong time point format! Max fraction is not consistent!");
	}
	$nTotalSeconds = $nHour*3600+$nMin*60+$nSec; 
	$nFrameNum = (int) (($nTotalSeconds*$nMaxFraction + $nFrac) /$nTimeUnit);
	
	return $nFrameNum;
}

// PT00H00M13S15N25F   --> frame count
function parseTimeDuration($szTimeDuration, $nMaxFraction, $nTimeUnit)
{
	$arTmp = explode("PT", $szTimeDuration);
	if(sizeof($arTmp) != 2)
	{
		terminatePrg("Wrong time point format!");
	}

	$szTmp = trim($arTmp[1]);
	$arTmp = explode("H", $szTmp);
	if(sizeof($arTmp) != 2)
	{
		//terminatePrg("Wrong time point format!");
		$nHour = 0;
		$szTmp = trim($arTmp[0]);
	}
	else
	{
		$nHour = intval($arTmp[0]);
		$szTmp = trim($arTmp[1]);
	}

	$arTmp = explode("M", $szTmp);
	if(sizeof($arTmp) != 2)
	{
		//terminatePrg("Wrong time point format!");
		$nMin = 0;
		$szTmp = trim($arTmp[0]);
	}
	else
	{
		$nMin = intval($arTmp[0]);
		$szTmp = trim($arTmp[1]);
	}
	
	$arTmp = explode("S", $szTmp);
	if(sizeof($arTmp) != 2)
	{
		// terminatePrg("Wrong time point format!");
		$szTmp = trim($arTmp[0]);
		$nSec = 0;
	}
	else
	{
		$nSec = intval($arTmp[0]);
		$szTmp = trim($arTmp[1]);
	}

	$arTmp = explode("N", $szTmp);
	if(sizeof($arTmp) != 2)
	{
		terminatePrg("Wrong time point format!");
	}
	$nFrac = intval($arTmp[0]);

	$szTmp = trim($arTmp[1]);
	$arTmp = explode("F", $szTmp);
	if(sizeof($arTmp) != 2)
	{
		terminatePrg("Wrong time point format!");
	}
	$nMaxFrac = intval($arTmp[0]);
	
	if($nFrac < 0)
	{
		$nFrac = 0;
	}

	if($nMaxFrac != $nMaxFraction || $nHour>24 || $nMin>60 || $nSec>60)
	{
		terminatePrg("Wrong time point format! Max fraction is not consistent!");
	}
	$nTotalSeconds = $nHour*3600+$nMin*60+$nSec; 
	$nFrameNum = (int) (($nTotalSeconds*$nMaxFraction + $nFrac) /$nTimeUnit);
	
	return $nFrameNum;
}

/**
	Input is the xml file for shot boundary.
 	Output is the list of shots.
 	Each shot info has starting frame and duration (frame count).
 	TRECVID2005, TRECVID2006, TRECVID2007 --> VideoID: TRECVID200x_xx
 	TRECVID2008, TRECVID2009 --> VideoID: yy (not include TRECVID200x)
 	@param szVideoIDPrefix --> TRECVID200x
*/
function parseOneShotXMLFile($szFPShotXMLFN, $szVideoIDPrefix="", $nMaxFraction=30000, $nTimeUnit=1001, $szFileExt="mpg")
{
	global $gszDelim;

	if (file_exists($szFPShotXMLFN))
	{
		$xmlRawObj = simplexml_load_file($szFPShotXMLFN);
		if($xmlRawObj === false)
		{
			terminatePrg("Failed to open xml file {$szFPShotXMLFN}!");
		}
	}
	else
	{
	}

	$xmlVideoObj = $xmlRawObj->Description->MultimediaContent->Video;

	$attrs = $xmlVideoObj->attributes();
	$szVideoID = (string)($attrs['id']);
	$szVideoName = (string)($xmlVideoObj->MediaLocator->MediaUri);

	if($szVideoIDPrefix)
	{
		$szVideoID = $szVideoIDPrefix . "_" . $szVideoID;
	}

	$arOutput = array();
	$arOutput['video_id'] = $szVideoID;

	//$arOutput['video_name'] = str_replace(".mpg", "", $szVideoName); // only name, no extension
	$arOutput['video_name'] = rtrim($szVideoName, $szFileExt);
	
	$arOutput['shot_list'] = array();
	foreach ($xmlVideoObj->TemporalDecomposition->VideoSegment as $videoSegment)
	{
		$attrs = $videoSegment->attributes();
		$szShotID = (string)($attrs['id']);

		$szTimePoint = (string)($videoSegment->MediaTime->MediaTimePoint);

		// in the case of subshot - TV2005
		if(!isset($videoSegment->MediaTime->MediaTimePoint))
		{
			continue;
		}

		$szTimeDuration =  (string)($videoSegment->MediaTime->MediaDuration);

		$nFrameStart = parseTimePoint($szTimePoint, $nMaxFraction, $nTimeUnit);
		$nDuration = parseTimeDuration($szTimeDuration, $nMaxFraction, $nTimeUnit);

		// concatenate videoID.shotID
		$arOutput['shot_list'][] = sprintf("%s.%s %s %d %s %d", $szVideoID, $szShotID, $gszDelim, $nFrameStart, $gszDelim, $nDuration);
	}

	return $arOutput;
}

/**
 *	Format:
 * 		GlobalConceptID #$# GlobalConceptName #$# NISTConceptName #$# ConceptID-2005 #$# ConceptID-2006 #$# ....
 * 	Output: LUT - lookup table
 * 		+ LUT-qrels[tvYear][nistConceptID] --> GlobalConceptName: used for conversion of NIST groundtruth
 * 		+ LUT-nsc[nscGlobalConceptName] --> nscGlobalConceptID
 * 		+ LUT-tv[nistConceptName] --> nscGlobalConceptName: used for conversion of LIG and ICT annotation
 *  	  
 */
function loadConceptMapList($szFPConceptListMapFN)
{
	$nNumRows = loadListFile($arRawList, $szFPConceptListMapFN);
	
	$arLUTQRels = array(); // used for conversion of NIST groundtruth
	$arLUTNSC = array(); // used for mapping between nscConceptName and nscConceptID
	$arLUTTV = array(); // used for mapping between nistConceptName and nscConceptName
	
	// skip the first row, header
	for($i=1; $i<$nNumRows; $i++)
	{
		$szLine = $arRawList[$i];
		$arTmp = explode("#$#", $szLine);
		
		$szGlobalConceptID = trim($arTmp[0]);
		$szGlobalConceptName = trim($arTmp[1]);
		$szNISTConceptName = trim($arTmp[2]);
		$nConceptID_2005 = intval($arTmp[3]);
		$nConceptID_2006 = intval($arTmp[4]);
		$nConceptID_2007 = intval($arTmp[5]);
		$nConceptID_2008 = intval($arTmp[6]);
		$nConceptID_2009 = intval($arTmp[7]);
		
		$arLUTNSC[$szGlobalConceptName] = $szGlobalConceptID;
		$arLUTTV[$szNISTConceptName] =  $szGlobalConceptName;
		
		if($nConceptID_2005 > 1000)
		{
			$arLUTQRels[2005][$nConceptID_2005] = $szGlobalConceptName;
		} 

		if($nConceptID_2006 > 1000)
		{
			$arLUTQRels[2006][$nConceptID_2006] = $szGlobalConceptName;
		} 

		if($nConceptID_2007 > 1000)
		{
			$arLUTQRels[2007][$nConceptID_2007] = $szGlobalConceptName;
		} 

		if($nConceptID_2008 > 1000)
		{
			$arLUTQRels[2008][$nConceptID_2008] = $szGlobalConceptName;
		} 

		if($nConceptID_2009 > 1000)
		{
			$arLUTQRels[2009][$nConceptID_2009] = $szGlobalConceptName;
		} 
	}
	
	$arLUT = array();
	
	$arLUT['qrels'] = $arLUTQRels; 
	$arLUT['nsc'] = $arLUTNSC;
	$arLUT['tv'] = $arLUTTV;

	return $arLUT;
}

/**
 * 		Parse annotations provided by LIG or ICT-CAS.
 *
 * 		Input:
 * 			+ Each row of the annotation file is for one keyframe.
 *
 * 		Output:
 * 			+ $arOutput[$szTargetConceptName][$garLabelList[$szLabel]][$szVideoID][$szFullShotID][$szKeyFrameID] = 1;
 * 				--> list of keyframes of one shots for annotation.
 * 		Implementation:
 * 			+ To extract shotID from keyframeID, delims such as RKF_, NRKF_, .RKF_ are used.
 *
 */
function parseCollaborativeAnnotationFile($szFPAnnotationFN, $szFPConceptListMapFN)
{
	global $garLabelList; //array("P" => "Pos", "N" => "Neg", "S" => "Skipped");

	$arLUTx = loadConceptMapList($szFPConceptListMapFN);
	$arLUTTV = $arLUTx['tv'];
	
	$nNumSamples = loadListFile($arRawList, $szFPAnnotationFN);

	$arOutput = array();
	for($i=0; $i<$nNumSamples; $i++)
	{
		// CJ MCG-ICT-CAS Airplane_flying BG_12460 TRECVID2007_1/shot1_100_RKF N
		// LIG lig Airplane_flying BG_38018.mpg TRECVID2007_93/shot93_28_NRKF_1 P
		$szLine = $arRawList[$i];

		$arTmp = explode(" ", $szLine);

		if(sizeof($arTmp) < 6)
		{
			print_r($arTmp);
			terminatePrg("Error in annotation file!");
		}

		$szTVConceptName = trim($arTmp[2]);
		if(!isset($arLUTTV[$szTVConceptName]))
		{
			printf("No mapping for [%s] exists!\n", $szTVConceptName);
			exit(1);
		}
		$szTargetConceptName = $arLUTTV[$szTVConceptName]; // mapping between concept
		$szVideoName = trim($arTmp[3]);

		$szTmp = trim($arTmp[4]);
		$arTmp1 = explode("/", $szTmp);
		$szVideoID = trim($arTmp1[0]);
		$szTmp = trim($arTmp1[1]);

		$szKeyFrameID = trim($arTmp1[1]);

		if(strstr($szTmp, "_RKF"))
		{
			$arTmp1 = explode("_RKF", $szTmp);
			$szShotID = trim($arTmp1[0]);
		}
		else
		{
			if(strstr($szTmp, "_NRKF"))
			{
				$arTmp1 = explode("_NRKF", $szTmp);
				$szShotID = trim($arTmp1[0]);
			}
			else
			{
				if(strstr($szTmp, ".RKF_"))  // nii-secode format
				{
					$arTmp1 = explode(".RKF_", $szTmp);
					$szShotID = trim($arTmp1[0]);
				}
				else
				{
					$szShotID = $szTmp;
				}
			}
		}

		$szFullShotID = sprintf("%s.%s", $szVideoID, $szShotID);

		$szLabel = trim($arTmp[5]);

		if(!isset($garLabelList[$szLabel]))
		{
			printf("Label [%s] does not exist!\n");
			exit();
		}

		$arOutput[$szTargetConceptName][$garLabelList[$szLabel]][$szVideoID][$szFullShotID][$szKeyFrameID] = 1;
	}

	return $arOutput;
}

/**
 * 		Parse annotations provided by NIST (ground truth)
 *
 * 		Input: 
 * 			+ nTVYear: needed because qrels files do not have information about TRECVID  year, 
 * 			+ szFPNISTAnnFN: provided by NIST.
 * 			+ szFPConceptListMapFN: consisting of information such as: 
 * 				++ global/unique conceptID, global/unique conceptName
 * 				++ nist-conceptID, nist-conceptID (might be different for years)
 * 		Output:
 * 			+ Each concept: 2 files, .pos and .neg --> can be used for training later
 * 			+ All concept in one file --> can be used for evaluation, e.g. MAP
 */

/**
 * 	IMPORTANT!!!!!!!!!
 * 	In NIST groundtruth, we NEED to swap label 
 * 
 * 		- Attached are the manual judgments (qrels) and a table of inferred average precision (infAP) scores. 
 * 		- The rightmost column in the qrels indicates whether the shot was judged to contain 
 * 			+ (1) the feature #+1000 (leftmost column) 
 * 			+ or not (0) 
 * 			+ or was not part of the 50% pool sample (-1).
 */

function parseNISTAnnotationFile($szFPAnnotationFN, $szFPConceptListMapFN, $nTVYear=2005)
{
	$arLUTx = loadConceptMapList($szFPConceptListMapFN);
	$arLUTQRels = $arLUTx['qrels'];
	
	$nNumSamples = loadListFile($arRawList, $szFPAnnotationFN);

	$garNISTLabelValList = array(1 => "Pos", -1 => "Skipped", 0 => "Neg"); // labels are SWAPPED

	$arOutput = array();
	for($i=0; $i<$nNumSamples; $i++)
	{
		// 1001 0 shot100_14 -1
		$szLine = $arRawList[$i];

		$arTmp = explode(" ", $szLine);

		if(sizeof($arTmp) < 4)
		{
			print_r($arTmp);
			terminatePrg("Error in annotation file!");
		}

		$szConceptID = trim($arTmp[0]);
		$szTargetConceptName = $arLUTQRels[$nTVYear][$szConceptID];

		$szShotID = trim($arTmp[2]);
		$nLabel = intval($arTmp[3]);

		$arTmp1 = explode("_", $szShotID);
		$szTmp = $arTmp1[0]; // shot100
		$arTmp1 = explode("shot", $szTmp);

		// TRECVID2005_100
		$szVideoID = sprintf("TRECVID%s_%s", $nTVYear, trim($arTmp1[1]));

		// TRECVID2005_100.shot100_1
		$szFullShotID = sprintf("%s.%s", $szVideoID, $szShotID);
		$szKeyFrameID = sprintf("%s.RKF", $szFullShotID); //unused
		$arOutput[$szTargetConceptName][$garNISTLabelValList[$nLabel]][$szVideoID][$szFullShotID][$szKeyFrameID] = 1;
	}

	return $arOutput;
}

/** IMPORTANT!!!!!!!
 * 	NSC annotation format. 
 * 		+ Org #$# Annotator #$# ConceptName #$# VideoID #$# ShotID #$# KeyFrameID #$# Label
 * 		+ Adding one more field KeyFrameID to unify annotation by shot and by keyframe
 */
function convert2NSCAnnotation(&$arAnnInput, $szAnnOrg="NII", $szAnnotatorName="nsc")
{
	global $garLabelValList; // $garLabelValList = array(1 => "Pos", -1 => "Neg", 0 => "Skipped");
	global $garInvLabelList; // $garInvLabelList = array("Pos" => "P", "Neg" => "N", "Skipped" => "S");

	// $arOutput[$szTargetConceptName][$garNISTLabelValList[$nLabel]][$szVideoID][$szFullShotID][$szKeyFrameID] = 1;	
	
	$arConceptList = array_keys($arAnnInput);
	$nNumConcepts = sizeof($arConceptList);
		
	$arAllOutputList = array();
	$arConceptOutputList = array();
	for($i=0; $i<$nNumConcepts; $i++)
	{
		$szConceptName = $arConceptList[$i]; // this is nscGlobalConceptName
			
		$arLabelList = array_keys($arAnnInput[$szConceptName]);
		$nNumLabels = sizeof($arLabelList);

		for($j=0; $j<$nNumLabels; $j++)
		{
			$szLabel = $arLabelList[$j];  // Pos or Neg
			$szFileExt = strtolower($szLabel); // Pos --> pos for filename ext
			$szShortLabel = $garInvLabelList[$szLabel]; 

			$arVideoList = array_keys($arAnnInput[$szConceptName][$szLabel]);
			$nNumVideos = sizeof($arVideoList);
			$arOutputListx = array();
			for($k=0; $k<$nNumVideos; $k++)
			{
				$szVideoID = $arVideoList[$k];
				$arShotList = array_keys($arAnnInput[$szConceptName][$szLabel][$szVideoID]);
	
				$nNumShots = sizeof($arShotList);
				
				for($l=0; $l<$nNumShots; $l++)
				{
					$szShotID = $arShotList[$l];
					$arKeyFrameList = array_keys($arAnnInput[$szConceptName][$szLabel][$szVideoID][$szShotID]);
					
					$nNumKeyFrames = sizeof($arKeyFrameList);
					
					for($m=0; $m<$nNumKeyFrames; $m++)
					{
						$szKeyFrameID = $arKeyFrameList[$m];
						$szAnn = sprintf("%s #$# %s #$# %s #$# %s #$# %s #$# %s #$# %s", 
							$szAnnOrg, $szAnnotatorName, $szConceptName, $szVideoID, $szShotID, $szKeyFrameID, $szShortLabel);
						$arOutputListx[] = $szAnn;
						$arAllOutputList[] = $szAnn;	
					}
				}
			}
			$arConceptOutputList[$szConceptName][$szFileExt] = $arOutputListx; 
		}
	}
	$arNSCAnnOutput = array();
	
	$arNSCAnnOutput['concept'] = $arConceptOutputList;  // concept level
	$arNSCAnnOutput['all'] = $arAllOutputList; // global level
	
	return $arNSCAnnOutput;
}

/** IMPORTANT!!!!!!!
 * 	TRECEVal annotation format. 
 * 		+ ConceptID Unused ShotID Label --> 1: Relevant, 0: Irrelevent, -1: UnJudged 
 * 		+ 1038 0 shot100_113 0 
 */
function convert2TRECEvalAnnotation(&$arAnnInput, $szFPConceptListMapFN)
{
	$garTRECEvalLabelList = array("Pos" => "1", "Neg" => "0", "Skipped" => "-1");

	$arLUTx = loadConceptMapList($szFPConceptListMapFN);
	$arLUTNSC = $arLUTx['nsc']; 
	
	// $arOutput[$szTargetConceptName][$garNISTLabelValList[$nLabel]][$szVideoID][$szFullShotID][$szKeyFrameID] = 1;	
	
	$arConceptList = array_keys($arAnnInput);
	$nNumConcepts = sizeof($arConceptList);
		
	$arAllOutputList = array();
	for($i=0; $i<$nNumConcepts; $i++)
	{
		$szConceptName = $arConceptList[$i]; // this is nscGlobalConceptName
		$szConceptID = $arLUTNSC[$szConceptName]; 
			
		$arLabelList = array_keys($arAnnInput[$szConceptName]);
		$nNumLabels = sizeof($arLabelList);

		for($j=0; $j<$nNumLabels; $j++)
		{
			$szLabel = $arLabelList[$j];  // Pos or Neg
			$szFileExt = strtolower($szLabel); // Pos --> pos for filename ext
			$szShortLabel = $garTRECEvalLabelList[$szLabel]; 

			$arVideoList = array_keys($arAnnInput[$szConceptName][$szLabel]);
			$nNumVideos = sizeof($arVideoList);

			for($k=0; $k<$nNumVideos; $k++)
			{
				$szVideoID = $arVideoList[$k];
				$arShotList = array_keys($arAnnInput[$szConceptName][$szLabel][$szVideoID]);
	
				$nNumShots = sizeof($arShotList);
				
				for($l=0; $l<$nNumShots; $l++)
				{
					$szShotID = $arShotList[$l];
					$arKeyFrameList = array_keys($arAnnInput[$szConceptName][$szLabel][$szVideoID][$szShotID]);
					
					$nNumKeyFrames = sizeof($arKeyFrameList);
					
					for($m=0; $m<$nNumKeyFrames; $m++)
					{
						$szKeyFrameID = $arKeyFrameList[$m];
						$szAnn = sprintf("%s #$# 0 #$# %s #$# %s ", $szConceptID, $szShotID, $szShortLabel);
						$arAllOutputList[] = $szAnn;	
					}
				}
			}
		}
	}
	
	return $arAllOutputList;
}

function parseNSCAnnotationArray(&$arRawList)
{
	global $garLabelList; //array("P" => "Pos", "N" => "Neg", "S" => "Skipped");


	$nNumRows = sizeof($arRawList);
	
	$arOutput = array();
	for($i=0; $i<$nNumRows; $i++)
	{
		$szLine = $arRawList[$i];
		
		$arTmp = explode("#$#", $szLine);
		
		if(sizeof($arTmp) != 7)
		{
			printf("Error in annotation format [%s]!\n", $szLine);
			print_r($arTmp);
			exit();
		}
		
		$szConceptName = trim($arTmp[2]);
		$szVideoID = trim($arTmp[3]);
		$szShotID = trim($arTmp[4]);
		$szKeyFrameID = trim($arTmp[5]);
		$szLabel = trim($arTmp[6]); // P or N or S
		
		$arOutput[$szConceptName][$garLabelList[$szLabel]][$szVideoID][$szShotID][$szKeyFrameID] = 1; // consistency
		
	}
	
	return $arOutput;
}
/** 
 * 	NSC annotation format. 
 * 		+ Org #$# Annotator #$# ConceptName #$# VideoID #$# ShotID #$# KeyFrameID #$# Label
 */
function parseNSCAnnotationFile($szFPAnnotationFN)
{
	$nNumRows = loadListFile($arRawList, $szFPAnnotationFN);
	
	return parseNSCAnnotationArray($arRawList);
}

/**
 * 	Input: VideoID
 * 	Output: 
 * 		+ path
 * 		+ video name
 * 		+ shot list, i.e. shot boundary info
 * 		+ keyframe list
 */
function loadVideoShotInfo($szVideoID, $szRootVideoArchiveDir, $szVideoArchiveName="trecvid")
{
	$szFPVideoListFN = sprintf("%s/metadata/%s/video.%s.lst", $szRootVideoArchiveDir, $szVideoArchiveName, $szVideoArchiveName);
	$nNumVideos = loadListFile($arList, $szFPVideoListFN);
	
	$nFound = 0;
	for($i=0; $i<$nNumVideos; $i++)
	{
		// TRECVID2005_1 #$# 20041116_110000_CCTV4_NEWS3_CHN #$# tv2005/test
		$szLine = $arList[$i];
		
		$arTmp = explode("#$#", $szLine);
		
		$szVideoIDx = trim($arTmp[0]);
		
		if($szVideoIDx == $szVideoID)
		{
			$szVideoName = trim($arTmp[1]);
			$szVideoPath = trim($arTmp[2]);
			$nFound = 1;
			break;
		}
	}
	
	$arOutput = array();
	if(!$nFound)
	{
		return $arOutput;
	}
	
	$szFPShotListFN = sprintf("%s/metadata/%s/%s.sb", $szRootVideoArchiveDir, $szVideoArchiveName, $szVideoID);
	$szFPKeyFrameListFN = sprintf("%s/metadata/%s/%s.prg", $szRootVideoArchiveDir, $szVideoArchiveName, $szVideoID);
	
	$nNumShots = loadListFile($arShotList, $szFPShotListFN);
	$nNumKeyFrames = loadListFile($arKeyFrameList, $szFPKeyFrameListFN);	

	// alignment
	for($i=2; $i<$nNumShots; $i++) // two first lines are ignored
	{
		// TRECVID2009_699.shot699_1 #$# 0 #$# 196
		$szLineS = $arShotList[$i];
		$arTmpS = explode("#$#", $szLineS);
		$szShotID = trim($arTmpS[0]);
		$nFrameStart = intval($arTmpS[1]);
		$nDuration = intval($arTmpS[2]);
		
		$arOutput[$szShotID]['shot_start'] = $nFrameStart;
		$arOutput[$szShotID]['shot_duration'] = $nDuration;
	}

	for($i=0; $i<$nNumKeyFrames; $i++) 
	{
		// TRECVID2009_699.shot699_1.RKF_0.Frame_21 
		$szLineKF = $arKeyFrameList[$i];
		$arTmpKF = explode(".RKF_", $szLineKF);
		$szShotID = trim($arTmpKF[0]);

		$arOutput[$szShotID]['keyframe'][] = $szLineKF;
	}
	
	$arFinalOutput = array();
	$arFinalOutput[$szVideoID]['video_path'] = $szVideoPath;
	$arFinalOutput[$szVideoID]['shot_info'] = $arOutput;
	$arFinalOutput[$szVideoID]['video_name'] = $szVideoName;
	
	return $arFinalOutput;
}


//////////////////////// CUT OUT ///////////////////////////

/**
 * 	ConceptDesc file contains description of concepts
 * 	Each line, concept_name (also dir name) and description
 * 	$arOutput[$szConceptName] = $szConceptDesc;
 */
function loadConceptDescList($szFPInputFN)
{
	$nNumConcepts = loadListFile($arConceptDescList, $szFPInputFN);

	global $gszDelim;
	$arOutput = array();
	for($i=0; $i<$nNumConcepts; $i++)
	{
		// Chair #$# a seat with four legs and a back for one person
		$arTmp = explode($gszDelim, $arConceptDescList[$i]);
		$szConceptName = trim($arTmp[0]);
		$szConceptDesc = trim($arTmp[1]);

		$arOutput[$szConceptName] = $szConceptDesc;
	}

	return $arOutput;
}

function loadConceptIDMapList($szFPInputFN)
{
	$nNumConcepts = loadListFile($arConceptIDMapList, $szFPInputFN);

	global $gszDelim;
	$arOutput = array();
	for($i=0; $i<$nNumConcepts; $i++)
	{
		// Chair #$# a seat with four legs and a back for one person
		$arTmp = explode($gszDelim, $arConceptIDMapList[$i]);
		$nConceptID = intval($arTmp[0]);
		$szConceptName = trim($arTmp[1]);

		$arOutput[$szConceptName] = $nConceptID;
	}

	return $arOutput;
}

/**
 * 	Mapping between conceptID and conceptName
 */
function loadConceptQueryList($szFPInputFN)
{
	$nNumConcepts = loadListFile($arConceptIDList, $szFPInputFN);

	global $gszDelim;
	$arOutput = array();
	for($i=0; $i<$nNumConcepts; $i++)
	{
		// Chair #$# a seat with four legs and a back for one person
		$arTmp = explode($gszDelim, $arConceptIDList[$i]);
		$szConceptID = trim($arTmp[0]);
		$szConceptName = trim($arTmp[1]);

		$arOutput[$szConceptID] = $szConceptName;
	}

	return $arOutput;
}

/**
 * 	arShotList --> list of shots in plain list, e.g. arShotList[] = TRECVID2005_1.shot1_12
 * 	arProgList --> list of shots grouped by programs, e.g. arProgList[TRECVID2005_1] = TRECVID2005_1.shot1_12
 */
function convertShotList2VideoProgList(&$arShotList)
{
	$nNumShots = sizeof($arShotList);

	$arProgList = array();
	for($i=0; $i<$nNumShots; $i++)
	{
		// TRECVID2007_159.shot159_63.RKF_2.Frame_11450
		$szFullShotID = $arShotList[$i];

		$arTmp = explode(".", $szFullShotID);

		$szVideoID = trim($arTmp[0]); // first part is the video ID

		$arProgList[$szVideoID][] = $szFullShotID;
	}

	return $arProgList;
}

/**
 * 	Load Dvf feature file --> store in array: $arFinalOutput[$szKeyFrameID] = $szFeature;
 *
 * 	Just parse into 2 parts: feature (string) and keyframeID
 */

function loadDvfFeatureFile($szFPFeatureFN, $nKFIndex=2)
{
	//$nKFIndex=2; //default value

	$nNumRows = loadListFile($arRowList, $szFPFeatureFN);
	$nNumCommentLines = countNumCommentLines($arRowList);

	$arFinalOutput = array();
	for($i=$nNumCommentLines; $i<$nNumRows; $i++)
	{
		$szOneRowDvfAnnData = $arRowList[$i];

		$arTmp = explode("%", $szOneRowDvfAnnData);

		if(sizeof($arTmp) != 2)
		{
			print_r($arTmp);
			terminatePrg("Error in parsing line - No [%%] is found");
		}

		$szFeature = trim($arTmp[0]);
		$szAnn = trim($arTmp[1]);
		$arTmp1 = explode(" ", $szAnn);

		$szKeyFrameID = trim($arTmp1[$nKFIndex]);

		$arFinalOutput[$szKeyFrameID] = $szFeature;
	}

	return $arFinalOutput;
}

function saveRankListAsAnnFile($szFPOutputFN, &$arFinalRankList, $szConceptName, $nMaxSamples=5000)
{
	$nNumSamples = sizeof($arFinalRankList);

	$arKeyFrameList = array_keys($arFinalRankList);

	$arAnnList = array();
	for($kk=0; $kk<$nNumSamples; $kk++)
	{
		if($kk>$nMaxSamples)
		{
			break;
		}
			
		$szKeyFrameID = $arKeyFrameList[$kk];
			
		$arTmpxx = explode(".", $szKeyFrameID);
		$szVideoID = trim($arTmpxx[0]);
			
		$szAnnStr = sprintf("NII-SECODE baseline %s %s %s/%s P", $szConceptName, $szVideoID, $szVideoID, $szKeyFrameID);
			
		$arAnnList[] = $szAnnStr;
	}

	saveDataFromMem2File($arAnnList, $szFPOutputFN, "wt");

}

/**
 * 	Input is list of keyframes in ann format, i.e conceptName, videoID, keyframeID, label
 * 	Output is list of keyframes grouped by videoID/shotID/keyframeID
 *
 * 	$arFinalList[videoID][shotID][]
 *
 */
function parseAnnData2VideoShotFormat($szFPAnnFN)
{
	$nAddingVideoIDPrefix = 0; // if using NII annotation
	$arAnnOutput = parseCommonAnnotationFile($szFPAnnFN, $nAddingVideoIDPrefix);

	$arConceptList = array_keys($arAnnOutput);
	$nNumConcepts = sizeof($arConceptList);

	global $gszPosLabel;
	$szLabel = $gszPosLabel;

	$arFinalList = array();
	for($i=0; $i<$nNumConcepts; $i++)
	{
		$szConceptName =  $arConceptList[$i];

		$arAnnList = $arAnnOutput[$szConceptName][$szLabel];
		// shots are stored in plain list
		$arAnnShotList = array_keys($arAnnList);

		// shots are grouped by videoID
		$arAnnProgShotList = convertShotList2VideoProgList($arAnnShotList);

		$arAnnProgList = array_keys($arAnnProgShotList);

		$nNumProgs = sizeof($arAnnProgList);

		for($j=0; $j<$nNumProgs; $j++)
		{
			$szVideoID = $arAnnProgList[$j];
				
			$arShotListx = $arAnnProgShotList[$szVideoID];

			$nNumShots = sizeof($arShotListx);
				
			for($k=0; $k<$nNumShots; $k++)
			{
				$szShotID = $arShotListx[$k];

				if(!isset($arFinalList[$szVideoID][$szShotID]))
				{
					$arFinalList[$szVideoID][$szShotID] = array();
				}
				$arFinalList[$szVideoID][$szShotID] = array_merge($arFinalList[$szVideoID][$szShotID], $arAnnList[$szShotID]);
			}
		}
	}

	return $arFinalList;
}


function convertHLFRankedList2TRECVIDXMLFormat(&$arRankedList, $nMaxShots, $nConceptID)
{
	$arOutputList = array();
	
	// <videoFeatureExtractionFeatureResult fNum="01">
	$arOutputList[] = sprintf("\t<videoFeatureExtractionFeatureResult fNum='%02d'>", $nConceptID);

	$arKeys = array_keys($arRankedList);
	if($nMaxShots > sizeof($arKeys))
	{
		$nMaxShots = sizeof($arKeys);
	}
	
	for($j=0; $j<$nMaxShots; $j++)
	{
		$szShotIDx = $arKeys[$j];
		$arTmp = explode(".", $szShotIDx);
		$szShotID = trim($arTmp[1]);
		$arOutputList[] = sprintf("\t\t<item seqNum='%d' shotId='%s' />", $j, $szShotID);
	}
	$arOutputList[] = sprintf("\t</videoFeatureExtractionFeatureResult>\n");

	return $arOutputList;
}


function convertTimeStamp2Frame($szTime, $nFrameRate= 25)
{
	// 04m17.120s
	$arTmp = explode("m", $szTime);
	$nMin = intval($arTmp[0]);
	$szTmp = trim($arTmp[1]);

	// 17.120s
	$arTmp = explode(".", $szTmp);
	$nSec = intval($arTmp[0]);
	$szTmp = trim($arTmp[1]);

	$arTmp = explode("s", $szTmp);
	$nMilSec = intval($arTmp[0]);

	$nFrameID = intval(($nMin*60+$nSec+$nMilSec*1.0/1000)*$nFrameRate);

	return $nFrameID;
}


/**
 * 	Parse the file topic.2009.xml
 *	
 * 	Fields such as keywordN and keywordV are added manually  
 * 	Keywords are extracted in advance using tool http://l2r.cs.uiuc.edu/~cogcomp/pos_demo.php
 * 	There are two types: noun (singular and plurar) and verb (gerund, present participle, past participle).
 * 	Stop words: people, person, visible, outside, view, frame, area
 *  
 */
function parseTopicXMLFile($szFPConfigXMLFN)
{
	if (file_exists($szFPConfigXMLFN))
	{
		$xmlRawObj = simplexml_load_file($szFPConfigXMLFN);
	}
	else
	{
		terminatePrg("Failed to open xml file {$szFPConfigXMLFN}!");
	}


	$arOutput = array();
	foreach ($xmlRawObj->videoTopic as $videoTopic)
	{
		$attrs = $videoTopic->attributes();
		$szTopicID = (int)($attrs['num']);

		$arOutput1 = array();
		$attrs = $videoTopic->textDescription->attributes();
		$arOutput1['textDesc'] = (string)($attrs['text']);
		$attrs = $videoTopic->keyWordN->attributes();
		$arOutput1['keyWordN'] = (string)($attrs['text']);
		$attrs = $videoTopic->keyWordV->attributes();
		$arOutput1['keyWordV'] = (string)($attrs['text']);
		$i=0;
		$arOutput2 = array();
		foreach ($videoTopic->videoExample as $videoExample)
		{
			$attrs = $videoExample->attributes();
			$arOutput2[$i]['src'] = (string)($attrs['src']);
			$arOutput2[$i]['start'] = convertTimeStamp2Frame((string)($attrs['start']), 25);
			$arOutput2[$i]['stop'] = convertTimeStamp2Frame((string)($attrs['stop']), 25);

			$i++;
		}
		$arOutput1['videoExample'] = $arOutput2;

		$arOutput[$szTopicID] = $arOutput1;
	}
	return $arOutput;
}

function loadExperimentConfig($szFPConfigFN)
{
	loadListFile($arRawList, $szFPConfigFN);
	$arConfig = array();
	foreach($arRawList as $szLine)
	{
		$arTmp = explode("#$#", $szLine);
		$szKey = trim($arTmp[0]);
		$szVal = trim($arTmp[1]);

		$arConfig[$szKey] = $szVal;
	}

	return $arConfig;
}

function getResizedFrameSize($nFrameWidth, $nFrameHeight, $nMaxFrameWidth=352, $nMaxFrameHeight=300)
{
	$fScaleX = 	$nMaxFrameWidth*1.0/$nFrameWidth;
	$fScaleY = 	$nMaxFrameHeight*1.0/$nFrameHeight;
	
	$nNewFrameWidth = $nFrameWidth;
	$nNewFrameHeight = $nFrameHeight;
	if(($nFrameWidth > $nMaxFrameWidth) || ($nFrameHeight > $nMaxFrameHeight))
	{
		// try scale X
		$nNewFrameWidth = round($nFrameWidth*$fScaleX);
		$nNewFrameHeight = round($nFrameHeight*$fScaleX);
		
		if(($nNewFrameWidth > $nMaxFrameWidth) || ($nNewFrameHeight > $nMaxFrameHeight))
		{
			// try scale Y
			$nNewFrameWidth = round($nFrameWidth*$fScaleY);
			$nNewFrameHeight = round($nFrameHeight*$fScaleY);
		}
	}
	
	$arOutput = array();
	$arOutput['width'] = $nNewFrameWidth;
	$arOutput['height'] = $nNewFrameHeight;
	
	return $arOutput;
}

?>
