<?php
// Lam Quang Vu
// 9 June 2013
/*
 * Chay post processing tren ranklist, lay top K va down K shot de smoothing score cho cac shot.
 */
function PostProcessing($szETF, $arTopBot, $fPower = 1.2, $nWindowSize = 3) 
{
	$arrETF = LoadETFinfo ( $szETF );
	foreach ( $arrETF as $szVideoName => $arVideoInfo ) {
		$arrKey = array_keys ( $arVideoInfo );
		$nSize = sizeof ( $arrKey );
		// foreach ($arVideoInfo as $szShotID => $arValue)
		$fTolerant = $arTopBot [$szVideoName] ['topT'];
		$fNewTolerant = $arTopBot [$szVideoName] ['topT'] * 0.5;
		
		$arShotScore = array ();
		for($i = 0; $i < $nSize; $i ++) {
			$szShotID = $arrKey [$i];
			$arValue = $arrETF [$szVideoName] [$szShotID];
			$arShotScore [$i] = $arValue ['orgscore'];
		}
		for($i = 0; $i < $nSize; $i ++) {
			printf ( "%d/%d - Processing [%s]\n", $i + 1, $nSize, $szShotID );
			$szShotID = $arrKey [$i];
			$arValue = $arrETF [$szVideoName] [$szShotID];
			$arValue ['newscore'] = $arValue ['orgscore'];
			
			// run score awarding
			$nHalfWS = intval ( $nWindowSize / 2 );
			$nStart = max ( 0, $i - $nHalfWS );
			$nEnd = min ( $nSize, $i + $nHalfWS );
			
			// score awarding
			$nFlag = 1;
			for($k = $nStart; $k < $nEnd; $k ++) {
				// if($arShotScore[$k] <= $arTopBot[$szVideoName]['topT'])
				if ($arShotScore [$k] <= $fNewTolerant) {
					$nFlag = 0;
					break;
				}
			}
			if ($nFlag) {
				
				$arValue ['newscore'] = $arValue ['orgscore'] * floatval ( $fPower );
				for($j = $nEnd; $j < $nSize; $j ++) {
					if ($arShotScore [$j] >= $fTolerant) {
						$arValue ['newscore'] *= 1.1;
					} else {
						break;
					}
				}
			}
			
			// score penalty
			
			if ($arShotScore [$i] >= $fNewTolerant) {
				$nFlag = 1;
				$fPenalty = $arTopBot [$szVideoName] ['bottomT'];
			// xet cho truong hop negative, tang window size value
				$nNewWindowSize = 5;
				$nNewHalfWS = intval ( $nNewWindowSize / 2 );
				$nNewStart = max ( 0, $i - $nNewHalfWS );
				$nNewEnd = min ( $nSize, $i + $nNewHalfWS );
				
				for($k = $nNewStart; $k < $nNewEnd; $k ++) {
					if (($arShotScore [$k] >= $fPenalty) && ($k != $i)) {
						$nFlag = 0;
						break;
					}
				}
				if ($nFlag) {
					
					$arValue ['newscore'] = $arValue ['orgscore'] /$fPower;
					for($j = $nEnd; $j < $nSize; $j ++) {
						if ($arShotScore [$j] < $fPenalty) {
							$arValue ['newscore'] /= 10;
						} else {
							break;
						}
					}
				}
			}
			$arrETF [$szVideoName] [$szShotID] = $arValue;
		}
	}
	return ($arrETF);
}

if ($argc != 9) {
	printf ( "Number of params [%s] is incorrect [8]\n", $argc );
	printf ( "Usage %s <RootInDir> <GTFile> <PrjNameExp> <ProjectKF> <GroqupSet> <Concept Name> <Threshold> <Filter>", $argv [0] );
	exit ();
}
require_once "ksc-AppConfig.php";
// require_once "nsc-TRECVIDTools.php";

