<?php

class dmWidgetNavigationBreadCrumbView extends dmWidgetPluginView
{

	public function getRequiredVars()
	{
    return array('separator', 'includeCurrent');
	}

	public function getViewVars(array $vars = array())
  {
    $vars = parent::getViewVars($vars);

    $currentPage = dmContext::getInstance()->getPage();

    $treeObject = dmDb::table('DmPage')->getTree();
    $treeObject->setBaseQuery(dmDb::table('DmPage')->createQuery('p')->withI18n());

    $ancestors = $currentPage->getNode()->getAncestors();

    $treeObject->resetBaseQuery();

    $vars['pages'] = $ancestors ? $ancestors : array();

    if ($vars['includeCurrent'])
    {
    	$vars['pages'][] = $currentPage;
    }
    
    $vars['nbPages'] = count($vars['pages']);

    return $vars;
  }
  
  protected function doRender(array $vars)
  {
  	$html = '<ol>';

		foreach($vars['pages'] as $position => $page)
		{
		  $html .= dmStaticHelper::£('li', dmFrontLinkTag::build($page)->render());
		
		  if ($vars['separator'] && ($position < ($vars['nbPages']-1)))
		  {
		    $html .= dmStaticHelper::£('li', $vars['separator']);
		  }
		}
		
    $html .= '</ol>';
    
    return $html;
  }
  
  public function toIndexableString(array $vars)
  {
    return '';
  }

}