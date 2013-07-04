************ TAKE HOME MESSAGES ***************
1. Copy & moving a large number of files --> Using tar
2. Size of one video program (devel) ~ 6.0 GB --> total ~ 1.2TB (6.0 x 200) ==> size of test: 1.2TB x 3 ~ 3.6TB (dense4mul)
--> 1.3TB for phow6 and phow8 (DELETE to get free space on per610a) --> Jul17 09:15
3. Check existence of file --> MUST use both file_exists() and filesize();

4. Estimation for BOW clustering
// for trecvid2012 --> the number of videos is REDUCED to 200
// shot information can not be inferred from keyframeID --> one shot = one keyframes
$fVideoSamplingRate = 1.0; // percentage of videos of the set will be selected
$fShotSamplingRate = 0.05; // lower this value if we want more videos, percentage of shots of one video will be selected
$fKeyFrameSamplingRate = 0.0001; // i.e. 1KF/shot - $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFsPerShot));

// Estimation
// Max KeyFrames = 1.5M / (1000 * 0.7) = 2K KF
// Number of videos = 200 --> Number of KF per video ($fVideoSamplingRate = 1.0) = 2K / 200 = 10 (QUOTA)
// Number of shots per video (by parsing .RKF) ~ 400K (of devel set 2012) /200 (videos - new organization) = 2K
// Number of KF per shot ~ 1KF
// --> if ($fShotSamplingRate = 0.05) --> 2K * 0.05 = 100 (10 (QUOTA))

5. Clear -E state
-- login to root account on bc201
- qmod -c '*' 

6. BOW-GridAll
--> local dir is not enough space

7. Kill all mv
pgrep -u ledduy mv | xargs kill -9

8. - IOStat
+ login to per610a
+ mount --> sdi <--> das09lf
- http://blog.scoutapp.com/articles/2011/02/10/understanding-disk-i-o-when-should-you-be-worried
+  iostat -kxd sdi 5

*********** Jul 03, 2012 **************
- Benchmark dir: $gszRootBenchmarkDir = "/net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012"; // *** CHANGED ***
- Resize frame: 500x500 (different from last year)

/////////////////////////////////////////////////////////////////////////
@@ STEP 1: Metadata Organization
--> trecvid-active/iacc.1.c.master.shot.reference
1. Copy C.collection.xml to tv2012.collection.xml
2. Untar mp7.tar.gz and then rename mp7 dir to tv2012.shot
--> Check the number of xml files --> 8,263
3. Untar sb.tar.gz and then move sb dir to tv2011.shot/sb
--> Check the number of sb files --> 8,263
NOTE:    sb/TRECVIDFILENAME.sb - Contains the same information as msb - 
   provided for compatibility with older software since until TV2009 
   there was a difference between msb and sb.
/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////   
@@ STEP 2: ksc-Tool-ParseNISTCollectionXML.php
- Parse file C.collection.xml to get mapping between VideoID <--> VideoName
- Output: metadata/keyframe-5/tv2012.test.lst / tv2012.test.lst 

/*
* 	Input is xml files provided by TRECVID:
* 		+ collection: mapping between videoID and videoName, patName (collection.xml)
*
* 	Output is a set of files for NII-SECODE
* 		+ video.trecvid.lst: all in one file, mapping between videoID, videoName, and path
* 		+ tv200x.lst: mapping between videoID, videoName, and path for given year.
* 		+ tv2012.test.lstx --> extend lst file by adding duration, framecount, frame rate
*			--> TRECVID2012_19861 #$# 071404whoisthisman._-o-_.071404whoisthisman_512kb #$# tv2012/test #$# 15.00 #$# 452 #$# 30.1120 #$# 573672.0000 
*/

