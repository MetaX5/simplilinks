<?php
/**
 * Plugin Name: SimpliLinks
 * Plugin URI: https://github.com/MetaX5
 * Description: Internal linking plugin.
 * Version: 0.1
 * Text Domain: simplilinks
 * Domain Path: /languages
 * Author: Mateusz Minkiewicz
 * Author URI: https://github.com/MetaX5
 */
 
if (!defined('ABSPATH')) exit; // Exit if accessed directly
remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );

function enqueue_admin_script( $hook ) {
    wp_register_style( 'mmlinks', plugin_dir_url( __FILE__ ) . '/css/mmlinks.css' );
    wp_enqueue_style( 'mmlinks' );
    wp_enqueue_script( 'mmlinks', plugin_dir_url( __FILE__ ) . '/js/mmlinks.js', array(), '1.0' );
	wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'my-script-handle', plugins_url('/js/mmlinks.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
}
add_action( 'admin_enqueue_scripts', 'enqueue_admin_script' );

function my_init() {
    load_plugin_textdomain( 'simplilinks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'my_init' );

function my_plugin_load_my_own_textdomain( $mofile, $domain ) {
    if ( 'simplilinks' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
        $locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
        $mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
    }
    return $mofile;
}
add_filter( 'load_textdomain_mofile', 'my_plugin_load_my_own_textdomain', 10, 2 );


global $jal_db_version;
$jal_db_version = '1.0';

function jal_install() {
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . "mmlinks_frazy";
	$table_name2 = $wpdb->prefix . "mmlinks_wylosowane";
	$table_name3 = $wpdb->prefix . "mmlinks_link";
	$table_name4 = $wpdb->prefix . "mmlinks_conf";
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id_frazy mediumint(9) NOT NULL AUTO_INCREMENT,
		link varchar(1000) DEFAULT '' NOT NULL,
		fraza varchar(1000) DEFAULT '' NOT NULL,
		PRIMARY KEY  (id_frazy)
	) $charset_collate;";
	
	$sql1 = "CREATE TABLE $table_name2 (
		id_wylosowane mediumint(9) NOT NULL AUTO_INCREMENT,
		link varchar(1000) DEFAULT '' NOT NULL,
		fraza varchar(1000) DEFAULT '' NOT NULL,
		id_wpisu mediumint(9) NOT NULL,
		PRIMARY KEY  (id_wylosowane)
	) $charset_collate;";	
	
	$sql2 = "CREATE TABLE $table_name3 (
		id_link mediumint(9) NOT NULL AUTO_INCREMENT,
		link varchar(1000) DEFAULT '' NOT NULL,
		PRIMARY KEY  (id_link)
	) $charset_collate;";
	
	$sql3 = "CREATE TABLE $table_name4 (
		id_conf mediumint(9) NOT NULL AUTO_INCREMENT,
		kolor varchar(1000) DEFAULT '' NOT NULL,
		orientacja varchar(1000) DEFAULT '' NOT NULL,
		PRIMARY KEY  (id_conf)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	dbDelta( $sql1 );
	dbDelta( $sql2 );
	dbDelta( $sql3 );

	add_option( 'jal_db_version', $jal_db_version );
}
register_activation_hook( __FILE__, 'jal_install' );

add_action('wp_head', 'my_custom_styles', 100);

function my_custom_styles() {
	global $wpdb;
	$nazwa_tabeli = $wpdb->prefix . "mmlinks_conf";
	$conf_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $nazwa_tabeli WHERE id_conf = %d", 1 ) );
	
	if ( $conf_row !== null ) {
		echo "
		<style>
			.mmlinks-anchor {
				color: ".$conf_row->kolor.";
			}
		</style>";
	}
}

function mmlinks_delete_plugin_database_tables() {
	global $wpdb;
	$tableArray = [   
		$wpdb->prefix . "mmlinks_frazy",
		$wpdb->prefix . "mmlinks_wylosowane",
		$wpdb->prefix . "mmlinks_link",
		$wpdb->prefix . "mmlinks_conf"
	];

	foreach ($tableArray as $tablename) {
		$wpdb->query("DROP TABLE IF EXISTS $tablename");
	}
}
register_uninstall_hook(__FILE__, 'mmlinks_delete_plugin_database_tables');

