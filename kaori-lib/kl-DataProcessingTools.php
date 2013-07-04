<?php

/**
 * 		Tools for processing data formats.
 *
 * 		Copyright (C) 2011 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 23 Nov 2011.
 */

// Update Nov 25 2010
// Add functions to handle svf format
// Add descriptions to some functions
// * @@@IMPORTANT: First index is 1 --> for compatible with libsvm format in which first index starts from 1
// function parseOneRowDvf2FeatureVector(&$szOneRowDvfData)
// Modify functions related to svf format --> check value <> 0
// Move functions loading GZDvf and GZSvf from nsc-TRECVIDTools to here

/////////////////////////////////////////////////////////////////
require_once "kl-AppConfig.php";
require_once "kl-IOTools.php";
require_once "kl-MiscTools.php";

/**
 * Count number of comment lines starting from the beginning of a list of string.
 * Each comment line starts with special character '%'. This convention is derived from GreedyRSC Tool.
 */
function countNumCommentLines(&$arRowList)
{
	$nCount = 0;

	$nNumRows = sizeof($arRowList);

	for($i=0; $i<$nNumRows; $i++)
	{
		// if the first character of the line is % --> comment line
		if($arRowList[$i][0] == '%')
			$nCount++;
		else
			break;
	}
	return $nCount;
}

/**
 * Parse one row (~ one string) into a feature vector (~ array of float values).
 * dvf format: NumDims Val1 Val2 ... ValN. Values are seperated by ONE space (' ')
 * Return is $arOutput['feature_vector'].
 * @@@IMPORTANT: First index is 1 --> for compatible with libsvm format in which first index starts from 1
 */

function parseOneRowDvf2FeatureVector(&$szOneRowDvfData)
{
	$szOutput = explode(" ", $szOneRowDvfData);
	$nNumDims = trim($szOutput[0]);
	if(sizeof($szOutput) != $nNumDims+1)
	{
		// another try if there are 2 spaces instead of one space

		$szOutput = explode("  ", $szOneRowDvfData);
		$nNumDims = trim($szOutput[0]);

		if(sizeof($szOutput) != $nNumDims+1)
		{
			print_r($szOutput);
			terminatePrg("Inconsistency in data - the number of dimensions and the values are not matched");
		}
	}

	$arFeatureVector = array();

	for($i=0; $i<$nNumDims; $i++)
	{
		// to ensure first index is always 1, not 0
		$arFeatureVector[$i+1] = floatval($szOutput[$i+1]);
	}

	$arOutput = array();
	$arOutput['feature_vector'] = $arFeatureVector;

	return $arOutput;
}

/**
 * Parse one row (~ one string) into a feature vector (~ array of float values).
 * svf format: NumDims Pos0 Val0 Pos1 Val ...
 * Values are seperated by ONE space (' ')
 * Return is $arOutput['feature_vector'].
 */

function parseOneRowSvf2FeatureVector(&$szOneRowDvfData)
{
	$szOutput = explode(" ", $szOneRowDvfData);
	$nNumDims = trim($szOutput[0]);
	if(sizeof($szOutput) != 2*$nNumDims+1)
	{
		// another try if there are 2 spaces instead of one space
		$szOutput = explode("  ", $szOneRowDvfData);
		$nNumDims = trim($szOutput[0]);

		if(sizeof($szOutput) != 2*$nNumDims+1)
		{
			print_r($szOutput);
			terminatePrg("Inconsistency in data - the number of dimensions and the values are not matched");
		}
	}

	$arFeatureVector = array();

	for($i=0; $i<$nNumDims; $i++)
	{
		$nIndex = intval($szOutput[2*$i+1]);
		$fVal = floatval($szOutput[2*$i+2]);
		$arFeatureVector[$nIndex] = $fVal;
	}

	$arOutput = array();
	$arOutput['feature_vector'] = $arFeatureVector;

	return $arOutput;
}

/**
 * One row dvf-ann feature consists of 2 parts: feature data and annotation data seperated by '%'
 * Keyframe index = 3 --> the 3-rd member is for keyframe id --> STARTING FROM 0 --> the 3rd member means KFIndex=2
 * Ann format: partition -->  program --> keyframe. Each is separated by ONE space (' ')
 * Output: arOutput['raw_ann'], $arOutput['keyframe_id'], arOutput['feature_vector']
 */

function parseOneRowDvfAnn2FeatureVector(&$szOneRowDvfAnnData, $nKFIndex=2)
{
	$szOutput = explode("%", $szOneRowDvfAnnData);

	if(sizeof($szOutput) != 2)
	{
		print_r($szOutput);
		terminatePrg("Ann data does not have annotation part with '%'");
	}

	$szDvfData = trim($szOutput[0]);
	$arTmpOutput = parseOneRowDvf2FeatureVector($szDvfData);

	$szAnnotation = trim($szOutput[1]);
	$szOutput = explode(" ", $szAnnotation);

	if(sizeof($szOutput) < $nKFIndex)
	{
		print_r($szOutput);
		printf("%s\n", $szOneRowDvfAnnData);
		terminatePrg("Invalid keyframe index");
	}

	$szKeyFrameID = trim($szOutput[$nKFIndex]);

	$arOutput = array();
	$arOutput['raw_ann'] = $szAnnotation;
	$arOutput['keyframe_id'] = $szKeyFrameID;
	$arOutput['feature_vector'] = $arTmpOutput['feature_vector'];

	return $arOutput;
}

