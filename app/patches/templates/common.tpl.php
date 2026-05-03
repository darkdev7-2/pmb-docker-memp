<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: common.tpl.php,v 1.275.2.2 2021/07/27 13:44:31 dgoron Exp $

if (stristr($_SERVER['REQUEST_URI'], "tpl.php")) die("no access");

global $class_path, $include_path, $msg, $cms_active_toolkits, $css, $opac_ie_reload_on_resize, $opac_default_style_addon;
global $opac_show_social_network, $pmb_logs_activate, $std_header, $charset;
global $opac_biblio_name, $lvl, $opac_faviconurl, $opac_map_activate, $opac_map_base_layer_type, $javascript_path, $opac_allow_affiliate_search;
global $opac_allow_simili_search, $opac_visionneuse_allow, $opac_scan_request_activate, $opac_url_base, $opac_notice_enrichment, $opac_recherche_ajax_mode, $base_path;
global $inclus_header, $inclure_recherche, $short_header, $short_footer, $popup_header, $popup_footer, $liens_bas, $liens_bas_disabled, $opac_lien_bas_supplementaire, $opac_biblio_website;
global $opac_lien_moteur_recherche, $opac_accessibility, $accessibility, $home_on_left, $opac_logosmall, $common_tpl_lang_select, $home_on_top, $loginform, $opac_show_loginform;
global $opac_show_meteo, $opac_show_meteo_url, $meteo, $opac_biblio_town, $adresse, $opac_biblio_adr1, $opac_biblio_cp, $opac_biblio_country, $opac_biblio_phone, $opac_biblio_email;
global $opac_biblio_post_adress, $opac_facettes_ajax, $map_location_search, $facette, $lvl1, $footer, $inclus_footer, $std_header_suite, $opac_biblio_important_p1;
global $opac_biblio_important_p2, $footer_suite, $opac_biblio_preamble_p1, $opac_biblio_preamble_p2, $script_analytics_html, $liens_opac, $begin_result_liste, $opac_recherche_show_expand;

require_once($class_path."/common.class.php");
require_once($class_path."/sort.class.php");
require_once($class_path."/cms/cms_toolkits.class.php");
require_once($class_path."/html_helper.class.php");
require_once($class_path."/cookies_consent.class.php");

//Surlignage
require_once("$include_path/javascript/surligner.inc.php");

// template for PMB OPAC

// �l�ments standards pour les pages :
// $short_header
// $std_header
//
//$footer qui contient
//	$liens_bas : barre de liens bibli, google, pmb
//	contenu du div bandeau (bandeau de gauche) soit
//		$home
//		$loginform
//		$meteo
//		$adresse
//
//Classes et IDs utilis�s dans l'OPAC
//
//Tout est contenu dans #container
//
//Partie gauche (menu)
//	#bandeau
//		#accueil
//		#connexion
//		#meteo
//		#addresse
//		
//Partie droite (principale)
//	#intro (tout le bloc incluant pmb, nom de la bibli, message d'accueil)
//		#intro_pmb : pmb
//		#intro_message : message d'information s'il existe
//		#intro_bibli
//			h3 : nom de la bibli
//			p .intro_bibli_presentation_1 : texte de pr�sentation de la bibli
//	
//	#main : contient les diff�rents blocs d'affichage et de recherches (browsers)
//		div
//			h3 : nom du bloc
//			contenu du bloc
					

//R�cup�ration du login
if (!$_SESSION["user_code"]) {
	//Si pas de session
	$cb_=$msg['common_tpl_cardnumber_default'];
} else {
	//R�cup�ration des infos de connection
	$cb_=$_SESSION["user_code"];
}
$toolkits_scripts_html="";
if($cms_active_toolkits) {
	$cms_toolkits_scripts = cms_toolkits::load();
	if(count($cms_toolkits_scripts)) {
		$toolkits_scripts_html =implode('', $cms_toolkits_scripts);
	}
}
$stylescsscodehtml=HtmlHelper::getInstance()->getStyle($css);
//HEADER : short_header = pour les popups
//         std_header = pour les pages standards