function mmlinks_shortcode($atts) {
	global $wpdb;
	$post_id = get_the_ID();
	$post_id = intval($post_id);
	$content = '';
	$table_name = $wpdb->prefix . "mmlinks_wylosowane";
	$nazwa_tabeli = $wpdb->prefix . "mmlinks_conf";
	$conf_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $nazwa_tabeli WHERE id_conf = %d", 1 ) );
	$results = $wpdb->get_results(
		"
			SELECT *
			FROM $table_name
			WHERE id_wpisu = $post_id
		"
	);
	
	$links = array_column($results, 'link');
	$links = array_unique($links);
	$results = array_filter($results, function ($key, $value) use ($links) {
		return in_array($value, array_keys($links));
	}, ARRAY_FILTER_USE_BOTH);
	 
	$index = 0;
	
	foreach ( $results as $result ) {

		if ( $conf_row !== null ) {
			if ( $conf_row->orientacja === "poziomo" ) {
				if ( count($results) === $index+1 ) {
					$content .= '<a class="mmlinks-anchor" href="'.$result->link.'">'.$result->fraza.'</a>';
					break;
				}
				$content .= '<a class="mmlinks-anchor" href="'.$result->link.'">'.$result->fraza.'</a> | ';
			} else {
				$content .= '<a class="mmlinks-anchor" href="'.$result->link.'">'.$result->fraza.'</a><br>';
			}
			
		} else {
			$content .= '<a class="mmlinks-anchor" href="'.$result->link.'">'.$result->fraza.'</a><br>';
		}
		$index++;
	}
	
    return $content;
}

add_shortcode('simplilinks', 'mmlinks_shortcode');

function mmlinks_dodanie_do_menu() {
	add_menu_page('Otwórz','SimpliLinks','administrator','mmlinks','mmlinks_open','dashicons-admin-links','24');
	add_submenu_page('mmlinks', esc_html__('Add links', 'simplilinks'), esc_html__('Add links', 'simplilinks'), 'administrator', 'mmlinks','mmlinks_open' );
	add_submenu_page('mmlinks', esc_html__('Link database', 'simplilinks'), esc_html__('Link database', 'simplilinks'), 'administrator', 'mmlinks_all','mmlinks_all' );
	add_submenu_page('mmlinks', esc_html__('Settings', 'simplilinks'), esc_html__('Settings', 'simplilinks'), 'administrator', 'mmlinks_config','mmlinks_config' );
}
add_action ('admin_menu','mmlinks_dodanie_do_menu');

function mmlinks_generuj_zbior( $link ) {
	global $wpdb;
	$table_name = $wpdb->prefix . "mmlinks_frazy";
	$results = $wpdb->get_results(
		"
			SELECT id_frazy, link
			FROM $table_name
		"
	);
	
	$zbior = array();
	foreach ( $results as $result ) {
		if ( $result->link === $link ) continue;
		$zbior[] = $result->id_frazy;
	}
	
	return $zbior;
}

function mmlinks_losuj_unikalne($zbior, $ile_wylosowac){
	
	$wylosowane_liczby = array();
	
	for ($i = 0; $i < $ile_wylosowac; $i++) {
		if ( count($zbior) === 0 ) {
			return 'str';
		}
		$wylosowany_index = array_rand($zbior,1);
		$wylosowane_liczby[] = $zbior[$wylosowany_index];
		unset($zbior[$wylosowany_index]);
	}
	return $wylosowane_liczby;
}

