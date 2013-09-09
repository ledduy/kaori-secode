<?php

/**
 * 		@file 	ksc-ProcessOneRun-Train-New-ComputeKernel.php
 * 		@brief 	Compute Kernel Matrix to Train One Run .
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 03 Sep 2013.
 */

//*** Update Sep 03, 2013
// Specific for ImageCLEF
/*
//--> New mapping
// + .pos.ann --> TRECVID2011_19545 #$# TRECVID2011_19545.shot19545_4 #$# TRECVID2011_19545.shot19545_4.RKF_RKF #$# Pos
//--> BEFORE: video path for TRECVID2011_19545 is stored in tv2012.devel-nist.lst
//--> CURRENT: video path for TRECVID2011_19545 is found by
//>>> mapping TRECVID2011_19545 to new video program NEWTV2012_xxxx
//>>> find the mapping in tv2012.devel-nistNew.lst

// Customization for ImageCLEF --> much simpler than TRECVID-SIN
function loadVideoPathMappingImageCLEF($szMetaDataDir, $szPatName)
 */


//*** Update Jul 18, 2012
// Customize for tvsin12 --> MAJOR changes --> search for Jul 18
// Collecting feature vectors for training (4K + 40K) might take more than ONE hour

// ************ NEW **************
//- All feature files are stored in one public dir in /local/ledduy so that ALL concepts (of one run config type, i.e. ignore dup systems .Rxx) can be used
//---> HOW to handle conflicts, because when a file is copying, check file_exists() and filesize() does not work
// --> try cp -u
//- Keyframes are organized into NewVideoID-->OrigVideoID-->KeyFrame so that one feature file for one new video program is loaded ONCE
//  Neg is processed before Pos to reduce duplication of copying files

// Sleep 60 seconds for waiting copying process per cycle, max 10 cycles
// Use exit if Empty feature list  --> Run several rounds for INCOMPLETE runs
// FACTS: some .flag files are not DELETED --> DEADLOCK

// Use 4K + 40K for concepts such as Face --> training time took ~54 hours
/////////////////////////////////

//--> Experiment Dir, Metadata Dir and Feature Dir might be DIFFERENT
//--> New mapping
// + .pos.ann --> TRECVID2011_19545 #$# TRECVID2011_19545.shot19545_4 #$# TRECVID2011_19545.shot19545_4.RKF_RKF #$# Pos
//--> BEFORE: video path for TRECVID2011_19545 is stored in tv2012.devel-nist.lst
//--> CURRENT: video path for TRECVID2011_19545 is found by
//>>> mapping TRECVID2011_19545 to new video program NEWTV2012_xxxx
//>>> find the mapping in tv2012.devel-nistNew.lst

// Update Jun 27, 2012
// !!! IMPORTANT !!!
// Best g --> 2^-6 = 0.015 for local features, C=1  --> use this for reducing the training time (for searching best params)
// Best range for g = [-4, -8]

// Update Nov 25, 2011
// $fSamplingRate = 1, $nKeyFrameBasedAssociation = 1 --> fixed to work not only video data (e.g. TRECVID), but image data (e.g. imageCLEF, imageNET)


// !!! IMPORTANT PARAMS
/*
 "max_kf_shot_devel_neg #$# 1", --> max keyframes per shot to include into the devel set, use 1 if wanting to include a subset of keyframes per shot
 "max_kf_shot_devel_pos #$# 5", --> max keyframes per shot to include into the devel set, use 5, 10, 20 if wanting to include all keyframes
 "neg_sampling_rate #$# 1.0", --> shot sampling rate *** SHOULD NOT CHANGE ***  --> NOW IGNORED
 "pos_sampling_rate #$# 1.0", --> shot sampling rate *** SHOULD NOT CHANGE *** --> NOW IGNORED
 "use_keyframe_annotation #$# 1", --> TRECVID annotation style *** SHOULD NOT CHANGE *** --> NOW IGNORED
 */

/*
 $arDefaultConfig = array(
 "max_kf_test #$# 5",
 "kernel #$# 5 #$# Chi-Square RBF",
 "use_keyframe_annotation #$# 1",
 "svm_train_pos_weight #$# -1 #$# Auto",
 "svm_train_neg_weight #$# -1 #$# Auto",
 "svm_grid_start_C #$# 0",
 "svm_grid_end_C #$# 4",
 "svm_grid_step_C #$# 2",
 "svm_grid_start_G #$# -10",
 "svm_grid_end_G #$# 0",
 "svm_grid_step_G #$# 2",
 "max_kf_devel_pos_set #$# 10000",
 "max_kf_devel_neg_set #$# 20000",
 "max_kf_devel_sub_size #$# 3000",
 "max_kf_shot_devel_neg #$# 1",
 "max_kf_shot_devel_pos #$# 5",
 "neg_sampling_rate #$# 1.0",
 "pos_sampling_rate #$# 1.0",
 "svm_train_mem_size #$# 700",
 );
 */
/////////////////////////////////////////////////////////////\
// *********** Update Jun 18 ***********
/*
 if($gfPosWeight == -1 || $gfNegWeight == -1)
 {
 $gfPosWeight = intval($nTotalNegSamples/$nTotalPosSamples);

 // Found this BUG when working with imageCLEF2011
 if(!$gfPosWeight)
 {
 $gfPosWeight = 1;
 }
 $gfNegWeight = 1;
 }
 */

