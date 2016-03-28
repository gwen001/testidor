<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class Utils
{
	const TMP_DIR = '/tmp/';
	const T_SHELL_COLORS = array(
		'black' => '30',
		'blue' => '34',
		'green' => '32',
		'cyan' => '36',
		'red' => '31',
		'purple' => '35',
		'brown' => '33',
		'light_grey' => '37',
		'dark_grey' => '30',
		'light_blue' => '34',
		'light_green' => '32',
		'light_cyan' => '36',
		'light_red' => '31',
		'light_purple' => '35',
		'yellow' => '33',
		'white' => '37',
	);


	public static function help( $error='' )
	{
		if( is_file('README.md') ) {
			$help = file_get_contents( 'README.md' )."\n";
			preg_match_all( '#```(.*)```#s', $help, $matches );
			if( count($matches[1]) ) {
				echo trim($matches[1][0])."\n\n";
			}
		} else {
			echo "No help found!\n";
		}

		if( $error ) {
			echo "Error: ".$error."!\n";
		}

		exit();
	}


	public static function isEmail( $str )
	{
		return filter_var( $str, FILTER_VALIDATE_EMAIL );
	}


	public static function _print( $str, $color )
	{
		echo "\033[".self::T_SHELL_COLORS[$color]."m".$str." \033[0m";
	}


	public static function _array_search( $array, $search, $ignore_case=true )
	{
		if( $ignore_case ) {
			$f = 'stristr';
		} else {
			$f = 'strstr';
		}

		if( !is_array($search) ) {
			$search = array( $search );
		}

		foreach( $array as $k=>$v ) {
			foreach( $search as $str ) {
				if( $f($v, $str) ) {
					return $k;
				}
			}
		}

		return false;
	}
}

?>
