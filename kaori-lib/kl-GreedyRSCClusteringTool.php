<?php

/**
 * 		Tools for clustering.
 *
 * 		Copyright (C) 2011 Duy-Dinh Le.
 * 		All rights reserved.
 * 		Email		: ledduy@gmail.com, ledduy@ieee.org.
 * 		Version		: 1.0.
 * 		Last update	: 23 Nov 2011.
 */

require_once "kl-AppConfig.php";
require_once "kl-IOTools.php";
require_once "kl-MiscTools.php";


// Global variables
$gszMaxClusterSize = 100000; // maximum cluster size. 
$gszMaxChunks = 20;
$gszMaxBlocksPerChunk = 5;
$gszMaxSamplesPerBlock = 50000;


/*
 *
 */

$arGreedyRSCEnv = array();

/*
 *  path to applications
 */
$arGreedyRSCEnv["app_path"] = "";

/*
 * $fileNameExt is the file type for the input data (‘cvf’, ‘dvf’, or ‘svf’, see below);
 */

$arGreedyRSCEnv["fileNameExt"] = "dvf";

/*
 * $loadFlag is 1 if previously-saved temporary files are to be loaded and reused whenever they might exist,
 * and 0 if temporary files are not to be reused;
 */
$arGreedyRSCEnv["loadFlag"] = 1;

/*
 * $saveFlag is 1 if temporary files are to be retained after program execution ends,
 * and 0 if temporary files are not to be retained;
 */
$arGreedyRSCEnv["saveFlag"] = 1;

/*
 * $sashDeg is the internal node degree parameter for the SASH approximate search structure
 * (recommended value: 4);
 */
$arGreedyRSCEnv["sashDeg"] = 4;

/*
 * $scaleFactor is the time-accuracy trade-off parameter for the SASH approximate search structure
 * (recommended value: 1.5);
 * 0 is exact search
 * SPEED RELATED
 */
$arGreedyRSCEnv["scaleFactor"] = 0;

/*
 * $minCluSig is the minimum threshold on the normalized squared Z-statistic (significance) score for the cluster
 * - for a perfectly well-associated cluster, this number would equal the cluster size
 * (recommended value: 4.0);
 * ACCURACY RELATED
 */
$arGreedyRSCEnv["minCluSig"] = 4;

/*
 * $minMemSig is the minimum threshold on the individual set correlation between a cluster and
 * each of its members, also used as a minimum limit on the square of the average set correlation
 * between a cluster and its members
 * (recommended values: 0.1, or 0.15 when main memory is limited);
 * lower value --> noisier cluster
 * ACCURACY RELATED
 */
$arGreedyRSCEnv["minMemSig"] = 0.1;

/*
 * $maxEdgeSig is the maximum inter-cluster correlation value for two clusters to be declared distinct
 * (recommended value: 0.5);
 * Increasing the value of parameter <maxEdgSig> also results in the generation of a larger number of clusters.
 */
$arGreedyRSCEnv["maxEdgeSig"] = 0.5;

/*
 * $minEdgeSig is the minimum inter-cluster correlation value necessary for two clusters to be declared related
 * (recommended value: 0.1);
 * controls only the reporting of relationships between pairs of clusters
 * – it has no effect on the cluster generation process itself.
 */
$arGreedyRSCEnv["minEdgeSig"] = 0.1;

/*
 * $seed is the seed value for random number generation.
 *
 */
$arGreedyRSCEnv["seed"] = 1234;

/*
 * $annotType is the file type for the input data (either ‘img’ for images, ‘html’ for a link in HTML format,
 * or ‘txt’ for plain txt);
 */
$arGreedyRSCEnv["annotType"] = "img";

/*
 * $annotFlag is 1 if annotations have been provided in ANN files,
 * and 0 if default item labels should be used;
 */
$arGreedyRSCEnv["annotFlag"] = 1;

$arGreedyRSCEnv["clustering_app"] = "GreedyRSCClustering";
$arGreedyRSCEnv["partition_app"] = "GreedyRSCPartition";
$arGreedyRSCEnv["annotator_app"] = "GreedyRSCAnnotator";
$arGreedyRSCEnv["sash_tester_app"] = "GreedyRSCSashTester";

//////////////////////////////////////////////////////////////////////

/**
 * The input file contains member information of each cluster.
 * One row is for one cluster.
 * Output: arRSCMemList[k] --> list of samples belonging to cluster k
 * Filter clusters whose size is larger than nMaxSize (i.e. highly noisy cluster).
 */