// *********** Update May 14 ***********
// Check whether training sample file is non-zero
// 	if(!$nTotalPosSamples)
	//	{
	//		printf("Training samples not found\n");
	//		continue;
	//	}


	// *********** Update Mar 06 ***********
	// Adjust params for training: memsize, epsilon, shrinking heuristic
	// $gnMemSize = min($arRunConfig['svm_train_mem_size'], 1500);

	// *********** Update Feb 28 ***********
	// $nMinTarModelSize = 100*1024; // 1MB = 1024x1024MB, for checking file size
	// $nMinModelSize = $nMinTarModelSize*2;

	// *********** Update Feb 21 ***********
	// Test perf of harhes.Soft-500-VL2z with epsilon = 0.001

	// *********** Update Feb 17 ***********
	// Adding unset() for forcing the memory free

	// *********** Update Feb 16 ***********
	// Test perf of harhes.Soft-500-VL2 with epsilon = 0.1  --> < 0.5 & 0.001

	// *********** Update Feb 15 ***********
	// Set MaxMemSize for SVM Training --> 500M
	// $gnMemSize = min($arRunConfig['svm_train_mem_size'], 500);

	// *********** Update Feb 12 ***********
	// set $gnEpsilon = 0.001; (i.e default value)
	//$gnEpsilon = 0.5; // --> number of sv is much smaller (~1/2) --> perf degraded --> but training is very fast

	// *********** Update Jan 29 ***********
	// Adding epsilon param --> 0.5 to handle the case of long training time, turn of shrinking heuristic
	//$gszSVMSubParam = sprintf("-w1 %f -w-1 %f -m %d -e %f -h 0", $gfPosWeight, $gfNegWeight, $gnMemSize, $gnEpsilon);

	// *********** Update Jan 25 ***********
	// Modify default gszFeatureFormat
	// bow  --> svf
	//	if(strstr($szFeatureExt, "bow"))
		//	{
		//		$gszFeatureFormat = "svf";
		//	}
		//	else
			//	{
			//		$gszFeatureFormat = "dvf";
			//	}

			// *********** Update Jan 02 ***********
			// Adding param $nSkipExistingModels = 1;

			// *********** Update Dec 27 ***********
			// Adding description for steps of generating training set
			// A bit complicated when using devel-nist and devel-cv374 datasets for tv2008-tv2010

			// *********** Update Dec 24 ***********
			// Supporting feature format param in run config --> FeatureFormat for svf

			// *********** Update Dec 06 ***********
			// No need to archive training data for saving disk spaces

			// *********** Update Nov 25 ***********
			// Support linear SVM + svf (sparse vector format) --> adding function to load GZSvf data
			// Adding global param - feature format

			// *********** Update 18 Oct ***********
			// Previous: only shot info is used for associating annotation and feature
			// This one might cause many errors in training set, e.g situation of tv2010 with many blank frames
			// NOW: Handle this case by utilizing keyframe info which LIG annotation was  based on
			// Check sys_id for consistency with file name  --> USE the core name, so sys_id is UNUSED

			// *********** Update 15 Oct ***********
			// Add to arRunConfig params for svm_grid
			// + (startC, endC, stepC) and (startG, endG, stepG)
			// + memsize
			// + pos, neg weight
			// + prob output

			// *********** Update 14 Oct ***********
			// Allow to change devel_pat and test_pat in each run config
			// KeyFrameID must follow the format: videoID.shotID.KeyFrameID
			// 	--> videoID and shotID can be extracted by parsing with .
			// shot111_1_RKF  --> TRECVID2007_219.shot219_1.RKF --> KSC format of keyframe ID

			// *********** Update 03 Oct ***********
			//  Move $szExpConfig = "hlf-tv2005" to nsc-ProcessOneRun-Train-TV10-SGE.php;
			//	Should check szTmpDir

			// *********** Update 02 Oct ***********
			// Model file will be saved in tmp dir, compressed and then move to the archive
			// C is large, e.g. C=16, --> long time to convergence !!! IMPORTANT
			// Train classifiers with -b option  --> modify SVMTools by adding one more param

			// Q: The training time is too long. What should I do?
			// For large problems, please specify enough cache size (i.e., -m).
			// Slow convergence may happen for some difficult cases (e.g. -c is large).
			// You can try to use a looser stopping tolerance with -e.
			// If that still doesn't work, you may train only a subset of the data.
			// You can use the program subset.py in the directory "tools" to obtain a random subset.
			// When using large -e, you may want to check if -h 0 (no shrinking) or -h 1 (shrinking) is faster. See a related question below.
			// If the number of iterations is high, then shrinking often helps.
			// However, if the number of iterations is small (e.g., you specify a large -e), then probably using -h 0 (no shrinking) is better.

			// Q: Why using the -b option does not give me better accuracy?
			// There is absolutely no reason the probability outputs guarantee you better accuracy.
			// The main purpose of this option is to provide you the probability estimates, but not to boost prediction accuracy.
			// From our experience, after proper parameter selections, in general with and without -b have similar accuracy.
			// Occasionally there are some differences. It is not recommended to compare the two under just a fixed parameter set as more differences will be observed.

			// *********** Update Oct 01 ***********
			//  Most important param: $szExpConfig = "hlf-tv2005";

			// *********** Update Aug 27 ***********
			// Posweight change to 100 (old value: 1000)
			// Change params:
			// $szExpConfig --> hlf-tv2005
			// $szRootDir = "/net/sfv215/export/raid6";

			// *********** Update Aug 9 ***********
			// CHANGE - select multiple keyframes/shots, instead of starting 0, starting from the middle

			// *********** Update Aug 6 - see !!! IMPORTANT ***********
			// Fixed memory problem when the training sample (neg) is huge --> flushing temporary data into files, default 5,000.
			// Check the model file existing or not

			// *********** Update Aug 4 ***********
			// Change the way select keyframes by using shuffle_assoc for $arAnnList (randomize video prgs) and $arShotList (randomize shots)
			// Prev version --> not good when the number of samples is huge

			// This version is for TV10.
			// + limit the number of keyframes for each training set (pos or neg)
			// + support multi annotation sources
			// + support compressed feature file

			// Selection process
			// 1. Load all shots of ann and a subset of shots is selected
			// 2. Shuffle video list, pick one video
			// 3. Load all shots of that video, and pick shots belonging to selected subset.
			// 4. For picked shot, select a subset of keyframes
			// 5. If the number of keyframes is larger than MAX, then STOP

			// Prev version did not shuffle the video list, so only shots of top videos are selected because the limit of MAX keyframes

			/////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";

//////////////////// THIS PART FOR CUSTOMIZATION ////////////////////

// !!! IMPORTANT --> this global param is used in generateTrainData
$gnMaxMemSize = 500; // only keep 500 points in mem
$gszFeatureFormat = "dvf";

$nSkipExistingModels = 1;

$nMinTarModelSize = 50*1024; // 1MB = 1024x1024MB, for checking file size
$nMinModelSize = $nMinTarModelSize*2;


// !!! IMPORTANT
// must check pos weight and neg weight
$gfPosWeight = 100;
$gfNegWeight = 1;

// memsize is override later
$gnMemSize = 8000;  // more mem size since the data set is large

$gnStartC = 0;
$gnEndC = 2; // C should not be too large since it will make slow convergence when the data set is large
$gnStepC = 2;
$gnStartG = -8;
$gnEndG = -4;
$gnStepG = 2;

$gnKernelType = 5; // default CHISQUARE-RBF kernel

// !!! IMPORTANT
$gnUseProbOutput = 1; // to normalize scores for fusion later

// This config is for fast training and testing, but may cause lower accuracy
//$gnEpsilon = 0.5; // --> number of sv is much smaller (~1/2) --> perf degraded --> but training is very fast --> NOT IMPRESSIVE
//$gnShrinkingHeuristic = 0;

// This config is default by LibSVM, slow for training and testing if the number of dimensions and samples are large, but more accuracy
//$gnEpsilon = 0.001; // default value was 0.001
//$gnShrinkingHeuristic = 1; // used with 0.001

// This config is hoped to balance between training&testing time and accuracy
$gnEpsilon = 0.1; // test the trade-off between the accuracy and speed --> NOT IMPRESSIVE ???
$gnShrinkingHeuristic = 0;

// !!! IMPORTANT these are modified later using global var-- keep this for just reference
// pos label is +1 and neg label is -1
// in training data, pos samples must appear before neg samples --> useful for prediction later.
$gszSVMSubParam = sprintf("-w1 %f -w-1 %f -m %d -e %f -h %d", $gfPosWeight, $gfNegWeight, $gnMemSize, $gnEpsilon, $gnShrinkingHeuristic);
//$szSearchRange = "-log2c 0,4,2 -log2g -18,-2,4";  // 3*5 points/grid
$gszSVMSearchRange = sprintf("-log2c %d,%d,%d -log2g %d,%d,%d", $gnStartC, $gnEndC, $gnStepC, $gnStartG, $gnEndG, $gnStepG);


//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$gszRootBenchmarkExpDir = $gszRootBenchmarkDir;

$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
$szFPLogFN = sprintf("%s.log", $szScriptBaseName); //*** CHANGED ***

//////////////////// END FOR CUSTOMIZATION ////////////////////

/////////////////////// START ////////////////////////
$szExpName = "imageclef2012-PhotoAnnFlickr";
$szFPRunConfigFN = "/net/sfv215/export/raid6/ledduy/ImageCLEF/2012/PhotoAnnFlickr/experiments/imageclef2012-PhotoAnnFlickr/runlist/imageclef2012-PhotoAnnFlickr.nsc.bow.dense6mul.rgbsift.Soft-500.devel2012.L1norm1x1.ksc.imageclef2012.R1.cfg";

$nStart = 0;
$nEnd = 1;

if($argc!=5)
{
	printf("Number of params [%s] is incorrect [5]\n", $argc);
	printf("Usage %s <ExpConfig> <RunConfigFN> <StartConcept> <EndConcept>\n", $argv[0]);
	printf("Usage %s %s %s %s %s\n", $argv[0], $szExpConfig, $szFPRunConfigFN, $nStart, $nEnd);
	exit();
}

// $szFPRunConfigFN = sprintf("%s/trecvid/experiments/hlf-tv2007/hlf-tv2007.run001.dkf-5.tkf-5.cfg", $szRootDir);

$szExpConfig = $argv[1];
$szFPRunConfigFN = $argv[2];
$nStart = intval($argv[3]);
$nEnd = intval($argv[4]);

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]",
		$szStartTime,
		$argv[1], $argv[2], $argv[3], $argv[4]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");


//$szRootDir = "U:";
$szRootExpDir = sprintf("%s", $gszRootBenchmarkExpDir); // New Jul 18

$szFPConfigFN = sprintf("%s/experiments/%s/%s.cfg", $szRootExpDir, $szExpConfig, $szExpConfig);

$arConfig = loadExperimentConfig($szFPConfigFN);
$arRunConfig = loadExperimentConfig($szFPRunConfigFN);

