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


$nAction = 0;
if(isset($_REQUEST['vAction']))
{
	$nAction = $_REQUEST['vAction'];
}

if($nAction == 0)  // Let user pick the ResultDir
{
    printf("<P><H1>DEMO - Violent Scene Detection - Test Video</H1>\n");
    
	printf("<FORM TARGET='_blank' method='POST'>\n");

	printf("<label for='file'>Filename:</label>");
	printf("<input type='file' name='file' id='file'><br>");	
	printf("<P><INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1'>\n");
	printf("<INPUT TYPE='SUBMIT' VALUE='Submit'>\n");
	printf("&nbsp;&nbsp; <INPUT TYPE='RESET' VALUE='Reset'>\n");
	printf("</FORM>\n");
	exit();
}
///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/annotation/keyframe-5/

if($nAction == 1)  // Let user pick the ModelFeatureConfig
{
	$szFile = $_REQUEST['file'];
	printf("<P><H1>DEMO - Violent Scene Detection - Test Video</H1>\n");
	printf("<H3><br>");
	printf("Test Video uploading ... <br>\n",$szFile);
	printf("Test Video uploaded: %s<br>\n",$szFile);
	printf("Please wait to process this video ... <br>");
	printf("Click <a href='http://localhost:8081/users/ledduy/lqvu-mediaeval/2013-mediaeval/webcode2013/2013-nsc-web-mediaeval13-ViewRankedListAll-byVideo.php?vVideoName=movie-TheGodFather-1972-dvd2008-MediaEval&vAction=1&vExpName=mediaeval-vsd2013-shot&vFeatureName=0_SS_2014_fusion_com_all_features_best_4_1&vConceptName=objviolentscenes&vFilter=&vPageID=0&vViewKF=View&vNumShot=50'>here</a> to view the results ! <br>");
	printf("</H3>");
	exit();
}

?>
