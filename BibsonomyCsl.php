<?php

/*
  Plugin Name: BibSonomy/PUMA CSL - Publications
  Plugin URI: http://www.bibsonomy.org/help_en/Wordpress%20Plugin%20bibsonomy_csl
  Description: Plugin to create publication lists based on the Citation Style Language (CSL). Allows direct integration with the social bookmarking and publication sharing systems BibSonomy http://www.bibsonomy.org or PUMA.
  Author: Sebastian Böttger
  Author URI: http://www.academic-puma.de
  Version: 2.1.3
 */


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

if (is_admin()) {
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-custom', get_template_directory_uri() . '/css/jquery-ui-custom.css');
}


require_once 'lib/bibsonomy/BibsonomyAPI.php';
require_once 'lib/bibsonomy/DocumentUrl.php';
require_once 'lib/bibsonomy/CurlHttpRequestProxy.php';
require_once 'BibsonomyOptions.php';


$custom_meta_fields = array(
    "type" => array(
        'label' => 'Select BibSonomy content source type',
        'desc' => 'You can choose between user, group and viewable. For a detailed explanation, refer to <a target="_blank" href="http://www.bibsonomy.org/help_en/URL%20Scheme%20Semantics">http://www.bibsonomy.org/help_en/URL%20Scheme%20Semantics</a>.',
        'id' => BibsonomyCsl::PREFIX. 'type',
        'type' => 'select',
        'options' => array(
            'one' => array(
                'label' => '',
                'value' => ''
            ),
            'two' => array(
                'label' => 'user',
                'value' => 'user'
            ),
            'three' => array(
                'label' => 'group',
                'value' => 'group'
            ),
            'four' => array(
                'label' => 'viewable',
                'value' => 'viewable'
            )
        )
    ),
    "type_value" => array(
        'label' => 'Specify the value of the content source type',
        'desc' => 'Here you can specify the value of the content source type. As an example, insert an user id when filtering by user or a group id for filtering by group.',
        'id' => BibsonomyCsl::PREFIX. 'type_value',
        'type' => 'text'
    ),
    "tags" => array(
        'label' => 'Filter the publication list by choosing one or more tags.',
        'desc' => 'Filter the results by choosing one or more tags. As an example, if you type in the tag "myown", the result is limited to publications which are annotated with this tag. If you want to select more than one tag you have to separate them by a space character.',
        'id' => BibsonomyCsl::PREFIX. 'tags',
        'type' => 'text'
    ),
    "search" => array(
        'label' => 'Filter the result list by using free fulltext search',
        'desc' => 'You can also filter the result list by using free fulltext search. The search syntax is explained here in greater detail: <a href="http://www.bibsonomy.org/help_en/Search%2Bpages" target="_blank">http://www.bibsonomy.org/help_en/Search%2Bpages</a>.',
        'id' => BibsonomyCsl::PREFIX. 'search',
        'type' => 'text'
    ),
    "end" => array(
        'label' => 'Limit the length of the result list',
        'desc' => '',
        'id' => BibsonomyCsl::PREFIX. 'end',
        'type' => 'text',
        'default' => 100
    ),
    "stylesheet" => array(
        'label' => 'CSL-Stylesheet',
        'desc' => 'Choose a stylesheet.',
        'id' => BibsonomyCsl::PREFIX. 'stylesheet',
        'options' => array(),
        'type' => 'select'
    ),
    "style_url" => array(
        'label' => 'URL to CSL-Stylesheet',
        'desc' => 'Alternativly insert an URL of a stylesheet. A huge set of styles can you find at <a href="http://zotero.org/styles/" target="_blank">zotero.org/styles/</a>.',
        'id' => BibsonomyCsl::PREFIX. 'style_url',
        'type' => 'text'
    ),
    "abstract" => array(
        'label' => 'Show link to abstract',
        'desc' => 'If you select this, a hyperlink to the abstract of the publication (if exists) will be shown.',
        'id' => BibsonomyCsl::PREFIX. 'abstract',
        'type' => 'checkbox'
    ),
    "links" => array(
        'label' => 'Show URL, BibTeX and EndNote links',
        'desc' => 'If you select this, a hyperlink (if exists) of the publication and two hyperlinsk to show BibTeX and EndNote exports will be shown.',
        'id' => BibsonomyCsl::PREFIX. 'links',
        'type' => 'checkbox'
    ),
    "download" => array(
        'label' => 'Show download links',
        'desc' => 'If you select this, for each post a hyperlink to the associated document (if exists) will be shown.',
        'id' => BibsonomyCsl::PREFIX. 'download',
        'type' => 'checkbox'
    ),
    "preview" => array(
        'label' => 'Show thumbnails of documents',
        'desc' => 'If you select this, for each post a preview image of the associated document (if exists) will be shown.',
        'id' => BibsonomyCsl::PREFIX. 'preview',
        'type' => 'checkbox'
    ),
    "groupyear" => array(
        'label' => 'Group publications by year',
        'desc' => 'If you select grouping, publications will be grouped by their publishing year. If you select grouping with jump labels, all publishing years of your publication list will be displayed as jump labels at the top of the list. ',
        'id' => BibsonomyCsl::PREFIX. 'groupyear',
        'type' => 'select',
        'options' => array(
            'one' => array(
                'label' => 'no grouping',
                'value' => ''
            ),
            'two' => array(
                'label' => 'grouping without jump labels',
                'value' => 'grouping'
            ),
            'three' => array(
                'label' => 'grouping with jump labels ',
                'value' => 'grouping-anchors'
            )
        )
    ),
    "css" => array(
        'label' => 'Define layout modifications for your publication list with CSS',
        'desc' => 'You can define CSS details (Cascading Style Sheets) to manipulate the look and feel of your publication list items.',
        'id' => BibsonomyCsl::PREFIX. 'css',
        'type' => 'textarea',
        'default' => "
/* Use this field to overwrite the style of the publication list */

ul.bibsonomycsl_publications {

}

ul.bibsonomycsl_publications li {

}

ul.bibsonomycsl_publications div.bibsonomycsl_entry {

}

.bibsonomycsl_publications span.title { 

}

.bibsonomycsl_publications span.pdf {

}

.bibsonomycsl_publications span.bibtex {

}

img.bibsonomycsl_preview {

}

.bibsonomycsl_preview_border {

}

"

    )
);

