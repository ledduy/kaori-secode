<?php
/**
 * 		SVM Tools for training, predicting.
 * 
 * 		Copyright (C) 2011 Duy-Dinh Le
 * 		All rights reserved. 
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 23 Nov 2011.
 */

// Update Feb 04
// Adding log file in training process

// Update Oct 15
// Adding $nKernelType as a param for generalized RBF kernel (RBF=2, LAPLACIAN=3, CHISQRBF=4)
// Adding $nUseProbOutput for runTrainClassifier
// runTrainClassifier & runPredictClassifier --> $nUseProbOutput=0 - default
// Adding function for scaling data

// ///////////////////////////////////////////////////////////////
require_once "kl-IOTools.php";

/*
 * $gszSVMTrainApp = sprintf("%s/libsvm291/svm-train", $gszKaoriCoreBinApp); $gszSVMPredictScoreApp = sprintf("%s/libsvm291/svm-predict-score", $gszKaoriCoreBinApp); $gszGridSearchApp = sprintf("%s/libsvm291/grid.py", $gszKaoriCoreBinApp); $gszSVMSelectSubSetApp = sprintf("%s/libsvm291/subset.py", $gszKaoriCoreBinApp); $gszSVMScaleApp = sprintf("%s/libsvm291/svm-scale", $gszKaoriCoreBinApp);
 */

// list of default params
$gfPosWeight = 1;
$gfNegWeight = 1;
$gnMemSize = 600;
$gnStartC = 0;
$gnEndC = 4;
$gnStepC = 2;
$gnStartG = - 18;
$gnEndG = - 2;
$gnStepG = 4;

// pos label is +1 and neg label is -1
// in training data, pos samples must appear before neg samples --> useful for prediction later.
$gszSVMSubParam = sprintf("-w1 %f -w-1 %f -m %d", $gfPosWeight, $gfNegWeight, $gnMemSize);

// $szSearchRange = "-log2c 0,4,2 -log2g -18,-2,4"; // 3*5 points/grid

$gszSVMSearchRange = sprintf("-log2c %d,%d,%d -log2g %d,%d,%d", $gnStartC, $gnEndC, $gnStepC, $gnStartG, $gnEndG, $gnStepG);

/**
 * Load log file and select the best perf
 * Same performance --> smaller C is preferable
 *
 * @param
 *            $szFPGridSearchLogFN
 */
function selectBestSVMParamFromGridSearchResult($szFPGridSearchLogFN)
{
    // raw data, each line format:
    // log2C log2G Perf
    $nNumGridPoints = loadListFile($arRawList, $szFPGridSearchLogFN);
    
    if ($nNumGridPoints < 2)
    {
        terminatePrg("Grid search result file error!");
    }
    
    $nMinRow = 0;
    for ($i = 0; $i < $nNumGridPoints; $i ++)
    {
        $arOutput = explode(" ", $arRawList[$i]);
        
        $arGridList[$i]['log2C'] = floatval(trim($arOutput[0]));
        $arGridList[$i]['log2G'] = floatval(trim($arOutput[1]));
        $arGridList[$i]['perf'] = floatval(trim($arOutput[2]));
    }
    
    $nBestID = 0;
    for ($i = 1; $i < $nNumGridPoints; $i ++)
    {
        if ($arGridList[$i]['perf'] > $arGridList[$nBestID]['perf'])
        {
            $nBestID = $i;
            continue;
        }
        
        if ($arGridList[$i]['perf'] == $arGridList[$nBestID]['perf'] && $arGridList[$i]['log2G'] == $arGridList[$nBestID]['log2G'] && $arGridList[$i]['log2C'] < $arGridList[$nBestID]['log2C'])
        {
            $nBestID = $i;
            continue;
        }
    }
    
    $paramC = pow(2, $arGridList[$nBestID]['log2C']);
    $paramG = pow(2, $arGridList[$nBestID]['log2G']);
    $fBestPerf = $arGridList[$nBestID]['perf'];
    
    printf("Best perf [%f]  for params [%s, %s]\n", $fBestPerf, $paramC, $paramG);
    
    $arOutput['param_C'] = $paramC;
    $arOutput['param_G'] = $paramG;
    $arOutput['best_perf'] = $fBestPerf;
    
    return $arOutput;
}

/**
 * Select a subset used in the process of finding the optimal params
 * Use subset instead of full set to reduce the processing time
 *
 * @param
 *            $szFPSubSetOutputFN
 * @param
 *            $szFPDataInputFN
 * @param
 *            $nMaxSubSamples
 */
