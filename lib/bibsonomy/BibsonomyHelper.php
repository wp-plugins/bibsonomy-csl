<?php
/*
    This file is part of BibSonomy/PUMA CSL for WordPress.

    BibSonomy/PUMA CSL for WordPress is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    BibSonomy/PUMA CSL for WordPress is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with BibSonomy/PUMA CSL for WordPress.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Helper Class
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author Sebastian Böttger
 */
class BibsonomyHelper {
	
    
	public static function readCSLFolder($folder) {
		
		$arr = array();
		
		$handle = opendir($folder);
		
		if($handle) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$arr[] = self::getDataFromCSLFile($folder.$file);
				}
			}
		}
		
		return $arr;
	}
	
	
	/**
	 * Opens and reads a file into a variable. Then, the charset will be checked 
	 * and adjusted if necessary. It returns the variable.
	 * 
	 * @param string $filename name with path to the file
	 * @param string $charset = 'UTF-8' 
	 * @return string 
	 */
	public static function getDataFromCSLFile($filename, $charset = 'UTF-8') {
		
		if(floatval(phpversion()) >= 4.3) {
			$data = file_get_contents($filename);
		} 
		else {
			if(!file_exists($filename)) {
				return -3;
			}
			
			$filehandle = fopen($filename, 'r');
			
			if(!$filehandle) { 
				return -2;
			}
			
			$data = '';
			
			while( ! feof($filehandle) ) {
			
				$data .= fread($filehandle, filesize($sFilename));
			}
			
			fclose($filehandle);
		}
		
		if( ( $encoding = mb_detect_encoding($data, 'auto', true) ) != $charset) {
		
			$data = mb_convert_encoding($data, $charset, $encoding);
		}

		return $data;
	}
	
	/**
	 * replaces all characters that are not a-z, A-Z, 0-9 with 
	 * underscore character _
	 * 
	 * @param string $string
	 * @return string
	 */
	public static function replaceSpecialCharacters($string) {
		
		return preg_replace(array("![,./\ ]!", "![^a-zA-Z0-9]!"), "_", $string);  
	}
}

