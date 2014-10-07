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

///net/per900c/raid0/ledduy/usr.local/bin/php -f 
///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/2014-nsc-mediaeval14-ConvertToETFnMAP2014.php 
///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014 
///net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/trackeval/MediaEval2013-objviolentscenes.groundtruth.etf 
//mediaeval-vsd-2014.devel2013-new.test2013-new 
//keyframe-5 
//test2013 
//objviolentscenes 0 @all
$phpDir = sprintf("/net/per900c/raid0/ledduy/usr.local/bin/php -f ");
$scriptDir = sprintf("/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014/2014-nsc-mediaeval14-ConvertToETFnMAP2014.php");
$rootDir = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014";
$gtfile = $_REQUEST['vGTFile']; //3
$testcfgname = $_REQUEST['vTestCfg']; //4
$prjName = $_REQUEST['vPrjName']; //5
$testPart = $_REQUEST['vTestPart']; //6
$conceptName = $_REQUEST['vConcept']; //7
$fThreshold = $_REQUEST['vThreshold']; //8
$szFeature  = $_REQUEST['vFeature']; //9
$strcmd = sprintf("%s %s %s %s %s %s %s %s %f %s",$phpDir,$scriptDir,$rootDir,$gtfile,$testcfgname,$prjName,$testPart,$conceptName,$fThreshold,$szFeature);
printf("<h3>Command = %s\n<br></h3>",$strcmd);
execSysCmd ($strcmd);
printf("<H1> Done, please back & refresh !</H1>");

?>
