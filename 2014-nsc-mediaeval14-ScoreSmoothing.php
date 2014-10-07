<?php
// Lam Quang Vu
// 10 OCT 2013
/*
 * Thuc hien score smoothing cho tren cac rank list
 */
if ($argc != 5) {
	printf ( "Number of params [%s] is incorrect [5]\n", $argc );
	printf ( "Usage %s <RootInDir> <PrjNameExp> <Concept Name> <Feature List File>", $argv [0] );
	exit ();
}
require_once "ksc-AppConfig.php";

/*
 * Dac ta trong file rank 0 0 VSD11_13.shot13_216 1 0.98005 mediaeval-vsd2012-S.nsc.cCV_YCrCb.g6.q3.g_cm.shotmax.ksc.vsd12.R1 Dac ta format se nop <source> 1 <start_time> <duration> event - violence [<score> [<decision>]] /net/sfv215/export/raid6/ledduy/lqvu-mediaeval/experiments/mediaeval-vsd2012-S/results/mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q59.g_lbp.shotmax.ksc.vsd12.R1 /net/sfv215/export/raid6/ledduy/mediaeval-2013/experiments/mediaeval-vsd2012-kf5
 */

$szRootDir = $argv [1];
$szPrjName = $argv [2];
$szConceptName = $argv [3];
$szListFeatures = $argv [4];

$szRootLogs = sprintf ( "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/temp/lqvuLog/ScoreSmoothing/");
if (! file_exists ( $szRootLogs )) {
	makeDir ( $szRootLogs );
}
$szLogAll = sprintf ( "%s/LogAll.csv", $szRootLogs );
$fLogAll = fopen ( $szLogAll, "w" );

// /net/per610a/export/das11f/ledduy/mediaeval-vsd-2013/experiments/mediaeval-vsd2013-shot/results
///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/result/keyframe-5/mediaeval-vsd-2014.devel2013-new.test2013-new

// Thu muc chinh luu ket qua

$szResultDir = sprintf ( "%s/result/keyframe-5/%s", $szRootDir, $szPrjName );
$nFeatures = loadListFile ( $arrFeatures, $szListFeatures );

foreach ( $arrFeatures as $szFeature ) {
	$szNewFeatureName = sprintf ( "0_SS_%s", $szFeature );
	$szRootOutDir = sprintf ( "%s/%s", $szResultDir, $szNewFeatureName );
	if (! file_exists ( $szRootOutDir )) {
		makeDir ( $szRootOutDir );
	}
	$szFPRankListFN = sprintf ( "%s/%s.rank", $szRootOutDir, $szConceptName );
	$szRootInResult = sprintf ( "%s/%s", $szResultDir, $szFeature );
	printf ( "%s\n", $szRootInResult );
	
	$arrRankList = array ();
	$arrShotList = array ();
	$arrNewRankList = array ();
	
	$szViolentscenes = sprintf ( "%s/%s.rank", $szRootInResult, $szConceptName );
	printf ( "\nFile size of szViolentscenes = %d \n", filesize ( $szViolentscenes ) );
	if (file_exists ( $szViolentscenes ) && (filesize ( $szViolentscenes ) > 0)) {
		$arrRankList = LoadRankList ( $szViolentscenes );
		$arrShotList = GetShotList ( $szViolentscenes );
		foreach ( $arrShotList as $tmpShotID ) {
			// VSD11_15.shot15_439
			//VSD_test2013_1.shot_1068
			$tmpShot = explode ( ".", $tmpShotID );
			$szVideoID = trim ( $tmpShot [0] ); // VSD11_15
			$sztmpShotID = trim ( $tmpShot [1] ); // shot15_439
			
			$tmpID = explode ( "_", $sztmpShotID );
			$shotNameID = trim ( $tmpID [0] );
			$shotNumber = intval ( trim ( $tmpID [1] ) );
			
			$nPreShotNumber = $shotNumber - 1;
			$nNextShotNumber = $shotNumber + 1;
			// printf("%d\n",$shotNumber);
			
			if ($nPreShotNumber <= 0) {
				$nPreShotNumber = 1;
				printf ( "--%s--\n", $tmpShotID );
			}
			if (! is_numeric ( $arrRankList [$szNextShotID] ['Score'] )) {
				$nNextShotNumber = $shotNumber;
				printf ( "--%s--\n", $tmpShotID );
			}
			$szPreShotID = sprintf ( "%s.%s_%d", $szVideoID, $shotNameID, $nPreShotNumber );
			$szNextShotID = sprintf ( "%s.%s_%d", $szVideoID, $shotNameID, $nNextShotNumber );
			
			$arrNewRankList [$tmpShotID] = ($arrRankList [$szPreShotID] ['Score'] + $arrRankList [$tmpShotID] ['Score'] + $arrRankList [$szNextShotID] ['Score']) / 3;
			$tmp = sprintf ( "%s,%f,%f,%f,%f\n", $tmpShotID, $arrRankList [$tmpShotID] ['Score'], $arrRankList [$szPreShotID] ['Score'], $arrRankList [$szNextShotID] ['Score'], $arrNewRankList [$tmpShotID] );
			fwrite ( $fLogAll, $tmp );
		}
		arsort ( $arrNewRankList );
		
		$arOutput = array ();
		$nRank = 1;
		foreach ( $arrNewRankList as $szShotID => $fScore ) {
			$nConceptID = $arrRankList [$szShotID] ['AttID'];
			$szRunCfg = $szNewFeatureName;
			$arOutput [] = sprintf ( "%s 0 %s %d %f %s", $nConceptID, $szShotID, $nRank, $fScore, $szRunCfg );
			$nRank ++;
		}
		
		saveDataFromMem2File ( $arOutput, $szFPRankListFN );
	}
}

fclose ( $fLogAll );

// cac ham duoc dung trong chuong trinh
function LoadRankList($szRankFile) {
	$arrRank = array ();
	$nLine = loadListFile ( $arrTmp, $szRankFile );
	foreach ( $arrTmp as $line ) {
		// 1 0 VSD11_15.shot15_439 2 1 mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q59.g_lbp.shotavg.ksc.vsd12.R1
		$tmp = sscanf ( $line, "%s %d %s %d %f %s", $AttID, $tmp1, $ShotID, $tmpO, $Score, $feature );
		$arrRank [trim ( $ShotID )] ['Score'] = $Score;
		$arrRank [trim ( $ShotID )] ['AttID'] = $AttID;
		$arrRank [trim ( $ShotID )] ['tmp1'] = $tmp1;
	}
	return $arrRank;
}
function GetShotList($szRankFile) {
	$arrShotOut = array ();
	$nLine = loadListFile ( $arrShot, $szRankFile );
	foreach ( $arrShot as $line ) {
		// 1 0 VSD11_15.shot15_439 2 1 mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q59.g_lbp.shotavg.ksc.vsd12.R1
		$tmp = sscanf ( $line, "%s %d %s %d %f %s", $AttID, $tmp1, $ShotID, $tmpO, $Score, $feature );
		$arrShotOut [] = trim($ShotID);
	}
	return $arrShotOut;
}

?>