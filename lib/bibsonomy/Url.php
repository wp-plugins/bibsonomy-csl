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
 * Class for URL representation
 *
 * @package ext_bibsonomy_csl
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author Sebastian Böttger
 */
class BibsonomyCsl_Url {
        
    /**
	 * 
     * 
     * @var array $components 
     */
    protected $components;
        
	
    /**
     * Builds an url from an associatice array like parse_url() returns.
     * This method is a reproduction from pecl_http method "http_build_url" (http://pecl.php.net/http)
     * 
     * The associative components array can have the following keys:
     * - schmeme (i.e. http)
     * - user 
     * - pass
     * - host (example.com)
     * - port 
     * - path (/the/path/)
     * - query (var1=value1&var2=value2)
     * - fragment (#chapter1)
     * 
     * @param array $components in form of an associative array like parse_url() returns 
     * @return string URL string
     */
    public static function http_build_url($components) {

            $url = $components["scheme"]."://";

            if($components["user"] && $components["pass"]) {
                    $url .= $components["user"].":".$components["pass"]."@";
            }
            $url .= $components["host"];

            if($components["port"]) {
                    $url .= ":".$components["port"];
            }
            if($components["path"]) {
                    $url .= $components["path"];
            }
            if($components["query"]) {
                    $url .= "?".$components["query"];
            }
            if($components["fragment"]) {
                    $url .= "#".$components["fragment"];
            }

            return $url;
    }
	
    /**
     * Constructor
	 * 
     * @param mixed array for FlexForm settings or null when using url string
     * @param string $url 
     */
    public function __construct($url = null) {
        
		$this->components = parse_url($url);
		
		if($this->components == false) {
			throw new Exception("Invalid URL.");
		}
        
    }
    
    
    /**
     * returns url string without user and passwort part
     * @return string 
     */
    public function getUrlWithoutAuth() {
        $components = $this->components;
        
        unset($components["user"]);
        unset($components["pass"]);
        
        return self::http_build_url($components);
    }
    
    
    /**
     * returns user name for authentication
     * @return string 
     */
    public function getAuthUser() {
        return $this->components["user"];
    }
    
    /**
     * returns password for authentication
     * @return string 
     */
    public function getAuthPass() {
        return $this->components["pass"];
    }
    
    /**
	 * returns whole url
     * @return string
     */
    public function getUrl() {

        return self::http_build_url($this->components);
    }
    
    /**
     *
     * @return string 
     */
    public function __toString() {
        
        return $this->getUrl();
    }

}
