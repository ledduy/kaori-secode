# Written by Duy Le - ledduy@ieee.org
# Last update Sep 3, 20134

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
# export LD_LIBRARY_PATH=/net/per900b/raid0/ledduy/usr.local/lib:$LD_LIBRARY_PATH

# Log info of the job to output file *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-ProcessOneRun-Train-New] [$1] [$2] [$3] [$4]

# change to the code dir  --> NEW!!!  *** CHANGED ***
cd /net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/kaori-secode-vsd2014

# Log info of current dir
pwd

# Command -  *** CHANGED ***
/net/per900c/raid0/ledduy/usr.local/bin/php -f ksc-ProcessOneRun-Train-New.php $1 $2 $3 $4

# Log ending time
date
