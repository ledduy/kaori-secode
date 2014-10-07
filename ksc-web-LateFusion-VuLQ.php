 <script language="JavaScript">
function toggle(source) {
	checkboxes = document.getElementsByName('vFeatureList[]');
	  for(var i=0, n=checkboxes.length;i<n;i++) {
	    checkboxes[i].checked = source.checked;
	  }
	}
</script>
<?php

/**
 * 		@file 	ksc-web-ViewResult.php
 * 		@brief 	Create fusion config file, run fusion.
 *		@author Lam Quang Vu.
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.q
 * 		Last update	: 13 Sep 2014.
 */

require_once "ksc-AppConfig.php";

$arrGT = array();
$arrGT["mediaeval-vsd-2014.devel2013-new.test2013-new"]["objviolentscenes"] = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/trackeval/MediaEval2013-objviolentscenes.groundtruth.etf";
$arrGT["mediaeval-vsd-2014.devel2013-new.test2013-new"]["subjviolentscenes"] = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/trackeval/MediaEval2013-subjviolentscenes.groundtruth.etf";
$arrGT["mediaeval-vsd-2014.devel2014-new.test2014-new"]["subjviolentscenes"] = "";
$arrGT["mediaeval-vsd-2014.devel2013-new.test2014-new"]["subjviolentscenes"] = "";

$arrPart = array();
$arrPart["mediaeval-vsd-2014.devel2013-new.test2013-new"]="test2013";
$arrPart["mediaeval-vsd-2014.devel2014-new.test2014-new"]="test2014";
$arrPart["mediaeval-vsd-2014.devel2013-new.test2014-new"]="test2014";

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;

////////////////// START //////////////////

//$szRootExpDir  = $szRootDir
$szRootExpDir = sprintf("%s", $gszRootBenchmarkExpDir);

$szRootResultDir = sprintf("%s/result/keyframe-5", $szRootExpDir); // dir containing prediction result of RUNs
$arResultDirList = collectDirsInOneDir($szRootResultDir);

$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootExpDir); // dir containing keyframes


$szExpMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootExpDir); // --> get test-pat

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

$nAction = 0;
if(isset($_REQUEST['vAction']))
{
	$nAction = $_REQUEST['vAction'];
}

if($nAction == 0)  // Let user pick the ResultDir
{
    printf("<P><H1>Fusion Ranklist</H1>\n");
    
	printf("<FORM TARGET='_self'>\n");

	printf("<P><H2>Select TestConfigName</H2>\n");
	printf("<SELECT NAME='vTestConfigName'>\n");
	foreach($arResultDirList as $szResultDir)
	{
		printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szResultDir, $szResultDir);
	}
	printf("</SELECT>\n");

	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}
$szTestConfigName = 'mediaeval-vsd-2014.devel2013-new.test2013-new';

if(isset($_REQUEST['vTestConfigName']))
{
	$szTestConfigName = $_REQUEST['vTestConfigName'];
}

$arConceptList = array("objviolentscenes","subjviolentscenes");

if($nAction == 1)  // Let user pick the ModelFeatureConfig
{
	printf("<P><H1>Fusion Ranklist</H1>\n");

	printf("<FORM TARGET='_self'>\n");

	printf("<P><H2>Select TestConfigName</H2>\n");
	//printf("<SELECT NAME='vTestConfigName'>\n");
	//printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szTestConfigName, $szTestConfigName);
	//printf("</SELECT>\n");
	
	printf("<P><H2>Select TestConfigName: %s</H2>\n",$szTestConfigName);
	printf("<SELECT NAME='vTestConfigName'>\n");
	foreach($arResultDirList as $szResultDir)
	{
		if ($szResultDir==$szTestConfigName)
			printf("<OPTION VALUE='%s' selected='true'>%s</OPTION>\n", $szResultDir, $szResultDir);
		else 
		{
			printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szResultDir, $szResultDir);
		}
		
	}
	printf("</SELECT>\n");
	
	
	/*printf("<SELECT NAME='vTestConfigName'>\n");
	foreach($arResultDirList as $szResultDir)
	{
		printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szResultDir, $szResultDir);
	}
	printf("</SELECT>\n");*/
	

	printf("<P><H2>Select ConceptName</H2>\n");
	printf("<SELECT NAME='vConceptName'>\n");
	foreach($arConceptList as $szConceptName)
	{
		printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szConceptName, $szConceptName);
	}
	printf("</SELECT>\n");
	
	printf("<H2>Filter <INPUT TYPE=TEXT NAME='vFilter' VALUE='' SIZE=50></H2>");
	
	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='2'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}