// pick the file name as sysID
// hlf-tv2012.nsc.bow.dense6mul.rgbsift.Soft-500-VL2.tv2012.devel-nistNew.norm3x1.ksc
$szSysID = basename($szFPRunConfigFN, ".cfg");

// Update Jul 18
$arTmpzz = explode(".ksc", $szSysID);
$gszFeatureDirFromSysID = trim($arTmpzz[0]);

$szFeatureExt =  $arRunConfig['feature_ext']; //"nsc.cCV_HSV.g5.q3.g_cm";

$szRootExpDir = sprintf("%s/%s", $arConfig['exp_dir'], $arConfig['exp_name']);

/// !!! IMPORTANT
if(isset($arRunConfig['sub_ann']))
{
	$szExpAnnDir = sprintf("%s/%s/%s", $szRootExpDir, $arConfig['ann_dir'], $arRunConfig['sub_ann']); // annotation
}
else
{
	// if having multiple annotation sources
	$szExpAnnDir = sprintf("%s/%s", $szRootExpDir, $arConfig['ann_dir']); // annotation
}

$szExpMetaDataDir = sprintf("%s/%s", $szRootExpDir, $arConfig['metadata_dir']); // annotation
$szExpModelDir = sprintf("%s/%s", $szRootExpDir, $arConfig['model_dir']); // annotation
$szExpResultDir = sprintf("%s/%s", $szRootExpDir, $arConfig['result_dir']); // annotation

// !!! IMPORTANT CHANGE Update Jul 18
//$szRootMetaDataKFDir = $arConfig['root_metadata_kf_dir'];
//$szRootFeatureDir = $arConfig['root_feature_dir']; //

$szRootMetaDataKFDir = getRootBenchmarkMetaDataDir($szFeatureExt);
$szRootFeatureDir = getRootBenchmarkFeatureDir($szFeatureExt);

$szTrainVideoList = $arRunConfig['devel_pat'];

// !!! IMPORTANT CHANGE Update Jul 18
$szPatName = str_replace(".lst", "", $szTrainVideoList);

$szMetaDataDirzz = sprintf("%s", $szRootMetaDataKFDir);
$arNEWVideoPathList = loadVideoPathMappingImageCLEF($szMetaDataDirzz, $szPatName);

// new param for feature format - Dec 24
if(isset($arRunConfig['feature_format']))
{
	$gszFeatureFormat = $arRunConfig['feature_format'];
}
else
{
	// bow  --> svf
	if(strstr($szFeatureExt, "bow"))
	{
		$gszFeatureFormat = "svf";
	}
	else
	{
		$gszFeatureFormat = "dvf";
	}
}
$szFeatureFormat = $gszFeatureFormat;
printf("###Feature format: %s\n", $gszFeatureFormat);

// new params for associating annotated shots/keyframes with features
$nKeyFrameBasedAssociation = $arRunConfig['use_keyframe_annotation'];

// new params for run config file
$gfPosWeight = $arRunConfig['svm_train_pos_weight'];
$gfNegWeight = $arRunConfig['svm_train_neg_weight'];

$gnMemSize = min($arRunConfig['svm_train_mem_size'], 1500);  // max 1500MB for training
$gnStartC = $arRunConfig['svm_grid_start_C'];
$gnEndC = $arRunConfig['svm_grid_end_C'];
$gnStepC = $arRunConfig['svm_grid_step_C'];
$gnStartG = $arRunConfig['svm_grid_start_G'];;
$gnEndG = $arRunConfig['svm_grid_end_G'];;
$gnStepG = $arRunConfig['svm_grid_step_G'];

// these param (kernel type and prob output) are set via global param
$gnKernelType = $arRunConfig['kernel'];
$gnUseProbOutput = $arRunConfig['svm_train_use_prob_output'];

$gnPerformDataScaling = 1; // default --> OLD ONE
if(isset($arRunConfig['svm_scaling']))
{
    $gnPerformDataScaling = $arRunConfig['svm_scaling'];
}

// pos label is +1 and neg label is -1
// in training data, pos samples must appear before neg samples --> useful for prediction later.
$gszSVMSubParam = sprintf("-w1 %f -w-1 %f -m %d -e %f -h %d", $gfPosWeight, $gfNegWeight, $gnMemSize, $gnEpsilon, $gnShrinkingHeuristic);

//$szSearchRange = "-log2c 0,4,2 -log2g -18,-2,4";  // 3*5 points/grid
$gszSVMSearchRange = sprintf("-log2c %d,%d,%d -log2g %d,%d,%d", $gnStartC, $gnEndC, $gnStepC, $gnStartG, $gnEndG, $gnStepG);

// Update Oct 14
if(isset($arRunConfig['devel_pat']))
{
	$szTrainVideoList = $arRunConfig['devel_pat'];
}

$szFPTrainVideoListFN = sprintf("%s/%s", $szExpMetaDataDir, $szTrainVideoList); // trecvid.video.tv2007.devel.lst

$szFPConceptListFN = sprintf("%s/%s/%s.lst", $szRootExpDir, $arConfig['ann_dir'], $arConfig['concept_list']);

$fSamplingPosRate = $arRunConfig['pos_sampling_rate']; //0.75
$nNumKeyFramesPerPosShot = $arRunConfig['max_kf_shot_devel_pos']; //2
$nMaxPosKeyFramesPerRun = $arRunConfig['max_kf_devel_pos_set'];

$fSamplingNegRate = $arRunConfig['neg_sampling_rate']; //0.75
$nNumKeyFramesPerNegShot = $arRunConfig['max_kf_shot_devel_neg']; //2
$nMaxNegKeyFramesPerRun = $arRunConfig['max_kf_devel_neg_set'];
$nMaxSubSamples = $arRunConfig['max_kf_devel_sub_size'];

// Oct 18
if($szSysID != $arRunConfig['sys_id'])
{
	$arRunConfig['sys_id'] = $szSysID;
}
$szExpModelRunDir = sprintf("%s/%s", $szExpModelDir, $arRunConfig['sys_id']);
makeDir($szExpModelRunDir);

$nNumConcepts = loadListFile($arConceptList, $szFPConceptListFN);

if($nEnd > $nNumConcepts)
{
	$nEnd = $nNumConcepts;
}

// $nStart = 0;
// $nEnd = 1;

