<head>
<title>NII-UIT-Violent Scene Detection Demo@2014</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=1" />
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
</head>
<body>

<!-- Start of Google Analytics Code -->
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-1229912-2";
urchinTracker();
</script>
<!-- End of Google Analytics Code -->

<!-- Start of StatCounter Code -->
<script type="text/javascript" language="javascript">
var sc_project=1575092; 
var sc_invisible=1; 
var sc_partition=14; 
var sc_security="433ecd94"; 
</script>

<script type="text/javascript" language="javascript" src="http://www.statcounter.com/counter/counter.js"></script><noscript><a href="http://www.statcounter.com/" target="_blank"><img  src="http://c15.statcounter.com/counter.php?sc_project=1575092&amp;java=0&amp;security=433ecd94&amp;invisible=1" alt="free hit counter script" border="0"></a> </noscript>
<!-- End of StatCounter Code -->



<?php
ini_set("max_execution_time", "60"); // 60 secs

printf("<H2>NII-UIT-VSD DEMO</H2>\n");
// Form upload

$nAction = 0;
if(isset($_REQUEST['vAction']))
{
	$nAction = $_REQUEST['vAction'];
}

if(!$nAction)
{
	printf("<H3>Upload an image to search</H3>");
	printf("<FORM METHOD='POST' ENCTYPE='multipart/form-data' TARGET=_blank>\n");
	printf("<P>Input File<BR>\n");
	printf("<INPUT TYPE='FILE'  NAME='vImageName' /><BR>\n");

	printf("<P>Config<BR>\n");
	printf("<SELECT NAME='vFeatureExt'>\n");
	printf("<OPTION VALUE='nsc.cCV_GRAY.g4.q59.g_lbp'>Texture</OPTION>\n");
	printf("<OPTION VALUE='nsc.cCV_HSV.g4.q8.g_ch'>Color</OPTION>\n");
	printf("</SELECT>\n");

	printf("<P>\n");	
	printf("<INPUT TYPE='SUBMIT' VALUE='Upload' />\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset' />\n");
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1' />\n");
	printf("</FORM>\n");

	printf("<H3> OR Paste an image URL</H3>\n");
	printf("<FORM METHOD='GET' TARGET=_blank>\n");
	printf("<P>Input URL<BR>\n");
	printf("<INPUT TYPE='TEXT'  NAME='vImgURL' SIZE='100'/><BR>\n");

	printf("<P>Config<BR>\n");
	printf("<SELECT NAME='vFeatureExt'>\n");
	printf("<OPTION VALUE='nsc.cCV_GRAY.g4.q59.g_lbp'>Texture</OPTION>\n");
	printf("<OPTION VALUE='nsc.cCV_HSV.g4.q8.g_ch'>Color</OPTION>\n");
	printf("</SELECT>\n");
	
	printf("<P>\n");

	printf("<INPUT TYPE='SUBMIT' VALUE='Upload' />\n");
	printf(" <INPUT TYPE='RESET' VALUE='Reset' />\n");
	printf("<INPUT TYPE='HIDDEN' NAME='vAction' VALUE='1' />\n");
	printf("</FORM>\n");

	printf("<H4> Some URLs for testing</H4>\n");
	printf("http://g-ecx.images-amazon.com/images/G/01/dvd/universal2009/Fighting_2_Large.jpg<BR>");
	printf("http://g-ecx.images-amazon.com/images/G/01/dvd/universal2009/Fighting_2_Large.jpg<BR>");

	exit();
}

$szInputImageDir = "upload";

$szFeatureExt = $_REQUEST['vFeatureExt'];
if(isset($_REQUEST['vImgURL']))
{
	$szInputURL = $_REQUEST['vImgURL'];
	$ext = pathinfo($szInputURL, PATHINFO_EXTENSION);
	if($ext != "jpg")
	{
		printf("<H2>Error: Only support jpg file!\n");
		exit();
	}

	$szURL = sprintf("http://per900c.hpc.vpl.nii.ac.jp/users-ext/ledduy//www/kaori-secode-vsd2014/ksc-web-Demo-NTDV.php?vImgURL=%s&vFeatureExt=%s"
	, $szInputURL, $szFeatureExt);

	$szIPAddr = $_SERVER['REMOTE_ADDR'];
	$szTime = date("H:i:s, j-m-y");
	$fFile = fopen("upload/Demo-VSD.log", "a+t");

	if($fFile)
	{
		$szOutput = sprintf("%s ### %s ### %s\n", $szTime, $szIPAddr, $szInputURL);
		fwrite($fFile, $szOutput);
		fclose($fFile);
	}

}
else
{
	$ext = pathinfo($_FILES['vImageName']['name'], PATHINFO_EXTENSION);
	if($ext != "jpg")
	{
		printf("<H2>Error: Only support jpg file!\n");
		exit();
	}

	$szImageName = str_replace(".jpg", "", $_FILES['vImageName']['name']); 

	if(isset($_FILES['vImageName']))
	{
		$uploadfile = sprintf("%s/%s.jpg", $szInputImageDir, $szImageName);
		move_uploaded_file($_FILES['vImageName']['tmp_name'], $uploadfile);
	}

	$szURL = sprintf("http://per900c.hpc.vpl.nii.ac.jp/users-ext/ledduy//www/kaori-secode-vsd2014/ksc-web-Demo-NTDV.php?vImgName=%s&vFeatureExt=%s"
	, $szImageName, $szFeatureExt);


	$szIPAddr = $_SERVER['REMOTE_ADDR'];
	$szTime = date("H:i:s, j-m-y");
	$fFile = fopen("upload/Demo-VSD.log", "a+t");

	if($fFile)
	{
		$szOutput = sprintf("%s ### %s ### %s\n", $szTime, $szIPAddr, $szImageName);
		fwrite($fFile, $szOutput);
		fclose($fFile);
	}
}
//printf("[%s - %s]<BR>\n", $szImageName, $_FILES['vImageName']['tmp_name']);

printf("<!-- %s -->\n", $szURL);
$szOutput = file_get_contents($szURL);

if($szOutput != "")
{
	$arTmp = explode("HIDEALL", $szOutput);
	if(sizeof($arTmp) > 1)
	{
		$szOutput1 = trim($arTmp[1]);
	}
	else
	{
		$szOutput1 = trim($arTmp[0]);
	}
	printf("%s", $szOutput1);
}
else
{
	printf("<H3>Server error!\n");
}

?>

</body>
