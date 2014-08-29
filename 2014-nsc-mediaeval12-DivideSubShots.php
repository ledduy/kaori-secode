<?php
/*
 * Lam Quang Vu
 * 20 08 2014
 * Divide movie to shots with length = N frame
 * Note: run PHP from : /net/per900c/raid0/ledduy/usr.local/bin/php 
 * Code nay chia cac movie thanh cac shot deu nhau voi length fix =  N frame
*/
require_once "nsc-AppConfig.php";


if($argc!=5)
{
	printf("Number of params [%s] is incorrect [5]\n", $argc);
	printf("Usage %s <RootInputDir> <ProjectName> <SET> <ShotLength>\n", $argv[0]);
	exit();
}


$szRootInputDir  = $argv[1];
$szProjectName = $argv[2]; //shot_0180 - ten cua Prj chia subshot
$szSET = $argv[3]; // dev11
$nSubShotLength = intval($argv[4]); // 60
//$szOverlap = floatval($argv[5]); // 0.5 - overlap 50%, 1 - no overlap


$szLogDir = sprintf("./OUTPUT/LOGs");
if (!file_exists($szLogDir))
{
	makeDir($szLogDir);
}
// file dung de ghi lai toan bo cac tap dev11,test11,test12
$szLogAllData = sprintf("%s/LogAll_DivideShubShot_%3d_%.1f.csv",$szLogDir,$nSubShotLength,$szOverlap);
if (!file_exists($szLogAllData))
{	
	$flogAllData = fopen($szLogAllData,"a");
	fwrite($flogAllData,"ProjectName,SET,VideoID,TotalParentShot,TotalSubShot\n");
}
else 
{
	$flogAllData = fopen($szLogAllData,"a");
}

///net/sfv215/export/raid6/ledduy/mediaeval-2013/metadata/shot_0shot_0180180
$szRootMetadata = sprintf("%s/metadata/%s",$szRootInputDir,$szProjectName);

///net/sfv215/export/raid6/ledduy/mediaeval-vsd/SBfiles/shot_0180/dev11
$szRootSBfiles = sprintf("%s/SBfiles/%s/%s",$szRootInputDir,$szProjectName,$szSET);

// tao thu muc luu SB file moi
$szRootSBfilesOutput = sprintf("%s/SBfiles/shot_%03d_%.1f",$szRootInputDir,$nSubShotLength,$szOverlap);
if (!file_exists($szRootSBfilesOutput))
{
	makeDir($szRootSBfilesOutput);
}

$szRootSBfilesOutputDir = sprintf("%s/%s",$szRootSBfilesOutput,$szSET);
if (!file_exists($szRootSBfilesOutputDir))
{
	makeDir($szRootSBfilesOutputDir);
}


// tao thu muc luu lai metadata moi
$szRootMetadataRootOut = sprintf("%s/metadata/shot_%03d_%.1f",$szRootInputDir,$nSubShotLength,$szOverlap);
if (!file_exists($szRootMetadataRootOut))
{
	makeDir($szRootMetadataRootOut);
}

$szNewListFile = sprintf("%s/%s.lst",$szRootMetadataRootOut,$szSET);
$fnewList = fopen($szNewListFile,"w");

$szNewListFileCSV = sprintf("%s/%s.csv",$szRootMetadataRootOut,$szSET);
$fnewListCSV = fopen($szNewListFileCSV,"w");


$szRootMetadataRootOutSET = sprintf("%s/%s",$szRootMetadataRootOut,$szSET);
if (!file_exists($szRootMetadataRootOutSET))
{
	makeDir($szRootMetadataRootOutSET);
}

$szListFile = sprintf("%s/%s.lst",$szRootMetadata,$szSET);
$nFiles = loadListFile($arListFiles,$szListFile);

