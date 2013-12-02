<head>
<title>NII-Kaori-Secode@MediaEval2013 - View Ranked List By Submission File (ETF)</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=1" />
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
</head>
<body>

<!-- Start of Google Analytics Code -->
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-1229912-2";
urchinTracker();
</script>
<!-- End of Google Analytics Code -->

<!-- Start of StatCounter Code -->
<script type="text/javascript" language="javascript">
var sc_project=1575092; 
var sc_invisible=1; 
var sc_partition=14; 
var sc_security="433ecd94"; 
</script>

<script type="text/javascript" language="javascript" src="http://www.statcounter.com/counter/counter.js"></script><noscript><a href="http://www.statcounter.com/" target="_blank"><img  src="http://c15.statcounter.com/counter.php?sc_project=1575092&amp;java=0&amp;security=433ecd94&amp;invisible=1" alt="free hit counter script" border="0"></a> </noscript>
<!-- End of StatCounter Code -->


<?php
require_once "nsc-localAppConfig.php";
require_once "ps-EvalTool.php";

// internet version
ini_set("memory_limit", "256M"); // UNLIMIT
$arConceptList = array(
		"objviolentscenes",
		"subjviolentscenes",
		"carchase",
		"coldarms",
		"fire",
		"firearms",
		"gore",
		"blood_high",
		"blood_low",
		"blood_medium",
		"blood_unnoticeable",
		"fights_1vs1",
		"fights_small",
		"fights_large",
		"fights_distant_attack");

$nAction = $_REQUEST['vAction'];
$nExperiment = $_REQUEST['vExpName'];
$szFeatureName = $_REQUEST['vFeatureName'];
$szConceptname = $_REQUEST['vConceptName'];
$nPageIDForm = (int)$_REQUEST['vPageID'];
$szFilter = $_REQUEST['vFilter'];
$szViewKF = $_REQUEST['vViewKF'];
$szNumShotPerPage = (int)$_REQUEST['vNumShot'];

if ($nPageIDForm <0) $nPageIDForm = 0;
  
if ($_REQUEST['vNumShot'] == "")
	$szNumShotPerPage = 100;

$szFileName = "2013-nsc-web-mediaeval13-ViewRankedListAll-byVideo.php";

$szRootDir = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2013";
//$szRootDir = "/net/sfv215/export/raid6/ledduy/lqvu-mediaeval";
$szProjectName = "keyframe-5";
$szSBfilesRoot = sprintf("%s/SBfiles",$szRootDir);

// ten file dung de view icon trung hay sai
//MediaEval2013-test12-subjviolentscenes.groundtruth.forview  

$arGT = array("mediaeval-vsd2012-kf5" =>"MediaEval2013",
			  "mediaeval-vsd2013-shot" =>"MediaEval2013",
			  "mediaeval-vsd2013-kf5" =>"MediaEval2013",
			  "mediaeval-vsd2013-kf10" =>"MediaEval2013");
//$arGT = array("mediaeval-vsd2012-kf5" =>"2012_Test12_Allconcept-GT");


$arSetGroup = array("mediaeval-vsd2012-kf5"=>"test12",
					"mediaeval-vsd2013-kf5"=>"test12",
					"mediaeval-vsd2013-shot"=>"test2013",
					"mediaeval-vsd2013-kf10"=>"test12");


$szRootExpDir = sprintf("%s/experiments",$szRootDir);

if (!$szConceptname)
{
	printf("<H1>View RankList - 5 Keyframes Per MediaEval-Shot</H1>\n");
	printf("<FORM TARGET='_self'>\n");
	printf("<H3>Select concept ");
	printf("<SELECT NAME='vConceptName'>\n");
	foreach($arConceptList as $line)
	{
		$sztmp = trim($line);
		if ($sztmp == $_REQUEST['vConceptName'])
			printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n", $sztmp,$sztmp);
		else
			printf("<OPTION VALUE='%s'>%s</OPTION>\n", $sztmp, $sztmp);
	}
	printf("</SELECT></H3>\n");
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE=1>\n");
	printf("<P><INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset'>\n");
	
	printf("</FORM>\n");
	exit();
}

