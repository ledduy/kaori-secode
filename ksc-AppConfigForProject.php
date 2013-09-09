<?php

/**
 * 		@file 	ksc-AppConfigForProject.php
 * 		@brief 	Configuration file for a specific project.
 * 		Some variables will be overrided.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 09 Sep 2013.
 */

//*** Update Sep 09, 2013
// Customize for VSD2013
// Look for CHANGED FOR VSD13

// //////////////// HOW TO CUSTOMIZE /////////////////////////

// $szExpName = "mediaeval-vsd2012"; /// *** CHANGED ***
// Changes for $arPat2PathList, etc
// function getRootDirForFeatureExtraction($szFeatureExt)

// ////////////////////////////////////////////////////////////

// ///////////////// IMPORTANT PARAMS /////////////////////////

// --> for load balancing purpose, features might be stored in different servers
// function getRootDirForFeatureExtraction($szFeatureExt)

// ///////////////////////////////////////////////////////////////

// SVM configs
$gszSVMTrainApp = sprintf("libsvm291/svm-train");
$gszSVMPredictScoreApp = sprintf("libsvm291/svm-predict-score");
$gszGridSearchApp = sprintf("libsvm291/grid.py");
$gszSVMSelectSubSetApp = sprintf("libsvm291/subset.py");
$gszSVMScaleApp = sprintf("libsvm291/svm-scale");

// ////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

// The information below is mainly for feature extraction using SGE

$szExpName = "mediaeval-vsd2013"; // CHANGED FOR VSD13
$szExpConfig = $szExpName;

$szProjectCodeName = "kaori-secode-vsd2013"; // CHANGED FOR VSD13
                                            
// --> name of list of videos, i.e, metadata/keyframe-5/<pat-name.lst> = metadata/keyframe-5/tv2012.devel.lst
$arPat2PathList = array(
    "devel2013-new" => "devel2013-new",
    "test2013-new" => "test2013-new" 
); // CHANGED FOR VSD13

$nNumPats = sizeof($arPat2PathList);

// this part is for SGE - Feature Extraction
// --> dir name + path containing keyframes, i.e, keyframe-5/<path-name> = keyframe-5/tv2012/devel
$arVideoPathList = array(
    "devel2013-new",
    "test2013-new" // CHANGED FOR VSD13
);

$arMaxVideosPerPatList = array(
    "devel2013-new" => 1215, // CHANGED FOR VSD13
    "test2013-new" => 500
); // Precise:

$arMaxHostsPerPatList = array(
    "devel2013-new" => 200,
    "test2013-new" => 200 // CHANGED FOR VSD13
); 
   
// these params are used in extracting raw local features.
   // normally, one keyframe --> one raw feature file
   // therefore, we need to specify a subset of KF of one video program for one job.
/*
 * for($jk=0; $jk<$nMaxKFPerVideo; $jk+=$nNumKFPerJob) { $nStartKFID = $jk; $nEndKFID = $nStartKFID + $nNumKFPerJob;
 */

// usually set this number to SUPER MAX keyframes per video
$nMaxKFPerVideo = 100000; // CHANGED FOR VSD13
                          
// usually set this number to $nMaxKFPerVideo if all KF is processed in one job
$nNumKFPerJob = $nMaxKFPerVideo; // *** CHANGED ***
                                 
// this param is used for ksc-BOW-Quantization-SelectKeyPointsForClustering.php
                                 // if no shot case (e.g, imageclef, imagenet), it is the ave number of keyframes per video.
$nAveShotPerVideo = 27; // CHANGED FOR VSD13 32K/1.2K ~ 27
                          
// set for training --> used to find cluster centers
$arBOWDevPatList = array(
    "devel2013-new"
); // CHANGED FOR VSD13

$szSysID = "mediaeval-vsd2013"; // CHANGED FOR VSD13
$szSysDesc = "Experiments for MediaEval-VSD2013"; // CHANGED FOR VSD13
                                               
// used for codeword assignment
$arBOWTargetPatList = array(
    "devel2013-new", // CHANGED FOR VSD13
    "test2013-new" // CHANGED FOR VSD13
);

$szConfigDir = "basic";

/*
$arFilterList = array(
    ".dense4.",
    ".dense6.",
    ".dense8.",
    ".phow6.",
    ".phow8.",
    ".phow10.",
    ".phow12.",
    ".dense4mul.csift.",
    ".dense4mul.rgbsift.",
    ".dense4mul.oppsift.",
    ".dense6mul.csift.",
    ".dense6mul.rgbsift.",
    ".dense6mul.oppsift.",
    ".g_cm.",
    ".g_ch.",
    ".g_eoh.",
    ".g_lbp."
);
*/

// ////////////////// END FOR CUSTOMIZATION ////////////////////
function getRootBenchmarkMetaDataDir($szFeatureExt)
{
    global $gszRootBenchmarkDir;
    
    $szOutputDir = sprintf("%s/metadata/keyframe-5", $gszRootBenchmarkDir);
    return $szOutputDir;
}

function getRootBenchmarkFeatureDir($szFeatureExt)
{
    $szOutputDir = sprintf("%s/feature/keyframe-5", getRootDirForFeatureExtraction($szFeatureExt));
    return $szOutputDir;
}

// *** CHANGED ***
function getRootDirForFeatureExtraction($szFeatureExt)
{
    global $gszRootBenchmarkDir;
    
    $szOutputDir = $gszRootBenchmarkDir; // DEFAULT
    
    /*
     * $szRootDir1 = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2012"; makeDir($szRootDir1); $szRootDir2 = "/net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012"; makeDir($szRootDir2);
     */
    
    $szRootDir1 = $szRootDir2 = $szOutputDir;
    if (strstr($szFeatureExt, ".dense4."))
    {
        $szOutputDir = $szRootDir2;
    }
    
    if (strstr($szFeatureExt, ".dense6."))
    {
        $szOutputDir = $szRootDir2;
    }
    
    if (strstr($szFeatureExt, ".dense8."))
    {
        $szOutputDir = $szRootDir2;
    }
    
    if (strstr($szFeatureExt, ".phow8."))
    {
        $szOutputDir = $szRootDir2;
    }
    
    if (strstr($szFeatureExt, ".phow10."))
    {
        $szOutputDir = $szRootDir2;
    }
    
    if (strstr($szFeatureExt, ".phow12."))
    {
        $szOutputDir = $szRootDir2;
    }
    
    if (strstr($szFeatureExt, ".dense6mul.csift"))
    {
        
        $szOutputDir = $szRootDir1;
    }
    
    if (strstr($szFeatureExt, ".dense6mul.rgbsift"))
    {
        $szOutputDir = $szRootDir1;
    }
    
    if (strstr($szFeatureExt, ".dense6mul.oppsift"))
    {
        
        $szOutputDir = $szRootDir1;
    }
    
    if (strstr($szFeatureExt, ".dense4mul.csift"))
    {
        
        $szOutputDir = $szRootDir1;
    }
    
    if (strstr($szFeatureExt, ".dense4mul.rgbsift"))
    {
        $szOutputDir = $szRootDir1;
    }
    
    if (strstr($szFeatureExt, ".dense4mul.oppsift"))
    {
        
        $szOutputDir = $szRootDir1;
    }
    
    return $szOutputDir;
}

?>