/**
 * One row svf-ann feature consists of 2 parts: feature data and annotation data seperated by '%'
 * Keyframe index = 3 --> the 3-rd member is for keyframe id --> STARTING FROM 0 --> the 3rd member means KFIndex=2
 * Ann format: partition -->  program --> keyframe. Each is separated by ONE space (' ')
 * Output: arOutput['raw_ann'], $arOutput['keyframe_id'], arOutput['feature_vector']
 */

function parseOneRowSvfAnn2FeatureVector(&$szOneRowDvfAnnData, $nKFIndex=2)
{
	$szOutput = explode("%", $szOneRowDvfAnnData);

	if(sizeof($szOutput) != 2)
	{
		print_r($szOutput);
		terminatePrg("Ann data does not have annotation part with '%'");
	}

	$szDvfData = trim($szOutput[0]);
	$arTmpOutput = parseOneRowSvf2FeatureVector($szDvfData);

	$szAnnotation = trim($szOutput[1]);
	$szOutput = explode(" ", $szAnnotation);

	if(sizeof($szOutput) < $nKFIndex)
	{
		print_r($szOutput);
		printf("%s\n", $szOneRowDvfAnnData);
		terminatePrg("Invalid keyframe index");
	}

	$szKeyFrameID = trim($szOutput[$nKFIndex]);

	$arOutput = array();
	$arOutput['raw_ann'] = $szAnnotation;
	$arOutput['keyframe_id'] = $szKeyFrameID;
	$arOutput['feature_vector'] = $arTmpOutput['feature_vector'];

	return $arOutput;
}

/**
 * LibSVM format: Label Index1:Val1 Index2:Val2 ... IndexN:ValN.
 * Output: arOutput['feature_vector'], arOutput['label']
 */

//
// !!! IMPORTANT two spaces between Label and the first pair  --> Modified (22.03.09) to handle this case
function parseOneRowLibSVM2FeatureVector(&$szOneRowLibSVMData)
{
	$szOutput = explode(" ", $szOneRowLibSVMData);
	$nLabel = intval($szOutput[0]);

	$nNumKeys = sizeof($szOutput);

	$arFeatureVector = array();
	for($i=1; $i<$nNumKeys; $i++)
	{
		$szIndexValPair = $szOutput[$i];
		$szTmp = explode(":", $szIndexValPair);

		if(sizeof($szTmp) != 2)
		{
			continue; // ignore this part
		}

		$nIndex = intval($szTmp[0]);
		$fVal = floatval($szTmp[1]);

		$arFeatureVector[$nIndex] = $fVal;
	}

	if(!sizeof($arFeatureVector))
	{
		print_r($szOutput);
		terminatePrg("Error in data format [{$szOneRowLibSVMData}]. No any pair [index:value] is found!");
	}


	$arOutput = array();
	$arOutput['label'] = $nLabel;
	$arOutput['feature_vector'] = $arFeatureVector;

	$arFeatureVector = NULL;
	$szOutput = NULL;

	return $arOutput;
}

/**
 *	Input is feature vector (no label, no annotation) in rsc-dvf format
 * 	Output is a string: len val1 val2 ...
 *  Key info is ignored!
 */
function convertFeatureVector2DvfFormat(&$arFeatureVector)
{
	$nNumDims = sizeof($arFeatureVector);
	$arKeys = array_keys($arFeatureVector);

	$szOutput = sprintf("%d", $nNumDims);
	for($i=0; $i<$nNumDims; $i++)
	{
		$nKey = $arKeys[$i];

		// only val
		$szOutput = $szOutput . " " . $arFeatureVector[$nKey];
	}

	return $szOutput;
}

/**
 *	Input is feature vector (no label, no annotation) in rsc-svf format
 * 	Output is a string: len index1 val1 index2 val2 ...
 * 	Modified to truth svf on Nov 25
 */
function convertFeatureVector2SvfFormat(&$arFeatureVector)
{
	$nNumDims = sizeof($arFeatureVector);
	$arKeys = array_keys($arFeatureVector);

	//$szOutput = sprintf("%d", $nNumDims);

	$szOutput = "";
	$nNonZeroCount = 0;
	for($i=0; $i<$nNumDims; $i++)
	{
		$nKey = $arKeys[$i];
		$nIndex = $nKey;

		$fVal = $arFeatureVector[$nKey];
		if($fVal)
		{
			// pair --> index val
			$szOutput = $szOutput . " " . $nIndex . " " . $fVal;
				
			$nNonZeroCount++;
		}
	}

	$szOutput = $nNonZeroCount . $szOutput; // the space is already put

	return $szOutput;
}