function loadMemFile(&$arRSCMemList, $szDataName, $szInputDir, $nMaxSize=-1, $nNumChunks=-1, $nNumBlocksPerChunk=-1)
{
	global $gszMaxClusterSize;  
	global $gszMaxChunks;
	global $gszMaxBlocksPerChunk;

	$nGlobalClusterID = 0; // global == all chunks and blocks
	$nStopFlag = 0;

	printf("<!--Loading clusters and their members from data file %s ...[", $szDataName);
	$nTotalMembers = 0;

	$nTotalChunks = 0;
	$nTotalBlocks = 0;

	if($nNumChunks < 0)
	{
		$nNumChunks = $gszMaxChunks; // default value
	}

	if($nNumBlocksPerChunk < 0)
	{
		$nNumBlocksPerChunk = $gszMaxBlocksPerChunk; // default value
	}

	if($nMaxSize < 0)
	{
		$nMaxSize = $gszMaxClusterSize; // Max value --> View all, no restriction
	}
	for($nChunk=0; $nChunk<$nNumChunks; $nChunk++)
	{
		$nPrevNumBlocks = $nTotalBlocks;
		for($nBlock=0; $nBlock<$nNumBlocksPerChunk; $nBlock++)
		{
			$szFPInputFN = sprintf("%s/%s-clustblk-c%d-b%d.mem", $szInputDir, $szDataName, $nChunk, $nBlock);

			if(!file_exists($szFPInputFN))
			{
				printf("File [%s] does not exist!\n", $szFPInputFN);
				continue;
			}

			$nTotalBlocks++;

			$hInput = fopen($szFPInputFN, "rt");
			$szCommentLine = fgets($hInput);

			$szLine = trim(fgets($hInput));
			$szOutput = explode(" ", $szLine);
			if(sizeof($szOutput)!= 2 || trim($szOutput[1]) != -5)
			{
				terminatePrg("Formating error [{$szLine}]");
			}

			$nNumClusters = trim($szOutput[0]);
			// print $nNumClusters;
			for($i=0; $i<$nNumClusters; $i++)
			{
				// Each cluster membership list is of the form
				// <offset> <len> <mem1> <overlaps1> . . . <mem<fsz>> <overlaps<fsz>>
				$szLine = fgets($hInput);
				$szOutput = explode(" ", $szLine);

				$nOffset = trim($szOutput[0]);
				$nLen = trim($szOutput[1]);

				if(sizeof($szOutput) < 2*$nLen+2)
				{
					printf("Number of val: [%s]. Len: [%s]\n", sizeof($szOutput), $nLen );
					terminatePrg("Formating error [{$szLine}]");
				}

				if($nLen == 0)
				{
					$nStopFlag = 1; // remaining is all zero --> STOP
					break;
				}
				else
				{
					if ($nLen>$nMaxSize)
					{
						$arRSCMemList[$nGlobalClusterID] = array();  // to keep global index consistent
					}
					else
					{
						$nTotalMembers += $nLen;
						$arCurCluster  = array();
						for($j=0; $j<$nLen; $j++)
						{
							$arCurCluster[$j] = trim($szOutput[2+$j*2]);
						}
						//$nSize = sizeof($arCurCluster);
						//print "{$nGlobalClusterID} - {$nSize}\n";
							
						$arRSCMemList[$nGlobalClusterID] = $arCurCluster;
					}
					$nGlobalClusterID++;
					if($nGlobalClusterID % 1000 == 0)
					{
						print ".";
					}
				}
			}
			fclose($hInput);
		}

		if($nTotalBlocks > $nPrevNumBlocks)
		{
			$nTotalChunks++;
		}
	}

	$nTotalClusters = sizeof($arRSCMemList);
	printf(".]. Finish!-->\n");
	$szLogStr = sprintf("<!--%s clusters and %s members are loaded. Num chunks: %s. Num blocks: %s!-->\n", $nTotalClusters, $nTotalMembers, $nTotalChunks, $nTotalBlocks);
	//	doLogging($szLogStr);
	printf($szLogStr);
	return $nTotalClusters;
}

/**
 * The input file contains cluster information of each sample.
 * One row is for one sample.
 * Output: arRSCInvMemList[k] --> list of clusters containing member k.
 */
