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
$MAXShot = 20;
$MAXKfShot = 5;
////////////////// START //////////////////
function CutExt($strFullName)
{
	$strtmp = explode(".",$strFullName);
	$count = count($strtmp);
	$strExt = $strtmp[$count-1];
	$strResult = substr($strFullName,0,-1*(strlen($strExt)+1));
	return $strResult;
}
function CutPath($strFullName)
{
	$strtmp = explode("/",$strFullName);
	$count = count($strtmp);
	$strExt = $strtmp[$count-1];
	return $strExt;
}

//$szRootExpDir  = $szRootDir
$szRootExpDir = sprintf("%s", $gszRootBenchmarkExpDir);

$szRootResultDir = sprintf("%s/result/keyframe-5", $szRootExpDir); // dir containing prediction result of RUNs
$arResultDirList = collectDirsInOneDir($szRootResultDir);

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootExpDir); // dir containing keyframes


$szExpMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootExpDir); // --> get test-pat


$nAction = 1;

printf("<P><H1>Violent Scene Detection - View Results by Videos</H1>\n");
    
$szTestConfigName = 'mediaeval-vsd-2014.devel2013-new.test2014-new';

$szResultDir = sprintf("%s/%s", $szRootResultDir, $szTestConfigName);
$arModelFeatureDirList = collectDirsInOneDir($szResultDir);

$szModelFeatureConfigName = 'fusion_fn_dense6mul.rgbsift.n3x1.shotmax_motion_audio[1-2-1]';

$szResultDir = sprintf("%s/%s", $szRootResultDir, $szTestConfigName);
$szResultConceptDir = sprintf("%s/%s", $szResultDir, $szModelFeatureConfigName);

//$arConceptList = collectDirsInOneDir($szResultConceptDir);
$arConceptList = collectFilesInOneDir($szResultConceptDir,"",".rank");
//print_r($arConceptList);


$szConceptName = 'subjviolentscenes';


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

//$arTestConfig['test_pat'] = "test2013-new";
$arrPart = array();
$arrPart["mediaeval-vsd-2014.devel2013-new.test2013-new"]="test2013";
$arrPart["mediaeval-vsd-2014.devel2014-new.test2014-new"]="test2014";
$arrPart["mediaeval-vsd-2014.devel2013-new.test2014-new"]="test2014";

$arTestConfig['test_pat'] = $arrPart[$szTestConfigName];
$szTestVideoList = sprintf("%s.lst", $arTestConfig['test_pat']);

$szFPTestVideoListFN = sprintf("%s/%s", $szExpMetaDataDir, $szTestVideoList);
//printf("%s", $szFPTestVideoListFN);
loadListFile($arList, $szFPTestVideoListFN);
$arPathLUT = array();
$arrListfilm = array();
foreach($arList as $szLine)
{
	$arTmp = explode("#$#", $szLine);
	$szVideoID = trim($arTmp[0]);
	$szFilmName = trim($arTmp[1]);
	$arrListfilm[$szVideoID]= $szFilmName; 
	$szVideoPath = trim($arTmp[2]);
	
	$arPathLUT[$szVideoID] = $szVideoPath;
	//$arPathLUT[$szFilmName] = $szVideoPath;
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
		
		//$arShotVideoLUT[$szShotID][$szVideoID][] = $szKeyFrameID;
		$arShotVideoLUT[$szVideoID][$szShotID][] = $szKeyFrameID;
	}	
}


$nPageIDForm = (int)$_REQUEST['vPageID'];
//printf("This is page %s",$nPageIDForm);
if ($nPageIDForm>1)
{
	$nStart = ($nPageIDForm-1) * $MAXShot;
}else
{
	$nPageIDForm=1;
	$nStart = 0;
}

