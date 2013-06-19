<?php

/*
  Plugin Name: BibSonomy CSL
  Plugin URI: http://www.bibsonomy.org/help_en/Wordpress%20Plugin%20bibsonomy_csl
  Description: Plugin to create publication lists based on the Citation Style Language (CSL). Allows direct integration with the social bookmarking and publication sharing system Bibsonomy http://www.bibsonomy.org or different sources.
  Author: Sebastian Böttger, Andreas Hotho
  Author URI: http://www.kde.cs.uni-kassel.de
  Version: 1.1.3
 */

if (is_admin()) {
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-custom', get_template_directory_uri() . '/css/jquery-ui-custom.css');
}


require_once 'lib/bibsonomy/BibsonomyAPI.php';
require_once 'lib/citeproc-php/CiteProc.php';
require_once 'BibsonomyOptions.php';

$prefix = 'bibsonomycsl_';
$custom_meta_fields = array(
    "type" => array(
        'label' => 'Select BibSonomy content source type',
        'desc' => 'You can choose between user, group and viewable. For a detailed explanation, refer to <a target="_blank" href="http://www.bibsonomy.org/help_en/URL%20Scheme%20Semantics">http://www.bibsonomy.org/help_en/URL%20Scheme%20Semantics</a>.',
        'id' => $prefix . 'type',
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
        'id' => $prefix . 'type_value',
        'type' => 'text'
    ),
    "tags" => array(
        'label' => 'Filter the publication list by choosing one or more tags.',
        'desc' => 'Filter the results by choosing one or more tags. As an example, if you type in the tag "myown", the result is limited to publications which are annotated with this tag. If you want to select more than one tag you have to separate them by a space character.',
        'id' => $prefix . 'tags',
        'type' => 'text'
    ),
    "search" => array(
        'label' => 'Filter the result list by using free fulltext search',
        'desc' => 'You can also filter the result list by using free fulltext search. The search syntax is explained here in greater detail: <a href="http://www.bibsonomy.org/help_en/Search%2Bpages" target="_blank">http://www.bibsonomy.org/help_en/Search%2Bpages</a>.',
        'id' => $prefix . 'search',
        'type' => 'text'
    ),
    "end" => array(
        'label' => 'Limit the length of the result list',
        'desc' => '',
        'id' => $prefix . 'end',
        'type' => 'text',
        'default' => 100
    ),
    "stylesheet" => array(
        'label' => 'CSL-Stylesheet',
        'desc' => 'Choose a stylesheet.',
        'id' => $prefix . 'stylesheet',
        'options' => array(),
        'type' => 'select'
    ),
    "style_url" => array(
        'label' => 'URL to CSL-Stylesheet',
        'desc' => 'Alternativly insert an URL of a stylesheet. A huge set of styles can you find at <a href="http://zotero.org/styles/" target="_blank">zotero.org/styles/</a>.',
        'id' => $prefix . 'style_url',
        'type' => 'text'
    ),
    "links" => array(
        'label' => 'Show URL and BibTeX links',
        'desc' => 'If you select this, a hyperlink (if exists) of the publication and hyperlink to BibTeX definition will be shown.',
        'id' => $prefix . 'links',
        'type' => 'checkbox',
    ),
    "groupyear" => array(
        'label' => 'Group publications by year',
        'desc' => 'If you select grouping, publications will be grouped by their publishing year. If you select grouping with jump labels, all publishing years of your publication list will be displayed as jump labels at the top of the list. ',
        'id' => $prefix . 'groupyear',
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
        'id' => $prefix . 'css',
        'type' => 'textarea',
        'default' => "
.bibsonomy_publications {
	
}

.bibsonomy_publications li {
	font-size: 14px;
	line-height: 18px;
	padding-bottom: 1em;
}

.bibsonomy_publications div.bibsonomy_entry {
	font-size: 13px;
}

.bibsonomy_publications span.title { 
	display: block;
	text-decoration: none;
	font-weight: bold;
	font-size: 14px !important;
}

.bibsonomy_publications span.pdf {
	display: block;
	font-size: 14px;
	line-height: 24px;
	padding: 0 10px 0 0;
	margin-top: 5px;
	float: left;
}

.bibsonomy_publications span.bibtex {
	display: block;
	font-size: 14px;
	line-height: 24px;
	padding: 0 10px 0 20px;
	margin-top: 5px;
	float: left;
	background: transparent url(/wp-content/plugins/bibsonomy_csl/img/logo_bibsonomy.png) 0 3px no-repeat; 
}
"
    )
);

