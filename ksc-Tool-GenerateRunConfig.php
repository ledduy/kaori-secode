<?php

/**
 * 		@file 	ksc-Tool-GenerateRunConfig.php
 * 		@brief 	Generate a run config - CMD app.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 06 Jul 2013.
 */


//*** Update Jul 18, 2012
//--> Seperate RootDirs for Metadata, Feature, Experiments
//"max_kf_devel_pos_set #$# 4000", // *** CHANGED ***
//"max_kf_devel_neg_set #$# 40000", // *** CHANGED ***

//--> BEFORE: the script scans ONE feature dir --> CURRENT: scan SEVERAL feature DIRS

// Update Jun 27, 2012
//--> Customize for imageclef2012
// Adding part to generate general config

//--> new set of training params to reduce the time of searching best params
/*
 "svm_grid_start_C #$# 0",
"svm_grid_end_C #$# 2",
"svm_grid_step_C #$# 2",
"svm_grid_start_G #$# -8",
"svm_grid_end_G #$# -4",
"svm_grid_step_G #$# 2",
*/

//--> scan feature/keyframe-5 to pick feature config for each run
// --> only run when all features are computed ***
//$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
//$arFeatureList = collectDirsInOneDir($szRootFeatureDir);

/////////////////////////////////////////////////////////////////////////
// Update Jun 13
// Adding for validation pats
// Filter for local features:
// + /net/sfv215/export/raid6/trecvid/experiments/hlf-tv2009/runlist/tmp/*-validation*VL2.tv2007.devel-nist*norm*R1*
// + /net/sfv215/export/raid6/trecvid/experiments/hlf-tv2008/runlist/tmp/basic/*-validation*g5*R1*

// Update Feb 03
// Output dir is runlist/tmp to avoid overring existing ones

// Update Dec 27
// Modify the devel test for tv2008 and tv2009 --> tv2008.devel-nist
// Generate hlt-tv20xx.fusion.template.cfg for fusion configs used in web app

// Update Dec 25
// runlist --> all run config files
// runlist/basic --> run config files for each feature
// runlist/fusion --> run config files for fusion

// Update Dec 08
// Copied from nsc-web-GenerateRunConfig-TV10.php

///////////////////////////////////////////////////////////////////////////////

require_once "ksc-AppConfig.php";

$szRootDir = $gszRootBenchmarkDir;

$szRootExpDir = sprintf("%s/experiments", $gszRootBenchmarkExpDir); // New Jul 18

$nUseProbOutput = 1;  // *** DO NOT CHANGE ***

// This part is default for TOMCCAP version
$arDefaultConfig = array(
		"max_kf_test #$# 5",
		"kernel #$# 5 #$# Chi-Square RBF",
		"use_keyframe_annotation #$# 1",
		"svm_train_pos_weight #$# -1 #$# Auto",
		"svm_train_neg_weight #$# -1 #$# Auto",
		"svm_grid_start_C #$# 0",  // *** CHANGED ***
		"svm_grid_end_C #$# 2", // *** CHANGED ***
		"svm_grid_step_C #$# 2", // *** CHANGED ***
		"svm_grid_start_G #$# -8", // *** CHANGED ***
		"svm_grid_end_G #$# -4", // *** CHANGED ***
		"svm_grid_step_G #$# 2", // *** CHANGED ***
		"max_kf_devel_pos_set #$# 4000", // *** CHANGED ***
		"max_kf_devel_neg_set #$# 40000", // *** CHANGED ***
		"max_kf_devel_sub_size #$# 3000",
		"max_kf_shot_devel_neg #$# 1",
		"max_kf_shot_devel_pos #$# 5",
		"neg_sampling_rate #$# 1.0",
		"pos_sampling_rate #$# 1.0",
		"svm_train_mem_size #$# 1500",
);

$arAnnSourceList = array(
		"hlf-tv2013" => "lig.iacc1.tv2012",  // ExpName ==> AnnName
);

// for dev pat
$arNISTDevPatList = array(
		"hlf-tv2013" => "devel-nistNew.lst",
);

// for test pat
$arKSCTestPatList = array(
		"hlf-tv2013" => "test.iacc.2.ANew.lst",
);

$szSysID = "tvsin13"; // *** CHANGED ***
$szSysDesc = "Experiments for TRECVID-SIN-2013"; // *** CHANGED ***