function runSelectSubSet($szFPSubSetOutputFN, $szFPDataInputFN, $nMaxSubSamples = 2000)
{
    global $gszSVMSelectSubSetApp;
    
    if (! isset($gszSVMSelectSubSetApp))
    {
        $gszSVMSelectSubSetApp = "subset.py";
    }
    
    $szCmdLine = sprintf("%s %s %d %s", $gszSVMSelectSubSetApp, $szFPDataInputFN, $nMaxSubSamples, $szFPSubSetOutputFN);
    
    return $szCmdLine;
}

// Update Oct 15
// nKernelType=2 --> default RBF
// add one more param for generalized RBF (RBF=2, LAPLACIAN=4, CHISQRBF=5)

/**
 * Run grid search to find optimal params (C, g)
 *
 * @param
 *            $szResultOutFN
 * @param
 *            $szFPDataInputFN
 * @param
 *            $szSearchRange
 * @param
 *            $szSVMSubParam
 */
function runGridSearch($szResultOutFN, $szFPDataInputFN, $szSearchRange = "", $szSVMSubParam = "", $nKernelType = 2)
{
    global $gszSVMTrainApp;
    global $gszGridSearchApp;
    global $gszSVMSearchRange;
    global $gszSVMSubParam;
    
    if ($szSearchRange == "")
    {
        $szSearchRange = $gszSearchRange;
    }
    
    if ($szSVMSubParam == "")
    {
        $szSVMSubParam = $gszSVMSubParam;
    }
    
    if (! isset($gszGridSearchApp))
    {
        $szGridSearchApp = "grid.py";
    } else
    {
        $szGridSearchApp = $gszGridSearchApp;
    }
    
    if (! isset($gszSVMTrainApp))
    {
        $szSVMTrainApp = "svmtrain.exe";
    } else
    {
        $szSVMTrainApp = $gszSVMTrainApp;
    }
    
    $szFigFN = sprintf("%s.png", $szFPDataInputFN);
    $szLogFN = sprintf("%s.log", $szFPDataInputFN);
    $szSearchCmdLine = sprintf("%s %s -svmtrain %s  -s 0 -t %d -png %s -out %s %s %s > %s", $szGridSearchApp, $szSearchRange, $szSVMTrainApp, $nKernelType, $szFigFN, $szResultOutFN, $gszSVMSubParam, $szFPDataInputFN, $szLogFN);
    
    return $szSearchCmdLine;
}

/**
 * Combine selecting a subset and running grid search
 *
 * @param
 *            $szDataName
 * @param
 *            $szInputDir
 * @param
 *            $szModelDir
 * @param
 *            $szSearchRange
 * @param
 *            $szSVMSubParam
 * @param
 *            $nMaxSubSamples
 */
function runSelectBestParams($szDataName, $szInputDir, $szModelDir, $szSearchRange = "", $szSVMSubParam = "", $nMaxSubSamples = 2000, $nKernelType = 2)
{
    $szFPDataInputFN = sprintf("%s/%s", $szInputDir, $szDataName);
    
    $szFPSubSetOutputFN = sprintf("%s/%s-%d.sub", $szModelDir, $szDataName, $nMaxSubSamples);
    
    $szCmdLine = runSelectSubSet($szFPSubSetOutputFN, $szFPDataInputFN, $nMaxSubSamples);
    printf("%s\n", $szCmdLine);
    system($szCmdLine);
    
    $szResultOutFN = sprintf("%s.param.txt", $szFPSubSetOutputFN);
    $szCmdLine = runGridSearch($szResultOutFN, $szFPSubSetOutputFN, $szSearchRange, $szSVMSubParam, $nKernelType);
    printf("%s\n", $szCmdLine);
    system($szCmdLine);
    
    $arOutput = selectBestSVMParamFromGridSearchResult($szResultOutFN);
    
    deleteFile($szFPSubSetOutputFN);
    
    return $arOutput;
}

/**
 *
 * @param
 *            $szTrainDataName
 * @param
 *            $szTestDataName
 * @param $szModelDir -->
 *            for saving normdata data
 * @param $szTrainDir -->
 *            combined with szTrainDataName for loading training data
 * @param $szTestDir -->
 *            combined with szTestDataName for loading testing data
 * @param
 *            $szStage
 */