/*
 * Dac ta trong file rank 0 0 VSD11_13.shot13_216 1 0.98005 mediaeval-vsd2012-S.nsc.cCV_YCrCb.g6.q3.g_cm.shotmax.ksc.vsd12.R1 Dac ta format se nop <source> 1 <start_time> <duration> event - violence [<score> [<decision>]] /net/sfv215/export/raid6/ledduy/lqvu-mediaeval/experiments/mediaeval-vsd2012-S/results/mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q59.g_lbp.shotmax.ksc.vsd12.R1 /net/sfv215/export/raid6/ledduy/mediaeval-2013/experiments/mediaeval-vsd2012-kf5
 */

$szRootDir = $argv [1];
$szGTFile = $argv [2];
$szPrjName = $argv [3];
$szProjectKF = $argv [4];
$szGroupSet = $argv [5];
$szConceptName = $argv [6];
$szThreshold = floatval ( $argv [7] );
$szRootAtt = $szConceptName; // objviolentscenes.rank
$szFilter = $argv [8];
function check($var) {
	if ($var == 0)
		return true;
	else
		return false;
}
$szouttemp = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/temp/lqvuLog/CheckResults";
$szStartTime = date("m.d.Y_H.i.s");
$strtmp = sprintf("%s_%s",$szouttemp,$szStartTime);
$szouttemp = $strtmp;
$sztmpOutput = sprintf ( "%s/CheckResults-%0.6f-%s", $szouttemp, $szThreshold, $szConceptName );
$sztemplateRun = "me13vsd_NII_";
$szRunExt = "etf";

$szRootLogs = sprintf ( "%s/CheckResults-%0.6f", $szouttemp, $szThreshold );
if (! file_exists ( $szRootLogs )) {
	makeDir ( $szRootLogs );
}
$szLogAll = sprintf ( "%s/LogAll.log", $szRootLogs );
$fLogAll = fopen ( $szLogAll, "w" );

$szRootOutput = sprintf ( "%s/%s-%.4f", $sztmpOutput, $szPrjName, $szThreshold );
if (! file_exists ( $szRootOutput )) {
	makeDir ( $szRootOutput );
}

$szLogAllMAP = sprintf ( "%s/LogAllMapAttibutes.csv", $szouttemp );
$flogAllMAP = fopen ( $szLogAllMAP, "a" );

// Thu muc chinh luu ket qua
/*
 * $szRootOutDir = sprintf("%s/%s",$sztmpOutput,$szFeature); if (!file_exists($szRootOutDir)) { makeDir($szRootOutDir); }
 */
$szFileMap = sprintf ( "%s/%s_%s.csv", $sztmpOutput, $szPrjName, $szConceptName );
$fResult = fopen ( $szFileMap, "a" );
$szStartTime = date ( "m.d.Y - H:i:s" );
$strtmp = sprintf ( "---%s---\n", $szStartTime );
fwrite ( $fResult, $strtmp );
$strW = sprintf ( "Feature,MAP,MAP100\n" );
fwrite ( $fResult, $strW );

$szRootTmp = sprintf ( "%s/%s_tmprank", $sztmpOutput, $szPrjName );
if (! file_exists ( $szRootTmp )) {
	makeDir ( $szRootTmp );
}
$szRootTmpETF = sprintf ( "%s/%s_tmpETF", $sztmpOutput, $szPrjName );
if (! file_exists ( $szRootTmpETF )) {
	makeDir ( $szRootTmpETF );
}
$szRootTmpMAP = sprintf ( "%s/%s_tmpMAP", $sztmpOutput, $szPrjName );
if (! file_exists ( $szRootTmpMAP )) {
	makeDir ( $szRootTmpMAP );
}

if ($szFilter == '@all') {
	$szFilter = '';
}
// /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/result/
// keyframe-5/mediaeval-vsd-2014.devel2013-new.test2013-new/nsc.bow.dense6mul.rgbsift.Soft-1000.devel2011-new.L1norm1x1.shotMAX.R11/objviolentscenes.rank

