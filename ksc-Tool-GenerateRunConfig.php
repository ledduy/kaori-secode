<?php

/**
 * 		@file 	ksc-Tool-GenerateRunConfig.php
 * 		@brief 	Generate a run config - CMD app.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2014 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 04 Sep 2013.
 */

// *** Update Jul 18, 2012
// --> Seperate RootDirs for Metadata, Feature, Experiments
// "max_kf_devel_pos_set #$# 4000", // *** CHANGED ***
// "max_kf_devel_neg_set #$# 40000", // *** CHANGED ***

// --> BEFORE: the script scans ONE feature dir --> CURRENT: scan SEVERAL feature DIRS

// Update Jun 27, 2012
// --> Customize for imageclef2012
// Adding part to generate general config

// --> new set of training params to reduce the time of searching best params
/*
 * "svm_grid_start_C #$# 0", "svm_grid_end_C #$# 2", "svm_grid_step_C #$# 2", "svm_grid_start_G #$# -8", "svm_grid_end_G #$# -4", "svm_grid_step_G #$# 2",
 */

// --> scan feature/keyframe-5 to pick feature config for each run
// --> only run when all features are computed ***
// $szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
// $arFeatureList = collectDirsInOneDir($szRootFeatureDir);

// ///////////////////////////////////////////////////////////////////////
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

// /////////////////////////////////////////////////////////////////////////////
require_once "ksc-AppConfig.php";

$szRootDir = $gszRootBenchmarkDir;

$nUseProbOutput = 1; // *** DO NOT CHANGE ***
                     
// This part is default for TOMCCAP version
$arDefaultConfig = array(

	"max_kf_test #$# 5",
    "kernel #$# 5 #$# Chi-Square RBF",
    "use_keyframe_annotation #$# 1",
    "svm_train_pos_weight #$# -1 #$# Auto",
    "svm_train_neg_weight #$# -1 #$# Auto",
    "svm_grid_start_C #$# 0", // *** CHANGED ***
    "svm_grid_end_C #$# 4", // *** CHANGED ***
    "svm_grid_step_C #$# 2", // *** CHANGED ***
    "svm_grid_start_G #$# -10", // *** CHANGED ***
    "svm_grid_end_G #$# 2", // *** CHANGED ***
    "svm_grid_step_G #$# 2", // *** CHANGED ***
    "max_kf_devel_pos_set #$# 20000", // *** CHANGED ***
    "max_kf_devel_neg_set #$# 40000", // *** CHANGED ***
    "max_kf_devel_sub_size #$# 4000",
    "max_kf_shot_devel_neg #$# 1",
    "max_kf_shot_devel_pos #$# 1",
    "neg_sampling_rate #$# 1.0",
    "pos_sampling_rate #$# 1.0",
    "svm_train_mem_size #$# 16000"
);

$nNumDupSys = 1; // *** CHANGED ***
                 
// ///////////////////////////// START ///////////////////////////

$szModelConfigName = "mediaeval-vsd-2014.devel2014-new";

$szFeatureType = "shot"; // local feature
                         // $szFeatureType = "CV"; // global feature
if ($argc != 3)
{
    printf("Usage: %s <ModelConfigName> <FeatureType>\n", $argv[0]);
    printf("Usage: %s  %s shot\n", $argv[0], $szModelConfigName, $szFeatureType );
    exit();
}

$szModelConfigName = $argv[1];

if ($nUseProbOutput)
{
    $nUseNormScore = 0;
} else
{
    $nUseNormScore = 1;
    exit();
}

$arCurrentConfig[] = sprintf("svm_train_use_prob_output #$# %d", $nUseProbOutput);


// --> BEFORE: the script scans ONE feature dir --> CURRENT: scan SEVERAL feature DIRS

    $szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
    $szFilter = $szFeatureType; // only collect shotMAX feature
    $arFeatureList = collectDirsInOneDir($szRootFeatureDir, $szFilter);
    sort($arFeatureList);
    
    // update Dec 25
    $szModelFeatureConfigDir = sprintf("%s/model/keyframe-5/%s/config/tmp", $szRootDir, $szModelConfigName);
    makeDir($szModelFeatureConfigDir);
    
    // template for fusion config - used in web app
    $arCustomConfig = array_merge($arCurrentConfig, $arDefaultConfig);
    
    foreach ($arFeatureList as $szFeatureExt)
    {
        if ($szFeatureType == "norm") // local feature
        {
            // not the real feature ext
            if (strstr($szFeatureExt, "raw") || strstr($szFeatureExt, "codebook"))
            {
                printf("### Skipping [%s]\n", $szFeatureExt);
                continue;
            }
        }
        
        if (! strstr($szFeatureExt, $szFeatureType))
        {
            printf("### Skipping [%s]\n", $szFeatureExt);
            continue;
        }
        
        $arCustomConfig = array_merge($arCurrentConfig, $arDefaultConfig);
        
        // do not change $arCurrentConfig, $arDefaultConfig
        $arCustomConfig[] = sprintf("feature_ext #$# %s", $szFeatureExt);
        
        $arFusion = $arCustomConfig; // adding fusion info
        for ($i = 1; $i <= $nNumDupSys; $i ++)
        {
            
            $szOutputName = sprintf("%s.R1%d", $szFeatureExt, $i);
           
            $szFPDestOutputFN = sprintf("%s/%s.cfg", $szModelFeatureConfigDir, $szOutputName);
            saveDataFromMem2File($arCustomConfig, $szFPDestOutputFN);
            
        }
        
    }

?>