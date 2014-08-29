<?php
/*
 * Lam Quang Vu
 * 29 Aug 2014
 * Tach ra thanh nhieu subset, moi set la 1250 KF (1250 la tham so)
 * Note: run PHP from : /net/per900b/raid0/ledduy/usr.local/bin/php 
*/

require_once "ksc-AppConfig.php";

$numPar = 8;
if($argc!=$numPar)
{
	printf("Number of params [%s] is incorrect [%d]\n", $argc,$numPar);
	//printf("Usage %s  <FilmName> <PathtoKF> <PathtoPRGfile> <SetGroup> <NumberKFperfolder>\n", $argv[0]);
	printf("Usage %s  <VideoID> <RootDir> <RootOut> <Partition> <NewPartition> <NumberKFperfolder> <ProjectName>\n", $argv[0]);
	exit();
}

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

$szFilmName = $argv[1]; // VideoID
$szRootDir = trim($argv[2]); 
$szRootOutDir = trim($argv[3]);
$szPartition = trim($argv[4]); //dev11
$szNewPartition = trim($argv[5]); //dev11-new
$szNumberKFperFolder = (int)$argv[6]; // Number of Keyframes in one folder ~ 1000
$szPrjName = trim($argv[7]); //keyframe-5

$szRootKF = sprintf("%s/%s/%s",$szRootDir,$szPrjName,$szPartition);
$szPRGFolder = sprintf("%s/metadata/%s/%s",$szRootDir,$szPrjName,$szPartition); 


//$szGroupSet = "dev11";
$szGroupSet = $szNewPartition;
// config rootdir before running

//$szRootDir = "/net/sfv215/export/raid4/ledduy/lqvu-Experiments/2012/ViolentSenceDetection/keyframes-vsd12-5KFpS";

// for test 1 film
//$szRootDir = "/net/sfv215/export/raid4/ledduy/lqvu-Experiments/lqvu-MediaEval/OUTPUT/ParseToSubTest12";


//Root Dir to parse

$szTmpKF = sprintf("%s/%s",$szRootOutDir,$szPrjName);
if (!file_exists($szTmpKF))
{
	makeDir($szTmpKF);
}

//ghi log file
$szRootLogDir = $szTmpKF;

$szScriptName = CutExt(CutPath($argv[0]));
printf("%s",$szScriptName);
$today = sprintf("%s",date("Y-m-d_H-i-s"));
// Tham so chon gan vao ten file log
$szSetOut = CutExt(CutPath($argv[4]));

$szLogname = sprintf("%s/%s.%s.log",$szRootLogDir,$szScriptName,$szSetOut);
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


$szTmpKF = sprintf("%s/%s/%s",$szRootOutDir,$szPrjName,$szGroupSet);
if (!file_exists($szTmpKF))
{
	makeDir($szTmpKF);
}

$szOutPutPRGfiles = sprintf("%s/metadata",$szRootOutDir);
if (!file_exists($szOutPutPRGfiles))
{
	makeDir($szOutPutPRGfiles);
}


$szOutPutPRGfilesMetaKF = sprintf("%s/metadata/%s",$szRootOutDir,$szPrjName);
if (!file_exists($szOutPutPRGfilesMetaKF))
{
	makeDir($szOutPutPRGfilesMetaKF);
}


$szOutPutPRGfilesMetaKFPRG = sprintf("%s/metadata/%s/%s",$szRootOutDir,$szPrjName,$szGroupSet);
if (!file_exists($szOutPutPRGfilesMetaKFPRG))
{
	makeDir($szOutPutPRGfilesMetaKFPRG);
}

$szOutputKFRoot = sprintf("%s/%s/%s",$szRootOutDir,$szPrjName,$szGroupSet);
$szOutputPRGRoot = sprintf("%s/metadata/%s/%s",$szRootOutDir,$szPrjName,$szGroupSet);
$szOutputListfileRoot = sprintf("%s/metadata/%s",$szRootOutDir,$szPrjName);

