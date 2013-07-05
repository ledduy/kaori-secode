# Written by Duy Le - ledduy@ieee.org
# Last update Jun 26, 2012

#!/bin/sh

# Force to use shell sh. Note that #$ is SGE command
#$ -S /bin/sh

# Force to limit hosts running jobs
#$ -q all.q@@bc5hosts,all.q@@bc3hosts

# Log starting time
date 

# for opencv shared lib
# export LD_LIBRARY_PATH=/net/per900b/raid0/ledduy/usr.local/lib:$LD_LIBRARY_PATH

# Log info of the job to output file  *** CHANGED ***
echo [$HOSTNAME] [$JOB_ID] [ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT] [$1] [$2] [$3] [$4] [$5] [$6] [$7] [$8]

# change to the code dir  --> NEW!!! *** CHANGED ***
cd /net/per900b/raid0/ledduy/github-projects/kaori-secode

# Log info of current dir
pwd

# Command - -->  must use " (double quote) for $2 because it contains a string  --- *** CHANGED ***
/net/per900b/raid0/ledduy/usr.local/bin/php -f ksc-Feature-ExtractRawAffCovSIFTFeature-COLORSIFT.php $1 "$2" $3 $4 $5 $6 $7 $8

# Log ending time
date
