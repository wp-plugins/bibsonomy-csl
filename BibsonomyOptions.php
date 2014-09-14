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
 * Description of BibsonomyOptions
 *
 * @author Sebastian Böttger
 */
class BibsonomyOptions {

	
	public function bibsonomy_add_settings_page() {
		add_options_page('Bibsonomy CSL', 'Bibsonomy CSL', 'manage_options', 'bibsonomy_csl', array(&$this, 'bibsonomy_options_page') );
		add_action('admin_init', array(&$this, 'bibsonomy_admin_init') );
	}
	
	public function bibsonomy_admin_init() {
		
		//register settings
		register_setting(
			'bibsonomy_options',
			'bibsonomy_options',
			array(&$this, 'bibsonomy_validate_options')
		);
		
		//add setting sections
		add_settings_section(
			'bibsonomy_main', 
			'Bibsonomy API settings',
			array(&$this, 'bibsonomy_section_text'),
			'bibsonomy'
		); 
		
		//adds bibsonomy host field
		add_settings_field(
			'bibsonomy_text_bibsonomyhost',
			'Enter your BibSonomy Host here',
			array(&$this, 'bibsonomy_setting_bibsonomyhost' ),
			'bibsonomy',
			'bibsonomy_main'
		);
		
		//adds user id field
		add_settings_field(
			'bibsonomy_text_user',
			'Enter your BibSonomy/PUMA user ID here',
			array(&$this, 'bibsonomy_setting_user' ),
			'bibsonomy',
			'bibsonomy_main'
		);
		
		//adds user API key field
		add_settings_field(
			'bibsonomy_text_apikey',
			'Enter your BibSonomy API key here',
			array(&$this, 'bibsonomy_setting_apikey' ),
			'bibsonomy',
			'bibsonomy_main'
		);
		
	}

	public function bibsonomy_setting_bibsonomyhost() {
		$options = get_option('bibsonomy_options');
		$bibsonomyhost = $options['bibsonomyhost'];
		echo '<p style="padding: 0; margin: 0; line-height: inherit;">http://<input type="text" id="bibsonomyhost" name="bibsonomy_options[bibsonomyhost]" ';
		if(isset($bibsonomyhost) && !empty($bibsonomyhost)) {
			echo 'value="'.$bibsonomyhost.'"';
		} else {
			echo 'value="www.bibsonomy.org"';
		}
		echo ' />';
	}
	
	public function bibsonomy_setting_user() {
		$options = get_option('bibsonomy_options');
		$user = $options['user'];
		echo '<input type="text" id="userid" name="bibsonomy_options[user]" value="'.$user.'" />';
	}
	
	public function bibsonomy_setting_apikey() {
		$options = get_option('bibsonomy_options');
		$apikey = $options['apikey'];
		echo '<input type="text" id="apikey" name="bibsonomy_options[apikey]" value="'.$apikey.'" />';
	}

	
	public function bibsonomy_section_text() {
		echo '<p>Enter your BibSonomy API settings.</p>';
	}
	
	
	public function bibsonomy_options_page() {
		?>
			<div class="wrap">
				<?php screen_icon(); ?>
				<h2>Bibsonomy CSL</h2>
				<form action="options.php" method="post">
					<?php settings_fields('bibsonomy_options'); ?>
					<?php do_settings_sections('bibsonomy'); ?>
					<input name="submit" type="submit" value="Save changes" />
				</form>
			</div>

		<?php
	}
	
	public function bibsonomy_validate_options($input) {

		$valid = array();
		$valid['user'] = $input['user'];
		$valid['apikey'] = $input['apikey'];
		$valid['bibsonomyhost'] = str_replace("http://", "", $input['bibsonomyhost']);
		
		return $valid;
	}

	
}

