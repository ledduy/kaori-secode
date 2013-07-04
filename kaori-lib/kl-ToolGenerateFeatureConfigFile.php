<?php

/**
 * 		Generate list of config feature files.
 * 
 * 		Copyright (C) 2011 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 23 Nov 2011.
 */

require_once "kl-GenerateFeatureConfigFile.php";

// for granularity
$arGridList = array(1, 2, 3, 4, 5, 6, 7);
$nNumGridPoints = sizeof($arGridList);

// for color space
$arColorTypeList = array("CV_Luv", "CV_HSV", "CV_RGB", "CV_YCrCb");
$nNumColorTypes = sizeof($arColorTypeList);

// for histogram quantization
$arCHBinList = array(4, 8, 16, 32, 64);
$nNumCHBinTypes = sizeof($arCHBinList);

$arEOHBinList = array(72, 36, 18, 12, 6);
$nNumEOHBinTypes = sizeof($arEOHBinList);

$arLBPBinList = array(5, 10, 20, 30, 59);
$nNumLBPBinTypes = sizeof($arLBPBinList);

$arOutputNameList = array();

$szOutputDir = "../../config/BaselineFeatureConfig";

for($i=0; $i<$nNumGridPoints; $i++)
{
	$nNumRows = $nNumCols = $arGridList[$i];

	// for lbp
	$szColorType = "CV_GRAY";
	for($k=0; $k<$nNumLBPBinTypes; $k++)
	{
		$nNumBins = $arLBPBinList[$k];

		$szFeatureExt = sprintf("nsc.c%s.g%d.q%d.g_lbp", $szColorType, $nNumRows, $nNumBins);
		$szOutputName = sprintf("ConfigFile.%s.txt", $szFeatureExt);
		$arOutputNameList[] = sprintf("%s #$# %s", $szFeatureExt, $szOutputName);
		
		$szFPOutputFN = sprintf("%s/%s", $szOutputDir, $szOutputName);
		generateLBPFeatureConfigFile($szFPOutputFN, $nNumRows, $nNumCols, $nNumBins);
	}

	// for color histogram & color moments
	for($j=0; $j<$nNumColorTypes; $j++)
	{
		$szColorType = $arColorTypeList[$j];

		for($k=0; $k<$nNumCHBinTypes; $k++)
		{
			$nNumBins = $arCHBinList[$k];

			$szFeatureExt = sprintf("nsc.c%s.g%d.q%d.g_ch", $szColorType, $nNumRows, $nNumBins);
			$szOutputName = sprintf("ConfigFile.%s.txt", $szFeatureExt);
			$arOutputNameList[] = sprintf("%s #$# %s", $szFeatureExt, $szOutputName);
			$szFPOutputFN = sprintf("%s/%s", $szOutputDir, $szOutputName);
			generateCHFeatureConfigFile($szFPOutputFN, $szColorType, $nNumRows, $nNumCols,
			$nNumBins, $nNumBins, $nNumBins);
		}

		$nNumBins = 3; // # moments
		$szFeatureExt = sprintf("nsc.c%s.g%d.q%d.g_cm", $szColorType, $nNumRows, $nNumBins);
		$szOutputName = sprintf("ConfigFile.%s.txt", $szFeatureExt);
		$arOutputNameList[] = sprintf("%s #$# %s", $szFeatureExt, $szOutputName);
		$szFPOutputFN = sprintf("%s/%s", $szOutputDir, $szOutputName);
		generateCMFeatureConfigFile($szFPOutputFN, $szColorType, $nNumRows, $nNumCols);
	}

	// for edge
	$szColorType = "CV_GRAY";
	$tLow = 10.0;
	$tHigh = 30.0;
	for($k=0; $k<$nNumEOHBinTypes; $k++)
	{
		$nNumEdgeBins = $arEOHBinList[$k];

		$szFeatureExt = sprintf("nsc.c%s.g%d.q%d.g_eoh", $szColorType, $nNumRows, $nNumEdgeBins);
		$szOutputName = sprintf("ConfigFile.%s.txt", $szFeatureExt);
		$arOutputNameList[] = sprintf("%s #$# %s", $szFeatureExt, $szOutputName);
		$szFPOutputFN = sprintf("%s/%s", $szOutputDir, $szOutputName);
		generateEOHFeatureConfigFile($szFPOutputFN, $nNumRows, $nNumCols, $nNumEdgeBins, $tLow, $tHigh);
	}
}

/*
$szFeatureListName = "BaselineFeatureConfig-tv2009";
$szFPOutputFN = sprintf("%s/%s.lst", $szOutputDir, $szFeatureListName);
saveDataFromMem2File($arOutputNameList, $szFPOutputFN, "wt");
*/

?>