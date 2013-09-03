<?php

/**
 * 		@file 	ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT.php
 * 		@brief 	Extract COLOR SIFT Feature.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */

// !!! IMPORTANT - MUST CHECK
// Adding option to shrink keyframe to using convert -resize 'wxh>'
// Adding option to support skipp existing files

// --> Search for Modified Jul 06
// --> TmpDir Description
// - TmpDir = RootTmpDir + ScriptName + FeatureExt + VideoPath
// - TmpDir + KeyFrames/VideoID --> Store keyframes downloaded from the server, ONE tar file for all keyframes of ONE video
// - Tmpdir + Feature/VideoID --> Store temporary files during feature extraction

// --> New process
// - Keyframes of ONE video might be packed in one or more tar files --> scan all .tar files and untar to LOCAL dir
// - Later steps will use keyframes stored in local dir
// ///////////////////////////////////////////////////////////////////

// *** Update Jul 06, 2012
// --> Modify to work with replicated servers, i.e. output feature dir is CHANGED

// Update Oct 08
// Adding 2 more params: StartKF, EndKF (within one videoID)

// Update Oct 06
// Use global var for keyframe extension --> $gKeyFrameImgExt = ".jpg"
// Modify funciton convert2VGGFormat($szFPOutputFN, $szFPInputFN)
// 2 output files: 1 is the same as before (param + descriptor), 1 is new --> only param used for SoftGrid later
// $szFPSimpleOutputFN = sprintf("%s.loc", $szFPOutputFN);
// saveDataFromMem2File($arSimpleOutput, $szFPSimpleOutputFN);

// ///// THIS PART MUST BE SYNC WITH ComputeSoftBOW //////////////
// moved to ksc-AppConfig
// $gnHavingResized = 1;
// $gnMaxFrameWidth = 350;
// $gnMaxFrameHeight = 350;

// $gszResizeOption = sprintf("-resize '%sx%s>'", $gnMaxFrameWidth, $gnMaxFrameHeight); // to ensure 352 is the width after shrinking
// ////////////////////////////////////////////////

// Update Aug 01
// Customize for tv2011
// Adding option to shrink keyframe to using convert -resize 'wxh>'

// /////////////////////
// rgSIFT & oppSIFT --> VERY SLOW if IMAGE is NOT RESIZED

/*
 * // Format of raw SIFT feature file // First row: NumDims // Second row: NumKeyPoints // Third row ...: x y -1 -1 -1 V1 V2 ... V_NumDims // x, y is used for SoftBOW-Grid --> location of the keypoint
 */

// Different from other version --> no convert to pgm, just copy
// Copied from nsc-Feature-ExtractRawAffCovSIFTFeature
//

// ///////////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

// ////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

// $szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootKeyFrameDir = sprintf("%s/keyframe-5", $szRootDir);
$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

$szRootPrgFileDir = $szRootMetaDataDir;

// Update Oct 06, 2011
$gKeyFrameImgExt = "jpg";

$gSkippExistingFiles = 1;

// only skip existing files not earlier than 05 Jul, 2013
$nDateLimit = mktime(0, 0, 0, 7, 5, 2013); // *** CHANGED ***
                                      
// ////////////////// END FOR CUSTOMIZATION ////////////////////
                                      
// //////////////////////// MAIN ////////////////////////
$szFeatureExt = "nsc.raw.dense6mul.sift";
$szFeatureConfigParam = "--detector densesampling --ds_spacing 6 --ds_scales 1.2+2.0 --descriptor sift";
$szPatName = "devel-nistNew";
$szVideoPath = "tv2012/devel-nistNew"; // path from archive name to keyframe dir, eg. tv2007/devel
$nStartVideoID = 0;
$nEndVideoID = 1;
$nStartKFID = 0;
$nEndKFID = 100000;

if ($argc != 9)
{
    printf("Number of args does not match [%d]\n", $argc);
    print_r($argv);
    printf("Usage: %s <FeatureExt> <FeatureConfigParam> <PatName> <VideoPath> <StartVideoID> <EndVideoID> <StartKFID> <EndKFID>\n", $argv[0]);
    printf("Usage: %s %s %s %s %s %s %s %s %s\n", $argv[0], $szFeatureExt, $szFeatureConfigParam, $szPatName, $szVideoPath, $nStartVideoID, $nEndVideoID, $nStartKFID, $nEndKFID);
    exit(1);
}