$BIBSONOMY_OPTIONS = get_option('bibsonomy_options');

class BibsonomyCsl {

    const PREFIX = 'bibsonomycsl_';
    
    /**
     * 
     * @var BibsonomyOptions 
     */
    protected $bibsonomyOptions;

    /**
     * Constructor. Instantiates BibsonomyOptions for admin settings page and registers activation and deactivation hook in WordPress 
     */
    public function __construct() {
        register_activation_hook(__FILE__, array(&$this, 'jal_install'));
        register_activation_hook(__FILE__, array(&$this, 'jal_install_data'));

        $this->bibsonomyOptions = new BibsonomyOptions();

        //activation
        register_activation_hook(__FILE__, array(&$this, 'activate'));
        
        //deactivation
        register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
        
        //uninstallation
        register_uninstall_hook(__FILE__, array(&$this, 'jal_uninstall'));
    }

    /**
     * Adds shortcode, filter and action hooks 
     */
    public function activate() {

        add_shortcode('bibsonomy', array(&$this, 'bibsonomycsl_shortcode_publications'));
        
        add_filter('template_redirect', array(&$this, 'bibsonomycsl_action'), 1);

        add_filter('the_content', array(&$this, 'bibsonomycsl_insert_publications_from_post_meta'));
        add_action('add_meta_boxes', array(&$this, 'bibsonomycsl_custom_fields'));
        add_action('save_post', array(&$this, 'bibsonomy_save_custom_meta'));
        
        //register options/settings page
        add_action('admin_menu', array(&$this->bibsonomyOptions, 'bibsonomy_add_settings_page'));

        //enqueue css and javascript
        add_action( 'wp_enqueue_scripts', array(&$this, 'bibsonomycsl_enqueue_scripts') );
        
        //custom css
        add_action('wp_head', array(&$this, 'bibsonomy_add_css'));
        
    }

    /**
     * Removes shortcode, filter and action hooks 
     */
    public function deactivate() {

        remove_shortcode('bibsonomy', array(&$this, 'bibsonomycsl_shortcode_publications'));
        remove_filter('the_content', array(&$this, 'bibsonomycsl_insert_publications_from_post_meta'));
        remove_filter('template_redirect', array(&$this, 'bibsonomycsl_download_document'));
        remove_action('add_meta_boxes', array(&$this, 'bibsonomycsl_custom_fields'));
        remove_action('save_post', array(&$this, 'bibsonomycsl_custom_fields_data'));
        remove_action('wp_print_styles', array(&$this, 'bibsonomycsl_enqueue_styles'));
        remove_action('wp_head', array(&$this, 'bibsonomy_add_css'));
        remove_action('wp_head', array(&$this, 'bibsonomy_add_js'));
    }