for($i=$nStart; $i<$nEnd; $i++)
{
	$szLine = $arConceptList[$i];
	// new format
	// 9003.Airplane #$# Airplane #$# 029 #$# Airplane #$# 218 #$# 003
	$arTmp = explode("#$#", $szLine);
	$szConceptName = trim($arTmp[0]);

	$szExpModelRunConceptDir = sprintf("%s/%s", $szExpModelRunDir, $szConceptName);
	makeDir($szExpModelRunConceptDir);

	/// !!! IMPORTANT
	$szTrainDataName = sprintf("%s.%s.svm", $szConceptName, $szFeatureExt);

	$szFPModelFN = sprintf("%s/%s.model", $szExpModelRunConceptDir, $szTrainDataName);

	if($nSkipExistingModels)
	{
		$szFPModelTarGZFN = sprintf("%s.tar.gz", $szFPModelFN);
		if((file_exists($szFPModelFN) && filesize($szFPModelFN)>=$nMinModelSize) || (file_exists($szFPModelTarGZFN) && filesize($szFPModelTarGZFN)>=$nMinTarModelSize))
		{
			printf("#@@@ Model [%s] already trained!\n", $szFPModelFN);
			continue;
		}
	}

	/// !!! IMPORTANT
	// /local/ledduy/results/hlf-tv2010.run000001/9004.Airplane_Flying
	/*
	 if(file_exists("/local/ledduy")) // use /local/ledduy
	{
	$szTmpDir = sprintf("/local/ledduy/%s/%s/%s", $arConfig['model_dir'], $arRunConfig['sys_id'], $szConceptName);
	makeDir($szTmpDir);
	}
	else // use /tmp
	{
	$szTmpDir = sprintf("/net/per900b/raid0/ledduy/tmp/tmp/%s/%s/%s", $arConfig['model_dir'], $arRunConfig['sys_id'], $szConceptName);
	makeDir($szTmpDir);
	}
	*/
	// Update Jul 18, 2012
	$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
	$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig
	$szTmpDir = sprintf("%s/%s/%s/%s/%s", $szLocalTmpDir, $szScriptBaseName, $arConfig['model_dir'], $arRunConfig['sys_id'], $szConceptName);
	makeDir($szTmpDir);


	//	$szFPFullFeatureOutputFN = sprintf("%s/%s.svm", $szExpModelRunConceptDir, $szConceptName);
	$szFPFullFeatureOutputFN = sprintf("%s/%s.%s.svm", $szTmpDir, $szConceptName, $szFeatureExt);

	$szFeatureDir = sprintf("%s/%s", $szRootFeatureDir, $szFeatureExt);
	//$szFeatureDir = sprintf("%s/baseline.%s", $szRootFeatureDir, $szFeatureExt);

	// Update Jul 18 --> Neg is processed before Pos to reduce duplication of copying files
	$szFPNegFeatureOutputFN = sprintf("%s/%s.%s.neg.svm", $szTmpDir, $szConceptName, $szFeatureExt);
	$szFPNegFeatureAnnOutputFN = sprintf("%s/%s.%s.neg.ann", $szExpModelRunConceptDir, $szConceptName, $szFeatureExt);

	$szFPAnnNegFN = sprintf("%s/%s.neg.ann", $szExpAnnDir, $szConceptName);

	$nNegLabel = -1;

	// $szRootMetaDataKFDir --> UNUSED - Jul 18, 2012
	$nTotalNegSamples = generateTrainData($szTmpDir, $szFPNegFeatureOutputFN, $szFPNegFeatureAnnOutputFN,
			$szFPAnnNegFN, $szFPTrainVideoListFN, $szRootMetaDataKFDir, $szFeatureDir,
			$szFeatureExt,
			$fSamplingNegRate, $nNumKeyFramesPerNegShot, $nMaxNegKeyFramesPerRun, $nNegLabel, $nKeyFrameBasedAssociation);

	if(!$nTotalNegSamples)
	{
		printf("### NEG training samples not found\n");
		//continue;
		global $szFPLogFN;
		$arLogzz = array();
		$szErrorLog = sprintf("###SERIOUS ERR - %s!!! NEG training samples not found [%s] - Concept [%s]\n",
				date("m.d.Y - H:i:s:u"), $szFPNegFeatureOutputFN, $szConceptName);
		$arLogzz[] = $szErrorLog;
		saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");

		exit();  // Update Jul 18, 2012
	}

	// store in /tmp dir to avoid overload when running on SGE
	$szFPPosFeatureOutputFN = sprintf("%s/%s.%s.pos.svm", $szTmpDir, $szConceptName, $szFeatureExt);
	// keep this file
	$szFPPosFeatureAnnOutputFN = sprintf("%s/%s.%s.pos.ann", $szExpModelRunConceptDir, $szConceptName, $szFeatureExt);

	$szFPAnnPosFN = sprintf("%s/%s.pos.ann", $szExpAnnDir, $szConceptName);

	$nPosLabel = 1;

	// $szRootMetaDataKFDir --> UNUSED - Jul 18
	$nTotalPosSamples = generateTrainData($szTmpDir, $szFPPosFeatureOutputFN, $szFPPosFeatureAnnOutputFN,
			$szFPAnnPosFN, $szFPTrainVideoListFN, $szRootMetaDataKFDir, $szFeatureDir,
			$szFeatureExt,
			$fSamplingPosRate, $nNumKeyFramesPerPosShot, $nMaxPosKeyFramesPerRun, $nPosLabel, $nKeyFrameBasedAssociation);

	if(!$nTotalPosSamples)
	{
		printf("### POS training samples not found\n");
		//continue;

		global $szFPLogFN;
		$arLogzz = array();
		$szErrorLog = sprintf("###SERIOUS ERR - %s!!! POS training samples not found [%s] - Concept [%s]\n",
				date("m.d.Y - H:i:s:u"), $szFPPosFeatureOutputFN, $szConceptName);
		$arLogzz[] = $szErrorLog;
		saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");

		exit(); // Update Jul 18, 2012
	}

	// tar and copy for achiving  --> NO need to archive on update Dec 06
	$szCmdLine = sprintf("tar -cvzf %s/%s.tar.gz -C %s %s",
			$szTmpDir, basename($szFPPosFeatureOutputFN), $szTmpDir, basename($szFPPosFeatureOutputFN));
	//system($szCmdLine);
	$szCmdLine = sprintf("mv %s/%s.tar.gz %s",
			$szTmpDir, basename($szFPPosFeatureOutputFN), $szExpModelRunConceptDir);
	//system($szCmdLine);

	$szCmdLine = sprintf("tar -cvzf %s/%s.tar.gz -C %s %s",
			$szTmpDir, basename($szFPNegFeatureOutputFN), $szTmpDir, basename($szFPNegFeatureOutputFN));
	//system($szCmdLine);
	$szCmdLine = sprintf("mv %s/%s.tar.gz %s",
			$szTmpDir, basename($szFPNegFeatureOutputFN), $szExpModelRunConceptDir);
	//system($szCmdLine);

	loadListFile($arFeatureList, $szFPPosFeatureOutputFN);
	saveDataFromMem2File($arFeatureList, $szFPFullFeatureOutputFN, "wt");

	loadListFile($arFeatureList, $szFPNegFeatureOutputFN);
	saveDataFromMem2File($arFeatureList, $szFPFullFeatureOutputFN, "a+t");

	global $gszSVMSearchRange;
	global $gszSVMSubParam;
	global $gnEpsilon, $gnShrinkingHeuristic;

	$szTrainDataName = sprintf("%s.%s.svm", $szConceptName, $szFeatureExt);

	// !!! IMPORTANT -- adding nKernelType to support generalized RBF kernels such as LAPLACIAN, CHISQRBF
	$nUseProbOutput = $gnUseProbOutput;
	$nKernelType = $gnKernelType;

	// set by #Neg / # Pos
	if($gfPosWeight == -1 || $gfNegWeight == -1)
	{
		$gfPosWeight = intval($nTotalNegSamples/$nTotalPosSamples);

		// Found this BUG when working with imageCLEF2011
		if(!$gfPosWeight)
		{
			$gfPosWeight = 1;
		}
		$gfNegWeight = 1;
	}

	// pos label is +1 and neg label is -1
	// in training data, pos samples must appear before neg samples --> useful for prediction later.
	$gszSVMSubParam = sprintf("-w1 %f -w-1 %f -m %d -e %f -h %d", $gfPosWeight, $gfNegWeight, $gnMemSize, $gnEpsilon, $gnShrinkingHeuristic);

	//$szSearchRange = "-log2c 0,4,2 -log2g -18,-2,4";  // 3*5 points/grid
	$gszSVMSearchRange = sprintf("-log2c %d,%d,%d -log2g %d,%d,%d", $gnStartC, $gnEndC, $gnStepC, $gnStartG, $gnEndG, $gnStepG);

	// path to libsvm291 is defined in nsc-AppConfig
	$szSearchRange = $gszSVMSearchRange;
	$szSubParam = $gszSVMSubParam;

	if($nKernelType)
	{
		runTrainClassifier($szTrainDataName, $szTmpDir, $szExpModelRunConceptDir,
				$szSearchRange, $szSubParam, $nMaxSubSamples, $nUseProbOutput, $nKernelType);
	}
	else // linear kernel
	{
		runTrainLinearClassifier($szTrainDataName, $szTmpDir, $szExpModelRunConceptDir,
				$szSubParam, $nUseProbOutput);
	}

	// !!! IMPORTANT - model file is compressed
	$szModelOutputFN = sprintf("%s.model", $szTrainDataName);
	$szFPModelOutputFN = sprintf("%s/%s", $szExpModelRunConceptDir, $szModelOutputFN);

	$szCmdLine = sprintf("tar -cvzf %s.tar.gz -C %s %s", $szFPModelOutputFN, $szExpModelRunConceptDir, $szModelOutputFN);
	system($szCmdLine);

	deleteFile($szFPModelOutputFN);

	deleteFile($szFPPosFeatureOutputFN);
	deleteFile($szFPNegFeatureOutputFN);

	$szCmdLine = sprintf("rm -rf %s", $szTmpDir);
	system($szCmdLine);
}

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]",
		$szStartTime, $szFinishTime,
		$argv[1], $argv[2], $argv[3], $argv[4]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