printf("Keyframe - %s\n",$szOutputKFRoot);
printf("PRG - %s\n",$szOutputPRGRoot);
printf("List file - %s\n",$szOutputListfileRoot);

$szRootKFFolder = sprintf("%s/%s",$szRootKF,$szFilmName);
$szFNPRGfile = sprintf("%s/%s.prg",$szPRGFolder,$szFilmName);
$szFNPRGZfile = sprintf("%s/%s.prgz",$szPRGFolder,$szFilmName);
$szFNPRGXfile = sprintf("%s/%s.prgx",$szPRGFolder,$szFilmName);

$szLogDir = sprintf("%s/LOG/ParseToSubDirLogs",$szRootLogDir);
if (!file_exists($szLogDir))
{
	makeDir($szLogDir);
}

$szPrgExtension = ".prg";
$szPrgNewExtension = ".prgz";
$szPrgXExtension = ".prgx";
$szLogExtension = ".csv";


// File list of keyframes
//$szPRFFileName = sprintf("%s/%s%s",$szOutputPRGRoot,$szFileName,$szPrgExtension);
$szLogParseFN = sprintf("%s/%s_%s%s",$szLogDir,$szFilmName,$szPrjName,$szLogExtension);
$flog = fopen($szLogParseFN,"w");

$szLogParseFNAll = sprintf("%s/LogParseAllFilms_%s_%s",$szLogDir,$szPrjName,$szLogExtension);
$flogALL = fopen($szLogParseFNAll,"a");

$szListfilename = sprintf("%s/%s.lst",$szOutputListfileRoot,$szGroupSet);
$flistfile = fopen($szListfilename,"a");

$szSurveyFN = sprintf("%s/%s/%s.keyframe.csv",$szRootOutDir,$szPrjName,$szGroupSet);
$fsurvey = fopen($szSurveyFN,"a");
//Ghi dev11.lst o day
/*
$strTMP = explode("-",$szGroupSet);
$szListFNAll = sprintf("%s/%s.lst",$szOutputListfileRoot,$strTMP[0]);
$flistfileAll = fopen($szListFNAll,"a");
$strtmp = sprintf("%s #$# %s #$# %s\n",$szFilmName,$szFilmName,$strTMP[0]);
fwrite($flistfileAll,$strtmp);
fclose($flistfileAll);
*/

$nTotalKF = loadListFile($arrKeyframe, $szFNPRGfile);
$nTotalKFinPRGz = loadListFile($arrKeyframeinPRGZ,$szFNPRGZfile);
$nTotalKFinPRGx = loadListFile($arrKeyframeinPRGX,$szFNPRGXfile);

$nLoop = ceil($nTotalKF/$szNumberKFperFolder);
printf("total %d - folder %d\n",$nTotalKF,$nLoop);

$nSubFolder = 1;
$nKFperPart = 0;
$nOldFolder = 0;

