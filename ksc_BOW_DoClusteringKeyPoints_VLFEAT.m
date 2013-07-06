% Do clustering using VLFEAT-matlab
% This script must be put in vlfeat dir
% szFPInputFN - input file name in csv format
% szFPCentroidOutputFN - centroids of output clusters
% szFPIMemOutputFN - cluster assignment
% run('toolbox/vl_setup'); --> must call to init env
% 1M keypoints --> 3GB memory
% Written by Duy-Dinh Le
% Last update: Feb 21, 2011
% Using k-means, (not integer kmeans as before)
% Last update: Feb 08, 2011
% Using integer k-means

function ksc_BOW_DoClusteringKeyPoints_VLFEAT(szFPCentroidOutputFN, szFPIMemOutputFN, szFPInputFN, nNumClusters, szMethod)
	
	run('toolbox/vl_setup'); % init env
	
	fprintf(1, 'Loading csv data file ...\n');
	data = csvread(szFPInputFN);

	fprintf(1, 'Performing k-means [%u] clusters with method [%s]...\n', nNumClusters, szMethod);
	
	% [C,A] = vl_ikmeans(uint8(data'),nNumClusters,'method', szMethod) ; % convert to unit8, and transpose
	[C,A] = vl_kmeans(data',nNumClusters,'algorithm', szMethod) ; % transpose
	
	fprintf(1, 'Saving output data...\n');
	dlmwrite(szFPCentroidOutputFN, C'); % transpose C
 	dlmwrite(szFPIMemOutputFN, A');
 	quit;  % quit matlab since it is used to run within PHP 	
end