/**
 * Input is feature vector (no label, no annotation) in svf format
 * Output is a string: index1:val1 index2:val2 ...
 * 	Modified to truth svf on Nov 25
 *
 */
function convertFeatureVector2LibSVMSvfFormat(&$arFeatureVector)
{
	$nNumDims = sizeof($arFeatureVector);
	$arKeys = array_keys($arFeatureVector);

	$szOutput = "";
	for($i=0; $i<$nNumDims; $i++)
	{
		$nKey = $arKeys[$i];
		$nIndex = $nKey;

		$fVal = $arFeatureVector[$nKey];

		if($fVal)
		{
			// pair --> index:val
			$szOutput = $szOutput . " " . $nIndex . ":" . $fVal;
		}
	}

	return $szOutput;
}

/**
 * Combine feature vector and label --> libsvm use svf format
 */
function convertFeatureVector2LibSVMFormat(&$arFeatureVector, $nLabel)
{
	// libsvm use svf format
	$szFeatureVector = convertFeatureVector2LibSVMSvfFormat($arFeatureVector);
	$szOutput = sprintf("%d %s", $nLabel, $szFeatureVector);

	return $szOutput;
}

/**
 * Input data in LibSVM format consists of label and data
 * output data consists of 2 files, one for label, one for svf
 */
function convertLibSVMData2RSCSvfAnnData($szFPSvfOutputFN, $szFPAnnOutputFN, $szFPLibSVMFN)
{
	$nNumSamples = loadListFile($arSampleList, $szFPLibSVMFN);

	printf("Parsing %d LibSVM format samples ...", $nNumSamples);
	printProgressStart();

	$arLabelOutput = array();
	$arSvfOutput = array();
	$arSvfOutput[] = sprintf("%% %s %d", $szFPSvfOutputFN, $nNumSamples); // comment row
	$arSvfOutput[] = sprintf("%d", $nNumSamples); // comment row
	for($i=0; $i<$nNumSamples; $i++)
	{
		$arOutput = parseOneRowLibSVM2FeatureVector($arSampleList[$i]);
		$arLabelOutput[$i] = $arOutput['label'];
		$arSvfOutput[] = convertFeatureVector2SvfFormat($arOutput['feature_vector']);

		unset($arSampleList[$i]);
		$arSampleList[$i] = NULL;

		printProgress($i, $nNumSamples, 1000);
	}
	unset($arSampleList);  // free mem
	printf("\nFinish parsing %d LibSVM format samples!\n", $nNumSamples);

	saveDataFromMem2File($arLabelOutput, $szFPAnnOutputFN, "wt");
	saveDataFromMem2File($arSvfOutput, $szFPSvfOutputFN, "wt");
}

/**
 * Input is one dvf-ann format file.
 * Output is two files:
 * 		+ one is for feature vector part ([feature_vector+label] in LibSVM format),
 * 		+ one is for ann part (only pick the [keyframe_id])
 */
function convertDvfAnnFormat2LibSVMFormat($szFPOutputFN, $szFPAnnFN, $szFPInputFN, $nPseudoLabel=-1)
{
	$nNumFeatureRows = loadListFile($arFeatureList, $szFPInputFN);

	$nNumCommentRows = countNumCommentLines($arFeatureList);

	$nKFIndex=2;
	$arDataOutput = array();
	$arAnnOutput = array();
	printf("Parsing %d rows data ...", $nNumFeatureRows);
	printProgressStart();
	for($i=$nNumCommentRows; $i<$nNumFeatureRows; $i++)
	{
		$szOneRowDvfAnnData = $arFeatureList[$i];

		$arOutput = parseOneRowDvfAnn2FeatureVector($szOneRowDvfAnnData, $nKFIndex);
		$arDataOutput[$nPseudoLabel][]=  convertFeatureVector2LibSVMFormat($arOutput['feature_vector'], $nPseudoLabel);
		$arAnnOutput[$nPseudoLabel][] = $arOutput['keyframe_id'];
		printProgress($i, $nNumFeatureRows, 1000);
	}
	printf("\nFinish parsing %d rows data!", $nNumFeatureRows);

	saveDataFromMem2File($arDataOutput[$nPseudoLabel], $szFPOutputFN, "wt");
	saveDataFromMem2File($arAnnOutput[$nPseudoLabel], $szFPAnnFN, "wt");
}

/**
 * 	(Randomly)Divide one set into several subsets. Use shuffle to pick random data.
 * 	Keys are ignored, only values are considered
 * 	arInputList[i] can be a number or a string
 */
