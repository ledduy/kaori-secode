<?php

/**
 * 		@file 	ksc-Tool-Convert2NISTSubmissionFormat-TV13.php
 * 		@brief 	Convert From Ranked List to NIST Submission Format.
 *		@author Duy-Dinh Le (ledduy@gmail.com, ledduy@ieee.org).
 *
 * 		Copyright (C) 2010-2013 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Last update	: 05 Jul 2013.
 */
/**
 * Convert From Ranked List to NIST Submission Format.
 *
 * Copyright (C) 2010-2012 Duy-Dinh Le.
 * All rights reserved.
 * Email		: ledduy@gmail.com, ledduy@ieee.org.
 * Version		: 1.0.
 * Last update	: 25 Jul 2012.
 */

// *** Update Jul 25, 2012
// --> !!! IMPORTANT .rank files must be available

/*
 * <!ATTLIST videoFeatureExtractionRunResult trType (A|B|C|D|E|F) #REQUIRED sysId CDATA #REQUIRED class (M|P) #REQUIRED targetYear (13|14|15) #REQUIRED priority (1|2|3|4|5|6|7|8) #REQUIRED loc (Y|N) #REQUIRED desc CDATA #REQUIRED> <!-- trtype - system training type (see Guidelines) sysId - associated ID of the system (variant) that produced this result. This should incorporate an abbreviated form of the research group's name to make it unique across groups. 10 characters or less would be appreciated. class - indication of whether the run is a main (M) single-concept submission a concept-pair (P) run. targetYear - the year of the test data used (13 for non-progress runs; 14 or 15 for progress runs priority - evaluation priority If not all runs can be judged, then judging will begin with the run with priorty 1 and so on. Priority applies across all runs, regardless of class. Use each priority only once across all runs you submit. loc - indication of whether a file containing localization results is associated with this run; the file is specified on the submission webpage desc - verbal description of the characteristics of this run, how it differs in approach, resources, etc from others runs. -->
 */