function loadInvMemFile(&$arRSCInvMemList, $szDataName, $szInputDir, $nMaxSize=1000, $nNumChunks=-1, $nNumBlocksPerChunk=-1)
{
	global $gszMaxClusterSize;  
	global $gszMaxChunks;
	global $gszMaxBlocksPerChunk;

	$nGlobalSampleID = 0; // global == all chunks and blocks
	$nNumOrphans = 0;

	printf("<!-- Loading samples and their belonged clusters from data file %s ...[", $szDataName);
	$nTotalChunks = 0;
	$nTotalBlocks = 0;

	if($nNumChunks < 0)
	{
		$nNumChunks = $gszMaxChunks; // default value
	}

	if($nNumBlocksPerChunk < 0)
	{
		$nNumBlocksPerChunk = $gszMaxBlocksPerChunk; // default value
	}

	for($nChunk=0; $nChunk<$nNumChunks; $nChunk++)
	{
		$nPrevNumBlocks = $nTotalBlocks;
		for($nBlock=0; $nBlock<$nNumBlocksPerChunk; $nBlock++)
		{
			$szFPInputFN = sprintf("%s/%s-clustblk-c%d-b%d.imem", $szInputDir, $szDataName, $nChunk, $nBlock);
				
				
			if(!file_exists($szFPInputFN))
			{
				printf("File [%s] does not exist!\n", $szFPInputFN);
				continue;
			}
				

			$nTotalBlocks++;

			$hInput = fopen($szFPInputFN, "rt");
			$szCommentLine = fgets($hInput);
			// print $szCommentLine;

			$szLine = trim(fgets($hInput));
			$szOutput = explode(" ", $szLine);
			if(sizeof($szOutput)!= 2 || trim($szOutput[1]) != -5)
			{
				terminatePrg("Formating error [{$szLine}]");
			}

			$nNumSamples = trim($szOutput[0]);
			for($i=0; $i<$nNumSamples; $i++)
			{
				// Each cluster membership inverted list is of the form
				// <offset> <len> <clust1> <rank1> . . . <clust<fsz>> <rank<fsz>>
				$szLine = fgets($hInput);
				$szOutput = explode(" ", $szLine);

				$nOffset = trim($szOutput[0]);
				$nLen = trim($szOutput[1]);

				if(sizeof($szOutput) != 2*$nLen+2)
				{
					printf("Number of val: [%s]. Len: [%s]\n", sizeof($szOutput), $nLen );
					terminatePrg("Formating error [{$szLine}]");
				}

				if($nLen == 0)
				{
					$nNumOrphans++; // sample does not belong to any cluster
				}
				$arCurSample  = array();
				for($j=0; $j<$nLen; $j++)
				{
					$arCurSample[$j] = trim($szOutput[2+$j*2]);
				}
					
				$arRSCInvMemList[$nGlobalSampleID] = $arCurSample;
				$nGlobalSampleID++;
				if($nGlobalSampleID % 10000 == 0)
				{
					print ".";
				}
			}
			fclose($hInput);
		}
		if($nTotalBlocks > $nPrevNumBlocks)
		{
			$nTotalChunks++;
		}
	}

	$nTotalSamples = sizeof($arRSCInvMemList);
	printf(".]. Finish!-->\n");

	$szLogStr = sprintf("<!--%s samples are loaded.
							Num orphans: %s. Num chunks: %s. Num blocks: %s!-->\n",
	$nTotalSamples, $nNumOrphans, $nTotalChunks, $nTotalBlocks);
	//	doLogging($szLogStr);
	printf($szLogStr);

	return $nTotalSamples;
}

/**
 * 	Load data file including dvf, svf and ann.
 * 	If the original data has many chunks, all will be merged in one output array.
 * 	GlobalSampleID is used to specify the ID after merging
 * 
 * 	$arDataList[$nGlobalSampleID]
 */