$szResultDir = sprintf ( "%s/result/%s/%s", $szRootDir, $szProjectKF, $szPrjName );
$arrFeatures = collectDirsInOneDir ( $szResultDir, $szFilter );
// $nFeatures = loadListFile($arrFeatures,$szListFeatures);
sort ( $arrFeatures );
foreach ( $arrFeatures as $szFeature ) {
	$szRootInResult = sprintf ( "%s/result/%s/%s/%s", $szRootDir, $szProjectKF, $szPrjName, $szFeature );
	printf ( "%s\n", $szRootInResult );
	
	$arrRankList = array ();
	
	$szViolentscenes = sprintf ( "%s/%s.rank", $szRootInResult, $szRootAtt );
	printf ( "\nFile size of szViolentscenes = %d \n", filesize ( $szViolentscenes ) );
	if (file_exists ( $szViolentscenes ) && (filesize ( $szViolentscenes ) > 0)) {
		$arrRankList [$szRootAtt] = LoadRankList ( $szViolentscenes );
		
		// LoadSBFile cho tat ca cac thao tac luc sau
		$arrSubmission = LoadSBFile ( $szRootDir, $szGroupSet, $szProjectKF );
		
		// ConvertRankListToETF($szRootDir,$szRootOutout,$szListfile,$arrRankList,$szFileName,$szThreshold)
		$szFileETF = ConvertRankListToETF ( $arrSubmission, $szRootTmpETF, $arrRankList [$szRootAtt], $szRootAtt, $szThreshold );
		
		// old MAP2014
		
		$szOutfile = sprintf ( "%s/%s.out", $szRootTmpMAP, $szFeature );
		$szOutfileCmd = sprintf ( "%s/cmd_%s.txt", $szRootTmpMAP, $szFeature );
		
		$rootOrgMAP2014 = GetMAP ( $szOutfile, $szFileETF, $szGTFile, $szOutfileCmd );
		// end
		
		for($fTopPercent = 2; $fTopPercent < 60; $fTopPercent += 2) {
			$arrTopBot = CountTopBottomValue ( $szFileETF, $fTopPercent, $fBottomPercent = 20 );
			foreach ( $arrTopBot as $szVideoName => $arInfo ) {
				printf ( "%s - top = %f - bottom = %f\n", $szVideoName, $arInfo ['topT'], $arInfo ['bottomT'] );
			}
			$fPower = 2;
			$nWindowSize = 3;
			$arrETF = PostProcessing ( $szFileETF, $arrTopBot, $fPower, $nWindowSize );
			
			// print_r($arrETF);
			// exit();
			$szOutETF = sprintf ( "%s/PP_%s.%s", $szRootTmpETF, $szRootAtt, $szRunExt );
			SaveARRtoETFfile ( $arrETF, $szOutETF, 0 );
			
			$szOutfile = sprintf ( "%s/PP_%s.out", $szRootTmpMAP, $szFeature );
			$szOutfileCmd = sprintf ( "%s/PP_cmd_%s.txt", $szRootTmpMAP, $szFeature );
			$rootMAP2014 = GetMAP ( $szOutfile, $szOutETF, $szGTFile, $szOutfileCmd );
			
			printf ( "%d - MAP2014 - Org = %f --- New = %f\n", $fTopPercent, $rootOrgMAP2014, $rootMAP2014 );
		}
		// Get mAP by Mediaeval
		// $szOutfile = sprintf("%s/%s.out",$szRootTmpMAP,$szFeature);
		// $szOutfileCmd = sprintf("%s/cmd_%s.txt",$szRootTmpMAP,$szFeature);
		
		//
		
		/*
		 * //$szFileETF = sprintf("%s/%s%s.%s",$szRootTmpETF,$sztemplateRun,$szRootAtt,$szRunExt); $rootMAP2014 = GetMAP($szOutfile,$szFileETF,$szGTFile,$szOutfileCmd); //$rootMAP100 = ReadMAP100file($szOutfile); $strW = sprintf("%s,%0.6f,%0.6f\n",$szFeature,$rootMAP2014,$rootMAP100); fwrite($fResult,$strW); fwrite($fLogAll,$strW); $strWAll = sprintf("%s,%s,%s,%s,%s,%s,%0.6f,%0.6f\n",$szPrjName,$szProjectKF,$szFeature,$szGroupSet,$szConceptName,$szFeature,$rootMAP2014,$rootMAP100); fwrite($flogAllMAP,$strWAll); printf("\n-----Results------\n"); $arrResult = ReadAll($szOutfile); foreach ($arrResult as $szMetric => $fValue) { printf("%s = %f\n",$szMetric,$fValue); }
		 */
		// thuc hien post processing tren file ETF
		
		// ket thuc thuc hien post processing tren ETF
	}
}
$szEndTime = date ( "m.d.Y - H:i:s" );
$strtmp = sprintf ( "---%s---\n", $szEndTime );
fwrite ( $fResult, $strtmp );

