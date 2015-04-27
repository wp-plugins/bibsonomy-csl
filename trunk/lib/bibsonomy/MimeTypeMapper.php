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
 * 11.03.2014 
 * 
 * Description of Tx_ExtBibsonomyCsl_MimeTypeMapper
 *
 * @package ext_bibsonomy_csl
 * @author Sebastian Böttger <boettger@cs.uni-kassel.de> 
 */
class MimeTypeMapper {
    //put your code here
    
    private static $contentTypeMap = array(
        'pdf'   => 'application/pdf',
        'png'   => 'image/png',
        'jpg'   => 'image/jpg',
        'ps'    => 'application/postscript',
        'eps'   => 'application/postscript',
        'svg'   => 'image/svg+xml',
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt'   => 'application/mspowerpoint',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls'   => 'application/msexcel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'djv'   => 'image/x.djvu',
        'djvu'  => 'image/x.djvu',
        'txt'   => 'text/plain',
        'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt'   => 'application/vnd.oasis.opendocument.text ',
        'odp'   => 'application/vnd.oasis.opendocument.presentation'
    );
    
    
    public static function getMimeType($fileName) {
        
        $match = array();
        
        if(preg_match('/.+\.([a-zA-Z0-9]{2,3})$/i', $fileName, $match)) {
            return self::$contentTypeMap[$match[1]];
        } 
        
        return 'application/octet-stream';
    }
    
}
