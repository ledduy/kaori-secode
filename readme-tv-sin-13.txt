
/////////////////////////////////////////////////////////////////////////
@@ STEP 1: Metadata Organization - Jul 05, 2013

--> RootDir: /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/

--> Copy metadata (from NIST's 'trecvid-active') to trecvid-sin-2013/trecvid-active

--> untar files .mp7.tar.gz and msb.tgz
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 2: Parse collection.xml file - Jul 05, 2013
- Parse files iacc.2.A.collection.xml, iacc.2.B.collection.xml, iacc.2.C.collection.xml. to get mapping between VideoID <--> VideoName
- Output
	+ metadata/keyframe-5/tv2013.test.iacc.2.A.lst/lstx  
	+ metadata/keyframe-5/tv2014.test.iacc.2.B.lst/lstx  
	+ metadata/keyframe-5/tv2015.test.iacc.2.C.lst/lstx  

	+ mapping (lstx): TRECVID2012_19861 #$# 071404whoisthisman._-o-_.071404whoisthisman_512kb #$# tv2012/test #$# 15.00 #$# 452 #$# 30.1120 #$# 573672.0000 

###> php code: 
- ksc-Tool-ParseNISTCollectionXML.php --> parse .collection.xml file

### preparation for video dir
***  /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/video/tv2013/test.iacc.2.A
--> map to /net/per610a/export/das11f/ledduy/new-trecvid-sin/iacc.2.a/
mkdir -p /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/video/tv2013
cd /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/video/tv2013
ln -s /net/per610a/export/das11f/ledduy/new-trecvid-sin/iacc.2.a/ test.iacc.2.A

mkdir -p /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/video/tv2014
cd /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/video/tv2014
ln -s /net/per610a/export/das11f/ledduy/new-trecvid-sin/iacc.2.b/ test.iacc.2.B

mkdir -p /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/video/tv2015
cd /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/video/tv2015
ln -s /net/per610a/export/das11f/ledduy/new-trecvid-sin/iacc.2.c/ test.iacc.2.C

[ledduy@per900b tv2013]$ pwd
/net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/video/tv2013
[ledduy@per900b tv2013]$ ls -la
total 0
drwxr-xr-x 2 ledduy users 66 Jul  5 11:33 .
drwxr-xr-x 3 ledduy users 19 Jul  5 11:33 ..
lrwxrwxrwx 1 ledduy users 59 Jul  5 11:33 test.iacc.2.A -> /net/per610a/export/das11f/ledduy/new-trecvid-sin/iacc.2.a/
[ledduy@per900b tv2013]$

/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 3: Parse mp7.xml file to get SB info - Jul 05, 2013

Untar iacc.2.A.mp7.tar.gz ==> output dir is iacc.2.A.mp7 ==> 2,420 xml files
Untar iacc.2.B.mp7.tar.gz ==> output dir is iacc.2.B.mp7 ==> 2,396 xml files
Untar iacc.2.B.mp7.tar.gz ==> output dir is iacc.2.C.mp7 ==> 2,405 xml files

// msb files provided by LIG
Untar iacc.2.msb.tgz ==> output dir is msb/*.msb ==> 7,221 msb files
All msb files for iacc.2.A/B/C are put into ONE dir
NOTE:    sb/TRECVIDFILENAME.sb - Contains the same information as msb - 
   provided for compatibility with older software since until TV2009 
   there was a difference between msb and sb.

###> php code: 
- ksc-Tool-ParseNISTShotBoundaryXML.php --> parse videoNum.mp7.xml files to get sb info & double check with LIG'sb files

+ *.sb --> parse from videoID.xml
+ *.lig.sb --> parse from videoName.xml
- Comparing .sb file and .lig.sb file --> found inconsistency --> store videos in ErrInconsistency.ZZZ.csv 

/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////
@@ STEP 4: ksc-Tool-ExtractKeyFrames-SGE.php, ksc-Tool-ExtractKeyFrames.php, ksc-Tool-ExtractKeyFrames-Inconsitency.php
*** Requirements ***
- Video file (ksc-Tool-ParseNISTCollectionXML.php), Shot boundary information (ksc-Tool-ParseNISTShotBoundaryXML.php), number of keyframes per shot (10)

- AvgLoad: 0.08
- 252 jobs --> 1 job ~ 10 videos ~ 30 mins
- Running time for all: 15:15 ~ 17:45 (150 mins)

*****-  inconsistency videos with special treatment ****
+ convert to mpg with NO AUDIO (-an option)  ***NEW***
+ calculate source frame rate = Total frames / Duration. 
// Total frames --> obtain from .sb files (raw data provided by LIG)
// Duration --> obtain from .lstx files (parse from xml files)

+ devel: 400,289 keyframes, 19,701 videos
+ test.iacc.2.A (2013): 483,706 keyframes, 2,420 videos (max 5KF/shot) (counted by wc *.prg) 
+ test.iacc.2.A (2013) --> #shots: 117,517 (counted by wc *.lig.sb)
+ test.iacc.2.B (2014): 472,952 keyframes, 2,396 videos (max 5KF/shot)  
+ test.iacc.2.B (2014) --> #shots: 112,598 (counted by wc *.lig.sb)
+ test.iacc.2.C (2015): 487,083 keyframes, 2,405 videos (max 5KF/shot)  
+ test.iacc.2.C (2015) --> #shots: 118,277 (counted by wc *.sb)

(last year tvsin12: 1,118,043 keyframes, 162,160 shots)
--> this year: 1,443,741

Jul 08, 2013: Minor bugs caused keyframes of a number of videos can not be extracted
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 5: Copy data for devel set - RE-USE of trecvid-sin-2012 (per610a/das09f)
*** Copy keyframes
mkdir /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/keyframe-5/tv2012
cd /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/keyframe-5/tv2012
cp -R /net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012/keyframe-5/tv2012/devel-lig/ .
cp -R /net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012/keyframe-5/tv2012/devel-nist/ .
cp -R /net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012/keyframe-5/tv2012/devel-nistNew/ .

*** Copy metadata
mkdir /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/metadata/keyframe-5/tv2012
cd /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/metadata/keyframe-5/tv2012
cp -R /net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012/metadata/keyframe-5/tv2012/devel-nist .
cp -R /net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012/metadata/keyframe-5/tv2012/devel-nistNew .
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 6: Organize test data into subdirs to reduce the number of subdirs to be process

###> php code: 
- ksc-Tool-OrganizeDevelTestData-TV.php 
// Must run on grid due to huge processing time
// Use tar file to combine keyframes of one video program into ONE file
- Running time: 21:30 - 23:30 (2 hours)
- test.iacc.2.ANew --> 269 (2420) dirs, 9 videos/dir
- test.iacc.2.BNew --> 300 (2396) dirs, 8 videos/dir
- test.iacc.2.CNew --> 268 dirs (2405), 9 videos/dir
 /////////////////////////////////////////////////////////////////////////
 
/////////////////////////////////////////////////////////////////////////
@@ STEP 7: Organize test data into subdirs to reduce the number of subdirs to be process
>>>> BIG BOTTLENECK
###> php code: 
- ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT.php 
- 1,100 jobs/each feature (200 for devel-nistNew, 300 for each test.iacc.2.A/B/C
- 2 features: dense6mul.rgbsift and harlap6mul.rgbsift
- Running time (300 jobs, ave load 2.24 (0.60):  ~36 hours
/////////////////////////////////////////////////////////////////////////
 
/////////////////////////////////////////////////////////////////////////
@@ STEP 8: Quantization
###--> use vlfeat.0.9.14
###> php code: 
- ksc-BOW-Quantization-AllInOneStep.sh  (run on per900b)
- 2 features: dense6mul.rgbsift and harlap6mul.rgbsift
- Running time (300 jobs, ave load 2.24 (0.60)):  

###> php code: 
- ksc-BOW-ComputeAssignment-Sash

###> php code: 
- ksc-BOW-ComputeSoftBOW-GridAll

/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////
@@@ STEP 9: Organization for annotation data
- See runme.OrganizeAnnotationData.txt
/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////
@@@ STEP 10: Organization for running experiments
- runme.hlf-tv2012.sh --> new experiment dir: /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2012/experiments
- hlf-tv2013.cfg --> modify and copy to experiments/hlf-tv2013
/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////
@@@ STEP 11: ksc-Tool-GenerateRunConfig.php
//--> Seperate RootDirs for Metadata, Feature, Experiments

		"max_kf_devel_pos_set #$# 4000", // *** CHANGED ***
		"max_kf_devel_neg_set #$# 40000", // *** CHANGED ***
		"max_kf_devel_sub_size #$# 3000",
		"max_kf_shot_devel_neg #$# 1",
		"max_kf_shot_devel_pos #$# 5",


Task list:
+ clean up tmp dir in hosts
php -f ksc-Tool-DelTmpFiles.php 301 314 "find /local/ledduy -name '*Process*' | xargs rm -rf
 "
/////////////////////////////////////////////////////////////////////////
>>> raw:
+ dense6mul: DONE
2013 --> 269 ~ OK - 1 left (90-91) --> DONE
2014 --> 300 ~ OK
2015 --> 268 ~ OK
+ harlap6mul: DONE
2013 --> 269 ~ OK
2014 --> 300 ~ OK
2015 --> 268 ~ OK - 1 left (223-224) --> DONE
>>> bow-assignment-sash
+ dense6mul: NOT YET
2013 --> 265 < 269 (19, 27, 62, *91) --> /*** 4 ***/ 19@p900b-scr7, 27@p900b-scr8, 62-275834@bc309, 91@p900b-scr9
--> 266 (19-bc106, 27-bc102, 91-DONE)@p900b

******--> 267 - 19-bc106 & 27-bc102*****

2014 --> 298 < 300 ( 70, 73) --> 299 (last one is 73), --> 70 is the remaining - 275835@bc212) - DONE