// pb de resize de page avec IE6 et 7 : on force le rechargement de la page (position absolue qui reste absolue !)
if ($opac_ie_reload_on_resize) $iecssresizepb="onresize=\"history.go(0);\"";
else $iecssresizepb="";

if ($opac_default_style_addon) $css_addon = "
	<style type='text/css'>
	".$opac_default_style_addon."
		</style>";
else $css_addon="";

$script_analytics_html = common::get_script_analytics();

$std_header = "<!DOCTYPE html>
<html lang='".get_iso_lang_code()."'>
<head>
    ".common::get_metadata();

$title = common::get_html_title();

$std_header.="
	<title>".$title."</title>
	!!liens_rss!!
	".$toolkits_scripts_html.$stylescsscodehtml.$css_addon."
	<!-- css_authentication -->";
// FAVICON
if ($opac_faviconurl) $std_header.="	<link rel='SHORTCUT ICON' href='".$opac_faviconurl."' />";
else $std_header.="	<link rel='SHORTCUT ICON' href='".$base_path."/images/site/favicon.ico' />";
$std_header.="
	<script type=\"text/javascript\" src=\"includes/javascript/drag_n_drop.js\"></script>
	<script type=\"text/javascript\" src=\"includes/javascript/handle_drop.js\"></script>
	<script type=\"text/javascript\" src=\"includes/javascript/popup.js\"></script>
	".common::get_js_function_encode_url()."
	<script type='text/javascript'>
	  	if (!document.getElementsByClassName){ // pour ie
			document.getElementsByClassName = 
			function(nom_class){
				var items=new Array();
				var count=0;
				for (var i=0; i<document.getElementsByTagName('*').length; i++) {  
					if (document.getElementsByTagName('*').item(i).className == nom_class) {
						items[count++] = document.getElementsByTagName('*').item(i); 
				    }
				 }
				return items;
			 }
		}
	</script>
";

$src_maps_dojo_opac = '';
if($opac_map_activate){
	switch($opac_map_base_layer_type){
		case "GOOGLE" :
			$std_header.="<script src='http://maps.google.com/maps/api/js?v=3&amp;sensor=false'></script>";
			break;
	}
	$std_header.="<link rel='stylesheet' type='text/css' href='".$javascript_path."/openlayers/theme/default/style.css'/>";
	$std_header.="<script type='text/javascript' src='".$javascript_path."/openlayers/lib/OpenLayers.js'></script>";
	$src_maps_dojo_opac.= "<script type='text/javascript' src='".$javascript_path."/dojo/dojo/pmbmaps.js'></script>";
}
$std_header.= common::get_dojo_configuration();

$std_header.=$src_maps_dojo_opac;

$std_header.="<script type='text/javascript'>
	var pmb_img_patience = '".get_url_icon('patience.gif')."';
</script>";
$std_header .= common::get_js_script_social_network();

if($opac_allow_affiliate_search){
	$std_header.="
	<script type='text/javascript' src='includes/javascript/affiliate_search.js'></script>";
}
if($opac_allow_simili_search){
	$std_header.="
	<script type='text/javascript' src='includes/javascript/simili_search.js'></script>";
}
if($opac_visionneuse_allow) {
	$std_header.="
	<script type='text/javascript' src='".$base_path."/visionneuse/javascript/visionneuse.js'></script>";
}
if ($opac_scan_request_activate) {
	$std_header.="
	<script type='text/javascript' src='".$include_path."/javascript/scan_requests.js'></script>";
}

$std_header.="
	<script type='text/javascript' src='$include_path/javascript/http_request.js'></script>";

if (!cookies_consent::is_opposed_pmb_logs_service() && $pmb_logs_activate) {
	$std_header.="
	<script type='text/javascript' src='$include_path/javascript/track_clicks.js'></script>";
}

$std_header.="
	!!enrichment_headers!!
</head>

