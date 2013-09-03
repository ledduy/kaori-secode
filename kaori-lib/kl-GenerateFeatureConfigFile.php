<?php

/**
 * 		Generate config feature files.
 *		Input params: granularity (grid), color type, and quantization (#bins/channel)  		
 * 
 * 		Copyright (C) 2011 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 23 Nov 2011.
 */

/*
 * The config file is used for feature extraction. Using different config files, different feature types are generated for experiments.
 */
require_once "kl-IOTools.php";

/**
 * -desc dvf - color histogram, 5x5 grid, CV_BGR2HSV, histograms (0, 255, 8) - (0, 255, 4) - (0, 255, 4)
 * -type ch
 * -ext g_ch
 * -grid GRID_ROW_COL
 * -row 5
 * -col 5
 * -cc CV_HSV
 * -c1 0 255 8
 * -c2 0 255 4
 * -c3 0 255 4
 */
function generateCHFeatureConfigFile($szFPOutputFN, $szColorType = "CV_HSV", $nNumRows = 5, $nNumCols = 5, $nNumBins1 = 4, $nNumBins2 = 4, $nNumBins3 = 4)
{
    $arOutput = array();
    $arOutput[] = sprintf("-desc dvf - color histogram, %dx%d grid, %s, histograms (0, 255, %d) - (0, 255, %d) - (0, 255, %d)", $nNumRows, $nNumCols, $szColorType, $nNumBins1, $nNumBins2, $nNumBins3);
    $arOutput[] = sprintf("-type ch");
    $arOutput[] = sprintf("-ext nsc.c%s.g%d.q%d.g_ch", $szColorType, $nNumRows, $nNumBins1);
    $arOutput[] = sprintf("-grid GRID_ROW_COL");
    $arOutput[] = sprintf("-row %d", $nNumRows);
    $arOutput[] = sprintf("-col %d", $nNumCols);
    $arOutput[] = sprintf("-cc %s", $szColorType);
    $arOutput[] = sprintf("-c1 0 255 %d", $nNumBins1);
    $arOutput[] = sprintf("-c2 0 255 %d", $nNumBins2);
    $arOutput[] = sprintf("-c3 0 255 %d", $nNumBins3);
    
    saveDataFromMem2File($arOutput, $szFPOutputFN, "wt");
}

/**
 * -desc dvf - color moment, 5x5 grid, CV_BGR2Luv
 * -type cm
 * -ext g_cm
 * -grid GRID_ROW_COL
 * -row 5
 * -col 5
 * -cc CV_Luv
 */
function generateCMFeatureConfigFile($szFPOutputFN, $szColorType = "CV_Luv", $nNumRows = 5, $nNumCols = 5)
{
    $arOutput = array();
    $arOutput[] = sprintf("-desc dvf - color moments, %dx%d grid, %s", $nNumRows, $nNumCols, $szColorType);
    $arOutput[] = sprintf("-type cm");
    $arOutput[] = sprintf("-ext nsc.c%s.g%d.q%d.g_cm", $szColorType, $nNumRows, $nNumBins = 3);
    $arOutput[] = sprintf("-grid GRID_ROW_COL");
    $arOutput[] = sprintf("-row %d", $nNumRows);
    $arOutput[] = sprintf("-col %d", $nNumCols);
    $arOutput[] = sprintf("-cc %s", $szColorType);
    
    saveDataFromMem2File($arOutput, $szFPOutputFN, "wt");
}

/**
 * -desc dvf - edge orientation histogram, 5x5 grid, CV_BGR2GRAY, histogram (0, 360, 10+1), thresholds (10.0000, 30.0000)
 * -type eoh
 * -ext g_eoh
 * -grid GRID_ROW_COL
 * -row 5
 * -col 5
 * -cc CV_GRAY
 * -f1 10
 * -f2 30
 * -nb 10
 */
function generateEOHFeatureConfigFile($szFPOutputFN, $nNumRows = 5, $nNumCols = 5, $nNumEdgeBins = 10, $tLow = 10.0, $tHigh = 30.0)
{
    $szColorType = "CV_GRAY";
    
    $arOutput = array();
    $arOutput[] = sprintf("-desc dvf - edge orientation histogram, %dx%d grid, CV_BGR2GRAY, histogram (0, 360, %d+1), thresholds (%0.2f, %0.2f)", $nNumRows, $nNumCols, $nNumEdgeBins, $tLow, $tHigh);
    $arOutput[] = sprintf("-type eoh");
    $arOutput[] = sprintf("-ext nsc.c%s.g%d.q%d.g_eoh", $szColorType, $nNumRows, $nNumEdgeBins);
    $arOutput[] = sprintf("-grid GRID_ROW_COL");
    $arOutput[] = sprintf("-row %d", $nNumRows);
    $arOutput[] = sprintf("-col %d", $nNumCols);
    $arOutput[] = sprintf("-cc %s", $szColorType);
    $arOutput[] = sprintf("-f1 %f", $tLow);
    $arOutput[] = sprintf("-f2 %f", $tHigh);
    $arOutput[] = sprintf("-nb %f", $nNumEdgeBins);
    
    saveDataFromMem2File($arOutput, $szFPOutputFN, "wt");
}

/**
 * -desc dvf - local binary pattern, 5x5 grid, CV_BGR2GRAY, histogram (0, 59, 10)
 * -type lbp
 * -ext g_lbp
 * -grid GRID_ROW_COL
 * -row 5
 * -col 5
 * -cc CV_GRAY
 * -nb 10
 */
function generateLBPFeatureConfigFile($szFPOutputFN, $nNumRows = 5, $nNumCols = 5, $nNumBins = 10)
{
    $szColorType = "CV_GRAY";
    
    $arOutput = array();
    $arOutput[] = sprintf("-desc dvf - local binary pattern, %dx%d grid, CV_BGR2GRAY, histogram (0, 59, %d)", $nNumRows, $nNumCols, $nNumBins);
    $arOutput[] = sprintf("-type lbp");
    $arOutput[] = sprintf("-ext nsc.c%s.g%d.q%d.g_lbp", $szColorType, $nNumRows, $nNumBins);
    $arOutput[] = sprintf("-grid GRID_ROW_COL");
    $arOutput[] = sprintf("-row %d", $nNumRows);
    $arOutput[] = sprintf("-col %d", $nNumCols);
    $arOutput[] = sprintf("-cc %s", $szColorType);
    $arOutput[] = sprintf("-nb %f", $nNumBins);
    
    saveDataFromMem2File($arOutput, $szFPOutputFN, "wt");
}

?>