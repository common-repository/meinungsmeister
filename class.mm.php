<?php

class Meinungsmeister {
	const LOG_PATH = false; //'/Volumes/RAMDISK/wp-mm.txt';
	const MICRODATA_CACHE_TIME = 7200;
	
	private $initiated = false;
	private $options = null;
	
	public function __construct() {
		register_activation_hook( constant('MEINUNGSMEISTER__BOOTFILE'), Array( $this, 'plugin_activation' ) );
		register_deactivation_hook( constant('MEINUNGSMEISTER__BOOTFILE'), Array( $this, 'plugin_deactivation' ) );
		
		add_action( 'init', Array( $this, 'init' ) );
		add_filter( 'plugin_action_links_' . constant('MEINUNGSMEISTER__BASENAME'), Array($this, 'plugin_action_links') );
	}
	
	public function init() {
		$this->log("init". (is_admin()?" (isadmin)":""));
		if ( !$this->initiated ) {
			$this->init_hooks();
		}
	}
	
	private function init_hooks() {
		$this->initiated = true;
		if ( is_admin() ) {
			add_action( 'admin_menu', Array($this, 'admin_menu') );
			add_action( 'admin_init', Array($this, 'admin_init') );
		} else {
			add_action( 'wp_enqueue_scripts', Array($this, 'wp_enqueue_scripts' ) ); 
			add_action( 'wp_head', Array($this, 'wp_head' ) );
			add_action( 'wp_footer', Array($this, 'wp_footer' ) );
		}
	}
	
	
	public function admin_menu() {
		$this->log("admin_menu");
		add_options_page( 'Meinungsmeister', 'Meinungsmeister', 'manage_options', 'meinungsmeister', Array($this, 'display_options_page' ) );
	}
	
	public function admin_init() {
		$this->log("admin_init");
		add_settings_section( "mm_section1", "Konfiguration", Array( $this, 'display_section1' ), 'meinungsmeister' );
		add_settings_field( "mm_shortcode", "GoLocal Kurz-Id", Array( $this, 'display_text' ), 'meinungsmeister', "mm_section1", "mm_shortcode");
		add_settings_field( "mm_widget", "Widget", Array( $this, 'display_option' ), 'meinungsmeister', "mm_section1", "mm_widget");
		add_settings_field( "mm_microdata", "Mikrodaten", Array( $this, 'display_option' ), 'meinungsmeister', "mm_section1", "mm_microdata");
		register_setting( 'meinungsmeister', 'meinungsmeister' );
	}
	
	public function wp_enqueue_scripts() {
		$this->log("wp_enqueue_scripts is_front_page=".is_front_page());
		$mm_shortcode = $this->getOption('mm_shortcode', '');
		$mm_widget = $this->getOption('mm_widget', '');
		if (strlen($mm_shortcode) && ($mm_widget=="A" || ($mm_widget=="H" && is_front_page()))) {
			$url = 'https://www.meinungsmeister.de/js/widget/mm-swidget.js?golocalid=' . $mm_shortcode;
			wp_enqueue_script('meinungsmeister-widget', $url, Array(), false, true);
		}
	}
	
	public function wp_head() {
		$this->log("wp_head is_front_page=".is_front_page());
		
		//print '<script type="text/javascript" src="https://www.meinungsmeister.de/js/widget/mm-widget.js?golocalid=8ONV&containerid=myWidgetContainer" charset="utf-8"></script>';
	}
	
	public function wp_footer() {
		$this->log("wp_footer is_front_page=".is_front_page());
		
		$mm_shortcode = $this->getOption('mm_shortcode', '');
		$mm_microdata = $this->getOption('mm_microdata', '');
		
		if (strlen($mm_shortcode) && ($mm_microdata=="A" || ($mm_microdata=="H" && is_front_page()))) {
			print $this->getMicrodataCode($mm_shortcode);
		}
		
		//print '<div id="myWidgetContainer"></div>';
		//print '<script type="text/javascript" src="https://www.meinungsmeister.de/js/widget/mm-widget.js?golocalid=8ONV&containerid=myWidgetContainer" charset="utf-8"></script>';
	}
	
