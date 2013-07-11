<?php

/**
 * 		@file 	ksc-ProcessOneRun-Test-New.php
 * 		@brief 	Test One Run.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 10 Jul 2013.
 */

//*** Update Jul 18, 2012
// Customize for tvsin12 --> MAJOR changes --> search for Jul 18
// Collecting feature vectors for training (4K + 40K) might take more than ONE hour

// ************ NEW **************
//- All feature files are stored in one public dir in /local/ledduy so that ALL concepts (of one run config type, i.e. ignore dup systems .Rxx) can be used
//---> HOW to handle conflicts, because when a file is copying, check file_exists() and filesize() does not work
// --> try cp -u
//- Keyframes are organized into NewVideoID-->OrigVideoID-->KeyFrame so that one feature file for one new video program is loaded ONCE


/// !!! IMPORTANT
//--> Check model and res files before running

// Update Sep 07
/*
 // !!! IMPORTANT  -- Updated Sep 07 - Vu found this!
if($szTestDataName != $szTrainDataName)
{
$szCmdLine = sprintf("rm -rf %s/*%s*", $szTmpDir, $szTestDataName);
execSysCmd($szCmdLine);
}
*/

// Update Aug 07
// Customize for tv2011

/////////////////////////////////////////////////////////////////////////
// Update Jun 13
// Current setting --> no way to specify model dir since it is bound with sys_id
// Special treatment for validation run
// if(strstr($arRunConfig['sys_id'], "-validation"))
//{
//	$szExpModelRunDir = sprintf("%s/%s", $szExpModelDir, str_replace("-validation", "", $arRunConfig['sys_id']));
//}


// Update May 14
// if the feature file does not exist
//$szFPTarFeatureInputFNzz = sprintf("%s.tar.gz", $szFPFeatureInputFN);
//if(!file_exists($szFPTarFeatureInputFNzz))
//{
//	continue;
//}


// Update Jan 25
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


// Update Jan 02
// Adding param $nSkipExistingScores = 1;

// Update Dec 26
// Supporting feature format param in run config --> FeatureFormat for svf

// Update Dec 02
// Move parts of copying model files out of the loop

// Update 25 Nov
// Update functions to support svf format

// Update 18 Oct
// Check sys_id for consistency with file name  --> USE the core name, so sys_id is UNUSED

// Update 14 Oct
// Allow to change devel_pat and test_pat in each run config
// Use prob_output as param in run config

// Update 03 Oct
//  Move $szExpConfig = "hlf-tv2005" to nsc-ProcessOneRun-Test-TV10-SGE.php;
//	$szTmpDir = sprintf("%s/model-%d-%d", $szTmpDir, $nStartPrg, $nEndPrg);
// 	Several threads using the same model for predicting different video programs --> put szTmpDir with model-x-y for isolation

// Update 01 Oct
// Support .model file in compressed format (tar.gz) --> reduce more than 40% of size
// .model and .normdat files are copied to local dir
// Test classifiers with -b option  --> modify SVMTools by adding one more param
// Special treatment with output of -b option --> one more row added: labels 1 -1

// Update Aug 9
// check .res & .out files before prediction --> jobs can be run continuously when models are coming incrementally

// support compressed feature file

/////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";
require_once "ksc-Tool-TRECVID.php";


///////////////////////// THIS PART IS FOR CUSTOMIZATION ///////////////////////

//$szRootDir = "/net/sfv215/export/raid4/ledduy/trecvid-sin-2011";
$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig

$gnUseProbOutput = 1; // to normalize scores for fusion later

$gszFeatureFormat = "dvf";
$szFeatureFormat = $gszFeatureFormat;

// Update Jan 02, 2011
$nSkipExistingScores = 1; // *** CHANGED ***

$szFPLogFN = "ksc-ProcessOneRun-Test-New.log";
//////////////////// END FOR CUSTOMIZATION ////////////////////

////////////////////////////////// START ////////////////////////

$szExpConfig = "hlf-tv2013";
$szFPRunConfigFN = "/net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/experiments/hlf-tv2013/runlist/tmp/basic/hlf-tv2013.nsc.bow.harlap6mul.rgbsift.Soft-500.devel-nistNew.norm3x1.ksc.tvsin13.R1.cfg";
$nStartConcept = 1;
$nEndConcept = 2;
$nStartPrg = 1;
$nEndPrg = 2;