$szFeatureExt = $argv[1];
$szFeatureConfigParam = $argv[2];
$szPatName = $argv[3]; // e.g. tv2007.devel
$szVideoPath = $argv[4]; // path from archive name to keyframe dir, eg. tv2007/devel
$nStartVideoID = intval($argv[5]);
$nEndVideoID = intval($argv[6]);
$nStartKFID = intval($argv[7]);
$nEndKFID = intval($argv[8]);

$szFPLogFN = sprintf("ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT-%s.log", $szFeatureExt); // *** CHANGED ***
                                                                                                 
// *** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szFeatureExt); // *** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5/%s", $szRootOutputDir, $szFeatureExt);
makeDir($szRootFeatureDir);

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]-[%s]-[%s]", $szStartTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7], $argv[8]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

extractRawSIFTFeature($szRootFeatureDir, $szRootKeyFrameDir, $szRootMetaDataDir, $szRootPrgFileDir, $szPatName, $szVideoPath, $szFeatureExt, $szFeatureConfigParam, $nStartVideoID, $nEndVideoID, $nStartKFID, $nEndKFID);

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]-[%s]-[%s] ", $szStartTime, $szFinishTime, $argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6], $argv[7], $argv[8]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

// ///////////////////////////// FUNCTIONS ///////////////////

/**
 * This function is used if no .
 * prg file (~ keyframe list) exists
 *
 * @param
 *            $szRootKeyFrameDir
 * @param
 *            $szPath
 */
function getVideoProgsForOnePat($szRootKeyFrameDir, $szPath = "")
{
    // scan to get all video programs - the keyframes of each video program is stored in one dir
    $szKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szPath);
    
    $arVideoProgList = collectDirsInOneDir($szKeyFrameDir);
    sort($arVideoProgList);
    
    return $arVideoProgList;
}

// Tmp files are saved in local ws
/**
 *
 * @param
 *            $szAppName
 * @param
 *            $szFPFeatureConfigFN
 * @param
 *            $szFPInputImgFN
 * @param
 *            $szFPFeatureOutputFN
 * @param
 *            $szTmpDir
 */