///////////////////////////////// FUNCTIONS ///////////////////////////////
// $szFPAnnFN .pos or .neg

/**
 * Load keyframe list of one video program and organize into shot/keyframe
 * This is used to select a subset of keyframes per shot
 *
 * !!! IMPORTANT:
 * 	KeyFrameID must follow the format: videoID.shotID.KeyFrameID
 * 	--> videoID and shotID can be extracted by parsing with .
 *
 * @param $szFPKeyFrameListFN
 */
function loadKeyFrameList($szFPKeyFrameListFN)
{
	loadListFile($arRawList, $szFPKeyFrameListFN);

	$arKeyFrameList = array();
	foreach($arRawList as $szKeyFrameID)
	{
		// TRECVID2005_100.shot100_1.RKF_0.Frame_2
		$arTmp = explode(".", $szKeyFrameID);

		$szVideoID = trim($arTmp[0]);
		$szShotID = trim($arTmp[1]);
		$szFullShotID = sprintf("%s.%s", $szVideoID, $szShotID);

		$arKeyFrameList[$szVideoID][$szFullShotID][] = $szKeyFrameID;
	}

	return $arKeyFrameList;
}

/**
 * @param $szTmpDir --> used for extracting tar.gz feature file
 * @param $szFPFeatureOutputFN ~ in libsvm format
 * @param $szFPFeatureAnnOutputFN ~ for association later
 * @param $szFPAnnFN ~ annotation of pos samples
 * @param $szFPVideoListFN ~ to get video path
 * @param $szRootKFListDir ~ to get .prg files
 * @param $szRootFeatureDir
 * @param $szFeatureExt
 * @param $nNumKeyFramesPerShot
 * @param $nMaxKeyFrames ~ max keyframes selected for generating training data
 *
 */

/*
 $arDefaultConfig = array(
 		"max_kf_test #$# 5",
 		"kernel #$# 5 #$# Chi-Square RBF",
 		"use_keyframe_annotation #$# 1",
 		"svm_train_pos_weight #$# -1 #$# Auto",
 		"svm_train_neg_weight #$# -1 #$# Auto",
 		"svm_grid_start_C #$# 0",
 		"svm_grid_end_C #$# 4",
 		"svm_grid_step_C #$# 2",
 		"svm_grid_start_G #$# -10",
 		"svm_grid_end_G #$# 0",
 		"svm_grid_step_G #$# 2",
 		"max_kf_devel_pos_set #$# 10000",
 		"max_kf_devel_neg_set #$# 20000",
 		"max_kf_devel_sub_size #$# 3000",
"max_kf_shot_devel_neg #$# 1",
"max_kf_shot_devel_pos #$# 5",
"neg_sampling_rate #$# 1.0",
"pos_sampling_rate #$# 1.0", --> shot sampling rate
"svm_train_mem_size #$# 700",
);
*/

// Update Oct 18 --> allow both keyframe based association (done by NIST) and shot based association
// Update Oct 15 --> return number of training samples
// Modify MaxMemSize for cache
// TRECVID2007_68 #$# TRECVID2007_68.shot68_28 #$# TRECVID2007_68.shot68_28_RKF #$# Pos
// label info is ignored because we know the label as a param and assume the lable is equal to the input param

