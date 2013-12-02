<?php

// Scan model dir and list status

require_once 'ksc-AppConfig.php';

$szRootResultDir = '/net/per610a/export/das11f/ledduy/mediaeval-vsd-2013/experiments/mediaeval-vsd2013-shot/results'; 

$arRunIDList = array(
	'mediaeval-vsd2013-shot.mfcc.rastamat.cb256.fc.pca.l2.R1' => 'mfcc',
    'mediaeval-vsd2013-shot.densetrajectory.mbh.cb256.fc.pca.l2.R1' => 'dt-mbh'
);
$arConceptList = array('objviolentscenes', 'subjviolentscenes');

$arShotLUT = array();
$szRootMetaDataDir = '/net/per610a/export/das11f/ledduy/mediaeval-vsd-2013/metadata/keyframe-5';
$szFPMetaDataFN = sprintf('%s/test2013-new.lst', $szRootMetaDataDir);
loadListFile($arVideoList, $szFPMetaDataFN);
foreach($arVideoList as $szLine)
{
    $arTmp = explode('#$#', $szLine);
    // VSD13_22_001 #$# VSD13_22_001 #$# test2013-new
    $szVideoID = trim($arTmp[0]); 
    $szVideoPath = trim($arTmp[2]);
    
    $szFPShotLisFN = sprintf('%s/%s/%s.prgz', $szRootMetaDataDir, $szVideoPath, $szVideoID);
    loadListFile($arShotList, $szFPShotLisFN);
    foreach($arShotList as $szLine)
    {
        $arTmp = explode('#$#', $szLine);    
        $szShotID = trim($arTmp[1]);
        
        $arShotLUT[$szShotID] = $szVideoID;
    }
}

foreach($arRunIDList as $szRunID => $szFeatureExt)
{
    foreach($arConceptList as $szConceptID)
    {
        $szResultDir = sprintf('%s/%s/%s', $szRootResultDir, $szRunID, $szConceptID);
        $szFPSangVSDFN = sprintf('%s/SangVSD.res', $szResultDir);
        
        loadListFile($arRawList, $szFPSangVSDFN);
        
        $arResult = array();
        foreach($arRawList as $szLine)
        {
            $arTmp = explode('#$#', $szLine);
            $szShotID = trim($arTmp[0]);
            $fScore = floatval($arTmp[1]);
            
            if(!isset($arShotLUT[$szShotID]))
            {
                printf($szShotID);
                exit('Serious error\n');
            }
            $szVideoID = $arShotLUT[$szShotID];
            $arResult[$szVideoID][$szShotID] = $fScore;
        }

        foreach($arResult as $szVideoID => $arScoreList)
        {
            $arOutput = array();
            foreach($arScoreList as $szShotID => $fScore)
            {
                $arOutput[] = sprintf('%s #$# %s', $szShotID, $fScore);
            }
        
            $szFPOutputFN = sprintf('%s/%s.%s.svm.res', $szResultDir, $szVideoID, $szFeatureExt);
            saveDataFromMem2File($arOutput, $szFPOutputFN);       
        }        
    }
}

?>