<?php
/**
 *	File handler for phpuush
 *
 *	@author Blake <blake@totalru.in>
 *	@author PwnFlakes <pwnflak.es>
 *	@author Westie <westie@typefish.co.uk>
 *
 *	@version: 0.1
 */


/**
 *	We'll just load our file.
 */
$pUpload = new Upload();
$pUpload->loadAlias($_SEO[0]);

if(!isset($pUpload->id) || $pUpload->is_deleted)
{
	header("Location: /");
	return;
}


$aHeaders = $pFunctions->getHeaders();

if(isset($aHeaders["If-Modified-Since"]))
{
	$iCachedModificationDate = strtotime($aHeaders["If-Modified-Since"]);

	if($iCachedModificationDate == filemtime($pUpload->local_path))
	{
		$pUpload->incrementViews();

		header("Not Modified: Use browser cache", true, 304);
		return;
	}
}


/**
 *	Return things to the server...
 */
if((isset($_GET["height"]) || isset($_GET["width"])) && substr($pUpload->mime_type, 0, 6) != "image/")
{
	return;
}
elseif(isset($_SEO[1]))
{
	$sCacheItem = $aGlobalConfiguration["files"]["upload"]."/cache/geshi-".strtolower($_SEO[1])."-".$pUpload->file_hash.".html";
	$sRender = null;

	if(!file_exists($sCacheItem))
	{
		$sContents = file_get_contents($pUpload->local_path);

		$pGeshi = new Geshi($sContents, $_SEO[1]);
		$sRender = $pGeshi->parse_code();

		file_put_contents($sCacheItem, $sRender);
	}
	else
	{
		$sRender = file_get_contents($sCacheItem);
	}

	header("Cache-Control: public");
	header("Last-Modified: ".date("r", filemtime($sCacheItem)));
	header("Content-Length: ".filesize($sRender));
	header("Content-Type: text/html");
	header("Content-Transfer-Encoding: binary");
	header("Content-MD5: ".md5_file($sCacheItem));
	header("Content-Disposition: inline; filename=".$pFunctions->quote($pUpload->file_name));

	echo $sRender;
}
else
{
	header("Cache-Control: public");
	header("Last-Modified: ".date("r", filemtime($pUpload->local_path)));
	// header("Content-Length: {$pUpload->file_size}");
	// header("Content-Type: {$pUpload->mime_type}");
	header("Content-Transfer-Encoding: binary");
	header("Content-MD5: {$pUpload->file_hash}");
	header("Content-Disposition: inline; filename=".$pFunctions->quote($pUpload->file_name));

	$rPointer = $pUpload->getFile();
	$sContents = "";
	$sOut = "";
	while(($sContents = fread($rPointer, 1024)))
	{
		// echo $sContents;
		// flush();
		$sOut .= $sContents;
	}
	$image = "<img class='img' src=\"data:{$pUpload->mime_type};base64,".base64_encode($sOut)."\" />";

	$sData = "<html>
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400' rel='stylesheet' type='text/css'>
	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
	<style>
	html, body {margin: 0; padding: 0; height: auto; width: 100%; background: #F9F9F9; position: relative; font-family: 'Open Sans', sans-serif; text-align: center; }
	.img { margin-top: 30px; margin-bottom: 90px; }
	footer {height: 50px; text-align: center; width: 100%; margin-top: 20px; position: absolute; bottom: 20px;}
	a:active, a:hover, a {color: #000; text-decoration: none;}
	@media screen and (max-width: 568px) {
			.img {max-width: 100%; margin-top: 0; margin-bottom: 70px;}
			footer {margin-top: 10px; bottom: 10px;}
	}
	</style><body>".$image."
	<footer>
		<img src='http://pbs.twimg.com/profile_images/571147898500603905/WS3SXytK.jpeg' style='display: inline-block; height: 50px; width: 50px; border-radius: 100%; margin-right: 10px;'/>
		<ul style='list-style-type: none; margin: 0; padding: 0; font-size: 0px; height: 50px; display: inline-block; vertical-align: top; text-align: left;'>
			<a href='http://fanara.me'><li style='line-height: 22px; font-size: 16px; font-weight: 400; color: #1F1F21;'>Daniel Fanara</li><a/>
			<a href='http://twitter.com/fanieldanara'><li style='font-size: 12px; line-height: 17px; font-weight: 300; font-style: italic; color: #8E8E8E;'>@fanieldanara</li></a>
		</ul>
	</footer>
	</body></html>";
	header("Content-Length: " + strlen($sData));
	header("Content-Type: text/html");

	echo $sData;
	fclose($rPointer);
}

$pUpload->incrementViews();
return;
