# Written by Duy Le - ledduy@ieee.org
# Last update Oct 08, 2014

#!/bin/sh

# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh

# Force to limit hosts running jobs
#$ -q all.q@@bc2hosts,all.q@@bc3hosts,all.q@@bc4hosts,all.q@@bc5hosts

# Run in currect dir
#$ -cwd

# Log starting time
date 

# for opencv shared lib
export LD_LIBRARY_PATH=/net/per900c/raid0/ledduy/usr.local-per900b/lib:$LD_LIBRARY_PATH

# set path
# export PATH=$PATH:myDir
# echo $PATH

# Log info of the job to output file  *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-Feature-ExtractBaselineFeature] [$1] [$2] [$3] [$4] [$5] [$6]

# change to the code dir  --> NEW!!!  *** CHANGED ***
cd /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014

# Log info of current dir
pwd

# Command -  *** CHANGED ***
/net/per900c/raid0/ledduy/usr.local/bin/php -f ksc-Feature-ExtractBaselineFeature.php $1 $2 $3 $4 $5 $6

# Log ending time
date
