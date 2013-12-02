<?php

// Scan model dir and list status

require_once 'ksc-AppConfig.php';

$szModelDir = '/net/per610a/export/das11f/ledduy/mediaeval-vsd-2013/experiments/mediaeval-vsd2013-shot/models'; 

$arModelDirList = collectDirsInOneDir($szModelDir);
sort($arModelDirList);

$arOutput = array();
$arOutput[] = sprintf('RunID, ConceptID, Done, LogSize, LogFileLastAccess');
foreach($arModelDirList as $szRunID)
{
    $szRunModelDir = sprintf('%s/%s', $szModelDir, $szRunID);
    $arRunConceptList = collectDirsInOneDir($szRunModelDir);
    sort($arRunConceptList);
    foreach($arRunConceptList as $szConcepName)
    {
        $szConceptModelDir = sprintf('%s/%s', $szRunModelDir, $szConcepName);
        $arConceptModelList = collectFilesInOneDir($szConceptModelDir, '', '.model.tar.gz');
        $arConceptModelLogList = collectFilesInOneDir($szConceptModelDir, '', '.model.log');
        
        $nSize = 0;
        if(sizeof($arConceptModelLogList))
        {
            $szFPLogFN = sprintf('%s/%s.model.log', $szConceptModelDir, $arConceptModelLogList[0]);
            $nSize = filesize($szFPLogFN);
        }
        
        $szOutput = sprintf('%s, %s, %d, %d, %s', $szRunID, $szConcepName, sizeof($arConceptModelList), $nSize, date("H:i:s.F d Y", filectime($szFPLogFN)));
        $arOutput [] = $szOutput;
    }
}

saveDataFromMem2File($arOutput, 'mediaeval-vsd-2013.model.csv');
?>