if($argc!=7)
{
	printf("Number of params [%s] is incorrect [7]\n", $argc);
	printf("Usage %s <ExpConfig> <RunConfigFN> <StartConcept> <EndConcept> <StartPrg> <EndPrg>\n", $argv[0]);
	printf("Usage %s %s %s %s %s %s %s\n", $argv[0], $szExpConfig, $szFPRunConfigFN, $nStartConcept, $nEndConcept, $nStartPrg, $nEndPrg);
	exit();
}

$arLog = array();
$szStartTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Start [%s --> $$$]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]",
		$szStartTime,
		$argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

$szExpName = $argv[1];
$szFPRunConfigFN = $argv[2];
// $szFPRunConfigFN = sprintf("%s/trecvid/experiments/hlf-tv2007/hlf-tv2007.run001.dkf-5.tkf-5.cfg", $szRootDir);
$szSysID = basename($szFPRunConfigFN, ".cfg");

// Update Jul 18
$arTmpzz = explode(".ksc", $szSysID);
$gszFeatureDirFromSysID = trim($arTmpzz[0]);


$nStartConcept = intval($argv[3]);
$nEndConcept = intval($argv[4]);

$nStartPrg = intval($argv[5]);
$nEndPrg = intval($argv[6]);

//$szRootDir = "U:";
$szRootExpDir = sprintf("%s", $gszRootBenchmarkExpDir); // New Jul 18

$szFPConfigFN = sprintf("%s/experiments/%s/%s.cfg", $szRootExpDir, $szExpName, $szExpName);

$arConfig = loadExperimentConfig($szFPConfigFN);
$arRunConfig = loadExperimentConfig($szFPRunConfigFN);

$szFeatureExt =  $arRunConfig['feature_ext']; //"nsc.cCV_HSV.g5.q3.g_cm";

$szRootExpDir = sprintf("%s/%s", $arConfig['exp_dir'], $arConfig['exp_name']);
$szExpAnnDir = sprintf("%s/%s", $szRootExpDir, $arConfig['ann_dir']); // annotation
$szExpMetaDataDir = sprintf("%s/%s", $szRootExpDir, $arConfig['metadata_dir']); // annotation
$szExpModelDir = sprintf("%s/%s", $szRootExpDir, $arConfig['model_dir']); // annotation
$szExpResultDir = sprintf("%s/%s", $szRootExpDir, $arConfig['result_dir']); // annotation


// !!! IMPORTANT CHANGE Update Jul 18
//$szRootMetaDataKFDir = $arConfig['root_metadata_kf_dir'];
//$szRootFeatureDir = $arConfig['root_feature_dir']; //

$szRootMetaDataKFDir = getRootBenchmarkMetaDataDir($szFeatureExt);
$szRootFeatureDir = getRootBenchmarkFeatureDir($szFeatureExt);

$szTestVideoList = $arConfig['test_pat'];
// Update Oct 14
if(isset($arRunConfig['test_pat']))
{
	$szTestVideoList = $arRunConfig['test_pat'];
}

$szFPTestVideoListFN = sprintf("%s/%s", $szExpMetaDataDir, $szTestVideoList); // trecvid.video.tv2007.devel.lst

$szFPConceptListFN = sprintf("%s/%s.lst", $szExpAnnDir, $arConfig['concept_list']);

// Oct 18
if($szSysID != $arRunConfig['sys_id'])
{
	$arRunConfig['sys_id'] = $szSysID;
}

$szExpModelRunDir = sprintf("%s/%s", $szExpModelDir, $arRunConfig['sys_id']);
/*
 if(strstr($arRunConfig['sys_id'], "-validation"))
 {
$szExpModelRunDir = sprintf("%s/%s", $szExpModelDir, str_replace("-validation", "", $arRunConfig['sys_id']));
}
*/

$szExpResultRunDir = sprintf("%s/%s", $szExpResultDir, $arRunConfig['sys_id']);
makeDir($szExpResultRunDir);

// new
$gnUseProbOutput = $arRunConfig['svm_train_use_prob_output'];

// new param for feature format - Dec 26
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

$nNumConcepts = loadListFile($arConceptList, $szFPConceptListFN);

if($nEndConcept > $nNumConcepts)
{
	$nEndConcept = $nNumConcepts;
}