<body onload=\"window.defaultStatus='".$msg["page_status"]."';\" $iecssresizepb id=\"pmbopac\">";
if($opac_notice_enrichment == 0){
	$std_header.=common::get_js_function_record_display();
}
if($opac_recherche_ajax_mode){
	$std_header.="
	<script type='text/javascript' src='".$base_path."/includes/javascript/tablist_ajax.js'></script>";
}
$std_header.="
<script type='text/javascript' src='".$base_path."/includes/javascript/tablist.js'></script>
<script type='text/javascript' src='".$base_path."/includes/javascript/misc.js'></script>
	<div id='att' style='z-Index:1000'></div>
	<div id=\"container\"><div id=\"main\"><div id='main_header'>!!main_header!!</div><div id=\"main_hors_footer\">!!home_on_top!!
						\n";
$std_header.="
<script type='text/javascript' src='".$include_path."/javascript/auth_popup.js'></script>	
<script type='text/javascript' src='".$include_path."/javascript/pnb.js'></script>";

$inclus_header = "
!!liens_rss!!
!!enrichment_headers!!
".$toolkits_scripts_html.$stylescsscodehtml.$css_addon."	
<script type='text/javascript'>
	var pmb_img_patience = '".get_url_icon('patience.gif')."';
</script>";
if($opac_notice_enrichment == 0){
	$inclus_header.= common::get_js_function_record_display();
} 	
$inclus_header.= "
<script type='text/javascript' src='".$include_path."/javascript/http_request.js'></script>
<script type='text/javascript' src='".$include_path."/javascript/tablist_ajax.js'></script>
<script type='text/javascript' src='".$include_path."/javascript/tablist.js'></script>
<script type='text/javascript' src='".$include_path."/javascript/drag_n_drop.js'></script>
<script type='text/javascript' src='".$include_path."/javascript/handle_drop.js'></script>
<script type='text/javascript' src='".$include_path."/javascript/popup.js'></script>
".common::get_js_function_encode_url()."
<script type='text/javascript'>
  	if (!document.getElementsByClassName){ // pour ie
		document.getElementsByClassName = 
		function(nom_class){
			var items=new Array();
			var count=0;
			for (var i=0; i<document.getElementsByTagName('*').length; i++) {  
				if (document.getElementsByTagName('*').item(i).className == nom_class) {
					items[count++] = document.getElementsByTagName('*').item(i); 
			    }
			 }
			return items;
		 }
	}
</script>";
$inclus_header .= common::get_dojo_configuration();

$inclus_header .= common::get_js_script_social_network();

if($opac_allow_affiliate_search){
	$inclus_header.="
	<script type='text/javascript' src='includes/javascript/affiliate_search.js'></script>";
}
if($opac_allow_simili_search){
	$inclus_header.="
	<script type='text/javascript' src='includes/javascript/simili_search.js'></script>";
}
if($opac_visionneuse_allow) {
	$inclus_header.="
	<script type='text/javascript' src='".$opac_url_base."/visionneuse/javascript/visionneuse.js'></script>";
}
if ($opac_scan_request_activate) {
	$inclus_header.="
	<script type='text/javascript' src='".$opac_url_base."/includes/javascript/scan_requests.js'></script>";
}
if (!cookies_consent::is_opposed_pmb_logs_service() && $pmb_logs_activate) {
	$inclus_header.="
	<script type='text/javascript' src='$include_path/javascript/track_clicks.js'></script>";
}

$inclus_header.="

".(isset($inclure_recherche) ? $inclure_recherche : '')."
		
<div id='att' style='z-Index:1000'></div>
	<div id=\"container\"><div id=\"main\"><div id='main_header'>!!main_header!!</div><div id=\"main_hors_footer\">!!home_on_top!!
						\n";
$short_header = "<!DOCTYPE html>
<html lang='".get_iso_lang_code()."'>
<head>
<meta charset=\"".$charset."\">
<meta http-equiv='X-UA-Compatible' content='IE=Edge'>
<script type='text/javascript' src='includes/javascript/http_request.js'></script>
<script type='text/javascript' src='includes/javascript/auth_popup.js'></script>\n
<script type='text/javascript'>
var pmb_img_patience = '".get_url_icon('patience.gif')."';
</script>
".common::get_js_function_record_display()."
".common::get_dojo_configuration();

