<?php

/**
 * 		@file 	ksc-web-ViewResult.php
 * 		@brief 	View query, groundtruth, and ranking result.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 09 Aug 2014.
 */

require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;

////////////////// START //////////////////

//$szRootExpDir  = $szRootDir
$szRootExpDir = sprintf("%s", $gszRootBenchmarkExpDir);

$szRootResultDir = sprintf("%s/result/keyframe-5", $szRootExpDir); // dir containing prediction result of RUNs
$arResultDirList = collectDirsInOneDir($szRootResultDir);

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootExpDir); // dir containing keyframes


$szExpMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootExpDir); // --> get test-pat


$nAction = 0;
if(isset($_REQUEST['vAction']))
{
	$nAction = $_REQUEST['vAction'];
}

if($nAction == 0)  // Let user pick the ResultDir
{
    printf("<P><H1>View Results</H1>\n");
    
	printf("<FORM TARGET='_blank'>\n");

	printf("<P><H2>Select TestConfigName</H2>\n");
	printf("<SELECT NAME='vTestConfigName'>\n");
	foreach($arResultDirList as $szResultDir)
	{
		printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szResultDir, $szResultDir);
	}
	printf("</SELECT>\n");

	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}

$szTestConfigName = 'mediaeval-vsd-2014.devel2013-new.test2013-new';

$szResultDir = sprintf("%s/%s", $szRootResultDir, $szTestConfigName);
$arModelFeatureDirList = collectDirsInOneDir($szResultDir);

if(isset($_REQUEST['vTestConfigName']))
{
	$szTestConfigName = $_REQUEST['vTestConfigName'];
}

if($nAction == 1)  // Let user pick the ModelFeatureConfig
{
	printf("<P><H1>View Results</H1>\n");

	printf("<FORM TARGET='_blank'>\n");

	printf("<P><H2>Select TestConfigName</H2>\n");
	printf("<SELECT NAME='vTestConfigName'>\n");
	printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szTestConfigName, $szTestConfigName);
	printf("</SELECT>\n");

	printf("<P><H2>Select ModelFeatureConfigName</H2>\n");
	printf("<SELECT NAME='vModelFeatureConfigName'>\n");
	foreach($arModelFeatureDirList as $szModelFeatureDir)
	{
		printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szModelFeatureDir, $szModelFeatureDir);
	}
	printf("</SELECT>\n");
	
	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='2'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}

$szModelFeatureConfigName = 'nsc.bow.dense6mul.rgbsift.Soft-1000.devel2011-new.L1norm1x1.shotMAX.R11';
if(isset($_REQUEST['vModelFeatureConfigName']))
{
	$szModelFeatureConfigName = $_REQUEST['vModelFeatureConfigName'];
}

$szResultDir = sprintf("%s/%s", $szRootResultDir, $szTestConfigName);
$szResultConceptDir = sprintf("%s/%s", $szResultDir, $szModelFeatureConfigName);

$arConceptList = collectDirsInOneDir($szResultConceptDir);

if($nAction == 2)  // Let user pick the ModelFeatureConfig
{
	printf("<P><H1>View Results</H1>\n");

	printf("<FORM TARGET='_blank'>\n");

	printf("<P><H2>Select TestConfigName</H2>\n");
	printf("<SELECT NAME='vTestConfigName'>\n");
	printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szTestConfigName, $szTestConfigName);
	printf("</SELECT>\n");

	printf("<P><H2>Select ModelFeatureConfigName</H2>\n");
	printf("<SELECT NAME='vModelFeatureConfigName'>\n");
	printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szModelFeatureConfigName, $szModelFeatureConfigName);
	printf("</SELECT>\n");

	printf("<P><H2>Select ConceptName</H2>\n");
	printf("<SELECT NAME='vConceptName'>\n");
	foreach($arConceptList as $szConceptName)
	{
		printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szConceptName, $szConceptName);
	}
	printf("</SELECT>\n");
	
	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='3'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}


$szConceptName = 'objviolentscenes';
if(isset($_REQUEST['vConceptName']))
{
	$szConceptName = $_REQUEST['vConceptName'];
}

