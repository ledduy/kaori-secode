<?php
/*
 * Lam Quang Vu
 * 28 Aug 2014
 * Extract keyframe from Shot, lay cach deu step N keyframe
 * Note: run PHP from : /net/per900c/raid0/ledduy/usr.local/bin/php 
*/

require_once "ksc-AppConfig.php";
$numPar = 9;
if($argc!=$numPar)
{
	printf("Number of params [%s] is incorrect [%d]\n", $argc,$numPar);
	//printf("Usage %s <FilmPath> <FileName> <extension> <SBFilenames> <NumberofKeyframePerShot> <MaxShot> <SetGroup>\n", $argv[0]);
	printf("Usage %s <1.RootDirIn> <2.RootDirOut> <3.ProjectName> <4.List file> <.5NumberofKeyframePerShot> <6.VideoNumber> <7.startshot> <8.endshot>\n", $argv[0]);
	exit();
}

$szRootDirIn = $argv[1]; ///net/sfv215/export/raid4/ledduy/lqvu-Experiments/2012-lqvu-MediaEval/OUTPUT/mediaeval2012_org
$szRootDirOut = $argv[2];
$szPrjName = $argv[3];//"keyframe-5";
$szListFile = $argv[4]; //dev11.lst
$KFPS = (int)$argv[5]; // Number of Keyframes will be extracted per one Shot
//$TestMax = (int)$argv[6]; // Max number of Shot will be extracted keyframes
$VideoNumber= (int)$argv[6]; // Vi du nhu list co 14 video, thi cho video thu 0,1,2,3,4,5 den 13
$nStart = (int)$argv[7]; // shot bat dau
$nEnd = (int)$argv[8]; // shot ket thuc
$KFPerSecond = 25;


// ghi chu lai lan chay cua file nay
function CutExt($strFullName)
{
	$strtmp = explode(".",$strFullName);
	$count = count($strtmp);
	$strExt = $strtmp[$count-1];
	$strResult = substr($strFullName,0,-1*(strlen($strExt)+1));
	return $strResult;
}
function CutPath($strFullName)
{
	$strtmp = explode("/",$strFullName);
	$count = count($strtmp);
	$strExt = $strtmp[$count-1];
	return $strExt;
}
//"/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/bin-vsd2014";



// Load FFMPEG extension
ini_set('max_execution_time', 0);
$extension = "ffmpeg";
$extension_soname = $extension . "." . PHP_SHLIB_SUFFIX;
$extension_fullname = PHP_EXTENSION_DIR . "/" . $extension_soname;
if (!extension_loaded($extension)) {
	dl($extension_soname) or die("Can't load extension $extension_fullname\n");
}

$strzErro = "/net/per610a/export/das11f/ledduy/mediaeval-vsd-2014/code/temp/lqvuLog/ExtractKFErrors.log";
$ferr = fopen($strzErro,"a");
$errorLog = 0;

$szLSTfile  = sprintf("%s/metadata/%s",$szRootDirIn,$szListFile);
//printf("%s\n",$szLSTfile);

$nFilms = loadListFile($arrListFilmname,$szLSTfile);
//VSD11_13 #$# movie-KillBill1-2003-dvd2006-MediaEval #$# test11

$aLine1 = $arrListFilmname[0];
$strtmp = explode("#$#",$aLine1);
$szVideoID = trim($strtmp[0]);
$szVideoName = trim($strtmp[1]);
$szVideoGroupSet = trim($strtmp[2]);
$szGroupSet = CutExt($szListFile);
//Tao thu muc keyframe-5
$szTmpKF = sprintf("%s/%s",$szRootDirOut,$szPrjName);
if (!file_exists($szTmpKF))
{
	makeDir($szTmpKF);
}

//ghi log file
$szRootLogDir = $szTmpKF;

$szScriptName = CutExt(CutPath($argv[0]));
printf("%s",$szScriptName);
$today = sprintf("%s",date("Y-m-d_H-i-s"));
// Tham so chon gan vao ten file log
$szSetOut = CutExt(CutPath($argv[4]));