$short_header .= common::get_js_script_social_network();

if ($opac_scan_request_activate) {
	$short_header.="
	<script type='text/javascript' src='".$opac_url_base."/includes/javascript/scan_requests.js'></script>";
}
$short_header.="!!liens_rss!!
	".$toolkits_scripts_html.$stylescsscodehtml.$css_addon."
</head>
<body>";



$short_footer="</body></html>";

$popup_header = "<!DOCTYPE html>
<html lang='".get_iso_lang_code()."'>
<head>
	<meta charset=\"".$charset."\" />
	".$toolkits_scripts_html.$stylescsscodehtml.$css_addon."
	<title>".common::get_html_title()."</title>
    ".common::get_dojo_configuration()."
    ".common::get_js_function_encode_url()."
</head>
<body id='pmbopac' class='popup tundra'>
<script type='text/javascript'>
var opac_show_social_network =$opac_show_social_network;
var pmb_img_patience = '".get_url_icon('patience.gif')."';
</script>
".common::get_js_function_record_display()."
<script type='text/javascript' src='".$include_path."/javascript/http_request.js'></script>
<script type='text/javascript' src='".$include_path."/javascript/tablist.js'></script>
<script type='text/javascript' src='".$include_path."/javascript/misc.js'></script>
";

$popup_footer="</body></html>";



// liens du bas de la page
$liens_bas = "</div><!-- fin DIV main_hors_footer --><div id=\"footer\">

<span id=\"footer_rss\">
	<!-- rss -->
</span>
<span id=\"footer_link_sup\">
		$opac_lien_bas_supplementaire &nbsp;
</span>
";	
	
if ($opac_biblio_website)	$liens_bas .= "
<span id=\"footer_link_website\">
	<a class=\"footer_biblio_name\" href=\"$opac_biblio_website\" title=\"$opac_biblio_name\">$opac_biblio_name</a> &nbsp;
</span>	
";
$liens_bas .= "
<span id=\"footer_link_pmb\">
$opac_lien_moteur_recherche &nbsp;
		<a class=\"lien_pmb_footer\" href=\"https://www.sigb.net\" title=\"".$msg['common_tpl_motto']."\" target='_blank'>".$msg['common_tpl_motto_pmb']."</a> 	
</span>		
		
</div>" ;

// liens du bas de la page
$liens_bas_disabled = "</div><!-- fin DIV main_hors_footer --><div id=\"footer\"></div>";

// ACCESSIBILITE
if ($opac_accessibility) {
	$accessibility="<div id='accessibility'>\n
		<ul class='accessibility_font_size'>
			<li class='accessibility_font_size_small'><a href='javascript:set_font_size(-1);' title='".$msg["accessibility_font_size_small"]."'>A-</a></li>
			<li class='accessibility_font_size_normal'><a href='javascript:set_font_size(0);' title='".$msg["accessibility_font_size_normal"]."'>A</a></li>
			<li class='accessibility_font_size_big'><a href='javascript:set_font_size(1);' title='".$msg["accessibility_font_size_big"]."'>A+</a></li>
		</ul>
		</div>\n";
	if(isset($_SESSION["pmbopac_fontSize"])) {
		$accessibility.="<script type='text/javascript'>set_value_style('pmbopac', 'fontSize', '".$_SESSION["pmbopac_fontSize"]."');</script>";
	}
}

// HOME
$home_on_left = "<div id=\"accueil\">\n
<h3><span onclick='document.location=\"./index.php?\"' style='cursor: pointer;'>!!welcome_page!!</span></h3>\n";

if ($opac_logosmall<>"") $home_on_left .= "<p class=\"centered\"><a href='./index.php?'><img src='".$opac_logosmall."' alt='".$msg["welcome_page"]."'  style='border:0px' class='center'/></a></p>\n";
else $home_on_left .= "<p class=\"centered\"><a href='./index.php?'><img src='".get_url_icon('home.jpg')."' alt='".$msg["welcome_page"]."' style='border:0px' class='center'/></a></p>\n";
	
