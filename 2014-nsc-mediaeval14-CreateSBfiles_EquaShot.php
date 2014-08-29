<?php
// Lam Quang Vu
// 4/8/2014
// Chia phim thanh nhieu shot co do dai bang nhau voi length = N keyframe
// Input
/*
 * List, InFolder, OutFolder, length of shot (#keyframe), project name	
 */

// Output SB file format
/*
movie-Fargo-1996-dvd2004-MediaEval
VSD13_4
VSD13_4.shot4_1 #$# 0 #$# 17
 */
$numPar = 6;
if($argc!=$numPar)
{
	printf("Number of params [%s] is incorrect [%d]\n", $argc,$numPar);
	printf("Usage %s <ListFile> <InVideoFolder> <OutFolder> <N_Length> <Prj_Name>", $argv[0]);
	exit();
}

require_once "ksc-AppConfig.php";
//require_once "nsc-TRECVIDTools.php";



ini_set('max_execution_time', 0);
$extension = "ffmpeg";
$extension_soname = $extension . "." . PHP_SHLIB_SUFFIX;
$extension_fullname = PHP_EXTENSION_DIR . "/" . $extension_soname;
if (!extension_loaded($extension)) {
	dl($extension_soname) or die("Can't load extension $extension_fullname\n");
}

$szListFile = $argv[1];
$szInDir = $argv[2];
$szOutDir = $argv[3];
$nLength = $argv[4];
$szPrjName = $argv[5];





///net/per610a/export/das11f/ledduy/mediaeval-vsd-2013/SBfiles/keyframe-5/devel2013



$szFileSBDir = sprintf("%s/%s",$szOutDir,$szPrjName);
if (!file_exists($szFileSBDir))
{
	makeDir($szFileSBDir);
}



// ghi chu lai lan chay cua file nay
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
//"/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/bin-vsd2014";
$szLogDir = $szFileSBDir;

$szScriptName = CutExt(CutPath($argv[0]));
printf("%s",$szScriptName);
$today = sprintf("%s",date("Y-m-d_H-i-s"));
// Tham so chon gan vao ten file log
$szSetOut = CutExt(CutPath($argv[1]));
$szLogname = sprintf("%s/%s.%s.log",$szLogDir,$szScriptName,$szSetOut);
$fLogCode = fopen($szLogname,"a");
$strtmp = sprintf("Running time: %s\n",$today);
fwrite($fLogCode,$strtmp);
//printf("test1");
for ($i=0;$i<$numPar;$i++)
{
	$strtmp = sprintf("Parameter %d: %s\n",$i,$argv[$i]);
	printf("%s",$strtmp);
	fwrite($fLogCode,$strtmp);
}
fclose($fLogCode);
// ket thuc ghi chu



$nSubFile = loadListFile($arrListFile,$szListFile);

printf("\n%d\n",$nSubFile);
$fileSub = $arrListFile[0];
//VSD14_25 #$# V_FOR_VENDETTA #$# test2014
printf("\n%s\n",$fileSub);
$szTmp = explode("#$#",$fileSub);
$szVideoID = trim($szTmp[0]);
$szVideoName = trim($szTmp[1]);
$szSet = trim($szTmp[2]);

printf("%s\n",$szVideoName);
$szLogFile = sprintf("%s/%s_Video_SB_summary_%d.csv",$szOutDir,$szSetOut,$nLength);
$flog = fopen($szLogFile,"w");

$nAllKF = 0;
$nAllShot = 0;
$nAllVideo = 0;
$fAllduration = 0;
for ($i=0;$i<$nSubFile;$i++)
{
	$fileSub = $arrListFile[$i];
	//VSD14_25 #$# V_FOR_VENDETTA #$# test2014
	
	$szTmp = explode("#$#",$fileSub);
	$szVideoID = trim($szTmp[0]);
	$szVideoName = trim($szTmp[1]);
	$szSet = trim($szTmp[2]);
	
	printf("%s\n",$szVideoName);
	//$szLogFile = sprintf("%s/%s_Video_SB_summary.csv",$szOutDir,$szSet);
	//$flog = fopen($szLogFile,"a");
	
	
	$szFPVideoFN = sprintf("%s/%s/%s.mpg",$szInDir,$szSet,$szVideoName);
	printf("%s\n",$szFPVideoFN);
	$objVideo = new ffmpeg_movie ($szFPVideoFN);
	
	printf("duration = %s seconds\n", $objVideo->getDuration());
	printf("frame count = %s\n", $objVideo->getFrameCount());
	printf("frame rate = %0.3f fps\n", $objVideo->getFrameRate());
	printf("comment = %s\n", $objVideo->getComment());
	printf("title = %s\n", $objVideo->getTitle());
	printf("author = %s\n", $objVideo->getAuthor());
	printf("copyright = %s\n", $objVideo->getCopyright());
	printf("get bit rate = %d\n", $objVideo->getBitRate());
	
	$totalFrame = (int)$objVideo->getFrameCount();
	$fAllduration+= $objVideo->getDuration();
	$nAllKF+=$totalFrame;
	// chia thanh cac shot trong shot boundary
	
	$szFileSBDirSet = sprintf("%s/%s/%s",$szOutDir,$szPrjName,$szSetOut);
	if (!file_exists($szFileSBDirSet))
	{
		makeDir($szFileSBDirSet);
	}
	
	$nAllVideo++;
	
	$szFileSB = sprintf("%s/%s.sb",$szFileSBDirSet,$szVideoID);
	$fSB = fopen($szFileSB,"w");
	
	//8_MILE
	//VSD14_31
	
	$tmp=sprintf("%s\n",$szVideoName);
	fwrite($fSB,$tmp);
	$tmp=sprintf("%s\n",$szVideoID);
	fwrite($fSB,$tmp);
	
	//VSD14_31.shot31_1 #$# 0 #$# 1959
	
	$nShot = 0;
	
	$nLoopShot = intval(ceil($totalFrame/$nLength));
	printf("Totol shot = %d\n",$nLoopShot);
	for ($j=0;$j<$nLoopShot;$j++)
	{
		$startFrame = $j*$nLength;
		$duration = $nLength;
		if ($startFrame + $duration > $totalFrame)
			$duration = $totalFrame - $startFrame;
		
		$sztmp = sprintf("%s.shot%s_%d #$# %s #$# %s \n",$szVideoID,$szShotID,$nShot+1,$startFrame,$duration);
		fwrite($fSB,$sztmp);
		$nShot++;
	}
	$nAllShot+=$nShot;
	fclose($fSB);
	// ket thuc chia thanh cac shot trong shot boundary
	
	$str = sprintf("%s,%s,%f,%d,%d\n",$szVideoID,$szVideoName,$objVideo->getDuration(),$totalFrame,$nShot,$nLoopShot);
	fwrite($flog,$str);
	
}

$str = sprintf("All video, all shot,%f,%d,%d,%d\n",$fAllduration,$nAllKF,$nAllShot,$nAllVideo);
fwrite($flog,$str);

fclose($flog);
?>