function extractRawSIFTFeatureForOneImage($szAppName, $szFeatureConfigParam, $szFPInputImgFN, $szFPFeatureOutputFN, $szTmpDir = "/tmp")
{
    global $gKeyFrameImgExt; // jpg
    
    $arCmdLine = array();
    
    // this code did not work with pgm format --> keep jpg format
    $szBaseInputName = basename($szFPInputImgFN);
    $szFPJPGInputFN = sprintf("%s/%s.%s", $szTmpDir, $szBaseInputName, $gKeyFrameImgExt);
    
    global $gSkippExistingFiles;
    
    global $nDateLimit;
    
    $szFPSimpleFeatureOutputFN = sprintf("%s.loc", $szFPFeatureOutputFN);
    
    if ($gSkippExistingFiles)
    {
        $szFPTarGzFeatureOutputFN = sprintf("%s.tar.gz", $szFPFeatureOutputFN);
        
        $szFPTarGzSimpleFeatureOutputFN = sprintf("%s.tar.gz", $szFPSimpleFeatureOutputFN);
        
        // file exist AND not out of data --> skip
        if (file_exists($szFPTarGzFeatureOutputFN) && file_exists($szFPTarGzSimpleFeatureOutputFN) && filesize($szFPTarGzFeatureOutputFN) && filesize($szFPTarGzSimpleFeatureOutputFN))
        {
            $nSkipByOutOfDate = 1;
            if (filemtime($szFPTarGzFeatureOutputFN) - $nDateLimit > 0)
            {
                $nSkipByOutOfDate = 0;
            }
            
            if (! $nSkipByOutOfDate)
            {
                printf("File [%s] existed! Skip ...\n", $szFPTarGzFeatureOutputFN);
                return;
            }
        }
    }
    
    global $gszResizeOption;
    global $gnHavingResized;
    
    if ($gnHavingResized)
    {
        // convert Frame-5020.jpg -resize '350x350>' test3.jpg
        $szCmdLine = sprintf("convert %s %s %s", $szFPInputImgFN, $gszResizeOption, $szFPJPGInputFN);
    } else
    {
        // just copy
        $szCmdLine = sprintf("cp %s %s", $szFPInputImgFN, $szFPJPGInputFN);
    }
    
    execSysCmd($szCmdLine);
    $arCmdLine[] = $szCmdLine;
    
    // generate to tmp output
    $szBaseOutputName = basename($szFPFeatureOutputFN);
    $szFPFeatureTmpFN = sprintf("%s/%s", $szTmpDir, $szBaseOutputName);
    $szFPFeatureTmpFN2 = sprintf("%s/%s.raw", $szTmpDir, $szBaseOutputName);
    
    // only param, not including descriptor
    $szFPSimpleFeatureTmpFN = sprintf("%s.loc", $szFPFeatureTmpFN);
    
    // ./colorDescriptor test-tv05.jpg --detector densesampling --descriptor csift --ds_scales 1.2+2.0 --output test-tv05.densesampling.csift.mulscale
    // FeatureConfig = --detector densesampling --descriptor csift --ds_scales 1.2+2.0
    $szCmdLine = sprintf("%s %s %s --output %s", $szAppName, $szFPJPGInputFN, $szFeatureConfigParam, $szFPFeatureTmpFN2);
    execSysCmd($szCmdLine);
    $arCmdLine[] = $szCmdLine;
    
    convert2VGGFormat($szFPFeatureTmpFN, $szFPFeatureTmpFN2);
    
    if (file_exists($szFPFeatureTmpFN) && file_exists($szFPSimpleFeatureTmpFN))
    {
        // tar file http://www.skrakes.com/?p=154
        $szFPTarFeatureTmpFN = sprintf("%s.tar.gz", $szFPFeatureTmpFN);
        
        // if using -C <DirName>, the remaing one is only file name (no path)
        $szCmdLine = sprintf("tar -cvzf %s -C %s %s", $szFPTarFeatureTmpFN, $szTmpDir, $szBaseOutputName);
        execSysCmd($szCmdLine);
        $arCmdLine[] = $szCmdLine;
        
        // copy to the final dest
        $szCmdLine = sprintf("mv -f %s %s.tar.gz", $szFPTarFeatureTmpFN, $szFPFeatureOutputFN);
        execSysCmd($szCmdLine);
        $arCmdLine[] = $szCmdLine;
        
        // Update Oct 06, 2011
        // new output file --> <OutputFile>.loc
        // tar file http://www.skrakes.com/?p=154
        
        $szFPTarSimpleFeatureTmpFN = sprintf("%s.tar.gz", $szFPSimpleFeatureTmpFN);
        
        // if using -C <DirName>, the remaing one is only file name (no path)
        $szCmdLine = sprintf("tar -cvzf %s -C %s %s.loc", $szFPTarSimpleFeatureTmpFN, $szTmpDir, $szBaseOutputName);
        execSysCmd($szCmdLine);
        $arCmdLine[] = $szCmdLine;
        
        // copy to the final dest
        $szCmdLine = sprintf("mv -f %s %s.tar.gz", $szFPTarSimpleFeatureTmpFN, $szFPSimpleFeatureOutputFN);
        execSysCmd($szCmdLine);
        $arCmdLine[] = $szCmdLine;
    }
    
    // delete pgm file
    $szCmdLine = sprintf("rm -f %s/%s*", $szTmpDir, $szBaseInputName);
    execSysCmd($szCmdLine);
    $arCmdLine[] = $szCmdLine;
    
    $szCmdLine = sprintf("rm -f %s/%s*", $szTmpDir, $szBaseOutputName);
    execSysCmd($szCmdLine);
    $arCmdLine[] = $szCmdLine;
    
    return $arCmdLine;
}

