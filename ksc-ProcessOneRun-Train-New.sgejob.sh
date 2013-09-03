# Written by Duy Le - ledduy@ieee.org
# Last update Jul 8, 2013

#!/bin/sh

# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh

# Force to limit hosts running jobs
#$ -q all.q@@bchosts

# Run in currect dir
#$ -cwd

# Log starting time
date 

# for opencv shared lib
# export LD_LIBRARY_PATH=/net/per900b/raid0/ledduy/usr.local/lib:$LD_LIBRARY_PATH

# Log info of the job to output file *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-ProcessOneRun-Train-New] [$1] [$2] [$3] [$4]

# change to the code dir  --> NEW!!!  *** CHANGED ***
cd /net/per900b/raid0/ledduy/github-projects/kaori-secode-bow-test

# Log info of current dir
pwd

# Command -  *** CHANGED ***
/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-ProcessOneRun-Train-New.php $1 $2 $3 $4

# Log ending time
date