function splitOneSetIntoSubsets(&$arInputList, $nNumSubSets)
{
	$nNumSamples = sizeof($arInputList);

	if(!$nNumSamples)
	{
		$arOutputList = array();

		for($j=0; $j<$nNumSubSets; $j++)
		{
			$arOutputList[$j] = array();
		}
		return;
	}

	$nNumSubSamples = intval($nNumSamples/$nNumSubSets);

	$arOutputList = array();

	$arTmp = $arInputList; // copy data
	for($i=0; $i<$nNumSubSets-1; $i++)
	{
		$arOutputList[$i] = array();

		// do shuffle
		shuffle($arTmp);

		// pick a subset
		for($j=0; $j<$nNumSubSamples; $j++)
		{
			$arOutputList[$i][] = array_pop($arTmp);
		}
	}

	// the last one
	$arOutputList[$nNumSubSets-1] = $arTmp;

	return $arOutputList;
}

/**
 * 	Each feature vector is treated as svf format --> pair (key,value) is important
 * 	Output: arOutput['mean'], arOutput['std']
 */
function computeMeanAndStd(&$arFeatureVectorList)
{
	$nNumSamples = sizeof($arFeatureVectorList);

	if($nNumSamples <= 0)
	{
		terminatePrg("Error - No sample!");
	}

	$nVerbose = 1;
	if($nNumSamples < 1000)
	{
		$nVerbose = 0;
	}

	$arStdFeature = array();
	$arMeanFeature = array();
	$arMeanCount = array();
	if($nVerbose)
	{
		printf("<!--Computing mean and std for %d samples ...", $nNumSamples);
		printProgressStart();
	}
	$arKeys = array_keys($arFeatureVectorList);
	for($i=0; $i<$nNumSamples; $i++)
	{
		$nKey = $arKeys[$i];
		$arFeatureVector = $arFeatureVectorList[$nKey];

		$arDimKeys = array_keys($arFeatureVector);
		$nFeatureSize = sizeof($arDimKeys);

		for($j=0; $j<$nFeatureSize; $j++)
		{
			$nTmpKey = $arDimKeys[$j];

			if(isset($arMeanFeature[$nTmpKey]))
			{
				$arMeanFeature[$nTmpKey] += $arFeatureVector[$nTmpKey];
				$arStdFeature[$nTmpKey] += pow($arFeatureVector[$nTmpKey], 2.0);
				$arMeanCount[$nTmpKey] +=1;
			}
			else
			{
				$arMeanFeature[$nTmpKey] = $arFeatureVector[$nTmpKey];
				$arStdFeature[$nTmpKey] = pow($arFeatureVector[$nTmpKey], 2.0);
				$arMeanCount[$nTmpKey] = 1;
			}
		}

		if($nVerbose)
		{
			printProgress($i, $nNumSamples, 1000);
		}
	}

	$arKeys = array_keys($arMeanFeature);
	$nNumKeys = sizeof($arMeanFeature);

	for($i=0; $i<$nNumKeys; $i++)
	{
		$nKey = $arKeys[$i];

		$arMeanFeature[$nKey] /= 1.0*$arMeanCount[$nKey]; // float val
		$arStdFeature[$nKey] = sqrt($arStdFeature[$nKey]/(1.0*$arMeanCount[$nKey]) - pow($arMeanFeature[$nKey], 2.0));
	}
	if($nVerbose)
	{
		printf("Finish computing mean and std for %d samples!-->\n", $nNumSamples);
	}

	$arOutput = array();
	$arOutput['mean'] = $arMeanFeature;
	$arOutput['std'] = $arStdFeature;

	return $arOutput;
}

/**
 * 	Normalize the input feature vector into zero mean and unit std.
 */
function normalizeDataToZeroMeanAndUnitStd(&$arFeatureVectorList)
{
	$arOutput = computeMeanAndStd($arFeatureVectorList);

	$arMeanFeature = $arOutput['mean'];
	$arStdFeature = $arOutput['std'];

	$nNumSamples = sizeof($arFeatureVectorList);

	if($nNumSamples <= 0)
	{
		terminatePrg("Error - No sample\n");
	}

	printf("<!--Normalizing for %d samples ...", $nNumSamples);
	printProgressStart();
	$arKeys = array_keys($arFeatureVectorList);
	for($i=0; $i<$nNumSamples; $i++)
	{

		$nKey = $arKeys[$i];
		$arFeatureVector = $arFeatureVectorList[$nKey];

		$arDimKeys = array_keys($arFeatureVector);
		$nFeatureSize = sizeof($arDimKeys);

		for($j=0; $j<$nFeatureSize; $j++)
		{
			$nTmpKey = $arDimKeys[$j];

			if(!isset($arMeanFeature[$nTmpKey]) || !isset($arStdFeature[$nTmpKey]))
			{
				terminatePrg("Serious error - Data is not set!");
			}
			else
			{
				if($arStdFeature[$nTmpKey])
				{
					$arFeatureVector[$nTmpKey] = ($arFeatureVector[$nTmpKey] - $arMeanFeature[$nTmpKey])/$arStdFeature[$nTmpKey];
				}
				else
				{
					$arFeatureVector[$nTmpKey] = ($arFeatureVector[$nTmpKey] - $arMeanFeature[$nTmpKey]);
				}
			}
		}
		// update
		$arFeatureVectorList[$nKey] = $arFeatureVector;

		printProgress($i, $nNumSamples, 1000);
	}

	printf("Finish normalizing for %d samples!-->\n", $nNumSamples);

	return $arOutput;
}