	public function plugin_action_links($links) {
		$this->log("plugin_action_links");
		print_r($links);
				array_unshift($links, '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=meinungsmeister') ) .'">' . __( 'Settings' ) . '</a>');
		return $links;
	}
	
	
	public function display_section1() {
		$this->log("display_section1");
	}
	
	public function display_text($name) {
		$this->log("display_text ".$name);
		$text = $this->getOption($name, '');
		echo '<input name="meinungsmeister['.$name.']" id="'.$name.'" type="text" value="'.htmlentities($text).'" />';
	}
	
	public function display_option($name) {
		$this->log("display_option ".$name);
		$opt = $this->getOption($name, '');
		$opts = Array("N" => "Deaktiviert", "H" => "Nur auf der Startseite", "A" => "Immer anzeigen");
		if (!in_array($opt, array_keys($opts))) $opt = "N";
		foreach($opts as $k=>$v) {
			echo '<p>';
			echo '<label>';
			echo '<input type="radio" name="meinungsmeister['.$name.']" value="'.$k.'" '.($opt==$k?'checked="checked"':'').' />';
			echo ' '.htmlentities($v).'</label>';
			echo '</p>';
		}
	}
	
	public function display_options_page() {
		$this->log("display_options_page");
		
		$mm_shortcode = $this->getOption('mm_shortcode', '');
		if ($mm_shortcode) {
			$data = $this->getMicrodataCode($mm_shortcode, 0);
			if (strlen($data)) {
				try {
					$data = json_decode($data, 1);
				} catch (Exception $e) {
					$data = null;
				}
			} else {
				$data = null;
			}
		}
		
?>
		<div class="wrap">
			<h1>Meinungsmeister</h1>
			<form method="post" action="options.php">
			<?php
				settings_fields( 'meinungsmeister' );
				do_settings_sections( 'meinungsmeister' );
				submit_button();
?>
			</form>
<?php
			if (strlen($mm_shortcode)) {
				print '<h2>Anbieter-Daten</h2>';
				if ($data === null || !is_array($data) || !isset($data["name"])) {
					print "<b>Es gab ein Problem mit der Verbindung. Stimmt die Golocal-Id?</b>";
				} else {
					print "<b>Name:</b> ".htmlentities($data["name"])."<br/>";
					if (isset($data['aggregateRating']))
					print "<b>Bewertungen:</b> ".htmlentities($data['aggregateRating']["ratingCount"])."<br/>";
				}
			}
?>
		</div>
<?php
	}
	
	
	public function plugin_activation() {
		$this->log("activation");
	}
	
	
	public function plugin_deactivation() {
		$this->log("deactivation");
	}
	
	
	
// ####################################################################
	
	private function getOption($name, $default) {
		if ($this->options === null)
			$this->options = get_option("meinungsmeister", Array());
		return isset($this->options[$name]) ? $this->options[$name] : $default;
	}
	
	private function getMicrodataCode($golocalId, $addScriptTags=1) {
		$cacheKey =  "meinungsmeister_microdata_".$golocalId;
		
		$html = get_transient( $cacheKey );
		
		// WP 4.0/4.2/4.4 macht Probleme mit https unter Yosemite/El Capitan
		if (strlen($html)<5) {
			$url = 'http://www.meinungsmeister.de/rp/widgetmicrodata?golocalid='.$golocalId.'&callback=wordpress';
			
			$content = $this->http_get( $url );
			
			$html = "";
			if (preg_match('#wordpress\((.*?)\)#', $content, $match)) {
				try {
					$html = json_decode('['.$match[1].']', 1);
					$html = (is_array($html) && count($html)) ? $html[0] : '';
				} catch (Exception $e) { $html = ""; }
			}
			if (strlen($html)>5)
				set_transient( $cacheKey, $html, self::MICRODATA_CACHE_TIME);
		}
		
		if ($addScriptTags && strpos($html, '<script') === false) {
			$html = '<script type="application/ld+json" id="meinungsmeister-json-ld-data">'.PHP_EOL.
					$html.PHP_EOL.
					'</script>';
		}
		
		return $html;
	}
	
	
	private function log($text) {
		if (self::LOG_PATH) {
			file_put_contents(self::LOG_PATH, date("Y-m-d H:i:s") . " " . $text . "\n", FILE_APPEND);
		}
	}
	
	
	private function http_get( $url ) {
		if (is_array($request)) $request = json_encode($request);
		
		$ua = sprintf( 'WordPress/%s | Meinungsmeister/%s', $GLOBALS['wp_version'], constant('MEINUNGSMEISTER__VERSION') );
		
		$content_length = strlen( $request );
		
		$args = Array(
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent'  => $ua,
			'blocking'    => true,
			'compress'    => true,
			'decompress'  => true
		);
		
		$response = wp_remote_get( $url, $args );
		
		if (is_array($response) && isset($response["body"]))
			return $response["body"];
	}
	
}