function mmlinks_open() {
	$query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => -1 ) );
	$posts = $query->posts;
	echo '<div style="width:100%">';
	echo '<form action="'.$_SERVER['PHP_SELF'].'?page=mmlinks" method="post">';
	echo '<div class="mm-left-col">';
	echo '<div class="mm_va">';
	echo '<h3>';
	esc_html_e( 'Select the post for which you want to add phrases', 'simplilinks');
	echo '</h3>';

	if ( $query->have_posts() ) {
		echo '<input style="box-shadow: 0 0 0 transparent;
			border-radius: 4px;
			border: 1px solid #8c8f94;
			background-color: #fff;
			color: #2c3338;
			padding: 0 8px;
			line-height: 2;
			min-height: 30px;
			width:60%;
			margin-top: 3rem;" placeholder="';
			_e("Search", 'simplilinks');
			echo '" list="mm" name="mm_id">'; 
		echo '<datalist id="mm">';
		foreach($posts as $post) {
			echo '<option value="'.$post->ID.'">'.$post->post_title.'</option>';
		}
		echo '</datalist>';
	} else {
		echo '<p>';
		esc_html_e( 'No posts to display', 'simplilinks');
		echo '</p>';
	}
	
	wp_reset_postdata();
	printf ( '<br><br><input id="wybierz_link" class="button" name="submit" type="submit" value="%s">', esc_html__('Select', 'simplilinks') );
	
	echo '</div>';
	echo '</div>';
	
	echo '<div class="mm-right-col">';
	echo '<div class="mm_va">';

	if ( isset( $_POST['submit'] ) ) {
		$wybrany_link_id = $_POST['mm_id'];
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . "mmlinks_link", array( 'id_link' => 1 ) );
		$wpdb->insert( $wpdb->prefix . "mmlinks_link", array(
			'id_link' => 1,
			'link' => get_permalink( $wybrany_link_id )
		));	
		$post = get_post( $wybrany_link_id );
		echo '<div id="frazy">';
		printf ( '<br><h3>%1s: %2s</h3>' , esc_html__( 'Create phrases for a post', 'simplilinks'), $post->post_title );
		printf ('<input id="input_frazy" type="text" aria-autocomplete="list" autocomplete="off" placeholder="%s">', esc_html__('Type a phrase and press `ENTER`', 'simplilinks') );
		printf ('<div id="frazy-header"><span style="margin-left: 20px">%s</span></div>', esc_html__("Phrase", 'simplilinks') );
		echo '<ul id="lista_fraz"></ul>';
		echo '</div>';
		echo '<input type="hidden" id="frazy_array" name="frazy_array">';
		printf( '<input class="button button-primary button-large" id="submit_frazy" name="submit_frazy" type="submit" value="%s">', esc_html__( 'Save', 'simplilinks') );
	}
	
	if ( isset( $_POST['submit_frazy'] ) ) {
		printf ( '<div id="mm_success"><span>%s!!!</span></div>', esc_html__( 'Successfully added phrases to the database', 'simplilinks') );
		global $wpdb;
		$frazy_array = explode( ',', $_POST['frazy_array'] );
		$nazwa = $wpdb->prefix . "mmlinks_link";
		$mylink = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $nazwa WHERE id_link = %d", 1 ) );

		for ( $i = 0; $i < count( $frazy_array ); $i++) {
			$wpdb->insert( $wpdb->prefix . "mmlinks_frazy", array(
				'link' => $mylink->link,
				'fraza' => $frazy_array[$i]
			));	
		}
		
		printf ( '<h3>%s.</h3>', esc_html__( 'Successfully added phrases to the database', 'simplilinks') );
	}
	
	echo '</div>';
	echo '</form>';
	echo '</div>';
}


