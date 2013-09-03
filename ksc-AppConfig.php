<?php

/**
 * 		@file 	ksc-AppConfig.php
 * 		@brief 	Configuration file for KAORI-SECODE App.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 04 Jul 2013.
 */

// //////////////// HOW TO CUSTOMIZE /////////////////////////
// New on Sep 02, 2013
// $nUseTarFileForKeyFrame
// $nUseL1NormBoW

// --> Look for *** CHANGED *** and make appropriate changes
// $gszRootBenchmarkDir = "/net/sfv215/export/raid4/ledduy/lqvu-Experiments/2012/MediaEval2012"; // *** CHANGED ***
// $gszRootBenchmarkExpDir = $gszRootBenchmarkDir; --> change it if experiment dir is on different server for load balancing.
// $gszSGEScriptDir = "/net/per900b/raid0/ledduy/kaori-secode/php-DemoV1-MediaEval12"; // *** CHANGED ***
// $gszTmpDir = "/net/dl380g7a/export/ddn11a6/ledduy/tmp"; // *** CHANGED ***

// --> max frame size: 400x400
// $gnHavingResized = 1;
// $gnMaxFrameWidth = 400; // *** CHANGED ***
// $gnMaxFrameHeight = 400; // *** CHANGED ***

// ////////////////////////////////////////////////////////////

// ///////////////// IMPORTANT PARAMS /////////////////////////

// --> max frame size: 400x400
// $gnHavingResized = 1;
// $gnMaxFrameWidth = 400; // *** CHANGED ***
// $gnMaxFrameHeight = 400; // *** CHANGED ***

// --> BOW params -- SHOULD NOT CHANGE
// $nNumClusters = 500;
// $szKMeansMethod = 'elkan';

// $szTrialName = sprintf("Soft-%d-VL2", $nNumClusters);
// printf("### Trial Name: [%s]\n", $szTrialName);

// $nMaxCodeBookSize = $nNumClusters*2;

// --> kaori-lib, libsvm291 now are subdirs

// ///////////////////////////////////////////////////////////////

// ///////////////////////////////////////////////////////////////
// Import kaori-lib tools
require_once "kaori-lib/kl-AppConfig.php";
require_once "kaori-lib/kl-IOTools.php";
require_once "kaori-lib/kl-MiscTools.php";
require_once "kaori-lib/kl-DataProcessingTools.php";
require_once "kaori-lib/kl-SVMTools.php";

// Global vars

$gnPerformDataScaling = 1;
// this is used for csv-style file
$gszDelim = "#$#";

// LUT for annotaton data used in collaborative annotation and NIST ground truth
$garLabelList = array(
    "P" => "Pos",
    "N" => "Neg",
    "S" => "Skipped"
);
$garInvLabelList = array(
    "Pos" => "P",
    "Neg" => "N",
    "Skipped" => "S"
);
$garLabelValList = array(
    1 => "Pos",
    - 1 => "Neg",
    0 => "Skipped"
);
$garInvLabelValList = array(
    "Pos" => 1,
    "Neg" => - 1
);
$garLabelMapList = array(
    1 => "P",
    - 1 => "N",
    0 => "S"
);
$gszPosLabel = "Pos";
$gszNegLabel = "Neg";

// SVM configs
$gszSVMTrainApp = sprintf("libsvm291/svm-train");
$gszSVMPredictScoreApp = sprintf("libsvm291/svm-predict-score");
$gszGridSearchApp = sprintf("libsvm291/grid.py");
$gszSVMSelectSubSetApp = sprintf("libsvm291/subset.py");
$gszSVMScaleApp = sprintf("libsvm291/svm-scale");

// Will be overriden later
$gfPosWeight = 1000;
$gfNegWeight = 1;
$gnMemSize = 1000;
$gnStartC = 0;
$gnEndC = 6;
$gnStepC = 2;
$gnStartG = - 20;
$gnEndG = 0;
$gnStepG = 2;

$gszFeatureFormat = "dvf";

// Dir for feature config files --> GLOBAL features
$gszFeatureConfigDir = "BaselineFeatureConfig";