if (!$nExperiment)
{
	$arExp = collectDirsInOneDir($szRootExpDir);
	printf("<H1>View RankList - 5 Keyframes Per MediaEval-Shot</H1>\n");
	printf("<FORM TARGET='_self'>\n");
	
	printf("<H3>Select concept ");
	printf("<SELECT NAME='vConceptName'>\n");
	foreach($arConceptList as $line)
	{
		$sztmp = trim($line);
		if ($sztmp == $_REQUEST['vConceptName'])
			printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n", $sztmp,$sztmp);
		else
			printf("<OPTION VALUE='%s'>%s</OPTION>\n", $sztmp, $sztmp);
	}
	printf("</SELECT></H3>\n");
	
	printf("<H3>Select experiments ");
	printf("<SELECT NAME='vExpName'>\n");
	foreach($arExp as $line)
		{
			$szExpName = trim($line);
			if ($szExpName == $_REQUEST['vExpName'])
				printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n", $szExpName, $szExpName);
			else
				printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szExpName, $szExpName);
		}
	printf("</SELECT></H3>\n");
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE=1>\n");
	printf("<P><INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset'>\n");
	
	printf("</FORM>\n");
	exit();
}


//if (!$nAction)

///net/sfv215/export/raid6/ledduy/lqvu-mediaeval/experiments/mediaeval-vsd2012/results
	$szResultsDir = sprintf("%s/%s/results",$szRootExpDir,$nExperiment);
	$szSubmissionDir = sprintf("%s/%s/submission",$szRootExpDir,$nExperiment);

	$szResultsDir = sprintf("%s/%s/results",$szRootExpDir,$nExperiment);
	
	$arExp = collectDirsInOneDir($szRootExpDir);
	
	$arResults = collectFilesInOneDir($szSubmissionDir, $_REQUEST['vConceptName'], ".etf");
	sort($arResults);
	printf("<H1>View RankList - MediaEval2013</H1>\n");
	printf("<FORM TARGET='_self'>\n");
	printf("<H3>Select concept ");
	printf("<SELECT NAME='vConceptName'>\n");
	foreach($arConceptList as $line)
	{
		$sztmp = trim($line);
		if ($sztmp == $_REQUEST['vConceptName'])
			printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n", $sztmp,$sztmp);
		else
			printf("<OPTION VALUE='%s'>%s</OPTION>\n", $sztmp, $sztmp);
	}
	printf("</SELECT></H3>\n");
	
	printf("<H3>Select experiments ");
	printf("<SELECT NAME='vExpName'>\n");
	foreach($arExp as $line)
	{
		$szExpName = trim($line);
		if ($szExpName == $_REQUEST['vExpName'])
			printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n", $szExpName, $szExpName);
		else
			printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szExpName, $szExpName);
	}
	printf("</SELECT></H3>\n");
	
	printf("<H3>Select feature ");
	printf("<SELECT NAME='vFeatureName'>\n");
	foreach($arResults as $line)
	{
		$sztmp = trim($line);
		if ($sztmp == $_REQUEST['vFeatureName'])
			printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n", $sztmp,$sztmp);
		else
			printf("<OPTION VALUE='%s'>%s</OPTION>\n",$sztmp,$sztmp);
	}
	printf("</SELECT></H3>\n");
	printf("<H3>Filter ");
	printf("<INPUT TYPE=TEXT NAME='vFilter' VALUE='%s' SIZE=20></H3>\n",$szFilter);
	printf("<H3>Page ");
	printf("<INPUT TYPE=TEXT NAME='vPageID' VALUE='%d' SIZE=10></H3>\n",$nPageIDForm);
	printf("<H3>Num Shot/Page ");
	printf("<INPUT TYPE=TEXT NAME='vNumShot' VALUE='%d' SIZE=10></H3>\n",$szNumShotPerPage);
	if ($szViewKF <> 'View')
		printf("<input type='checkbox' name='vViewKF' value='View'>View keyframes<br>\n");
	else 
		printf("<input type='checkbox' name='vViewKF' value='View' checked>View keyframes<br>\n");
	
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE=1>\n");
	printf("<P><INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset'>\n");
	
	printf("</FORM>\n");
	
//printf("%s<br>",$szFeatureName);

//$szRankList = sprintf("%s/%s/%s.rank",$szResultsDir,$szFeatureName,$szConceptname);
	$szRankList = sprintf("%s/%s.etf",$szSubmissionDir,$szFeatureName);
if (!file_exists($szRankList))
{
	printf("<H3 color='red'>There is no rank list file !</H3>");
	exit;
}
printf("%s<br>",$szRankList);
/* $nTotalshot = loadListFile($arrShotScore,$szRankList);
//0 0 VSD11_15.shot15_450 1 1 mediaeval-vsd2012-S.nsc.cCV_GRAY.g4.q30.g_lbp.shot.ksc.vsd12.R1
$nShot = 0;
$arrShot = array();
$arrRank = array();
//0 0 VSD13_22.shot22_48 1 0.860879 mediaeval-vsd2013-shot.nsc.cCV_GRAY.g4.q30.g_lbp.shotmax.ksc.vsd13.R1
foreach($arrShotScore as $line)
{
	$tmp = sscanf($line,"%d %d %s %d %f %s",$tmp1,$tmp2,$szShotID,$szOder,$score,$tmpFeatureName);
	$arrScore[$szShotID] = $score;
	$arrShot[$nShot]['ShotID'] = $szShotID;
	$arrShot[$nShot]['Score'] = $score;
	$arrRank[]=$szShotID;
	$nShot++;
	//if ($nShot <= 100) printf("%s<br>",$line);
} */
//printf("<br>Total %d<br>",$nShot);

