<?php
/**
 * 		@file 	ksc-web-Demo-NTDV.php
 * 		@brief 	Predict an uploaded image .
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 18 Oct 2014.
 */

ini_set("max_execution_time", "120"); // 120 secs
 
require_once "ksc-AppConfig.php";

$gszKeyFramePathDir = "keyframe-5";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootKeyFrameDir = sprintf("%s/%s", $szRootDir, $gszKeyFramePathDir);
$szRootFeatureDir = sprintf("%s/feature/%s", $szRootDir, $gszKeyFramePathDir);
$szRootMetaDataDir = sprintf("%s/metadata/%s", $szRootDir, $gszKeyFramePathDir);
$szRootPatFileDir = $szRootMetaDataDir;
$szRootPrgFileDir = $szRootMetaDataDir;


// ### STEP 1: Save image to per900c server -- upload --> each uploaded image ~ one unique name
$time_start = microtime(true);

if(isset($_REQUEST['vImgURL']))
{
	$szInputURL = $_REQUEST['vImgURL'];
	$szImgName1 = str_replace(".jpg", "", basename($szInputURL));
	printf("<!--[%s] -- [%s] -->\n", $szInputURL, $szImgName1);
}
else // input  is uploaded file
{
	if(isset($_REQUEST['vImgName']))
	{
	$szRootURL = "http://www.satoh-lab.nii.ac.jp/~ledduy/Demo-VSD/upload";
	$szImgName1 = $_REQUEST['vImgName'];
	$szInputURL = sprintf("%s/%s.jpg", $szRootURL, $szImgName1);
	}
	else
	{
		printf("Error - Input file not found!\n");exit();
	}
}

// all uploaded files will be stored here
// and then copy to keyframe-5/web-test
$szWebTestDir = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/upload";

// to ensure upload files having unique names
$szFPTmpName = tempnam($szWebTestDir, $szImgName1);
$szFPOutputFN = sprintf("%s.jpg", $szFPTmpName);
$szImgName = str_replace(".jpg", "", basename($szFPOutputFN)); // ***vCHANGE***
saveImageFromURL($szFPOutputFN, $szInputURL);
deleteFile($szFPTmpName);

$szCmdLine = sprintf("chmod 777 %s", $szFPOutputFN);
system($szCmdLine);
$szFPSrcFN  = $szFPOutputFN;

// ### STEP 2: Extract feature

// Day la ds cac tham so khi goi script ksc-Feature-ExtractBaselineFeature.php

// printf("Usage: %s <FeatureExt> <FeatureConfigFile> <PatName> <VideoPath> <Start> <End>\n", $argv[0]);
$szFeatureExt = "nsc.cCV_GRAY.g4.q59.g_lbp";

if(isset($_REQUEST['vFeatureExt']))
{
	$szFeatureExt = $_REQUEST['vFeatureExt'];
}
$szFeatureConfigDir = "BaselineFeatureConfig";
$szFPFeatureConfigFN = sprintf("%s/ConfigFile.%s.txt", $szFeatureConfigDir, $szFeatureExt); 
$szPatName = "web-test";
$szVideoPath = $szPatName;


// Cac anh upload se duoc gom chung vao partition goi la web-test
// Trong partition nay, moi VideoID se co ten la NTDV_xxx, 
// trong do xxx sinh ra tu ham tempnam de phan biet giua cac lan upload khac nhau 
// de dam bao cac anh upload duoc xu li rieng biet, ko trung nhau

$szDestKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szPatName);

$szTmpUploadedKeyFrameDir = tempnam($szDestKeyFrameDir, "NTDV");
$szUploadedKeyFrameDir = sprintf("%s/%s-x", $szDestKeyFrameDir, basename($szTmpUploadedKeyFrameDir));
makeDir($szUploadedKeyFrameDir);
$szCmdLine = sprintf("chmod 777 -R %s", $szUploadedKeyFrameDir);
system($szCmdLine);
deleteFile($szTmpUploadedKeyFrameDir);
$szWebTestVideoID = basename($szUploadedKeyFrameDir); // de lay ra NTDV_xxx

// moi anh upload --> sinh ra VideoID tuong ung, chep vao thu muc VideoID do
// copy uploaded image to the dest dir: keyframe-5/web-test/NTDV-xxx

$szCmdLine = sprintf("cp %s %s", $szFPSrcFN, $szUploadedKeyFrameDir);
system($szCmdLine);
$szCmdLine = sprintf("chmod 777 -R %s", $szUploadedKeyFrameDir);
system($szCmdLine);

// sinh ra file VideoID.prg trong thu muc metadata
$szDestMetaDataDir = sprintf("%s/%s", $szRootMetaDataDir, $szPatName);
makeDir($szDestMetaDataDir);
$szCmdLine = sprintf("chmod 777 -R %s", $szDestMetaDataDir);
system($szCmdLine);