$szLogname = sprintf("%s/%s.%s.log",$szRootLogDir,$szScriptName,$szSetOut);
$fLogCode = fopen($szLogname,"a");
$strtmp = sprintf("Running time: %s\n",$today);
fwrite($fLogCode,$strtmp);
//printf("test1");
for ($i=0;$i<$numPar;$i++)
{
	$strtmp = sprintf("Parameter %d: %s\n",$i,$argv[$i]);
	printf("%s",$strtmp);
	fwrite($fLogCode,$strtmp);
}
fclose($fLogCode);
// ket thuc ghi chu


//Tao thu muc keyframe-5/dev11
$szTmpKF = sprintf("%s/%s/%s",$szRootDirOut,$szPrjName,$szGroupSet);
if (!file_exists($szTmpKF))
{
	makeDir($szTmpKF);
}
//Tao thu muc metadata
$szOutPutPRGfiles = sprintf("%s/metadata",$szRootDirOut);
if (!file_exists($szOutPutPRGfiles))
{
	makeDir($szOutPutPRGfiles);
}
//Tao thu muc metadata/keyframe-5
$szOutPutPRGfilesMetaKF = sprintf("%s/metadata/%s",$szRootDirOut,$szPrjName);
if (!file_exists($szOutPutPRGfilesMetaKF))
{
	makeDir($szOutPutPRGfilesMetaKF);
}
//Tao thu muc metadata/keyframe-5/dev11
$szOutPutPRGfilesMetaKFPRG = sprintf("%s/metadata/%s/%s",$szRootDirOut,$szPrjName,$szGroupSet);
if (!file_exists($szOutPutPRGfilesMetaKFPRG))
{
	makeDir($szOutPutPRGfilesMetaKFPRG);
}

$CreateTime = date('d_m_Y_h_i_s_a', time());
$szLOGDir = sprintf("%s/LOG/ExtractKeyframe/%s_%02d_ExtractKeyframe",$szRootLogDir,$szListFile,$VideoNumber);
if (!file_exists($szLOGDir))
{
	makeDir($szLOGDir);
}
$CreateTime = date('d_m_Y_h_i_s_a', time());
$szLogAll = sprintf("%s/LOG/ExtractKeyframe/LogAll_%s.csv",$szRootLogDir,$szPrjName);
$flogAll = fopen($szLogAll,"a+");

$szOutputKFSurvey = sprintf("%s/%s/%s.keyframe.csv",$szRootDirOut,$szPrjName,$szGroupSet);
$fkeyframe = fopen($szOutputKFSurvey,"a");

$strLogAll = sprintf("VideoID,VideoName,NumShot,TotalKeyframe,StartTime,EndTime,Duration,Error\n");
fwrite($flogAll,$strLogAll);

//$szListfilename = sprintf("%s/%s.lst",$szOutPutPRGfilesMetaKF,$szGroupSet);
//$flistfile = fopen($szListfilename,"a+");

