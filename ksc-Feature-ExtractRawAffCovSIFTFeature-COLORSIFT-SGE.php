<?php

/**
 * 		@file 	ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT-SGE.php
 * 		@brief 	Generate jobs for SGE to generate features.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */

/**
 * IMPORTANT NOTES:
 *
 * 1. Check the config file to be loaded in ksc-AppConfig.
 * 2. Check the feature list file ($szFPFeatureListFN = sprintf("%s/FeatureList.%s.lst", $szDetSysDir, $szSysName);)
 * 3. Check the concept list file ($szFPConceptListFN = sprintf("%s/TRECVIDRefConcepts.%s.lst", $szDetSysDir, $szSysName);)
 */

// *** Update Jul 05, 2012
// Customize for TVSIN2012

// Update Jun 26, 2012
// Customize for imageclef2012

// /////////////////////////////////////////////////////////////////
// Update Jul 04
// Copied from nsc-Feature-ExtractRawAffCovSIFTFeature-SGE.php

// /////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

// ////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

$szProjectCodeName = "kaori-secode-tvsin13"; // *** CHANGED ***
$szCoreScriptName = "ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT"; // *** CHANGED ***

$szSGEScriptDir = $gszSGEScriptDir; // defined in ksc-AppConfig

$szSGEScriptName = sprintf("%s.sgejob.sh", $szCoreScriptName);
$szFPSGEScriptName = sprintf("%s/%s", $szSGEScriptDir, $szSGEScriptName);

$szScriptBinDir = $gszScriptBinDir;
$szRootScriptOutputDir = sprintf("%s/%s/%s", $szScriptBinDir, $szProjectCodeName, $szCoreScriptName);
makeDir($szRootScriptOutputDir);

$arFeatureList = array(
    "nsc.raw.dense6mul.sift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor sift", // dense sampling, multi scale
    "nsc.raw.dense6mul.csift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor csift",
    "nsc.raw.dense6mul.rgsift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor rgsift",
    "nsc.raw.dense6mul.rgbsift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor rgbsift",
    "nsc.raw.dense6mul.oppsift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor opponentsift",
    
    "nsc.raw.dense4mul.sift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor sift", // dense sampling, multi scale
    "nsc.raw.dense4mul.csift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor csift",
    "nsc.raw.dense4mul.rgsift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor rgsift",
    "nsc.raw.dense4mul.rgbsift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor rgbsift",
    "nsc.raw.dense4mul.oppsift" => "--detector densesampling --ds_spacing 4 --ds_scales 1.2+2.0 --descriptor opponentsift",
    
    "nsc.raw.harlap6mul.rgbsift" => "--detector harrislaplace --descriptor rgbsift",

    // 6+1 scales (scale factor = sqrt(2) = 1.41)
    "nsc.raw.dense6mul7.sift" => "--detector densesampling --ds_spacing 6 --ds_scales 1.2+1.7+2.4+3.4+4.8+6.8+9.6 --descriptor sift", // dense sampling, multi scale
);

// ////////////////// END FOR CUSTOMIZATION ////////////////////

// /////////////////////////// MAIN ////////////////////////////////

