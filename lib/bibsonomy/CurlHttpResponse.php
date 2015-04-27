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
 * Represents the response message of a HTTP request
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html
 *
 * @package bibsonomy_csl
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author Sebastian Böttger
 */
class CurlHttpResponse {
    
    /**
	 * body of http response message
     * @var string $body 
     */
    protected $body;
    
    /**
	 * header of http response message
     * @var string $header;
     */
    protected $header;
    
    /**
	 * Url of the previous request
     * @var string $requestUrl 
     */
    protected $requestUrl;
    
    /**
	 * Response code
     * @var string $responseCode 
     */
    protected $responseCode;
    
    /**
	 * Constructor
     *
     * @param string $requestUrl
     * @param string $body
     * @param string $header
     * @param string $responseCode
     * @param string $responseStatus 
     */
    public function __construct(
            $requestUrl,
            $body,
            $header,
            $responseCode
            ) {
        
        $this->requestUrl = $requestUrl;
        $this->body = $body;
        $this->header = $header;
        $this->responseCode = $responseCode;

    }
    
    
    /**
     * Returns the body of the response.
     * @return string 
     */
    public function getBody() {
        return $this->body;
    }
    
    /**
     * Returns the header of the response.
     * @return string 
     */
    public function getHeader() {
        return $this->header;
    }
    
    /**
     * Returns the URL previous request.
     * @return string 
     */
    public function getRequestUrl() {
        return $this->requestUrl;
    }
    
    /**
     * returns the HTTP response code
	 * 
     * @return string 
     */
    public function getResponseCode()  {
        return $this->responseCode;
    }

    
}