$aLine = $arrListFilmname[$VideoNumber];
//foreach ($arrListFilmname as $aLine)
//{
	$startTime = date('m/d/Y h:i:s a', time());
	$mtime = microtime();
   	$mtime = explode(" ",$mtime);
   	$mtime = $mtime[1] + $mtime[0];
   	$starttime = $mtime; 
	
	$strtmp = explode("#$#",$aLine);
	$szVideoID = trim($strtmp[0]);
	$szVideoName = trim($strtmp[1]);
	$szVideoPartition = trim($strtmp[2]);
	$szGroupSet = CutExt($szListFile);
	//printf("%s - %s - %s\n",$szVideoID,$szVideoName,$szPatition);
	
	$szFileName = $szVideoID; // Movie filename
	
	// Sua lai ngay 14/8 vi SB file luu them 1 cap PrjName
	$szSBFileName =  sprintf("%s/sbinfo/%s/%s/%s.sb",$szRootDirIn,$szPrjName,$szGroupSet,$szVideoID);//SB filename
	
	
	//loadListFile($arrSB,$szSBFileName);
	
	// Luu KF ...mediaeva2012/keyframe-5/dev11
	$szOutputKFRoot = sprintf("%s/%s/%s",$szRootDirOut,$szPrjName,$szGroupSet);
	// Luu PRGZ file ...mediaeval2012/metadata/keyframe-5/dev11
	
	$szOutputPRGRoot = sprintf("%s/metadata/%s/%s",$szRootDirOut,$szPrjName,$szGroupSet);
	// Luu LST file ... mediaeval2012/metadata/keyframe-5
	
	$szOutputListfileRoot = sprintf("%s/metadata/%s",$szRootDirOut,$szPrjName);
	
	//printf("Keyframe - %s\n",$szOutputKFRoot);
	//printf("PRG - %s\n",$szOutputPRGRoot);
	//printf("List file - %s\n",$szOutputListfileRoot);

	// FilePath of Video & SB
	$szFPVideoFN = sprintf("%s/video/%s/%s.mpg",$szRootDirIn,$szVideoPartition,$szVideoName);
	$szSBVideoFN = sprintf("%s/sbinfo/%s/%s/%s.sb",$szRootDirIn,$szPrjName,$szGroupSet,$szVideoID);
	
	// Make directory to store Keyframes
	$szOutputDir = sprintf("%s/%s",$szOutputKFRoot,$szVideoID);
	if (!file_exists($szOutputDir))
	{
		makeDir($szOutputDir);
	}
	// Tao thu muc lýu LOG file
	
	
	$szPrgExtension = ".prgz";
	$szOldPrgExtension = ".prg";
	$szLogExtension = ".log";
	$szPrgxExtension = ".prgx";
	
	$szPRFFileName = sprintf("%s/%s%s",$szOutputPRGRoot,$szFileName,$szPrgExtension);
	$szPRGXFileName = sprintf("%s/%s%s",$szOutputPRGRoot,$szFileName,$szPrgxExtension);
	$szLogVideoFN = sprintf("%s/%s%s",$szLOGDir,$szFileName,$szLogExtension);
	$szOldPRGFileName = sprintf("%s/%s%s",$szOutputPRGRoot,$szFileName,$szOldPrgExtension);
	
	$fprg = fopen($szPRFFileName,"a");
	$fprgx = fopen($szPRGXFileName,"a");
	$flog = fopen($szLogVideoFN,"a");
	$foldprg = fopen($szOldPRGFileName,"a");
	
	$objVideo = new ffmpeg_movie ($szFPVideoFN);
	
	printf("duration = %s seconds\n", $objVideo->getDuration());
	printf("frame count = %s\n", $objVideo->getFrameCount());
	printf("frame rate = %0.3f fps\n", $objVideo->getFrameRate());
	printf("comment = %s\n", $objVideo->getComment());
	printf("title = %s\n", $objVideo->getTitle());
	printf("author = %s\n", $objVideo->getAuthor());
	printf("copyright = %s\n", $objVideo->getCopyright());
	printf("get bit rate = %d\n", $objVideo->getBitRate());
	
	$totalFrame = (int)$objVideo->getFrameCount();
	$framerate = (int)$objVideo->getFrameRate();
	// Write to LST file
	//$strtmpfile = sprintf("%s #$# %s #$# %s\n",$szVideoID,$szVideoName,$szPartition);
	//fwrite($flistfile,$strtmpfile);
	
	
	$nQuality = 100; // 100 --> Best
	$count = 0;
	$countKF = 0; // Count the number of KF
	
	$nSBLine = loadListFile($arrShotLine, $szSBFileName);
	// bo 2 dong dau cua SB file, ko dung (VideoName va VideoID)
	if ($nStart < 2) 
		$nStart = 2;
	if ($nEnd > $nSBLine)
		$nEnd = $nSBLine-1;	
	
	//printf("%d - %d - %d\n",$nSBLine,$nStart,$nEnd);
	
	for ($xi = $nStart;$xi<=$nEnd;$xi++)
	{
		$line = $arrShotLine[$xi];
		$count = $count + 1;
//		if ($count <= $TestMax)
		//{
			//Cu: movie-Eragon-2006-dvd2007-MediaEval.Shot0 #$# 0 #$# 85
			//Moi: VSD11_1.shot1_1 #$# 0 #$# 42
			//printf("%s\n",$line);
			$strtmp = sprintf("%s\n",$line);
			fwrite($flog,$strtmp);
			$tmp = sscanf($line, "%s #$# %s #$# %s",$tmpVideoID,$tmpStart,$tmpDuration);
			$VideoID = trim($tmpVideoID);
			$start = (int)trim($tmpStart);
			if ($start==0)
				$start=1;
			$duration = (int)trim($tmpDuration);
			printf("%s #$# %d #$# %d\n", $VideoID, $start, $duration);
			
			//echo "Line ". $count." ". $start." ". $duration. "<br>";
			if (($duration > $KFPS)&&($duration<=$framerate)) //truong hop tu 5 den 25kf
			{    // So luong KF lon hon so luong KF can lay
				//$szNumFrame = floor($duration*$KFPS/$KFPerSecond);
				//for ($i=1;$i<=$szNumFrame;$i++)
				//{
				//	$keyFrameID = round($start + $KFPerSecond*$i/$KFPS - $KFPerSecond/(2*$KFPS));
				$strtmp = sprintf("%s - xu ly tu 5 den 25, so kf = %d\n",$tmpVideoID,$duration);
				printf("%s",$strtmp);
				fwrite($flogAll,$strtmp);
 				
				$szNumFrame = $KFPS;
				$szInterval = $duration/$KFPS;
				for ($i=1;$i<=$szNumFrame;$i++)
				{
					$keyFrameID = round($start + $szInterval*$i - $szInterval/2 );
					if ($keyFrameID > ($totalFrame-2))
						break;
					//printf("%d\n",$keyFrameID);
					$objFrame = $objVideo->getFrame($keyFrameID);
					//if ($objFrame <> Null)
					//{
						$objGDFrame = $objFrame->toGDImage();
					
					
						$countKF = $countKF + 1;
						// ten file de luu thanh file JPG
						$filename = sprintf("%s/%s.RKF_%d.Frame_%d.jpg",$szOutputDir,$VideoID,$i,$keyFrameID);
						//printf("%s\n",$filename);
						
						//ten file de luu vao prgz file
						$orgFileName = sprintf("%s.RKF_%d.Frame_%d",$VideoID,$i,$keyFrameID);
						fwrite($foldprg,$orgFileName);
						fwrite($foldprg,"\n");
						
						imagejpeg($objGDFrame,$filename,$nQuality);
						if (!file_exists($filename)) {
							//printf("Not Save %s.RKF_%d.Frame_%d to file\n",$VideoID,$i,$keyFrameID);
							$errorLog = 1;
							$strtmperr= sprintf("Not Save %s to file\n",$filename);
							fwrite($ferr,$strtmperr);
						}
					//else
						//printf("Save %s.RKF_%d.Frame_%d to file\n",$VideoID,$i,$keyFrameID);
						
					
						list($width, $height) = getimagesize($filename);
						$strprgz = sprintf("%s #$# %s #$# %s #$# %d #$# %d\n",$szVideoID,$VideoID,$orgFileName,$width,$height);
						fwrite($fprg,$strprgz);
						
						$strprgx = sprintf("%s #$# %d #$# %d\n",$orgFileName,$width,$height);
						fwrite($fprgx,$strprgx);
						unset($objFrame);
						unset($objGDFrame);
					//}
					//else
					//{
						//$strtmperr= sprintf("Can not extract %d\n",$keyFrameID);
						//fwrite($ferr,$strtmperr);
					//}
				}
 
				
			}
			elseif ($duration<$KFPS) //truong hop nho hon 5kf
			{
				//if ($start + $KFPS >= $totalFrame)
					//$start = $totalFrame - $KFPS -1;
				$strtmp = sprintf("%s - xu ly tu nho hon 5, so kf = %d\n",$tmpVideoID,$duration);
				printf("%s",$strtmp);
				fwrite($flogAll,$strtmp);
 				
				
				$szNumFrame = $duration; //$duration;
				$szInterval = 1;
				for ($i=1;$i<$szNumFrame;$i++)
				{
					$keyFrameID = round($start + $i -1);
					if ($keyFrameID > ($totalFrame-2))
						break;
					$objFrame = $objVideo->getFrame($keyFrameID);
					$objGDFrame = $objFrame->toGDImage();
					$countKF = $countKF + 1;
					
					$filename = sprintf("%s/%s.RKF_%d.Frame_%d.jpg",$szOutputDir,$VideoID,$i,$keyFrameID);
						
					//ten file de luu vao prgz file
					$orgFileName = sprintf("%s.RKF_%d.Frame_%d",$VideoID,$i,$keyFrameID);
					fwrite($foldprg,$orgFileName);
					fwrite($foldprg,"\n");
					
					imagejpeg($objGDFrame,$filename,$nQuality);
					if (!file_exists($filename)) {
						//printf("Not Save %s.RKF_%d.Frame_%d to file\n",$VideoID,$i,$keyFrameID);
						$errorLog = 1;
					}
					//else
						//printf("Save %s.RKF_%d.Frame_%d to file\n",$VideoID,$i,$keyFrameID);
					
					list($width, $height) = getimagesize($filename);
					$strprgz = sprintf("%s #$# %s #$# %s #$# %d #$# %d\n",$szVideoID,$VideoID,$orgFileName,$width,$height);
					fwrite($fprg,$strprgz);
					
					$strprgx = sprintf("%s #$# %d #$# %d\n",$orgFileName,$width,$height);
					fwrite($fprgx,$strprgx);
					
					unset($objFrame);
					unset($objGDFrame);
				}
 
			} else // truong hop lon hon 25 kf 
			{
				//printf("xu ly tu LON hon 25, so kf = %d\n",$duration);
				//printf("xu ly tu LON hon 25, so kf = %d\n",$duration);
		
				
				$szNumFrame = $duration; //$duration;
				//$szInterval = 5;
				for ($i=1;$i<=$szNumFrame;$i=$i+$KFPS)
				{
					$keyFrameID = round($start + $i -1);
					if ($keyFrameID > ($totalFrame-2))
						break;
					$objFrame = $objVideo->getFrame($keyFrameID);
					$objGDFrame = $objFrame->toGDImage();
					$countKF = $countKF + 1;
						
					$filename = sprintf("%s/%s.RKF_%d.Frame_%d.jpg",$szOutputDir,$VideoID,$i,$keyFrameID);
					
					//ten file de luu vao prgz file
					$orgFileName = sprintf("%s.RKF_%d.Frame_%d",$VideoID,$i,$keyFrameID);
					fwrite($foldprg,$orgFileName);
					fwrite($foldprg,"\n");
											
					imagejpeg($objGDFrame,$filename,$nQuality);
					if (!file_exists($filename)) 
						{
						//printf("Not Save %s.RKF_%d.Frame_%d to file\n",$VideoID,$i,$keyFrameID);
								$errorLog = 1;
						}
				//else
					//printf("Save %s.RKF_%d.Frame_%d to file\n",$VideoID,$i,$keyFrameID);
					
					list($width, $height) = getimagesize($filename);
					$strprgz = sprintf("%s #$# %s #$# %s #$# %d #$# %d\n",$szVideoID,$VideoID,$orgFileName,$width,$height);
					fwrite($fprg,$strprgz);
												
					$strprgx = sprintf("%s #$# %d #$# %d\n",$orgFileName,$width,$height);
					fwrite($fprgx,$strprgx);
			
					unset($objFrame);
					unset($objGDFrame);
				}

			}
	}
	printf("Total number of keyframes = %d\n",$countKF);
	$strtmp = sprintf("Total number of keyframes = %d\n",$countKF);
	fwrite($flog,$strtmp);
	
	$endTime = date('m/d/Y h:i:s a', time());
	
	$mtime = microtime();
   	$mtime = explode(" ",$mtime);
   	$mtime = $mtime[1] + $mtime[0];
   	$endtime = $mtime;
   	$totaltime = ($endtime - $starttime);
   	 
	
	$strLogAll = sprintf("%s,%s,%d,%d,%s,%s,%d,%d\n",$szVideoID,$szVideoName,$nSBLine-2,$countKF,$startTime,$endTime,$totaltime,$errorLog);
	fwrite($flogAll,$strLogAll);
	// ghi lai so shot va so keyframe
	$strtmpkt = sprintf("%s,%s,%s,%d,%d\n",$szVideoID,$szVideoName,$szPartition,$nSBLine-2,$countKF);
	fwrite($fkeyframe,$strtmpkt);
	
	fclose($foldprg);
	fclose($fprg);
	flcose($fprgx);
	fclose($flog);
	
	unset($objVideo);


//fclose($flistfile);
fclose($fkeyframe);
fclose($flogAll);
fclose($ferr);

?>

