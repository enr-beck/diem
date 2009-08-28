<?php
/**
 */
class PluginDmPageTable extends myDoctrineTable
{
	protected
	$recordPageCache = array();
	
	/*
	 * Check that basic pages exist
	 * ( root page, 404 page )
	 * and, if they don't, will create them
	 */
  public function checkBasicPages()
  {
    if (!$root = $this->getTree()->fetchRoot())
    {
      $root = $this->create(array(
        'name' => dm::getI18n()->__('Home'),
        'title' => dm::getI18n()->__('Home').' | '.dmDb::table('DmSite')->getInstance()->getName(),
        'module' => 'main',
        'action' => 'root',
        'slug' => ''
      ));

      $this->getTree()->createRoot($root);
    }

    if (!$this->createQuery('p')->where('p.module = ? AND p.action = ?', array('main', 'error404'))->exists())
    {
      dmDb::create('DmPage', array(
        'name' => dm::getI18n()->__('Page not found'),
        'title' => dm::getI18n()->__('Page not found').' | '.dmContext::getInstance()->getSite()->name,
        'module' => 'main',
        'action' => 'error404',
        'slug' => '-error404'
      ))->getNode()->insertAsLastChildOf($root);
    }
  }
  
  /*
   * Check that search page exist
   * and, if doesn't, will create them
   */
  public function checkSearchPage()
  {
    if (!$this->createQuery('p')->where('p.module = ? AND p.action = ?', array('main', 'search'))->exists())
    {
      dmDb::create('DmPage', array(
        'name' => dm::getI18n()->__('Search results'),
        'title' => dm::getI18n()->__('Search results').' | '.dmContext::getInstance()->getSite()->name,
        'module' => 'main',
        'action' => 'search',
        'slug' => '-search'
      ))->getNode()->insertAsLastChildOf($this->getTree()->fetchRoot());
    }
  }

  public function prepareRecordPageCache($module)
  {
  	$timer = dmDebug::timer('DmPageTable::prepareRecordPageCache');
  	
  	$module = dmString::modulize($module);
  	
    $this->recordPageCache[$module] = $this->createQuery('p INDEXBY p.record_id')
    ->withI18n()
    ->select('p.id, p.module, p.action, p.record_id, p.is_secure, p.lft, p.rgt, translation.slug, translation.name, translation.is_approved')
    ->where('module = ? AND p.record_id != 0', $module)
    ->fetchRecords();
    
    $timer->addTime();
  }
  
	/*
	 * Queries
	 */

	public function queryByModuleAndAction($module, $action)
	{
		return $this->createQuery('p')
    ->where('p.module = ? AND p.action = ?', array($module, $action));
	}

  
  public function findAllForCulture($culture, $hydrationMode = Doctrine::HYDRATE_ARRAY)
  {
  	return $this->createQuery()
  	->withI18n($culture)
  	->execute(array(), $hydrationMode);
  }
  
  /*
   * Performance finder shortcuts
   */

	public function findOneBySlug($slug)
	{
		return $this->createQuery('p')
		->withI18n()
		->where('translation.slug = ?', $slug)
		->fetchRecord();
	}

  public function findByAction($action)
  {
    return $this->createQuery('p')->where('p.action = ?', $action)->fetchRecords();
  }

  public function findByModule($module)
  {
    return $this->createQuery('p')->where('p.module = ?', $module)->fetchRecords();
  }

  public function findOneByRecord(myDoctrineRecord $record)
  {
    return $this->createQuery('p')
    ->where('p.module = ? AND p.action = ? AND record_id = ?', array(
      $record->dmModule->getKey(), 'show', $record->id
    ))
    ->dmCache()
    ->fetchRecord();
  }
  
  
  public function findOneByIdWithI18n($id, $culture = null)
  {
  	return $this->createQuery('p')
  	->withI18n($culture)
  	->where('p.id = ?', $id)
  	->fetchOne();
  }
  
  public function findOneByRecordWithI18n(myDoctrineRecord $record)
  {
  	$module = $record->dmModule->getKey();
  	
  	if (!isset($this->recordPageCache[$module]))
  	{
  		$this->prepareRecordPageCache($module);
  	}
    
    if (isset($this->recordPageCache[$module][$record->id]))
  	{
  		return $this->recordPageCache[$module][$record->id];
  	}
  	
    return $this->createQuery('p')
    ->where('p.module = ? AND p.action = ? AND record_id = ?', array(
      $module, 'show', $record->id
    ))
    ->dmCache()
    ->fetchRecord();
  }

  public function findByModuleAndAction($module, $action)
  {
    return $this->createQuery('p')
    ->where('p.module = ? AND p.action = ?', array($module, $action))
    ->dmCache()
    ->fetchRecords();
  }

  public function findOneByModuleAndAction($module, $action)
  {
    return $this->createQuery('p')
    ->where('p.module = ? AND p.action = ?', array($module, $action))
    ->dmCache()
    ->fetchRecord();
  }

}