foreach($arListFiles as $line)
{
	fwrite($fnewList,$line);
	fwrite($fnewList,"\n");
	
	$strtmp = explode ("#$#",$line);
	$szVideoID = trim($strtmp[0]);
	printf("%s\n",$szVideoID);
	
	// Lay SB file cu
	$szSBOldFN = sprintf("%s/%s.sb",$szRootSBfiles,$szVideoID);
	$nShots = loadListFile($arShots,$szSBOldFN);
	
/* 	movie-Armageddon-1998-dvd2002-MediaEval
	VSD11_1
	VSD11_1_shot1_1 #$# 0 #$# 291 */
	//movie-ReservoirDogs-1992-dvd2004-MediaEval
	//VSD11_9
	//VSD11_9_shot9_1.subshot_1 #$# 0 #$# 60
	
	// Tao file SB moi va ghi 2 dong dau vao
	$szSBNewFN = sprintf("%s/%s.sb",$szRootSBfilesOutputDir,$szVideoID);
	$fSbfile = fopen($szSBNewFN,"w");
	$strtmp = sprintf("%s\n",$arShots[0]);
	fwrite($fSbfile,$strtmp);
	$strtmp = sprintf("%s\n",$arShots[1]);
	fwrite($fSbfile,$strtmp);
	//VSD11_2
	$strtmpV = explode("_",$arShots[1]);
	$szVideoNumber = trim($strtmpV[1]); //2
	
	//VSD11_2_shot2
	$szShotTemplate = sprintf("%s_shot%d",trim($arShots[1]),$szVideoNumber);
	
	
	
	//Tao file metadata moi luu cac shot moi
	$szMetaNewFN = sprintf("%s/%s.log",$szRootSBfilesOutputDir,$szVideoID);
	$fMetafile = fopen($szMetaNewFN,"w");
	
	
	// Tao MAP files de luu giua sub shot va parentshot
	$szMapNewFN = sprintf("%s/%s.map",$szRootSBfilesOutputDir,$szVideoID);
	$fMapfile = fopen($szMapNewFN,"w");
	
	
	// Xu ly divide shot lai o day
	$nTotalNewShot = 0;
	
	for ($i=2;$i<$nShots;$i++)
	{
		//VSD11_1_shot1_1 #$# 0 #$# 291
		$strShot = explode("#$#",$arShots[$i]);
		$szShotID = trim($strShot[0]);
		$szShotStart = intval($strShot[1]);
		$szShotduration = intval($strShot[2]);
		//$strtmp = sprintf("%s\n",$arShots[$i]);
		//fwrite($flogAllData,$strtmp);
		
		$szShotEnd = $szShotStart + $szShotduration - 1;
		// Chia cac shot o day
		$nsubshot = 1;
		$nSSStart = $szShotStart;
		while ($nSSStart < $szShotEnd)
		{
			if (($nSSStart + $nSubShotLength )< $szShotEnd)
			{
				// ghi vao SB file
				//VSD11_2.shot2_1.subshot_1 #$# 0 #$# 30
				$nTotalNewShot ++;
				$szNewShotID = sprintf("%s_%d",$szShotTemplate,$nTotalNewShot);
				
				$strtmp = sprintf("%s #$# %d #$# %d\n",$szNewShotID,$nSSStart,$nSubShotLength);
				fwrite($fSbfile,$strtmp);
					
				
				// ghi vao log file, list shot moi
				$strtmp1 = sprintf("%s #$# %s #$# %s #$# %d #$# %d #$# %d #$# %d \n",$szVideoID,$szShotID,$szNewShotID,$szShotStart,$szShotduration,$nSSStart,$nSubShotLength);
				fwrite($fMetafile,$strtmp1);
					
				// ghi lai map cua subshot voi parent shot
				$strtmp = sprintf("%s #$# %s #$# %s\n",$szVideoID,$szShotID,$szNewShotID);
				fwrite($fMapfile,$strtmp);
				
				$nsubshot++;
				
				$nSSStart = $nSSStart + $szOverlap*$nSubShotLength;
				
			} else // truong hop phan con cai cua Shot nho hon ShotLenght, lo qua shot khac 
			{	
				// Phan du ra thi ghi xuong luon
				$nTotalNewShot ++;
				$szNewShotID = sprintf("%s_%d",$szShotTemplate,$nTotalNewShot);
				
				
				$nSubShottmp = $szShotEnd - $nSSStart + 1;
				$strtmp = sprintf("%s #$# %d #$# %d\n",$szNewShotID,$nSSStart,$nSubShottmp);
				fwrite($fSbfile,$strtmp);
				
				// Ghi log lai
				$strtmp = sprintf("%s #$# %s #$# %s #$# %d #$# %d #$# %d #$# %d \n",$szVideoID,$szShotID,$szNewShotID,$szShotStart,$szShotduration,$nSSStart,$nSubShottmp);
				fwrite($fMetafile,$strtmp);
				
				// ghi lai map cua subshot voi parent shot
				$strtmp = sprintf("%s #$# %s #$# %s\n",$szVideoID,$szShotID,$szNewShotID);
				fwrite($fMapfile,$strtmp);;
					
					
				$nSSStart = $nSSStart + $nSubShotLength;
				
				break;
				
			}
		}
		
		// ----ket thuc chia cac shot
	}	
	$strtmp = sprintf("%s,%s,%s,%d,%d\n",$szProjectName,$szSET,$szVideoID,$nShots-2,$nTotalNewShot);
	fwrite($flogAllData,$strtmp);
		
	fclose($fSbfile);
	fclose($fMetafile);
	fclose($fMapfile);
	
	$strtmp= sprintf("%s #$# %d #$# %d\n",$line,$nShots-2,$nTotalNewShot);
	fwrite($fnewListCSV,$strtmp);
}

fclose($fnewList);
fclose($fnewListCSV);
fclose($flogAllData);

?>