/*
$nShot = 0;
foreach($arrScore as $key => $score)
{
	$nShot++;
	printf("%d.%s - %.6f<br>",$nShot,$key,$score);
}
*/

// Lay thong tin keyframe


//$szSBfiles = sprintf("%s/%s",$szSBfilesRoot,$arSetGroup[$nExperiment]);

// Doan nay cho view cu, co test file, dung trong attribute
/* $strTmp = explode("-",$szFeatureName);
$nSize = sizeof($strTmp);
$szSET = trim($strTmp[$nSize-2]);

printf("<br>%s<br>",$szSET);
//$szGroupName = $arSetGroup[$nExperiment];
$szGroupName = $szSET; */
$szGroupName = $arSetGroup[$nExperiment];
$szListFile = sprintf("%s/metadata/%s/%s.lst",$szRootDir,$szProjectName,$szGroupName);

loadListFile($arFilm,$szListFile);
$arShotList = array();
$arFilmList = array();
$arFilmName = array(); 

$szVideoName = $_REQUEST['vVideoName'];

if (!$szVideoName)
{
	printf("<FORM TARGET='_self'>\n");
	
	printf("<H3>Select video: ");
	printf("<SELECT NAME='vVideoName'>\n");
	
	foreach($arFilm as $line)
	{
		$sztmp = trim($line);
		
		$strtmp = explode("#$#",$sztmp);
		$strVideoID = trim($strtmp[0]);
		$strVideoSet = trim($strtmp[2]);
		$strFilmName = trim($strtmp[1]);
		
		//if ($sztmp == $_REQUEST['vVideoName'])
		//	printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n",$strFilmName,$strFilmName);
		//else
		printf("<OPTION VALUE='%s'>%s</OPTION>\n",$strFilmName,$strFilmName);
		
	}
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE=1>\n");
	printf("<INPUT TYPE='HIDDEN' NAME='vExpName' VALUE=%s>\n",$nExperiment);
	printf("<INPUT TYPE='HIDDEN' NAME='vFeatureName' VALUE=%s>\n",$szFeatureName);
	printf("<INPUT TYPE='HIDDEN' NAME='vConceptName' VALUE=%s>\n",$szConceptname);
	printf("<INPUT TYPE='HIDDEN' NAME='vFilter' VALUE=%s>\n",$szFilter);
	printf("<INPUT TYPE='HIDDEN' NAME='vPageID' VALUE=%s>\n",$nPageIDForm);
	printf("<INPUT TYPE='HIDDEN' NAME='vViewKF' VALUE=%s>\n",$szViewKF);
	printf("<INPUT TYPE='HIDDEN' NAME='vNumShot' VALUE=%s>\n",$szNumShotPerPage);
	printf("</SELECT></H3>\n");
	printf("<P><INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	
	exit;
}
else 
{
	printf("<FORM TARGET='_self'>\n");
	
	printf("<H3>Select video: ");
	printf("<SELECT NAME='vVideoName'>\n");
	
	foreach($arFilm as $line)
	{
		$sztmp = trim($line);
	
		$strtmp = explode("#$#",$sztmp);
		$strVideoID = trim($strtmp[0]);
		$strVideoSet = trim($strtmp[2]);
		$strFilmName = trim($strtmp[1]);
	
		if ($sztmp == $_REQUEST['vVideoName'])
			printf("<OPTION VALUE='%s' selected='selected'>%s</OPTION>\n",$strFilmName,$strFilmName);
		else
			printf("<OPTION VALUE='%s'>%s</OPTION>\n",$strFilmName,$strFilmName);
	}
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE=1>\n");
	printf("<INPUT TYPE='HIDDEN' NAME='vExpName' VALUE=%s>\n",$nExperiment);
	printf("<INPUT TYPE='HIDDEN' NAME='vFeatureName' VALUE=%s>\n",$szFeatureName);
	printf("<INPUT TYPE='HIDDEN' NAME='vConceptName' VALUE=%s>\n",$szConceptname);
	printf("<INPUT TYPE='HIDDEN' NAME='vFilter' VALUE=%s>\n",$szFilter);
	printf("<INPUT TYPE='HIDDEN' NAME='vPageID' VALUE=%s>\n",$nPageIDForm);
	printf("<INPUT TYPE='HIDDEN' NAME='vViewKF' VALUE=%s>\n",$szViewKF);
	printf("<INPUT TYPE='HIDDEN' NAME='vNumShot' VALUE=%s>\n",$szNumShotPerPage);
	printf("</SELECT></H3>\n");
	printf("<P><INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
}

foreach($arFilm as $line)
{
	$strtmp = explode("#$#",$line);
	$strVideoID = trim($strtmp[0]);
	$strVideoSet = trim($strtmp[2]);
	$strFilmName = trim($strtmp[1]);
	if ($strFilmName==$szVideoName)
		break;
}

$arFilmName[$strVideoID] = $strFilmName;
$szPrgzfile = sprintf("%s/metadata/%s/%s/%s.prgz",$szRootDir,$szProjectName,$strVideoSet,$strVideoID);
$nKeyframe = loadListFile($arrKeyframe,$szPrgzfile);

$arTmpShot = array();
foreach ($arrKeyframe as $line)
{
	$strtmp = explode("#$#",$line);
	$sztmpVideoID = trim($strtmp[0]);
	$sztmpShotID = trim($strtmp[1]);
	$sztmpKFID = trim($strtmp[2]);
	$arShotList[$sztmpShotID][] = $sztmpKFID;
	$arFilmList[$sztmpShotID][] = $strVideoID;
	$arTmpShot[] = $sztmpShotID;  
}

$arAllShotbyVideo = array_unique($arTmpShot);

$nTotalshot = sizeof($arAllShotbyVideo);

//loadListFile($arrShotScore,$szRankList);

$arrShotScore = loadSubmissionFile($szRankList);
    
//printf($strFilmName); 
$strFilmNameEz = sprintf("%s.mpg", $strFilmName);
//print_r($arrShotScore[$strFilmNameEz]); exit();       

$arShotScoreList = $arrShotScore[$strFilmNameEz];
$nRank = 1;
$szGroupName = "test2013";
foreach($arShotScoreList as $szShotIDz => $fScore)
{
    $arTmp = explode("_", $szShotIDz);
    $szShotID = trim($arTmp[0]);
    $fShotStart = floatval($arTmp[1]);
    $fShotDuration = floatval($arTmp[2]);

    $nShotDuration = $fShotDuration*25;
    $nShotStart = $fShotStart*25;
    $szLink = getVideoClipURL($strFilmName, $nShotStart, $nShotDuration, $szShotID, $szGroupName);
    printf("<P>%d. <A HREF='%s'  target=_blank>%s - %f - %f - [%f]</A>\n", $nRank, $szLink, $szShotID, $fShotStart, $fShotDuration, $fScore);
    $nRank++;
}

function getVideoClipURL($szVideoName, $nShotStart, $nShotDuration, $szShotID, $szGroupName)
{
	$nShotDuration = min($nShotDuration, 25*30); // max 30 sec
	$nShotDuration = max(50, $nShotDuration); // min 1sec
	$szClipID = sprintf("%s-%s",$szVideoName, $szShotID);
	$szViewVideoClipURL = sprintf("2013-nsc-web-mediaeval12-ViewVideoClip.php?vVideoID=%s&vStartFrame=%d&vDuration=%d&vClipID=%s&vGroup=%s",
			$szVideoName, $nShotStart, $nShotDuration, $szClipID,$szGroupName);
	return $szViewVideoClipURL;
}

function loadSubmissionFile($szFPInputFN)
{
    loadListFile($arRawList, $szFPInputFN);

    $arShotIDCount = array();
    foreach($arRawList as $szLine)
    {
        // movie-TheGodFather-1972-dvd2008-MediaEval.mpg 1 0.00 5.52 event - violence 0.047557 t
        $arTmp = explode(' ', $szLine);
        $szVideoName = trim($arTmp[0]);
        $fShotStart = floatval($arTmp[2]);
        $fShotDuration = floatval($arTmp[3]);
        $fScore = floatval($arTmp[7]);

        if(!isset($arShotIDCount[$szVideoName]))
        {
            $arShotIDCount[$szVideoName] = 1;
        }
        $szShotID = sprintf('shot%d_%f_%f', $arShotIDCount[$szVideoName], $fShotStart, $fShotDuration);
        $arShotIDCount[$szVideoName]++;
        $arOutput[$szVideoName][$szShotID] = $fScore;

    }
    //print_r($arOutput);
    $arFinalOutput = array();
    foreach($arOutput as $szVideoID => $arTmp)
    {
        arsort($arTmp);
        $arFinalOutput[$szVideoID] = $arTmp;
    }

    //print_r($arFinalOutput);
    return $arFinalOutput;
}

?>