function mmlinks_all() {
	global $wpdb;
	$link_array = array();
	$table_name = $wpdb->prefix . "mmlinks_frazy";
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$wpdb->delete( $table_name, array( 'id_frazy' => $_POST['wylosowane_usun'] ) );
		error_reporting(false);
		ini_set("display_errors", 0);
		header("Refresh:0");
	}
	
	$rows = $wpdb->get_results(
		"
			SELECT *
			FROM $table_name
			ORDER BY `link`
		"
	);
	
	echo '<form id="wylosowane" action="'.$_SERVER['PHP_SELF'].'?page=mmlinks_all" method="post">';
	printf ( '<input type="text" id="myInput" onkeyup="mm_Search()" placeholder="%1s">
			<table id="tabela">
				<tr>
					<th style="display:none">ID_Frazy</th>
					<th>%2s</th>
					<th>%3s</th>
					<th>%4s</th>
				</tr>', esc_html__( 'Search..', 'simplilinks'), esc_html__( 'Link', 'simplilinks'), esc_html__( 'Phrase', 'simplilinks'), esc_html__( 'Delete', 'simplilinks') );
		foreach ( $rows as $key => $row ) {
			echo '
				<tr>
					<td style="display:none">'.$row->id_frazy.'</td>
				';
			if ( ($key > 0) && $rows[$key-1]->link !== $rows[$key]->link ) {
				echo '<td style="border-bottom: 0; border-left: 0;">
				<a style="text-decoration:none;color:black" target="_blank" href="'.$row->link.'">'.get_the_title( url_to_postid($row->link) ).'</a><br>
				<a target="_blank" href="'.$row->link.'">'.$row->link.'</a>
				</td>';
			} else {
				if ( $key === 0 ) {
					echo '<td style="border-bottom: 0; border-left: 0;">
					<a style="text-decoration:none;color:black" target="_blank" href="'.$row->link.'">'.get_the_title( url_to_postid($row->link) ).'</a><br>
					<a target="_blank" href="'.$row->link.'">'.$row->link.'</a>
					</td>';
				} else {
					echo '<td style="border:0">
					<a style="text-decoration:none;color:black;opacity:0" target="_blank" href="'.$row->link.'">'.get_the_title( url_to_postid($row->link) ).'</a><br>
					<a style="opacity:0" target="_blank" href="'.$row->link.'">'.$row->link.'</a>				
					</td>';
				}
			}	
			echo '
					<td>'.$row->fraza.'</td>           
					<td class="iks"><li><button type="submit" style="" class="btn_d"></button></li></td>           
				</tr>
			';
		}
		echo '</table>';
		echo '</form>';
}

