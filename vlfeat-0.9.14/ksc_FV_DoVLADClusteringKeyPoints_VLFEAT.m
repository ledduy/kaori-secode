% Do clustering using Kmeans+kdtree - VLFEAT-matlab-0.9.17 
% This script must be put in vlfeat dir
% szFPInputFN - input file name in csv format
% szFPVLADOutputFN - output vars of a VLAD including centers, kdtree
% nNumClusters - 256 is default
% run('toolbox/vl_setup'); --> must call to init env
% Written by Duy-Dinh Le
% Last update: Sep 03, 2013

function ksc_FV_DoVLADClusteringKeyPoints_VLFEAT(szFPVLADOutputFN, szFPInputFN, nNumClusters)
	
	matlabpool 8;
	run('toolbox/vl_setup'); % init env
	
	fprintf(1, 'Loading csv data file ...\n');
	data = csvread(szFPInputFN);

	fprintf(1, 'Performing VLAD for [%u] clusters...\n', nNumClusters);
	
	%To compute VLAD, we first need to obtain a visual word dictionary. This time, we use K-means:
	centers = vl_kmeans(data', nNumClusters);	
	
	%vl_vlad requires the data-to-cluster assignments to be passed in. This allows using a fast vector quantization technique (e.g. kd-tree) as well as switching from soft to hard assignment. In this example, we use a kd-tree for quantization:
	kdtree = vl_kdtreebuild(centers) ;
	
	% save these var for loading later in compute vlad encoding.
	save(szFPVLADOutputFN, 'centers', 'kdtree', 'nNumClusters');
	
 	quit;  % quit matlab since it is used to run within PHP 	
end