// load rank list
$szFPRankFN = sprintf("%s/%s.rank", $szResultConceptDir, $szConceptName);

if(!file_exists($szFPRankFN))
{
	exit("File not found [$szFPRankFN]\n");
}

loadListFile($arList, $szFPRankFN);

$arRankList = array();
foreach($arList as $szLine)
{
	$arTmp = explode(" ", $szLine);
	
	//$arOutput[] = sprintf("%s 0 %s %s %s %s", $nConceptID, $szShotID, $nRank, $fScore, $szRunSysID);
	
	$szConcetpNamezz = trim($arTmp[0]);
	if($szConcetpNamezz != $szConceptNmae)
	{
		// do nothing --> checkpoint
	}
	$szShotID = trim($arTmp[2]);
	$fScore = floatval($arTmp[4]);

	$arRankList[$szShotID] = $fScore;
}

// load metadata
$szFPTestConfigFN = sprintf("%s/%s.cfg", $szResultDir, $szTestConfigName);

$arTestConfig = loadExperimentConfig($szFPTestConfigFN); // to get model_name & test_pat

$arTestConfig['test_pat'] = "test2013-new";
$szTestVideoList = sprintf("%s.lst", $arTestConfig['test_pat']);

$szFPTestVideoListFN = sprintf("%s/%s", $szExpMetaDataDir, $szTestVideoList);
printf("%s", $szFPTestVideoListFN);
loadListFile($arList, $szFPTestVideoListFN);
$arPathLUT = array();
foreach($arList as $szLine)
{
	$arTmp = explode("#$#", $szLine);
	$szVideoID = trim($arTmp[0]);
	$szVideoPath = trim($arTmp[2]);
	
	$arPathLUT[$szVideoID] = $szVideoPath;
}

$arShotKeyFrameLUT = array();
$arShotVideoLUT = array();
foreach($arPathLUT as $szVideoID => $szVideoPath)
{
	$szTestPatName = $arTestConfig['test_pat'];
	
	$szFPKeyFrameListFN = sprintf("%s/%s/%s.prgz", $szExpMetaDataDir, $szTestPatName, $szVideoID);
	loadListFile($arList, $szFPKeyFrameListFN);
	foreach($arList as $szLine)
	{
		$arTmp = explode("#$#", $szLine);
		$szOrigVideoID = trim($arTmp[0]);
		$szShotID = trim($arTmp[1]);
		$szKeyFrameID = trim($arTmp[2]);
		
//		print_r($arTmp);exit();
		
		$arShotVideoLUT[$szShotID][$szVideoID][] = $szKeyFrameID;
	}	
}

//print_r($arShotVideoLUT);exit();

// show rank list
$nCount = 1;
foreach($arRankList as $szShotID => $fScore)
{
	//printf("<P>%s\n", $szShotID);
	//continue;
	// show keyframes of szShotID
	

//	print_r($arShotVideoLUT[$szShotID]);
//	exit();
	foreach($arShotVideoLUT[$szShotID] as $szVideoIDzz => $arKeyFrameListzz)
	{
		printf("<P>%d. [%s] - \n", $nCount, $szVideoIDzz);
		//print_r($arKeyFrameListzz);exit();
		$nSubCount = 1;
		foreach($arKeyFrameListzz as $szKeyFrameID)
		{
			if(($nSubCount % 5) == 1)
			{
				//printf("%s - ", $szKeyFrameID);

				//print_r($arPathLUT);
				//$szVideoPath = $arPathLUT[$szVideoIDzz];
				$szFPKeyFrameIDFN = sprintf("%s/%s/%s/%s.jpg", $szRootKeyFrameDir, $szVideoPath, $szVideoIDzz, $szKeyFrameID);
				if(file_exists($szFPKeyFrameIDFN))
				{
					printf("%s - ", $szKeyFrameID);
				}
				else 
				{
					printf("File not found %s", $szFPKeyFrameIDFN);	
				}
			}
			$nSubCount++;
				
		}
		printf("\n");
		
	}	
	$nCount++;
	if($nCount >= 100)
	{
		break;
	}
}
exit();

