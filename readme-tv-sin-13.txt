
/////////////////////////////////////////////////////////////////////////
@@ STEP 1: Metadata Organization - Jul 05, 2013

--> RootDir: /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/

--> Copy metadata (from NIST's 'trecvid-active') to trecvid-sin-2013/trecvid-active

--> untar files .mp7.tar.gz and msb.tgz
/////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////
@@ STEP 2: Parse collection.xml file - Jul 05, 2013
- Parse files iacc.2.A.collection.xml, iacc.2.B.collection.xml, iacc.2.C.collection.xml. to get mapping between VideoID <--> VideoName
- Output: 
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

****** run this script on per900a because ffmpeg on per900b FAILED ***********

+ devel: 400,289 keyframes, 19,701 videos
+ test.iacc.2.A (2013): 408,989 2,420 videos (max 5KF/shot) (counted by wc *.prg) 
+ test.iacc.2.A (2013) --> #shots: 117,517 (counted by wc *.lig.sb)
+ test.iacc.2.B (2014): 413,828 keyframes, 2,936 videos (max 5KF/shot)  
+ test.iacc.2.B (2014) --> #shots: 112,598 (counted by wc *.lig.sb)
+ test.iacc.2.C (2015): 441,177 keyframes, 2,405 videos (max 5KF/shot)  
+ test.iacc.2.C (2015) --> #shots: 118,277 (counted by wc *.sb)

(last year tvsin12: 1,118,043 keyframes, 162,160 shots)
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
 /////////////////////////////////////////////////////////////////////////
 
 /////////////////////////////////////////////////////////////////////////
@@ STEP 7: Organize test data into subdirs to reduce the number of subdirs to be process

###> php code: 
- ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT.php 
- 1,100 jobs/each feature (200 for devel-nistNew, 300 for each test.iacc.2.A/B/C
- 2 features: dense6mul.rgbsift and harlap6mul.rgbsift
- Running time (300 jobs, ave load 2.24 (0.60): 
 /////////////////////////////////////////////////////////////////////////