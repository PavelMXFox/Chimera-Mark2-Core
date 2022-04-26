<?php 
$codes=[
    "400"=>"Bad request",
    "401"=>"Unauthorized",
    "403"=>"Forbidden",
    "404"=>"Not found",
    "405"=>"Method Not Allowed",
    "500"=>"Internal server error",
    "501"=>"Not Implemented",
    
];

$jsons=[
    "api"
];


$code=$_GET["code"];


if (array_key_exists("FOX_REWRITE", $_SERVER) && $_SERVER["FOX_REWRITE"] != "yes")
{
    $prefix = ($_SERVER["CONTEXT_PREFIX"]."index.php/");
} else {
    $prefix = ($_SERVER["CONTEXT_PREFIX"]);
}

$prefix = preg_replace(["![/]+!","![\.]+!"], ["\/","\."], $prefix);
$req=(preg_replace("/".$prefix."/", '', $_SERVER["REQUEST_URI"]));
$req = explode("/",explode("?", $req, 2)[0]);

if (array_search($req[1], $jsons)!==false) {
    print '{"error":{"code":'.$code.',"message":"'.(array_key_exists($code, $codes)?$codes[$code]:"Server error").'"}}';
    exit;
}

?>

<html  class=login>

<body  class=login style="
    background-image: url(/static/theme/chimera/img/chimera_logo.svg);
    min-width: inherit;
    min-height: 100%;
    background-repeat: no-repeat;
    background-size: contain;
    background-position-x: center;
    background-position-y: center;
    background-color: #150007;">

<div style=' text-align: center; width: 100%; heigth: 100%;'>

</div>

<div style=" 
	border: 2px solid red;
   background-color: rgba(0, 0, 0, 0.85);
	
	margin: 0; 
	padding: 70 0 0 0; 
	position: absolute;
	left: calc(50% - 250px);
	display: block; 
	top: calc(50% - 75px); 
	text-align: center; 
	width: 500px;
	height: 150px;
	vertical-align: center; 
	font-family: 'Fira Mono', monospace; 
	font-weight: 200; 
	font-size: 32px; color: #FF3500; margin: 1%">
<?php 
print "ERROR: $code<br/>";
if (array_key_exists($code, $codes)) {
    print $codes[$code];
}
?></div>

</body>
</html>