// affichage du choix de langue  
$common_tpl_lang_select="<div id='lang_select'><h3 ><span>!!msg_lang_select!!</span></h3>!!lang_select!!</div>\n";

$home_on_left.="!!common_tpl_lang_select!!
					</div><!-- fermeture #accueil -->\n" ;

// HOME lorsque le bandeau gauche n'est pas affich�
$home_on_top ="<div id='home_on_top'>
	<span onclick='document.location=\"./index.php?\"' style='cursor: pointer;'><img src='".get_url_icon("home.gif")."' align='absmiddle' /> ".$msg["welcome_page"]."</span>
	</div>
	";

// LOGIN FORM=0
// Si le login est autoris�, alors afficher le formulaire de saisie utilisateur/mot de passe ou le code de l'utilisateur connect�
$loginform='';
if ($opac_show_loginform) {
	$loginform ="<div id=\"connexion\">\n
			<!-- common_tpl_login_invite --><div id='login_form'>!!login_form!!</div>\n
			</div><!-- fermeture #connexion -->\n";
} else {
	if (!file_exists($include_path.'/ext_auth.inc.php')) {
		$_SESSION["user_code"]="";
	}
}

// METEO
if ($opac_show_meteo && $opac_show_meteo_url) {
	$meteo = "<div id=\"meteo\">\n
		<h3>".$msg['common_tpl_meteo_invite']."</h3>\n
		<p class=\"centered\">$opac_show_meteo_url</p>\n
		<small>".$msg['common_tpl_meteo']." $opac_biblio_town</small>\n
		</div><!-- fermeture # meteo -->\n";
} else {
	$meteo = "";
}

// ADRESSE
$adresse = "<div id=\"adresse\">\n
		<h3>!!common_tpl_address!!</h3>\n
		<span>
			$opac_biblio_name<br />
			$opac_biblio_adr1<br />
			$opac_biblio_cp $opac_biblio_town<br />
			$opac_biblio_country&nbsp;<br />
			$opac_biblio_phone<br />";
			if ($opac_biblio_email) $adresse.="<span id='opac_biblio_email'>
			<a href=\"mailto:$opac_biblio_email\" title=\"$opac_biblio_email\">!!common_tpl_contact!!</a></span>";
$adresse.="</span>" ;
$adresse.="
	    </div><!-- fermeture #adresse -->" ;

// bloc post adresse
if ($opac_biblio_post_adress){
	$adresse .= "<div id=\"post_adress\">\n
		<span>".$opac_biblio_post_adress."
		</span>	
	    </div><!-- fermeture #post_adress -->" ;
}

if ($opac_facettes_ajax && ($opac_map_activate == 1 || $opac_map_activate == 3) && ($lvl == "more_results" || strpos($lvl, "_see")) !== false) {
	$map_location_search = "
			<div id='map_location_search'>
			</div>";
} else {
	$map_location_search = "";
}

if ($lvl == "more_results" || strpos($lvl, "_see") !== false) {
	$facette = "
			<div id='facette'>
				" . $map_location_search . "
				!!lst_facette!!
			</div>";
	$lvl1 = "<div id='lvl1'>!!lst_lvl1!!</div>";
}

//segment de recherche
if ($lvl == "search_segment") {
	$facette = "
			<div id='facette'>
				" . $map_location_search . "
				!!lst_facette!!
			</div>";
	$lvl1 = "<div id='lvl1'>!!lst_lvl1!!</div>";    
}

// le footer clos le <div id=\"supportingText\"><span>, reste ouvert le <div id=\"container\">
$footer = "	
		!!div_liens_bas!! \n
		</div><!-- /div id=main -->\n
		<div id=\"intro\">\n";

$inclus_footer = "	
		</span>
		!!div_liens_bas!! \n
		</div><!-- /div id=main -->\n
		<div id=\"intro\">\n";
		
// Si $opac_biblio_important_p1 est renseign�, alors intro_message est affich�
// Ceci permet plus de libert� avec la CSS
$std_header_suite="<div id=\"intro_message\">";
if ($opac_biblio_important_p1) 	
		 $std_header_suite.="<div class=\"p1\">$opac_biblio_important_p1</div>";
