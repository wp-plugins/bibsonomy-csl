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
require_once 'CurlHttpResponse.php';
require_once 'CurlHttpRequest.php';
require_once 'BibsonomyHelper.php';
require_once __DIR__.'/../../vendor/autoload.php';
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

        $url .= $BIBSONOMY_OPTIONS['bibsonomyhost'] . '/api/posts?';

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
     *
     * @return string
     */
    public function renderPublications($args) {
        global $wpdb, $post, $BIBSONOMY_OPTIONS;

        $publications = $this->fetchPublications($args);
        usort($publications, "self::cmpYear");

	    $xmlSource = "";

        if($args['stylesheet'] !== "url") {

            $table_name = $wpdb->prefix . "bibsonomy_csl_styles";

            try {
                if (is_array($publications) && count($publications) > 0) {
                    if ($args['style'] === '') {
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
        } else {
            $xmlSource = $this->fetchStylesheet($args);
        }
        
        $year = 0;
        $ret = '';

        if ($args["groupyear"] == "grouping-anchors") {
            $ret .= $this->renderGroupingAnchors($publications);
        }

        $ret .= '<ul class="' . BibsonomyCsl::PREFIX . 'publications">';

		$citeProc = new \AcademicPuma\CiteProc\CiteProc($xmlSource);

	    foreach ($publications as $key => $publication) {
	        $ret .= '<li class="' . BibsonomyCsl::PREFIX . 'pubitem">';
	        if ($args["groupyear"] == "grouping" || $args["groupyear"] == "grouping-anchors") {
                if ($year != $publication->issued->literal) {
                    $year = $publication->issued->literal;
                    $ret .= "\n</ul>";
                    $ret .= "\n<a name=\"jmp_" . BibsonomyHelper::replaceSpecialCharacters($year) . "\"></a><h3 style=\"font-size: 1.1em; font-weight: bold;\">$year</h3>";
                    $ret .= "\n<ul class=\"" . BibsonomyCsl::PREFIX . "publications\">";
                }
            }

            //$ret .= ((isset($args['cssitem']) && $args['cssitem'] != "") ? 'style="' . $args['cssitem'] . '"' : "") . '>';

            if ($args['preview']) {
                if (!empty($publication->documents)) {
                    $document_thumbnail_url = add_query_arg(array(
                        'action' => 'preview',
                        'userName' => $publication->documents[0]->userName,
                        'intraHash' => substr($publication->id, 0, 32),
                        'fileName' => urlencode($publication->documents[0]->fileName),
                        'size' => 'SMALL'
                            ), get_permalink($post->ID));
                    $ret .= '<div class="' . BibsonomyCsl::PREFIX . 'preview_border">';
                    $document_preview_url = add_query_arg(array(
                        'action' => 'preview',
                        'userName' => $publication->documents[0]->userName,
                        'intraHash' => substr($publication->id, 0, 32),
                        'fileName' => urlencode($publication->documents[0]->fileName),
                        'size' => 'LARGE'
                            ), get_permalink($post->ID));

                    $ret .= '<img onmouseover="javascript:showtrail(\'' . $document_preview_url . '\')" onmouseout="javascript:hidetrail()" class="' . BibsonomyCsl::PREFIX . 'preview" src="' . $document_thumbnail_url . '" /></div>';
                } else {
                    //default value
                    $type = empty($publication->type) ? 'entry' : $publication->type;
                    //render entry type preview
                    $ret .= '<div class="' . BibsonomyCsl::PREFIX . 'preview_border ' . BibsonomyCsl::PREFIX . 'preview_thumb">
                                    <span>
                                        <img class="bibsonomycsl_preview" style="z-index: 1;" src="' . plugins_url('/bibsonomy-csl/img/' . $type . '.jpg') . '" />
                                    </span>
                             </div>';
                }
            }

            $ret .= '<div class="' . BibsonomyCsl::PREFIX . 'entry">';


            $ret .= $citeProc->render($publication);

            if ($args['abstract'] && !empty($publication->abstract)) {
                $ret .= '<span class="' . BibsonomyCsl::PREFIX . 'export ' . BibsonomyCsl::PREFIX . 'abstract"><a rel="abs-' . $publication->id . '"  href="#">Abstract</a></span> ';
            }

            if ($args['links']) {

                $bibtex_url = add_query_arg(array(
                    'action' => 'bibtex',
                    'userName' => substr($publication->id, 32),
                    'intraHash' => substr($publication->id, 0, 32)
                        ), get_permalink($post->ID));

                $ret .= '<span class="' . BibsonomyCsl::PREFIX . 'export ' . BibsonomyCsl::PREFIX . 'bibtex"><a rel="bib-' . $publication->id . '" href="' . $bibtex_url . '">BibTeX</a></span> ';

                $endnote_url = add_query_arg(array(
                    'action' => 'endnote',
                    'userName' => substr($publication->id, 32),
                    'intraHash' => substr($publication->id, 0, 32)
                        ), get_permalink($post->ID));
                $ret .= '<span class="' . BibsonomyCsl::PREFIX . 'export ' . BibsonomyCsl::PREFIX . 'endnote"><a rel="end-' . $publication->id . '" href="' . $endnote_url . '">EndNote</a></span> ';
                
            }

            if ($args['download']) {
                if (!empty($publication->documents)) {

                    $document_download_url = add_query_arg(array(
                        'action' => 'download',
                        'userName' => $publication->documents[0]->userName,
                        'intraHash' => substr($publication->id, 0, 32),
                        'fileName' => urlencode($publication->documents[0]->fileName)
                            ), get_permalink($post->ID));

                    $ret .= '<span class="' . BibsonomyCsl::PREFIX . 'download"><a href="' . $document_download_url . '">Download</a></span> ';
                }
            }
            
            $ret .= '<span class="' . BibsonomyCsl::PREFIX . 'url"><a href="http://'.$BIBSONOMY_OPTIONS['bibsonomyhost'].'/publication/2'.substr($publication->id, 0, 32).'/'.substr($publication->id, 32).'" target="_blank">URL</a></span> ';
            
            $ret .= '<div style="clear: left"> </div>';

            if (!empty($publication->abstract)) {
                $ret .= '<div class="' . BibsonomyCsl::PREFIX . 'collapse ' . BibsonomyCsl::PREFIX . 'pub_abstract" style="display:none;" id="abs-' . $publication->id . '">' . htmlspecialchars($publication->abstract) . '</div>';
            }

            if ($args['links']) {

                $ret .= '<div class="' . BibsonomyCsl::PREFIX . 'collapse ' . BibsonomyCsl::PREFIX . 'pub_bibtex" style="display:none;" id="bib-' . $publication->id . '">' .
                        '<img src="' . plugins_url('/bibsonomy-csl/img/loading.gif') . '" alt="loading" />' .
                        '</div>';
                $ret .= '<div class="' . BibsonomyCsl::PREFIX . 'collapse ' . BibsonomyCsl::PREFIX . 'pub_endnote" style="display:none;" id="end-' . $publication->id . '">' .
                        '<img src="' . plugins_url('/bibsonomy-csl/img/loading.gif') . '" alt="loading" />' .
                        '</div>';
            }

            $ret .= '</div>';
            $ret .= '</li>';
        }

        $ret .= '</ul>';

        return $ret;
    }

    public function renderGroupingAnchors($publications) {

        $array = array();
        foreach ($publications as $pub) {
            $year = $pub->issued->literal;

            if (array_key_exists($year, $array)) {
                continue;
            }

            $array[$year] = '[<a href="#jmp_' . BibsonomyHelper::replaceSpecialCharacters($year) . '" title="Goto ' . $year . '">' . $year . '</a>]';
        }

        return implode(" ", array_values($array));
    }

    private function fetchPublications($args) {

        $biburl = new BibsonomyCsl_Url($this->buildAPIUrl($args));

        $request = new CurlHttpRequest($biburl);

        $jsonString = $request->send()->getBody();

        $hashMap = json_decode($jsonString);

        if (isset($hashMap->error)) {
            throw new Exception("BibSonomy API " . $hashMap->error);
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

        return file_get_contents($args['style']);
    }

    public static function cmpYear($a, $b) {
        return ($a->issued->literal < $b->issued->literal) ? 1 : 0;
    }

}

