# /************* STEPS FOR BOW MODEL ***************
# * 	===> STEP 1: nsc-BOW-SelectKeyPointsForClustering-TV11.php --> select keypoints from devel pat
# * 	===> STEP 2: nsc-BOW-DoClusteringKeyPoints-VLFEAT-TV11.php --> do clustering using VLFEAT vl_kmeans, L2 distance
# * 	===> STEP 3: nsc-ComputeSashForCentroids-TV10.php --> compute sash for fast keypoint assignment, make sure sashTool using L2 distance
# * 	STEP 4: nsc-ComputeAssignmentSash-TV11/-SGE.php --> compute sash assignment, using exact search (scale factor - 4)
# * 	STEP 5: nsc-ComputeSoftBOW-Grid-TV11/-SGE.php --> compute soft assignment for grid image
# */
# This script integrates 3 STEPS (1, 2, 3).
# Params: $1: DevPatName (tv2007.devel-nist) -- $2: RawFeatureExt (nsc.raw.dense6.sift)

/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-BOW-Quantization-SelectKeyPointsForClustering.php $1 $2
/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-BOW-Quantization-DoClusteringKeyPoints-VLFEAT.php $1 $2
/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-BOW-Quantization-ComputeSashForCentroids.php $1 $2
