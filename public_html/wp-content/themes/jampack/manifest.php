<?php
header( 'Content-Type: application/json' );
function get_array_from_query() {
	$resultArray = [];
	if (isset($_GET['data'])) {
		$jsonString = urldecode($_GET['data']);
		$resultArray = json_decode($jsonString, true);
	}
	return $resultArray;
}
$manifest_defaults = [
	"name"             => "Jampack",
	"short_name"       => "Jampack",
	"start_url"        => "/",
	"display"          => "standalone",
	"background_color" => "#FF6A4B",
	"theme_color"      => "#FF6A4B",
	"icons"            => [
		[
			"src"   => "/wp-content/themes/jampack/assets/img/logo.svg",
			"sizes" => "any",
			"type"  => "image/svg+xml"
		]
	]
];
$manifest_output = array_replace_recursive($manifest_defaults, get_array_from_query());
echo json_encode( $manifest_output, JSON_PRETTY_PRINT );

