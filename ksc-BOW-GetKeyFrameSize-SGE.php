<?php

/**
 * 		@file 	ksc-BOW-GetKeyFrameSize-SGE.php
 * 		@brief 	Generate jobs for SGE to get keyframe size.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Jul 2013.
 */

// Update Aug 01
// Customize for tv2011

// ////////////////////////////////////
// Update Jun 17
// Copied from nsc-ExtractKeyFrame-SGE.php

// ///////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

// ////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

$szProjectCodeName = "kaori-secode-bow-test";
$szCoreScriptName = "ksc-BOW-GetKeyFrameSize";

// $szSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-TVSIN11";
$szSGEScriptDir = $gszSGEScriptDir; // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

// ////////////////// END FOR CUSTOMIZATION ////////////////////

// /////////////////////////// MAIN ////////////////////////////////
$arPatList = array(
    // "devel-nistNew" => 500,
    "test.iacc.2.ANew" => 300,
    "test.iacc.2.BNew" => 300,
    "test.iacc.2.CNew" => 300
);

$nMaxHostsPerPat = 20;

$szFPLogFN = "/dev/null";

foreach ($arPatList as $szPatName => $nMaxVideosPerPat)
{
    $nNumVideosPerHost = intval($nMaxVideosPerPat / $nMaxHostsPerPat);
    
    for ($j = 0; $j < $nMaxVideosPerPat; $j += $nNumVideosPerHost)
    {
        $nStart = $j;
        $nEnd = $nStart + $nNumVideosPerHost;
        
        $szParam = sprintf("%s %s %s", $szPatName, $nStart, $nEnd);
        
        $szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
        execSysCmd($szCmdLine);
        sleep(1);
    }
}

?>