////////////////// SHOW QUERY ///////////////////
$arOutput = array();
$arOutput[] = sprintf("<P><H1>RunID: [%s]</H1>\n", $szRunID);
$arOutput[] = sprintf("<P><H1>Query [%s] - [%s]</H1>\n", $szQueryID, $szText);
$arOutput[] = sprintf("<P><H1>Scale factor (to scale up the test image using DPM model) - [%0.6f]</H1><BR>\n", $fConfigScale);
foreach($arQueryImgList as  $szQueryImg)
{
		$szURLImg = sprintf("%s/%s.%s", $szQueryKeyFrameDir, $szQueryImg, "png");
		if(!file_exists($szURLImg))
		{
            printf("<!-- File not found [%s] -->\n", $szURLImg);		  
		}
		$szRetURL = $szURLImg;
		$imgzz = imagecreatefrompng($szRetURL);
		$widthzz = imagesx($imgzz);
		$heightzz = imagesy($imgzz);

		// calculate thumbnail size
		$new_width = $thumbWidth;  // to reduce loading time
		$new_height = floor($heightzz*($thumbWidth/$widthzz));

		// create a new temporary image
		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		// copy and resize old image into new image
		// imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);

		// better quality compared with imagecopyresized
		imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
		//output to buffer
		ob_start();
		imagejpeg($tmp_img);
		$szImgContent = base64_encode(ob_get_clean());
		$arOutput[] = sprintf("<IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64,". $szImgContent ."' />", $szQueryImg, $fScore);

		imagedestroy($imgzz);
		imagedestroy($tmp_img);
		//		$arOutput[] = sprintf("<IMG SRC='%s' WIDTH='100' TITLE='%s'/> \n", $szURLImg, $szQueryImg);
}
$arOutput[] = sprintf("<P><BR>\n");

////////////////// VIEW DPM MODELS /////////////
$szModelDir = sprintf("%s/model/ins-dpm/%s/%s", $gszRootBenchmarkDir, $szTVYear, $szQueryPatName);
$szURLImg = sprintf("%s/%s.%s", $szModelDir, $szQueryID, "png");
if(!file_exists($szURLImg))
{
	printf("<!-- File not found [%s] -->\n", $szURLImg);
}
else
{
    $arOutput[] = sprintf("<P><H1>DPM Model</H1>\n", $szQueryID, $szText);   
}
$szRetURL = $szURLImg;
$imgzz = imagecreatefrompng($szRetURL);
$widthzz = imagesx($imgzz);
$heightzz = imagesy($imgzz);

// calculate thumbnail size
$new_width = $widthzz;
$new_height = $heightzz;

// create a new temporary image
$tmp_img = imagecreatetruecolor($new_width, $new_height);

// copy and resize old image into new image
// imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);

// better quality compared with imagecopyresized
imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
//output to buffer
ob_start();
imagejpeg($tmp_img);
$szImgContent = base64_encode(ob_get_clean());
$arOutput[] = sprintf("<IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64,". $szImgContent ."' />", $szQueryImg, $fScore);

imagedestroy($imgzz);
imagedestroy($tmp_img);
//		$arOutput[] = sprintf("<IMG SRC='%s' WIDTH='100' TITLE='%s'/> \n", $szURLImg, $szQueryImg);

$arOutput[] = sprintf("<P><BR>\n");