function generateTrainData($szTmpDir, $szFPFeatureOutputFN, $szFPFeatureAnnOutputFN,
$szFPAnnFN, $szFPVideoListFN,
$szRootKFListDir, $szRootFeatureDir, $szFeatureExt,
$fSamplingRate=1.0, $nNumKeyFramesPerShot=2, $nMaxKeyFrames=20000,
$nLabel = 1, $nKeyFrameBasedAssociation=1)
{
	global $gnMaxMemSize;
	global $gszFeatureFormat;


	$szFeatureFormat = $gszFeatureFormat;
	//printf("### Processing feature format: %s\n", $szFeatureFormat);
	$nMaxMemSize = $gnMaxMemSize; // only keep 500 points in mem

	printf("### Loading the list of videos of the training set ...\n");
	// load list of video progs of the training set, e.g. tv2007.devel.lst
	//  generate arVideoList[videoID] = videoPath
	loadListFile($arVideoList, $szFPVideoListFN);
	$arVideoPathList = array();
	foreach($arVideoList as $szLine)
	{
		// TRECVID2005_1 #$# 20041116_110000_CCTV4_NEWS3_CHN #$# tv2005/test
		$arTmp = explode("#$#", $szLine);

		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);
		$arVideoPathList[$szVideoID] = $szVideoPath;
	}

	printf("### Loading the annotation...\n");
	// load annotation, each row is for one shot
	// generate shot list arAllShotList[$szFullShotID]
	loadListFile($arRawList, $szFPAnnFN);

	// re-arrange ann
	$arAnnList = array();

	$arAllShotList = array(); // for sub-sampling
	foreach($arRawList as $szLine)
	{
		// TRECVID2007_68 #$# TRECVID2007_68.shot68_28 #$# TRECVID2007_68.shot68_28_RKF #$# Pos
		// label info is ignored because we know the label as a param and assume the lable is equal to the input param
		$arTmp = explode("#$#", $szLine);

		$szVideoID = trim($arTmp[0]);
		$szFullShotID = trim($arTmp[1]);

		// add keyframeID - Update Oct 18
		$szKeyFrameIDz = trim($arTmp[2]);

		$arAnnList[$szVideoID][$szFullShotID][] = $szKeyFrameIDz; // Oct 18

		$arAllShotList[$szFullShotID] = 1;
	}

	printf("### Selecting a subset of the annotation for training ...\n");

	// only pick a subset of $arAnnList
	$nTotalShots = sizeof($arAllShotList);

	// Update Nov 25, 2011
	$fSamplingRate = 1;

	$nNumSelShots = intval($fSamplingRate*$nTotalShots);
	$rand_keys = array_rand($arAllShotList, $nNumSelShots);

	// asort($rand_keys); not necessary

	// printf("Selection of %s/%s shots\n", $nNumSelShots, $nTotalShots);
	// print_r($rand_keys);

	// !!! IMPORTANT - using wt and a+t mode to flush temp data and save memory RAM
	$arAnnFeatureOutputList = array();
	saveDataFromMem2File($arAnnFeatureOutputList, $szFPFeatureOutputFN, "wt");
	$arAllKeyFrameList = array_keys($arAnnFeatureOutputList);
	saveDataFromMem2File($arAllKeyFrameList, $szFPFeatureAnnOutputFN, "wt");

	//!!! IMPORTANT CHANGE Jul 18, 2012
	// Gom tat ca cac keyframes thanh tung NewVideoID de dam bao moi lan chi doc 1 file chua tat ca keyframe of 1 video progam
	$arAllSelKFList = array();

	$nTotalFeatureVectors = 0;

	printf("### Preparing the keyframe list for getting feature vectors ...\n");

	/// NEW !!!
	// print_r($arAnnList);
	shuffle_assoc($arAnnList); // shuffle an associated array and remain the association between keys and values
	//printf("###After shuffle \n");
	//print_r($arAnnList); exit();


	foreach($arAnnList as $szVideoID => $arShotList)
	{

		/** Update Jul 18
		 if(!isset($arVideoPathList[$szVideoID]))
		 {
			printf("Video [%s] is not in the devel list!\n", $szVideoID);
			continue;
			}
			**/


		//$szVideoPath = $arVideoPathList[$szVideoID]; --> move to Update Jul 18

		/*  --> NOT USED -- Update Nov 25, 2011
		 $szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szRootKFListDir, $szVideoPath, $szVideoID);

		if(!file_exists($szFPKeyFrameListFN))
		{
		printf("File [%s] does not exist!\n", $szFPKeyFrameListFN);
		continue;
		}

		$arKeyFrameList = loadKeyFrameList($szFPKeyFrameListFN);
		*/
		/// NEW !!!
		shuffle_assoc($arShotList);
		//print_r($arShotList); exit();

		// select keyframe --> MOST IMPORTANT PART - Update Oct 18
		$arSelKFList = array();

		// this for loop is to pick the keyframe list of each selected shot
		foreach($arShotList as $szFullShotID => $arTmpzzz)
		{

			/*  --> NOT USED -- Update Nov 25, 2011
			 if(!isset($arKeyFrameList[$szVideoID][$szFullShotID]))
			 {
			printf("No keyframe list for [%s][%s]! Skipping ...\n", $szVideoID, $szFullShotID);
			continue;
			}

			if(!in_array($szFullShotID, $rand_keys))
			{
			printf("Shot [%s] was not selected!\n", $szFullShotID);
			continue;
			}
			*/

			// !!! IMPORTANT CHANGE Update Oct 18
			$arShotKFList = array();

			$nKeyFrameBasedAssociation = 1; // Update Nov 25, 2011
			if($nKeyFrameBasedAssociation)  // new Oct 18 --> only pick keyframes annotated by LIG,
			{
				$arShotKFList = $arShotList[$szFullShotID];
				//print_r($arShotKFList); exit();
			}
			else // old method --> pick all keyframes of the selected shot
			{
				exit("ERROR - This method is not supported!\n");
				// NOT USED , Update Nov 25, 2011
				$arShotKFList = $arKeyFrameList[$szVideoID][$szFullShotID];
			}

			$nNumKFs = sizeof($arShotKFList);
			//print_r($arShotKFList); exit();

			// this is for select a subset of keyframes given a keyframe list
			$nStep = intval($nNumKFs/$nNumKeyFramesPerShot);
			if($nStep <=0)
			{
				$nStep = 1;
			}

			// select keyframes from the middle (that is picked by LIG for collaborative annotation)
			$nCount = 0;

			$nMiddleKF = intval($nNumKFs/2);

			$arSelKFList[] = $arShotKFList[$nMiddleKF];
			$nCount++;

			$nNumLoops = intval($nNumKeyFramesPerShot/2);
			//printf("NumLoops - %s\n", $nNumLoops);
			for($k=1; $k<=$nNumLoops; $k++)
			{
				$nLIndex = intval($nMiddleKF-$k*$nStep);
				$nRIndex = intval($nMiddleKF+$k*$nStep);
				if($nRIndex < $nNumKFs)
				{
					$arSelKFList[] = $arShotKFList[$nRIndex];
					$nCount++;

					if($nCount>=$nNumKeyFramesPerShot)
					{
						break;
					}
				}

				if($nLIndex >= 0)
				{
					$arSelKFList[] = $arShotKFList[$nLIndex];
					$nCount++;
					if($nCount>=$nNumKeyFramesPerShot)
					{
						break;
					}
				}
			}
		}

		// load feature
		$nNumSelVectors = sizeof($arSelKFList);
		//print_r($arSelKFList);
		// if number of keyframes is zero
		if($nNumSelVectors <= 0)
		{
			printf("### No Keyframe is selected!\n");
			continue;
		}

		// !!! IMPORTANT CHANGE Jul 17
		global $arNEWVideoPathList;

		$szNewVideoID = $arNEWVideoPathList[$szVideoID]['id'];
			
		foreach($arSelKFList as $szKeyFrameID)
		{
			$arAllSelKFList[$szNewVideoID][$szVideoID][] = $szKeyFrameID;
		}
	}

	printf("### Gathering feature vectors ...\n");
	$nTotalFeatureVectors = 0;
	$nStopFlag = 0;
	foreach($arAllSelKFList as $szNewVideoID => $arSubVideoList)
	{
		$szNewVideoPath = $arVideoPathList[$szNewVideoID];

		// load ONCE for ALL orig video programs belonging ONE NEW video program
		$szFPFeatureInputFN = sprintf("%s/%s/%s.%s", $szRootFeatureDir, $szNewVideoPath, $szNewVideoID, $szFeatureExt);

		//$arFeatureList = loadOneDvfFeatureFile($szFPFeatureInputFN, $nKFIndex=2);
		if($szFeatureFormat == "svf")
		{
			// !!! IMPORTANT CHANGE Jul 18, 2012 --> load from CENTRAL local
			$arFeatureList = loadOneTarGZSvfFeatureFileNew($szTmpDir, $szFPFeatureInputFN, $nKFIndex=2);
		}
		else // default
		{
			// !!! IMPORTANT CHANGE Jul 18, 2012 --> load from CENTRAL local
			$arFeatureList = loadOneTarGZDvfFeatureFileNew($szTmpDir, $szFPFeatureInputFN, $nKFIndex=2);
		}

		if($arFeatureList === false)
		{
			global $szFPLogFN;
			$arLogzz = array();
			$szErrorLog = sprintf("###SERIOUS ERR!!! Empty feature list [%s]!\n", $szFPFeatureInputFN);
			$arLogzz[] = $szErrorLog;
			saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
				
			//continue;
			exit(); // to rerun next time --> Update Jul 18
		}

		foreach($arSubVideoList as $szVideoID => $arLocalKFList)
		{
			$nNumSelVectors = sizeof($arLocalKFList);
			printf("### Total current selected vectors [%s]. Loading feature file and picking [%s] feature vectors to add the pool.  ...\n", $nTotalFeatureVectors, $nNumSelVectors);
			printf("### VideoID [%s --> %s].  ...\n", $szNewVideoID, $szVideoID);
			print_r($arLocalKFList);

			//$szFPFeatureInputFN = sprintf("%s/%s/%s.%s", $szRootFeatureDir, $szVideoPath, $szVideoID, $szFeatureExt);

			// !!! IMPORTANT CHANGE Update Jul 18
			global $arNEWVideoPathList;
			$szNEWVideoPathz = $arNEWVideoPathList[$szVideoID]['path'];
			$szNEWVideoIDz  = $arNEWVideoPathList[$szVideoID]['id'];

			if($szNEWVideoIDz != $szNewVideoID || $szNEWVideoPathz != $szNewVideoPath)
			{
				global $szFPLogFN;
				$arLogzz = array();
				$szErrorLog = sprintf("###Serious error in Orig and New VideoID Mapping [Orig-%s] - [Sel-%s] - [Cur-%s]\n", $szVideoID, $szNEWVideoIDz, $szNewVideoID);
				$arLogzz[] = $szErrorLog;
				saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
				exit($szErrorLog);
			}

			//print_r(array_keys($arFeatureList)); print_r($arSelKFList); exit();

			foreach($arLocalKFList as $szKeyFrameID)
			{
				/// !!! IMPORTANT
				if(isset($arFeatureList[$szKeyFrameID]))
				{
					$arAnnFeatureOutputList[$szKeyFrameID] = convertFeatureVector2LibSVMFormat($arFeatureList[$szKeyFrameID], $nLabel);
					$nTotalFeatureVectors ++;
				}
			}

			if($nTotalFeatureVectors > $nMaxKeyFrames)
			{
				printf("### Reach the limit of number of keyframes [%s]. Break!\n", $nMaxKeyFrames);
				$nStopFlag = 1;
				break;
			}

			// flush to save memory - only store in mem max $nMaxMemSize samples
			if(sizeof($arAnnFeatureOutputList) > $nMaxMemSize)
			{
				saveDataFromMem2File($arAnnFeatureOutputList, $szFPFeatureOutputFN, "a+t");

				$arAllKeyFrameList = array_keys($arAnnFeatureOutputList);
				saveDataFromMem2File($arAllKeyFrameList, $szFPFeatureAnnOutputFN, "a+t");

				unset($arAnnFeatureOutputList);
				$arAnnFeatureOutputList = array();
			}
		}

		if($nStopFlag)
		{
			break;
		}
	}
	/// !!! IMPORTANT - using a+t
	saveDataFromMem2File($arAnnFeatureOutputList, $szFPFeatureOutputFN, "a+t");

	$arAllKeyFrameList = array_keys($arAnnFeatureOutputList);
	saveDataFromMem2File($arAllKeyFrameList, $szFPFeatureAnnOutputFN, "a+t");

	// free the memory
	unset($arAnnFeatureOutputList);
	unset($arShotKFList);
	unset($arFeatureList);

	return $nTotalFeatureVectors;
}