Example: 
+ NEWTV12_0001 #$# NEWTV12_0001 #$# tv2012/devel-nistNew
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 3: ksc-Tool-ParseNISTShotBoundaryXML.php
- Parse videoID.xml, videoName.sb files to get shotboundary information
- Output: metadata/keyframe-5/tv2012/test/*.sb - *.lig.sb
+ *.sb --> parse from videoID.xml
+ *.lig.sb --> parse from videoName.xml
- Comparing .sb file and .lig.sb file --> found inconsistency --> store videos in ErrInconsistency.csv 
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 4: ksc-Tool-ExtractKeyFrames-SGE.php, ksc-Tool-ExtractKeyFrames.php, ksc-Tool-ExtractKeyFrames-Inconsitency.php
*** Requirements ***
- Video file (ksc-Tool-ParseNISTCollectionXML.php), Shot boundary information (ksc-Tool-ParseNISTShotBoundaryXML.php), number of keyframes per shot (10)

- AvgLoad: 0.08
- 300 jobs
- Running time for all: 30 mins

*****- 23 inconsistency videos with special treatment ****
+ convert to mpg with NO AUDIO (-an option)  ***NEW***
+ calculate source frame rate = Total frames / Duration. 
// Total frames --> obtain from .sb files (raw data provided by LIG)
// Duration --> obtain from .lstx files (parse from xml files)

****** run this script on per900a because ffmpeg on per900b FAILED ***********

+ devel: 400,289 keyframes, 19,701 videos
+ test: 1,118,043 keyframes, 8,263 videos (max 10KF/shot) - VideoID: 19,861 - 28,123 
+ test --> #shots: 162,160 (counted by wc *.sb)
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 5: ksc-Tool-ConvertNISTKeyFrames.php
- Convert Keyframes Provided by NIST (or LIG) into KSC format (videoID.shotID.KeyFrame).
- TRECVID2010: 11,524 dirs
- TRECVID2011: 8,216 dirs
- Total: 19,740 (estimate) --> 19,701 (real)

/* Preparation
 * - Download keyframes of collaborative annotation --> keyframes.tgz (~6.6GB)
 * - Put in keyframe-5/tv2012/devel-lig and untar --> sub-dir: keyframes
 * - Move * --> devel --> devel-lig/TRECVIDzzz_zzz
 * - Run script --> keyframe's names are changed and copy to output dir devel-nist
 */
 -Running time: 2 threads, 2 hours
/////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////// 
@@ STEP 6: ksc-Tool-OrganizeDevelTestData-TV.php
- Run with 2 params: devel-nist and test
- Run .sh file
- develNew: 400,289 KF
- testNew: 1,118,043
********* Modify to run on GRID ***********
- ksc-Tool-OrganizeDevelTest-Data-TV-SGE.php --> generate script in bin2012
- ksc-Tool-OrganizeDevelTest-Data-TV.php --> main code --> be careful about a+t mode
- ksc-OrganizeDevelTest-Data-TV.sgejob.sh --> sge script
- Processing time (100 cores for devel-nist, 200 cores for test): 
- AvgLoad: 0.10 --> 0.14

***** Sau nhieu lan thu --> chon cach xai tar file de gom tat ca cac keyframes cua 1 video thanh 1 file .tar *****
- Toc do doc thi nhanh nhung toc do ghi thi rat cham
- Starting 15:55, 06 Jul (per900a) --> 18:25
/////////////////////////////////////////////////////////////////////////

********* STEP 6.1: ksc-Took-CheckKeyFrameExtraction.php*************
- Run on per610a
- Output csv files are stored in dir keyframe-5
- Facts for test partition
--> #keyframes after extracting from videos: 1,130,065 *WHY this number???*** 
### When running this script for test partition (before re-organize into testNew), still 1,118,043
### When running find -name "*.jpg" | wc, still 1,118,043
### When running wc *.prg, still 1,118,042
### When running wc *.prgx (testNew), still 1,118,042
====> Confirmed the number of extracted keyframes for test & testNew partition is 1,180,043 (Jul 12, 2012)
--> #keyframes after re-organization: 1,118,043 
*** BUG ***
 #raw feature files after extracting: 984,048 (lost 146K frames) --> WHY???  
 --> only affect to testNew. 
 --> for testNew, 2 video programs/job. If the number of keyframes of the former video program < the number of keyframes of the later video program --> bug occurs
 --> because the max number of keyframes is set to the max number of keyframes of the former video program   

