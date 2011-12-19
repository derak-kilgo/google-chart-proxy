<?php
/**
 * Probably a good idea to provide some kind of security check here so external users
 * aren't using your local resources.
 * @uses php-curl - Sending the request to google.
 * @uses php-gd - Making the default image should the url fail or be empty.
 */
@ob_start();
getChart();
ob_end_flush();

/**
 * 
 * A curl wrapper for the chart api
 * which converts _GET data into _POST data.
 * Useful for getting around googles 2048 character limit on the chart api.
 * POST api has a 16K limit.
 * @see http://code.google.com/apis/chart/image/faq.html#url_length
 */	
function getChart()
{
	if(isset($_GET) && count($_GET)){
		ob_clean();//remove any header or error messages from the output buffer
		//example for testing
	   	// $url = "http://chart.apis.google.com/chart?cht=p&chs=250x100&chd=t:40,30,20&chl=Hispanic|NonHispanic|Incomplete";
	    		
		$getData = http_build_query($_GET);
		$url = 'http://chart.apis.google.com/chart';
		//$bytes = file_get_contents($url);
		
		$ch = curl_init();
	
	    curl_setopt ($ch, CURLOPT_URL, $url);
	    curl_setopt ($ch, CURLOPT_HEADER, 0);
	    
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS,$getData);
	    ob_start();
	
	    curl_exec ($ch);
	    curl_close ($ch);
	    $bytes = ob_get_contents();
	
	    ob_end_clean();
		
		header('Content-type: image/png');
		echo $bytes;
	}else{
		makeImage('notfound');
	}
}
/**
 * 
 * Create a 'default' image incase the chart doesn't return a valid image.
 */
function makeImage()
{
	$im = @imagecreatetruecolor(100, 100)
		    or die("Cannot Initialize new GD image stream");
	header('Content-type: image/png');
	$grey = imagecolorallocate($im, 206, 206, 206);
	$white = imagecolorallocate($im, 254, 254, 254);
	$black = imagecolorallocate($im, 0, 0, 0);
	imagefill($im,0,0,$black);
	imagefilledrectangle($im, 5, 5, 95, 95, $grey);
		
	imagelinethick($im,7,7,93,93,$white,5);
	imagelinethick($im,95,5,5,95,$white,5);
	
	$x = 25;
	//Text displayed on the image.
	imagestring($im, 5, $x, 30,  "Chart", $black);
	imagestring($im, 5, $x, 50,  "Empty", $black);
	imagepng($im);
	die();
}
/**
 * 
 * Draw lines thicker than 1 pixel with GD.
 * This way it works well only for orthogonal lines.
 * @see http://php.net/manual/en/function.imageline.php
 * @author PHP Documentation
 * 
 * @param gd_resource $image
 * @param float $x1 X coord. of top corner.
 * @param float $y1 Y coord. of top corner.
 * @param float $x2 X coord. of bottom corner.
 * @param float $y2 Y coord. of bottom corner.
 * @param gd_color_resource $color
 * @param integer $thick
 */    
function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
    /* this way it works well only for orthogonal lines
    imagesetthickness($image, $thick);
    return imageline($image, $x1, $y1, $x2, $y2, $color);
    */
    if ($thick == 1) {
        return imageline($image, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2) {
        return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
        round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
        round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
        round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
    );
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
}