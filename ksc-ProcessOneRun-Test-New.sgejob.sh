# Written by Duy Le - ledduy@ieee.org
# Last update Jul 10, 2013

#!/bin/sh

# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh

# Force to limit hosts running jobs
#$ -q all.q@@bc2hosts,all.q@@bc4hosts,all.q@@bc5hosts,all.q@@bc3hosts 

# Run in currect dir
#$ -cwd

# Log starting time
date 

# for opencv shared lib
export LD_LIBRARY_PATH=/net/per900b/raid0/ledduy/usr.local/lib:$LD_LIBRARY_PATH

# Log info of the job to output file  *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-ProcessOneRun-Test-New] [$1] [$2] [$3] [$4] [$5] [$6]

# change to the code dir  --> NEW!!!  *** CHANGED ***
cd /net/per900b/raid0/ledduy/github-projects/kaori-secode

# Log info of current dir
pwd

# Command -  *** CHANGED ***
/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-ProcessOneRun-Test-New.php $1 $2 $3 $4 $5 $6

# Log ending time
date