*********************************************************************
/////////////////////////////////////////////////////////////////////////
@@ STEP 7: Local Feature Extraction
- Update ksc-AppConfigForProject.php --> new pat name --> devel-nistNew and testNew
- IOStat
+ login to per610a
+ mount --> sdi <--> das09lf
- http://blog.scoutapp.com/articles/2011/02/10/understanding-disk-i-o-when-should-you-be-worried
+  iostat -kxd sdi 5

*********** SIGNIFICANT CHANGE *****************
// Search for Modified Jul 06

//--> TmpDir Description
//- TmpDir = RootTmpDir + ScriptName + FeatureExt + VideoPath
//- TmpDir + KeyFrames  --> Store keyframes downloaded from the server, ONE tar file for all keyframes of ONE video
//- Tmpdir + Feature --> Store temporary files during feature extraction

//--> New process
//- Keyframes of ONE video might be packed in one or more tar files --> scan all .tar files and untar to LOCAL dir
//- Later steps will use keyframes stored in local dir

//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5/%s", $szRootDir, $szFeatureExt);
makeDir($szRootFeatureDir);

*** Use vlfeat-0.9.14@per900a ***
--> VLFEAT 
+ convert to pgm
+ resize
- run on bc3 & bc4 --> 276 cores
- Ave load: 0.50
- 10 hours (11:00 - 20:00, Jul 07)--> 400K ==> Estimate 30 hours --> STOP
*** WARNING ***
+ TmpDir mixing --> remove working files --> fix by putting VideoID in TmpFeatureDir

-- Rerun 1:00 Jul 08, 2012 (276 cores for VLFEAT, 24 core for COLORSIFT)
-- dense6mul.rgb ~ 16-20 hours/job-test, 2-6 hours/job-devel --> total jobs for devel = 200, test = 200
--> using 200 cores, ~1 day

- Split into smaller number of jobs, each job cares 200 keyframes

**************** IMPORTANT *******************
// vlfeat 0.9.14 changes the output of vl_phow, from 3 to 4 values
// --> output values for one keypoints = 128 + 1

*** File size problem ***
- When checking existence of a file, do not check the file size --> affect to the next step (ksc-BOW-ComputeAssignmentSash-XXX.php)
--> fix by adding checking of the file size before deciding whether to skip or not (i.e. re-computing the feature file).
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 8: ksc-BOW-Quantization-SelectKeyPointsForClustering.php

// Check FeatureOutputDir for load balancing
// Decided to use non-TV method that was used in imageclef12

// We need VideoID to find Path. For TRECVID, is is encoded in KeyFrameID
// TRECVID2005_101.shot101_1.RKF_0.Frame_4 --> TRECVID2005_101
// We need to modify this part for applying other set such as imageCLEF
/*
 // TRECVID2005_101.shot101_1.RKF_0.Frame_4
$arTmp = explode(".", $szKeyFrameID);
$szVideoID = trim($arTmp[0]);
$szVideoPath = $arVideoList[$szVideoID];

$szInputDir = sprintf("%s/%s/%s/%s",
		$szRootFeatureDir, $szFeatureExt, $szVideoPath, $szVideoID);
$szCoreName = sprintf("%s.%s", $szKeyFrameID, $szFeatureExt);
$szFPTarKeyPointFN = sprintf("%s/%s.tar.gz",
		$szInputDir, $szCoreName);
*/

//$szRootFeatureDir = sprintf("%s/feature/keyframe-5", $szRootDir);
//*** CHANGED *** !!! Modified Jul 06, 2012
$szRootOutputDir = getRootDirForFeatureExtraction($szFeatureExt); //*** CHANGED *** !!! New Jul 06, 2012
$szRootFeatureDir = sprintf("%s/feature/keyframe-5/%s", $szRootOutputDir, $szFeatureExt);
makeDir($szRootFeatureDir);

// for trecvid2012 --> the number of videos is REDUCED to 200
// shot information can not be inferred from keyframeID --> one shot = one keyframes
$fVideoSamplingRate = 1.0; // percentage of videos of the set will be selected
$fShotSamplingRate = 0.05; // lower this value if we want more videos, percentage of shots of one video will be selected
$fKeyFrameSamplingRate = 0.0001; // i.e. 1KF/shot - $nNumSelKFs = intval(max(1, $fKeyFrameSamplingRate*$nNumKFsPerShot));

