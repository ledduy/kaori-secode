
/////////////////////////////////////////////////////////////////////////
@@ STEP 1: Metadata Organization - Jul 04, 2013

--> RootDir: /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/

--> Copy metadata (from NIST's 'trecvid-active') to trecvid-sin-2013/trecvid-active

*********************
1. Collection xml files: iacc.2.A.collection.xml, iacc.2.B.collection.xml, iacc.2.C.collection.xml.

###> php code: 
- ksc-Tool-ParseNISTCollectionXML.php --> parse .collection.xml file
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

*********************
2. Untar iacc.2.A.mp7.tar.gz ==> output dir is iacc.2.A.mp7 ==> 2,420 xml files
Untar iacc.2.B.mp7.tar.gz ==> output dir is iacc.2.B.mp7 ==> 2,396 xml files
Untar iacc.2.B.mp7.tar.gz ==> output dir is iacc.2.C.mp7 ==> 2,405 xml files

3. Untar iacc.2.msb.tgz ==> output dir is msb/*.msb ==> 7,221 msb files
All msb files for iacc.2.A/B/C are put into ONE dir
NOTE:    sb/TRECVIDFILENAME.sb - Contains the same information as msb - 
   provided for compatibility with older software since until TV2009 
   there was a difference between msb and sb.

###> php code: 
- ksc-Tool-ParseNISTShotBoundaryXML.php --> parse videoNum.mp7.xml files to get sb info & double check with LIG'sb files

/////////////////////////////////////////////////////////////////////////