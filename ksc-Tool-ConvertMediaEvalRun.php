<?php

// Scan model dir and list status

require_once 'ksc-AppConfig.php';

function loadSubmissionFile($szFPInputFN)
{
    loadListFile($arRawList, $szFPInputFN);
    
    $arShotIDCount = array();
    foreach($arRawList as $szLine)
    {
        // movie-TheGodFather-1972-dvd2008-MediaEval.mpg 1 0.00 5.52 event - violence 0.047557 t
        $arTmp = explode(' ', $szLine);
        $szVideoName = trim($arTmp[0]);
        $fShotStart = floatval($arTmp[2]);
        $fShotDuration = floatval($arTmp[3]);
        $fScore = floatval($arTmp[7]);
        
        if(!isset($arShotIDCount[$szVideoName]))
        {
            $arShotIDCount[$szVideoName] = 1;
        }
        $szShotID = sprintf('shot%d_%f_%f', $arShotIDCount[$szVideoName], $fShotStart, $fShotDuration);
        $arShotIDCount[$szVideoName]++;
        $arOutput[$szVideoName][$szShotID] = $fScore;
        
    }
    //print_r($arOutput);
    $arFinalOutput = array();
    foreach($arOutput as $szVideoID => $arTmp)
    {
        arsort($arTmp);
        $arFinalOutput[$szVideoID] = $arTmp;
    }
    
    //print_r($arFinalOutput);
    return $arFinalOutput;
}

//$szFPInputFN = '/net/per610a/export/das11f/ledduy/mediaeval-vsd-2013/kaori-secode-vsd/FinalSubmission/me13vsd_NII_shotlevel_subjviolentscenes_fuseallconfig.etf';
$szFPInputFN = '/net/per610a/export/das11f/ledduy/mediaeval-vsd-2013/kaori-secode-vsd/FinalSubmission/me13vsd_NII_shotlevel_objviolentscenes_fuseallconfig.etf';
$arOutput = loadSubmissionFile($szFPInputFN);
foreach($arOutput as $szVideoName => $arShotScoreList)
{
    if(!strstr($szVideoName, 'Legally'))
    {
        continue;
    }
    $nCount = 1;
    foreach($arShotScoreList as $szShotID => $fScore)
    {
        
        printf("%d. %s - %f \n", $nCount, $szShotID, $fScore);
        $nCount++;
    }
}
?>