$szVideoID = $_REQUEST['vVideoID'];
$szSort = $_REQUEST['vSort'];
if (!$szVideoID)
{
	printf("<FORM TARGET='_self'>\n");

	printf("<H3>Select video: ");
	printf("<SELECT NAME='vVideoID'>\n");

	foreach($arrListfilm as $VideoID => $VideoName)
	{
		//if ($sztmp == $_REQUEST['vVideoID'])
		//	printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n",$strFilmName,$strFilmName);
		//else
		printf("<OPTION VALUE='%s'>%s</OPTION>\n",$VideoID,$VideoName);

	}
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE=1>\n");
	printf("<H3>Page: ");
	printf("<INPUT TYPE=TEXT NAME='vPageID' VALUE='%d' SIZE=10></H3>\n",$nPageIDForm);
	printf("<H3>List of shots by:<br>");
	printf("<input type='radio' name='vSort' value='Score' checked>Violent score<br>");
	printf("<input type='radio' name='vSort' value='Time'>Time<br>");
	
	printf("</SELECT></H3>\n");
	printf("<P><INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");

	exit;
}
else
{
	printf("<FORM TARGET='_self'>\n");
	
	printf("<H3>Selected video: ");
	printf("<SELECT NAME='vVideoID'>\n");
	
	foreach($arrListfilm as $VideoID => $VideoName)
	{
		//if ($sztmp == $_REQUEST['vVideoID'])
		//	printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n",$strFilmName,$strFilmName);
		//else
		if ($VideoID ==$szVideoID)
			printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n",$VideoID,$VideoName);
		else
			printf("<OPTION VALUE='%s'>%s</OPTION>\n",$VideoID,$VideoName);
	
	}
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE=1>\n");
	printf("<H3>Page: ");
	printf("<INPUT TYPE=TEXT NAME='vPageID' VALUE='%d' SIZE=10></H3>\n",$nPageIDForm);

	printf("<H3>List of shots by:<br>");
	if ($szSort=="Score")
	{
		printf("<input type='radio' name='vSort' value='Score' checked>Violent score<br>");
		printf("<input type='radio' name='vSort' value='Time'>Time<br>");
	}
	else 
	{
		printf("<input type='radio' name='vSort' value='Score'>Violent score<br>");
		printf("<input type='radio' name='vSort' value='Time' checked>Time<br>");
	}
	printf("</SELECT></H3>\n");
	printf("<P><INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");

}
$arrResult = array();
foreach($arShotVideoLUT[$szVideoID] as $szShotIDzz => $arKeyFrameListzz)
{
	$arrResult[$szShotIDzz] = $arRankList[$szShotIDzz];
}
//arsort($arrResult);
//ksort($arrResult);

if ($szSort=="Score")
{
	arsort($arrResult);
}

$arrResultZZ = array();
foreach($arrResult as $szShotIDzz => $fScore)
{
	$arrResultID[] = $szShotIDzz;
	$arrResultScore[]= $fScore;
}

$nTotalShot = sizeof($arrResultID);



//$nStart = 0;
$nNumPage = ceil($nTotalShot/$MAXShot);

printf("There are %d shots in %d pages<br>",$nTotalShot,$nNumPage);
if ($nPageIDForm>1)
{
	printf("This is page %d/%d<br>",$nPageIDForm,$nNumPage);
} else 
{
	printf("This is first page.<br>");
}

$nCount = 1;

