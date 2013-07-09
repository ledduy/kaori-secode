# Modified Jul 09, 2013
# Experiment dir --> /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/experiments

mkdir -p /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/experiments
cd /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/experiments

echo "############# INITIALIZING CONFIGS ################"
echo "@@@Making dirs"
mkdir hlf-tv2013
mkdir hlf-tv2013/annotation
mkdir hlf-tv2013/annotation/lig.iacc1.tv2012
mkdir hlf-tv2013/metadata

echo "@@@Copying metadata, i.e. list of video programs for devel and test pat"
cp /net/dl380g7a/export/ddn11a6/ledduy/trecvid-sin-2013/metadata/keyframe-5/*.lst ./hlf-tv2013/metadata

echo "@@@Copying annotation for devel --> for training purpose" - re-use hlf-tv2012
cp -R /net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012/annotation/concept/ksc/lig.iacc1.tv2012 ./hlf-tv2013/annotation/

echo "@@@Copying annotation for test --> for visualizing purpose, including tv2010.features.qrel for evaluation purpose (using with trec_eval_video)"
#cp -R /net/per610a/export/das09f/satoh-lab/ledduy/trecvid-sin-2012/annotation/concept/ksc/nist.tv2012 ./hlf-tv2012/annotation
#mv ./hlf-tv2012/annotation/nist.tv2012 ./hlf-tv2012/annotation/nist.hlf-tv2012

echo "@@@Copying concept list" - Only 60 concepts for hlf-tv2013
cp ./hlf-tv2013.Concepts.lst  ./hlf-tv2013/annotation/hlf-tv2013.Concepts.lst

echo "############# FINISH! ################"