function loadDataFile(&$arDataList, $szDataName, $szDataExt, $szInputDir, $nNumChunks=-1, $nNumBlocksPerChunk=-1)
{
	global $gszMaxClusterSize;  
	global $gszMaxChunks;
	global $gszMaxBlocksPerChunk;

	$nGlobalSampleID = 0; // global == all chunks and blocks
	$nNumOrphans = 0;

	if($nNumChunks < 0)
	{
		$nNumChunks = $gszMaxChunks; // default value
	}

	if($nNumBlocksPerChunk < 0)
	{
		$nNumBlocksPerChunk = $gszMaxBlocksPerChunk; // default value
	}
	$nTotalChunks = 0;
	$nTotalBlocks = 0;

	printf("<!--Loading data file %s ...[", $szDataName);
	for($nChunk=0; $nChunk<$nNumChunks; $nChunk++)
	{
		$nPrevNumBlocks = $nTotalBlocks;
		for($nBlock=0; $nBlock<$nNumBlocksPerChunk; $nBlock++)
		{
			$szFPInputFN = sprintf("%s/%s-c%d-b%d.%s", $szInputDir, $szDataName, $nChunk, $nBlock, $szDataExt);
			//			print $szFPInputFN;

			if(!file_exists($szFPInputFN))
			{
				printf("File [%s] does not exist!\n", $szFPInputFN);
				continue;
			}
			//			$szChunkBlock = sprintf("c%d-b%d", $nChunk, $nBlock);
			//			loadListFile($arDataList[$szChunkBlock], $szFPInputFN);

			$nTotalBlocks++;
			$hInput = fopen($szFPInputFN, "rt");
			$szCommentLine = fgets($hInput);

			$szLine = trim(fgets($hInput));
			$szOutput = explode(" ", $szLine);

			$nNumSamples = trim($szOutput[0]);
			for($i=0; $i<$nNumSamples; $i++)
			{

				$szLine = fgets($hInput);
				$nLen = trim($szOutput[0]);
				if($szDataExt == "dvf") // dense vector format
				{
					$arDataList[$nGlobalSampleID] = trim($szLine);  // NOT ENOUGH MEMORY TO PARSE INPUT LINES
					$nGlobalSampleID++;
					if($nGlobalSampleID % 10000 == 0)
					print ".";
				}
				else
				{
					if($szDataExt=="svf") // sparse vector format
					{
						$arDataList[$nGlobalSampleID] = trim($szLine);  // NOT ENOUGH MEMORY TO PARSE INPUT LINES
						$nGlobalSampleID++;
						if($nGlobalSampleID % 1000 == 0)
						print ".";
					}
					else
					{
						if($szDataExt=="ann") 
						{
							$arDataList[$nGlobalSampleID] = trim($szLine);  
							$nGlobalSampleID++;
							if($nGlobalSampleID % 1000 == 0)
							print ".";
						}
					}
				}
			}
			fclose($hInput);
		}
		if($nTotalBlocks > $nPrevNumBlocks)
		{
			$nTotalChunks++;
		}
	}

	$nTotalSamples = sizeof($arDataList);

	printf(".]. Finish!-->\n");

	$szLogStr = sprintf("<!--%s samples are loaded!. Num chunks: %s. Num blocks: %s!-->\n",
	$nTotalSamples, $nTotalChunks, $nTotalBlocks);
	//	doLogging($szLogStr);
	printf($szLogStr);

	return $nTotalSamples;
}


/**
 * $nMinClusSize controls the min cluster size
 */
function runGreedyRSCClustering($szDataName, $szInputDirPath, $szTmpDirPath, $szOutputDirPath, $nMinClusSize)
{
	global $arGreedyRSCEnv;
	/*
	 Usage:
	 GreedyRSC <dataFileName> <fileNameExt> <inputDirPath> <tempDirPath> <outputDirPath>
	 <loadFlag> <saveFlag> <sashDeg> <scaleFactor>
	 <minCluSig> <minMemSig> <maxEdgeSig> <minEdgeSig> <seed>
	 */

	$szGreedyRSCClusteringApp = sprintf("%s/%s", $arGreedyRSCEnv["app_path"], $arGreedyRSCEnv["clustering_app"]);

	$fileNameExt = $arGreedyRSCEnv["fileNameExt"];
	$loadFlag = $arGreedyRSCEnv["loadFlag"];
	$saveFlag = $arGreedyRSCEnv["saveFlag"];
	$sashDeg = $arGreedyRSCEnv["sashDeg"];
	$scaleFactor = $arGreedyRSCEnv["scaleFactor"];

	$arGreedyRSCEnv["minCluSig"] = $nMinClusSize;
	$minCluSig = $arGreedyRSCEnv["minCluSig"];

	$minMemSig = $arGreedyRSCEnv["minMemSig"];
	$maxEdgeSig = $arGreedyRSCEnv["maxEdgeSig"];
	$minEdgeSig = $arGreedyRSCEnv["minEdgeSig"];
	$seed = $arGreedyRSCEnv["seed"];

	$szCmdLine = sprintf("%s %s %s %s %s %s %f %f %f %f %f %f %f %f %f",
	$szGreedyRSCClusteringApp, $szDataName, $fileNameExt, $szInputDirPath, $szTmpDirPath, $szOutputDirPath,
	$loadFlag, $saveFlag, $sashDeg, $scaleFactor,
	$minCluSig, $minMemSig, $maxEdgeSig, $minEdgeSig, $seed);

	printf("%s\n", $szCmdLine);
	doLogging($szCmdLine);

	// logging more details
	doLoggingArray($arGreedyRSCEnv);

	system($szCmdLine);
}