//// VERY SPECIAL ****
////////////////// SHOW GROUNDTRUTH ///////////////////
$nShowGT = $_REQUEST['vShowGT'];
if($nShowGT)
{
	$arRawList = $arNISTList[$szQueryID];
}
else
{
    //printf("Path:$szVideoPath <BR>\n");
    $szQueryResultDir1 = sprintf("%s/%s/%s", $szResultDir, $szRunID, $szVideoPath);
    $szQueryResultDir = sprintf("%s/%s/%s/%s", $szResultDir, $szRunID, $szVideoPath, $szQueryID);

    $szFPOutputFN = sprintf("%s/%s.rank", $szQueryResultDir1, $szQueryID);
	
	//if the run is using DPM --> need to load .res since it contains info of bounding box
	if(stristr($szRunID, "dpm"))
	{
		$nLoadBoundingBox = 1;
	}
	else
	{	
		$nLoadBoundingBox = 0;
	}
	
	if(stristr($szRunID, "positive"))
	{
		$nShowPrevData = 1;  // showing previous rank and score
	}
	else
	{	
		$nShowPrevData = 0;
	}

	
    if(!file_exists($szFPOutputFN) || $nLoadBoundingBox || $nShowPrevData) // re-load .res files
    {
        $arRawListz = loadRankedList($szQueryResultDir, $nTVYear);
        $arRawList = array();
        $nCount = 0;
        foreach($arRawListz as $szShotID => $fScore)
        {
            $arRawList[] = sprintf("%s#$#%0.6f", $szShotID, $fScore);
            $nCount++;
            if($nCount>20000)
                break;
        }
        //saveDataFromMem2File($arRawList, $szFPOutputFN);
    }
    else
    {
        loadListFile($arRawList, $szFPOutputFN);
    }
}

$nNumVideos = sizeof($arRawList);
$arScoreList = array();
foreach($arRawList as $szLine)
{
    $arTmp = explode("#$#", $szLine);
    $szShotID = trim($arTmp[0]);
    $fScore = floatval($arTmp[1]);
    if(sizeof($arScoreList) < 100000)
    {
        $arScoreList[$szShotID] = $fScore;
    }
}

$arTmpzzz = computeTVAveragePrecision($arAnnList, $arScoreList, $nMaxDocs=1000);
$fMAP = $arTmpzzz['ap'];
$nTotalHitsz = $arTmpzzz['total_hits'];
$arOutput[] = sprintf("<P><H3>MAP: %0.2f. Num hits (@1000): %d<BR>\n", $fMAP, $nTotalHitsz);
////

////////////////// SHOW RANKED LIST ///////////////////

$nCount = 0;

$nMaxVideosPerPage = intval($_REQUEST['vMaxVideosPerPage']);
$nPageID = max(0, intval($_REQUEST['vPageID'])-1);
$nStartID = $nPageID*$nMaxVideosPerPage;
$nEndID = min($nStartID+$nMaxVideosPerPage, $nNumVideos, 1000);

$nNumPages = min(20, intval(($nNumVideos+$nMaxVideosPerPage-1)/$nMaxVideosPerPage));
$queryURL = sprintf("vQueryID=%s&vRunID=%s&vMaxVideosPerPage=%s&vTVYear=%d&vAction=%d&", 
    urlencode($szQueryIDz), urlencode($szRunID), urlencode($nMaxVideosPerPage), $nTVYear, $nAction);
	//printf($queryURL);

$szURLz = sprintf("ksc-web-ViewResult.php?%s&vShowGT=1", $queryURL);

$nViewImg = 0;
if($nShowGT)
{
	$arOutput[] = sprintf("<P><H2>Ranked List - [Ground Truth] - [%d] Video Clips</H2>\n", $nNumVideos);
}
else
{
	$arOutput[] = sprintf("<P><H2>Total Relevant Videos <A HREF='%s'>[%s]</A>. Click the link to view all relevant ones!</H2>\n",
			$szURLz, sizeof($arNISTList[$szQueryID]));
}
$arOutput[] = sprintf("<P><H2>Page: ");
for($i=0; $i<$nNumPages; $i++)
{
	if($i != $nPageID)
	{
		$szURL = sprintf("ksc-web-ViewResult.php?%s&vPageID=%d&vShowGT=%d", $queryURL, $i+1, $nShowGT);
		$arOutput[] = sprintf("<A HREF='%s'>%02d</A> ", $szURL, $i+1);
	}
	else
	{
		$arOutput[] = sprintf("%02d ", $i+1);
	}
}