/**
 * 	Normalize one feature vector using input mean and std
 * 	Mean and std are feature vectors (key and value are important).
 */
function normalizeOneFeatureVector(&$arFeatureVector, &$arMeanVector, &$arStdVector)
{
	$arKeys = array_keys($arFeatureVector);
	$nNumDims = sizeof($arKeys);
	for($k=0; $k<$nNumDims; $k++)
	{
		$nKey = $arKeys[$k];

		if(!isset($arMeanVector[$nKey]) || !isset($arStdVector[$nKey]))
		{
			terminatePrg("Serious error in mean or std data. Value is not set!");
		}
		else
		{
			if($arStdVector[$nKey])
			{
				$arFeatureVector[$nKey] = ($arFeatureVector[$nKey] - $arMeanVector[$nKey])/$arStdVector[$nKey];
			}
			else
			{
				$arFeatureVector[$nKey] = ($arFeatureVector[$nKey] - $arMeanVector[$nKey]);
			}
		}
	}
}

/**
 * 	Normalize feature vectors in one data file (LibSVM format).
 * 	The input mean and std is stored in one file (LibSVM format)
 */
function normalizeOneDataFile($szFPOutputFN, $szFPInputFN, $szFPMeanStdFN)
{
	$nNumRows = loadListFile($arMeanStd, $szFPMeanStdFN);

	if($nNumRows != 2)
	{
		terminatePrg("Error data format for mean and std!");
	}

	$arTmp = parseOneRowLibSVM2FeatureVector($arMeanStd[0]);
	$arMeanVector = $arTmp['feature_vector']; // ignore arTmp['label']
	$arTmp = parseOneRowLibSVM2FeatureVector($arMeanStd[1]);
	$arStdVector = $arTmp['feature_vector'];

	$nNumSamples = loadListFile($arRawDataList, $szFPInputFN);

	printf("<!--Normalizing %d samples ...", $nNumSamples);
	printProgressStart();

	$arOutput = array();
	for($i=0; $i<$nNumSamples; $i++)
	{
		$arResult = parseOneRowLibSVM2FeatureVector($arRawDataList[$i]);
		normalizeOneFeatureVector($arResult['feature_vector'], $arMeanVector, $arStdVector);
		$arOutput[$i] = convertFeatureVector2LibSVMFormat($arResult['feature_vector'], $arResult['label']);

		$arRawDataList[$i] = NULL;
		unset($arRawDataList[$i]); // free mem

		printProgress($i, $nNumSamples, 1000);
	}
	unset($arRawDataList);
	printf("Finish normalizing %d samples!-->\n", $nNumSamples);

	saveDataFromMem2File($arOutput, $szFPOutputFN, "wt");
}

/**
 * 	Input is feature file and ann file.
 * 	Output are pos file and neg file in LibSVM format.
 * 	This function does ann and feature vector association.
 */
function splitPosNegSamplesFromFeatureAnnData($szFPPosOutputFN, $szFPNegOutputFN, $szFPFeatureInputFN, $szFPAnnFN)
{
	$nPosLabel = 1;
	$nNegLabel = -1;

	// checking whether .ann and .feature file co-exist
	// added Dec 09, 2008
	if(!file_exists($szFPFeatureInputFN) || !file_exists($szFPAnnFN))
	{

		$arAnnOutput = array();
		$arAnnOutput[$nPosLabel] = array();
		$arAnnOutput[$nNegLabel] = array();
		saveDataFromMem2File($arDataOutput[$nPosLabel], $szFPPosOutputFN, "wt");
		saveDataFromMem2File($arDataOutput[$nNegLabel], $szFPNegOutputFN, "wt");

		return $arAnnOutput;
	}

	// end added
	$nNumFeatureRows = loadListFile($arFeatureList, $szFPFeatureInputFN);

	$arAnnList = loadLSCOMAnnData($szFPAnnFN);

	$nNumCommentRows = countNumCommentLines($arFeatureList);

	$nKFIndex=2;
	$arDataOutput = array();

	$arAnnOutput = array();

	printf("<!--Parsing %d rows data ...", $nNumFeatureRows);
	printProgressStart();
	for($i=$nNumCommentRows; $i<$nNumFeatureRows; $i++)
	{
		$szOneRowDvfAnnData = $arFeatureList[$i];

		$arOutput = parseOneRowDvfAnn2FeatureVector($szOneRowDvfAnnData, $nKFIndex);
		$szKeyFrameID = $arOutput['keyframe_id'];  // only pick keyframe_id

		if(isset($arAnnList[$szKeyFrameID]))
		{
			$nLabel = $arAnnList[$szKeyFrameID];
			$arDataOutput[$nLabel][]=  convertFeatureVector2LibSVMFormat($arOutput['feature_vector'], $nLabel);

			$arAnnOutput[$nLabel][] = $szKeyFrameID;
		}
		else
		{
			printf("Keyframe %s has not been labeled!\n", $szKeyFrameID);
		}

		printProgress($i, $nNumFeatureRows);
	}
	printf("Finish parsing %d rows data!-->", $nNumFeatureRows);

	saveDataFromMem2File($arDataOutput[$nPosLabel], $szFPPosOutputFN, "wt");
	saveDataFromMem2File($arDataOutput[$nNegLabel], $szFPNegOutputFN, "wt");

	return $arAnnOutput;
}