/*
<!DOCTYPE videoFeatureExtractionResults SYSTEM "http://www-nlpir.nist.gov/projects/tv2013/dtds/videoFeatureExtractionResults.dtd">

<!-- Partial example video feature extraction results for two runs -->

<videoFeatureExtractionResults>

<videoFeatureExtractionRunResult targetYear="13" trType="A" sysId="SiriusCy1" priority="1" class="M" loc="N"
desc="This run uses the top secret x-component">

<videoFeatureExtractionFeatureResult fNum="3">
<item seqNum="1" shotId="shot118_2" />
<item seqNum="2" shotId="shot118_3" />
<item seqNum="3" shotId="shot18_19" /> 
<item seqNum="4" shotId="shot123_2" /> 
<item seqNum="5" shotId="shot56_42" /> 
<item seqNum="6" shotId="shot193_3" /> 
<item seqNum="7" shotId="shot121_12" /> 
<item seqNum="8" shotId="shot22_20" /> 
<item seqNum="9" shotId="shot103_122" /> 
<!-- ... -->
<item seqNum="2000" shotId="shot118_2" />
</videoFeatureExtractionFeatureResult>

<!-- ... -->

<videoFeatureExtractionFeatureResult fNum="478">
<item seqNum="1" shotId="shot118_2" />
<item seqNum="2" shotId="shot118_3" />
<item seqNum="3" shotId="shot18_19" /> 
<item seqNum="4" shotId="shot123_2" /> 
<item seqNum="5" shotId="shot56_42" /> 
<item seqNum="6" shotId="shot193_3" /> 
<item seqNum="7" shotId="shot121_12" /> 
<item seqNum="8" shotId="shot22_20" /> 
<item seqNum="9" shotId="shot103_122" /> 
<!-- ... -->
<item seqNum="2000" shotId="shot118_2" />
</videoFeatureExtractionFeatureResult>

</videoFeatureExtractionRunResult>

<!-- localization results in a separate file are associated with this run -->
<videoFeatureExtractionRunResult targetYear="13" trType="C" sysId="SiriusCy6" priority="2" class="P" loc="Y"
desc="This run does not use the x-component">

<videoFeatureExtractionFeatureResult fNum="911">
<item seqNum="1" shotId="shot118_2" firstFeature="1"/>
<item seqNum="2" shotId="shot118_3" firstFeature="1"/>
<item seqNum="3" shotId="shot18_19" firstFeature="2"/> 
<item seqNum="4" shotId="shot123_2" firstFeature="0"/> 
<item seqNum="5" shotId="shot56_42" firstFeature="2"/> 
<item seqNum="6" shotId="shot193_3" firstFeature="0"/> 
<item seqNum="7" shotId="shot121_12" firstFeature="1"/> 
<item seqNum="8" shotId="shot22_20" firstFeature="1"/> 
<item seqNum="9" shotId="shot103_122" firstFeature="2"/> 
<!-- ... -->
<item seqNum="2000" shotId="shot118_2" firstFeature="2"/>
</videoFeatureExtractionFeatureResult>

<!-- ... -->

<videoFeatureExtractionFeatureResult fNum="920">
<item seqNum="1" shotId="shot118_2" firstFeature="2"/>
<item seqNum="2" shotId="shot118_3" firstFeature="1"/>
<item seqNum="3" shotId="shot18_19" firstFeature="2"/> 
<item seqNum="4" shotId="shot123_2" firstFeature="0"/> 
<item seqNum="5" shotId="shot56_42" firstFeature="0"/> 
<item seqNum="6" shotId="shot193_3" firstFeature="1"/> 
<item seqNum="7" shotId="shot121_12" firstFeature="0"/> 
<item seqNum="8" shotId="shot22_20" firstFeature="2"/> 
<item seqNum="9" shotId="shot103_122" firstFeature="1"/> 
<!-- ... -->
<item seqNum="2000" shotId="shot118_2" firstFeature="2"/>
</videoFeatureExtractionFeatureResult>


</videoFeatureExtractionRunResult>

</videoFeatureExtractionResults>

*/

require_once "ksc-AppConfig.php";

// Full runs and light runs

$arSysList = array(
    "hlf-tv2013.nsc.bow.dense6mul+harlap6mul.rgbsift.RA.ksc.tvsin13.fusion" => "targetYear=\"13\" trType=\"A\" sysId=\"Kitty.13A1\" priority=\"1\" class=\"M\" loc=\"N\" desc=\"Fusion of COLORSIFT (dense6mul.rgbsift+harlap6mul.rgbsift), 3x1 + 1x1 grid, max 4K Pos + 40K Neg\"",
    
    "hlf-tv2013.nsc.bow.dense6mul+harlap6mul.rgbsift.RB.ksc.tvsin13.fusion" => "targetYear=\"14\" trType=\"A\" sysId=\"Kitty.14B1\" priority=\"1\" class=\"M\" loc=\"N\" desc=\"Fusion of COLORSIFT (dense6mul.rgbsift+harlap6mul.rgbsift), 3x1 + 1x1 grid, max 4K Pos + 40K Neg\"",
    
    "hlf-tv2013.nsc.bow.dense6mul+harlap6mul.rgbsift.RC.ksc.tvsin13.fusion" => "targetYear=\"15\" trType=\"A\" sysId=\"Kitty.15C1\" priority=\"1\" class=\"M\" loc=\"N\" desc=\"Fusion of COLORSIFT (dense6mul.rgbsift+harlap6mul.rgbsift), 3x1 + 1x1 grid, max 4K Pos + 40K Neg\"",
    
    "hlf-tv2013.nsc.bow.dense6mul.rgbsift.RA.ksc.tvsin13.fusion" => "targetYear=\"13\" trType=\"A\" sysId=\"Kitty.13A2\" priority=\"2\" class=\"M\" loc=\"N\" desc=\"Fusion of COLORSIFT (dense6mul.rgbsift), 3x1 + 1x1 grid, max 4K Pos + 40K Neg\"",
    
    "hlf-tv2013.nsc.bow.dense6mul.rgbsift.RB.ksc.tvsin13.fusion" => "targetYear=\"14\" trType=\"A\" sysId=\"Kitty.14B2\" priority=\"2\" class=\"M\" loc=\"N\" desc=\"Fusion of COLORSIFT (dense6mul.rgbsift), 3x1 + 1x1 grid, max 4K Pos + 40K Neg\"",
    
    "hlf-tv2013.nsc.bow.dense6mul.rgbsift.RC.ksc.tvsin13.fusion" => "targetYear=\"15\" trType=\"A\" sysId=\"Kitty.15C2\" priority=\"2\" class=\"M\" loc=\"N\" desc=\"Fusion of COLORSIFT (dense6mul.rgbsift), 3x1 + 1x1 grid, max 4K Pos + 40K Neg\"",
    
    "hlf-tv2013.nsc.bow.harlap6mul.rgbsift.RA.ksc.tvsin13.fusion" => "targetYear=\"13\" trType=\"A\" sysId=\"Kitty.13A3\" priority=\"3\" class=\"M\" loc=\"N\" desc=\"Fusion of COLORSIFT (harlapmul.rgbsift), 3x1 + 1x1 grid, max 4K Pos + 40K Neg\""
);