/**
 * $nMinClusSize controls the min cluster size.
 * $fPartitionThreshold controls the cluster precision.
 */

function runGreedyRSCPartition($szDataName, $szClusDirPath, $szOutputDirPath, $nMinClusSize, $fPartitionThreshold)
{
	global $arGreedyRSCEnv;

	$szGreedyRSCPartitionApp = sprintf("%s/%s", $arGreedyRSCEnv["app_path"], $arGreedyRSCEnv["partition_app"]);

	/*
		Usage:
		GreedyRSCPartition <fileNamePrefix> <clustDirPath> <partitDirPath> <minClustSize> <partition_threshold>
		*/

	$fileNameExt = $arGreedyRSCEnv["fileNameExt"];

	$arGreedyRSCEnv["minCluSig"] = $nMinClusSize;
	$minCluSig = $arGreedyRSCEnv["minCluSig"];

	// partition threshold --> multiple by 10,000. e.g 0.7 --> 7,000
	
	$szCmdLine = sprintf("%s %s %s %s %f %f",
	$szGreedyRSCPartitionApp, $szDataName, $szClusDirPath, $szOutputDirPath, $nMinClusSize, $fPartitionThreshold*10000);
	printf("Command: [%s]\n", $szCmdLine);

	doLogging($szCmdLine);

	// logging more details
	doLoggingArray($arGreedyRSCEnv);

	system($szCmdLine);
}

/**
 * $szRelPath controls the path to find keyframes.
 */

function runAnnotator($szDataName, $szAnnDirPath, $szClusDirPath, $szOutputDirPath, $szRelPath, $szFileExt)
{
	global $arGreedyRSCEnv;

	$szGreedyRSCAnnotatorApp = sprintf("%s/%s", $arGreedyRSCEnv["app_path"], $arGreedyRSCEnv["annotator_app"]);

	$annotType = $arGreedyRSCEnv["annotType"];
	$annotFlag = $arGreedyRSCEnv["annotFlag"];
	$seed = $arGreedyRSCEnv["seed"];

	$szCmdLine = sprintf("%s %s %s %s %s %s %d %d %s %s",
	$szGreedyRSCAnnotatorApp , $szDataName,
	$annotType, $szAnnDirPath, $szClusDirPath, $szOutputDirPath,
	$annotFlag, $seed, $szRelPath, $szFileExt);
	
	printf("Command: [%s]\n", $szCmdLine);

	doLogging($szCmdLine);

	// logging more details
	doLoggingArray($arGreedyRSCEnv);

	system($szCmdLine);
}

function runSashTester($szDataName, $szInputDirPath, $szOutputDirPath, $fStartScaleFactor=0.125, $nNumQueries = 100, $nNumRepeats = 10)
{
	global $arGreedyRSCEnv;

	/*
	  Usage:
  		SashTester <fileNamePrefix> <fileNameExt>
             <inputDirPath> <outputDirPath>
             <sashDeg> <scaleFactor> <numQueries>
             <trialsPerQuery> <seed>
	 */

	$szGreedyRSCSashTesterApp = sprintf("%s/%s", $arGreedyRSCEnv["app_path"], $arGreedyRSCEnv["sash_tester_app"]);

	$fileNameExt = $arGreedyRSCEnv["fileNameExt"];
	$sashDeg = $arGreedyRSCEnv["sashDeg"];
	$scaleFactor = $fStartScaleFactor;
	$seed = $arGreedyRSCEnv["seed"];

	$szCmdLine = sprintf("%s %s %s %s %s %f %f %n %n %n",
		$szGreedyRSCSashTesterApp, $szDataName, $fileNameExt, $szInputDirPath, $szOutputDirPath,
		$sashDeg, $scaleFactor,
		$nNumQueries, $nNumRepeats, $seed);

	printf("Command: [%s]\n", $szCmdLine);

	doLogging($szCmdLine);

	// logging more details
	doLoggingArray($arGreedyRSCEnv);

	system($szCmdLine);
}


/**
 *	Combine all steps for clustering such as soft clustering, hard clustering (partitioning) in one  
 * 
 */
