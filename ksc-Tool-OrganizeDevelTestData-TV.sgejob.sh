# Written by Duy Le - ledduy@ieee.org
# Last update Jun 26, 2012

#!/bin/sh

# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh

# Force to limit hosts running jobs
#$ -q all.q@@bc3hosts,all.q@@bc2hosts

# Log starting time
date 

# for opencv shared lib
export LD_LIBRARY_PATH=/net/per900b/raid0/ledduy/usr.local/lib:$LD_LIBRARY_PATH

# Log info of the job to output file  --- *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-Tool-OrganizeDevelTestData-TV] [$1] [$2] [$3] 

# change to the code dir  --> NEW!!!  *** CHANGED ***
cd /net/per900b/raid0/ledduy/github-projects/kaori-secode

# Log info of current dir
pwd

# Command -  *** CHANGED ***
/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-Tool-OrganizeDevelTestData-TV.php $1 $2 $3

# Log ending time
date
