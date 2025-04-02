<?php
namespace Core;
class Geolocalizacao{
	public static function calcDist($lat_A, $long_A, $lat_B, $long_B){
	  $distance = sin(deg2rad($lat_A)) * sin(deg2rad($lat_B)) + cos(deg2rad($lat_A))*cos(deg2rad($lat_B))*cos(deg2rad($long_A - $long_B));
	  $distance = (rad2deg(acos($distance))) * 69.09;
	  return $distance;
	} 
	public static function distanciaKm($lat1, $lon1, $lat2, $lon2) {
		$lat = deg2rad($lat2-$lat1);
		$lon = deg2rad($lon2-$lon1);
		$t = sin($lat/2) * sin($lat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *sin($lon/2) * sin($lon/2);
		$l = 2 * atan2(sqrt($t), sqrt(1-$t));
		$result = 6371 * $l;
		return $result;
	}
	// You can find the original code in http://zips.sourceforge.net/#dist_calc
	public static function distancia(float $lat1,float $lon1,float $lat2,float $lon2,string $unit){

	    $theta = $lon1-$lon2;
	    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	    $dist = acos($dist);
	    $dist = rad2deg($dist);
	    $miles = $dist * 69.09;
	    $unit = strtoupper($unit);

	    if($unit == 'K'){
	        return ($miles * 1.609344);
	    } else if ($unit == 'N'){
	        return ($miles * 0.8684);
	    } else {
	        return $miles;
	    } 
	}
}