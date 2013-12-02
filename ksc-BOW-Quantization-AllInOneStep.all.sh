# This script runs for all features

# COLOR-SIFT
 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.dense6mul7.sift 
 exit
 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.dense6mul.rgbsift &
 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.dense6mul.sift &
 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.dense6mul.csift &
 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.dense6mul.oppsift &

 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.harlap6mul.rgbsift &
 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.harlap6mul.sift &
 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.harlap6mul.csift &
 ./ksc-BOW-Quantization-AllInOneStep.sh devel2013-new nsc.raw.harlap6mul.oppsift &
 
 