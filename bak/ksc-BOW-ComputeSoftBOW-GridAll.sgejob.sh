# Written by Duy Le - ledduy@ieee.org
# Last update Jul 10, 2011

#!/bin/sh

# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh

# Force to limit hosts running jobs
#$ -q all.q@@bc2hosts,all.q@@bc5hosts,all.q@@bc3hosts,

# Run in currect dir
#$ -cwd

# Log starting time
date 

# for opencv shared lib
# export LD_LIBRARY_PATH=/net/per900b/raid0/ledduy/usr.local/lib:$LD_LIBRARY_PATH

# set path
# export PATH=$PATH:/net/per900b/raid0/ledduy/video.archive/feature
# echo $PATH

# Log info of the job to output file  *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-BOW-ComputeSoftBOW-GridAll] [$1] [$2] [$3] [$4] [$5] 

# change to the code dir  --> NEW!!!  *** CHANGED ***
cd /net/per900b/raid0/ledduy/github-projects/kaori-secode

# Log info of current dir
pwd

# Command -  *** CHANGED ***
/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-BOW-ComputeSoftBOW-GridAll.php $1 $2 $3 $4 $5 

# Log ending time
date 