/**
 * $szVideoPath --> path from video archive name to location of video,
 * e.g.
 * trecvid/tv2005/devel
 * + trecvid: archive name,
 * + tv2005/devel: video path
 * szRootMetaDataDir, szRootKeyFrameDir, szRootFeatureDir already included archive name, i.e path/trecvid
 *
 * Directories for the feature are created!!!
 */

/**
 * + Video list: $szFPInputFN = sprintf("%s/%s.video.%s.lst", $szRootPatFileDir, $szArchiveName, $szPatName);
 * + $szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
 * + $szTmpDir = sprintf("%s/%s/%s/%s", $gszTmpDir, $szScriptBaseName, $szFeatureExt, $szVideoPath);
 * + $szKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szVideoPath); // video and keyframe share the same path.
 * + $szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootPrgFileDir, $szVideoPath, $szVideoID);
 */

// --> TmpDir Description !!! IMPORTANT !!! Update Jul 06, 2012
// - TmpDir = RootTmpDir + ScriptName + FeatureExt + VideoPath
// - TmpDir + KeyFrames --> Store keyframes downloaded from the server, ONE tar file for all keyframes of ONE video
// - Tmpdir + Feature --> Store temporary files during feature extraction
function extractRawSIFTFeature($szRootFeatureDir, $szRootKeyFrameDir, $szRootPatFileDir, $szRootPrgFileDir, $szPatName, $szVideoPath, $szFeatureExt, $szFeatureConfigParam, $nStartVideoID, $nEndVideoID, $nStartKFID, $nEndKFID)
{
    global $garAppConfig; // to access the app name of FeatureExtractor
    global $gszTmpDir;
    
    global $gKeyFrameImgExt; // jpg
    
    $szAppName = $garAppConfig["RAW_COLOR_SIFF_APP"];
    
    // !!! IMPORTANT
    $szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
    
    // <-- Modified Jul 06, 2012
    $szTmpDir = sprintf("%s/%s/%s/%s", $gszTmpDir, $szScriptBaseName, $szFeatureExt, $szVideoPath);
    makeDir($szTmpDir);
    
    $szLocalKeyFrameDir = sprintf("%s/keyframes", $szTmpDir);
    makeDir($szLocalKeyFrameDir);
    
    $szLocalTmpFeatureDir = sprintf("%s/features", $szTmpDir);
    makeDir($szLocalTmpFeatureDir);
    // --> Modified Jul 06, 2012
    
    // .lst is required for feature extraction application
    $szFPInputFN = sprintf("%s/%s.lst", $szRootPatFileDir, $szPatName);
    
    if (! file_exists($szFPInputFN))
    {
        printf("File [%s] does not exist!\n", $szFPInputFN);
        exit(1);
        // $arVideoProgList = getVideoProgsForOnePat($szRootKeyFrameDir, $szVideoPath);
        // saveDataFromMem2File($arVideoProgList, $szFPInputFN, "wt");
    } else
    {
        $nNumVideos = loadListFile($arVideoProgList, $szFPInputFN);
    }
    
    $szKeyFrameDir = sprintf("%s/%s", $szRootKeyFrameDir, $szVideoPath); // video and keyframe share the same path.
                                                                          
    // follow the same rule: <root>/<videopath>/<videoID>
    $szFeatureDir = sprintf("%s/%s", $szRootFeatureDir, $szVideoPath);
    makeDir($szFeatureDir);
    
    if ($nEndVideoID > $nNumVideos)
    {
        $nEndVideoID = $nNumVideos;
    }
    
    for ($iz = $nStartVideoID; $iz < $nEndVideoID; $iz ++)
    {
        $arTmp = explode("#$#", $arVideoProgList[$iz]);
        $szVideoID = trim($arTmp[0]);
        
        $szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootPrgFileDir, $szVideoPath, $szVideoID);
        
        if (! file_exists($szFPKeyFrameListFN))
        {
            printf("$$$ File [%s] does not exist!\n", $szFPKeyFrameListFN);
            continue;
        }
        $nNumKeyFrames = loadListFile($arKeyFrameList, $szFPKeyFrameListFN);
        
        $szFeatureOutputDir = sprintf("%s/%s", $szFeatureDir, $szVideoID);
        makeDir($szFeatureOutputDir);
        
        $nLocalStartKFID = $nStartKFID;
        $nLocalEndKFID = $nEndKFID;
        if ($nEndKFID > $nNumKeyFrames)
        {
            $nLocalEndKFID = $nNumKeyFrames;
        }
        
        if ($nLocalStartKFID > $nLocalEndKFID)
        {
            continue;
        }
        
        // <-- Modified Jul 06, 2012
        $szLocalKeyFrameDir2 = sprintf("%s/%s-%s-%s", $szLocalKeyFrameDir, $szVideoID, $nLocalStartKFID, $nLocalEndKFID);
        makeDir($szLocalKeyFrameDir2);
        
        $szLocalTmpFeatureDir2 = sprintf("%s/%s-%s-%s", $szLocalTmpFeatureDir, $szVideoID, $nLocalStartKFID, $nLocalEndKFID);
        makeDir($szLocalTmpFeatureDir2);
        
        // download and extract ALL .tar files from the server to the local dir
        $szServerKeyFrameDir = sprintf("%s/%s", $szKeyFrameDir, $szVideoID);
        $arTarFileList = collectFilesInOneDir($szServerKeyFrameDir, "", ".tar");
        foreach ($arTarFileList as $szTarFileName)
        {
            $szCmdLine = sprintf("tar -xvf %s/%s.tar -C %s", $szServerKeyFrameDir, $szTarFileName, $szLocalKeyFrameDir2);
            execSysCmd($szCmdLine);
        }
        // --> Modified Jul 06, 2012
        
        for ($jkf = $nLocalStartKFID; $jkf < $nLocalEndKFID; $jkf ++)
        {
            $szKeyFrameID = $arKeyFrameList[$jkf];
            
            // $szFPInputImgFN = sprintf("%s/%s/%s.%s", $szKeyFrameDir, $szVideoID, $szKeyFrameID, $gKeyFrameImgExt);
            
            // Modified Jul 06, 2012 -- $szLocalKeyFrameDir2
            $szFPInputImgFN = sprintf("%s/%s.%s", $szLocalKeyFrameDir2, $szKeyFrameID, $gKeyFrameImgExt);
            
            $szFPFeatureOutputFN = sprintf("%s/%s.%s", $szFeatureOutputDir, $szKeyFrameID, $szFeatureExt);
            
            // --> Modified Jul 06, 2012 -- $szLocalTmpFeatureDir
            extractRawSIFTFeatureForOneImage($szAppName, $szFeatureConfigParam, $szFPInputImgFN, $szFPFeatureOutputFN, $szLocalTmpFeatureDir2);
        }
        
        // --> Modified Jul 06, 2012
        // Delete local files
        $szCmdLine = sprintf("rm -rf %s", $szLocalKeyFrameDir2);
        execSysCmd($szCmdLine);
        
        $szCmdLine = sprintf("rm -rf %s", $szLocalTmpFeatureDir2);
        execSysCmd($szCmdLine);
    }
}