/**
 * Parse feature data from Feature Extraction App (each row has feature vector + ann) into 2 files.
 * One file is feature vector file in rsc-dvf format, and the other one is the annotation file.
 * Split into several parts when the number of rows is large.
 */
function parseDataFromKaoriFormat2RSCFormat($szFPInputFN, $szDataOutputDir, $szAnnOutputDir, $szDataName, $nMaxSamplesPerFile=100000)
{
	$nNumRows = loadListFile($arRawList, $szFPInputFN);

	$nNumCommentRows = countNumCommentLines($arRawList);

	$nMaxSamples = $nNumRows - $nNumCommentRows;

	$nNumFiles = intval(($nMaxSamples+$nMaxSamplesPerFile-1)/$nMaxSamplesPerFile);

	for($k=0; $k<$nNumFiles; $k++)
	{
		$arDvfOutputList = array();
		$arAnnOutputList = array();

		$arDvfOutputList[] = $arRawList[0];
		$arAnnOutputList[] = $arRawList[0];

		if($k<$nNumFiles-1)
		{
			$nNumSamples = $nMaxSamplesPerFile;
		}
		else
		{
			$nNumSamples = $nMaxSamples - $nMaxSamplesPerFile*$k;
		}
		$arDvfOutputList[] = $nNumSamples; //$nNumRows - $nNumCommentRows;
		$arAnnOutputList[] = $nNumSamples; //$nNumRows - $nNumCommentRows;

		$szFPDvfOutputFN = sprintf("%s/%s-c0-b%d.dvf", $szDataOutputDir, $szDataName, $k); // k is the block
		$szFPAnnOutputFN = sprintf("%s/%s-c0-b%d.ann", $szAnnOutputDir, $szDataName, $k); // k is the block
		for($i=0; $i<$nNumSamples; $i++)
		{

			$nGlobalIndex = $k*$nMaxSamplesPerFile+$nNumCommentRows + $i;
			$szLine = $arRawList[$nGlobalIndex];

			$arOutput = explode("%", $szLine);
			$szDvfData = $arOutput[0];
			$arDvfOutputList[] = $szDvfData;

			$szAnn = trim($arOutput[1]);
			$arOutput = explode(" ", $szAnn);
			$szAnn = $arOutput[2]; // path to keyframe
			$arAnnOutputList[] = trim($szAnn);

			$arRawList[$nGlobalIndex] = array();  // free for saving memory
		}
		saveDataFromMem2File($arDvfOutputList, $szFPDvfOutputFN, "wt");
		saveDataFromMem2File($arAnnOutputList, $szFPAnnOutputFN, "wt");
	}
}

/**
 * Parse feature data from Feature Extraction App (each row has feature vector + ann) into 2 files.
 * One file is feature vector file in rsc-dvf format, and the other one is the annotation file.
 * Split into several parts when the number of rows is large.
 * This version is improved compared to parseDataFromKaoriFormat2RSCFormat
 * 		+ support multiple chunks and blocks (the previous version only supports one chunk).
 * 		+ allow to select one part or full description of ann ($nAnnIndex = -1 --> take full).
 */