// file .prg chi co duy nhat 1 keyframe - chinh la ten cua keyframe duoc upload
$szFPVideoProgFN = sprintf("%s/%s.prg", $szDestMetaDataDir, $szWebTestVideoID);
$arTemp = array();
$arTemp[] = sprintf("%s", basename($szFPSrcFN, ".jpg")); // ko lay .jpg (mac dinh anh upload la .jpg)
saveDataFromMem2File($arTemp, $szFPVideoProgFN);
$szCmdLine = sprintf("chmod 777 %s", $szFPVideoProgFN);
system($szCmdLine);

// them file .prg nay vao trong web-test.lst
$szFPWebTestFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szPatName);
if(!file_exists($szFPWebTestFN))
{
	// tao ra file nay va them file .prg vao
	$arTemp = array();
	
	$arTemp[] = sprintf("%s #$# %s #$# web-test", $szWebTestVideoID, $szWebTestVideoID); 
	saveDataFromMem2File($arTemp, $szFPWebTestFN);
	$szCmdLine = sprintf("chmod 777 %s", $szFPWebTestFN);
	system($szCmdLine);
	$nInFilePos = 0; // first position
}
else // neu tap tin da ton tai thi se them vao cuoi
{
	$nInFilePos = loadListFile($arTemp, $szFPWebTestFN); // last position
	$arTemp[$nInFilePos] = sprintf("%s #$# %s #$# web-test", $szWebTestVideoID, $szWebTestVideoID); 

	saveDataFromMem2File($arTemp, $szFPWebTestFN);
	$szCmdLine = sprintf("chmod 777 %s", $szFPWebTestFN);
	system($szCmdLine);	
}

// InFilePos --> dung de dinh vi VideoID trong file web-test.prg

// Feature Dir
$szDestFeatureDir = sprintf("%s/%s/%s", $szRootFeatureDir, $szFeatureExt, $szPatName);
makeDir($szDestFeatureDir);
$szCmdLine = sprintf("chmod 777 -R %s", $szDestFeatureDir);
system($szCmdLine);

$nStart = $nInFilePos;
$nEnd = $nInFilePos+1;

$szParam = sprintf("%s %s %s %s %d %d", $szFeatureExt, $szFPFeatureConfigFN, $szPatName, $szVideoPath, $nStart, $nEnd);
$szCmdLine = sprintf("php -f ksc-Feature-ExtractBaselineFeature.php %s", $szParam);
execSysCmd($szCmdLine);
$szCmdLine = sprintf("chmod 777 -R %s", $szDestFeatureDir);
system($szCmdLine);

// convert to shotFeature --> just copy and rename
$szShotExt="shotMAX";
$szShotFeatureExt = sprintf("%s.%s", $szFeatureExt, $szShotExt);
$szShotDestFeatureDir = sprintf("%s/%s/%s", $szRootFeatureDir, $szShotFeatureExt, $szPatName);

convert2ShotFeature($szDestFeatureDir, $szShotDestFeatureDir, $szShotExt);	
compressFeatureFiles($szShotDestFeatureDir);

### STEP 3: Predict

 //   printf("Usage %s %s %s %s %s %s %s\n", $argv[0], $szTestConfigName, $szModelFeatureConfig, $nStartConcept, $nEndConcept, $nStartPrg, $nEndPrg);
 
$szTestConfigName = "mediaeval-vsd-2014.devel2014-new.web-test";
$szModelFeatureConfig = sprintf("%s.%s.R11", $szFeatureExt, $szShotExt);
$nStartConcept = 0;
$nEndConcept = 1;
$nStartPrg = $nInFilePos;
$nEndPrg = $nInFilePos+1;
$szParam = sprintf("%s %s %s %s %s %s", $szTestConfigName, $szModelFeatureConfig, $nStartConcept, $nEndConcept, $nStartPrg, $nEndPrg);
$szCmdLine = sprintf("php -f ksc-ProcessOneRun-Test-New-NTDV.php %s", $szParam);
execSysCmd($szCmdLine);
	
### STEP 4 - Return result

// /export/das11f/ledduy/mediaeval-vsd-2014/result/keyframe-5/mediaeval-vsd-2014.devel2014-new.web-test/nsc.cCV_GRAY.g4.q59.g_lbp.shotMAX.R11/subjviolentscenes
$szResDir = sprintf("%s/result/keyframe-5/%s/%s/subjviolentscenes", $szRootDir, $szTestConfigName, $szModelFeatureConfig);

// NTDV034fCq-x.nsc.cCV_GRAY.g4.q59.g_lbp.shotMAX.svm.res
$szFPScoreFN = sprintf("%s/%s.%s.svm.res", $szResDir, $szWebTestVideoID, $szShotFeatureExt);