function doClusteringAll($szRootOutputDir, $szDataName, $szRelPath, $nMinClusSize, $fPartitionThreshold)
{
	$szInputDataDirPath = sprintf("%s/data", $szRootOutputDir); // data dir contains dvf and ann files

	$szInputTmpDirPath = sprintf("%s/tmp", $szRootOutputDir); // tmp dir stores tmp files in clustering process
	makeDir($szInputTmpDirPath);

	$szOutputClusDirPath = sprintf("%s/clus", $szRootOutputDir); // clus dir stores clustering result (soft clustering)
	makeDir($szOutputClusDirPath);

	runGreedyRSCClustering($szDataName, $szInputDataDirPath, $szInputTmpDirPath, $szOutputClusDirPath, $nMinClusSize);

	// NEW!!! - combine partition threshold with dir name
	$szInputClusDirPath = $szOutputClusDirPath; // get result from the prev step

	$szOutputPartDirPath = sprintf("%s/part-%s", $szRootOutputDir, $fPartitionThreshold); // part dir stores clustering result (hard clustering)
	makeDir($szOutputPartDirPath);

	runGreedyRSCPartition($szDataName, $szInputClusDirPath, $szOutputPartDirPath, $nMinClusSize, $fPartitionThreshold);

	$szInputAnnDirPath = $szInputDataDirPath; // ann and dvf files are located at the same dir
	$szInputPartDirPath = $szOutputPartDirPath; // get result from the prev step
	$szOutputHTMLDirPath = sprintf("%s/ann-%s", $szRootOutputDir, $fPartitionThreshold);
	makeDir($szOutputHTMLDirPath);

}


/**
 * $arClusterHist[k]  --> number of clusters having k members
 */
function computeClusterHistogram(&$arRSCMemList)
{
	$arClusterHist = array();

	$nNumClusters = sizeof($arRSCMemList);

	for($i=0; $i<$nNumClusters; $i++)
	{
		$nNumFaceTracks = sizeof($arRSCMemList[$i]);

		if(isset($arClusterHist[$nNumFaceTracks]))
		{
			$arClusterHist[$nNumFaceTracks]++;
		}
		else
		{
			$arClusterHist[$nNumFaceTracks] = 1;
		}
	}

	return $arClusterHist;
}

/**
 * View clustering result.
 * One row is one cluster.
 * A subset of members is viewed.
 */

function viewMembersOfOneClusterFromMem(&$arCluster, &$arAnnList, $nClusterID,
$szRelPath, $szVideoPath, $szImgExt, $szViewClusterMemberScript,
$szAddParams="", $nImgWidth=80, $nNumSampleMembersPerCluster=-1)
{
	$nNumMembers = sizeof($arCluster);

	if($nNumSampleMembersPerCluster < 0)
	{
		$nNumSampleMembersPerCluster = $nNumMembers;
	}

	$nInterval = max(1, $nNumMembers/$nNumSampleMembersPerCluster);

	$nNumSamplesToShow = min($nNumMembers, $nNumSampleMembersPerCluster);

	$arOutput = array();

	$nSampleIndex = 0;

	// cluster size
	$arOutput[$nSampleIndex] = sprintf("%s", $nNumMembers);
	$nSampleIndex++;

	for($j=0; $j<$nNumSamplesToShow; $j++)
	{
		$nMemberID = $arCluster[$j*$nInterval];
		$szImgName = $arAnnList[$nMemberID];
		$szImgURL = sprintf("%s/%s%s", $szRelPath, $szImgName, $szImgExt);
		$szParams = sprintf("vVideoPath=%s&vRelPath=%s&vImgName=%s&vClusterID=%s&vMemberID=%s",
		$szVideoPath, $szRelPath, $szImgName, $nClusterID, $nMemberID);
		$szLinkURL = sprintf("%s?%s&%s", $szViewClusterMemberScript, $szParams, $szAddParams);
		$arOutput[$nSampleIndex] = sprintf("<A HREF='%s' target='View Cluster Member'><IMG SRC= '%s' WIDTH='%s' TITLE='%s'></A>", $szLinkURL, $szImgURL, $nImgWidth, $szImgName);
		$nSampleIndex++;

		if(($j+1) % 10 == 0)
		{
			$arOutput[$nSampleIndex] = "<BR>";
			$nSampleIndex++;
		}
	}
	return $arOutput;
}


// NEW
// In partion result, cluster size (after filtering) 100, but max SNN is only 10. 
// $fMinRelSNNSize = 10/100 --> control the cluster quality <-- $fRelSNNSizezz = 1.0*$nMaxSNN/($nClusterSizezz+1); // +1 to avoid 0
// $fMinSNN --> minimum SNN
// $fScore = 1.0*$nNumSNN/$nMaxSNN --> used for comparison with fMemThreshold

