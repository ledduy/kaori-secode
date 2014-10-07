<?php

/**
 * 		@file 	ksc-web-ViewResult.php
 * 		@brief 	View query, groundtruth, and ranking result.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 09 Aug 2014.
 */

require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";

$arrGT = array();
$arrGT["mediaeval-vsd-2014.devel2013-new.test2013-new"]["objviolentscenes"] = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/trackeval/MediaEval2013-objviolentscenes.groundtruth.etf";
$arrGT["mediaeval-vsd-2014.devel2013-new.test2013-new"]["subjviolentscenes"] = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/trackeval/MediaEval2013-subjviolentscenes.groundtruth.etf";
$arrGT["mediaeval-vsd-2014.devel2014-new.test2014-new"]["subjviolentscenes"] = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/trackeval/MediaEval2014-subjviolentscenes.groundtruth.etf";
$arrGT["mediaeval-vsd-2014.devel2013-new.test2014-new"]["subjviolentscenes"] = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/trackeval/MediaEval2014-subjviolentscenes.groundtruth.etf";

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


$nAction = 0;
if(isset($_REQUEST['vAction']))
{
	$nAction = $_REQUEST['vAction'];
}

if($nAction == 0)  // Let user pick the ResultDir
{
    printf("<P><H1>View Results</H1>\n");
    
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
	printf("<P><H1>View Results</H1>\n");

	printf("<FORM TARGET='_self'>\n");

	printf("<P><H2>Select TestConfigName</H2>\n");
	//printf("<SELECT NAME='vTestConfigName'>\n");
	//printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szTestConfigName, $szTestConfigName);
	//printf("</SELECT>\n");
	
	printf("<SELECT NAME='vTestConfigName'>\n");
	foreach($arResultDirList as $szResultDir)
	{
		printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szResultDir, $szResultDir);
	}
	printf("</SELECT>\n");
	

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

$szConceptName = 'objviolentscenes';
if(isset($_REQUEST['vConceptName']))
{
	$szConceptName = $_REQUEST['vConceptName'];
}
if(isset($_REQUEST['vFilter']))
{
	$szFilter = $_REQUEST['vFilter'];
}

