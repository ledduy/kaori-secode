<?php
// Lam Quang Vu
// 3 SEP 2012
// fusion on submission format
if ($argc != 4) {
	printf ( "Number of params [%s] is incorrect [4]\n", $argc );
	printf ( "Usage %s <RootDir> <ConceptName> <CFGFusionList>", $argv [0] );
	exit ();
}

require_once "ksc-AppConfig.php";


function CutExt($strFullName) {
	$strtmp = explode ( ".", $strFullName );
	$count = count ( $strtmp );
	$strExt = $strtmp [$count - 1];
	$strResult = substr ( $strFullName, 0, - 1 * (strlen ( $strExt ) + 1) );
	return $strResult;
}
function CutPath($strFullName)
{
	$strtmp = explode("/",$strFullName);
	$count = count($strtmp);
	$strExt = $strtmp[$count-1];
	return $strExt;
}

$szRootDir = $argv [1];
$szConceptName = $argv [2];
$szListfile = $argv [3];
$szOutFile = CutExt ( CutPath($szListfile));

/* $arrConceptID = array (
		"subjviolentscenes" => 1,
		"objviolentscenes" => 0 
); */
$sztmpOutPut = sprintf ( "%s/%s", $szRootDir, $szOutFile );

if (! file_exists ( $sztmpOutPut )) {
	makeDir ( $sztmpOutPut );
}

$strcmd = sprintf("chmod -R 777 %s",$sztmpOutPut);
execSysCmd ($strcmd);

$szFileOut = sprintf ( "%s/%s.rank", $sztmpOutPut, $szConceptName );
// $fOut = fopen($szFileOut,"w");

$nSubFile = loadListFile ( $arrSubFile, $szListfile );
$arrResult = array ();
$arrWeight = array ();
$arrShotList = array ();
for($i = 0; $i < $nSubFile; $i ++) {
	$fileSub = $arrSubFile [$i];
	$strTemp = explode ( "#$#", $fileSub );
	$szFeatureName = trim ( $strTemp [0] );
	$nFeatureWeight = intval ( trim ( $strTemp [1] ) );
	
	$szFileSub = sprintf ( "%s/%s/%s.rank", $szRootDir, $szFeatureName, $szConceptName );
	printf ( "%s\n", $szFileSub );
	$arrShotList = GetShotList ( $szFileSub );
	// printf("%s\n",$szFileSub);
	// $arr = LoadRankList($szFileSub);
	$arrWeight [$i] = $nFeatureWeight;
	
	$nLine = loadListFile ( $arrTmp, $szFileSub );
	foreach ( $arrTmp as $line ) {
		// 1 0 VSD11_15.shot15_439 2 1 mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q59.g_lbp.shotavg.ksc.vsd12.R1
		$tmp = sscanf ( $line, "%s %d %s %d %f %s", $AttID, $tmp1, $ShotID, $tmpO, $Score, $feature );
		$arrResult [$i] [trim ( $ShotID )] = $Score;
	}
}
// arsort($arrShotList);
$nCount = 1;
$arrOutput = array ();
foreach ( $arrShotList as $key => $shotID ) {
	
	$fTotal = 0;
	$nTotalW = 0;
	for($i = 0; $i < $nSubFile; $i ++) {
		$fTotal += $arrResult [$i] [$shotID] * $arrWeight [$i];
		$nTotalW += $arrWeight [$i];
	}
	$arrOutput [$shotID] = $fTotal / $nTotalW;
	
	$strtmp = sprintf ( "%s\n", $shotID );
	printf ( "Shot %s: %f = %f - %d", $arrOutput [$shotID], $fTotal, $nTotalW );
}
arsort ( $arrOutput );
$nRank = 1;
$arrOut = array ();
foreach ( $arrOutput as $szShotID => $fScore ) {
	$arrOut [] = sprintf ( "%s 0 %s %s %s %s",$szConceptName, $szShotID, $nRank, $fScore, $szOutFile );
	$nRank ++;
}

saveDataFromMem2File ( $arrOut, $szFileOut );
$strcmd = sprintf("chmod -R 777 %s",$szFileOut);
execSysCmd ($strcmd);
printf ( "done" );

// exit;
/*
 * for ($j=0;$j<$nShotLine;$j++) { $fScore = ($arrResult[0][$j]['Score']*$fW1+$arrResult[1][$j]['Score']*$fW2)/($fW1+$fW2); $filmName = $arrResult[0][$j]['FilmName']; $number = $arrResult[0][$j]['Number']; $start = $arrResult[0][$j]['Start']; $end =$arrResult[0][$j]['End']; $event = $arrResult[0][$j]['Event']; $daucach =$arrResult[0][$j]['Daucach']; $violent =$arrResult[0][$j]['Violent']; $decision =$arrResult[0][$j]['Decision']; $strtmp = sprintf("%s %d %.2f %.2f %s %s %s %.6f %s\n",$filmName,$number,$start,$end,$event,$daucach,$violent,$fScore,$decision); printf("%s",$strtmp); fwrite($fOut,$strtmp); }
 */
fclose ( $fOut );

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
		$arrShotOut [] = $ShotID;
	}
	return $arrShotOut;
}

?>