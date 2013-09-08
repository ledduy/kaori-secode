% Perform Fisher Vector Encoding using GMM - VLFEAT-matlab-0.9.17 
% This script must be put in vlfeat dir
% szFPInputFN - input file name in csv format, each row = descriptor of one keypoint
% szFPGMMInputFN - variables of a GMM saved in prev step
% run('toolbox/vl_setup'); --> must call to init env
% Written by Duy-Dinh Le
% Last update: Sep 03, 2013

function ksc_FV_PerformFisherVectorEncoding_VLFEAT(szFPGMMInputFN, szFPInputFN, nNormMethod)
	
	run('./toolbox/vl_setup'); % init env
	
	fprintf(1, 'Loading GMM data file ...\n');
	load(szFPGMMInputFN);

	fprintf(1, 'Loading csv data file ...\n');
	data = dlmread(szFPInputFN);

	% data is row-representation --> use data' for col-representation
	
	if nNormMethod == 1
		enc = vl_fisher(data', means, covariances, priors, 'Verbose', 'Normalized');
	end

	if nNormMethod == 2
		enc = vl_fisher(data', means, covariances, priors, 'Verbose', 'SquareRoot');
	end
	
	if nNormMethod == 3
		enc = vl_fisher(data', means, covariances, priors, 'Verbose', 'Improved');
	end

	if nNormMethod == 4
		enc = vl_fisher(data', means, covariances, priors, 'Verbose', 'Fast');
	end
	
	szFPOutputFN = sprintf('%s.fve', szFPInputFN);
	dlmwrite(szFPOutputFN, enc');
	
 	quit;  % quit matlab since it is used to run within PHP 	
end
