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

require_once 'CurlHttpRequest.php';
require_once 'DocumentUrl.php';
require_once 'MimeTypeMapper.php';

/**
 * Represents a HTTP proxy request. 
 * Dependency: cURL
 * @see http://php.net/book.curl.php
 * 
 * @uses Tx_ExtBibsonomyCsl_Url
 *
 * @package ext_bibsonomy_csl
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author Sebastian Böttger
 */
class CurlHttpRequestProxy extends CurlHttpRequest {

    
    /**
     * Constructor
     * 
     * @param Tx_ExtBibsonomyCsl_BibsonomyDocumentUrl $url 
     */
    public function __construct(DocumentUrl $url) {
        
        parent::__construct($url);
    }
    
    public function send() {
        $this->curl();
    }
    
    /**
     * Executes the an request with cURL.
     * @return Tx_ExtBibsonomyCsl_CurlHttpResponse the response 
     */
    protected function curl() {

        $requestUrl = $this->url->getUrlWithoutAuth();
        
        $mime = MimeTypeMapper::getMimeType($this->url->getFileName());
        
        if ($this->url->getPreview() !== false) {
            header("Cache-Control: no-transform,public,max-age=604800,s-maxage=604800");
            header("Content-Type: image/jpeg");
        } else {
            header("Content-Type: $mime");
        }
        
        //Download ?
        if($this->url->getPreview() === false) {
            header("Content-Disposition: attachment; filename=" . $this->url->getFileName());
        }
        
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->url->getAuthUser() . ":" . $this->url->getAuthPass());
        
        curl_exec($ch);
        curl_close($ch);
    }
}
