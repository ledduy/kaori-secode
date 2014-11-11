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

require_once "ksc-AppConfig.php";

$gszKeyFramePathDir = "keyframe-5";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootKeyFrameDir = sprintf("%s/%s", $szRootDir, $gszKeyFramePathDir);
$szRootFeatureDir = sprintf("%s/feature/%s", $szRootDir, $gszKeyFramePathDir);
$szRootMetaDataDir = sprintf("%s/metadata/%s", $szRootDir, $gszKeyFramePathDir);
$szRootPatFileDir = $szRootMetaDataDir;
$szRootPrgFileDir = $szRootMetaDataDir;


// Extract feature

// Day la ds cac tham so khi goi script ksc-Feature-ExtractBaselineFeature.php

// printf("Usage: %s <FeatureExt> <FeatureConfigFile> <PatName> <VideoPath> <Start> <End>\n", $argv[0]);
$szFeatureExt = "nsc.cCV_GRAY.g4.q59.g_lbp";
$szFeatureConfigDir = "BaselineFeatureConfig";
$szFPFeatureConfigFN = sprintf("%s/ConfigFile.%s.txt", $szFeatureConfigDir, $szFeatureExt); 
$szPatName = "web-test";
$szVideoPath = $szPatName;
$nStart = 0;
$nEnd = 2;

$szParam = sprintf("%s %s %s %s %d %d", $szFeatureExt, $szFPFeatureConfigFN, $szPatName, $szVideoPath, $nStart, $nEnd);
//printf("%s",$szParam);

// Cac anh upload se duoc gom chung vao partition goi la web-test
// Trong partition nay, moi VideoID se co ten la NTDV_xxx, 
// trong do xxx sinh ra tu ham tempnam de phan biet giua cac lan upload khac nhau 
// de dam bao cac anh upload duoc xu li rieng biet, ko trung nhau
$szFPSrcFN = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/upload"; // day la duong dan den anh ma nguoi dung da upload len server
$strtmp = sprintf("%s/*",$szFPSrcFN);
$files = glob($strtmp); // get all file names
foreach($files as $file){ // iterate files
	if(is_file($file))
	{
		unlink($file); // delete file
		//$szCmdLine = sprintf("
	}
}

function check_url($url)
{
	$pos = strpos($url, "www");
	if ($pos !== false && $pos == 0)
		return true;
	$pos = strpos($url, "http");
	if ($pos !== false && $pos == 0)
		return true;
	return false;
}
$url = trim($_POST["filepath"]);

if(check_url($url))
{
	$ext = pathinfo($url);
	$query_file_name = $_POST["x1"]. "_" . $_POST["y1"]. "_" . $_POST["x2"]. "_" . $_POST["y2"]. "_". md5($url) . "." . $ext['extension'];
	//$query_file_name = $_FILES["file"]["name"];
	$query_file_path = sprintf('upload/%s', $query_file_name);
	file_put_contents($query_file_path, fopen($url, 'r'));
	echo "Stored in: " . $query_file_path;
	//$complete_note = $query_file_path . ".txt";
	//$note_file = fopen($complete_note, "w");
	//fclose($note_file);

	//$res_file = sprintf('upload/%s', $query_file_name);
	// waiting for result file
	/*while (True)
		{
	if (file_exists ( $res_file ))
		break;
	}
	*/
	//$score_random = rand(1, 100);
	//header("Location: index.php?file=" . $res_file);
}
else
{
echo "Upload: " . $_FILES["file"]["name"] . "<br>";
echo "Type: " . $_FILES["file"]["type"] . "<br>";
echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";
$path = $_FILES["file"]["name"];
$ext = pathinfo($path);
//$query_file_name = $_POST["x1"]. "_" . $_POST["y1"]. "_" . $_POST["x2"]. "_" . $_POST["y2"]. "_". md5($path) . "." . $ext['extension'];
$query_file_name = $_FILES["file"]["name"];
$query_file_path = sprintf('upload/%s', $query_file_name);
// or upload from local computer
move_uploaded_file($_FILES["file"]["tmp_name"], $query_file_path);
echo "Stored in: " . $query_file_path;
}
printf("<br>");
printf("<br>");
printf("<br>");

$szDestKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szPatName);
//$szStartTime = date("m.d.Y_H.i.s");
//$strtmp = sprintf("%s",$szStartTime);

$szTmpUploadedKeyFrameDir = tempnam($szDestKeyFrameDir, "NTDV");
$szUploadedKeyFrameDir = sprintf("%s/%s-x", $szDestKeyFrameDir, basename($szTmpUploadedKeyFrameDir));
makeDir($szUploadedKeyFrameDir);
$szCmdLine = sprintf("chmod 777 -R %s", $szUploadedKeyFrameDir);
system($szCmdLine);

$szWebTestVideoID = basename($szUploadedKeyFrameDir); // de lay ra NTDV_xxx

//exit;

// moi anh upload --> sinh ra VideoID tuong ung, chep vao thu muc VideoID do
// copy uploaded image to the dest dir: keyframe-5/web-test/NTDV-xxx

//$szFPSrcFN = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/upload"; // day la duong dan den anh ma nguoi dung da upload len server
$szCmdLine = sprintf("cp -r %s/. %s", $szFPSrcFN, $szUploadedKeyFrameDir);
printf("%s\n <br>",$szCmdLine);

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
//$arrTempKF = collectFilesInOneDir($szUploadedKeyFrameDir);

$arTemp = array();
//$arTemp[] = sprintf("%s", basename($szFPSrcFN, ".jpg")); // ko lay .jpg (mac dinh anh upload la .jpg)
$arTemp = collectFilesInOneDir($szUploadedKeyFrameDir);
saveDataFromMem2File($arTemp, $szFPVideoProgFN);
$szCmdLine = sprintf("chmod 777 %s", $szFPVideoProgFN);
system($szCmdLine);

// them file .prg nay vao trong web-test.lst
$szFPWebTestFN = sprintf("%s/%s.lst", $szRootMetaDataDir, $szPatName);
//if(!file_exists($szFPWebTestFN))
//{
	// tao ra file nay va them file .prg vao
	$arTemp = array();
	$arTemp[] = sprintf("%s", basename($szFPVideoProgFN, ".prg")); // ko lay .prg
	saveDataFromMem2File($arTemp, $szFPWebTestFN);
	$szCmdLine = sprintf("chmod 777 %s", $szFPWebTestFN);
	system($szCmdLine);
	$nInFilePos = 0; // first position
//}
/* else // neu tap tin da ton tai thi se them vao cuoi
{
	$nInFilePos = loadListFile($arTemp, $szFPWebTestFN); // last position
	$arTemp[$nInFilePos] = sprintf("%s", basename($szFPVideoProgFN, ".prg"));
	saveDataFromMem2File($arTemp, $szFPWebTestFN);
	$szCmdLine = sprintf("chmod 777 %s", $szFPWebTestFN);
	system($szCmdLine);	
}
 */
// InFilePos --> dung de dinh vi VideoID trong file web-test.prg

// Feature Dir
$szDestFeatureDir = sprintf("%s/%s/%s", $szRootFeatureDir, $szFeatureExt, $szPatName);
makeDir($szDestFeatureDir);
$szCmdLine = sprintf("chmod 777 -R %s", $szDestFeatureDir);
system($szCmdLine);

$szCmdLine = sprintf("php -f ksc-Feature-ExtractBaselineFeature.php %s", $szParam);
execSysCmd($szCmdLine);
$szCmdLine = sprintf("chmod 777 -R %s", $szDestFeatureDir);
system($szCmdLine);

?>