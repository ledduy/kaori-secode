# /************* STEPS FOR BOW MODEL ***************
# * 	===> STEP 1: ksc-BOW-Quantization-SelectKeyPointsForClustering.php --> select keypoints from devel pat - 
# *		!!! NEW !!! Keypoints are extracted only for selected keyframes (no need to extract keypoints of ALL keyframes as previous versions).
# * 	===> STEP 2: ksc-BOW-Quantization-DoClusteringKeyPoints-VLFEAT.php --> do clustering using VLFEAT vl_kmeans, L2 distance
# * 	===> STEP 3: ksc-BOW-Quantization-ComputeSashForCentroids.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
# * 	STEP 4: ksc-Feature-ExtractBoW-SPM/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
# *				AND compute soft assignment for grid image at the same time (NEW)
# */
# This script integrates 3 STEPS (1, 2, 3).
# Params: $1: SrcPatName (test2013-new) -- $2: RawFeatureExt (nsc.raw.dense6mul.sift)

/net/per900c/raid0/ledduy/usr.local/bin/php -f ksc-BOW-Quantization-SelectKeyPointsForClustering.php $1 $2
/net/per900c/raid0/ledduy/usr.local/bin/php -f ksc-BOW-Quantization-DoClusteringKeyPoints-VLFEAT.php $1 $2
/net/per900c/raid0/ledduy/usr.local/bin/php -f ksc-BOW-Quantization-ComputeSashForCentroids.php $1 $2