$nNumDupSys = 1; // *** CHANGED ***

/////////////////////////////// START ///////////////////////////

$szExpName = "hlf-tv2013";
$szFeatureType = "norm"; // local feature
//$szFeatureType = "CV"; // global feature
if($argc != 3)
{
	printf("Usage: %s <ExpName> <FeatureType>\n", $argv[0]);
	printf("Usage: %s  %s norm/CV\n", $argv[0], $szExpName);
	exit();
}

$szExpName = $argv[1];
//$szFeatureType = $argv[2];
//$nValidationRun = 0;
$szFeatureType = "norm"; // local feature


$szAnnSource = $arAnnSourceList[$szExpName];
$szDevPat = $arNISTDevPatList[$szExpName];
$szTestPat = $arKSCTestPatList[$szExpName];

if($nValidationRun)
{
	// re-write devel and test pat
	$szDevPat = $arNISTDevValTrainPatList[$szExpName];
	$szTestPat = $arNISTDevValTestPatList[$szExpName];
}


if($nUseProbOutput)
{
	$nUseNormScore = 0;
}
else
{
	$nUseNormScore = 1;
}

/*
 sys_id #$# trial-1-s
sys_desc #$# Test svf format and new combination
feature_ext #$# nsc.cCV_GRAY.g6.q59.g_lbp
kernel #$# 5 #$# Chi-Square RBF
num_dup_sys #$# 3
max_kf_test #$# 5
devel_pat #$# tv2010.devel-nist.lst
test_pat #$# tv2010.test.lst
sub_ann #$# lig.iacc1.tv2010
*/

// This part is for specific TRECVID year


$arCurrentConfig[] = sprintf("sys_id #$# %s", $szSysID);
$arCurrentConfig[] = sprintf("sys_desc #$# %s", $szSysDesc);
$arCurrentConfig[] = sprintf("num_dup_sys #$# %s", $nNumDupSys);
$arCurrentConfig[] = sprintf("devel_pat #$# %s", $szDevPat);
$arCurrentConfig[] = sprintf("test_pat #$# %s", $szTestPat);
$arCurrentConfig[] = sprintf("sub_ann #$# %s", $szAnnSource);
$arCurrentConfig[] = sprintf("svm_train_use_prob_output #$# %d", $nUseProbOutput);

$szRootMetaDataDir = sprintf("%s/metadata/keyframe-5", $szRootDir);

//--> BEFORE: the script scans ONE feature dir --> CURRENT: scan SEVERAL feature DIRS

$arRootBenchmarkFeatureDirList = array(
		"/net/sfv215/export/raid4/ledduy/trecvid-sin-2012",
		"/net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012"
);