fclose ( $fResult );
fclose ( $fLogAll );
fclose ( $flogAllMAP );

// cac ham duoc dung trong chuong trinh
function LoadRankList($szRankFile) {
	$arrRank = array ();
	$nLine = loadListFile ( $arrTmp, $szRankFile );
	foreach ( $arrTmp as $line ) {
		// 1 0 VSD11_15.shot15_439 2 1 mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q59.g_lbp.shotavg.ksc.vsd12.R1
		$tmp = sscanf ( $line, "%s %d %s %d %f %s", $AttID, $tmp1, $ShotID, $tmpO, $Score, $feature );
		$arrRank [trim ( $ShotID )] = $Score;
	}
	return $arrRank;
}
function GetShotList($szRankFile) {
	$arrShotOut = array ();
	$nLine = loadListFile ( $arrShot, $szRankFile );
	foreach ( $arrShot as $line ) {
		// 1 0 VSD11_15.shot15_439 2 1 mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q59.g_lbp.shotavg.ksc.vsd12.R1
		$tmp = sscanf ( $line, "%d %d %s %d %f %s", $AttID, $tmp1, $ShotID, $tmpO, $Score, $feature );
		$arrShotOut [] = $ShotID;
	}
	return $arrShotOut;
}
function FusionRankListAVG($arrRank1, $arrRank2, $arrShotList) {
	$arrRank = array ();
	foreach ( $arrShotList as $ShotID ) {
		$arrRank [$ShotID] = ($arrRank1 [$ShotID] + $arrRank2 [$ShotID]) / 2;
	}
	return $arrRank;
}
function FusionRankListMAX($arrRank1, $arrRank2, $arrShotList) {
	$arrRank = array ();
	foreach ( $arrShotList as $ShotID ) {
		if ($arrRank1 [$ShotID] > $arrRank2 [$ShotID])
			$arrRank [$ShotID] = $arrRank1 [$ShotID];
		else
			$arrRank [$ShotID] = $arrRank2 [$ShotID];
	}
	return $arrRank;
}
function SaveRankListtoFile($arrFusion, $szFileName, $szFeature) {
	$fRank = fopen ( $szFileName, "w" );
	arsort ( $arrFusion );
	$nOrder = 1;
	printf ( "Saving ranklist to file ....\n" );
	foreach ( $arrFusion as $key => $Value ) {
		$strW = sprintf ( "0 0 %s %d %f %s\n", $key, $nOrder, $Value, $szFeature );
		$nOrder ++;
		fwrite ( $fRank, $strW );
	}
	fclose ( $fRank );
	printf ( "Finish saving %d line to file %s\n", $nOrder - 1, $szFileName );
}
function LoadSBFile($szRootDir, $szListfile, $szProjectKF) {
	$szListfileFN = sprintf ( "%s/metadata/%s/%s.lst", $szRootDir, $szProjectKF, $szListfile );
	$nVideo = loadListFile ( $arrVideo, $szListfileFN );
	$arVideo = array ();
	$nTotalShot = 0;
	$arrSubmission = array ();
	foreach ( $arrVideo as $video ) {
		$strtmp = explode ( "#$#", $video );
		$szVideoID = trim ( $strtmp [0] );
		$szVideoName = trim ( $strtmp [1] );
		$arVideo [$szVideoID] = $szVideoName;
		printf ( "%s\n", $arVideo [$szVideoID] );
		// /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/sbinfo/keyframe-5/test2013/VSD_test2013_1.sb
		$szSBfile = sprintf ( "%s/sbinfo/%s/%s/%s.sb", $szRootDir, $szProjectKF, $szListfile, $szVideoID );
		$nShot = loadListFile ( $arrShot, $szSBfile );
		for($i = 2; $i < $nShot; $i ++) {
			$line = $arrShot [$i];
			$strtmp = explode ( "#$#", $line );
			$szShotID = trim ( $strtmp [0] );
			$szStart = ( int ) trim ( $strtmp [1] );
			$szDuration = ( int ) trim ( $strtmp [2] );
			$nTotalShot ++;
			$szSStart = floor ( $szStart / 25 ) + ($szStart % 25) * 40 / 1000;
			$szSDuration = floor ( $szDuration / 25 ) + ($szDuration % 25) * 40 / 1000;
			$arrSubmission [$szShotID] ['Start'] = $szSStart;
			$arrSubmission [$szShotID] ['Duration'] = $szSDuration;
			$arrSubmission [$szShotID] ['FilmName'] = $szVideoName;
			$arrSubmission [$szShotID] ['Score'] = 0;
		}
	}
	return $arrSubmission;
}
function CountTopBottomValue($szETF, $fTopPercent = 20, $fBottomPercent = 20) {
	$arrResult = array ();
	$arScoreList = array ();
	$nShot = loadListFile ( $arrETF, $szETF );
	// movie-FantasticFour1-2005-dvd2005-MediaEval.mpg 1 0.000 5.000 event - violence 0.019789 t
	foreach ( $arrETF as $line ) {
		$tmp = sscanf ( $line, "%s %d %f %f %s - %s %f %s", $VideoName, $tmp1, $fStart, $fDuration, $tmpEvent, $tmpViolent, $fScore, $szDecision );
		$szVideoName = trim ( $VideoName );
		$szShotID = sprintf ( "%s_%0.5f", $szVideoName, $fStart );
		$arrResult [$szVideoName] [$szShotID] ['start'] = floatval ( $fStart );
		$arrResult [$szVideoName] [$szShotID] ['duration'] = floatval ( $fDuration );
		$arrResult [$szVideoName] [$szShotID] ['orgscore'] = floatval ( $fScore );
		$arrResult [$szVideoName] [$szShotID] ['newscore'] = 0;
		
		$arScoreList [$szVideoName] [$szShotID] = floatval ( $fScore );
	}
	
	foreach ( $arScoreList as $szVideoName => $arTmp ) {
		arsort ( $arTmp );
		
		$arKeys = array_keys ( $arTmp );
		
		$nTopN = intval ( sizeof ( $arKeys ) * $fTopPercent / 100 );
		$nBottomN = intval ( sizeof ( $arKeys ) * (100 - $fBottomPercent) / 100 );
		
		$szKey = $arKeys [$nTopN]; // $nTopN = 100
		
		$fTopTheshold = $arTmp [$szKey];
		
		$szKey = $arKeys [$nBottomN]; // $nTopN = 100
		
		$fBottomTheshold = $arTmp [$szKey];
		
		$arrResult [$szVideoName] ['topT'] = $fTopTheshold;
		$arrResult [$szVideoName] ['bottomT'] = $fBottomTheshold;
	}
	
	return ($arrResult);
}
function SaveARRtoETFfile($arrETF, $szOutETF, $szThreshold) {
	$fETFout = fopen ( $szOutETF, "w" );
	foreach ( $arrETF as $szVideoName => $arVideoInfo ) {
		foreach ( $arVideoInfo as $szShotID => $arValue ) {
			// $tmp = sscanf($line,"%s %d %f %f %s - %s %f %s",$VideoName,$tmp1,$fStart,$fDuration,$tmpEvent,$tmpViolent,$fScore,$szDecision);
			
			if ($arValue ['newscore'] >= $szThreshold)
				$szViolent = "t";
			else
				$szViolent = "f";
			$strtmp = sprintf ( "%s 1 %.3f %.3f event - violence %.6f %s\n", $szVideoName, $arValue ['start'], $arValue ['duration'], $arValue ['newscore'], $szViolent );
			// printf("%s",$strtmp);
			fwrite ( $fETFout, $strtmp );
		}
	}
	fclose ( $fETFout );
}
function LoadETFinfo($szETF) {
	$nShot = loadListFile ( $arrETF, $szETF );
	$arrResult = array ();
	// movie-FantasticFour1-2005-dvd2005-MediaEval.mpg 1 0.000 5.000 event - violence 0.019789 t
	foreach ( $arrETF as $line ) {
		$tmp = sscanf ( $line, "%s %d %f %f %s - %s %f %s", $VideoName, $tmp1, $fStart, $fDuration, $tmpEvent, $tmpViolent, $fScore, $szDecision );
		$szVideoName = trim ( $VideoName );
		$szShotID = sprintf ( "%s_%0.5f", $szVideoName, $fStart );
		$arrResult [$szVideoName] [$szShotID] ['start'] = floatval ( $fStart );
		$arrResult [$szVideoName] [$szShotID] ['duration'] = floatval ( $fDuration );
		$arrResult [$szVideoName] [$szShotID] ['orgscore'] = floatval ( $fScore );
		$arrResult [$szVideoName] [$szShotID] ['newscore'] = 0;
	}
	return ($arrResult);
}
function ConvertRankListToETF($arrSubmission, $szRootOutout, $arrRankList, $szFileName, $szThreshold) {
	$sztemplateRun = "me13vsd_NII_";
	$szRunExt = "etf";
	
	// 0 0 VSD11_13.shot13_216 1 0.98005 mediaeval-v.....
	$arrSubOneRun = $arrSubmission;
	foreach ( $arrRankList as $ShotID => $Score ) {
		// $tmp = sscanf($line,"%d %d %s %d %f %s",$tmp1,$tmp2,$tmpShotID,$tmp3,$tmpScore,$tmp4);
		$arrSubOneRun [$ShotID] ['Score'] = $Score;
	}
	// Mo file de luu
	$szTestFeature = trim ( $szFileName );
	$szFileSub = sprintf ( "%s/%s%s.%s", $szRootOutout, $sztemplateRun, $szTestFeature, $szRunExt );
	$fSub = fopen ( $szFileSub, "w" );
	// $szFileSubNoScore = sprintf("%s/%s%s-NoScore.%s",$szRootOutout,$sztemplateRun,$szTestFeature,$szRunExt);
	// $fSubNoScore = fopen($szFileSubNoScore,"w");
	
	// $szFileSubLog = sprintf("%s/%s%s.log",$szRootOutout,$sztemplateRun,$szTestFeature,$szRunExt);
	// $fSubLog = fopen($szFileSubLog,"w");
	
	printf ( "Writing to Run file %s....\n", $szFileSub );
	
	foreach ( $arrSubOneRun as $key => $arInfo ) {
		$szFilmName = $arInfo ['FilmName'];
		$szStart = $arInfo ['Start'];
		$szDuration = $arInfo ['Duration'];
		$szScore = $arInfo ['Score'];
		// movie-KillBill1-2003-dvd2006-MediaEval.mpg 1 0.000 1.360 event - violence - f
		if ($szScore >= $szThreshold)
			$szViolent = "t";
		else
			$szViolent = "f";
		
		$strtmp = sprintf ( "%s.mpg 1 %.3f %.3f event - violence %.6f %s\n", $szFilmName, $szStart, $szDuration, $szScore, $szViolent );
		// printf("%s",$strtmp);
		fwrite ( $fSub, $strtmp );
		
		// $strtmp = sprintf("%s.mpg %s 1 %.3f %.3f event - violence %.6f %s\n",$szFilmName,$key,$szStart,$szDuration,$szScore,$szViolent);
		// printf("%s",$strtmp);
		// fwrite($fSubLog,$strtmp);
		
		// $strtmpNS = sprintf("%s.mpg 1 %.3f %.3f event - violence %s\n",$szFilmName,$szStart,$szDuration,$szScore,$szViolent);
		// fwrite($fSubNoScore,$strtmpNS);
	}
	fclose ( $fSub );
	// fclose($fSubNoScore);
	// fclose($fSubLog);
	printf ( "End Writing to Run file ....\n" );
	
	if (file_exists ( $szFileSub ) && (filesize ( $szFileSub ) > 0)) {
		return ($szFileSub);
	} else {
		return ("Error");
	}
}

