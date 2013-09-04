% Extract SIFT-based feature using VLFEAT
% This script must be put in vlfeat dir
% szFPInputFN - input file name in pgm format
% szFPOutputFN - output file name, same format with vgg's affcov implementation o1
% run('toolbox/vl_setup'); --> must call to init env
% nSize: sampling step
% nKPDetector: 0-DOG, 1-DENSE, 2-PHOW

%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% Last update: May 11, 2011
% Adding option for including color info in phow
% Last update: Mar 09, 2011
% Adding keypoint location to output
% Check matlab license:  /usr/local/matlab/etc/lmstat -a | more 
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% Last update: Feb 21, 2011
% Only save keypoints that are not empty (all 128 values = 0)

function ksc_BOW_ExtractRawSIFTFeature_VLFEAT(szFPOutputFN, szFPInputFN, nKPDetector, nSize)
	
	run('toolbox/vl_setup'); % init env

	im = imread(szFPInputFN);
		
	if(nKPDetector == 0)
		[frames, descrs] = vl_sift(im2single(im));
	end

	if(nKPDetector == 1)
		[frames, descrs] = vl_dsift(im2single(im), 'step', nSize, 'fast');
	end
	
	if (nKPDetector == 2)	
		[frames, descrs] = vl_phow(im2single(im), 'step', nSize, 'fast', 1);
	end
	
	if (nKPDetector == 3)	
		[frames, descrs] = vl_phow(im2single(im), 'step', nSize, 'fast', 1, 'color', 1);
	end

    fOutput = fopen(szFPOutputFN, 'wt');    
    % first 2 rows: nNumDims (ie 128) and nNumKPs
    fprintf(fOutput, '%u\n', size(descrs,1));
    fprintf(fOutput, '%u\n', size(descrs,2));
    
    for i=1:size(descrs,2)

    	% Update Mar 09 --> variable frames stores information of (X, Y) 
    	szTmp = sprintf('');
    	for j=1:size(frames,1)
    		szTmp1 = szTmp;
    		szTmp = sprintf('%s%0.1f ', szTmp1, frames(j, i));
    	end
    	
    	szVector = sprintf('%s-1 -1 -1', szTmp);
		if(nKPDetector == 0)
			szVector = sprintf('%s-1', szTmp); % frames has 4 output values for one keypoint
		end

		if(nKPDetector == 1)
			szVector = sprintf('%s-1 -1 -1', szTmp); % frames has 2 output values for one keypoint
		end
	
		if (nKPDetector == 2)	
			szVector = sprintf('%s-1 -1', szTmp); % frames has 3 output values for one keypoint
		end
    	
		if (nKPDetector == 3)	
			szVector = sprintf('%s-1 -1', szTmp); % frames has 3 output values for one keypoint
		end
    	
    	nAllZero = 0; % check whether zero feature vector or not
		for j=1:size(descrs,1)
    		szTmp = szVector;
    		% o1 format has 5 params before 128 SIFT values
        	szVector = sprintf('%s %u', szTmp, descrs(j,i));
        	
        	if(descrs(j,i) ~= 0)
        		nAllZero = 1;
        	end	
		end
		if(nAllZero ~= 0)
			fprintf(fOutput, '%s\n', szVector);
		end
	end   
	fclose(fOutput);
 	quit;  % quit matlab since it is used to run within PHP 	
end