foreach($arRootBenchmarkFeatureDirList as $szRootBenchmarkFeatureDir)
{
	$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootBenchmarkFeatureDir);
	$arFeatureList = collectDirsInOneDir($szRootFeatureDir);
	sort($arFeatureList);

	// update Dec 25
	$szRunListOutputDir = sprintf("%s/%s/runlist/tmp", $szRootExpDir, $szExpName);
	makeDir($szRunListOutputDir);
	$szRunListBasicOutputDir = sprintf("%s/basic", $szRunListOutputDir);
	makeDir($szRunListBasicOutputDir);
	$szRunListFusionOutputDir = sprintf("%s/fusion", $szRunListOutputDir);
	makeDir($szRunListFusionOutputDir);

	// template for fusion config - used in web app
	$arCustomConfig = array_merge($arCurrentConfig, $arDefaultConfig);
	$szFPFusionTemplateFN = sprintf("%s/%s/%s.fusion.template.cfg", $szRootExpDir, $szExpName, $szExpName);
	saveDataFromMem2File($arCustomConfig, $szFPFusionTemplateFN);

	foreach($arFeatureList as $szFeatureExt)
	{
		if($szFeatureType == "norm") // local feature
		{
			// not the real feature ext
			if(strstr($szFeatureExt, "raw") || strstr($szFeatureExt, "codebook") )
			{
				printf("### Skipping [%s]\n", $szFeatureExt);
				continue;
			}
		}

		if(!strstr($szFeatureExt, $szFeatureType))
		{
			printf("### Skipping [%s]\n", $szFeatureExt);
			continue;
		}

		$arCustomConfig = array_merge($arCurrentConfig, $arDefaultConfig);

		// do not change $arCurrentConfig, $arDefaultConfig
		$arCustomConfig[] = sprintf("feature_ext #$# %s", $szFeatureExt);

		$arFusion = $arCustomConfig; // adding fusion info
		for($i=1; $i<=$nNumDupSys; $i++)
		{

			$szOutputName = sprintf("%s.%s.ksc.%s.R%d", $szExpName, $szFeatureExt, $szSysID, $i);

			// if validatationRun --> change the name
			if($nValidationRun)
			{
				$szOutputName = sprintf("%s-validation.%s.ksc.%s.R%d", $szExpName, $szFeatureExt, $szSysID, $i);
			}

			$szFusedRunID = sprintf("run_id_R%d", $i);
			$arFusion[] = sprintf("%s #$# %s", $szFusedRunID, $szOutputName);
			$arFusion[] = sprintf("%s_feature #$# %s", $szFusedRunID, $szFeatureExt);
			$arFusion[] = sprintf("%s_weight #$# 1", $szFusedRunID);
			$arFusion[] = sprintf("%s_normscore #$# %d", $szFusedRunID, $nUseNormScore);

			$szFPDestOutputFN = sprintf("%s/%s.cfg", $szRunListOutputDir, $szOutputName);
			saveDataFromMem2File($arCustomConfig, $szFPDestOutputFN);

			$szFPDestOutputFN = sprintf("%s/%s.cfg", $szRunListBasicOutputDir, $szOutputName);
			saveDataFromMem2File($arCustomConfig, $szFPDestOutputFN);
		}

		$szOutputName = sprintf("%s.%s.ksc.%s.fusion", $szExpName, $szFeatureExt, $szSysID);
		// if validatationRun --> change the name
		if($nValidationRun)
		{
			$szOutputName = sprintf("%s-validation.%s.ksc.%s.fusion", $szExpName, $szFeatureExt, $szSysID);
		}

		$szFPDestOutputFN = sprintf("%s/%s.cfg", $szRunListOutputDir, $szOutputName);
		saveDataFromMem2File($arFusion, $szFPDestOutputFN);

		$szFPDestOutputFN = sprintf("%s/%s.cfg", $szRunListFusionOutputDir, $szOutputName);
		saveDataFromMem2File($arFusion, $szFPDestOutputFN);
	}
}
// generate for general config

$arOutput = array();
$arOutput[] = sprintf("exp_name #$# %s #$# runs for %s", $szExpName, $szExpName); // exp_name #$# ilsvrc2011 #$# runs for ilsvrc2011
$arOutput[] = sprintf("exp_dir #$# %s #$# full path = exp_dir/exp_name", $szRootExpDir); //exp_dir #$# /net/sfv215/export/raid4/ledduy/imagenet-ilsvrc-2011/experiments #$# full path = exp_dir/exp_name
$arOutput[] = sprintf("run_list #$# %s.run #$# list of runs", $szExpName); // run_list #$# ilsvrc2011.run #$# list of runs
$arOutput[] = sprintf("concept_list #$# %s.Concepts #$# ~/annotation, concept list used for this experiment", $szExpName); //concept_list #$# ilsvrc2011.Concepts #$# ~/annotation, concept list used for this experiment
$arOutput[] = sprintf("devel_pat #$# %s #$#", $szDevPat); //devel_pat #$# ilsvrc2011.devel.lst #$#
$arOutput[] = sprintf("test_pat #$# %s #$#", $szTestPat); // test_pat #$# ilsvrc2011.test.lst #$#
$arOutput[] = sprintf("eval_qrel #$# %s.feature.qrels.txt #$# groundtruth file for evaluation", $szExpName); // eval_qrel #$# ilsvrc2011.feature.qrels.txt #$# groundtruth file for evaluation

$arOutput[] = sprintf("root_metadata_kf_dir #$# %s #$# common metadata of the archive, used for retrieving lists of keyframes of video programs", $szRootMetaDataDir); // root_metadata_kf_dir #$# /net/sfv215/export/raid4/ledduy/imagenet-ilsvrc-2011/metadata/keyframe-5 #$# common metadata of the archive, used for retrieving lists of keyframes of video programs
$arOutput[] = sprintf("root_feature_dir #$# %s #$# common feature dir, used for retrieving feature vectors of keyframes", $szRootFeatureDir); // root_feature_dir #$# /net/sfv215/export/raid4/ledduy/imagenet-ilsvrc-2011/feature/keyframe-5 #$# common feature dir, used for retrieving feature vectors of keyframes