// hlf-tv2012.Kitty-F2.ksc.tvsin12.ksc.tvsin12.fusion used for Light run
$arOutputIDList = array(
    "hlf-tv2013.nsc.bow.dense6mul+harlap6mul.rgbsift.RA.ksc.tvsin13.fusion" => "NII.Kitty.13A1",
    
    "hlf-tv2013.nsc.bow.dense6mul+harlap6mul.rgbsift.RB.ksc.tvsin13.fusion" => "NII.Kitty.14B1",
    
    "hlf-tv2013.nsc.bow.dense6mul+harlap6mul.rgbsift.RC.ksc.tvsin13.fusion" => "NII.Kitty.15C1",
    
    "hlf-tv2013.nsc.bow.dense6mul.rgbsift.RA.ksc.tvsin13.fusion" => "NII.Kitty.13A2",
    
    "hlf-tv2013.nsc.bow.dense6mul.rgbsift.RB.ksc.tvsin13.fusion" => "NII.Kitty.14B2",
    
    "hlf-tv2013.nsc.bow.dense6mul.rgbsift.RC.ksc.tvsin13.fusion" => "NII.Kitty.15C2",
    
    "hlf-tv2013.nsc.bow.harlap6mul.rgbsift.RA.ksc.tvsin13.fusion" => "NII.Kitty.13A3"
);

// RunID --> for locating .rank files
// A - used only IACC training data
// If the run is a SIN run of the "no annotation" sort then choose from the following 2 training types:
// E - used only training data collected automatically using only the concepts' name and definition
// F - used only training data collected automatically using a query built manually from the concepts' name and definition
// As the name "no annotation" indicates, for the categories E and F, no manual annotation should be done on the automatically collected data; automatic processing is allowed and encouraged but data should be processed blindly.

// class - indication of whether the fun is a full (F) submission
// or a light (L) submission as defined in the guidelines or a concept-pair (P) run.

$szRootDir = $gszRootBenchmarkDir; // defined in ksc-AppConfig
$szRootExpDir = sprintf("%s/experiments/hlf-tv2013", $gszRootBenchmarkExpDir); // New Jul 18

$szNISTSubmissionOutputDir = sprintf("%s/nist-submission", $szRootExpDir);
makeDir($szNISTSubmissionOutputDir);