//--> New mapping
// + .pos.ann --> TRECVID2011_19545 #$# TRECVID2011_19545.shot19545_4 #$# TRECVID2011_19545.shot19545_4.RKF_RKF #$# Pos
//--> BEFORE: video path for TRECVID2011_19545 is stored in tv2012.devel-nist.lst
//--> CURRENT: video path for TRECVID2011_19545 is found by
//>>> mapping TRECVID2011_19545 to new video program NEWTV2012_xxxx
//>>> find the mapping in tv2012.devel-nistNew.lst

// Customization for ImageCLEF --> much simpler than TRECVID-SIN
function loadVideoPathMappingImageCLEF($szMetaDataDir, $szPatName)
{
	$szFPOutputFN = sprintf("%s/%s.map.lst", $szMetaDataDir, $szPatName);

	if(!file_exists($szFPOutputFN))
	{
		$arNEWVideoPathList = array();

		$szFPPatListFN = sprintf("%s/%s.lst", $szMetaDataDir, $szPatName);
		loadListFile($arList, $szFPPatListFN); // tv2012.devel-nistNew.lst

		$arOutput = array();

		$arVideoList = array();
		foreach($arList as $szLine)
		{
			// NEWTV12_0001 #$# NEWTV12_0001 #$# tv2012/devel-nistNew

			$arTmp = explode("#$#", $szLine);
			$szNewVideoPath = trim($arTmp[2]);
			$szNewVideoID = trim($arTmp[0]);

			$szFPKeyFrameListFN = sprintf("%s/%s/%s.prg", $szMetaDataDir, $szNewVideoPath, $szNewVideoID);
			loadListFile($arKFList, $szFPKeyFrameListFN);
			foreach($arKFList as $szLine)
			{
				$szOrigVideoID = $szNewVideoID;

				$arNEWVideoPathList[$szOrigVideoID]['id'] = $szNewVideoID;
				$arNEWVideoPathList[$szOrigVideoID]['path'] = $szNewVideoPath;
					
				if(!isset($arVideoList[$szOrigVideoID]))
				{
					$arVideoList[$szOrigVideoID] = 1;
					$arOutput[] = sprintf("%s #$# %s #$# %s", $szOrigVideoID, $szNewVideoID, $szNewVideoPath);
				}
			}

		}

		saveDataFromMem2File($arOutput, $szFPOutputFN);
		return $arNEWVideoPathList;
	}
	else
	{
		$arNEWVideoPathList = array();
		loadListFile($arList, $szFPOutputFN);
		foreach($arList as $szLine)
		{
			$arTmp = explode("#$#", $szLine);
			$szOrigVideoID = trim($arTmp[0]);
			$szNewVideoID = trim($arTmp[1]);
			$szNewVideoPath = trim($arTmp[2]);

			$arNEWVideoPathList[$szOrigVideoID]['id'] = $szNewVideoID;
			$arNEWVideoPathList[$szOrigVideoID]['path'] = $szNewVideoPath;
		}
		return $arNEWVideoPathList;
	}
}

// These functions are RE-USED in ksc-ProcessOneRun-Test-New.php
function loadOneTarGZSvfFeatureFileNew($szTmpDir, $szFPServerFeatureInputFN, $nKFIndex=2)
{
	global $gszTmpDir;
	global $gszFeatureDirFromSysID;

	$nMaxWaitingCount = 30; // --> total = 10x60 ~ 10 minutes
	$nSleepCycle = 60; // 60 seconds

	$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
	$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig

	//hlf-tv2012.nsc.bow.dense6mul.rgbsift.Soft-500-VL2.tv2012.devel-nistNew.norm3x1

	$szCentralTmpDir = sprintf("%s/%s/%s", $szLocalTmpDir, $szScriptBaseName, $gszFeatureDirFromSysID);
	makeDir($szCentralTmpDir);

	// one local dir to store ALL feature files, serve for ALL jobs
	$szLocalDirForAllFeatureFiles4AllJobs = $szCentralTmpDir;

	$szLocalName = basename($szFPServerFeatureInputFN);

	$szFPTarGzLocalFeatureInputFN = sprintf("%s/%s.tar.gz", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);

	$szFPLocalFeatureInputFN = sprintf("%s/%s", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);

	$szFPSemaphoreFN = sprintf("%s/%s.flag", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);

	global $szFPLogFN;
	if(!file_exists($szFPTarGzLocalFeatureInputFN))
	{
		// if there is no any copying job
		if(!file_exists($szFPSemaphoreFN) || !filesize($szFPSemaphoreFN))
		{
			$arLogzz = array();
			$szStartTime = date("m.d.Y - H:i:s:u");
			$szErrorLog = sprintf("### INFO - [%s]!!! Start copying - [%s]\n",
					$szStartTime, $szFPServerFeatureInputFN);
			$arLogzz[] = $szErrorLog;
			saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
				
			// lock the file
			$arLogzz = array();
			$arLogzz[] = sprintf("Locking at: %s", date("m.d.Y - H:i:s"));
			saveDataFromMem2File($arLogzz, $szFPSemaphoreFN);

			$szCmdLine = sprintf("cp %s.tar.gz %s", $szFPServerFeatureInputFN, $szLocalDirForAllFeatureFiles4AllJobs);
			execSysCmd($szCmdLine);

			// unlock
			deleteFile($szFPSemaphoreFN);
				
			$arLogzz = array();
			$szEndTime = date("m.d.Y - H:i:s:u");
			$szErrorLog = sprintf("### INFO - [%s --> %s]!!! Finish copying - [%s]\n",
					$szStartTime, $szEndTime, $szFPServerFeatureInputFN);
			$arLogzz[] = $szErrorLog;
			saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
		}

		// if there is another copying job --> waiting for the job finishes
		else
		{
			$nWaitingCount = 0;
			while($nWaitingCount<$nMaxWaitingCount)
			{
				if(!file_exists($szFPSemaphoreFN))  // check the job finishes ??
				{
					if($nWaitingCount) // if having at least one time of waiting
					{
						$arLogzz = array();
						$szEndTime = date("m.d.Y - H:i:s:u");
						$szErrorLog = sprintf("### WARNING - CLEARED [%s --> %s] !!! Waiting - Branch 1 - [%d] for copying [%s]\n",
								$szStartTime, $szEndTime, $nWaitingCount, $szFPServerFeatureInputFN);
						$arLogzz[] = $szErrorLog;
						saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
					}
					break;
				}
				else  // wait
				{
					printf(">>> Waiting - Branch 1 - %d\n", $nWaitingCount);

					$arLogzz = array();
						
					if($nWaitingCount == 0)
					{
						$szStartTime = date("m.d.Y - H:i:s:u");
					}
						
					$szErrorLog = sprintf("### WARNING [%s] !!! Waiting - Branch 1 - [%d] for copying [%s]\n",
							date("m.d.Y - H:i:s:u"), $nWaitingCount, $szFPServerFeatureInputFN);
					$arLogzz[] = $szErrorLog;
					saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
						
					sleep($nSleepCycle);
				}
				$nWaitingCount++;

				if($nWaitingCount>=$nMaxWaitingCount)  // no longer waiting --> RESET
				{
					// delete for reset
					deleteFile($szFPSemaphoreFN);
					$szEndTime = date("m.d.Y - H:i:s:u");
					$szErrorLog = sprintf("### SUPER WARNING [%s --> %s] !!! Delete flag file for RESET [%s]\n",
							$szStartTime, $szEndTime, $szFPServerFeatureInputFN);
					$arLogzz[] = $szErrorLog;
					saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
						
					sleep($nSleepCycle);
					break;
				}
			}
		}
	}
	else
	{
		// if there is another copying job --> waiting for the job finishes
		$nWaitingCount = 0;
		while($nWaitingCount<$nMaxWaitingCount)
		{
			if(!file_exists($szFPSemaphoreFN))  // 
			{
				if($nWaitingCount) // if having at least one time of waiting
				{
					$arLogzz = array();
					$szEndTime = date("m.d.Y - H:i:s:u");
					$szErrorLog = sprintf("### WARNING - CLEARED [%s --> %s] !!! Waiting - Branch 2 - [%d] for copying [%s]\n",
							$szStartTime, $szEndTime, $nWaitingCount, $szFPServerFeatureInputFN);
					$arLogzz[] = $szErrorLog;
					saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
				}
				break;
			}
			else
			{
				printf(">>> Waiting - Branch 2 - %d\n", $nWaitingCount);

				$arLogzz = array();

				if($nWaitingCount == 0)
				{
					$szStartTime = date("m.d.Y - H:i:s:u");
				}

				$szErrorLog = sprintf("### WARNING [%s] !!! Waiting - Branch 2 - [%d] for copying [%s]\n",
						date("m.d.Y - H:i:s:u"), $nWaitingCount, $szFPServerFeatureInputFN);
				$arLogzz[] = $szErrorLog;
				saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");

				sleep($nSleepCycle);
			}

			$nWaitingCount++;
			if($nWaitingCount>=$nMaxWaitingCount)
			{
				// delete for reset
				deleteFile($szFPSemaphoreFN);
				$szEndTime = date("m.d.Y - H:i:s:u");
				$szErrorLog = sprintf("### SUPER WARNING [%s --> %s] !!! Delete flag file for RESET [%s]\n",
						$szStartTime, $szEndTime, $szFPServerFeatureInputFN);
				$arLogzz[] = $szErrorLog;
				saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
					
				sleep($nSleepCycle);
				break;
			}
		}
	}

	return loadOneTarGZSvfFeatureFile($szTmpDir, $szFPLocalFeatureInputFN, $nKFIndex);
}