loadListFile($arTmp, $szFPScoreFN);
foreach($arTmp as $szLine)
{
	$arZ = explode("#$#", $szLine);
	$szKeyFrameFN = trim($arZ[0]);
	$fScore = floatval($arZ[1]);
	
	break; // only one keyframe
}

printf("HIDEALL");
printf("<!--%s - %0.6f-->\n", $szKeyFrameFN, $fScore);

		$szRetURL = $szFPSrcFN;
		
	if(file_exists($szFPSrcFN))
	{
		//printf($szRetURL);exit();
		$imgzz = imagecreatefromjpeg($szRetURL);
		$widthzz = imagesx($imgzz);
		$heightzz = imagesy($imgzz);

		// calculate thumbnail size
		$thumbWidth = min($widthzz, 320);
		$new_width = $thumbWidth;  // to reduce loading time
		$new_height = floor($heightzz*($thumbWidth/$widthzz));

		// create a new temporary image
		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		// better quality compared with imagecopyresized
		imagecopyresampled($tmp_img, $imgzz, 0, 0, 0, 0, $new_width, $new_height, $widthzz, $heightzz);
		//output to buffer
		ob_start();
		imagejpeg($tmp_img);
		$szImgContent = base64_encode(ob_get_clean());
		$szOutput = sprintf("<IMG  TITLE='%s - %s' SRC='data:image/jpeg;base64,". $szImgContent ."' />", $szQueryImg, $fScore);

		imagedestroy($imgzz);
		imagedestroy($tmp_img);
		printf("<H3>Query Image:</H3><P>%s\n", $szOutput);
	}	
	else
	{
		exit("File not found [$szFPSrcFN]");
	}
	
	$fThreshold = 0.1;
	if($fScore >= $fThreshold)
	{
		$szRes = "<IMG SRC='winky-icon.png'> - VIOLENCE FOUND"; 
	}
	else
	{
		$szRes = "<IMG SRC='sad-icon2.png'> - VIOLENCE NOT FOUND"; 
	}
	printf("<H3>Prediction Score [%s]: %0.6f - %s\n", $szFeatureExt, $fScore, $szRes);

/////////////////////	
function convert2ShotFeature($szFeatureDir, $szShotFeatureDir, $szShotExt="shotMAX")	
{
	$arFileList = collectAllFilesInOneDir($szFeatureDir);

	sort($arFileList);
	$nCount = 0;
	
	foreach($arFileList as $szFileName)
	{
		$szFPInputFN = sprintf("%s/%s", $szFeatureDir, $szFileName);
		
		$szFPOutputFN = sprintf("%s/%s.%s", $szShotFeatureDir, $szFileName, $szShotExt);

		if(!file_exists($szFPInputFN))
		{
			continue;
		}

		if(file_exists($szFPOutputFN))
		{
			printf("Skip - File existed [%s]\n", $szFPOutputFN);
			continue;
		}

		$szCmdLine = sprintf("cp %s %s",
				$szFPInputFN, $szFPOutputFN);
		execSysCmd($szCmdLine);
	}
}

function compressFeatureFiles($szDirName)
{
	$arFileList = collectAllFilesInOneDir($szDirName);

	sort($arFileList);
	$nCount = 0;
	foreach($arFileList as $szFileName)
	{
		$szFPInputFN = sprintf("%s/%s", $szDirName, $szFileName);

		if(strstr($szFileName, "tar.gz"))
		{
			printf("File [%s] already in compressed format!\n", $szFileName);
			$nCount++;
			continue;
		}

		$szFPOutputFN = sprintf("%s.tar.gz", $szFPInputFN);

		if(!file_exists($szFPInputFN))
		{
			continue;
		}

		$szCmdLine = sprintf("tar -cvzf %s -C %s %s",
				$szFPOutputFN, $szDirName, $szFileName);
		execSysCmd($szCmdLine);

		// already compressed successfully --> delete orig file
		if(file_exists($szFPOutputFN) && filesize($szFPOutputFN) > 0)
		{
			$nCount++;
			deleteFile($szFPInputFN);
		}
	}

	return $nCount;
}	

function saveImageFromURL($szFPOutputFN, $szInputURL)
{
	$ch = curl_init($szInputURL);
	$fp = fopen($szFPOutputFN, "wb");

	// set URL and other appropriate options
	$options = array(CURLOPT_FILE => $fp,
	CURLOPT_HEADER => 0,
	CURLOPT_FOLLOWLOCATION => 1,
	CURLOPT_TIMEOUT => 60); // 1 minute timeout (should be enough)

	curl_setopt_array($ch, $options);

	$nRet = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	fclose($fp);

	if ($nRet === false || $info['http_code'] != 200)
	{
		$nRet = 0;
	}
	else
	{
		$nRet = 1;
	}

	return $nRet;
}
?>