$szTestConfigName = 'mediaeval-vsd-2014.devel2013-new.test2013-new';

if(isset($_REQUEST['vTestConfigName']))
{
	$szTestConfigName = $_REQUEST['vTestConfigName'];
}

if(isset($_REQUEST['vFilter']))
{
	$szFilter = $_REQUEST['vFilter'];
}

$szConceptName = 'objviolentscenes';
if(isset($_REQUEST['vConceptName']))
{
	$szConceptName = $_REQUEST['vConceptName'];
}

$szResultDir = sprintf("%s/%s", $szRootResultDir, $szTestConfigName);
$arModelFeatureDirList = collectDirsInOneDir($szResultDir,$szFilter);
///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/result/keyframe-5/mediaeval-vsd-2014.devel2013-new.test2013-new/nsc.bow.dense6mul.sift.Soft-1000.devel2011-new.L1norm1x1.shotMAX.R11/objviolentscenes.map2014
sort($arModelFeatureDirList);
$arMap2014 = array();
$arMapat100 = array();
foreach($arModelFeatureDirList as $szModelFeatureDir)
{
	$szMap2014FN = sprintf("%s/%s/%s/%s.map2014",$szRootResultDir,$szTestConfigName,$szModelFeatureDir,$szConceptName);
	if (file_exists($szMap2014FN))
	{
		$nNumRows = loadListFile($arTmpz, $szMap2014FN);
		$fMAP2014 = floatval($arTmpz[$nNumRows-2]);
		$fMAP2013 = floatval($arTmpz[$nNumRows-1]);
		$arMap2014[$szModelFeatureDir] = $fMAP2014;
		$arMapat100[$szModelFeatureDir] = $fMAP2013;
	} 	else 
	{
		$arMap2014[$szModelFeatureDir] = -1;
		$arMapat100[$szModelFeatureDir] = -1;
	};
}

/*
	printf("<SELECT NAME='vModelFeatureConfigName'>\n");
	printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szModelFeatureConfigName, $szModelFeatureConfigName);
	printf("</SELECT>\n");
*/	
	