// Estimation
// Max KeyFrames = 1.5M / (1000 * 0.7) = 2K KF
// Number of videos = 200 --> Number of KF per video ($fVideoSamplingRate = 1.0) = 2K / 200 = 10 (QUOTA)
// Number of shots per video (by parsing .RKF) ~ 400K (of devel set 2012) /200 (videos - new organization) = 2K
// Number of KF per shot ~ 1KF
// --> if ($fShotSamplingRate = 0.05) --> 2K * 0.05 = 100 (10 (QUOTA))

/////////////////////////////////////////////////////////////////////////
@@ STEP 9: ksc-BOW-GetKeyFrameSize
--> keyframes are packed in tar file, so we need to unpack first
--> No need to use SGE because it runs very FAST
*********** IMPORTANT ***********
--> Need to have stats on file size and relation with #keypoints

/////////////////////////////////////////////////////////////////////////
@@ STEP 10: ksc-BOW-ComputeAssignment-Sash
-*** NEWTV12_1024.nsc.bow.dense6mul.oppsift.Soft-500-VL2.tv2012.devel-nistNew.label.lst.tar.gz: Run on per900b, 15:00 Jul 11 -- 06:00 Jul 12 ---> 15 hours 
//--> Heavy because raw feature files must be loaded for assigning labels
//--> Adding code for checking existing files to ensure the number of keyframes of one video = the number of lines in label.lst file 
**** Ensure number of keyframes = number of lines
/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////
@@ STEP 11: ksc-BOW-ComputeSoftBOW-GridAll <-- NEW
- Compute ALL grid ONCE
// When running on the grid engine, take a lot of time for reading data (keypoint assignment)
//--> Compute features for ALL grids ONCE to reduce processing time (mainly reading & transferring data)
//--> Adding code for checking existing files to ensure the number of keyframes of one video = the number of lines in label.lst file 
**** Ensure number of keyframes = number of lines
//**** Processing Time ********
//--> Load label list file for one video program --> each line --> one keyframe
//--> Load raw keypoint file for each keyframe to get the location of each keypoint  


***************** Jul 16 - BUGS *******************
- Mot so jobs bi loop lai
- Mot so video program co cutput file size = 0 

###Start [07.16.2012 - 02:06:17 --> $$$]: [tv2012.testNew]-[nsc.bow.dense6mul.oppsift.Soft-500-VL2.tv2012.devel-nistNew]-[nsc.raw.dense6mul.oppsift]-[166]-[168]

###Start [07.16.2012 - 02:33:54 --> $$$]: [tv2012.testNew]-[nsc.bow.dense6mul.oppsift.Soft-500-VL2.tv2012.devel-nistNew]-[nsc.raw.dense6mul.oppsift]-[166]-[168]

Clear -E state
-- login to root account on bc201
- qmod -c '*' 
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@@ STEP 12: Organization for annotation data
- See runme.OrganizeAnnotationData.txt
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@@ STEP 13: Organization for running experiments
- runme.hlf-tv2012.sh --> new experiment dir: /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2012/experiments
- hlf-tv2012.cfg --> copy to experiments/hlf-tv2012
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@@ STEP 14: ksc-Tool-GenerateRunConfig.php
//--> Seperate RootDirs for Metadata, Feature, Experiments

/////////////////////////////////////////////////////////////////////////
 
 
/////////////////////////////////////////////////////////////////////////
@@@ STEP 15: ksc-ProcessOneRun-Train-New.php
 // Customize for tvsin12  
//--> Experiment Dir, Metadata Dir and Feature Dir might be DIFFERENT
//--> New mapping 
// + .pos.ann --> TRECVID2011_19545 #$# TRECVID2011_19545.shot19545_4 #$# TRECVID2011_19545.shot19545_4.RKF_RKF #$# Pos
//--> BEFORE: video path for TRECVID2011_19545 is stored in tv2012.devel-nist.lst
//--> CURRENT: video path for TRECVID2011_19545 is found by 
//>>> mapping TRECVID2011_19545 to new video program NEWTV2012_xxxx
//>>> find the mapping in tv2012.devel-nistNew.lst 
 
 //** SELECTION PROCESS - Update Aug 12
