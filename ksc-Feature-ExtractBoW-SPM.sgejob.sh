# Written by Duy Le - ledduy@ieee.org
# Last update Sep 02, 2013

#!/bin/sh

# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh

# Force to limit hosts running jobs
#$ -q all.q@@bc2hosts,all.q@@bc3hosts,all.q@@bc4hosts,all.q@@bc5hosts

# Log starting time
date 

# for opencv shared lib
# export LD_LIBRARY_PATH=/net/per900c/raid0/ledduy/usr.local/lib:$LD_LIBRARY_PATH

# Log info of the job to output file  *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-Feature-ExtractBoW-SPM] [$1] [$2] [$3] [$4] [$5] [$6] [$7]

# change to the code dir  --> NEW!!! CHANGED FOR VSD14
cd /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014

# Log info of current dir
pwd

# Command - -->  must use " (double quote) for $2 because it contains a string  --- *** CHANGED ***
/net/per900c/raid0/ledduy/usr.local/bin/php -f ksc-Feature-ExtractBoW-SPM.php $1 "$2" $3 $4 $5 $6 $7

# Log ending time
date