// Some items of the cluster might be eliminated due to the threshold. I.e, size of the cluster before filtering might be different after clustering.

function loadMemFileWithThreshold(&$arRSCMemList, $szDataName, $szInputDir,
$nMaxSize=-1, $nNumChunks=-1, $nNumBlocksPerChunk=-1, $fMemThreshold=0.1, $fMinSNN = 4, $fMinRelSNNSize=0.25)
{
	global $gszMaxClusterSize;  
	global $gszMaxChunks;
	global $gszMaxBlocksPerChunk;

	$nGlobalClusterID = 0; // global == all chunks and blocks
	$nStopFlag = 0;

	printf("<!--Loading clusters and their members from data file %s ...[", $szDataName);
	$nTotalMembers = 0;

	$nTotalChunks = 0;
	$nTotalBlocks = 0;

	if($nNumChunks < 0)
	{
		$nNumChunks = $gszMaxChunks; // default value
	}

	if($nNumBlocksPerChunk < 0)
	{
		$nNumBlocksPerChunk = $gszMaxBlocksPerChunk; // default value
	}

	if($nMaxSize < 0)
	{
		$nMaxSize = $gszMaxClusterSize; // Max value --> View all, no restriction
	}
	for($nChunk=0; $nChunk<$nNumChunks; $nChunk++)
	{
		$nPrevNumBlocks = $nTotalBlocks;
		for($nBlock=0; $nBlock<$nNumBlocksPerChunk; $nBlock++)
		{
			$szFPInputFN = sprintf("%s/%s-clustblk-c%d-b%d.mem", $szInputDir, $szDataName, $nChunk, $nBlock);

			if(!file_exists($szFPInputFN))
			{
				printf("File [%s] does not exist!\n", $szFPInputFN);
				continue;
			}

			$nTotalBlocks++;

			$hInput = fopen($szFPInputFN, "rt");
			$szCommentLine = fgets($hInput);

			$szLine = trim(fgets($hInput));
			$szOutput = explode(" ", $szLine);
			if(sizeof($szOutput)!= 2 || trim($szOutput[1]) != -5)
			{
				terminatePrg("Formating error [{$szLine}]");
			}

			$nNumClusters = trim($szOutput[0]);
			// print $nNumClusters;
			for($i=0; $i<$nNumClusters; $i++)
			{
				// Each cluster membership list is of the form
				// <offset> <len> <mem1> <overlaps1> . . . <mem<fsz>> <overlaps<fsz>>
				$szLine = fgets($hInput);
				$szOutput = explode(" ", $szLine);

				$nOffset = trim($szOutput[0]);
				$nLen = trim($szOutput[1]);

				if(sizeof($szOutput) != 2*$nLen+2)
				{
					printf("Number of val: [%s]. Len: [%s]\n", sizeof($szOutput), $nLen );
					terminatePrg("Formating error [{$szLine}]");
				}

				if($nLen == 0)
				{
					$nStopFlag = 1; // remaining is all zero --> STOP
					break;
				}
				else
				{
					if ($nLen>$nMaxSize)
					{
						$arRSCMemList[$nGlobalClusterID] = array();  // to keep global index consistent
					}
					else
					{
						$arCurCluster  = array();
						$nMaxSNN = 0;
						for($j=0; $j<$nLen; $j++)
						{

							$szMemID = trim($szOutput[2+$j*2]);
							$nNumSNN = -intval(trim($szOutput[2+$j*2+1]));

							if($nMaxSNN == 0)
							{
								$nMaxSNN = $nNumSNN;  // the first one is also the max one
							}
							else
							{
								$fScore = 1.0*$nNumSNN/$nMaxSNN;
								if($fScore < $fMemThreshold || $nNumSNN < $fMinSNN)
								{
									continue;
								}
							}

							$arCurCluster[$j] = $szMemID;
							$nTotalMembers++;
						}
						//$nSize = sizeof($arCurCluster);
						//print "{$nGlobalClusterID} - {$nSize}\n";

						
						$nClusterSizezz = sizeof($arCurCluster);
						$fRelSNNSizezz = 1.0*$nMaxSNN/($nClusterSizezz+1); // +1 to avoid 0
						if($fRelSNNSizezz < $fMinRelSNNSize)
						{
							$arCurCluster = array(); // reset
						}
						$arRSCMemList[$nGlobalClusterID] = $arCurCluster;
					}
					$nGlobalClusterID++;
					if($nGlobalClusterID % 1000 == 0)
					{
						print ".";
					}
				}
			}
			fclose($hInput);
		}

		if($nTotalBlocks > $nPrevNumBlocks)
		{
			$nTotalChunks++;
		}
	}

	$nTotalClusters = sizeof($arRSCMemList);
	printf(".]. Finish!-->\n");
	$szLogStr = sprintf("<!--%s clusters and %s members are loaded. Num chunks: %s. Num blocks: %s!-->\n", $nTotalClusters, $nTotalMembers, $nTotalChunks, $nTotalBlocks);
	//	doLogging($szLogStr);
	printf($szLogStr);
	return $nTotalClusters;
}