//foreach($arShotVideoLUT[$szVideoID] as $szShotIDzz => $arKeyFrameListzz)
//foreach($arrResult as $szShotIDzz => $fScore)
for ($i=$nStart;$i<$nTotalShot;$i++) 
{
	$szShotIDzz = $arrResultID[$i];
	$fScore = $arrResultScore[$i];
	printf("<P>%d. [%s] - Violent score = %0.4f \n<br>", $nCount, $szShotIDzz,$fScore);
	//print_r($arKeyFrameListzz);exit();
	$nSubCount = 1;
	foreach($arShotVideoLUT[$szVideoID][$szShotIDzz] as $szKeyFrameID)
	{
		if(($nSubCount % $MAXKfShot) == 1)
		{
			//printf("%s - ", $szKeyFrameID);

			//print_r($arPathLUT);
			$szVideoPath = $arPathLUT[$szVideoID];
			$szFPKeyFrameIDFN = sprintf("%s/%s/%s/%s.jpg", $szRootKeyFrameDir, $szVideoPath, $szVideoID, $szKeyFrameID);
			if(file_exists($szFPKeyFrameIDFN))
			{
				//printf("%s - ", $szKeyFrameID);
				$imgzz = imagecreatefromjpeg ( $szFPKeyFrameIDFN );
				$widthzz = imagesx ( $imgzz );
				$heightzz = imagesy ( $imgzz );

				// calculate thumbnail size
				$new_width = $thumbWidth = 100;
				$new_height = floor ( $heightzz * ($thumbWidth / $widthzz) );

				// create a new temporary image
				$tmp_img = imagecreatetruecolor ( $new_width, $new_height );

				// copy and resize old image into new image
				// imagecopyresized($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);

				// better quality compared with imagecopyresized
				imagecopyresampled ( $tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz );
				// output to buffer
				ob_start ();
				imagejpeg ( $tmp_img );

				$szImgContent = base64_encode ( ob_get_clean () );
				// $szFilmName
				// $szShotID
				// $szSBfiles
				// $szGroupName
					
				//$szViewVideoClipURL = getVideoClipURL ( $szFilmName, $szShotID, $szSBfiles, $szGroupName );
				$szViewVideoClipURL = "#";
					
				//$nID = $nStartID + $nCount;
				//$nID = $nSubCount;
				printf ( "<A HREF='%s' target='_blank'><IMG TITLE='%s' WIDTH='100' SRC='data:image/jpeg;base64," . $szImgContent . "' /></A>\n",$szViewVideoClipURL, basename ( $szFPKeyFrameIDFN ) );
				imagedestroy ( $imgzz );
				imagedestroy ( $tmp_img );
			}
			else
			{
				printf("File not found %s", $szFPKeyFrameIDFN);
			}
		}
		$nSubCount++;

	}
	printf("\n");

	$nCount++;
	if($nCount >= $MAXShot+1)
	{
		break;
	}
}

//print_r($arShotVideoLUT);exit();

// show rank list

function getVideoClipURL($szVideoID, $szShotID, $szMetaDataDir, $szGroupName) {
	// $arTmpzz = explode(".keyframe", $szKeyFrameID);
	// $szTargetShotID = trim($arTmpzz[0]);
	$szTargetShotID = $szShotID;
	printf ( "<!--ShotID = %s-->\n", $szTargetShotID );

	// copied test video to devel dir --> BAD!!!
	// Xu ly truong hop VideoID_01
	// $strtmp = explode("_",$szVideoID);
	// $szVideoID = $strtmp[0];
	$szFPShotSBFN = sprintf ( "%s/%s.sb", $szMetaDataDir, $szVideoID );
	printf ( "<!--SB file = %s-->\n", $szFPShotSBFN );
	$nNumRows = loadListFile ( $arList, $szFPShotSBFN );
	for($i = 2; $i < $nNumRows; $i ++) {
		// movie-TheWizardOfOz-1939-dvd2000-MediaEval.Shot0001 #$# 48 #$# 26
		// movie-Armageddon-1998-dvd2002-MediaEval
		// VSD11_1
		// VSD11_1.shot1_1 #$# 0 #$# 42

		$arTmp = explode ( "#$#", $arList [$i] );
		// $szTmp = explode (".",$arTmp[0]);
		// $szShotID = trim($szTmp[1]);
		$szShotID = trim ( $arTmp [0] );
		$nStartFrame = intval ( $arTmp [1] );
		$nDuration = intval ( $arTmp [2] );

		if ($szShotID == $szTargetShotID) {
			// printf("Reach here\n");
			break;
		}

		printf ( "<!-- %s-->\n", $arList [$i] );
	}
	$szVideoName = $arList [0];
	$nDuration = min ( $nDuration, 25 * 30 ); // max 30 sec
	$nDuration = max ( 50, $nDuration ); // min 1sec
	$szClipID = sprintf ( "%s", $szShotID );
	$szViewVideoClipURL = sprintf ("2014-nsc-web-mediaeval14-ViewVideoClip.php?vVideoID=%s&vStartFrame=%d&vDuration=%d&vClipID=%s&vGroup=%s", $szVideoName, $nStartFrame, $nDuration, $szClipID, $szGroupName );
	return $szViewVideoClipURL;
}

?>
