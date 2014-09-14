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

require_once 'Url.php';

/**
 * Description of Tx_ExtBibsonomyCsl_ThumbnailUrl
 *
 * @author Sebastian Böttger <boettger@cs.uni-kassel.de>
 */
class DocumentUrl extends BibsonomyCsl_Url {

    /**
     * owner's userName of the publication
     * 
     * @var string $userName 
     */
    private $userName;
    
    /**
     * intraHash of the publication
     * 
     * @var string $pubHash 
     */
    private $pubHash;
    
    /**
     * filename of the document
     * 
     * @var string $fileName 
     */
    private $fileName;
    
    /**
     * fetch document or preview?
     * 
     * @var boolean|string 
     */
    private $preview;
    
    
    protected $settings;
    
    /**
     * 
     * @param array $settings
     * @param string $userName
     * @param string $pubHash
     * @param string $fileName
     * @param boolean|string $preview
     */
    public function __construct($settings, $userName, $pubHash, $fileName, $preview = "SMALL") {
        
        $this->userName = $userName;
        $this->pubHash = $pubHash;
        $this->fileName = $fileName;
        $this->preview = $preview;
        
        $this->settings = $settings;
        
        $this->initForBibsonomyAPI();
    }
    
    /**
     * initializes the url for a document request
     * @throws Tx_ExtBibsonomyCsl_Domain_Exception_AuthenticationException
     */
    protected function initForBibsonomyAPI() {

        //parse baseUrl
        $components = parse_url($this->settings["bib_server"]);

        //check authentication
        if (empty($this->settings["bib_login_name"]) || empty($this->settings["bib_api_key"])) {
            throw new Exception("User name or API key for REST API was not set.");
        }
        
        //set user/pass for authentication
        $components["user"] = $this->settings["bib_login_name"];
        $components["pass"] = $this->settings["bib_api_key"];

        //set api path for getting thumbs
        $components["path"] =   "/api/users/" . $this->userName . 
                                "/posts/"     . $this->pubHash  .
                                "/documents/" . $this->fileName;
        
        //preview requested?
        if($this->preview !== false) {

            $components["query"] = http_build_query(array('preview' => $this->preview));
        }
        
        $this->components = $components;
    }
    
    /**
     * 
     * @return string
     */
    public function getUserName() {
        return $this->userName;
    }
    
    /**
     * 
     * @return string
     */
    public function getPubHash() {
        return $this->pubHash;
    }
    
    /**
     * 
     * @return string
     */
    public function getFileName() {
        return $this->fileName;
    }
    
    /**
     * 
     * @return boolean|string
     */
    public function getPreview() {
        return $this->preview;
    }
}