// NEW 
// Load clustering results with filtering and late fusion of features 
function loadClusterData(&$arRSCMemList, &$arAnnList, $arFeatureList,
$szOrigDataName, $szOrigClusterDir, $szOrigDataDir, $fPartThreshold, 
$nNumChunks, $nNumBlocksPerChunk, $nMaxSize, $fMemThreshold, $fMinSNN, $fMinRelSNNSize)
{
	$nNumFeatures = sizeof($arFeatureList);
	
//	print_r($arFeatureList);

	$arRSCMemList= array();
	$arAnnList = array();

	$nGlobalCluserIndex = 0;
	$nGlobalAnnIndex = 0;
	for($i=0; $i<$nNumFeatures; $i++)
	{
		$szFeatureExt = $arFeatureList[$i];
		$szClusterDir = sprintf("%s/baseline.%s/part-%s", $szOrigClusterDir, $szFeatureExt, $fPartThreshold);
		$szDataDir = sprintf("%s/baseline.%s/data", $szOrigDataDir, $szFeatureExt);
		$szDataName = sprintf("%s.baseline.%s", $szOrigDataName, $szFeatureExt);

		$arRSCMemListTmp = array();
		$arAnnListTmp = array();
		loadMemFileWithThreshold($arRSCMemListTmp, $szDataName, $szClusterDir,
		$nMaxSize, $nNumChunks, $nNumBlocksPerChunk, $fMemThreshold, 
		$fMinSNN, $fMinRelSNNSize);
		loadDataFile($arAnnListTmp, $szDataName, "ann", $szDataDir, $nNumChunks, $nNumBlocksPerChunk);

		// merging
		$nNumClusters = sizeof($arRSCMemListTmp);
		$arKeys = array_keys($arRSCMemListTmp);
		for($j=0; $j<$nNumClusters; $j++)
		{
			$szKey = $arKeys[$j];
			
			// use key as string value
			$szIndex = sprintf("Clus-%06d", $nGlobalCluserIndex);
			$arRSCMemList[$szIndex] = $arRSCMemListTmp[$szKey];
			
			// printf("<!--%s - %s-->\n", $nGlobalCluserIndex, sizeof($arRSCMemListTmp[$szKey]));
			$nGlobalCluserIndex++;
		}

	}

	// share the same ann
	$arAnnList = $arAnnListTmp;
	
//	print_r(array_keys($arRSCMemList));
//	exit();

}

/*
 The SASH parameters <sashDeg> and <scaleFactor> together control the time-accuracy trade-off
 for the relevant set generation phase of the clustering process.

 For most data sets, a default fixed value of <sashDeg>=4 is recommended,
 with <scaleFactor> being set to between 1.0 (faster, lower accuracy) and 2.0 (slower, higher accuracy).

 However, for protein sequence clustering where data vector coordinates are based on BLAST E-values
 with a reference subset of sequences, better performance was achieved with a
 higher SASH degree of <sashDeg>=16 and a corresponding lower scale factor of <scaleFactor>=0.125.

 As a general recommendation, the average SASH recall accuracy should be at least 60% when searching
 for 120 neighbors of query items drawn from the data set (“query-by-example”) – higher accuracies are
 of course preferable!

 Users should consider using the SashTester utility program to explicitly test the
 SASH performance beforehand if accuracy or running time is of particular concern.

 The parameters <minCluSig> and <minMemSig> strongly influence the number of candidates generated
 in the course of the clustering, as well as the final number of clusters produced. Setting the
 parameters to values lower than those recommended above can lead to a blowout in main-memory usage
 when clustering large data sets.

 Increasing the value of parameter <maxEdgSig> also results in the generation of a larger number of clusters.

 Parameter <minEdgSig> controls only the reporting of relationships between pairs of clusters
 – it has no effect on the cluster generation process itself.
 */
?>