$arOutput[] = sprintf("<BR>\n");
//print_r($arScoreList);exit();
for($i=$nStartID; $i<$nEndID; $i++)
{
	$szLine = $arRawList[$i];
	$arTmp = explode("#$#", $szLine);
	$szShotID = trim($arTmp[0]);
	$fScore = floatval($arTmp[1]);

	$szShotKFDir = sprintf("%s/%s/%s", $szKeyFrameDir, $szPatName4KFDir, $szShotID); 
	
	//$arImgList = collectFilesInOneDir($szShotKFDir, "", ".jpg");
	//$arImgList = collectFilesInOneDir($szShotKFDir, "", "." . $szImgFormat);
	//printf("ShotDir: [%s] - Source: [%s]", $szShotKFDir, $szLine); exit();
	
	// load from frame.txt --> only work with CZ data
	$szFPKeyFrameListFN = sprintf("%s/frames.txt", $szShotKFDir);
	if(!file_exists($szFPKeyFrameListFN))
	{
		printf("<!-- File not found [%s]-->\n", $szFPKeyFrameListFN);
		continue;
	}
	loadListFile($arImgList, $szFPKeyFrameListFN);
	
	
	$arOutput[] = sprintf("%d. ", $nCount+1);
	$nCountz = 0;
	$nSampling = 0;
	$nNumKFzz = sizeof($arImgList);
	$nSamplingRate = intval($nNumKFzz/$nNumShownKFPerShot);
	
	$arSelList = array();

	$nGotIt = 0;
	foreach($arImgList as $szImg)
	{
		$nSampling++;
		if(($nSampling % $nSamplingRate) != 0)
		{
			continue;
		}

		//$szURLImg = sprintf("%s/%s/%s/%s.%s",
		//		$szKeyFrameDir, $szPatName4KFDir, $szShotID, $szImg, $szImgFormat);

		$szURLImg = sprintf("%s/%s/%s/%s",
				$szKeyFrameDir, $szPatName4KFDir, $szShotID, $szImg);
		///
		// generate thumbnail image
		$szRetURL = $szURLImg;
		
		if(!file_exists($szURLImg))
		{
		    printf("<!-- File not found [%s] -->\n", $szURLImg);
		    exit();
		}
		
		if($szImgFormat == "png")
		{
			$imgzz = imagecreatefrompng($szRetURL);
		}
		else
		{
			$imgzz = imagecreatefromjpeg($szRetURL);
		}
		
		if(!$imgzz)
		{
			printf("<P>Error in loading image [%s]<br>\n", $szRetURL);
			exit();
		}


		$widthzz = imagesx($imgzz);
		$heightzz = imagesy($imgzz);

		// calculate thumbnail size
		$new_width = $thumbWidth;  // to reduce loading time
		
		$fScaleFactor = 1.0*$thumbWidth/$widthzz/$fConfigScale;
		$new_height = floor($heightzz*($thumbWidth/$widthzz));

		// create a new temporary image
		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		// copy and resize old image into new image
		// imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);

		// better quality compared with imagecopyresized
		imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
		
		$red = imagecolorallocate($tmp_img, 255, 0, 0);
		$green = imagecolorallocate($tmp_img, 0, 255, 0);

		//print_r($arBoundingBoxList[$szShotID]);
		//exit($szKeyFrameIDz);
		if($nLoadBoundingBox)
		{
			$nMatch = 0;
			foreach($arBoundingBoxList[$szShotID] as $szKeyFrameIDz => $arCoods)
			{
				//print_r($arCoods); exit();
				//exit("$szKeyFrameIDz - $szImg");
				
			    // Fix this bug: shot200_832_KSC00:43:45.8_000005 - 00:43:45.8_000004.png
				$szImg1 = str_replace('.png', '', $szImg);
			    if(strstr($szKeyFrameIDz, $szImg1))
				{
				  $nLeft = intval($arCoods['l']*$fScaleFactor);
				  $nTop = intval($arCoods['t']*$fScaleFactor);
				  $nRight = intval($arCoods['r']*$fScaleFactor);
				  $nBottom = intval($arCoods['b']*$fScaleFactor);
				  
				  $arSelList[] = $szImg;
				  $nMatch = 1;
				  break;
				}
				else  // keep it for the case of no match 
				{
				    //printf('<P>No match %s - %s', $szKeyFrameIDz, $szImg);
					$nLeft = intval($arCoods['l']*$fScaleFactor);
					$nTop = intval($arCoods['t']*$fScaleFactor);
					$nRight = intval($arCoods['r']*$fScaleFactor);
					$nBottom = intval($arCoods['b']*$fScaleFactor);
				}
			}

			if($nMatch)
			{
				imagerectangle($tmp_img, $nLeft, $nTop, $nRight, $nBottom, $red);	// true detection result
			}
			else
			{
				imagerectangle($tmp_img, $nLeft, $nTop, $nRight, $nBottom, $green); // just for reference	because the keyframe is different - might be OK if two frames are adjcent
			}
		}
		
		$szPrevData = "";
		if($nShowPrevData)
		{
			//print_r($arPrevDataList); exit();
			//printf("[%s] - [%s]", $szKeyFrameIDz, $szImg);
			//exit();
			
			if(!isset($arPrevDataList[$szShotID]))
			{
				printf("Data not set for [%s]\n", $szShotID);
				exit();
			}
			$szPrevData = sprintf("Prev rank: [%d] - Prev score [%0.6f]", $arPrevDataList[$szShotID]['rank'], $arPrevDataList[$szShotID]['score']);
		}		
        
		ob_start();
		imagejpeg($tmp_img);
		$szImgContent = base64_encode(ob_get_clean());
		// update Jul 13, 2014 --> adding URL to view matched points
		$szURL = sprintf('ksc-web-ViewMatch.php?vQueryID=%s&vShotID=%s&vTVYear=%s&vRunID=%s', urlencode($szQueryIDz), $szShotID, $nTVYear, urlencode($szRunID));

		$arOutput[] = sprintf("<A HREF='%s' TARGET=_blank><IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64,". $szImgContent ."' /></A>", $szURL, $szShotID, $fScore );

		imagedestroy($imgzz);
		imagedestroy($tmp_img);
		///
		//		$arOutput[] = sprintf("<IMG SRC='%s' WIDTH='100' TITLE='%s - %s'/> \n", $szURLImg, $szImg, $fScore);
		$nCountz++;
		if($nCountz>=$nNumShownKFPerShot)
		{
			break;
		}
	}

/*	
	if(sizeof($arSelList) == 0)
	{
	    print_r($arBoundingBoxList[$szShotID]);
	    print_r($arImgList);exit();
	}
*/
	$arOutput[] = sprintf("[%s-%0.6f]\n", $szShotID, $fScore);
	if($szPrevData!="")
	{
	    $arOutput[] = sprintf("[%s]\n", $szPrevData);
	}	
	if(in_array($szShotID, $arNISTList[$szQueryID]))
	{
		$arOutput[] = sprintf("<IMG SRC='winky-icon.png'><BR>\n");
		$nHits++;
	}
	else
	{
		if(in_array($szShotID, $arJudgedShots[$szQueryID]))
		{
			$arOutput[] = sprintf("<IMG SRC='sad-icon2.png'><BR>\n");
		}
		else
		{
			$arOutput[] = sprintf("<IMG SRC='unknown-icon.png' WIDTH=50><BR>\n");
		}
	}

	$arOutput[] = sprintf("<BR>\n");

	$nCount++;
	if($nCount > 100)
	{
		break;
	}
}

$arOutput[] = sprintf("<P><H2>Num hits (top %s): %d/%d.</H2>\n", $nMaxVideosPerPage, $nHits, $nTotalHits);

$arOutput[] = sprintf("<P><H2>Page: ");
for($i=0; $i<$nNumPages; $i++)
{
	if($i != $nPageID)
	{
		$szURL = sprintf("ksc-web-ViewResult.php?%s&vPageID=%d&vShowGT=%d", $queryURL, $i+1, $nShowGT);
		$arOutput[] = sprintf("<A HREF='%s'>%02d</A> ", $szURL, $i+1);
	}
	else
	{
		$arOutput[] = sprintf("%02d ", $i+1);
	}
}
$arOutput[] = sprintf("<P><BR>\n");

foreach($arOutput as $szLine)
{
	printf("%s\n", $szLine);
}

//ob_flush_end();
exit();

//////////////////////////////// FUNCTIONS ///////////////////////////////////

?>