foreach ($arFeatureList as $szFeatureExt => $szFeatureConfigParam)
{
    $szScriptOutputDir = sprintf("%s/%s", $szRootScriptOutputDir, $szFeatureExt);
    makeDir($szScriptOutputDir);
    
    $arRunFileList = array();
    
    // $arPat2PathList -->
    // "devel-nistNew" => "tv2012/devel-nistNew",
    // "test.iacc.2.ANew" => "tv2013/test.iacc.2.ANew", // iacc.2.A
    
    foreach ($arPat2PathList as $szFPPatName => $szVideoPath)
    {
        $arCmdLineList = array();
        
        // $arMaxVideosPerPatList -->
        // "devel-nistNew" => 200,
        // "test.iacc.2.ANew" => 300, // *** CHANGED ***
        
        $nMaxVideosPerPat = $arMaxVideosPerPatList[$szFPPatName];
        $nNumVideosPerHost = max(1, intval($nMaxVideosPerPat / $nMaxHostsPerPat)); // Oct 19
        
        printf("DB-%s - %s\n", $nMaxVideosPerPat, $nNumVideosPerHost);
        for ($j = 0; $j < $nMaxVideosPerPat; $j += $nNumVideosPerHost)
        {
            $nStartVideoID = $j;
            $nEndVideoID = $nStartVideoID + $nNumVideosPerHost;
            
            $szFPLogFN = "/dev/null";
            
            for ($jk = 0; $jk < $nMaxKFPerVideo; $jk += $nNumKFPerJob)
            {
                $nStartKFID = $jk;
                $nEndKFID = $nStartKFID + $nNumKFPerJob;
                // printf("Usage: %s <FeatureExt> <FeatureConfigParam> <PatName> <VideoPath> <StartVideoID> <EndVideoID> <StartKFID> <EndKFID>\n", $argv[0]);
                $szParam = sprintf("%s '%s' %s %s %s %s %s %s", $szFeatureExt, $szFeatureConfigParam, $szFPPatName, $szVideoPath, $nStartVideoID, $nEndVideoID, $nStartKFID, $nEndKFID);
                $szCmdLine = sprintf("qsub -e %s -o %s %s %s", $szFPLogFN, $szFPLogFN, $szFPSGEScriptName, $szParam);
                
                $arCmdLineList[] = $szCmdLine;
            }
        }
        
        $szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.%s.sh", $szScriptOutputDir, $szCoreScriptName, $szFeatureExt, $szFPPatName); // specific for one set of data
        saveDataFromMem2File($arCmdLineList, $szFPOutputFN, "wt");
        $arRunFileList[] = $szFPOutputFN;
    }
    
    $szFPOutputFN = sprintf("%s/runme.qsub.%s.%s.all.sh", $szScriptOutputDir, $szCoreScriptName, $szFeatureExt); // specific for one set of data
    saveDataFromMem2File($arRunFileList, $szFPOutputFN, "wt");
}

// $szFPOutputFN = sprintf("%s/runme.qsub.%s.all.sh", $szScriptOutputDir, $szCoreScriptName); // specific for one set of data
// saveDataFromMem2File($arRunFileList, $szFPOutputFN, "wt");

/*
 * Detectors The detector option can be one of the following: --detector harrislaplace --detector densesampling Harris-Laplace salient point detector The Harris-Laplace salient point detector uses a Harris corner detector and subsequently the Laplacian for scale selection. See the paper corresponding to this software for references. Additional options for the Harris-Laplace salient point detector: --harrisThreshold threshold [default: 1e-9] --harrisK k [default: 0.06] --laplaceThreshold threshold [default: 0.03] Dense sampling detector The dense sampling samples at every 6th pixel in the image. For better coverage, a honeyrate structure is used: every odd row is offset by half of the sampling spacing (e.g. by 3 pixels by default). This reduces the overlap between points. By default, the dense sampling will automatically infer a single scale from the spacing parameter. However, you can also specify multiple scales to sample at, for example: --detector densesampling --ds_spacing 10 --ds_scales 1.2+2.0 Additional options for the dense sampling detector: --ds_spacing pixels [default: 6] --ds_scales scale1+scale2+... The default sampling scale for a spacing of 6 pixels is 1.2. Descriptors The following descriptors are available (the name to pass to --descriptoris shown in parentheses): RGB histogram (rgbhistogram) Opponent histogram (opponenthistogram) Hue histogram (huehistogram) rg histogram (nrghistogram) Transformed Color histogram (transformedcolorhistogram) Color moments (colormoments) Color moment invariants (colormomentinvariants) SIFT (sift) HueSIFT (huesift) HSV-SIFT (hsvsift) OpponentSIFT (opponentsift) rgSIFT (rgsift) C-SIFT (csift) RGB-SIFT(rgbsift), equal to transformed color SIFT (transformedcolorsift). See the journal paper for equivalence. File format (text) Files written using --output <filename>look as follows: KOEN1 10 4 <CIRCLE 91 186 16.9706 0 0>; 28 45 4 0 0 0 9 14 10 119; <CIRCLE 156 179 16.9706 0 0>; 7 82 80 62 23 2 15 6 21 23; <CIRCLE 242 108 12 0 0>; 50 67 10 0 0 0 69 44 31 23 0 1; <CIRCLE 277 105 14.2705 0 0>; 21 12 0 0 7 18 127 50 2 0 0; The first line is used as a marker for the file format. The second line specifies the dimensionality of the point descriptor. The third line describes the number of points present in the file. Following this header, there is one line per point. The per-point lines all consist of two parts: a description of the point (<CIRCLE x y scale orientation cornerness>) and a list of numbers, the descriptor vector. These two parts can be seperated through the semicolon ;. The xand ycoordinates start counting at 1, like Matlab. By default, the program uses a Harris-Laplace scale-invariant point detector to obtain the scale-invariant points in an image (these are refered to as CIRCLE in the file format of the descriptors). *
 */

?>