function runScaleData($szTrainDataName, $szTestDataName, $szModelDir, $szTrainDir, $szTestDir, $szStage = "TRAIN")
{
    global $gnPerformDataScaling;
    
    printf("Run data scaling ...\n");
    global $gszSVMScaleApp;
    
    if (! isset($gszSVMScaleApp))
    {
        $szSVMScaleApp = "svm-scale.exe";
    } else
    {
        $szSVMScaleApp = $gszSVMScaleApp;
    }
    
    $szFPOrigTrainDataInputFN = sprintf("%s/%s.orig", $szTrainDir, $szTrainDataName);
    $szFPTrainDataInputFN = sprintf("%s/%s", $szTrainDir, $szTrainDataName);
    
    $szFPNormDataInputFN = sprintf("%s/%s.normdat", $szModelDir, $szTrainDataName);
    
    $szFPOrigTestDataInputFN = sprintf("%s/%s.orig", $szTestDir, $szTestDataName);
    $szFPTestDataInputFN = sprintf("%s/%s", $szTestDir, $szTestDataName);
    
    if ($szStage == "TRAIN")
    {
        
        if ($gnPerformDataScaling)
        {
            $szCmdLine = sprintf("cp %s %s", $szFPTrainDataInputFN, $szFPOrigTrainDataInputFN); // saving the orig data
            printf("%s\n", $szCmdLine);
            system($szCmdLine);
            
            // normalize to [0, 1] for each feature (i.e. dimension)
            // -s option --> save normdata
            // use directive > for output file
            $szCmdLine = sprintf("%s -l 0 -u 1 -s %s %s > %s", $szSVMScaleApp, $szFPNormDataInputFN, $szFPOrigTrainDataInputFN, $szFPTrainDataInputFN);
            printf("%s\n", $szCmdLine);
            system($szCmdLine);
        }
    } else
    {
        if ($gnPerformDataScaling)
        {
            $szCmdLine = sprintf("cp %s %s", $szFPTestDataInputFN, $szFPOrigTestDataInputFN); // saving the orig data
            printf("%s\n", $szCmdLine);
            system($szCmdLine);
            // normalize to [0, 1] for each feature (i.e. dimension)
            // -r option --> load saved normdata
            $szCmdLine = sprintf("%s -l 0 -u 1 -r %s %s > %s", $szSVMScaleApp, $szFPNormDataInputFN, $szFPOrigTestDataInputFN, $szFPTestDataInputFN);
            printf("%s\n", $szCmdLine);
            system($szCmdLine);
        }
    }
}

function trainSVMClassifier($szFPModelOutputFN, $szFPDataInputFN, $szMainParam, $szSubParam = "")
{
    global $gszSVMSubParam;
    global $gszSVMTrainApp;
    
    if (! isset($gszSVMTrainApp))
    {
        $szSVMTrainApp = "svmtrain.exe";
    } else
    {
        $szSVMTrainApp = $gszSVMTrainApp;
    }
    
    if ($szSubParam == "")
    {
        $szSubParam = $gszSVMSubParam;
    }
    
    $szFPModelOutputLogFN = sprintf("%s.log", $szFPModelOutputFN);
    $szCmdLine = sprintf("%s %s %s %s %s > %s", $szSVMTrainApp, $szMainParam, $gszSVMSubParam, $szFPDataInputFN, $szFPModelOutputFN, $szFPModelOutputLogFN);
    
    return $szCmdLine;
}

// Update Oct 15
// Adding $nKernelType as a param for generalized RBF kernel (RBF=2, LAPLACIAN=3, CHISQRBF=4)
/**
 * Train SVM classifier with RBF kernel
 * Optimal params (c, g) are found by grid search
 *
 * @param
 *            $szDataName
 * @param $szInputDir -->            
 * @param
 *            $szModelDir
 * @param
 *            $szSearchRange
 * @param
 *            $szSubParam
 * @param
 *            $nMaxSubSamples
 */
function runTrainClassifier($szDataName, $szInputDir, $szModelDir, $szSearchRange = "", $szSubParam = "", $nMaxSubSamples = 2000, $nUseProbOutput = 0, $nKernelType = 2)
{
    $timeStart = microtime_float();
    $szFPDataInputFN = sprintf("%s/%s", $szInputDir, $szDataName);
    
    if (! file_exists($szFPDataInputFN))
    {
        printf("Data file [%s] not found\n", $szFPDataInputFN);
        exit();
    }
    // NEW !!!!!!!!! --> Scale the data for normalization
    runScaleData($szDataName, "", $szModelDir, $szInputDir, "", $szStage = "TRAIN");
    
    $szFPModelOutputFN = sprintf("%s/%s.model", $szModelDir, $szDataName);
    
    $arOutput = runSelectBestParams($szDataName, $szInputDir, $szModelDir, $szSearchRange, $szSubParam, $nMaxSubSamples, $nKernelType);
    $param_G = $arOutput['param_G'];
    $param_C = $arOutput['param_C'];
    
    // t=2 --> RBF kernel
    // s=0 --> C-SVC
    $szMainParam = sprintf("-s 0 -t %d -b %s -c %s -g %s", $nKernelType, $nUseProbOutput, $param_C, $param_G);
    $szCmdLine = trainSVMClassifier($szFPModelOutputFN, $szFPDataInputFN, $szMainParam, $szSubParam);
    printf("%s\n", $szCmdLine);
    system($szCmdLine);
    
    $timeEnd = microtime_float();
    $timeRun = $timeEnd - $timeStart;
    printf("Running time: %0.2f seconds\n", $timeRun);
}