// si $opac_biblio_important_p2 est renseign� alors suite d'intro_message
if ($opac_biblio_important_p2 && !$std_header_suite)
	   $std_header_suite.="<div class=\"p2\">$opac_biblio_important_p2</div>";
else $std_header_suite.="<div class=\"p2\">$opac_biblio_important_p2</div>";
// fin intro_message
$std_header_suite.="</div>";
	
$std_header.=$std_header_suite ;
$inclus_header.=$std_header_suite;

if(!isset($footer_suite)) $footer_suite = '';
$footer.= $footer_suite ;
$inclus_footer.= $footer_suite ;
eval("\$opac_biblio_preamble_p1=\"".str_replace("\"","\\\"",$opac_biblio_preamble_p1)."\";");
eval("\$opac_biblio_preamble_p2=\"".str_replace("\"","\\\"",$opac_biblio_preamble_p2)."\";");
$footer_suite ="<div id=\"intro_bibli\">
			<h3>$opac_biblio_name</h3>
			<div class=\"p1\">$opac_biblio_preamble_p1</div>
			<div class=\"p2\">$opac_biblio_preamble_p2</div>
			</div>
		<div id=\"ministeres_logos\" style=\"text-align:center;padding:12px 8px 8px;background:#fff;border-top:1px solid #e0e0e0;\">
			<img src=\"".$base_path."/images/site/logo_memp.png\" alt=\"Minist&egrave;re des Enseignements Maternel et Primaire\" title=\"Minist&egrave;re des Enseignements Maternel et Primaire\" style=\"max-height:80px;max-width:48%;margin:0 4px;vertical-align:middle;\" />
			<img src=\"".$base_path."/images/site/logo_secondaire.png\" alt=\"Minist&egrave;re de l&#039;Enseignement Secondaire\" title=\"Minist&egrave;re de l&#039;Enseignement Secondaire\" style=\"max-height:80px;max-width:48%;margin:0 4px;vertical-align:middle;\" />
		</div>
		</div><!-- /div id=intro -->";

$footer.= $footer_suite ;
$inclus_footer.= $footer_suite ;
		
$footer .="		
		!!contenu_bandeau!!";

$footer .="</div><!-- /div id=container -->
		!!cms_build_info!!
		<script type='text/javascript'>init_drag();	//rechercher!!</script> 
		".$script_analytics_html."
		</body>
		</html>
		"; //".($surligne?"rechercher(1);":"")."

$inclus_footer .="
		!!contenu_bandeau!!
		</div><!-- /div id=container -->
		!!cms_build_info!!
		<script type='text/javascript'>init_drag(); //rechercher!!</script>
				";

$inclus_footer .= $script_analytics_html;

function format_lien_rech($url) {
	global $base_path;
	global $use_opac_url_base, $opac_url_base;
	
	if($use_opac_url_base) return $opac_url_base.$url;
	else return $base_path.'/'.$url;
}

$liens_opac['lien_rech_notice'] 		= format_lien_rech("index.php?lvl=notice_display&id=!!id!!");
$liens_opac['lien_rech_auteur'] 		= format_lien_rech("index.php?lvl=author_see&id=!!id!!");
$liens_opac['lien_rech_editeur'] 		= format_lien_rech("index.php?lvl=publisher_see&id=!!id!!");
$liens_opac['lien_rech_titre_uniforme']	= format_lien_rech("index.php?lvl=titre_uniforme_see&id=!!id!!");
$liens_opac['lien_rech_serie'] 			= format_lien_rech("index.php?lvl=serie_see&id=!!id!!");
$liens_opac['lien_rech_collection'] 	= format_lien_rech("index.php?lvl=coll_see&id=!!id!!");
$liens_opac['lien_rech_subcollection'] 	= format_lien_rech("index.php?lvl=subcoll_see&id=!!id!!");
$liens_opac['lien_rech_indexint'] 		= format_lien_rech("index.php?lvl=indexint_see&id=!!id!!");
//$liens_opac['lien_rech_motcle'] 		= format_lien_rech("index.php?lvl=search_result&mode=keyword&auto_submit=1&user_query=!!mot!!");
$liens_opac['lien_rech_motcle'] 		= format_lien_rech("index.php?lvl=more_results&mode=keyword&user_query=!!mot!!&tags=ok");
$liens_opac['lien_rech_categ'] 			= format_lien_rech("index.php?lvl=categ_see&id=!!id!!");
$liens_opac['lien_rech_perio'] 			= format_lien_rech("index.php?lvl=notice_display&id=!!id!!");
$liens_opac['lien_rech_bulletin'] 		= format_lien_rech("index.php?lvl=bulletin_display&id=!!id!!");
$liens_opac['lien_rech_concept'] 		= format_lien_rech("index.php?lvl=concept_see&id=!!id!!");
$liens_opac['lien_rech_authperso'] 		= format_lien_rech("index.php?lvl=authperso_see&id=!!id!!");


