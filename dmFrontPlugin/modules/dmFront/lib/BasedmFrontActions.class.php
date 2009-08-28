<?php

class BasedmFrontActions extends dmFrontBaseActions
{
	
	public function executeToAdmin(dmWebRequest $request)
	{
		return $this->redirect(
		  dmFrontLinkTag::build('app:admin')->getHref()
		);
	}

	public function executePage(dmWebRequest $request)
	{
    $this->forward404Unless($this->site = $this->getSite(), 'No current site');

		$this->forward404Unless($this->page = $this->getPage(), 'No current page');
		
		if ($this->page->isModuleAction('main', 'error404'))
		{
      $this->context->getResponse()->setStatusCode(404);
		}
		
    $this->setLayout(dmOs::join(sfConfig::get("dm_front_dir"), "modules/dmFront/templates/layout"));

    $this->helper = $this->getDmContext()->getPageHelper();
    
    $this->launchDirectActions($request);
	}

	/*
	 * If an sfAction exists for the current page module.action,
	 * it will be executed
	 * If some sfActions exist for the current widgets module.action,
	 * they will be executed to
	 */
	protected function launchDirectActions($request)
	{
		$timerLaunchAction = dmDebug::timer("dmFrontActions::launchDirectActions");
		
    /*
     * Find module/action for page widget ( including layout )
     */
    $moduleActions = array();
    foreach($this->helper->getAreas() as $areaArray)
    {
    	foreach($areaArray['Zones'] as $zoneArray)
    	{
    		foreach($zoneArray['Widgets'] as $widgetArray)
    		{
        	$widgetModuleAction = $widgetArray['module'].'/'.$widgetArray['action'].'Widget';
        	
        	if(!isset($moduleActions[$widgetModuleAction]))
        	{
            $moduleActions[] = $widgetModuleAction;
        	}
    		}
    	}
    }
    
    // Add module action for page
    $moduleActions[] = $this->page->module.'/'.$this->page->action.'Page';
    
    $controller = $this->context->getController();
    foreach($moduleActions as $moduleAction)
    {
      list($module, $action) = explode("/", $moduleAction);
      
      if ($controller->actionExists($module, $action))
      {
        $actionToRun = 'execute'.ucfirst($action);
        $controller->getAction($module, $action)->$actionToRun($request);
      }
    }
    
    $timerLaunchAction->addTime();
	}

	/*
	 * When site is not approved,
	 * non-authentified users
	 * will be forwarded
	 * to this action
	 */
	public function executeWait(sfWebRequest $request)
	{

	}

	public function executeEditToggle(sfWebRequest $request)
	{
		$this->getUser()->setIsEditMode($request->getParameter('active'));
		return $this->renderText('ok');
	}
	
	public function executeShowToolBarToggle(sfWebRequest $request)
	{
    $this->getUser()->setShowToolBar($request->getParameter('active'));
    return $this->renderText('ok');
	}

}