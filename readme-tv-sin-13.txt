
/////////////////////////////////////////////////////////////////////////
@@ STEP 1: Metadata Organization - Jul 04, 2013

--> RootDir: /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/

--> Copy metadata (from trecvid-active) to trecvid-sin-2013/trecvid-active

1. Collection xml files: iacc.2.A.collection.xml, iacc.2.B.collection.xml, iacc.2.C.collection.xml.

2. Untar iacc.2.A.mp7.tar.gz ==> output dir is iacc.2.A.mp7 ==> 2,420 xml files
Untar iacc.2.B.mp7.tar.gz ==> output dir is iacc.2.B.mp7 ==> 2,396 xml files
Untar iacc.2.B.mp7.tar.gz ==> output dir is iacc.2.C.mp7 ==> 2,405 xml files

3. Untar iacc.2.msb.tgz ==> output dir is msb/*.msb ==> 7,221 msb files
--> Check the number of sb files --> 8,263
NOTE:    sb/TRECVIDFILENAME.sb - Contains the same information as msb - 
   provided for compatibility with older software since until TV2009 
   there was a difference between msb and sb.

--> php code: 
/////////////////////////////////////////////////////////////////////////