for ($i=0;$i<$nTotalKF;$i++)
{
	// Create folder to store keyframe
		
	if ($nOldFolder <> $nSubFolder)
	{
		$szSubKFFolderName = sprintf("%s/%s_%03d",$szOutputKFRoot,$szFilmName,$nSubFolder);
		makeDir($szSubKFFolderName);
		
		//printf("%s\n",$szSubKFFolderName);
		// Write to lst file
		$strtmp = sprintf("%s_%03d #$# %s_%03d #$# %s\n",$szFilmName,$nSubFolder,$szFilmName,$nSubFolder,$szGroupSet);
		fwrite($flistfile,$strtmp);
	
		$strPRGfile = sprintf("%s/%s_%03d.prg",$szOutputPRGRoot,$szFilmName,$nSubFolder);
		$fprg = fopen($strPRGfile,"w");
		
		$strPRGZfile = sprintf("%s/%s_%03d.prgz",$szOutputPRGRoot,$szFilmName,$nSubFolder);
		$fprgz = fopen($strPRGZfile,"w");
		
		$strPRGXfile = sprintf("%s/%s_%03d.prgx",$szOutputPRGRoot,$szFilmName,$nSubFolder);
		$fprgx = fopen($strPRGXfile,"w");
		
		$nOldFolder++;
	}
	
	// Copy keyframes
	
		//Write to PRG file // Chinh them o day
	
		fwrite($fprg,$arrKeyframe[$i]);
		fwrite($fprg,"\n");
		
		fwrite($fprgz,$arrKeyframeinPRGZ[$i]);
		fwrite($fprgz,"\n");
		
		fwrite($fprgx,$arrKeyframeinPRGX[$i]);
		fwrite($fprgx,"\n");
		
		$strcmd = sprintf("cp %s/%s.jpg %s",$szRootKFFolder,trim($arrKeyframe[$i]),$szSubKFFolderName);
		execSysCmd($strcmd);
	//}
		//	VSD13_1 #$# VSD13_1.shot1_1 #$# VSD13_1.shot1_1.RKF_1.Frame_1 #$# 720 #$# 576
		$szLine = explode("#$#",$arrKeyframeinPRGZ[$i]);
		$szCurrShotID = trim($szLine[1]);
		if (($i+1) == $nTotalKF)
			$szNextShotID = "Stop";
		else {
			$szNextLine = explode("#$#",$arrKeyframeinPRGZ[$i+1]);
			$szNextShotID = trim($szNextLine[1]);
		}
		
		if (($szNextShotID <> $szCurrShotID) && ($nKFperPart > $szNumberKFperFolder))
		{
			$nSubFolder++;
			$nKFforPart = $nKFperPart; 
			$nKFperPart = 0;
		} 
		else {
			$nKFperPart++;
		}
		
	if ($nOldFolder <> $nSubFolder)
	{	
		fclose($fprg);
		fclose($fprgz);
		fclose($fprgx);
	
		// Use -C and . for excluding the path 
		$szFPTarFN = sprintf("%s/%s_%03d/%s_%03d.tar",$szOutputKFRoot,$szFilmName,$nSubFolder-1,$szFilmName,$nSubFolder-1);
		$szFPSrcDir = $szSubKFFolderName;
		$szCmdLine = sprintf("tar -cvf %s -C %s .", $szFPTarFN, $szFPSrcDir);
		printf("%s\n",$szCmdLine);
		//fwrite($flog,$szCmdLine);
		//fwrite($flog,"\n");
		execSysCmd($szCmdLine);
	
		//$szFPTarFN --> full path to output file (.tar), eg /net/sfvzzz/.../movie-A1.tar
		//$szFPSrcDir --> full path to dir containing keyframes, /net/sfvzzz/.../movie-A1
		
		// ghi so luong tung part ra file log, xu ly sau
		$strtmp = sprintf("%s_%03d,%d\n",$szFilmName,$nSubFolder-1,$nKFforPart+1);
		fwrite($flog,$strtmp);
		fwrite($flogALL,$strtmp);
			
	}
}

$strtmp = sprintf("%s_%03d,%d\n",$szFilmName,$nSubFolder-1,$nKFforPart+1);
fwrite($flog,$strtmp);
fwrite($flogALL,$strtmp);

$szFPTarFN = sprintf("%s/%s_%03d/%s_%03d.tar",$szOutputKFRoot,$szFilmName,$nSubFolder,$szFilmName,$nSubFolder);
$szFPSrcDir = $szSubKFFolderName;
$szCmdLine = sprintf("tar -cvf %s -C %s .", $szFPTarFN, $szFPSrcDir);
printf("%s\n",$szCmdLine);
execSysCmd($szCmdLine);

$strtmp = sprintf("%s,%d,%d\n",$szFilmName,$nTotalKF,$nSubFolder);
fwrite($fsurvey,$strtmp);

fclose($flogALL);
fclose($flog);
fclose($flistfile);
fclose($fsurvey);

?>