function parseDataFromKaoriFormat2RSCFormatMultipleChunkBlock($szFPInputFN, $szDataOutputDir, $szAnnOutputDir,
		$szDataName,
		$nMaxSamplesPerBlock=50000,
		$nMaxBlocksPerChunk=3, $nAnnIndex=2)
{
	$nNumRows = loadListFile($arRawList, $szFPInputFN);

	$nNumCommentRows = countNumCommentLines($arRawList);

	$nMaxSamples = $nNumRows - $nNumCommentRows;

	$nNumFiles = intval(($nMaxSamples+$nMaxSamplesPerBlock-1)/$nMaxSamplesPerBlock);

	$nNumChunks = intval(($nNumFiles+$nMaxBlocksPerChunk-1)/$nMaxBlocksPerChunk);

	$nGlobalIndex = $nNumCommentRows;

	$nCount = 0;
	for($nChunk=0; $nChunk<$nNumChunks; $nChunk++)
	{
		for($nBlock=0; $nBlock<$nMaxBlocksPerChunk; $nBlock++)
		{
			$arDvfOutputList = array();
			$arAnnOutputList = array();

			$arDvfOutputList[] = $arRawList[0];  // first line - comment line
			$arAnnOutputList[] = $arRawList[0];

			$nNumRemainingSamples = $nMaxSamples - ($nGlobalIndex-$nNumCommentRows);

			$nNumSamples = min($nNumRemainingSamples, $nMaxSamplesPerBlock);

			// second line - total samples
			$arDvfOutputList[] = $nNumSamples; //$nNumRows - $nNumCommentRows;
			$arAnnOutputList[] = $nNumSamples; //$nNumRows - $nNumCommentRows;

			$szFPDvfOutputFN = sprintf("%s/%s-c%d-b%d.dvf", $szDataOutputDir, $szDataName, $nChunk, $nBlock); // k is the block
			$szFPAnnOutputFN = sprintf("%s/%s-c%d-b%d.ann", $szAnnOutputDir, $szDataName, $nChunk, $nBlock); // k is the block

			for($i=0; $i<$nNumSamples; $i++)
			{
				// $nGlobalIndex = $nCount*$nMaxSamplesPerBlock+$nNumCommentRows + $i;
				$szLine = $arRawList[$nGlobalIndex];

				$arOutput = explode("%", $szLine);
				$szDvfData = $arOutput[0];
				$arDvfOutputList[] = $szDvfData;

				$szAnn = trim($arOutput[1]);
				if($nAnnIndex != -1)
				{
					$arOutput = explode(" ", $szAnn);
					$szAnn = trim($arOutput[$nAnnIndex]); // path to keyframe
				}
				$arAnnOutputList[] = $szAnn;

				$arRawList[$nGlobalIndex] = array();  // free for saving memory

				$nGlobalIndex++;
			}

			saveDataFromMem2File($arDvfOutputList, $szFPDvfOutputFN, "wt");
			saveDataFromMem2File($arAnnOutputList, $szFPAnnOutputFN, "wt");

			$nCount++;

			if($nCount>=$nNumFiles)
			{
				break;
			}
		}
		if($nCount>=$nNumFiles)
		{
			break;
		}
	}
}

/**
 * 	$szFPMeanStdOutputFN --> mean and std extracted from input file
 *	$szFPNormOutputFN  --> normalized data
 *
 * 	The memory problem might happen when the input data is large (high dimensional data)
 *
 */
function extractMeanStdAndDoNormalizationForOneDataFile($szFPNormOutputFN, $szFPMeanStdOutputFN, $szFPInputFN)
{
	$nNumSamples = loadListFile($arRawList, $szFPInputFN);

	$arFeatureVectorList = array();
	$arLabelList = array();

	printf("Parsing %d samples ...", $nNumSamples);
	printProgressStart();

	$nGlobalIndex = 0;
	$arOutput = array();
	for($i=0; $i<$nNumSamples; $i++)
	{
		$arOutput = parseOneRowLibSVM2FeatureVector($arRawList[$i]);

		$arLabelList[$nGlobalIndex] = $arOutput['label'];
		$arFeatureVectorList[$nGlobalIndex] = $arOutput['feature_vector'];
		$nGlobalIndex++;

		unset($arRawList[$i]);
		unset($arOutput);
		printProgress($i, $nNumSamples, 1000);
	}
	unset($arRawList); // free mem

	// recalculate
	$nNumSamples = sizeof($arFeatureVectorList);

	printf("Finish parsing %d samples!\n", $nNumSamples);

	$arMeanStd = normalizeDataToZeroMeanAndUnitStd($arFeatureVectorList);

	$arFinalOutput = array();
	printf("Converting %d samples to LibSVM format...", $nNumSamples);
	printProgressStart();
	for($i=0; $i<$nNumSamples; $i++)
	{
		$arFinalOutput[$i] = convertFeatureVector2LibSVMFormat($arFeatureVectorList[$i], $arLabelList[$i]);

		unset($arFeatureVectorList[$i]);
		printProgress($i, $nNumSamples, 1000);
	}
	unset($arFeatureVectorList); // release memory
	printf("Finish converting %d samples to LibSVM format!\n", $nNumSamples);
	saveDataFromMem2File($arFinalOutput, $szFPNormOutputFN, "wt");

	$arMeanStdOutput = array();
	$arMeanStdOutput[] = convertFeatureVector2LibSVMFormat($arMeanStd['mean'], 0);
	$arMeanStdOutput[] = convertFeatureVector2LibSVMFormat($arMeanStd['std'], 0);
	saveDataFromMem2File($arMeanStdOutput, $szFPMeanStdOutputFN, "wt");

	// delete the orig file to save disk space
	//	deleteFile($szFPInputFN);
}

function computeEuclideanDistance2Vector(&$arFeature1, &$arFeature2)
{
	$nNumDims = sizeof($arFeature1);

	$fDist = 0;

	$arKeys = array_keys($arFeature1);

	for($i=0; $i<$nNumDims; $i++)
	{
		$szKey = $arKeys[$i];
		$fTmp = $arFeature1[$szKey] - $arFeature2[$szKey];
		$fDist +=  $fTmp*$fTmp;
	}

	return sqrt($fDist);
}