$BIBSONOMY_OPTIONS = get_option('bibsonomy_options');

class BibsonomyCsl {

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
        register_activation_hook(__FILE__, array(&$this, 'activate'));

        register_deactivation_hook(__FILE__, array(&$this, 'jal_uninstall'));
        register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
        $this->bibsonomyOptions = new BibsonomyOptions();
    }

    /**
     * Adds shortcode, filter and action hooks 
     */
    public function activate() {

        add_shortcode('bibsonomy', array(&$this, 'bibsonomycsl_shortcode_publications'));
        add_filter('the_content', array(&$this, 'bibsonomycsl_insert_publications_from_post_meta'));
        add_action('add_meta_boxes', array(&$this, 'bibsonomycsl_custom_fields'));
        add_action('save_post', array(&$this, 'bibsonomy_save_custom_meta'));
        add_action('wp_head', array(&$this, 'bibsonomy_add_css'));
        //register options/settings page
        add_action('admin_menu', array(&$this->bibsonomyOptions, 'bibsonomy_add_settings_page'));

        //add_action( 'wp_print_styles', array(&$this, 'bibsonomycsl_enquere_styles') );
    }

    /**
     * Removes shortcode, filter and action hooks 
     */
    public function deactivate() {

        remove_shortcode('bibsonomy', array(&$this, 'bibsonomycsl_shortcode_publications'));
        remove_filter('the_content', array(&$this, 'bibsonomycsl_insert_publications_from_post_meta'));
        remove_action('add_meta_boxes', array(&$this, 'bibsonomycsl_custom_fields'));
        remove_action('save_post', array(&$this, 'bibsonomycsl_custom_fields_data'));
        remove_action('wp_print_styles', array(&$this, 'bibsonomycsl_enquere_styles'));
        remove_action('wp_head', array(&$this, 'bibsonomy_add_css'));
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

        $args['val'] = get_post_meta($post->ID, 'bibsonomycsl_type_value', true);

        $args['tags'] = get_post_meta($post->ID, 'bibsonomycsl_tags', true);
        $args['search'] = get_post_meta($post->ID, 'bibsonomycsl_search', true);

        $args['end'] = get_post_meta($post->ID, 'bibsonomycsl_end', true);

        $args['style'] = get_post_meta($post->ID, 'bibsonomy_style_url', true);


        $args['stylesheet'] = get_post_meta($post->ID, 'bibsonomycsl_stylesheet', true);

        $args['links'] = get_post_meta($post->ID, 'bibsonomycsl_links', true);

        $args['groupyear'] = get_post_meta($post->ID, 'bibsonomycsl_groupyear', true);

        $args['cssitem'] = get_post_meta($post->ID, 'bibsonomycsl_cssitem', true);

        $bibAPI = new BibsonomyAPI();

        return "$content\n"
                . $bibAPI->renderPublications($args);
    }

    public function bibsonomy_add_css() {
        global $post;


        echo '<style type="text/css">' . "\n" .
        get_post_meta($post->ID, 'bibsonomycsl_css', true) .
        '</style>' . "\n";
        //return $css;
    }

    public function bibsonomycsl_enquere_styles() {

        wp_enqueue_style('Bibsonomy Bibliography Standard Style', '/wp-content/plugins/bibsonomy_csl/css/bibstyles.css', array(), false, 'screen');
    }

}

$bibsonomy = new BibsonomyCsl();

add_action('init', array(&$bibsonomy, 'activate'));
?>
