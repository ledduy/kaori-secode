<?php

/**
 * 
 * @param $arScoreList: $arScoreList[DocID] = fVal  --> key is DocID and arScoreList has been sorted.
 * @param $arAnnList: $arAnnList[DocID] = nLabel --> key is DocID, nLabel \in {0, 1}
 */
function computePrecisionRecall(&$arAnnList, &$arScoreList, $nMaxDocs = 2000)
{
    // scan ann list to get total hits
    $nNumAnns = sizeof($arAnnList);
    $arList = array_keys($arAnnList);
    $nTotalHits = 0;
    for ($i = 0; $i < $nNumAnns; $i ++)
    {
        $szDocID = $arList[$i];
        $nLabel = $arAnnList[$szDocID];
        
        if ($nLabel > 0)
        {
            $nTotalHits ++;
        }
    }
    
    $arDocList = array_keys($arScoreList);
    $nNumDocs = sizeof($arScoreList);
    
    $nHits = 0;
    $nNumCounts = min($nMaxDocs, $nNumDocs);
    for ($i = 0; $i < $nNumCounts; $i ++)
    {
        $szDocID = $arDocList[$i];
        $nLabel = $arAnnList[$szDocID];
        
        if (isset($arAnnList[$szDocID]))
        {
            $nLabel = $arAnnList[$szDocID];
        } else
        {
            $nLabel = 0;
        }
        
        if ($nLabel > 0)
        {
            $nHits ++;
        }
    }
    
    $arOutput = array();
    $arOutput['num_hits'] = $nHits;
    $arOutput['total_hits'] = $nTotalHits;
    
    if ($nTotalHits)
    {
        $arOutput['rec'] = ($nHits * 100.0) / $nTotalHits;
    } else
    {
        $arOutput['rec'] = 0.0;
    }
    
    if ($nNumCounts)
    {
        $arOutput['prec'] = ($nHits * 100.0) / $nNumCounts;
    } else
    {
        $arOutput['prec'] = 0.0;
    }
    
    if ($arOutput['rec'] + $arOutput['prec'])
    {
        $arOutput['f1_score'] = 2 * $arOutput['rec'] * $arOutput['prec'] / ($arOutput['rec'] + $arOutput['prec']);
    } else
    {
        $arOutput['f1_score'] = 0;
    }
    
    return $arOutput;
}

/**
 * Convert to format of computeAveragePrecision
 */
function computeAPForOneRankedList(&$arAnnList, &$arRankedList, $nMaxDocs = 2000)
{
    $arScoreList = array();
    $nCount = sizeof($arRankedList);
    for ($i = 0; $i < $nCount; $i ++)
    {
        $szKey = $arRankedList[$i];
        $arScoreList[$szKey] = $i;
    }
    
    $arAP = computeAveragePrecision($arAnnList, $arScoreList, $nMaxDocs);
    
    return $arAP;
}

/**
 * REF: http://en.wikipedia.org/wiki/Information_retrieval#Mean_Average_precision
 *
 * @param $arAnnList -->
 *            $arAnnList[$szDocID] = $nLabel (nLabel is used to compute #hits)
 * @param $arScoreList -->
 *            $arScoreList[$szDocID] = $fScore
 * @param
 *            $nMaxDocs
 */
function computeAveragePrecision(&$arAnnList, &$arScoreList, $nMaxDocs = 10000)
{
    // scan ann list to get total hits
    $nNumAnns = sizeof($arAnnList);
    $arList = array_keys($arAnnList);
    $nTotalHits = 0;
    for ($i = 0; $i < $nNumAnns; $i ++)
    {
        $szDocID = $arList[$i];
        
        if (isset($arAnnList[$szDocID]))
        {
            $nLabel = $arAnnList[$szDocID];
        } else
        {
            $nLabel = 0;
        }
        
        if ($nLabel > 0)
        {
            $nTotalHits ++;
        }
    }
    
    $arDocList = array_keys($arScoreList);
    
    // print_r($arDocList);
    // print_r($arAnnList);
    
    $nNumDocs = sizeof($arScoreList);
    
    $nHits = 0;
    $nNumCounts = min($nMaxDocs, $nNumDocs);
    
    $arRecList = array();
    $arPrecList = array();
    $nRank = 0;
    for ($i = 0; $i < $nNumCounts; $i ++)
    {
        $nRank ++;
        
        $szDocID = $arDocList[$i];
        
        if (isset($arAnnList[$szDocID]))
        {
            $nLabel = $arAnnList[$szDocID];
        } else
        {
            $nLabel = 0;
        }
        
        if ($nLabel > 0)
        {
            $nHits ++;
            
            $fRecall = 1;
        } else
        {
            $fRecall = 0;
        }
        
        $fPrecision = 100.0 * $nHits / $nRank;
        $arRecList[$nRank] = $fRecall;
        $arPrecList[$nRank] = $fPrecision;
    }
    
    $nNumRecPoints = sizeof($arRecList);
    $arKeys = array_keys($arRecList);
    $fSum = 0;
    for ($i = 0; $i < $nNumRecPoints; $i ++)
    {
        $szKey = $arKeys[$i];
        $fSum += $arPrecList[$szKey] * $arRecList[$szKey];
    }
    
    $fAveragePrecision = $fSum / $nTotalHits;
    
    $arOutput = array();
    
    $arOutput['total_hits'] = $nTotalHits;
    $arOutput['prec_list'] = $arPrecList;
    $arOutput['rec_list'] = $arRecList;
    $arOutput['ap'] = $fAveragePrecision;
    
    return $arOutput;
}

