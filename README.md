kaori-secode
============

KAORI-SECODE - A Framework for Semantic Concept Detection

1. Purpose
- Test new local feature implementations using ImageCLEF dataset (http://www.clef-initiative.eu/documents/71612/ec10fe5c-92e7-4217-b6fa-24ad439df1ba).
  + Combine the steps of raw feature extraction, quantization, and encoding in ONE job so that it is more efficient when working on the grid.
  + Check whether normalization is needed in encoding. Currently, no normalization is used. However, in kaori-ins, it is showed that normalization improves the matching performance.
  + Check the effect of codebook size. Currently, it is fixed to 500.
  + Check soft-assignment in encoding method. Currently, some parameters, e.g norm of L2 distance of each feature point are fixed in an adhoc way
- Adding more implementations, for example, fisher vector, LLC.

2. Step 1 - Initialization
- bow-test branch is initialized from sin13 branch.
- *** REF is v:\kaori-secode\php-DemoV1-ImageCLEF12

3. Step 2 - Check app configs
*** ksc-AppConfig.php: Update paths
==> benchmark dir on per610a/das09f is empty, now is on sfv215/raid6 
//$gszRootBenchmarkDir = "/net/per610a/export/das09f/satoh-lab/ledduy/ImageCLEF/2012/PhotoAnnFlickr"; // *** CHANGED ***
## $gszRootBenchmarkDir = "/net/sfv215/export/raid6/ledduy/ImageCLEF/2012/PhotoAnnFlickr"; // update 01 Sep 2013

*** ksc-AppConfigForProject.php
==> minor changes

4. Step 3 - Check metadata   
*** devel2012.lst: OK
+ 150 rows --> 15K training images (for 94 concepts)
+ imageclef2012-devel-0001 #$# imageclef2012-devel-0001 #$# devel2012
*** devel2012 subdir: OK
+ .prg and .prgx are OK.

- test2012.lst: OK
+ 100 rows --> 10K testing images (for 94 concepts)
+ imageclef2012-test-0001 #$# imageclef2012-test-0001 #$# test2012
*** test2012 subdir: OK
+ .prg and .prgx are OK.

5. Step 4 - Local feature extractio with new implementation integrating all in one step.
*** ksc-Feature-ExtractBoW-SPM.php --> copy from kaori-ins and MODIFY
*** Re-use old codebook
+ old codebook dir: bow.codebook.Soft-500-VL2.imageclef2012.devel
+ new codebook dir (copy and rename): bow.codebook.Soft-500.devel2012
+ rename files in subdir, eg. /net/sfv215/export/raid6/ledduy/ImageCLEF/2012/PhotoAnnFlickr/feature/keyframe-5/bow.codebook.Soft-500.devel2012/nsc.raw.dense6mul.rgbsift/data
### Only need for 2 *Centroid* file: Soft-500.devel2012 <-- Soft-500-VL2.imageclef2012.devel.nsc.raw.dense6mul.rgbsift.Centroids-c0-b0.dvf
*** Check output file -->  OK.
*** Processing time: ave 60mins/job (100KF) 

6. Step 5 - Train & Test classifier 
*** Features to compare: 
+ nsc.bow.dense6mul.rgbsift.Soft-500.devel2012.L1norm1x1  vs nsc.bow.dense6mul.rgbsift.Soft-500.devel2012.NOnorm1x1 
+ nsc.bow.dense6mul.rgbsift.Soft-500.devel2012.L1norm3x1  vs nsc.bow.dense6mul.rgbsift.Soft-500.devel2012.NOnorm3x1
*** Prepare run config
imageclef2012-PhotoAnnFlickr.nsc.bow.dense6mul.rgbsift.Soft-500.devel2012.L1norm1x1.ksc.imageclef2012.R1.cfg: modify FeatureExt and devel & test pat 

*** Modify global config
imageclef2012-PhotoAnnFlickr.cfg 
*** Modify code Train --> loadVideoMap
*** Processing time: 2h - 4h/classifier
5K+10K --> 5 hours
--> ImageCLEF 2012 --> some features are NOT COMPLETE 

7. Step 6 - Evaluation
*** Groundtruth - Raw
/net/sfv215/export/raid6/ledduy/ImageCLEF/2012/PhotoAnnFlickr/data/fast.hevs.ch/photo-flickr/test_annotations.zip 
Extract the directory concepts to ImageCLEF/2012/PhotoAnnFlickr/annotation/groundtruth

***
imageclef2012-PhotoAnnFlickr.all.local+global.new4x4.ksc.imageclef2012.fusion.eval.csv - 33.06 === Reported result of ImageCLEF12 - (NII.Run1.KSC.Loc45-G8)
dense6mul.rgbsift.NOnorm1x1: 25.46
dense6mul.rgbsift.NOnorm3x1: 27.96
dense6mul.rgbsift.L1norm1x1: 25.56 (minor improvement) --> might be due to scaling - WIN: 50 - LOSE: 44
dense6mul.rgbsift.L1norm1x1: 27.68 (minor decrease) WIN: 46 - LOSE: 48

8. Step 7 - Check scaling in training 
*** New param: $nPerformDataScaling = 0;
*** New config param in file .cfg