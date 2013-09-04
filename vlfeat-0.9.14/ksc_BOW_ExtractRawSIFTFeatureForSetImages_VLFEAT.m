% Extract SIFT-based feature using VLFEAT for a SET of images
% This script must be put in vlfeat dir
% szFPInputFN - List of input and output files in csv format (delimiter = SPACE)
% run('toolbox/vl_setup'); --> must call to init env
% nSize: sampling step
% nKPDetector: 0-DOG, 1-DENSE, 2-PHOW

% Last update: Jul 07, 2012
% Only save keypoints that are not empty (all 128 values = 0)
% Copy parts of ksc_BOW_ExtractSIFTFeature_VLFEAT

function ksc_BOW_ExtractRawSIFTFeatureForSetImages_VLFEAT(szFPInputFN, nKPDetector, nSize)
	
	fprintf(1, 'Loading csv data file ...\n');
	fid = fopen(szFPInputFN);
	arList = textscan(fid, '%s %s');
	fclose(fid);

	run('toolbox/vl_setup'); % init env

	nNumFiles = size(arList{1}, 1);
	% Copy parts of ksc_BOW_ExtractSIFTFeature_VLFEAT
	for j=1:nNumFiles
		szFPKeyFrameInputFN = arList{1}{j};
		szFPFeatureOutputFN = arList{2}{j};

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

		fprintf(1, '###%d/%d. Processing file [%s]...\n', j, nNumFiles, szFPKeyFrameInputFN);
		
		im = imread(szFPKeyFrameInputFN);
			
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
	
	    fOutput = fopen(szFPFeatureOutputFN, 'wt');    
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
				% szVector = sprintf('%s-1 -1', szTmp); % frames has 3 output values for one keypoint - VLFEAT 0.9.8
				szVector = sprintf('%s-1', szTmp); % frames has 2 output values for one keypoint - VLFEAT 0.9.14
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
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%	
	end
 	quit;  % quit matlab since it is used to run within PHP 	
end