// No param, fixed names
$arOutput[] = sprintf("ann_dir #$# annotation #$# annotation data"); // ann_dir #$# annotation #$# annotation data copied from trecvid/metadata/annotation/concept/ksc
$arOutput[] = sprintf("metadata_dir #$# metadata #$# metadata data, e.g. video list for devel and test partitions"); // metadata_dir #$# metadata #$# metadata data, e.g. video list for devel and test partitions, copied from trecvid/metadata/keyframe-5
$arOutput[] = sprintf("model_dir #$# models #$# models for concept detectors, generated by training process"); // model_dir #$# models #$# models for concept detectors, generated by training process
$arOutput[] = sprintf("result_dir #$# results #$# prediction results for test dataset, generated by testing process"); // result_dir #$# results #$# prediction results for test dataset, generated by testing process

$szFPOutputFN = sprintf("%s/%s/%s.cfg", $szRootExpDir, $szExpName, $szExpName);
saveDataFromMem2File($arOutput, $szFPOutputFN);
/*
 sys_id #$# trial-1-s
sys_desc #$# Test svf format and new combination
feature_ext #$# nsc.cCV_GRAY.g6.q59.g_lbp
kernel #$# 5 #$# Chi-Square RBF
num_dup_sys #$# 3
max_kf_test #$# 5
devel_pat #$# tv2010.devel-nist.lst
test_pat #$# tv2010.test.lst
sub_ann #$# lig.iacc1.tv2010
use_keyframe_annotation #$# 1
svm_train_use_prob_output #$# 1
svm_train_pos_weight #$# -1 #$# Auto
svm_train_neg_weight #$# -1 #$# Auto
svm_grid_start_C #$# 0
svm_grid_end_C #$# 4
svm_grid_step_C #$# 2
svm_grid_start_G #$# -10
svm_grid_end_G #$# 0
svm_grid_step_G #$# 2
max_kf_devel_pos_set #$# 10000
max_kf_devel_neg_set #$# 20000
max_kf_devel_sub_size #$# 3000
max_kf_shot_devel_neg #$# 1
max_kf_shot_devel_pos #$# 5
neg_sampling_rate #$# 1.0
pos_sampling_rate #$# 1.0
svm_train_mem_size #$# 2000
*/

// general config for hlf-tv20ZZ.cfg
/*
 exp_name #$# hlf-tv2009 #$# runs using tv2009 dataset
exp_dir #$# /net/sfv215/export/raid6/trecvid/experiments #$# full path = exp_dir/exp_name
run_list #$# hlf-tv2009.run #$# list of runs
concept_list #$# hlf-tv2009.Concepts #$# ~/annotation, concept list used for this experiment
devel_pat #$# tv2009.devel-nist.lst #$# ~metadata/tv2009.devel-nist.lst, use NIST keyframes and annotations. However this might be overriden in run config (NEW!)
test_pat #$# tv2009.test.lst #$# metadata/trecvid.video.tv2009.test.lst, use own test keyframes. However this might be overriden in run config (NEW!)
eval_qrel #$# tv2009.feature.qrels.txt #$# groundtruth file for evaluation, located in metadata dir ann_dir #$# annotation #$# annotation data copied from trecvid/metadata/annotation/concept/ksc
ann_dir #$# annotation #$# annotation data copied from trecvid/metadata/annotation/concept/ksc
metadata_dir #$# metadata #$# metadata data, e.g. video list for devel and test partitions, copied from trecvid/metadata/keyframe-5
model_dir #$# models #$# models for concept detectors, generated by training process
result_dir #$# results #$# prediction results for test dataset, generated by testing process
root_metadata_kf_dir #$# /net/sfv215/export/raid6/trecvid/metadata/keyframe-5 #$# common metadata of the archive, used for retrieving lists of keyframes of video programs
root_feature_dir #$# /net/sfv215/export/raid6/trecvid/feature/keyframe-5 #$# common feature dir, used for retrieving feature vectors of keyframes
*/
?>