// convert to canonical format (i.e VGG format)
/*
 * // Format of raw SIFT feature file // First row: NumDims // Second row: NumKeyPoints // Third row ...: x y -1 -1 -1 V1 V2 ... V_NumDims // x, y is used for SoftBOW-Grid --> location of the keypoint
 */

// Update Oct 06, 2011
// 2 output files: 1 is the same as before (param + descriptor), 1 is new --> only param used for SoftGrid later
// $szFPSimpleOutputFN = sprintf("%s.loc", $szFPOutputFN);
// saveDataFromMem2File($arSimpleOutput, $szFPSimpleOutputFN);
function convert2VGGFormat($szFPOutputFN, $szFPInputFN)
{
    $arOutput = array();
    
    // Update Oct 06, 2011
    $arSimpleOutput = array(); // --> only store spatial information, not including SIFT descriptor
    
    $nNumRows = loadListFile($arInput, $szFPInputFN);
    
    // skip the first row - KOEN1
    // keep the next 2 rows --> NumDims and NumKeyPoints
    
    $arOutput[] = $arInput[1];
    $arOutput[] = $arInput[2];
    
    $arSimpleOutput[] = $arInput[1];
    $arSimpleOutput[] = $arInput[2];
    for ($i = 3; $i < $nNumRows; $i ++)
    {
        $szLine = &$arInput[$i];
        $arTmp = explode(";", $szLine);
        // print_r($arTmp);
        $szVal = trim($arTmp[1]);
        
        // $arTmp = explode(" ", $szVal);
        // printf(sizeof($arTmp));exit();
        
        $szTmp = trim($arTmp[0]);
        
        $arTmp = explode("<CIRCLE", $szTmp);
        // print_r($arTmp);
        
        $szTmp = trim($arTmp[1]);
        $arTmp = explode(">", $szTmp);
        // print_r($arTmp); exit();
        $szParam = trim($arTmp[0]);
        
        $arOutput[] = sprintf("%s %s", $szParam, $szVal);
        $arSimpleOutput[] = sprintf("%s", $szParam); // only keep param
    }
    saveDataFromMem2File($arOutput, $szFPOutputFN);
    
    $szFPSimpleOutputFN = sprintf("%s.loc", $szFPOutputFN);
    saveDataFromMem2File($arSimpleOutput, $szFPSimpleOutputFN);
}
/*
 * Detectors The detector option can be one of the following: --detector harrislaplace --detector densesampling Harris-Laplace salient point detector The Harris-Laplace salient point detector uses a Harris corner detector and subsequently the Laplacian for scale selection. See the paper corresponding to this software for references. Additional options for the Harris-Laplace salient point detector: --harrisThreshold threshold [default: 1e-9] --harrisK k [default: 0.06] --laplaceThreshold threshold [default: 0.03] Dense sampling detector The dense sampling samples at every 6th pixel in the image. For better coverage, a honeyrate structure is used: every odd row is offset by half of the sampling spacing (e.g. by 3 pixels by default). This reduces the overlap between points. By default, the dense sampling will automatically infer a single scale from the spacing parameter. However, you can also specify multiple scales to sample at, for example: --detector densesampling --ds_spacing 10 --ds_scales 1.2+2.0 Additional options for the dense sampling detector: --ds_spacing pixels [default: 6] --ds_scales scale1+scale2+... The default sampling scale for a spacing of 6 pixels is 1.2. Descriptors The following descriptors are available (the name to pass to --descriptoris shown in parentheses): RGB histogram (rgbhistogram) Opponent histogram (opponenthistogram) Hue histogram (huehistogram) rg histogram (nrghistogram) Transformed Color histogram (transformedcolorhistogram) Color moments (colormoments) Color moment invariants (colormomentinvariants) SIFT (sift) HueSIFT (huesift) HSV-SIFT (hsvsift) OpponentSIFT (opponentsift) rgSIFT (rgsift) C-SIFT (csift) RGB-SIFT(rgbsift), equal to transformed color SIFT (transformedcolorsift). See the journal paper for equivalence. File format (text) Files written using --output <filename>look as follows: KOEN1 10 4 <CIRCLE 91 186 16.9706 0 0>; 28 45 4 0 0 0 9 14 10 119; <CIRCLE 156 179 16.9706 0 0>; 7 82 80 62 23 2 15 6 21 23; <CIRCLE 242 108 12 0 0>; 50 67 10 0 0 0 69 44 31 23 0 1; <CIRCLE 277 105 14.2705 0 0>; 21 12 0 0 7 18 127 50 2 0 0; The first line is used as a marker for the file format. The second line specifies the dimensionality of the point descriptor. The third line describes the number of points present in the file. Following this header, there is one line per point. The per-point lines all consist of two parts: a description of the point (<CIRCLE x y scale orientation cornerness>) and a list of numbers, the descriptor vector. These two parts can be seperated through the semicolon ;. The xand ycoordinates start counting at 1, like Matlab. By default, the program uses a Harris-Laplace scale-invariant point detector to obtain the scale-invariant points in an image (these are refered to as CIRCLE in the file format of the descriptors). *
 */
?>
