<?php
/**
 * @version 1.5 stable $Id: view.html.php 1940 2014-08-29 17:55:03Z ggppdk $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 * 
 * FLEXIcontent is a derivative work of the excellent QuickFAQ component
 * @copyright (C) 2008 Christoph Lukes
 * see www.schlu.net for more information
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('legacy.view.legacy');

/**
 * HTML View class for the Tags View
 *
 * @package Joomla
 * @subpackage FLEXIcontent
 * @since 1.0
 */
class FlexicontentViewTags extends JViewLegacy
{
	/**
	 * Creates the page's display
	 *
	 * @since 1.0
	 */
	function display( $tpl = null )
	{
		//initialize variables
		$app      = JFactory::getApplication();
		$document = JFactory::getDocument();
		$menus = $app->getMenu();
		$menu  = $menus->getActive();
		$uri   = JFactory::getURI();
		$view  = JRequest::getCmd('view');
		
		// Get view's Model
		$model  = $this->getModel();
		
		// Get tag and set tag parameters as VIEW's parameters (tag parameters are merged with component/page(=menu item) and optionally with tag cloud parameters)
		$tag = $model->getTag();
		if ( empty($tag) ) {
			// Raise a 404 error, if tag doesn't exist or access isn't permitted, maybe move this into model ??
			$tid = JRequest::getInt('id', 0);
			$msg = JText::sprintf( $tid ? 'Tag id was not set (is 0)' : 'Tag #%d not found', $tid );
			if (FLEXI_J16GE) throw new Exception($msg, 404); else JError::raiseError(404, $msg);
		}
		
		// Get parameters via model
		$params  = $model->getParams();
		
		// Get various data from the model
		$items   = $this->get('Data');
		$total   = $this->get('Total');
		
		// Make sure field values were retrieved e.g. we need 'item->categories' for template classes
		$items 	= FlexicontentFields::getFields($items, $view, $params);
		
		// Calculate CSS classes needed to add special styling markups to the items
		flexicontent_html::calculateItemMarkups($items, $params);
		
		
		// ********************************
		// Load needed JS libs & CSS styles
		// ********************************
		
		FLEXI_J30GE ? JHtml::_('behavior.framework', true) : JHTML::_('behavior.mootools');
		flexicontent_html::loadFramework('jQuery');
		flexicontent_html::loadFramework('flexi_tmpl_common');
		
		// Add css files to the document <head> section (also load CSS joomla template override)
		if (!$params->get('disablecss', '')) {
			$document->addStyleSheetVersion($this->baseurl.'/components/com_flexicontent/assets/css/flexicontent.css', FLEXI_VHASH);
			//$document->addCustomTag('<!--[if IE]><style type="text/css">.floattext {zoom:1;}</style><![endif]-->');
		}
		if (file_exists(JPATH_SITE.DS.'templates'.DS.$app->getTemplate().DS.'css'.DS.'flexicontent.css')) {
			$document->addStyleSheetVersion($this->baseurl.'/templates/'.$app->getTemplate().'/css/flexicontent.css', FLEXI_VHASH);
		}
		
		
		// **********************************************************
		// Calculate a (browser window) page title and a page heading
		// **********************************************************
		
		// Verify menu item points to current FLEXIcontent object
		if ( $menu ) {
			$view_ok     = 'tags'   == @$menu->query['view'];
			$tid_ok      = $tag->id == (int) @$menu->query['id'];
			$menu_matches = $view_ok && $tid_ok;
			//$menu_params = FLEXI_J16GE ? $menu->params : new JParameter($menu->params);  // Get active menu item parameters
		} else {
			$menu_matches = false;
		}
		
		// MENU ITEM matched, use its page heading (but use menu title if the former is not set)
		if ( $menu_matches ) {
			$default_heading = FLEXI_J16GE ? $menu->title : $menu->name;
			
			// Cross set (show_) page_heading / page_title for compatibility of J2.5+ with J1.5 template (and for J1.5 with J2.5 template)
			$params->def('page_heading', $params->get('page_title',   $default_heading));
			$params->def('page_title',   $params->get('page_heading', $default_heading));
		  $params->def('show_page_heading', $params->get('show_page_title',   0));
		  $params->def('show_page_title',   $params->get('show_page_heading', 0));
		}
		
		// MENU ITEM did not match, clear page title (=browser window title) and page heading so that they are calculated below
		else {
			// Clear some menu parameters
			//$params->set('pageclass_sfx',	'');  // CSS class SUFFIX is behavior, so do not clear it ?
			
			// Calculate default page heading (=called page title in J1.5), which in turn will be document title below !! ...
			// meta_params->get('page_title') is meant for <title> but let's use as ... default page heading
			$default_heading = JText::_('FLEXI_ITEMS_WITH_TAG').": ". $tag->name;
			
			// Decide to show page heading (=J1.5 page title), this is always yes
			$show_default_heading = 1;
			
			// Set both (show_) page_heading / page_title for compatibility of J2.5+ with J1.5 template (and for J1.5 with J2.5 template)
			$params->set('page_title',   $default_heading);
			$params->set('page_heading', $default_heading);
		  $params->set('show_page_heading', $show_default_heading);
			$params->set('show_page_title',   $show_default_heading);
		}
		
		// Prevent showing the page heading if ... currently no reason
		if ( 0 ) {
			$params->set('show_page_heading', 0);
			$params->set('show_page_title',   0);
		}
		
		
		
		// ************************************************************
		// Create the document title, by from page title and other data
		// ************************************************************
		
		// Use the page heading as document title, (already calculated above via 'appropriate' logic ...)
		$doc_title = $params->get( 'page_title' );
		
		// Check and prepend or append site name to page title
		if ( $doc_title != $app->getCfg('sitename') ) {
			if ($app->getCfg('sitename_pagetitles', 0) == 1) {
				$doc_title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $doc_title);
			}
			elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
				$doc_title = JText::sprintf('JPAGETITLE', $doc_title, $app->getCfg('sitename'));
			}
		}
		
		// Finally, set document title
		$document->setTitle($doc_title);
		
		
		// ************************
		// Set document's META tags
		// ************************
		
		// Workaround for Joomla not setting the default value for 'robots', so component must do it
		$app_params = $app->getParams();
		if (($_mp=$app_params->get('robots')))    $document->setMetadata('robots', $_mp);
		
		// Overwrite with menu META data if menu matched
		if ($menu_matches) {
			if (($_mp=$menu->params->get('menu-meta_description')))  $document->setDescription( $_mp );
			if (($_mp=$menu->params->get('menu-meta_keywords')))     $document->setMetadata('keywords', $_mp);
			if (($_mp=$menu->params->get('robots')))                 $document->setMetadata('robots', $_mp);
			if (($_mp=$menu->params->get('secure')))                 $document->setMetadata('secure', $_mp);
		}
		
		
		// ****************************************************************
		// Make sure Joomla SEF plugin has inserted a correct REL canonical
		// or that it has not insert any REL if current URL is sufficient
		// ****************************************************************
		
		if ($params->get('add_canonical'))
		{
			// Create desired REL canonical URL
			$start = JRequest::getInt('start', '');
			$ucanonical = JRoute::_(FlexicontentHelperRoute::getTagRoute($tag->id).($start ? "&start=".$start : ''));
			flexicontent_html::setRelCanonical($ucanonical);
		}
		
		// Disable features, that are not supported by the view
		$params->set('use_filters',0);
		$params->set('show_alpha',0);
		$params->set('clayout_switcher',0);
		
		//ordering
		$filter_order		= JRequest::getCmd('filter_order', 'i.title');
		$filter_order_Dir	= JRequest::getCmd('filter_order_Dir', 'ASC');
		$filter				= JRequest::getString('filter');
		
		$lists						= array();
		$lists['filter_order']		= $filter_order;
		$lists['filter_order_Dir'] 	= $filter_order_Dir;
		$lists['filter']			= $filter;
		
		// Create the pagination object
		$pageNav = $this->get('pagination');
		// URL-encode filter values
		foreach($_GET as $i => $v) {
			if (substr($i, 0, 6) === "filter") {
				$_revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
				$v = str_replace('&', '__amp__', $v);
				$v = strtr(rawurlencode($v), $_revert);
				$pageNav->setAdditionalUrlParam($i, $v);
			}
		}
		
		// Create links, etc
		$link = JRoute::_(FlexicontentHelperRoute::getTagRoute($tag->slug), false);
		
		//$print_link = JRoute::_('index.php?view=tags&id='.$tag->slug.'&pop=1&tmpl=component');
    $curr_url   = str_replace('&', '&amp;', $_SERVER['REQUEST_URI']);
    $print_link = $curr_url .(strstr($curr_url, '?') ? '&amp;'  : '?').'pop=1&amp;tmpl=component&amp;print=1';
		
		$pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));
		
		$this->assignRef('action',    $link);  // $uri->toString()
		$this->assignRef('print_link',$print_link);
		$this->assignRef('tag',       $tag);
		$this->assignRef('items',     $items);
		$this->assignRef('lists',     $lists);
		$this->assignRef('params',    $params);
		$this->assignRef('pageNav',   $pageNav);
		$this->assignRef('pageclass_sfx', $pageclass_sfx);
		
		$print_logging_info = $params->get('print_logging_info');
		if ( $print_logging_info ) { global $fc_run_times; $start_microtime = microtime(true); }
		
		parent::display($tpl);
		
		if ( $print_logging_info ) @$fc_run_times['template_render'] += round(1000000 * 10 * (microtime(true) - $start_microtime)) / 10;
	}
}
?>