/*
 * function PostProcessing($szRootOutout,$arrSubmission,$arrRankList,$szFileName) { $sztemplateRun = "me13vsd_NII_"; $szRunExt = "etf"; $arrSubOneRun = $arrSubmission; foreach ($arrRankList as $ShotID => $Score) { $arrSubOneRun[$ShotID]['OrgScore'] = $Score; $arrSubOneRun[$ShotID]['NewScore'] = 0; } // Mo file de luu $szTestFeature = trim($szFileName); $szFileSub = sprintf("%s/%s%s.%s",$szRootOutout,$sztemplateRun,$szTestFeature,$szRunExt); $fSub = fopen($szFileSub,"w"); printf("Writing to Run file %s....\n",$szFileSub); foreach($arrSubOneRun as $key => $arInfo) { $szFilmName = $arInfo['FilmName']; $szStart = $arInfo['Start']; $szDuration = $arInfo['Duration']; $szScore = $arInfo['Score']; if ($szScore >= $szThreshold) $szViolent = "t"; else $szViolent = "f"; $strtmp = sprintf("%s.mpg 1 %.3f %.3f event - violence %.6f %s\n",$szFilmName,$szStart,$szDuration,$szScore,$szViolent); //printf("%s",$strtmp); fwrite($fSub,$strtmp); } fclose($fSub); printf("End Writing to Run file ....\n"); if ( file_exists($szFileSub) && (filesize($szFileSub)>0) ) { return($szFileSub); } else { return("Error"); } }
 */
