<?php
/*
 * Lam Quang Vu
 * 30 Aug 2014
 * Create annotation for 1 file prg - 1 concept - shot annotation
 * Note: run PHP from : /net/per900cb/raid0/ledduy/usr.local/bin/php
 * Update 30/8 2014: tao annotation lai dua vao shot boundary 
*/
require_once "ksc-AppConfig.php";

$numPar = 8;
if($argc!=$numPar)
{
	printf("Number of params [%s] is incorrect [%d]\n", $argc,$numPar);
	printf("Usage %s <RootOutputDir> <ProjectName> <ConceptList> <OldVideoList> <NewVideoList> <ProjectKeyframename> <OverlapRate>\n", $argv[0]);
	exit();
}
//$szProjectExpName = "keyframe-5";
$sztmpRootOutput = $argv[1];
$nNumKFperShot = 5;
$szProjectName = $argv[2];

$szConceptList = $argv[3];
$szPartition = $argv[4];
$szNewPartition = $argv[5];
$szProjectExpName = $argv[6];

$fOverlapRate = floatval($argv[7]); // So luong keyframe nam trong pos segment


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

function WirteErrorLog($szStingErr)
{
	$szLogErr = sprintf("/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/temp/lqvuLog/LogAll-Errors.csv");
	$flogErr = fopen($szLogErr,"a");
	$today = sprintf("%s",date("Y-m-d_H-i-s"));
	$strtmp = sprintf("Time: %s\n",$today);
	fwrite($flogErr,$strtmp);
	$strtmp = sprintf("Code: %s\n",$argv[0]);
	fwrite($flogErr,$strtmp);
	$strtmp = sprintf("Error: %s\n",$szStingErr);
	fclose($flogErr);
	
}
function CheckOverlab($x1,$x2,$y1,$y2,$fOverload=1)
{
	//printf("%d,%d,%d,%d,%.1f",$x1,$x2,$y1,$y2,$fOverload);
	if ($x1>$x2)
	{
		$tmp = $x1;
		$x1 = $x2;
		$x2 = $tmp;
	}
	if ($y1>$y2)
	{
		$tmp = $y1;
		$y1 = $y2;
		$y2 = $tmp;
	}
	if ((($x2-$x1)+($y2-$y1)) - (max($x2,$y2)-min($x1,$y1)) >= $fOverload)
	{
		//printf("--True\n");
		return 1;
	}
	else
	{
		//printf("--Flase\n");
		return 0;
	}
}

$szOldVideoList = sprintf("%s/metadata/%s/%s.lst",$sztmpRootOutput,$szProjectExpName,$szPartition);
$szNewVideoList = sprintf("%s/metadata/%s/%s.lst",$sztmpRootOutput,$szProjectExpName,$szNewPartition);

printf("%s/n",$szConceptList);
printf("%s/n",$szOldVideoList);
printf("%s/n",$szNewVideoList);

$szLOGDir = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/temp/lqvuLog/CreateAnnotation";
if (!file_exists($szLOGDir))
{
	makeDir($szLOGDir);
}

$szLogAll = sprintf("%s/LogAll-%s.csv",$szLOGDir,$szProjectName);
$flogAll = fopen($szLogAll,"a");


$szRootOutput = sprintf("%s/annotation/%s/%s",$sztmpRootOutput,$szProjectExpName,$szProjectName);
if (!file_exists($szRootOutput))
{
	makeDir($szRootOutput);
}

//ghi log file
$szRootLogDir = sprintf("%s/annotation/%s",$sztmpRootOutput,$szProjectExpName);

$szScriptName = CutExt(CutPath($argv[0]));
printf("%s",$szScriptName);
$today = sprintf("%s",date("Y-m-d_H-i-s"));
// Tham so chon gan vao ten file log
$szSetOut = CutExt(CutPath($argv[4]));
// Cap nhat ten cua log file
$szLogname = sprintf("%s/%s.%s.log",$szRootLogDir,$szScriptName,$szProjectName);
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


$nConcept = loadListFile($arrConcept,$szConceptList);
$nOldVideo = loadListFile($arrOldVideo,$szOldVideoList);
$nNewVideo = loadListFile($arrNewVideo,$szNewVideoList);

$szFNConcept = sprintf("%s/%s.Concepts.lst",$szRootOutput,$szProjectName);
$fconceptlst = fopen($szFNConcept,"w");

$nTotalKFAllVideo = 0;

$arrtmpAllShot = array();

