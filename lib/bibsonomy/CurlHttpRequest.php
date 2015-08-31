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
 * Represents a HTTP request. 
 * Needs CURL as a dependency.
 * @see http://php.net/book.curl.php
 * 
 * @uses Tx_ExtBibsonomyCsl_Url
 *
 * @package ext_bibsonomy_csl
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author Sebastian Böttger
 */
class CurlHttpRequest {

    /**
     * URL of request
     * @var Tx_ExtBibsonomyCsl_BibsonomyApiUrl 
     */
    protected $url;

    /**
     * Constructor
     * 
     * @param Tx_ExtBibsonomyCsl_BibsonomyApiUrl $url 
     */
    public function __construct(BibsonomyCsl_Url $url) {

        $this->url = $url;
    }

    /**
     * Executes the request.
     * If pcntl_fork is available, it forks the request in an own process. 
     * 
     * @param boolean $fork
     * @param integer $sleep
     * @return Tx_ExtBibsonomyCsl_CurlHttpResponse|boolean returns a response object if request succeeded otherwise false;
     */
    public function send($fork = true, $sleep = 5) {

        $time = microtime(true);

        $expire = $time + $sleep;

        $status = null;

        if ($fork && function_exists('pcntl_fork')) {

            $pid = pcntl_fork();

            if ($pid == -1) {

                die('could not fork');
            } else if ($pid) {

                return $this->curl();
                pcntl_wait($status);
            } else {

                while (microtime(true) < $expire) {
                    sleep(0.5);
                }
                return false;
            }
        }
        return $this->curl();
    }

    /**
     * Executes the an request with cURL.
     * @return Tx_ExtBibsonomyCsl_CurlHttpResponse the response 
     */
    private function curl() {


        $ch = curl_init();

        $requestUrl = $this->url->getUrlWithoutAuth();
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //Auth Basic?
	    $authUser = $this->url->getAuthUser();
	    $authPass = $this->url->getAuthPass();

	    if ( !empty($authUser) && !empty($authPass) ) {

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->url->getAuthUser() . ":" . $this->url->getAuthPass());
        }

        $responseBody = curl_exec($ch);

        $responseHeader = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return new CurlHttpResponse(
                $requestUrl, $responseBody, $responseHeader, $responseCode);
    }

    /**
     *
     * @return BibsonomyCsl_Url 
     */
    public function getUrl() {

        return $this->url;
    }

}