// ////////////////// THIS PART FOR CUSTOMIZATION ////////////////////
$gnUseTarFileForKeyFrame = 0; // whether to pack keyframes in .tar files
                             
// Root of a benchmark, e.g. trecvid-sin-2011, trecvid-med-2011, imageCLEF, ImageNet
                             // $gszRootBenchmarkDir = "/net/per610a/export/das09f/satoh-lab/ledduy/ImageCLEF/2012/PhotoAnnFlickr"; // *** CHANGED ***

$gszRootBenchmarkDir = "/net/sfv215/export/raid6/ledduy/ImageCLEF/2012/PhotoAnnFlickr"; // update 01 Sep 2013
                                                                                        
// Dir for php code
                                                                                        // just copy from local dir (c:\Users\ledduy\git\kaori-secode), NOT check out
$gszSGEScriptDir = "/net/per900b/raid0/ledduy/github-projects/kaori-secode-bow-test"; // *** CHANGED ***
                                                                                      
// *** SHOULD NOT CHANGE *****
                                                                                      // Dir for .sh script
$gszScriptBinDir = "/net/per900b/raid0/ledduy/bin13/bin-bow-test";
makedir($gszScriptBinDir);

// feature extraction app
$garAppConfig["BL_FEATURE_EXTRACT_APP"] = "FeatureExtractorCmd/FeatureExtractorCmd";

// UvA's color descriptor code
$garAppConfig["RAW_COLOR_SIFF_APP"] = "colordescriptor30/x86_64-linux-gcc/colorDescriptor ";

// VLFEAT
$garAppConfig["RAW_VLFEAT_DIR"] = "vlfeat-0.9.14"; // --> move to subdir
                                                   
// Oxford VGG's code
$garAppConfig["RAW_AFF_COV_SIFF_APP"] = "aff.cov.sift/extract_features_64bit.ln";

$garAppConfig["SASH_KEYPOINT_TOOL_BOW_L2_APP"] = "sashKeyPointTool/sashKeyPointTool-nsc-BOW-L2";

// TmpDir --> IMPORTANT - Update on Sep 02, 2013
$gszTmpDir = "/local/ledduy"; // must INCLUDE benchmark name
if (! file_exists($gszTmpDir))
{
    $gszTmpDir = "/net/dl380g7a/export/ddn11a6/ledduy/tmp/kaori-secode-bow-test"; // *** CHANGED ***
    makeDir($gszTmpDir);
}

// !!! IMPORTANT PARAMS !!!
// used with BOW features
$gnHavingResized = 1;
$gnMaxFrameWidth = 500; // *** CHANGED ***
$gnMaxFrameHeight = 500; // *** CHANGED ***
$gszResizeOption = sprintf("-resize '%sx%s>'", $gnMaxFrameWidth, $gnMaxFrameHeight); // to ensure W is the width after shrinking
                                                                                     
// / !!! IMPORTANT PARAM !!!
$nNumClusters = 500;
$szKMeansMethod = 'elkan';

$szTrialName = sprintf("Soft-%d", $nNumClusters);
// printf("### Trial Name: [%s]\n", $szTrialName);

$nMaxCodeBookSize = $nNumClusters * 2;
$nUseL1NormBoW = 1;

// ////////////////// END FOR CUSTOMIZATION ////////////////////

require_once "ksc-AppConfigForProject.php";

// ------------------
// /////////////////////////////////// HISTORY ///////////////////////////
// ------------------------------------------------------------------------------------
// JUL 03, 2012 --> starting date for TRECVID-SIN 2012
// *** Update Jul 07, 2012
// Create bin dir, and move external apps into bin dir

// *** Update Jul 03, 2012
// Customize for TRECVID-SIN12
// --> CHANGE benchmark dir,
// --> CHANGE maxW x maxH for resized keyframes.

// *** Update Jun 26, 2012
// Customize for ImageCLEF12

// Update Nov 23, 2011
// *** kaori-lib, libsvm291 now are subdirs ***
// change name to ksc-ZZZ

// ///////////////////////////////////////////////////////////////
// Update Nov 25 2010
// $gszFeatureFormat = "dvf"; --> used for supporting both dvf and svf in train and test

// Update Oct 01
// Remove some unused parts

// exit("Here");
?>