$arrAllKF = array();
$arrAnnInfoNewVideo = array();
sort($arrNewVideo);
foreach($arrNewVideo as $newVideo)
{
	//VSD11_15_01 #$# VSD11_15_01 #$# test11-new
	$strtmp = explode("#$#",$newVideo);
	$szNewVideoID = trim($strtmp[0]);
	$szPRGZFN= sprintf("%s/metadata/%s/%s/%s.prgz",$sztmpRootOutput,$szProjectExpName,$szNewPartition,$szNewVideoID);
	
	$nKF = loadListFile($arrKeyframe,$szPRGZFN);
	
	$strout = sprintf("%s,%d\n",$szNewVideoID,$nKF);
	//fwrite($flogAll,$strout);
	printf("%s",$strout);
	
	foreach ($arrKeyframe as $line)
	{
		//VSD11_1 #$# VSD11_1.shot1_1 #$# VSD11_1.shot1_1.RKF_1.Frame_4 #$# 720 #$# 576
		$strtmp = explode("#$#",$line);
		$strOrgVideoID = trim($strtmp[0]);
		$strOrgShotID = trim($strtmp[1]);
		$strOrgKFID = trim($strtmp[2]);
		
		$strtmpKFID = explode("Frame_",$strOrgKFID);
		$keyframeID = (int)trim($strtmpKFID[1]);
		
		$arrAllKF[$nTotalKFAllVideo]['VideoID'] = $strOrgVideoID;
		$arrAllKF[$nTotalKFAllVideo]['NewVideoID'] = $szNewVideoID;
		$arrAllKF[$nTotalKFAllVideo]['ShotID'] = $strOrgShotID;
		$arrAllKF[$nTotalKFAllVideo]['KeyframeID'] = $strOrgKFID;
		$arrAllKF[$nTotalKFAllVideo]['KFID'] = $keyframeID;
		$arrtmpAllShot[$nTotalKFAllVideo]=$strOrgShotID;
		
		$arrAnnInfoNewVideo[$strOrgShotID] = $szNewVideoID;
		$nTotalKFAllVideo++;
	}
}



// load sb files
$arSBShots = array();
$arSBShotInfoVideo = array();
$nCountSBShots = 0;
foreach ($arrOldVideo as $oldVideoLine)
{
	//VSD13_1 #$# movie-Armageddon-1998-dvd2002-MediaEval #$# devel2013
	$strtmp = explode("#$#",$oldVideoLine);
	$szOldVideoID = trim($strtmp[0]);
	$szFNSB = sprintf("%s/sbinfo/%s/%s/%s.sb",$sztmpRootOutput,$szProjectExpName,$szPartition,$szOldVideoID);
	$nCountShotSB = loadListFile($arrSBShots, $szFNSB);
	$szSBVideoID = trim($arrSBShots[1]);
	for ($xi =2; $xi <$nCountShotSB;$xi++)
	{
		$strtmpsb = explode("#$#",$arrSBShots[$xi]);
		$tmpSBShotID = trim($strtmpsb[0]);
		$tmpSBStart = intval($strtmpsb[1]);
		$tmpSBDuration = intval($strtmpsb[2]);
		$tmpSBEnd = $tmpSBStart + $tmpSBDuration -1;
		$arSBShots[$szSBVideoID][$tmpSBShotID]['Start']=$tmpSBStart;
		$arSBShots[$szSBVideoID][$tmpSBShotID]['End']=$tmpSBEnd;
		$arSBShotInfoVideo[$tmpSBShotID] = $szSBVideoID;
		$nCountSBShots++;
	}
}
$result = array_unique($arrtmpAllShot);
//print_r($result);
$nCountShot = count($result);
sort($result);
printf("Total shot from NewList = %d\n",$nCountShot);
printf("Total KF from NewList = %d\n",$nTotalKFAllVideo);

printf("Total shot from SBList = %d\n",$nCountSBShots);

$arrShotScore = array();

$arRootVideo = array();
$arrAnn = array();

