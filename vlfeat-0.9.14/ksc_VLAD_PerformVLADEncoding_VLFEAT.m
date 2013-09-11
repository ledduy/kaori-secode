% Perform VLAD Encoding using VLAD - VLFEAT-matlab-0.9.17 
% This script must be put in vlfeat dir
% szFPInputFN - input file name in csv format, each row = descriptor of one keypoint
% szFPGMMInputFN - variables of a VLAD saved in prev step
% run('toolbox/vl_setup'); --> must call to init env
% Written by Duy-Dinh Le
% Last update: Sep 03, 2013

function ksc_VLAD_PerformVLADEncoding_VLFEAT(szFPVLADInputFN, szFPInputFN, nNormMethod)
	
	run('./toolbox/vl_setup'); % init env
	
	fprintf(1, 'Loading VLAD data file ...\n');
	load(szFPVLADInputFN);
	
	% data is row-representation --> use data' for col-representation
	fprintf(1, 'Loading csv data file ...\n');
	data = dlmread(szFPInputFN);

	nn = vl_kdtreequery(kdtree, centers, data'); 

	% Now we have in the nn the indeces of the nearest center to each vector in the matrix dataToBeEncoded. The next step is to create an assignment matrix:
	
	assignments = zeros(nNumClusters, size(data', 2));
	assignments(sub2ind(size(assignments), nn, 1:length(nn))) = 1;
	
	% It is now possible to encode the data using the vl_vlad function:
	enc = vl_vlad(data', centers, assignments, 'verbose');
	szFPOutputFN = sprintf('%s.vlad', szFPInputFN);
	dlmwrite(szFPOutputFN, enc');
	
 	quit;  % quit matlab since it is used to run within PHP 	
end
