<?php

/*
  Plugin Name: BibSonomy CSL (incl. Tag Cloud Widget)
  Plugin URI: http://
  Description: Extension to create publication lists based on the Citation Style Language (CSL). Allows direct integration with the social bookmarking and publication sharing system Bibsonomy http://www.bibsonomy.org or different sources.
  Author: Sebastian Böttger, Andreas Hotho 
  Author URI: http://www.kde.cs.uni-kassel.de
  Version: 1.1.3
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
			array( 'description' => __( 'Widget to generate BibSonomy Tag Cloud', 'text_domain' ), ) // Args
		);
	}
	
	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
 	public function form( $instance ) {
		// outputs the options form on admin
		?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'header' ); ?>">Headline</label> 
			<input 
				   id="<?php echo $this->get_field_id( 'header' ); ?>" 
				   name="<?php echo $this->get_field_name( 'header' ); ?>" 
				   type="text" 
				   value="<?php echo esc_attr( $instance['header']  ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'type' ); ?>">Bibsonomy source type</label>
			<select 
				   id="<?php echo $this->get_field_id( 'type' ); ?>"
				   name="<?php echo $this->get_field_name( 'type' ); ?>"
				   >
				<option value="user" <?php echo ($instance['type'] == 'user') ? 'selected="selected"' : ''?>>user</option>
				<option value="group" <?php echo ($instance['type'] == 'group') ? 'selected="selected"' : ''?>>group</option>
				<option value="viewable" <?php echo ($instance['type'] == 'viewable') ? 'selected="selected"' : ''?>>viewable</option>
			</select>   
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'value' ); ?>">BibSonomy source type value</label>
			<input 
				   id="<?php echo $this->get_field_id( 'value' ); ?>" 
				   name="<?php echo $this->get_field_name( 'value' ); ?>" 
				   type="text" 
				   value="<?php echo esc_attr( $instance['value']  ); ?>" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'end' ); ?>">Number of tags</label>
			<input 
				   id="<?php echo $this->get_field_id( 'end' ); ?>" 
				   name="<?php echo $this->get_field_name( 'end' ); ?>" 
				   type="text" 
				   value="<?php echo (!empty($instance['end'])) ? esc_attr( $instance['end'] ) : 30 ; ?>" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('layout'); ?>">BibSonomy Tag Cloud Layout</label>
			<select 
				   id="<?php echo $this->get_field_id('layout'); ?>"
				   name="<?php echo $this->get_field_name('layout')?>">
				<option value="simple"    <?php echo ($instance['layout'] == 'simple'   ) ? 'selected="selected"' : ''?>>Simple</option>
				<option value="decorated" <?php echo ($instance['layout'] == 'decorated') ? 'selected="selected"' : ''?>>Decorated</option>
				<option value="button"    <?php echo ($instance['layout'] == 'button'   ) ? 'selected="selected"' : ''?>>Button Style</option>
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
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['header'] = strip_tags( $new_instance['header'] );
		$instance['type']	= strip_tags( $new_instance['type']   );
		$instance['value']  = strip_tags( $new_instance['value']  );
		$instance['layout'] = strip_tags( $new_instance['layout'] );
		$instance['end'] = strip_tags( $new_instance['end'] );
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
	public function widget( $args, $instance ) {
		extract( $args );
		$header = '<h3 class="widget-title">'.$instance['header'].'</h3>';
		$type = $instance['type'];
		$value = $instance['value'];
		$layout = $instance['layout'];
		$end = @$instance['end'];
		echo $before_widget;
		if ( ! empty( $type )  &&  ! empty( $value ) ) {
			echo "<h2>$header</h2>";
			//echo '<div style="text-align:center">';
			echo $this->renderTagCloud($type, $value, $layout, $end);
			//echo '</div>';
		}
		echo $after_widget;
	}
	
	public function renderTagCloud($type, $value, $layout, $end) {
		
		//print_r($layout);
		
		switch($layout) {
			
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
		$url  = "http://".$options['user'].":".$options['apikey']."@";
		$url .= $options['bibsonomyhost']."/api/tags?$type=$value&format=json&order=frequency&end=$end";
		
		$bibApiUrl = new BibsonomyCsl_Url($url);
		$request = new CurlHttpRequest($bibApiUrl);
		return json_decode($request->send()->getBody()); 
	}
	
	
	public function buttonstyleTagCloud($type, $value, $end) {
		global $BIBSONOMY_OPTIONS;
		$json = $this->fetchTags($type, $value, $end);
		
		$maxcount = $json->tags->tag[0]->usercount;
		$out = array();
		
		foreach($json->tags->tag as $tag) {
			$min = 10;
			$max = 15;
			
			$count = $tag->usercount;
			
			$size = ($count/$maxcount)*($max - $min) + $min;
			$size = ceil($size);
			$weight = ceil( ($count/$maxcount) * 8 + 1 ) * 100;
			$color = ceil(  ($count/$maxcount) * 10 + 3 );
			
			$color = dechex(16 - $color);
			
			$color = $color.$color.$color;
			
			$out[$tag->name] = '<a href="http://'.$BIBSONOMY_OPTIONS['bibsonomyhost'].'/'.$type.'/'.$value.'/'
				.urlencode($tag->name).'" target="_blank" style="
					margin: 5px; 
					background-color: #efefef; 
					padding: 0 0.5em; 
					color: #'.$color.'; 
					border-radius: 4px; 
					border: 1px solid #ddd; 
					line-height: 20px; 
					display: block; 
					float: left; 
					font-size: '.$size.'px; 
					font-weight: '.$weight.'"
				>'.$tag->name.'</a> ';
		}
		sort($out);
		$str = '<div style="clear:left;">';
	
		foreach($out as $key => $val) {
			$str .= $val;
		}
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
		
		foreach($json->tags->tag as $tag) {
			
			$max = 2;
			$min = 0.7;
			
			$count = $tag->usercount;
			
			$size = ($count/$maxcount)*($max - $min) + $min;
			
			$out[$tag->name] = '<a href="http://'.$BIBSONOMY_OPTIONS['bibsonomyhost'].'/'.$type.'/'.$value.'/'.urlencode($tag->name).'" target="_blank" style="font-size: '.sprintf("%01.2f",$size).'em;">'.$tag->name.'</a> ';
		}
		sort($out);
		$str = "";
	
		foreach($out as $key => $val) {
			$str .= $val;
		}
		
		return $str;
		
	}
	
	public function decoratedTagCloud($type, $value, $end) {
		global $BIBSONOMY_OPTIONS;
		$json = $this->fetchTags($type, $value, $end);
		
		//$maxcount = $json->tags->tag[0]->usercount;
		$out = array();
		$path_l = "wp-content/plugins/bibsonomy_csl/img/bg_tag_left.png";
		$path_r = "wp-content/plugins/bibsonomy_csl/img/bg_tag_right.png";
		foreach($json->tags->tag as $tag) {
			$out[$tag->name] = '<span style="display: block; margin: 5px; float: left;"><a href="http://'.$BIBSONOMY_OPTIONS['bibsonomyhost'].'/'.$type.'/'.$value.'/'.urlencode($tag->name).'" target="_blank" style="color: #1982D1; float: left; background: transparent url('.home_url( $path_l ).') top left no-repeat; padding: 2px 0px 6px 18px; font-weight: 200; font-size: 12px; line-height: 12px !important;">'.$tag->name.'</a><span style="background: transparent url('.home_url( $path_r ).') top left no-repeat; padding: 2px 2px 4px 1px; width: 9px;">&nbsp;</span></span>';
		}
		
		sort($out);
		$str = "";
	
		foreach($out as $key => $val) {
			$str .= $val;
		}
		
		return $str;
	}
	
}


add_action( 'widgets_init', function(){
     return register_widget( 'BibsonomyTagCloudWidget' );
});


?>