if($nAction == 2)  // Let user pick the ModelFeatureConfig
{
	$szConceptName =  $_REQUEST['vConceptName'];
	$szTestConfigName =$_REQUEST['vTestConfigName'];
	
	printf("<P><H1>Fusion Ranklist</H1>\n");

	printf("<FORM TARGET='_self'>\n");

	printf("<P><H2>Select TestConfigName: %s</H2>\n",$szTestConfigName);
//	printf("<P><INPUT TYPE='HIDDEN' NAME='vTestConfigName' VALUE=%s>\n",$szTestConfigName);
	printf("<SELECT NAME='vTestConfigName'>\n");
	printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szTestConfigName, $szTestConfigName);
	printf("</SELECT>\n");
	
	printf("<P><H2>Select ConceptName : %s</H2>\n",$szConceptName);
	printf("<P><INPUT TYPE='HIDDEN' NAME='vConceptName' VALUE=%s>\n",$szConceptName);
	//printf("<SELECT NAME='vConceptName'>\n");
	//printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szConceptName, $szConceptName);
	//printf("</SELECT>\n");
	printf("<SELECT NAME='vConceptName'>\n");
	foreach($arConceptList as $sztmpConceptName)
	{	if ($sztmpConceptName==$szConceptName)
		{
			printf("<OPTION VALUE='%s' selected='true'>%s</OPTION>\n", $sztmpConceptName, $sztmpConceptName);
		} else 
		{
			printf("<OPTION VALUE='%s'>%s</OPTION>\n", $sztmpConceptName, $sztmpConceptName);
		}
	}
	printf("</SELECT>\n");
	
	printf("<H2>Filter <INPUT TYPE=TEXT NAME='vFilter' VALUE='%s' SIZE=50></H2>",$szFilter);
	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='2'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	
	printf("</FORM>\n");
	
	
	printf("<FORM TARGET='_blank'  method='POST'>\n");
	
	printf("<P><INPUT TYPE='HIDDEN' NAME='vConceptName' VALUE=%s>\n",$szConceptName);
	printf("<P><INPUT TYPE='HIDDEN' NAME='vTestConfigName' VALUE=%s>\n",$szTestConfigName);
	
	printf("<P><H2>List of ModelFeatureConfigName</H2>\n");
	
	printf("<table border=1>");
	printf("<tr>");
	printf("	<td align='center'>No.</td>");
	printf("	<td><input type='CHECKBOX' onClick='toggle(this)'><br></td>");
	printf("	<td align='center'>Feature Configs</td>");
	printf("	<td align='center'>Weight</td>");
	printf("</tr>");
	
	$nCount=1;
	foreach($arModelFeatureDirList as $szModelFeatureDir)
	{
		printf("<tr>");
		printf("<td>%d",$nCount);
		printf("</td>");
		printf("<td>");
		printf("	<INPUT TYPE='CHECKBOX' NAME='vFeatureList[]' VALUE='%s'></BR>\n",$szModelFeatureDir);
		printf("<INPUT TYPE='HIDDEN' NAME='vFeaturesID[%s]' VALUE='%d'>\n",$szModelFeatureDir,$nCount-1);
		
		printf("</td>");
		printf("<td>%s",$szModelFeatureDir);
		printf("</td>");
		printf("	<td><INPUT TYPE='TEXT' NAME='vWeight[]' VALUE='1' SIZE=3></td>");
		printf("</tr>");		
		$nCount++;
	}
	printf("</table>");
	
	printf("<H2>Output Config Name (fusion_ will be prefix added auto):<INPUT TYPE=TEXT NAME='vCfgName' VALUE='%s' SIZE=50></H2>",$szFilter);
	
	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='3'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	
}