switch($opac_allow_simili_search){
	case "1" :
		$simili_search_call = "show_simili_search_all();show_expl_voisin_search_all();";
		break;
	case "2" :
		$simili_search_call = "show_expl_voisin_search_all();";
		break;
	case "3" :
		$simili_search_call = "show_simili_search_all()";
		break;
	default:
		$simili_search_call = "";
		break;
}

$begin_result_liste = "<span class=\"espaceResultSearch\">&nbsp;</span>" ;
if($opac_recherche_ajax_mode){
	if($opac_recherche_show_expand){
		$begin_result_liste = "<span class=\"expandAll\"><a href='javascript:expandAll_ajax(".$opac_recherche_ajax_mode.");$simili_search_call'><img class='img_plusplus' src='".get_url_icon("expand_all.gif")."' style='border:0px' id='expandall'></a></span>".$begin_result_liste."<span class=\"collapseAll\"><a href='javascript:collapseAll()'><img class='img_moinsmoins' src='".get_url_icon("collapse_all.gif")."' style='border:0px' id='collapseall'></a></span>";
	}
}else{
	if($opac_recherche_show_expand){
		$begin_result_liste = "<span class=\"expandAll\"><a href='javascript:expandAll()'><img class='img_plusplus' src='".get_url_icon("expand_all.gif")."' style='border:0px' id='expandall'></a></span>".$begin_result_liste."<span class=\"collapseAll\"><a href='javascript:collapseAll()'><img class='img_moinsmoins' src='".get_url_icon("collapse_all.gif")."' style='border:0px' id='collapseall'></a></span>";
	}
}

define( 'AFF_ETA_NOTICES_NON', 0 );
define( 'AFF_ETA_NOTICES_ISBD', 1 );
define( 'AFF_ETA_NOTICES_PUBLIC', 2 );
define( 'AFF_ETA_NOTICES_BOTH', 4 );
define( 'AFF_ETA_NOTICES_BOTH_ISBD_FIRST', 5 );
define( 'AFF_ETA_NOTICES_REDUIT', 8 );
define( 'AFF_ETA_NOTICES_DEPLIABLES_NON', 0 );
define( 'AFF_ETA_NOTICES_DEPLIABLES_OUI', 1 );
define( 'AFF_ETA_NOTICES_TEMPLATE_DJANGO', 9 );

define( 'AFF_BAN_NOTICES_NON', 0 );
define( 'AFF_BAN_NOTICES_ISBD', 1 );
define( 'AFF_BAN_NOTICES_PUBLIC', 2 );
define( 'AFF_BAN_NOTICES_BOTH', 4 );
define( 'AFF_BAN_NOTICES_BOTH_ISBD_FIRST', 5 );
define( 'AFF_BAN_NOTICES_REDUIT', 8 );
define( 'AFF_BAN_NOTICES_DEPLIABLES_NON', 0 );
define( 'AFF_BAN_NOTICES_DEPLIABLES_OUI', 1 );
define( 'AFF_BAN_NOTICES_TEMPLATE_DJANGO', 9 );
