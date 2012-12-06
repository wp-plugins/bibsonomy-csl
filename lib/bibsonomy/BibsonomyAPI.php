<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2012 
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */
require_once 'Url.php';
require_once 'CurlHttpResponse.php';
require_once 'CurlHttpRequest.php';

/**
 * Description of BibsonomyAPI
 *
 * @author Sebastian Böttger
 */
class BibsonomyAPI {

	/**
	 * 
	 */
	private function buildAPIUrl($args) {
		global $BIBSONOMY_OPTIONS;
		$url = 'http://';

		if (isset($args['user']) && isset($args['apikey'])) {
			$url .= $args['user'] . ':' . $args['apikey'] . '@';
		}

		$url .= $BIBSONOMY_OPTIONS['bibsonomyhost'].'/api/posts?';

		if (isset($args['type']) && $args['type'] != '' && isset($args['val'])) {
			$url .= $args['type'] . '=' . urlencode($args['val']) . '&';
		}
		if (isset($args['tags']) && $args['tags'] != '') {
			$url .= 'tags=' . urlencode($args['tags']) . '&';
		}
		if (isset($args['search']) && $args['search'] != '') {
			$url .= 'search=' . urlencode($args['search']) . '&';
		}
		if (isset($args['end'])) {
			$url .= 'end=' . urlencode($args['end']) . '&';
		} else {
			$url .= 'end=20&';
		}
		
		$url .= 'format=csl&resourcetype=bibtex';
		
		return $url;
	}

	/**
	 *
	 * @param array $args
	 */
	public function renderPublications($args) {
		global $wpdb;
		$publications = array();
		$xmlSource = "";
		$table_name = $wpdb->prefix . "bibsonomy_csl_styles";


		try {

			$publications = $this->fetchPublications($args);

			if (is_array($publications) && count($publications) > 0) {
				if ($args['style'] == '') {
					$query = "SELECT xml_source FROM $table_name WHERE id='" . $args['stylesheet'] . "';";

					$results = $wpdb->get_results($query);

					$xmlSource = $results[0]->xml_source;
				} else {
					$xmlSource = $this->fetchStylesheet($args);
				}
			} else {

				return "";
			}
		} catch (Exception $e) {

			return '<p style="border: 1px solid #f00; padding: 0.5em 1em;">Error: ' . $e->getMessage() . '</p>' . "\n<!--" . $e->getTraceAsString() . "-->\n";
		}


		$citeProc = new citeproc($xmlSource);

		$ret = "";

		foreach ($publications as $key => $publication) {
			global $BIBSONOMY_OPTIONS;

			$ret .= '<li class="bibsonomy_pubitem"';
			$ret .= ((isset($args['cssitem']) && $args['cssitem'] != "") ? 'style="' . $args['cssitem'] . '"' : "") . '>';
			$ret .= '<div class="bibsonomy_entry">';

			$ret .= $citeProc->render($publication);
			
			if (!empty($publication->URL)) {
				$ret .= '<span class="pdf"><a href="' . $publication->URL . '" target="_blank">PDF</a></span> ';
			}

			$ret .= '<span class="bibtex"><a href="http://'.$BIBSONOMY_OPTIONS['bibsonomyhost'].'/bib/bibtex/2' . substr($publication->id, 0, 32) . '?bibtex.entriesPerPage=1' . '" target="_blank">BibTeX</a></span>';

			$ret .= '<div style="clear: left"> </div>';
			$ret .= '</div>';
			$ret .= '</li>';
		}

		return $ret;
	}

	private function fetchPublications($args) {

		$biburl = new BibsonomyCsl_Url($this->buildAPIUrl($args));

		$request = new CurlHttpRequest($biburl);

		$jsonString = $request->send()->getBody();
		
		$hashMap = json_decode($jsonString);

		if( isset($hashMap->error) ) {
			throw new Exception("BibSonomy API ".$hashMap->error);
		}

		$publications = array();

		foreach ($hashMap as $key => $object) {
			$intrahash = "2" . substr($object->id, 0, 32);
			$publications[$intrahash] = $object;
		}

		return $publications;
	}

	private function fetchStylesheet($args) {

		if (!isset($args['style']) || $args['style'] == "") {
			throw new Exception("No style given!");
		}

		$url = new BibsonomyCsl_Url($args['style']);

		$req = new CurlHttpRequest($url);
		$response = $req->send();

		return $response->getBody();
	}

}

?>