if($nAction == 3)  // Let user pick the ModelFeatureConfig
{
	$rootFusionCfg = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/fusionlist";
	if ($_REQUEST['vCfgName']=='')
	{
		printf("<H1>Please back and specify the fusion name !</H1>");
		exit;
	}
	$szConceptName =  $_REQUEST['vConceptName'];
	$szTestConfigName =$_REQUEST['vTestConfigName'];
	$szFilter = $_REQUEST['vFilter'];
	
	
	printf("<P><H1>Create Fusion Ranklist</H1>\n"); 
	
	printf("<FORM TARGET='_blank'>\n");
	printf("<P><INPUT TYPE='HIDDEN' NAME='vTestConfigName' VALUE=%s>\n",$szTestConfigName);
	printf("<P><INPUT TYPE='HIDDEN' NAME='vConceptName' VALUE=%s>\n",$szConceptName);
	printf("<P><INPUT TYPE='HIDDEN' NAME='vFilter' VALUE=%s>\n",$szFilter);
	
	$arRunList = $_REQUEST['vFeatureList'];
	$arFeaturesID = $_REQUEST['vFeaturesID'];
	$arWeight = $_REQUEST['vWeight'];
	//
	print_r($arWeight);
	print_r($arRunList);
	print_r($arFeaturesID);
	
	$szFusionName = sprintf("fusion_%s",$_REQUEST['vCfgName']);
	
	printf("Fusion list configs: %s.lst\n<br>",$szFusionName);
	$szFusionCfgName = sprintf("%s/%s.lst",$rootFusionCfg,$szFusionName);
	$fcfg = fopen($szFusionCfgName,"w");
	
	$nFeatures = sizeof($arRunList);
	for ($i=0;$i<$nFeatures;$i++)
	{
		if(!isset($arFeaturesID[$arRunList[$i]]))
		{
			$nWeight = 1;
		}
		else
		{
			$nWeight=$arWeight[$arFeaturesID[$arRunList[$i]]];
		}
		$strtmp = sprintf("%s #$# %d\n",$arRunList[$i],$nWeight);
		printf("%s<br>\n",$strtmp);
		fwrite($fcfg,$strtmp);
	} 
	fclose($fcfg);
	printf("<P><INPUT TYPE='HIDDEN' NAME='vRunFusionFile' VALUE=%s>\n",$szFusionCfgName);
	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='4'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Run fusion'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	//printf("Click here to run fusion: %s.lst\n<br>",$szFusionName);
}
if($nAction == 4)  // Let user pick the ModelFeatureConfig
{
	$rootFusionCfg = $szRootResultDir;
	
	// fuse all concepts ONCE
	foreach($arConceptList as $szConceptName)
	{
	//	$szConceptName =  $_REQUEST['vConceptName']; NOT USED
		$szTestConfigName =$_REQUEST['vTestConfigName'];
		$szFusionList = $_REQUEST['vRunFusionFile'];
		$szFilter = $_REQUEST['vFilter'];
		$szCmd = "/net/per900c/raid0/ledduy/usr.local/bin/php -f  /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/2014-nsc-mediaeval14-FusionOnRanklist.php";
		$szRootDir = sprintf("%s/%s",$szRootResultDir,$szTestConfigName);
		$strcmd = sprintf("%s %s %s %s",$szCmd,$szRootDir,$szConceptName,$szFusionList);
		execSysCmd ($strcmd);
		
		$szConfigFeatureName = CutExt(CutPath($szFusionList));
		
		$szDestDir = sprintf("%s/%s/%s", $szRootResultDir,$szTestConfigName,$szConfigFeatureName);
		makeDir($szDestDir);
		$szCmdLine = sprintf("chmod 777 %s", $szDestDir);
		system($szCmdLine);
		
		$szCmdLine = sprintf("cp %s %s/", $szFusionList, $szDestDir);
		system($szCmdLine);
		
		$szFileRank = sprintf("%s/%s/%s/%s.rank",$szRootResultDir,$szTestConfigName,$szConfigFeatureName,$szConceptName);
		printf("<br><br> File rank = %s\n",$szFileRank);
		if (file_exists($szFileRank))
		{
			$strlink = sprintf("ksc-web-ViewMAP2014.php?vTestConfigName=%s&vConceptName=%s&vFilter=%s&vAction=2",$szTestConfigName,$szConceptName,$szConfigFeatureName);
			printf("<H2> Fusion Done, click  <a href='%s' target='_blank'> here </a> to view results</H2>",$strlink);
			
			$szCmdLine = sprintf("chmod 777 %s", $szFileRank);
			system($szCmdLine);
		}
		else
		{
			//http://localhost:8081/users-ext/ledduy//www/kaori-secode-vsd2014/ksc-web-fusionweb.php?vTestConfigName=mediaeval-vsd-2014.devel2013-new.test2013-new&vConceptName=objviolentscenes&vFilter=fusion&vAction=2
			$strlink = sprintf("ksc-web-fusionweb.php?vTestConfigName=%s&vConceptName=%s&vFilter=%s&vAction=2",$szTestConfigName,$szConceptName,$szFilter);
			printf("<H2> Fusion failed, click <a href='%s' target='_blank'> here </a> to create fusion rank list again ! </H2>",$strlink);
		}
	}
}
//net/per900c/raid0/ledduy/usr.local/bin/php -f  /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/2014-nsc-mediaeval14-FusionOnRanklist.php 
//net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/result/keyframe-5/mediaeval-vsd-2014.devel2013-new.test2013-new 
//objviolentscenes 
//net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/fusionranklist_alllocal.lst
exit; 

?>