/**
 * 	Load and parse data into 2 parts: KeyFrameID & FeatureVector
 *
 * 	@param $szFPFeatureFN
 */


function loadOneDvfFeatureFile($szFPFeatureFN, $nKFIndex=2)
{
	$nNumRows = loadListFile($arRowList, $szFPFeatureFN);

	$nNumCommentLines = countNumCommentLines($arRowList);

	$arOutput = array();
	for($i=$nNumCommentLines; $i<$nNumRows; $i++)
	{
		$szRow = $arRowList[$i];
		$arTmp = parseOneRowDvfAnn2FeatureVector($szRow, $nKFIndex);

		$szKeyFrameID = $arTmp['keyframe_id'];

		$arOutput[$szKeyFrameID] = $arTmp['feature_vector'];

	}

	return $arOutput;
}

function loadOneSvfFeatureFile($szFPFeatureFN, $nKFIndex=2)
{
	$nNumRows = loadListFile($arRowList, $szFPFeatureFN);

	$nNumCommentLines = countNumCommentLines($arRowList);

	$arOutput = array();
	for($i=$nNumCommentLines; $i<$nNumRows; $i++)
	{
		$szRow = $arRowList[$i];
		$arTmp = parseOneRowSvfAnn2FeatureVector($szRow, $nKFIndex);

		$szKeyFrameID = $arTmp['keyframe_id'];

		$arOutput[$szKeyFrameID] = $arTmp['feature_vector'];

	}

	return $arOutput;
}


/**
 * 	Load and parse data into 2 parts: KeyFrameID & FeatureVector-Raw (i.e. str, not array!!!!)
 *
 *  $arOutput[$szKeyFrameID] = $szDvfData;
 *
 * 	@param $szFPFeatureFN
 */


function parseOneDvfFeatureFile($szFPFeatureFN, $nKFIndex=2)
{
	$nNumRows = loadListFile($arRowList, $szFPFeatureFN);

	$nNumCommentLines = countNumCommentLines($arRowList);

	$arOutput = array();
	for($i=$nNumCommentLines; $i<$nNumRows; $i++)
	{
		$szRow = $arRowList[$i];
		$arTmp = explode("%", $szRow);

		if(sizeof($arTmp) != 2)
		{
			print_r($arTmp);
			terminatePrg("Ann data does not have annotation part with '%'");
		}

		$szDvfData = trim($arTmp[0]);
		$szAnnotation = trim($arTmp[1]);
		$arTmpx = explode(" ", $szAnnotation);

		if(sizeof($arTmpx) < $nKFIndex)
		{
			print_r($arTmpx);
			terminatePrg("Invalid keyframe index");
		}

		$szKeyFrameID = trim($arTmpx[$nKFIndex]);

		$arOutput[$szKeyFrameID] = $szDvfData;
	}

	return $arOutput;
}

function loadOneTarGZDvfFeatureFile($szLocalDir, $szFPFeatureInputFN, $nKFIndex=2)
{
	$szFPTarFeatureInputFN = sprintf("%s.tar.gz", $szFPFeatureInputFN);

	if(!file_exists($szFPTarFeatureInputFN) || !filesize($szFPTarFeatureInputFN))
	{
		printf("#@@@# File [%s] not found!", $szFPTarFeatureInputFN);
		return false;
	}

	$szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTarFeatureInputFN, $szLocalDir);
	execSysCmd($szCmdLine);

	$szFPLocalFeatureFN = sprintf("%s/%s", $szLocalDir, basename($szFPFeatureInputFN));

	if(!file_exists($szFPLocalFeatureFN))
	{
		printf("#@@@# File [%s] not found!", $szFPLocalFeatureFN);
		return false;
	}
	$arFeatureList = loadOneDvfFeatureFile($szFPLocalFeatureFN, $nKFIndex);

	deleteFile($szFPLocalFeatureFN);

	return $arFeatureList;
}

function loadOneTarGZSvfFeatureFile($szLocalDir, $szFPFeatureInputFN, $nKFIndex=2)
{
	$szFPTarFeatureInputFN = sprintf("%s.tar.gz", $szFPFeatureInputFN);

	if(!file_exists($szFPTarFeatureInputFN) || !filesize($szFPTarFeatureInputFN))
	{
		printf("#@@@# File [%s] not found!", $szFPTarFeatureInputFN);
		return false;
	}

	$szCmdLine = sprintf("tar -xvf %s -C %s", $szFPTarFeatureInputFN, $szLocalDir);
	execSysCmd($szCmdLine);

	$szFPLocalFeatureFN = sprintf("%s/%s", $szLocalDir, basename($szFPFeatureInputFN));

	if(!file_exists($szFPLocalFeatureFN))
	{
		printf("#@@@# File [%s] not found!", $szFPLocalFeatureFN);
		return false;
	}
	$arFeatureList = loadOneSvfFeatureFile($szFPLocalFeatureFN, $nKFIndex);

	deleteFile($szFPLocalFeatureFN);

	return $arFeatureList;
}

?>