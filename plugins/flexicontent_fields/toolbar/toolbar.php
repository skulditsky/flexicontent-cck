<?php
/**
 * @version 1.0 $Id: toolbar.php 1880 2014-03-28 07:10:44Z ggppdk $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @subpackage plugin.file
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('cms.plugin.plugin');
require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'helpers'.DS.'route.php');

class plgFlexicontent_fieldsToolbar extends JPlugin
{
	static $field_types = array('toolbar');
	
	// ***********
	// CONSTRUCTOR
	// ***********
	
	function __construct( &$subject, $params )
	{
		parent::__construct( $subject, $params );
		JPlugin::loadLanguage('plg_flexicontent_fields_toolbar', JPATH_ADMINISTRATOR);
	}
	
	
	
	// *******************************************
	// DISPLAY methods, item form & frontend views
	// *******************************************
	
	// Method to create field's HTML display for item form
	function onDisplayField(&$field, &$item)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
	}
	
	
	// Method to create field's HTML display for frontend views
	function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		$view = JRequest::getString('view', FLEXI_ITEMVIEW);
		
		//if ($view != FLEXI_ITEMVIEW) return;
		if (JRequest::getCmd('print')) return;
		
		global $mainframe, $addthis;
		//$scheme = JURI::getInstance()->getScheme();  // we replaced http(s):// with //
		$document	= JFactory::getDocument();
		
		$lang = $document->getLanguage();
		$lang = $item->params->get('language', $lang);
		$lang = $lang ? $lang : 'en-GB';
		$lang = substr($lang, 0, 2);
		$lang = in_array($lang, array('en','es','it','th')) ? $lang : 'en';
		
		// parameters shortcuts
		$display_comments	= $field->parameters->get('display_comments', 1) && $item->parameters->get('comments',0);
		$display_resizer	= $field->parameters->get('display_resizer', 1);
		$display_print 		= $field->parameters->get('display_print', 1);
		$display_email 		= $field->parameters->get('display_email', 1);
		$display_voice 		= $field->parameters->get('display_voice', 1);
		$display_pdf 		= 0; //$field->parameters->get('display_pdf', 1);
		$load_css 			= $field->parameters->get('load_css', 1);
		
		$display_social 	= $field->parameters->get('display_social', 1);
		$addthis_user		= $field->parameters->get('addthis_user', '');
		$addthis_pubid	= $field->parameters->get('addthis_pubid', $addthis_user);
		
		$spacer_size		= $field->parameters->get('spacer_size', 21);
		$module_position	= $field->parameters->get('module_position', '');
		$default_size 		= $field->parameters->get('default_size', 12);
		$default_line 		= $field->parameters->get('default_line', 16);
		$target 			= $field->parameters->get('target', 'flexicontent');
		$voicetarget 		= $field->parameters->get('voicetarget', 'flexicontent');

		$spacer				= ' style="width:'.$spacer_size.'px;"';
		// define a global variable to be sure the script is loaded only once
		$addthis		= isset($addthis) ? $addthis : 0;
		
		if ($load_css) {
			$document->addStyleSheet(JURI::root(true).'/plugins/flexicontent_fields/toolbar/toolbar/toolbar.css');
		}
		
		if ($display_social || $display_comments || $display_email || $display_print) {
			$item_url = FlexicontentHelperRoute::getItemRoute($item->slug, $item->categoryslug);
			$server = JURI::getInstance()->toString(array('scheme', 'host', 'port'));
			$item_link = $server . JRoute::_($item_url);
			// NOTE: this uses current SSL setting (e.g menu item), and not URL scheme: http/https 
			//$item_link = JRoute::_($item_url, true, -1);
		}
		
		$ops = array();
		$add_this = '';

		// comments button
		if ($display_comments)
		{
			$comment_link = $item_link . '#addcomments';
			
			$ops[] = '
			<div class="flexi-react toolbar-element">
				<span class="comments-bubble">'.($module_position ? '<!-- jot '.$module_position.' s -->' : '').$this->_getCommentsCount($item->id).($module_position ? '<!-- jot '.$module_position.' e -->' : '').'</span>
				<span class="comments-legend flexi-legend"><a href="'.$comment_link.'" title="'.JText::_('FLEXI_FIELD_TOOLBAR_COMMENT').'">'.JText::_('FLEXI_FIELD_TOOLBAR_COMMENT').'</a></span>
			</div>
			';
		}

		// text resizer
		if ($display_resizer)
		{
			$document->addScriptDeclaration('var textsize = '.$default_size.';
			var lineheight = '.$default_line.';
			function fsize(size,line,unit,id){
				var vfontsize = document.getElementById(id);
				if(vfontsize){
					vfontsize.style.fontSize = size + unit;
					vfontsize.style.lineHeight = line + unit;
				}
			}
			function changetextsize(up){
				if(up){
					textsize 	= parseFloat(textsize)+2;
					lineheight 	= parseFloat(lineheight)+2;
				}else{
					textsize 	= parseFloat(textsize)-2;
					lineheight 	= parseFloat(lineheight)-2;
				}
			}');
			$ops[] = '
			<div class="flexi-resizer toolbar-element">
				<a class="decrease" href="javascript:fsize(textsize,lineheight,\'px\',\''.$target.'\');" onclick="changetextsize(0);">'.JText::_("FLEXI_FIELD_TOOLBAR_DECREASE").'</a>
				<a class="increase" href="javascript:fsize(textsize,lineheight,\'px\',\''.$target.'\');" onclick="changetextsize(1);">'.JText::_("FLEXI_FIELD_TOOLBAR_INCREASE").'</a>
				<span class="flexi-legend">'.JText::_("FLEXI_FIELD_TOOLBAR_SIZE").'</span>
			</div>
			';
		}
		
		// email button
		if ($display_email)
		{
			require_once(JPATH_SITE.DS.'components'.DS.'com_mailto'.DS.'helpers'.DS.'mailto.php');
			
			$url = 'index.php?option=com_mailto&tmpl=component&link='.MailToHelper::addLink( $item_link );
			$estatus = 'width=400,height=400,menubar=yes,resizable=yes';
			$ops[] = '
			<div class="flexi-email toolbar-element">
				<span class="email-legend flexi-legend"><a rel="nofollow" href="'. JRoute::_($url) .'" onclick="window.open(this.href,\'win2\',\''.$estatus.'\'); return false;" title="'.JText::_('FLEXI_FIELD_TOOLBAR_SEND').'">'.JText::_('FLEXI_FIELD_TOOLBAR_SEND').'</a></span>
			</div>
			';
		}
		
		// print button
		if ($display_print)
		{
			$pop = JRequest::getInt('pop');
			$pstatus = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';
			$print_link = $pop ? '#' : ( $item_link .(strstr($item_link, '?') ? '&amp;'  : '?') . 'pop=1&amp;print=1&amp;tmpl=component' );
			$js_link = $pop ? 'onclick="window.print();return false;"' : 'onclick="window.open(this.href,\'win2\',\''.$pstatus.'\'); return false;"';
			$ops[] = '
			<div class="flexi-print toolbar-element">
				<span class="print-legend flexi-legend"><a rel="nofollow" href="'. $print_link .'" '.$js_link.' title="'.JText::_('FLEXI_FIELD_TOOLBAR_PRINT').'">'.JText::_('FLEXI_FIELD_TOOLBAR_PRINT').'</a></span>
			</div>
			';
		}

		// voice button
		if ($display_voice)
		{
			if ($lang=='th') {
				// Special case language case, maybe la=laos, and Bhutan languages in the future (NECTEC support these languages)
				$document->addScript(JURI::root(true).'/plugins/flexicontent_fields/toolbar/toolbar/th.js');
			} else {
				$document->addScript('//vozme.com/get_text.js');
			}
			
			$ops[] = '
			<div class="flexi-voice toolbar-element">
			'.( $lang=='th' ? '
				<span class="voice-legend flexi-legend"><a href="javascript:void(0);" onclick="openwindow(\''.$voicetarget.'\',\''.$lang.'\');"        class="mainlevel-toolbar-article-horizontal" rel="nofollow">' . JTEXT::_('FLEXI_FIELD_TOOLBAR_VOICE') . '</a></span>' : '
				<span class="voice-legend flexi-legend"><a href="javascript:void(0);"     onclick="get_id(\''.$voicetarget.'\',\''.$lang.'\',\'fm\');" class="mainlevel-toolbar-article-horizontal" rel="nofollow">' . JTEXT::_('FLEXI_FIELD_TOOLBAR_VOICE') . '</a></span>' ).'
			</div>
			';
		}

		// pdf button
		if ($display_pdf)
		{
			$pdflink 	= 'index.php?view=items&cid='.$item->categoryslug.'&id='.$item->slug.'&format=pdf';
			$ops[] = '
			<div class="flexi-pdf toolbar-element">
				<span class="pdf-legend flexi-legend"><a href="'.JRoute::_($pdflink).'" title="'.JText::_('FLEXI_FIELD_TOOLBAR_PDF').'">'.JText::_('FLEXI_FIELD_TOOLBAR_PDF').'</a></span>
			</div>
			';
		}

		// AddThis social SHARE buttons, also optionally add OPEN GRAPH TAGs
		if ($display_social)
		{
			// ***************
			// OPEN GRAPH TAGs
			// ***************
			// OPEN GRAPH: site name
			if ($field->parameters->get('add_og_site_name'))
			{
				$document->addCustomTag("<meta property=\"og:site_name\" content=\"".JFactory::getApplication()->getCfg('sitename')."\" />");
			}
			
			// OPEN GRAPH: title
			if ($field->parameters->get('add_og_title')) {
				$title = flexicontent_html::striptagsandcut($item->title);
				$document->addCustomTag("<meta property=\"og:title\" content=\"{$title}\" />");
			}
			
			// OPEN GRAPH: description
			if ($field->parameters->get('add_og_descr'))
			{
				if ( $item->metadesc ) {
					$document->addCustomTag('<meta property="og:description" content="'.$item->metadesc.'" />');
				} else {
					$text = flexicontent_html::striptagsandcut($item->text);
					$document->addCustomTag("<meta property=\"og:description\" content=\"{$text}\" />");
				}
			}
			
			// OPEN GRAPH: type
			$og_type = (int) $field->parameters->get('add_og_type');
			if ($og_type) {
				if ($og_type > 2) $og_type = 1;
				$og_type_names = array(1=>'article', 2=>'website');
				$document->addCustomTag("<meta property=\"og:type\" content=\"".$og_type_names[$og_type]."\">");
			}
			
			// OPEN GRAPH: image (extracted from item's description text)
			if ($field->parameters->get('add_og_image'))
			{
				$og_image_field     = $field->parameters->get('og_image_field');
				$og_image_fallback  = $field->parameters->get('og_image_fallback');
				$og_image_thumbsize = $field->parameters->get('og_image_thumbsize');
				if ($og_image_field)
				{
					$imageurl = FlexicontentFields::getFieldDisplay($item, $og_image_field, null, 'display_'.$og_image_thumbsize.'_src', 'module');
					if ( $imageurl ) {
						$img_field = $item->fields[$og_image_field];
						if ( (!$imageurl && $og_image_fallback==1) || ($imageurl && $og_image_fallback==2 && $img_field->using_default_value) ) {
							$imageurl = $this->_extractimageurl($item);
						}
					}
				}
				else
				{
					$imageurl = $this->_extractimageurl($item);
				}
				// Add image if fould, making sure it is converted to ABSOLUTE URL
				if ($imageurl) {
					$is_absolute = (boolean) parse_url($imageurl, PHP_URL_SCHEME); // preg_match("#^http|^https|^ftp#i", $imageurl);
					$imageurl = $is_absolute ? $imageurl : JURI::root().$imageurl;
					$document->addCustomTag("<meta property=\"og:image\" content=\"{$imageurl}\" />");
				}
			}
			
			// Add og-URL explicitely as this is required by facebook ?
			if ($item_link) {
				$document->addCustomTag("<meta property=\"og:url\" content=\"".$item_link."\" />");
			}

			
			
			// ****************************
			// AddThis social SHARE buttons
			// ****************************
			
			$addthis_outside_toolbar  = $field->parameters->get('addthis_outside_toolbar', 0);
			$addthis_custom_code       = $field->parameters->get('addthis_custom_code', false);
			$addthis_custom_predefined = $field->parameters->get('addthis_custom_predefined', false);
			
			$addthis_code = '';
			if ($addthis_custom_code) {
				$addthis_code = str_replace('_item_url_', $item_link, $addthis_custom_code);
				$addthis_code = str_replace('_item_title_', htmlspecialchars( $item->title, ENT_COMPAT, 'UTF-8' ), $addthis_code);
			}
			else {
				switch ($addthis_custom_predefined) {
					case 1:
						$addthis_code .= '
						<!-- AddThis Button BEGIN -->
						<div class="addthis_toolbox addthis_default_style addthis_counter_style" addthis:url="'.$item_link.'" addthis:title="'.htmlspecialchars( $item->title, ENT_COMPAT, 'UTF-8' ).'">
						<a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
						<a class="addthis_button_tweet"></a>
						<a class="addthis_button_pinterest_pinit"></a>
						<a class="addthis_counter addthis_pill_style"></a>
						</div>
						<!-- AddThis Button END -->
						';
						break;
					case 2:
						$addthis_code .= '
						<!-- AddThis Button BEGIN -->
						<div class="addthis_toolbox addthis_default_style addthis_32x32_style" addthis:url="'.$item_link.'" addthis:title="'.htmlspecialchars( $item->title, ENT_COMPAT, 'UTF-8' ).'">
						<a class="addthis_button_preferred_1"></a>
						<a class="addthis_button_preferred_2"></a>
						<a class="addthis_button_preferred_3"></a>
						<a class="addthis_button_preferred_4"></a>
						<a class="addthis_button_compact"></a>
						<a class="addthis_counter addthis_bubble_style"></a>
						</div>
						<!-- AddThis Button END -->
						';
						break;
					default:
					case 3:
						$addthis_code .= '
						<!-- AddThis Button BEGIN -->
						<div class="addthis_toolbox addthis_default_style addthis_16x16_style" addthis:url="'.$item_link.'" addthis:title="'.htmlspecialchars( $item->title, ENT_COMPAT, 'UTF-8' ).'">
						<a class="addthis_button_preferred_1"></a>
						<a class="addthis_button_preferred_2"></a>
						<a class="addthis_button_preferred_3"></a>
						<a class="addthis_button_preferred_4"></a>
						<a class="addthis_button_compact"></a>
						<a class="addthis_counter addthis_bubble_style"></a>
						</div>
						<!-- AddThis Button END -->
						';
						break;
					case 4:
						$addthis_code .= '
						<!-- AddThis Button BEGIN -->
						<a class="addthis_button" href="//www.addthis.com/bookmark.php?v=300&pubid='.$addthis_pubid.'"><img src="//s7.addthis.com/static/btn/v2/lg-share-en.gif" width="125" height="16" alt="'.JText::_('FLEXI_FIELD_TOOLBAR_SHARE').'" style="border:0"/></a>
						<!-- AddThis Button END -->
						';
						break;
					case 5:
						$addthis_code .= '
						<!-- AddThis Button BEGIN -->
						<div class="addthis_toolbox addthis_floating_style addthis_counter_style" style="left:50px;top:50px;" addthis:url="'.$item_link.'" addthis:title="'.htmlspecialchars( $item->title, ENT_COMPAT, 'UTF-8' ).'">
						<a class="addthis_button_facebook_like" fb:like:layout="box_count"></a>
						<a class="addthis_button_tweet" tw:count="vertical"></a>
						<a class="addthis_button_google_plusone" g:plusone:size="tall"></a>
						<a class="addthis_counter"></a>
						</div>
						<!-- AddThis Button END -->
						';
						break;
					case 6:
						$addthis_code .= '
						<!-- AddThis Button BEGIN -->
						<div class="addthis_toolbox addthis_floating_style addthis_32x32_style" style="left:50px;top:50px;" addthis:url="'.$item_link.'" addthis:title="'.htmlspecialchars( $item->title, ENT_COMPAT, 'UTF-8' ).'">
						<a class="addthis_button_preferred_1"></a>
						<a class="addthis_button_preferred_2"></a>
						<a class="addthis_button_preferred_3"></a>
						<a class="addthis_button_preferred_4"></a>
						<a class="addthis_button_compact"></a>
						</div>
						<!-- AddThis Button END -->
						';
						break;
					case 7:
						$addthis_code .= '
						<!-- AddThis Button BEGIN -->
						<div class="addthis_toolbox addthis_floating_style addthis_16x16_style" style="left:50px;top:50px;" addthis:url="'.$item_link.'" addthis:title="'.htmlspecialchars( $item->title, ENT_COMPAT, 'UTF-8' ).'">
						<a class="addthis_button_preferred_1"></a>
						<a class="addthis_button_preferred_2"></a>
						<a class="addthis_button_preferred_3"></a>
						<a class="addthis_button_preferred_4"></a>
						<a class="addthis_button_compact"></a>
						</div>
						<!-- AddThis Button END -->
						';
						break;
				}
			}
			if ($addthis_outside_toolbar)
				$add_this = '<div class="flexi-socials-outside">'.$addthis_code.'</div>';
			else 
				$add_this = '<div class="flexi-socials toolbar-element">' .$addthis_code. '</div>';
			
			
			if (!$addthis) {
				$document->addCustomTag('	
					<script type="text/javascript">
					var addthis_config = {
						services_exclude: "print,email"
					}
					</script>
					<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js'.($addthis_pubid ? '#pubid='.$addthis_pubid : '').'"></script>
				');
				$addthis = 1;
			}
		}
		
		$display = '
		<div class="flexitoolbar">
			'.implode('<div class="toolbar-spacer"'.$spacer.'></div>', $ops).'
			'.$add_this.'
		</div>';

		$field->{$prop} = $display;
	}
	
	
	
	// **************************************************************
	// METHODS HANDLING before & after saving / deleting field events
	// **************************************************************
	
	// Method to handle field's values before they are saved into the DB
	function onBeforeSaveField( &$field, &$post, &$file, &$item )
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
	}
	
	
	// Method to take any actions/cleanups needed after field's values are saved into the DB
	function onAfterSaveField( &$field, &$post, &$file, &$item ) {
	}
	
	
	// Method called just before the item is deleted to remove custom item data related to the field
	function onBeforeDeleteField(&$field, &$item) {
	}
	
	
	
	// **********************
	// VARIOUS HELPER METHODS
	// **********************
	
	function _getCommentsCount($id)
	{
		$db = JFactory::getDBO();
		static $jcomment_installed = null;
		
		if ($jcomment_installed===null) {
			$app = JFactory::getApplication();
			$dbprefix = $app->getCfg('dbprefix');
			$db->setQuery('SHOW TABLES LIKE "'.$dbprefix.'jcomments"');
			$jcomment_installed = (boolean) count($db->loadObjectList());
		}
		if (!$jcomment_installed) return 0;
		
		$query 	= 'SELECT COUNT(com.object_id)'
				. ' FROM #__jcomments AS com'
				. ' WHERE com.object_id = ' . (int)$id
				. ' AND com.object_group = ' . $db->Quote('com_flexicontent')
				. ' AND com.published = 1'
				;
		$db->setQuery($query);
				
		return $db->loadResult() ? (int)$db->loadResult() : 0;
	}
	
	
	function _extractimageurl(& $item)
	{
		$matches = NULL;
		preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $item->text, $matches);
		$imageurl = @$matches[1][0];
		if($imageurl) {
			if($imageurl{0} == '/') {
				$imageurls = explode('/', $imageurl);
				$paths = array();
				$found = false;
				foreach($imageurls as $folder) {
					if(!$found) {
						if($folder!='images') continue;
						else {
							$found = true;
						}
					}
					$paths[] = $folder;
				}
				$imageurl = '/'.implode('/', $paths);
				$imageurl = JURI::root(true).$imageurl;
			}elseif(substr($imageurl, 0, 7)=='images/') {
				$imageurl = JURI::root(true).'/'.$imageurl;
			}
		}
		return $imageurl;
	}

}