2015 --> 267 < 268 (27*) --> DONE (last one is 027 - GOOD)

+ harlap6mul: DONE
2013 --> 240 < 269 (re-submitted sge 50/job) --> 260 --> 266 (50-100->275853 *96*-DONE,  158-bc101-DONE, 142-bc102-DONE) --> 269 - DONE
2014 --> 269 < 300 (re-submitted sge 50/job) --> 299 (119 - bc103-DONE)
2015 --> 242 < 268 (224*, re-submitted sge 50/job) --> 266 (50 - bc301-DONE, 224-bc105-DONE)  --> 268 - DONE

>>> SoftGrid
+ dense6mul: NOT YET
2013 --> 262,262 < 269 --> 262  --> *19, *27, 28-p900a-scr0, 61-p900a-scr1, *62, 63-p900a-scr2, *91
--> 262 (19, 27, 91-bc101)
***** 1x1: 265 (19, 27, 91-bc101, 62-bc104)
***** 3x1: 265 (19, 27, 91-bc101, 62-bc104)


2014 --> 258,258 < 300 (re-submitted sge 50/job) --> 285 --> 294 (275854@100-150-->135 , 275853@50-100 --> *70, 73, 74, 92-p900a-scr5-DONE, 98-p900a-scr3-DONE) 
--> 298 (70, 74)  --> 299 (70 - bc101) - DONE