/**
 * This one is used in ICDM paper --> almost the same with the above
 *
 * NEW:
 * + If $nMaxDocs < $nAllHits --> $nMaxDocs = $nAllHits;
 */

/**
 *
 * @param $arScoreList: $arScoreList[DocID]
 *            = fVal --> key is DocID and arScoreList has been sorted.
 * @param $arAnnList: $arAnnList[DocID]
 *            = nLabel --> key is DocID, nLabel \in {0, 1}
 */
function computeTVAveragePrecision(&$arAnnList, &$arScoreList, $nMaxDocs = 2000)
{
    // scan ann list to get total hits
    $nNumAnns = sizeof($arAnnList);
    $arList = array_keys($arAnnList);
    $nAllHits = 0;
    for ($i = 0; $i < $nNumAnns; $i ++)
    {
        $szDocID = $arList[$i];
        
        if (isset($arAnnList[$szDocID]))
        {
            $nLabel = $arAnnList[$szDocID];
        } else
        {
            $nLabel = 0;
        }
        
        if ($nLabel > 0)
        {
            $nAllHits ++;
        }
    }
    
    // NEW!!
    if ($nAllHits > $nMaxDocs)
    {
        $nAllHits = $nMaxDocs;
    }
    
    // print_r($arAnnList); exit();
    
    $arDocList = array_keys($arScoreList);
    $nNumDocs = sizeof($arScoreList);
    
    $nHits = 0;
    $nNumCounts = min($nMaxDocs, $nNumDocs);
    
    $arRecList = array();
    $arPrecList = array();
    $arHitList = array();
    for ($i = 0; $i < $nNumCounts; $i ++)
    {
        $szDocID = $arDocList[$i];
        
        if (isset($arAnnList[$szDocID]))
        {
            $nLabel = $arAnnList[$szDocID];
        } else
        {
            $nLabel = 0;
        }
        
        if ($nLabel > 0)
        {
            $nHits ++;
            
            $fRecall = 100.0 * $nHits / $nAllHits;
            $fPrecision = 100.0 * $nHits / ($i + 1);
            $arRecList[] = $fRecall;
            $arPrecList[] = $fPrecision;
            $arHitList[] = $i;
        }
    }
    
    $nIndex = 0;
    $nNumRecPoints = sizeof($arRecList);
    $fSum = 0;
    for ($i = 0; $i <= 100; $i += 10)
    {
        // compute interpolated precision at recall = 0%, 10%, 20%, ...
        // interpolated prec = prec (rec>=i)
        $arInterpolatedPrec[$nIndex] = 0;
        for ($j = $nNumRecPoints - 1; $j >= 0; $j --)
        {
            if ($arRecList[$j] >= $i)
            {
                if ($arPrecList[$j] > $arInterpolatedPrec[$nIndex])
                {
                    $arInterpolatedPrec[$nIndex] = $arPrecList[$j];
                }
            } else
            {
                break;
            }
        }
        $fSum += $arInterpolatedPrec[$nIndex];
        $nIndex ++;
    }
    // print_r($arInterpolatedPrec);
    $fAveragePrecision = $fSum / 11.0;
    // exit("MAP: $fAveragePrecision");
    
    $arOutput = array();
    
    $arOutput['total_hits'] = $nHits;
    $arOutput['prec_list'] = $arPrecList;
    $arOutput['rec_list'] = $arRecList;
    $arOutput['hit_list'] = $arHitList;
    $arOutput['ap'] = $fAveragePrecision;
    
    return $arOutput;
}
?>