foreach ($arSysList as $szRunID => $szRunDesc)
{
    $szListNamezz = "hlf-tv2013.Concepts.lst";
    $szFPConceptListFN = sprintf("%s/annotation/%s", $szRootExpDir, $szListNamezz);
    $arConceptList = loadConceptListDatazz($szFPConceptListFN);
    
    $arOutput = array();
    $arOutput[] = "<!DOCTYPE videoFeatureExtractionResults SYSTEM \"http://www-nlpir.nist.gov/projects/tv2013/dtds/videoFeatureExtractionResults.dtd\">";
    $arOutput[] = "<videoFeatureExtractionResults>";
    $arOutput[] = sprintf("<videoFeatureExtractionRunResult %s>", $szRunDesc);
    
    $szExpDir = sprintf("%s/results/%s", $szRootExpDir, $szRunID);
    
    foreach ($arConceptList as $szConceptName => $nConceptID)
    {
        $szFeatureID = sprintf("%03d", $nConceptID);
        $arOutput[] = sprintf("<videoFeatureExtractionFeatureResult fNum=\"%s\">", $szFeatureID);
        
        // 9004.Airplane_Flying.rank
        $szFPRankListFN = sprintf("%s/%s.rank", $szExpDir, $szConceptName);
        
        if (! file_exists($szFPRankListFN))
        {
            printf("File [%s] not found\n", $szFPRankListFN);
            exit();
        }
        loadListFile($arRankList, $szFPRankListFN);
        $nSeqNum = 1;
        foreach ($arRankList as $szRankLine)
        {
            // 9015 0 TRECVID2010_11306.shot11306_47 1 0.74788911318681 hlf-tv2010.run1003.fusion.run10x
            $arTmp = explode(" ", $szRankLine);
            
            $nConceptIDCheck = intval($arTmp[0]) - 9000;
            if ($nConceptIDCheck != $nConceptID)
            {
                printf("Serious error [%s] - [%s]!\n", $nConceptIDCheck, $nConceptID);
                exit();
            }
            
            $szFullShotID = trim($arTmp[2]);
            if (! strstr($szFullShotID, "TREC"))
            {
                printf("Serious error!\n");
                exit();
            }
            $arTmpz = explode(".", $szFullShotID);
            $szShotID = trim($arTmpz[1]);
            $arOutput[] = sprintf("<item seqNum=\"%d\" shotId=\"%s\" />", $nSeqNum, $szShotID);
            $nSeqNum ++;
        }
        $arOutput[] = sprintf("</videoFeatureExtractionFeatureResult>");
    }
    $arOutput[] = "</videoFeatureExtractionRunResult>";
    $arOutput[] = "</videoFeatureExtractionResults>";
    
    $szRunIDOutput = $arOutputIDList[$szRunID];
    $szFPOutputFN = sprintf("%s/%s.xml", $szNISTSubmissionOutputDir, $szRunIDOutput);
    saveDataFromMem2File($arOutput, $szFPOutputFN);
}

// ////////////////////////////////////// FUNCTIONS ////////////////////////////////////
function loadConceptListDatazz($szFPConceptListFN)
{
    loadListFile($arRawList, $szFPConceptListFN);
    $arConceptList = array();
    foreach ($arRawList as $szLine)
    {
        // 9001.Actor #$# Actor #$# 0001 #$# Actor #$# 0149 #$# 0001
        $arTmp = explode("#$#", $szLine);
        $szConceptName = trim($arTmp[0]);
        
        $nLocalConceptID = intval($arTmp[2]);
        $nTV10ConceptID = intval($arTmp[5]);
        if ($nLocalConceptID != $nTV10ConceptID)
        {
            printf("Serious error!\n");
            exit();
        }
        
        $arTmp = explode(".", $szConceptName);
        $nValidateConceptID = intval($arTmp[0]) - 9000;
        if ($nValidateConceptID != $nLocalConceptID)
            if ($nLocalConceptID != $nTV10ConceptID)
            {
                printf("Serious error!\n");
                exit();
            }
        
        $arConceptList[$szConceptName] = $nTV10ConceptID;
    }
    
    return $arConceptList;
}

?>