for($i=$nStartConcept; $i<$nEndConcept; $i++)
{
	$szLine = $arConceptList[$i];
	// new format
	// 9003.Airplane #$# Airplane #$# 029 #$# Airplane #$# 218 #$# 003
	$arTmp = explode("#$#", $szLine);
	$szConceptName = trim($arTmp[0]);
	$nConceptID = intval($arTmp[5]) + 9000;

	printf("###%d. Processing concept [%s] ...\n", $i, $szConceptName);
	$szExpModelRunConceptDir = sprintf("%s/%s", $szExpModelRunDir, $szConceptName);
	$szExpScoreRunConceptDir =   sprintf("%s/%s", $szExpResultRunDir, $szConceptName);
	makeDir($szExpScoreRunConceptDir);

	/*
	 // /local/ledduy/results/hlf-tv2010.run000001/9004.Airplane_Flying
	if(file_exists("/local/ledduy"))
	{
	$szTmpDir = sprintf("/local/ledduy/%s/%s/%s", $arConfig['result_dir'], $arRunConfig['sys_id'], $szConceptName);
	makeDir($szTmpDir);

	}
	else
	{
	$szTmpDir = sprintf("/net/per900b/raid0/ledduy/tmp/tmp/%s/%s/%s", $arConfig['result_dir'], $arRunConfig['sys_id'], $szConceptName);
	makeDir($szTmpDir);

	}
	*/

	// Update Jul 18, 2012
	$szScriptBaseName = basename($_SERVER['SCRIPT_NAME'], ".php");
	$szLocalTmpDir = $gszTmpDir;  // defined in ksc-AppConfig
	$szTmpDir = sprintf("%s/%s/%s/%s/%s", $szLocalTmpDir, $szScriptBaseName, $arConfig['result_dir'], $arRunConfig['sys_id'], $szConceptName);
	makeDir($szTmpDir);

	$nNumVideos = loadListFile($arVideoList, $szFPTestVideoListFN);

	$szTrainDataName = sprintf("%s.%s.svm", $szConceptName, $szFeatureExt);
	$nCount = 1;
	//foreach($arVideoList as $szLine)
	if($nEndPrg > $nNumVideos)
	{
		$nEndPrg = $nNumVideos;
	}

	// !!! IMPORTANT  --> move here (Sep 07) since it depends on $nEndPrg
	// tmp model dir used for predicting video programs from nStartPrg to nEndPrg
	$szTmpDir = sprintf("%s/model-%d-%d", $szTmpDir, $nStartPrg, $nEndPrg);
	makeDir($szTmpDir);

	$szTmpModelDir = $szTmpDir;

	// Modify Dec 02  --> Move the part of copying model file to tmp dir out of the loop
	$szTestDataDir = $szTmpDir; // store in tmp format

	$szModelDir = $szExpModelRunConceptDir;

	// copy .model to $szTmpModelDir
	$szFPModelFN = sprintf("%s/%s.model", $szModelDir, $szTrainDataName);

	$szFPModelTarFN = sprintf("%s/%s.model.tar.gz", $szModelDir, $szTrainDataName);
	if(!file_exists($szFPModelTarFN))
	{
		printf("Model file [%s] does not exist!\n", $szFPModelTarFN);
		continue;
	}

	$szCmdLine = sprintf("cp %s.tar.gz %s", $szFPModelFN, $szTmpModelDir);
	execSysCmd($szCmdLine);

	// data for normalization
	$szFPModelNormDataFN = sprintf("%s/%s.normdat", $szModelDir, $szTrainDataName);
	$szCmdLine = sprintf("cp %s %s", $szFPModelNormDataFN, $szTmpModelDir);
	execSysCmd($szCmdLine);

	// extract, i.e. decompress
	$szFPLocalModelFN = sprintf("%s/%s.model", $szTmpModelDir, $szTrainDataName);
	$szCmdLine = sprintf("tar -xvf %s.tar.gz -C %s", $szFPLocalModelFN, $szTmpModelDir);
	execSysCmd($szCmdLine);

	$szScoreDir = $szExpScoreRunConceptDir;

	$szFeatureDir = sprintf("%s/%s", $szRootFeatureDir, $szFeatureExt);

	// End Modify Dec 02

	for($kk=$nStartPrg; $kk<$nEndPrg; $kk++)
	{
		$szLine = $arVideoList[$kk];

		$arTmp = explode("#$#", $szLine);
		$szVideoID = trim($arTmp[0]);
		$szVideoPath = trim($arTmp[2]);

		printf("###%d. Processing video [%s] ...\n", $nCount, $szVideoID);
		$nCount++;

		$szTestDataName = sprintf("%s.%s.svm", $szVideoID, $szFeatureExt);

		//		$szFeatureDir = sprintf("%s/baseline.%s", $szRootFeatureDir, $szFeatureExt);

		$szFPFeatureInputFN = sprintf("%s/%s/%s.%s",
				$szFeatureDir, $szVideoPath,
				$szVideoID, $szFeatureExt);

		$szFPFeatureOutputFN = sprintf("%s/%s",
				$szTestDataDir, $szTestDataName);

		/// !!! IMPORTANT
		$szFPScoreAnnFN = sprintf("%s/%s.res", $szScoreDir, $szTestDataName);

		if($nSkipExistingScores)
		{
			// file size != 0
			if(file_exists($szFPScoreAnnFN) && filesize($szFPScoreAnnFN))
			{
				printf("File [%s] already existed!\n", $szFPScoreAnnFN);
				continue;
			}
		}
		// for convenience in processing score and ann association in prediction stage
		$szFPAnnOutputFN = sprintf("%s.ann", $szFPFeatureOutputFN);

		// if the feature file does not exist
		$szFPTarFeatureInputFNzz = sprintf("%s.tar.gz", $szFPFeatureInputFN);
		if(!file_exists($szFPTarFeatureInputFNzz))
		{
			continue;
		}
		if($szFeatureFormat == "svf")
		{
			convertSvfData2LibSVMFormat($szTmpDir,
					$szFPFeatureInputFN,
					$szFPFeatureOutputFN, $szFPAnnOutputFN);
		}
		else // default
		{
			convertDvfData2LibSVMFormat($szTmpDir,
					$szFPFeatureInputFN,
					$szFPFeatureOutputFN, $szFPAnnOutputFN);
		}
		//doPredictionAssociation($szTrainDataName, $szTestDataName,
		//$szTestDataDir, $szModelDir, $szScoreDir);
			
		// .model is copied to local tmp dir
		doPredictionAssociation($szTrainDataName, $szTestDataName,
				$szTestDataDir, $szTmpModelDir, $szScoreDir, $gnUseProbOutput);

		// !!! IMPORTANT  -- Updated Sep 07 - Vu found this!
		if($szTestDataName != $szTrainDataName)
		{
			$szCmdLine = sprintf("rm -rf %s/*%s*", $szTmpDir, $szTestDataName);
			execSysCmd($szCmdLine);
		}
	}
	$szCmdLine = sprintf("rm -rf %s/*%s*", $szTmpDir, $szTrainDataName);
	execSysCmd($szCmdLine);
}

