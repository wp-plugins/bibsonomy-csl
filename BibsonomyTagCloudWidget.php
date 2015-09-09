<?php
/*
  Plugin Name: BibSonomy/PUMA CSL - Publications & Tag Cloud Widget
  Plugin URI: http://www.bibsonomy.org/help_en/Wordpress%20Plugin%20bibsonomy_csl
  Description: Plugin to create tag clouds from BibSonomy or PUMA.
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

require_once 'lib/bibsonomy/BibsonomyAPI.php';
require_once 'lib/bibsonomy/Url.php';
require_once 'lib/bibsonomy/CurlHttpRequest.php';
require_once 'lib/bibsonomy/CurlHttpResponse.php';

/**
 * Description of BibsonomyTagWidget
 *
 * @author Sebastian Böttger
 */
class BibsonomyTagCloudWidget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    public function __construct() {
        parent::__construct(
                'bibsonomy_tag_cloud_widget', // Base ID
                'BibsonomyTagCloudWidget', // Name
                array('description' => __('Widget to generate BibSonomy Tag Cloud', 'text_domain'),) // Args
        );
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance) {
        // outputs the options form on admin
        ?>

        <p>
            <label for="<?php echo $this->get_field_id('header'); ?>">Headline</label> 
            <input 
                id="<?php echo $this->get_field_id('header'); ?>" 
                name="<?php echo $this->get_field_name('header'); ?>" 
                type="text" 
                value="<?php echo esc_attr($instance['header']); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('type'); ?>">Bibsonomy source type</label>
            <select 
                id="<?php echo $this->get_field_id('type'); ?>"
                name="<?php echo $this->get_field_name('type'); ?>"
                >
                <option value="user" <?php echo ($instance['type'] == 'user') ? 'selected="selected"' : '' ?>>user</option>
                <option value="group" <?php echo ($instance['type'] == 'group') ? 'selected="selected"' : '' ?>>group</option>
                <option value="viewable" <?php echo ($instance['type'] == 'viewable') ? 'selected="selected"' : '' ?>>viewable</option>
            </select>   
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('value'); ?>">BibSonomy source type value</label>
            <input 
                id="<?php echo $this->get_field_id('value'); ?>" 
                name="<?php echo $this->get_field_name('value'); ?>" 
                type="text" 
                value="<?php echo esc_attr($instance['value']); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('end'); ?>">Number of tags</label>
            <input 
                id="<?php echo $this->get_field_id('end'); ?>" 
                name="<?php echo $this->get_field_name('end'); ?>" 
                type="text" 
                value="<?php echo (!empty($instance['end'])) ? esc_attr($instance['end']) : 30; ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('layout'); ?>">BibSonomy Tag Cloud Layout</label>
            <select 
                id="<?php echo $this->get_field_id('layout'); ?>"
                name="<?php echo $this->get_field_name('layout') ?>">
                <option value="simple"    <?php echo ($instance['layout'] == 'simple' ) ? 'selected="selected"' : '' ?>>Simple</option>
                <option value="decorated" <?php echo ($instance['layout'] == 'decorated') ? 'selected="selected"' : '' ?>>Decorated</option>
                <option value="button"    <?php echo ($instance['layout'] == 'button' ) ? 'selected="selected"' : '' ?>>Button Style</option>
            </select>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['header'] = strip_tags($new_instance['header']);
        $instance['type'] = strip_tags($new_instance['type']);
        $instance['value'] = strip_tags($new_instance['value']);
        $instance['layout'] = strip_tags($new_instance['layout']);
        $instance['end'] = strip_tags($new_instance['end']);
        return $instance;
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance) {
        extract($args);
        $header = '<h3 class="widget-title">' . $instance['header'] . '</h3>';
        $type = $instance['type'];
        $value = $instance['value'];
        $layout = $instance['layout'];
        $end = @$instance['end'];
        echo $before_widget;
        if (!empty($type) && !empty($value)) {
            echo "<h2>$header</h2>";
            //echo '<div style="text-align:center">';
            echo $this->renderTagCloud($type, $value, $layout, $end);
            //echo '</div>';
        }
        echo $after_widget;
    }

    public function renderTagCloud($type, $value, $layout, $end) {

        //print_r($layout);

        switch ($layout) {

            case 'decorated':
                return $this->decoratedTagCloud($type, $value, $end);

            case 'button':
                return $this->buttonstyleTagCloud($type, $value, $end);

            case 'simple':
            default:
                return $this->simpleTagCloud($type, $value, $end);
        }
    }

    public function fetchTags($type, $value, $end = 30) {
        global $BIBSONOMY_OPTIONS;
        $options = $BIBSONOMY_OPTIONS;
        $url = "http://" . $options['user'] . ":" . $options['apikey'] . "@";
        $url .= $options['bibsonomyhost'] . "/api/tags?$type=$value&format=json&order=frequency&end=$end";

        $bibApiUrl = new BibsonomyCsl_Url($url);
        $request = new CurlHttpRequest($bibApiUrl);
        return json_decode($request->send()->getBody());
    }

    public function buttonstyleTagCloud($type, $value, $end) {
        global $BIBSONOMY_OPTIONS;
        $json = $this->fetchTags($type, $value, $end);

        $maxcount = $json->tags->tag[0]->usercount;
        $out = array();

        foreach ($json->tags->tag as $tag) {
            $min = 10;
            $max = 15;

            $count = $tag->usercount;

            $size = ($count / $maxcount) * ($max - $min) + $min;
            $size = ceil($size);
            $weight = ceil(($count / $maxcount) * 8 + 1) * 100;
            $color = ceil(($count / $maxcount) * 10 + 3);

            $color = dechex(16 - $color);

            $color = $color . $color . $color;

            $out[$tag->name] = '<a href="http://' . $BIBSONOMY_OPTIONS['bibsonomyhost'] . '/' . $type . '/' . $value . '/'
                    . urlencode($tag->name) . '" target="_blank" style="
					
					color: #' . $color . '; 
					font-size: ' . $size . 'px; 
					font-weight: ' . $weight . '"
				>' . $tag->name . '</a> ';
        }
        sort($out);
        $str = '<div class="bibsonomycsl_tagcloud bibsonomycsl_tagcloud_buttonstyle">';

        foreach ($out as $key => $val) {
            $str .= $val;
        }
        $str .= '<p style="clear:left;"><!-- --></p>';
        $str .= '</div>';
        return $str;
    }

    /**
     *
     * @param string $type
     * @param string $value 
     * @return string rendered TagCloud
     */
    public function simpleTagCloud($type, $value, $end) {
        global $BIBSONOMY_OPTIONS;
        $json = $this->fetchTags($type, $value, $end);

        $maxcount = $json->tags->tag[0]->usercount;
        $out = array();

        foreach ($json->tags->tag as $tag) {

            $max = 2;
            $min = 0.7;

            $count = $tag->usercount;

            $size = ($count / $maxcount) * ($max - $min) + $min;

            $out[$tag->name] = '<a href="http://' . $BIBSONOMY_OPTIONS['bibsonomyhost'] . '/' . $type . '/' . $value . '/' . urlencode($tag->name) . '" target="_blank" style="font-size: ' . sprintf("%01.2f", $size) . 'em;">' . $tag->name . '</a> ';
        }
        sort($out);
        $str = '<div class="bibsonomycsl_tagcloud bibsonomycsl_tagcloud_simplestyle">';

        foreach ($out as $key => $val) {
            $str .= $val;
        }
        $str .= '<p style="clear:left;"><!-- --></p>';
        $str .= '</div>';
        
        return $str;
    }

    public function decoratedTagCloud($type, $value, $end) {
        global $BIBSONOMY_OPTIONS;
        $json = $this->fetchTags($type, $value, $end);

        //$maxcount = $json->tags->tag[0]->usercount;
        $out = array();
        
        $path_l = plugins_url('/bibsonomy-csl/img/bg_tag_left.png');
        $path_r = plugins_url('/bibsonomy-csl/img/bg_tag_right.png');
        
        foreach ($json->tags->tag as $tag) {
            $out[$tag->name] = '<span><a href="http://' . $BIBSONOMY_OPTIONS['bibsonomyhost'] . '/' . $type . '/' . $value . '/' . urlencode($tag->name) . '" target="_blank">' . $tag->name . '</a><span>&nbsp;</span></span>';
        }

        sort($out);
        $str = '<div class="bibsonomycsl_tagcloud bibsonomycsl_tagcloud_decoratedstyle">';

        foreach ($out as $key => $val) {
            $str .= $val;
        }
        
        $str .= '<p style="clear:left;"><!-- --></p>';
        $str .= '</div>';
        
        return $str;
    }

}

add_action('widgets_init', function() {
    return register_widget('BibsonomyTagCloudWidget');
});

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('bibsonomycsl-tagcloud', plugins_url('',__FILE__).'/css/bibsonomycsl-tagcloud.css');
});