function mmlinks_config() {
	global $wpdb;
	echo '<div style="width:100%">';
	echo '<div class="mm-left-col">';
	echo '<div class="mm_va">';
	printf( '<h3>%s.</h3>', esc_html__( 'Enter the number of random phrases that will be displayed', 'simplilinks') );
	printf( '<h4>%s: <span style="color: red">[simplilinks]</span></h4>', esc_html__('Use shortcode to display phrases' , 'simplilinks') );
	echo '<form action="'.$_SERVER['PHP_SELF'].'?page=mmlinks_config" method="post">';
	printf( '<input style="margin-top: 3rem" id="ilosc_losowan" name="ilosc_losowan" type="number" min="1" step="1" placeholder="%s">', esc_html__('Number of draws', 'simplilinks') );
	printf( '<input class="button" id="submit_losowania" name="submit_losowania" type="submit" value="%s">', esc_html__('Random', 'simplilinks') );
	echo '</form>';
	if ( isset( $_POST['submit_losowania'] ) ) {
		$ilosc_losowan = $_POST['ilosc_losowan'];

		$table_name = $wpdb->prefix . "mmlinks_frazy";
		$results = $wpdb->get_results(
			"
				SELECT *
				FROM $table_name
			"
		);
		$posts_ids = get_posts(array(
			'fields'          => 'ids', // Only get post IDs
			'posts_per_page'  => -1
		));
		$table_name2 = $wpdb->prefix . "mmlinks_wylosowane";
		$wpdb->query("DELETE FROM $table_name2");
		$wpdb->query("alter table $table_name2 auto_increment = 0");
		
		for ($i = 0; $i < count($posts_ids); $i++ ) {
			$aktualne_id_wpisu = intval($posts_ids[$i]);
			$wylosowane = mmlinks_losuj_unikalne( mmlinks_generuj_zbior( get_permalink( $aktualne_id_wpisu ) ), $ilosc_losowan );
			
			if ( is_string( $wylosowane ) ) {
				printf( '<br><br><br><h2 style="color:red">%s.</h2>', esc_html__('Not enough phrases to complete the draw. Add more phrases to complete the draw or reduce the number of phrases drawn. The database of links and phrases drawn has been cleared', 'simplilinks') );
				return;
			} else {
				for ( $j = 0; $j < $ilosc_losowan; $j++ ) {
					
					$liczba = intval($wylosowane[$j]);
					$query = $wpdb->get_results(
						"
							SELECT *
							FROM $table_name
							WHERE id_frazy = $liczba
						"
					);
					
					foreach ($query as $row) {
						$wpdb->insert( $wpdb->prefix . "mmlinks_wylosowane", array(
							'link' => $row->link,
							'fraza' => $row->fraza,
							'id_wpisu' => $aktualne_id_wpisu
						));
					}
				}
			}
		}
		printf( '<br><br><h3>%1s.<br><br>%2s: <span style="color: red">[simplilinks]</span></h3>', esc_html__('Successfully drawn phrases for each entry', 'simplilinks'), esc_html__('Use shortcode to view them', 'simplilinks') );
	}
	
	echo '</div></div>';
	
	
	$nazwa_tabeli = $wpdb->prefix . "mmlinks_conf";
	if ( isset( $_POST['config-save'] ) ) {
		$wpdb->delete( $wpdb->prefix . "mmlinks_conf", array( 'id_conf' => 1 ) );
		$wpdb->insert( $wpdb->prefix . "mmlinks_conf", array(
			'id_conf' => 1,
			'kolor' => $_POST['kolor'],
			'orientacja' => $_POST['orientacja']
		));
		
		printf( '<div id="mm_success2"><span>%s!</span></div>', esc_html__('Settings saved successfully', 'simplilinks') );
	}
	$conf_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $nazwa_tabeli WHERE id_conf = %d", 1 ) );
	
	echo '<div class="mm-right-col">';
	echo '<div class="mm_va" style="align-items: center">';
	echo '<form action="'.$_SERVER['PHP_SELF'].'?page=mmlinks_config" method="post">';
	echo '<input type="hidden" id="kolor" name="kolor">';
	echo '<input type="hidden" id="orientacja" name="orientacja">';
	printf( '<h3>%s..</h3>', esc_html__('Choose the color of the links', 'simplilinks') );
	if ( $conf_row !==  null ) {
		echo '<input id="kolor-value" type="text" value="'.$conf_row->kolor.'" class="my-color-field" data-default-color="#000000">';
		if ( $conf_row->orientacja === "pionowo" ) {
			printf( '<h3 style="margin-top:3rem !important">%s..</h3>', esc_html__('Select link orientation', 'simplilinks') );
			echo '<img style="margin-right:1rem" class="mm-outline" width="200" src="'.plugins_url( '/images/pion.jpg', __FILE__ ).'" alt="pionowo">';
			echo '<img style="margin-top:2rem" width="200" src="'.plugins_url( '/images/poziom.jpg', __FILE__ ).'" alt="pionowo">';
		} else {
			printf( '<h3 style="margin-top:3rem !important">%s..</h3>', esc_html__('Select link orientation', 'simplilinks') );
			echo '<img style="margin-right:1rem" width="200" src="'.plugins_url( '/images/pion.jpg', __FILE__ ).'" alt="pionowo">';
			echo '<img class="mm-outline" style="margin-top:2rem" width="200" src="'.plugins_url( '/images/poziom.jpg', __FILE__ ).'" alt="pionowo">';
		}
	} else {
		echo '<input id="kolor-value" type="text" value="#000000" class="my-color-field" data-default-color="#000000">';
		printf( '<h3 style="margin-top:3rem !important">%s..</h3>', esc_html__('Select link orientation', 'simplilinks') );
		echo '<img style="margin-right:1rem" class="mm-outline" width="200" src="'.plugins_url( '/images/pion.jpg', __FILE__ ).'" alt="pionowo">';
		echo '<img style="margin-top:2rem" width="200" src="'.plugins_url( '/images/poziom.jpg', __FILE__ ).'" alt="pionowo">';
	}

	printf('<input id="config-save" name="config-save" style="margin-top: 2rem" class="button button-primary button-large" type="submit" value="%s"></input>', esc_html__('Save', 'simplilinks') );
	
	echo '</form>';
	echo '</div></div></div>';
}