$szResultDir = sprintf("%s/%s", $szRootResultDir, $szTestConfigName);
$arModelFeatureDirList = collectDirsInOneDir($szResultDir,$szFilter);
///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/result/keyframe-5/mediaeval-vsd-2014.devel2013-new.test2013-new/nsc.bow.dense6mul.sift.Soft-1000.devel2011-new.L1norm1x1.shotMAX.R11/objviolentscenes.map2014
$arMap2014 = array();
$arMapat100 = array();
$arMapat100_2013 = array();
$arMap_2013 = array();
foreach($arModelFeatureDirList as $szModelFeatureDir)
{
	$szMap2014FN = sprintf("%s/%s/%s/%s.map2014",$szRootResultDir,$szTestConfigName,$szModelFeatureDir,$szConceptName);
	$szMap2013FN = sprintf("%s/%s/%s/%s_old.map2013",$szRootResultDir,$szTestConfigName,$szModelFeatureDir,$szConceptName);
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
	}
	if (file_exists($szMap2013FN))
	{
		$nNumRows = loadListFile($arTmpz, $szMap2013FN);
		$fMAP2013old = floatval($arTmpz[$nNumRows-1]);
		$fMAP1002013old = floatval($arTmpz[$nNumRows-2]);
		$arMap_2013[$szModelFeatureDir] = $fMAP2013old;
		$arMapat100_2013[$szModelFeatureDir] = $fMAP1002013old;
	} 	else
	{
		$arMapat100_2013[$szModelFeatureDir] = -1;
		$arMap_2013[$szModelFeatureDir] = -1;
	}
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
	$szFilter = $_REQUEST['vFilter'];
	
	printf("<P><H1>View Results</H1>\n");

	printf("<FORM TARGET='_self'>\n");

	printf("<P><H2>Select TestConfigName: %s</H2>\n",$szTestConfigName);
	//printf("<SELECT NAME='vTestConfigName'>\n");
	//printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szTestConfigName, $szTestConfigName);
	//printf("</SELECT>\n");
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
	
	

	printf("<P><H2>Select ConceptName : %s</H2>\n",$szConceptName);
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
	
	printf("<P><H2>List of ModelFeatureConfigName</H2>\n");
	printf("<P><H3>Note: MAP2014: segment-based, MAP2014</H3>\n");
	printf("<P><H3>Note: MAP@100(2014): segment-based, MAP@100, equal shots</H3>\n");
	printf("<P><H3>Note: MAP@100(2013): shot-based, MAP@100, convet to Shot boundaries</H3>\n");
	printf("<P><H3>Note: MAP(2013): shot-based, MAP, convet to Shot boundaries</H3>\n");
	//printf("<P><H4>(Value = -1 ~ Not Available)</H4>\n");
	//printf("<SELECT NAME='vModelFeatureConfigName'>\n");
	/* foreach($arModelFeatureDirList as $szModelFeatureDir)
	 {
	//printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szModelFeatureDir, $szModelFeatureDir);
	printf("<INPUT TYPE='CHECKBOX' NAME='vFeatureList[]' VALUE='%s'>%s - MAP: [N/A]</BR>\n",$szModelFeatureDir,$szModelFeatureDir);
	} */
	//printf("</SELECT>\n");
	
	/* foreach($arMap2014 as $key => $fValue)
	 {
	//printf("<OPTION VALUE='%s'>%s</OPTION>\n", $szModelFeatureDir, $szModelFeatureDir);
	printf("<INPUT TYPE='CHECKBOX' NAME='vFeatureList[]' VALUE='%s'>%s - MAP2014: [%f]</BR>\n",$key,$key,$fValue);
	} */
	//$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https');
	/* 	$host     = $_SERVER['HTTP_HOST'];
	 $script   = $_SERVER['SCRIPT_NAME'];
	//$params   = $_SERVER['QUERY_STRING'];
	$currentUrl = sprintf("http://%s/%s?vTestConfigName=%s&vConceptName=%s&vAction=2",$host,$script,$szTestConfigName,$szConceptName);
	
	$strSortbyName = $currentUrl.'&vSortby=1';
	$strSortbyMap = $currentUrl.'&vSortby=2';
	
	$szSortby = intval($_REQUEST['vSortby']);
	$arResult = array();
	if ($szShotby==2)
	{
	$arResult = arsort($arMap2014);
	printf("<h3>Sorted by MAP2014</h3>");
	} else
	{
	$arResult = ksort($arMap2014);
	printf("<h3>Sorted by Feature Configs</h3>");
	}  */
	
	printf("<table border=1>");
	printf("<tr>");
	printf("	<td align='center'>No.</td>");
	//printf("	<td align='center'><A HREF='%s' target='_self'>Feature Configs</A></td>",$strSortbyName);
	//printf("	<td><A HREF='%s' target='_self'>MAP2014</A></td>",$strSortbyMap);
	printf("	<td align='center'>Feature Configs</td>");
	
	$gtfile = $arrGT[$szTestConfigName][$szConceptName]; //$_REQUEST['vGTFile']; //3
	$testcfgname = $szTestConfigName; //4
	$prjName = "keyframe-5"; //5
	$testPart = $arrPart[$szTestConfigName]; //6
	$conceptName = $szConceptName; //7
	$fThreshold = 0; //8
	$szFeature  = "@all"; //9
	$strLink = sprintf("ksc-web-CountMAPResult.php?vGTFile=%s&vTestCfg=%s&vPrjName=%s&vTestPart=%s&vConcept=%s&vThreshold=%f&vFeature=%s",$gtfile,$testcfgname,$prjName,$testPart,$conceptName,$fThreshold,$szFeature);
	if ($gtfile<>""){
		printf("	<td><A href=%s target='_blank'>MAP2014</A></td>",$strLink);
		printf("	<td><A href=%s target='_blank'>MAP@100(2014)</A></td>",$strLink);
		printf("	<td>MAP@100(2013)</td>");
		printf("	<td>MAP(2013)</td>");
	}
	else {
		printf("	<td>MAP2014</td>");
		printf("	<td>MAP@100(2014)</td>");
		printf("	<td>MAP(2013)</td>");
	}
	
	//printf("	<td>MAP2014</td>");
	printf("</tr>");
	
	
	arsort($arMap2014);
	$i=1;
	$szConceptName =  $_REQUEST['vConceptName'];
	$szTestConfigName =$_REQUEST['vTestConfigName'];
	foreach($arMap2014 as $key => $fValue)
	{
		printf("<tr>");
		//printf("	<td><INPUT TYPE='CHECKBOX' NAME='vFeatureList[]' VALUE='%s'></td>",$key);
		printf("	<td>%d</td>",$i);
		$i++;
		//http://localhost:8081/users-ext/ledduy//www/kaori-secode-vsd2014/ksc-web-ViewResult.php?vTestConfigName=mediaeval-vsd-2014.devel2013-new.test2013-new&vModelFeatureConfigName=nsc.bow.dense6mul.csift.Soft-1000.devel2011-new.L1norm1x1.shotMAX.R11&vConceptName=objviolentscenes&vAction=3
		$strLink = sprintf("ksc-web-ViewResult.php?vTestConfigName=%s&vModelFeatureConfigName=%s&vConceptName=%s&vAction=3",$szTestConfigName,$key,$szConceptName);
		printf("	<td><A href=%s target='_blank'>%s</A></td>",$strLink,$key);
		if ($fValue==-1)
		{
			///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/result/keyframe-5/mediaeval-vsd-2014.devel2013-new.test2013-new/nsc.bow.dense6mul.rgbsift.Soft-1000.devel2011-new.L1norm1x1.shotAVG.R11/objviolentscenes.rank
			///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/result/keyframe-5/mediaeval-vsd-2014.devel2013-new.test2013-new/mediaeval-vsd2013-shot.idensetraj.hoghofmbh.cb256.fc.pca.l2.R11/objviolentscenes.rank
			$ranklistfile = sprintf("%s/%s/%s/%s.rank",$szRootResultDir,$szTestConfigName,$key,$szConceptName);
			$gtfile = $arrGT[$szTestConfigName][$szConceptName]; //$_REQUEST['vGTFile']; //3
			$testcfgname = $szTestConfigName; //4
			$prjName = "keyframe-5"; //5
			$testPart = $arrPart[$szTestConfigName]; //6
			$conceptName = $szConceptName; //7
			$fThreshold = 0; //8
			$szFeature  = $key; //9
			$strLink = sprintf("ksc-web-CountMAPResult.php?vGTFile=%s&vTestCfg=%s&vPrjName=%s&vTestPart=%s&vConcept=%s&vThreshold=%f&vFeature=%s",$gtfile,$testcfgname,$prjName,$testPart,$conceptName,$fThreshold,$szFeature);
			if (($gtfile<>"")  && (filesize($ranklistfile)>0) ){
				printf("	<td><A href=%s target='_blank'>N/A</A></td>",$strLink);
				printf("	<td><A href=%s target='_blank'>N/A</A></td>",$strLink);
				printf("	<td>N/A</td>",$strLink);
				printf("	<td>N/A</td>",$strLink);
			}
			else {
				printf("	<td>N/A</td>");
				printf("	<td>N/A</td>");
				printf("	<td>N/A</td>");
				printf("	<td>N/A</td>");
			}
		}
		else
		{
			printf("	<td>%0.6f</td>",$fValue);
			printf("	<td>%0.6f</td>",$arMapat100[$key]);
			if ($arMapat100_2013[$key]>0)
				printf("	<td>%0.6f</td>",$arMapat100_2013[$key]);
			else 
				printf("	<td>N/A</td>");
			if ($arMap_2013[$key]>0)
				printf("	<td>%0.6f</td>",$arMap_2013[$key]);
			else
				printf("	<td>N/A</td>");
				
			
		}
		printf("</tr>");
	}
	
	
	printf("</table>");
}

//printf("Done %s",$currentUrl);
exit; 

?>