$arLog = array();
$szFinishTime = date("m.d.Y - H:i:s");
$arLog[] = sprintf("###Finish [%s --> %s]: [%s]-[%s]-[%s]-[%s]-[%s]-[%s]",
		$szStartTime, $szFinishTime,
		$argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
saveDataFromMem2File($arLog, $szFPLogFN, "a+t");

/////////////////////////////// FUNCTIONS ////////////////////////////////////

function convertDvfData2LibSVMFormat($szTmpDir,
		$szFPFeatureInputFN,
		$szFPFeatureOutputFN, $szFPAnnOutputFN)
{
	$nLabel = 1;

	//$arFeatureList = loadOneDvfFeatureFile($szFPFeatureInputFN, $nKFIndex=2);

	//$arFeatureList = loadOneTarGZDvfFeatureFile($szTmpDir, $szFPFeatureInputFN, $nKFIndex=2);


	// Modified Jul 18, 2012
	$arFeatureList = loadOneTarGZDvfFeatureFileNew($szTmpDir, $szFPFeatureInputFN, $nKFIndex=2);

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

	$nNumKeyFrames = sizeof($arFeatureList);
	$arKeyFrameList = array_keys($arFeatureList);
	for($k=0; $k<$nNumKeyFrames; $k++)
	{
		$szKeyFrameID = $arKeyFrameList[$k];

		$arAnnFeatureList[$szKeyFrameID] = convertFeatureVector2LibSVMFormat($arFeatureList[$szKeyFrameID], $nLabel);
	}

	saveDataFromMem2File($arAnnFeatureList, $szFPFeatureOutputFN, "wt");

	$arAllKeyFrameList = array_keys($arAnnFeatureList);
	saveDataFromMem2File($arAllKeyFrameList, $szFPAnnOutputFN, "wt");
}

function convertSvfData2LibSVMFormat($szTmpDir,
		$szFPFeatureInputFN,
		$szFPFeatureOutputFN, $szFPAnnOutputFN)
{
	$nLabel = 1;

	//$arFeatureList = loadOneSvfFeatureFile($szFPFeatureInputFN, $nKFIndex=2);

	//$arFeatureList = loadOneTarGZSvfFeatureFile($szTmpDir, $szFPFeatureInputFN, $nKFIndex=2);

	// Modified Jul 18, 2012
	$arFeatureList = loadOneTarGZSvfFeatureFileNew($szTmpDir, $szFPFeatureInputFN, $nKFIndex=2);

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

	$nNumKeyFrames = sizeof($arFeatureList);
	$arKeyFrameList = array_keys($arFeatureList);
	for($k=0; $k<$nNumKeyFrames; $k++)
	{
		$szKeyFrameID = $arKeyFrameList[$k];

		$arAnnFeatureList[$szKeyFrameID] = convertFeatureVector2LibSVMFormat($arFeatureList[$szKeyFrameID], $nLabel);
	}

	saveDataFromMem2File($arAnnFeatureList, $szFPFeatureOutputFN, "wt");

	$arAllKeyFrameList = array_keys($arAnnFeatureList);
	saveDataFromMem2File($arAllKeyFrameList, $szFPAnnOutputFN, "wt");
}

function doPredictionAssociation($szTrainDataName, $szTestDataName,
		$szTestDataDir, $szModelDir, $szScoreDir, $nUseProbOutput=0)
{
	$szFPScoreOutputFN = runPredictClassifier(
			$szTrainDataName, $szTestDataName, $szTestDataDir, $szModelDir, $szScoreDir, $nUseProbOutput);

	// associate ann and score
	$nNumSamples1 = loadListFile($arScoreList, $szFPScoreOutputFN);

	$szFPAnnFN = sprintf("%s/%s.ann", $szTestDataDir, $szTestDataName);
	$nNumSamples = loadListFile($arAnnList, $szFPAnnFN);

	$arOutputScoreList = array();

	// !!! IMPORTANT - special treatment for different -b option
	if($nUseProbOutput==0)
	{
		if($nNumSamples1 != $nNumSamples)
		{
			printf("Error in result file!\n");
			exit();
		}

		for($i=0; $i<$nNumSamples; $i++)
		{
			$szKey = $arAnnList[$i];
			$fScore = $arScoreList[$i];
			$arOutputScoreList[$szKey] = $fScore; // for fusion
		}
	}
	else
	{
		// one row different
		if($nNumSamples1 != $nNumSamples+1)
		{
			printf("Error in result file!\n");
			exit();
		}

		// labels 1 -1
		$szAnnRowz = $arScoreList[0];
		$arTmpzz = explode(" ", $szAnnRowz);
		$nPosIndex = 1; // default
		for($i=1; $i<sizeof($arTmpzz); $i++)
		{
			if(intval($arTmpzz) == 1)
			{
				$nPosIndex = $i; // index for positive label
				break;
			}
		}

		for($i=0; $i<$nNumSamples; $i++)
		{
			$szKey = $arAnnList[$i];

			$szScorez = $arScoreList[$i+1]; // due to first row --> labels 1 -1
			$arTmpzz = explode(" ", $szScorez);
			$fScore = floatval($arTmpzz[$nPosIndex]);

			$arOutputScoreList[$szKey] = $fScore; // for fusion
		}
	}

	$szFPScoreAnnFN = sprintf("%s/%s.res", $szScoreDir, $szTestDataName);
	$arOutput = array();
	foreach($arOutputScoreList as $szKey => $fScore)
	{
		$arOutput[] = sprintf("%s #$# %s", $szKey, $fScore);
	}
	saveDataFromMem2File($arOutput, $szFPScoreAnnFN);
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