for ($i=0;$i<$nConcept;$i++)
{
	// ghi danh sach concept vao list
	$strtmp = sprintf("%d #$# %s #$# %s\n",$i,$arrConcept[$i],$arrConcept[$i]);
	fwrite($fconceptlst,$strtmp);
	printf("%s",$strtmp);
	$szConcept = $arrConcept[$i];
	for ($j=0;$j<$nOldVideo;$j++)
	{
		$line = $arrOldVideo[$j];
		//VSD11_1 #$# movie-Armageddon-1998-dvd2002-MediaEval #$# dev11
		$strtmp = explode("#$#",$line);
		$szVideoID  = trim($strtmp[0]);
		$szVideoName  = trim($strtmp[1]);
		$szVideoPartition  = trim($strtmp[2]);
		$arRootVideo[$szVideoID]['Name']=$szVideoName;
		$arRootVideo[$szVideoID]['Partition']=$szVideoPartition;
	
		$szFNGT = sprintf("%s/groundtruth/%s/%s/%s-%s.groundtruth.txt",$sztmpRootOutput,$arRootVideo[$szVideoID]['Partition'],$arRootVideo[$szVideoID]['Name'],$arRootVideo[$szVideoID]['Name'],$szConcept);
		//printf("%d.%s\n",$j,$arRootVideo[$szVideoID]['Name']);
		printf("%d.%s\n",$j,$szFNGT);
		
		if (!file_exists($szFNGT))
		{
			$strtmp = sprintf("Khong ton tai file %s\n",$szFNGT);
			WriteErrorLog($strtmp);
			exit;
		}
		//loadListFile($arrPosSeg,$szFNGT);
		
		
		$countPosSeg = 0;
		// Load groundtruth file to Array
		$nPosSeg = loadListFile($arrPosSeg,$szFNGT);
		$arRootVideo[$szVideoID][$szConcept]['NumSeg']= $nPosSeg;
		foreach($arrPosSeg as $line)
		{
		
			if (strlen(trim($line))>0)
			{
				$tmp = sscanf($line, "%d %d %s",$szStart,$szEnd,$szTmp);
				//printf("Seg%03d - %d - %d\n",$countPosSeg,$szStart[$countPosSeg],$szEnd[$countPosSeg]);
				//$strtmp = sprintf("Seg%03d - %d - %d\n",$countPosSeg,$szStart[$countPosSeg],$szEnd[$countPosSeg]);
				//fwrite($flog,$strtmp);
				$strtmp = sprintf("%s - %s - Seg %d - %d - %d\n",$szVideoID,$szConcept,$countPosSeg,$szStart,$szEnd);
				//printf("%s",$strtmp);
				//fwrite($flogAll,$strtmp);
				
				$arRootVideo[$szVideoID][$szConcept][$countPosSeg]['Start'] = $szStart;
				$arRootVideo[$szVideoID][$szConcept][$countPosSeg]['End'] = $szEnd;
				
				$strtmp = sprintf("Checklai %s - %s - Seg %d - %d - %d\n",$szVideoID,$szConcept,$countPosSeg,$arRootVideo[$szVideoID][$szConcept][$countPosSeg]['Start'],$arRootVideo[$szVideoID][$szConcept][$countPosSeg]['End']);
				//printf("%s",$strtmp);
				//fwrite($flogAll,$strtmp);
				
				$countPosSeg = $countPosSeg + 1;
			}
		}
	}
	$szPosKFAnn = sprintf("%s/%s.pos.ann",$szRootOutput,$szConcept);
	$szNegKFAnn = sprintf("%s/%s.neg.ann",$szRootOutput,$szConcept);
	$fPosKF = fopen($szPosKFAnn,"a+");
	$fNegKF = fopen($szNegKFAnn,"a+");
	$szLogDir = sprintf("%s/%s",$szLOGDir,$szProjectName);
	if (!file_exists($szLogDir))
	{
		makeDir($szLogDir);
	}
	$szLogFN = sprintf("%s/%s.csv",$szLogDir,$szConcept);
	$flogforconcept = fopen($szLogFN,"a");
	
	$countKFPos = 0;
	$countKFNeg = 0;
	
	
	foreach ($result as $line)
	{
		$arrShotScore[$line] = 0;
	}
// chinh lai khuc nay
// gan ket qua cho arrShotScore = 1 or 0
// load SB file, check khoang cach
	///net/per610a/export/das11f/ledduy/mediaeval-vsd-2013/SBfiles/devel2013
	
	foreach ($result as $CheckShotID)
	{
		//VSD13_1 #$# movie-Armageddon-1998-dvd2002-MediaEval #$# devel2013
		$tmpVideoID = $arSBShotInfoVideo[$CheckShotID];
		$nPosSegFilm = $arRootVideo[$tmpVideoID][$szConcept]['NumSeg'];
		$nCheck = 0;
		
		$arrAnn[$CheckShotID]['NewVideoID']= $tmpNewVideoID;
		
		for ($ii=0;$ii<$nPosSegFilm;$ii++)
		{
		// Pos keyframe
		
			$tmpCheckShot_Start= $arSBShots[$tmpVideoID][$CheckShotID]['Start'];
			$tmpCheckShot_End= $arSBShots[$tmpVideoID][$CheckShotID]['End'];
			$tmpCheckShot_Duration = $tmpCheckShot_End - $tmpCheckShot_Start + 1 ;
			
									
			$tmpStart = $arRootVideo[$tmpVideoID][$szConcept][$ii]['Start'];
			$tmpEnd = $arRootVideo[$tmpVideoID][$szConcept][$ii]['End'];
			$tmpDuration = $arRootVideo[$tmpVideoID][$szConcept][$ii]['End'] - $arRootVideo[$tmpVideoID][$szConcept][$ii]['Start'] +1; 
			
			if ($tmpDuration > $tmpCheckShot_Duration) // groundth lon hon shot can xet
				$tmpOverlap = $fOverlapRate*$tmpCheckShot_Duration;
			else 
				$tmpOverlap = $fOverlapRate*$tmpDuration;
			
			//printf("VideoID = %s --- CheckshotID = %s -- %d -- %d with Segment %d-%d overlap %d",$tmpVideoID,$CheckShotID,$tmpCheckShot_Start,$tmpCheckShot_End, $tmpStart,$tmpEnd,$tmpOverlap);
			if (CheckOverlab($tmpCheckShot_Start,$tmpCheckShot_End, $tmpStart,$tmpEnd,$tmpOverlap))
			{
					$nCheck = 1;
					$arrShotScore[$CheckShotID]=1;
					break;
			}
			//printf("-- Result %d\n",$nCheck);
		}
	}
/* 	for ($t=0;$t<$nTotalKFAllVideo;$t++)
	{
		//$strtmp = sprintf("%d #$# %s #$# %s #$# %s #$# %d\n",$k,$arrAllKF[$k]['VideoID'],$arrAllKF[$k]['ShotID'],$arrAllKF[$k]['KeyframeID'],$arrAllKF[$k]['KFID']);
		//printf("%s",$strtmp);
		//fwrite($flogAll,$strtmp);
		
		$tmpVideoID = $arrAllKF[$t]['VideoID'];
		$tmpNewVideoID = $arrAllKF[$t]['NewVideoID'];
		$tmpShotID =  $arrAllKF[$t]['ShotID'];
		$tmpKeyframeIDfull = $arrAllKF[$t]['KeyframeID'];
		$tmpkeyframeID = $arrAllKF[$t]['KFID'];
		
		$nPosSegFilm = $arRootVideo[$tmpVideoID][$szConcept]['NumSeg'];
		
		$nCheck = 0;
		
		$arrAnn[$tmpShotID]['NewVideoID']= $tmpNewVideoID;
		
		for ($ii=0;$ii<$nPosSegFilm;$ii++)
		{
			// Pos keyframe
			$tmpStart = $arRootVideo[$tmpVideoID][$szConcept][$ii]['Start'];
			$tmpEnd = $arRootVideo[$tmpVideoID][$szConcept][$ii]['End'];
			
			if (($tmpkeyframeID >= $tmpStart) and ($tmpkeyframeID <= $tmpEnd))
			{	
				$nCheck = 1;
				$arrShotScore[$tmpShotID]++;
				break;
			}
		}
		
	} */
	
	
	
// ket thuc chinh lai khuc nay
	$sumPosKF = 0;
	foreach ($result as $line)
	{
		$sumPosKF += $arrShotScore[$line];
		//VSD11_10_05 #$# VSD11_10.shot10_1000 #$# VSD11_10.shot10_1000 #$# pos
		// trong file sb VSD13_2.shot2_2 #$# 36 #$# 165
		
		if ($arrShotScore[$line] == 1)
		{
			$strtmp = sprintf("%s #$# %s #$# %s #$# pos\n",$arrAnnInfoNewVideo[$line],$line,$line);
			printf("%s",$strtmp);
			fwrite($fPosKF,$strtmp);
			
			$strtmp = sprintf("%s,%s,%s,pos,%d\n",$arrAnnInfoNewVideo[$line],$line,$line,$arrShotScore[$line]);
			fwrite($flogforconcept,$strtmp);
			$countKFPos +=1;
		} else 
		{
			$strtmp = sprintf("%s #$# %s #$# %s #$# neg\n",$arrAnnInfoNewVideo[$line],$line,$line);
			printf("%s",$strtmp);
			fwrite($fNegKF,$strtmp);
			
			$strtmp = sprintf("%s,%s,%s,pos,%d\n",$arrAnnInfoNewVideo[$line],$line,$line,$arrShotScore[$line]);
			fwrite($flogforconcept,$strtmp);
			$countKFNeg +=1;
		}
		
	}
	
	$strtmp = sprintf("%s,%d,%d,%d,%d\n",$szConcept,$nCountShot,$countKFNeg+$countKFPos,$countKFPos,$countKFNeg);
	fwrite($flogAll,$strtmp);
	
	fclose($fPosKF);
	fclose($fNegKF);
	fclose($flogforconcept);

}


fclose($flogAll);
fclose($fconceptlst);
exit;

?>