function ReadMAPfile($szMapfile) {
	$fmap = fopen ( $szMapfile, "r" );
	while ( ! feof ( $fmap ) ) {
		
		$line = fgets ( $fmap );
		$strtmp = explode ( " ", $line );
		if (trim ( $strtmp [0] ) == "MAP")
			$mAP = ( float ) trim ( $strtmp [2] );
	}
	printf ( "\nMAP2013 = %0.8f\n", $mAP );
	return $mAP;
}
function ReadMAP100file($szMapfile) {
	$fmap = fopen ( $szMapfile, "r" );
	while ( ! feof ( $fmap ) ) {
		
		$line = fgets ( $fmap );
		$strtmp = explode ( " ", $line );
		if (trim ( $strtmp [0] ) == "MAP-AT100")
			$mAP = ( float ) trim ( $strtmp [2] );
	}
	printf ( "\nMAP100 = %0.8f\n", $mAP );
	return $mAP;
}
function ReadMAP2014file($szMapfile) {
	$fmap = fopen ( $szMapfile, "r" );
	while ( ! feof ( $fmap ) ) {
		
		$line = fgets ( $fmap );
		$strtmp = explode ( " ", $line );
		if (trim ( $strtmp [0] ) == "MAP2014")
			$mAP = ( float ) trim ( $strtmp [2] );
	}
	printf ( "\nMAP2014 = %0.8f\n", $mAP );
	return $mAP;
}
function ReadAll($szMapfile) {
	$arResult = array ();
	$fmap = fopen ( $szMapfile, "r" );
	while ( ! feof ( $fmap ) ) {
		
		$line = fgets ( $fmap );
		$strtmp = explode ( " ", $line );
		if (trim ( $strtmp [0] ) == "MAP2014")
			$arResult ['MAP2014'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "recall")
			$arResult ['recall'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "precision")
			$arResult ['precision'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "F-measure")
			$arResult ['F-measure'] = ( float ) trim ( $strtmp [2] );
		if ((trim ( $strtmp [0] ) == "MediaEval") && (trim ( $strtmp [1] ) == "cost"))
			$arResult ['MediaEval cost'] = ( float ) trim ( $strtmp [3] );
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "precision"))
			$arResult ['AED precision'] = ( float ) trim ( $strtmp [7] ) / 100;
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "recall"))
			$arResult ['AED recall'] = ( float ) trim ( $strtmp [7] / 100 );
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "NBCORRECT"))
			$arResult ['AED NBCORRECT'] = ( float ) trim ( $strtmp [3] );
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "NBFOUND"))
			$arResult ['AED NBFOUND'] = ( float ) trim ( $strtmp [3] );
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "NBMISSED"))
			$arResult ['AED NBMISSED'] = ( float ) trim ( $strtmp [3] );
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "NBFA"))
			$arResult ['AED NBFA'] = ( float ) trim ( $strtmp [3] );
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "NBNREF"))
			$arResult ['AED NBNREF'] = ( float ) trim ( $strtmp [3] );
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "F-measure"))
			$arResult ['AED F-measure'] = ( float ) trim ( $strtmp [3] ) / 100;
		if ((trim ( $strtmp [0] ) == "AED") && (trim ( $strtmp [1] ) == "MediaEval"))
			$arResult ['AED MediaEval cost'] = ( float ) trim ( $strtmp [4] );
		if (trim ( $strtmp [0] ) == "MAP")
			$arResult ['MAP'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "PrecisionAT100")
			$arResult ['PrecisionAT100'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "R-PrecisionAT100")
			$arResult ['R-PrecisionAT100'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "MAP-AT100")
			$arResult ['MAP-AT100'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "PrecisionAT20")
			$arResult ['PrecisionAT20'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "R-PrecisionAT20")
			$arResult ['R-PrecisionAT20'] = ( float ) trim ( $strtmp [2] );
		if (trim ( $strtmp [0] ) == "MAP-AT20")
			$arResult ['MAP-AT20'] = ( float ) trim ( $strtmp [2] );
	}
	return $arResult;
}
function GetMAP($szOutfile, $szFileETF, $szGTETF, $szOutfileCmd) {
	// $szToolDir = "/net/sfv215/export/raid4/ledduy/lqvu-Experiments/2013-lqvu-MediaEval/CheckGroundtruth";
	
	// perl ./trackeval -error=evt,sum,src -det=det_filename.txt ./GT_MediaEval2013-objviolentscenes.groundtruth.etf /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/temp/trackeval/Test7_MediaEval2013-objviolentscenes.groundtruth.etf > ./outABC-8.txt
	$szToolDir = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/trackeval";
	// $szToolDir = "/net/sfv215/export/raid6/ledduy/lqvu-mediaeval/2013-code/CheckGroundtruth";
	// $szGTETF = sprintf("%s/MediaEval2012-violentscenes.groundtruth.etf",$szToolDir);
	// MediaEval2012-violentscenes.groundtruth.etf
	// ./trackeval -error=evt,sum,src vsd2011-violentscenes.groundtruth.etf ../OUTPUT/FusionAttributes/mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q59.g_lbp.shotavg.ksc.vsd12.R1_tmpETF/me12vsd_NII_violentscenes_blood.etf -o=violent_blood.out
	// perl ./trackeval -error=evt,sum,src -det=det_filename.txt
	// ./GT_MediaEval2013-objviolentscenes.groundtruth.etf /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/temp/trackeval/Test7_MediaEval2013-objviolentscenes.groundtruth.etf > ./outABC-8.txt
	$strcmd = sprintf ( "perl %s/trackeval -error=evt,sum,src -det=det_filename.txt  %s %s -o=%s > %s", $szToolDir, $szGTETF, $szFileETF, $szOutfile, $szOutfileCmd );
	execSysCmd ( $strcmd );
	return (ReadMAP2014file ( $szOutfile ));
}

exit ();

/*
 * MediaEval cost = 1.013391 AED precision = 714 / 6569 = 0.1087% AED recall = 714 / 715 = 0.9986% AED NBCORRECT = 714 AED NBFOUND = 714 AED NBMISSED = 1 AED NBFA = 5855 AED NBNREF = 5855 AED F-measure = 0.1960% AED MediaEval cost = 1.013986 MAP = 0.0808097406964226 PrecisionAT100 = 0.0666666666666667 R-PrecisionAT100 = 0.0619376213650772 MAP-AT100 = 0.127309713860969
 */
?>