2015 --> 244,244 < 268 (re-submitted sge 50/job) --> 264 --> 268 --> DONE

+ harlap6mul: DONE

2013 --> 240,240 < 269 (re-submitted sge 50/job) --> no change --> resubmit 1/job --> 260 --> 40-p900c-scr2-DONE, 41-p900c-sc1-DONE, 50-p900c-scr3-DONE, 91-p900c-scr4, 94-p900c-scr5-DONE, 95-p900c-scr6-DONE, 96*, 142*, 158* 
--> 266 (96-bc101-DONE, 142-bc102-DONE, 158-bc103-DONE) --> 269 - DONE 

2014 --> 269,269 < 300 (re-submitted sge 50/job) --> no change --> resubmit 1/job --> 299 --> 119* (bc104) --> DONE

2015 --> 242,242 < 268 (re-submitted sge 50/job) --> no change --> resubmit 1/job --> 266 
--> 50* (bc105-scr5-DONE), 224*(p900a-scr4-DONE) --> DONE


Training
- harlap3x1 --> 1 remaining --> Singing (grid) - 276306 - DONE
- harlap1x1 --> DONE
- dense3x1 --> 5 remaining --> Singing (broken-->per900c-scr8), Animal (broken-->per900c-scr7), Sitting_Down (broken-->p900c-scr1), Instrument (broken-->per900c-scr2), Quardrup (broken-per900c-scr3)
- dense1x1 --> DONE

Testing
- harlap3x1: 
- harlap1x1: 15,693 /  