    public function jal_install() {
        global $wpdb, $jal_db_version;

        $table_name = $wpdb->prefix . "bibsonomy_csl_styles";

        $sql = "CREATE TABLE $table_name (
			id varchar(255) NOT NULL,
			title tinytext NOT NULL,
			xml_source text NOT NULL,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL
			)
		ENGINE=MyISAM DEFAULT CHARSET=utf8";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option("jal_db_version", $jal_db_version);
    }

    public function jal_uninstall() {
        global $wpdb, $jal_db_version;

        // Make sure that we are uninstalling
        if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            exit();
        }
        
        $option_name = 'bibsonomy_options';

        if ( !is_multisite() )  {
            delete_option( $option_name );
        } else {
            
            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            $original_blog_id = get_current_blog_id();

            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                delete_option( $option_name );     
            }

            switch_to_blog( $original_blog_id );
        } 
        
        $table_name = $wpdb->prefix . "bibsonomy_csl_styles";
        $wpdb->query("DROP TABLE {$table_name}");
    }

    public function jal_install_data() {

        global $wpdb;
        $table_name = $wpdb->prefix . "bibsonomy_csl_styles";
        require_once('lib/bibsonomy/BibsonomyHelper.php');

        $sources = BibsonomyHelper::readCSLFolder(__DIR__ . '/csl_styles/');

        $rows_affected = false;
        foreach ($sources as $source) {

            $xml = new DOMDocument();
            $xml->loadXML($source);

            $title = $xml->getElementsByTagName("title")->item(0)->nodeValue;
            $id = $xml->getElementsByTagName("id")->item(0)->nodeValue;



            $rows_affected = $wpdb->insert(
                    $table_name, array(
                'id' => $id,
                'time' => current_time('mysql'),
                'title' => $title,
                'xml_source' => $source)
            );
        }

        return $rows_affected;
    }

    /**
     * Adds meta box for posts and pages
     */
    public function bibsonomycsl_custom_fields() {
        add_meta_box(
                'bibsonomycsl_custom_fields', // this is HTML id of the box on edit screen
                'Add BibSonomy Publications', // title of the box
                array(&$this, 'bibsonomycsl_custom_fields_box_content'), // function to be called to display the checkboxes, see the function below
                'post', // on which edit screen the box should appear
                'normal', // part of page where the box should appear
                'default'        // priority of the box
        );
        add_meta_box(
                'bibsonomycsl_custom_fields', // this is HTML id of the box on edit screen
                'Add BibSonomy Publications', // title of the box
                array(&$this, 'bibsonomycsl_custom_fields_box_content'), // function to be called to display the checkboxes, see the function below
                'page', // on which edit screen the box should appear
                'normal', // part of page where the box should appear
                'default'        // priority of the box
        );
    }

    /**
     *  Displays the metabox
     */
    public function bibsonomycsl_custom_fields_box_content($post_id) {
        global $custom_meta_fields, $post, $wpdb;
        wp_nonce_field(plugin_basename(__FILE__), 'bibsonomycsl_nonce');

        
        $custom_meta_fields["stylesheet"]["options"]["url"]["label"] = "Use stylesheet from URL";
        $custom_meta_fields["stylesheet"]["options"]["url"]["value"] = "url";
        
        $table_name = $wpdb->prefix . "bibsonomy_csl_styles";
        $results = $wpdb->get_results("SELECT id, title FROM $table_name ORDER by id ASC;");
        
        foreach ($results as $key => $result) {

            $custom_meta_fields["stylesheet"]["options"][$key]["label"] = $result->title;
            $custom_meta_fields["stylesheet"]["options"][$key]["value"] = $result->id;
        }

        echo '<input type="hidden" name="custom_meta_box_nonce" value="' . wp_create_nonce(basename(__FILE__)) . '" />';


        // Begin the field table and loop
        echo '<table class="form-table">';
        foreach ($custom_meta_fields as $key => $field) {
            // get value of this field if it exists for this post
            $meta = get_post_meta($post->ID, $field['id'], true);
            // begin a table row with
            echo '<tr>
                    <th><label for="' . $field['id'] . '">' . $field['label'] . '</label></th>
                    <td>';
            switch ($field['type']) {

                case 'text':
                    echo '<input type="text" name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . (!$meta ? $field['default'] : $meta) . '" size="50" />
								<br /><span class="description">' . $field['desc'] . '</span>';
                    break;

                case 'textarea':
                    echo '<textarea style="color: #000; font-family: \'Courier New\', Courier; line-height: 1em; font-size: 1em;" cols="80" rows="20" name="' . $field['id'] . '" id="' . $field['id'] . '" cols="60" rows="4">' . (!$meta ? $field['default'] : $meta) . '</textarea>
								<br /><span class="description">' . $field['desc'] . '</span>';
                    break;

                case 'checkbox':

                    echo '<input type="checkbox" name="' . $field['id'] . '" id="' . $field['id'] . '" ' . ($meta ? 'checked="checked"' : '' ) . '/>
								<label for="' . $field['id'] . '">' . $field['desc'] . '</label>';
                    break;

                case 'select':
                    echo '<select name="' . $field['id'] . '" id="' . $field['id'] . '">';
                    foreach ($field['options'] as $option) {
                        echo '<option', $meta == $option['value'] ? ' selected="selected"' : '', ' value="' . $option['value'] . '">' . $option['label'] . '</option>';
                    }
                    echo '</select><br /><span class="description">' . $field['desc'] . '</span>';
                    break;
            } //end switch
            echo '</td></tr>';
        } // end foreach
        echo '</table>'; // end table
    }

    /**
     * Saves the data.
     * @global array $custom_meta_fields
     * @param integer $post_id
     * @return void
     */
    public function bibsonomy_save_custom_meta($post_id) {
        global $custom_meta_fields;
        // verify nonce
        if (!wp_verify_nonce($_POST['custom_meta_box_nonce'], basename(__FILE__)))
            return $post_id;
        // check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;
        // check permissions
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        }
        elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        // loop through fields and save the data
        foreach ($custom_meta_fields as $field) {

            $old = get_post_meta($post_id, $field['id'], true);

            $new = $_POST[$field['id']];

            if ($new && $new != $old) {

                update_post_meta($post_id, $field['id'], $new);
            } elseif ('' == $new && $old) {

                delete_post_meta($post_id, $field['id'], $old);
            }
        } // end foreach
    }

    /**
     * Returns html rendered publication list.
     * Called when shortcode bibsonomy was beeing used.
     * 
     * @param array $args
     * @param string $content
     * @return string
     */
    public function bibsonomycsl_shortcode_publications($args, $content) {
        global $BIBSONOMY_OPTIONS;
        $bibAPI = new BibsonomyAPI();

        if (!isset($args['user']) || !isset($args['apikey'])) {

            $args['user'] = $BIBSONOMY_OPTIONS['user'];
            $args['apikey'] = $BIBSONOMY_OPTIONS['apikey'];
        }


        return "<h2>$content</h2>\n"
                . $bibAPI->renderPublications($args);
    }

    /**
     *
     * @global object $post
     * @param string $content
     * @return string html rendered publication list
     */
    public function bibsonomycsl_insert_publications_from_post_meta($content) {
        global $post, $BIBSONOMY_OPTIONS;

        $args = array();

        $args['type'] = get_post_meta($post->ID, 'bibsonomycsl_type', true);

        if ($args['type'] === '') {
            return $content;
        }

        $args['user'] = $BIBSONOMY_OPTIONS['user'];
        $args['apikey'] = $BIBSONOMY_OPTIONS['apikey'];


        if ($args['user'] == '' || $args['apikey'] == '') {
            return $content;
        }

        $args['val'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'type_value', true);
        $args['tags'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'tags', true);
        $args['search'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'search', true);
        $args['end'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'end', true);
        $args['abstract'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'abstract', true);
        $args['download'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'download', true);
        $args['preview'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'preview', true);
        $args['style'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'style_url', true);
        $args['stylesheet'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'stylesheet', true);
        $args['links'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'links', true);
        $args['groupyear'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'groupyear', true);
        $args['cssitem'] = get_post_meta($post->ID, BibsonomyCsl::PREFIX.'cssitem', true);

        $bibAPI = new BibsonomyAPI();
        
        $ttimg = '<div id="trailimageid"><img id="ttimg" src="'.plugins_url('',__FILE__).'/img/loading.gif"></div>';
        
        return "$ttimg $content\n"
                . $bibAPI->renderPublications($args);
    }

    public function bibsonomycsl_action() {
        $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

        if (!empty($action)) {

            $intraHash = filter_input(INPUT_GET, 'intraHash', FILTER_SANITIZE_STRING);
            $userName = filter_input(INPUT_GET, 'userName', FILTER_SANITIZE_STRING);
            $fileName = filter_input(INPUT_GET, 'fileName', FILTER_SANITIZE_STRING);

            switch ($action) {

                case 'download':
                    $this->bibsonomycsl_download_document($userName, $intraHash, $fileName);
                    break;

                case 'preview':
                    $this->bibsonomycsl_preview_document($userName, $intraHash, $fileName);
                    break;
                
                case 'bibtex':
                    $this->bibsonomycsl_bibtex_export($userName, $intraHash);
                    break;
                
                case 'endnote':
                    $this->bibsonomycsl_endnote_export($userName, $intraHash);
                    break;
            }
        }
        return;
    }

    public function bibsonomycsl_download_document($userName, $intraHash, $fileName) {

        $settings = $this->bibsonomycsl_get_settings();

        $url = new DocumentUrl($settings, $userName, $intraHash, $fileName, false);

        $proxy = new CurlHttpRequestProxy($url);

        $proxy->send();

        exit();
    }

    public function bibsonomycsl_preview_document($userName, $intraHash, $fileName) {

        $size = filter_input(INPUT_GET, 'size', FILTER_SANITIZE_STRING);

        $settings = $this->bibsonomycsl_get_settings();

        $url = new DocumentUrl($settings, $userName, $intraHash, $fileName, $size);

        $proxy = new CurlHttpRequestProxy($url);

        $proxy->send();

        exit();
    }

    public function bibsonomycsl_bibtex_export($userName, $intraHash) {
        
        $url = new BibsonomyCsl_Url($this->buildExportUrl($userName, $intraHash, 'bibtex'));
        $this->bibsonomycsl_print_export($url);
    }
    
    public function bibsonomycsl_endnote_export($userName, $intraHash) {
        
        $url = new BibsonomyCsl_Url($this->buildExportUrl($userName, $intraHash, 'endnote'));
        $this->bibsonomycsl_print_export($url);
    }
    
    private function bibsonomycsl_print_export(BibsonomyCsl_Url $url) {
        
        $request = new CurlHttpRequest($url);

        //gets response object
        $response = $request->send();
        if ($response === false) {
            throw new Exception(
                "HTTP Error. Maybe this happens because the server connection is blocked by a firewall.\n"
                . "Please contact your webmaster!", 14
                );
        }
        //returns body message of response
        print '<textarea rows="10">'.$response->getBody().'</textarea>';
        exit();
    }
    
    private function buildExportUrl($userName, $intraHash, $format) {
        
        $settings = $this->bibsonomycsl_get_settings();
        
        $components = parse_url($settings['bib_server']);
        
        //set user/pass for authentication
        $components['user'] = $settings['bib_login_name'];
        $components['pass'] = $settings['bib_api_key'];

        //set path to api
        $components['path'] = '/api/users/'.urldecode($userName).'/posts/'.$intraHash;
        
        $qry = array();
        $qry['format'] = $format;
        $qry['resourcetype'] = 'bibtex';
        
        $components['query'] = http_build_query($qry);
        
        return BibsonomyCsl_Url::http_build_url($components);
    }
    
    
    private function bibsonomycsl_get_settings() {
        global $BIBSONOMY_OPTIONS;

        $settings['bib_login_name'] = $BIBSONOMY_OPTIONS['user'];
        $settings['bib_api_key'] = $BIBSONOMY_OPTIONS['apikey'];
        $settings['bib_server'] = 'http://' . $BIBSONOMY_OPTIONS['bibsonomyhost'];

        return $settings;
    }
    
    public function bibsonomy_add_css() {
        global $post;

        
        echo '<style type="text/css" rel="stylesheet">' . "\n" .
        get_post_meta($post->ID, 'bibsonomycsl_css', true) .
        '</style>' . "\n";
    }

    public function bibsonomycsl_enqueue_scripts() {

        wp_enqueue_style('bibsonomycsl', plugins_url('',__FILE__).'/css/bibsonomycsl.css');
        wp_enqueue_script('bibsonomycsl', plugins_url('',__FILE__).'/js/bibsonomycsl.js');
        wp_enqueue_script('tooltip', plugins_url('',__FILE__).'/js/tooltip.js');
        
    }

}

$bibsonomy = new BibsonomyCsl();

add_action('init', array(&$bibsonomy, 'activate'));
