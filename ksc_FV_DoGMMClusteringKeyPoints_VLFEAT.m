% Do clustering using GMM - VLFEAT-matlab-0.9.17 
% This script must be put in vlfeat dir
% szFPInputFN - input file name in csv format
% szFPGMMOutputFN - output vars of a GMM including means, covariances, priors
% nNumClusters - 256 is default
% run('toolbox/vl_setup'); --> must call to init env
% Written by Duy-Dinh Le
% Last update: Sep 03, 2013

function ksc_FV_DoGMMClusteringKeyPoints_VLFEAT(szFPGMMOutputFN, szFPInputFN, nNumClusters)
	
	matlabpool 8;
	run('toolbox/vl_setup'); % init env
	
	fprintf(1, 'Loading csv data file ...\n');
	data = csvread(szFPInputFN);

	fprintf(1, 'Performing GMM for [%u] clusters...\n', nNumClusters);
	
	%[MEANS, COVARIANCES, PRIORS] = VL_GMM(X, NUMCLUSTERS) fits a GMM with NUMCLUSTERS components to the data X. Each column of X represent a sample point. X may be either SINGLE or DOUBLE. MEANS, COVARIANCES, and PRIORS are respectively the means, the diagonal covariances, and the prior probabilities of the Guassian modes. MEANS and COVARIANCES have the same number of rows as X and NUMCLUSTERS columns with one column per mode. PRIORS is a row vector with NUMCLUSTER entries summing to one.
	
	% data is row-representation --> use data' for col-representation
	
	% processing time: 7 hours (max 20 iterations, kmeans initialization, 128 clusters, 1,578,691 descriptors, 384 dims-rgbsift)
	[means, covariances, priors] = vl_gmm(data', nNumClusters, 'verbose', 'MaxNumIterations', 100, 'Initialization', 'KMeans');	
	
	% save these var for loading later in compute fisher encoding.
	save(szFPGMMOutputFN, 'means', 'covariances', 'priors');
	
 	quit;  % quit matlab since it is used to run within PHP 	
end