/**
 * Train SVM classifier with linear kernel
 *
 * @param
 *            $szDataName
 * @param
 *            $szInputDir
 * @param
 *            $szModelDir
 * @param
 *            $szSubParam
 */
function runTrainLinearClassifier($szDataName, $szInputDir, $szModelDir, $szSubParam = "", $nUseProbOutput = 0)
{
    $timeStart = microtime_float();
    $szFPDataInputFN = sprintf("%s/%s", $szInputDir, $szDataName);
    
    // NEW !!!!!!!!! --> Scale the data for normalization
    runScaleData($szDataName, "", $szModelDir, $szInputDir, "", $szStage = "TRAIN");
    
    $szFPModelOutputFN = sprintf("%s/%s.model", $szModelDir, $szDataName);
    
    // t=0 --> linear kernel
    $szMainParam = sprintf("-s 0 -t 0 -b %s", $nUseProbOutput);
    $szCmdLine = trainSVMClassifier($szFPModelOutputFN, $szFPDataInputFN, $szMainParam, $szSubParam);
    printf("%s\n", $szCmdLine);
    system($szCmdLine);
    
    $timeEnd = microtime_float();
    $timeRun = $timeEnd - $timeStart;
    printf("Running time: %0.2f seconds\n", $timeRun);
}

function predictScoreBySVMClassifier($szFPScoreOutputFN, $szFPModelInputFN, $szFPDataInputFN, $nUseProbOutput = 0)
{
    global $gszSVMPredictScoreApp;
    
    if (! isset($gszSVMPredictScoreApp))
    {
        $szSVMPredictScoreApp = "svmpredict-score.exe";
    } else
    {
        $szSVMPredictScoreApp = $gszSVMPredictScoreApp;
    }
    
    $szCmdLine = sprintf("%s -b %d %s %s %s", $szSVMPredictScoreApp, $nUseProbOutput, $szFPDataInputFN, $szFPModelInputFN, $szFPScoreOutputFN);
    
    printf("%s\n", $szCmdLine);
    
    return $szCmdLine;
}

/**
 *
 * @param $szTrainDataName -->
 *            combined with szModelDir for loading model, and normdata
 * @param
 *            $szTestDataName
 * @param $szInputDir -->
 *            combined with szTestDataName for loading testing data
 * @param $szModelDir -->
 *            combined with szTrainDataName for loading model, and normdata
 * @param $szOutputDir -->
 *            output score dir
 */
function runPredictClassifier($szTrainDataName, $szTestDataName, $szInputDir, $szModelDir, $szOutputDir, $nUseProbOutput = 0)
{
    $timeStart = microtime_float();
    $szFPDataInputFN = sprintf("%s/%s", $szInputDir, $szTestDataName);
    $szFPModelFN = sprintf("%s/%s.model", $szModelDir, $szTrainDataName);
    $szFPScoreOutputFN = sprintf("%s/%s.out", $szOutputDir, $szTestDataName);
    
    if (! file_exists($szFPModelFN))
    {
        printf("Model file [%s] not found\n", $szFPModelFN);
        exit();
    }
    
    if (! file_exists($szFPDataInputFN))
    {
        printf("Data file [%s] not found\n", $szFPDataInputFN);
        exit();
    }
    
    runScaleData($szTrainDataName, $szTestDataName, $szModelDir, $szModelDir, $szInputDir, $szStage = "TEST");
    $szCmdLine = predictScoreBySVMClassifier($szFPScoreOutputFN, $szFPModelFN, $szFPDataInputFN, $nUseProbOutput);
    
    printf("%s\n", $szCmdLine);
    system($szCmdLine);
    
    $timeEnd = microtime_float();
    $timeRun = $timeEnd - $timeStart;
    printf("Running time: %0.2f seconds\n", $timeRun);
    
    return $szFPScoreOutputFN;
}

?>