// Step 1: Load annotation data --> ($szVideoID --> original videoID) 		
// TRECVID2007_68 #$# TRECVID2007_68.shot68_28 #$# TRECVID2007_68.shot68_28_RKF #$# Pos
// $arAnnList[$szVideoID][$szFullShotID][] = $szKeyFrameIDz; // Oct 18
// $arAllShotList[$szFullShotID] = 1;
//--> NEW: All shots are picked (fSamplingRate = 1.0)

// Step 2: Select keyframes from shots
//** $nNumKeyFramesPerShot is the important param, usually set to 5
// shuffle_assoc($arAnnList); // bottom will be ignored if it reaches the limit max samples to be selected 
// From $arAnnList, For each $szVideoID --> get a list of $arShotList  --> for each $szFullShotID 
//--> get a list of $arShotKFList --> get a subset $arSelKFList (according to $nNumKeyFramesPerShot)
//--> aggregate all $arSelKFList into $arAllSelKFList[$szNewVideoID][$szVideoID][] = $szKeyFrameID;
// There is a map
// $arNEWVideoPathList[$szOrigVideoID]['id'] = $szNewVideoID;
// $arNEWVideoPathList[$szOrigVideoID]['path'] = $szNewVideoPath;
/*
		$szNewVideoID = $arNEWVideoPathList[$szVideoID]['id'];
			
		foreach($arSelKFList as $szKeyFrameID)
		{
			$arAllSelKFList[$szNewVideoID][$szVideoID][] = $szKeyFrameID;
		}
*/		
// $arAllSelKFList is organized into $szNewVideoID because feature file is referred to $szNewVideoID

// Step 3: Get feature vectors
// load feature files of $szNewVideoID --> MIGHT NOT BE GOOD in the case of TRECVID when one $szNewVideoID contains several $szOrigVideoID 
// because most of feature vectors (usually happenning in neg sample list)
// of some top $szNewVideoID will be selected, not scatterred as in $arAnnList.
 
 
 // New range of grid search
 
 -- 120 jobs finished after ~ 2 hours
 
 
 *** 4K + 40K --> CHANGED to 3K + 40K (changed in ksc-ProcessOneRun-Train) -- 12:20 Jul20 (effective time)
 4K + 40K --> for concepts such as Face --> ~ 48-54 hours --> TOO slow


*** dense6mul.oppsift.norm3x1 --> 4.2 GB for devel-nistNew
*** dense6mul.oppsift.norm4x4 --> 12.1 GB for devel-nistNew

ksc-ProcessOneRun-Train-New2.php --> for no-ann task
		$szFullShotID = $szKeyFrameIDz;

/////////////////////////////////////////////////////////////////////////
 
/////////////////////////////////////////////////////////////////////////
@@@ STEP 16: ksc-ProcessOneRun-Test-New.php
 // Customize for tvsin12  
- Re-use functions for loading features into central pool shared by jobs of one host in ksc-ProcessOneRun-Train-New.php
/////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////
@@@ STEP 17: ksc-ProcessOneRun-Rank.php
- Copied from tvsin11 (instead of imageclef) 

/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////
@@@ STEP 18: ksc-ProcessOneRun-LateFusion.php
 
 --> ksc-web-GenerateRunConfig-Fusion.php
 --> 500 cores, 1.5 hours (Kitty-F2)

/////////////////////////////////////////////////////////////////////////
 
 
 NoAnn
 - Copy keyframe-5/tv2012/devel-noAnn/*.jpg
 - Copy metadata/keyframe-5/tv2012/devel-noAnn/*.prg
 - Generate tv2012.devel-noAnn.lst (videoID #$# videoID #$# videoPath)
 - For each keyframe dir --> create a tar file
 - Use ColorSIFT3x1, BOW of devel-nistNew
 - An
 
 