function loadOneTarGZDvfFeatureFileNew($szTmpDir, $szFPServerFeatureInputFN, $nKFIndex=2)
{
	global $gszTmpDir;
	global $gszFeatureDirFromSysID;

	$nMaxWaitingCount = 30; // --> total = 10x60 ~ 10 minutes
	$nSleepCycle = 60; // 60 seconds

	$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
	$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig

	//hlf-tv2012.nsc.bow.dense6mul.rgbsift.Soft-500-VL2.tv2012.devel-nistNew.norm3x1

	$szCentralTmpDir = sprintf("%s/%s/%s", $szLocalTmpDir, $szScriptBaseName, $gszFeatureDirFromSysID);
	makeDir($szCentralTmpDir);

	// one local dir to store ALL feature files, serve for ALL jobs
	$szLocalDirForAllFeatureFiles4AllJobs = $szCentralTmpDir;

	$szLocalName = basename($szFPServerFeatureInputFN);

	$szFPTarGzLocalFeatureInputFN = sprintf("%s/%s.tar.gz", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);

	$szFPLocalFeatureInputFN = sprintf("%s/%s", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);

	$szFPSemaphoreFN = sprintf("%s/%s.flag", $szLocalDirForAllFeatureFiles4AllJobs, $szLocalName);

	global $szFPLogFN;
	if(!file_exists($szFPTarGzLocalFeatureInputFN))
	{
		// if there is no any copying job
		if(!file_exists($szFPSemaphoreFN) || !filesize($szFPSemaphoreFN))
		{
			$arLogzz = array();
			$szStartTime = date("m.d.Y - H:i:s:u");
			$szErrorLog = sprintf("### INFO - [%s]!!! Start copying - [%s]\n",
					$szStartTime, $szFPServerFeatureInputFN);
			$arLogzz[] = $szErrorLog;
			saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
				
			// lock the file
			$arLogzz = array();
			$arLogzz[] = sprintf("Locking at: %s", date("m.d.Y - H:i:s"));
			saveDataFromMem2File($arLogzz, $szFPSemaphoreFN);

			$szCmdLine = sprintf("cp %s.tar.gz %s", $szFPServerFeatureInputFN, $szLocalDirForAllFeatureFiles4AllJobs);
			execSysCmd($szCmdLine);

			// unlock
			deleteFile($szFPSemaphoreFN);
				
			$arLogzz = array();
			$szEndTime = date("m.d.Y - H:i:s:u");
			$szErrorLog = sprintf("### INFO - [%s --> %s]!!! Finish copying - [%s]\n",
					$szStartTime, $szEndTime, $szFPServerFeatureInputFN);
			$arLogzz[] = $szErrorLog;
			saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
		}

		// if there is another copying job --> waiting for the job finishes
		else
		{
			$nWaitingCount = 0;
			while($nWaitingCount<$nMaxWaitingCount)
			{
				if(!file_exists($szFPSemaphoreFN))  // check the job finishes ??
				{
					if($nWaitingCount) // if having at least one time of waiting
					{
						$arLogzz = array();
						$szEndTime = date("m.d.Y - H:i:s:u");
						$szErrorLog = sprintf("### WARNING - CLEARED [%s --> %s] !!! Waiting - Branch 1 - [%d] for copying [%s]\n",
								$szStartTime, $szEndTime, $nWaitingCount, $szFPServerFeatureInputFN);
						$arLogzz[] = $szErrorLog;
						saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
					}
					break;
				}
				else  // wait
				{
					printf(">>> Waiting - Branch 1 - %d\n", $nWaitingCount);

					$arLogzz = array();
						
					if($nWaitingCount == 0)
					{
						$szStartTime = date("m.d.Y - H:i:s:u");
					}
						
					$szErrorLog = sprintf("### WARNING [%s] !!! Waiting - Branch 1 - [%d] for copying [%s]\n",
							date("m.d.Y - H:i:s:u"), $nWaitingCount, $szFPServerFeatureInputFN);
					$arLogzz[] = $szErrorLog;
					saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
						
					sleep($nSleepCycle);
				}
				$nWaitingCount++;

				if($nWaitingCount>=$nMaxWaitingCount)  // no longer waiting --> RESET
				{
					// delete for reset
					deleteFile($szFPSemaphoreFN);
					$szEndTime = date("m.d.Y - H:i:s:u");
					$szErrorLog = sprintf("### SUPER WARNING [%s --> %s] !!! Delete flag file for RESET [%s]\n",
							$szStartTime, $szEndTime, $szFPServerFeatureInputFN);
					$arLogzz[] = $szErrorLog;
					saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
						
					sleep($nSleepCycle);
					break;
				}
			}
		}
	}
	else
	{
		// if there is another copying job --> waiting for the job finishes
		$nWaitingCount = 0;
		while($nWaitingCount<$nMaxWaitingCount)
		{
			if(!file_exists($szFPSemaphoreFN))  // 
			{
				if($nWaitingCount) // if having at least one time of waiting
				{
					$arLogzz = array();
					$szEndTime = date("m.d.Y - H:i:s:u");
					$szErrorLog = sprintf("### WARNING - CLEARED [%s --> %s] !!! Waiting - Branch 2 - [%d] for copying [%s]\n",
							$szStartTime, $szEndTime, $nWaitingCount, $szFPServerFeatureInputFN);
					$arLogzz[] = $szErrorLog;
					saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
				}
				break;
			}
			else
			{
				printf(">>> Waiting - Branch 2 - %d\n", $nWaitingCount);

				$arLogzz = array();

				if($nWaitingCount == 0)
				{
					$szStartTime = date("m.d.Y - H:i:s:u");
				}

				$szErrorLog = sprintf("### WARNING [%s] !!! Waiting - Branch 2 - [%d] for copying [%s]\n",
						date("m.d.Y - H:i:s:u"), $nWaitingCount, $szFPServerFeatureInputFN);
				$arLogzz[] = $szErrorLog;
				saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");

				sleep($nSleepCycle);
			}

			$nWaitingCount++;
			if($nWaitingCount>=$nMaxWaitingCount)
			{
				// delete for reset
				deleteFile($szFPSemaphoreFN);
				$szEndTime = date("m.d.Y - H:i:s:u");
				$szErrorLog = sprintf("### SUPER WARNING [%s --> %s] !!! Delete flag file for RESET [%s]\n",
						$szStartTime, $szEndTime, $szFPServerFeatureInputFN);
				$arLogzz[] = $szErrorLog;
				saveDataFromMem2File($arLogzz, $szFPLogFN, "a+t");
					
				sleep($nSleepCycle);
				break;
			}
		}
	}
	
	return loadOneTarGZDvfFeatureFile($szTmpDir, $szFPLocalFeatureInputFN, $nKFIndex);
}

?>