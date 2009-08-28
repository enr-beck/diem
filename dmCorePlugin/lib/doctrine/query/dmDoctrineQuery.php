<?php

abstract class dmDoctrineQuery extends Doctrine_Query
{

	protected static
	$cacheDrivers;

  /**
   * Constructor.
   *
   * @param Doctrine_Connection  The connection object the query will use.
   * @param Doctrine_Hydrator_Abstract  The hydrator that will be used for generating result sets.
   */
  public function __construct(Doctrine_Connection $connection = null, Doctrine_Hydrator_Abstract $hydrator = null)
  {
    parent::__construct($connection, $hydrator);

    if (sfConfig::get('dm_orm_cache_result_enabled_default'))
    {
      $this->dmCache();
    }
  }

  /**
   * useResultCache if available
   *
   * @param Doctrine_Cache_Interface|bool $driver      cache driver
   * @param integer $timeToLive                        how long the cache entry is valid
   * @return Doctrine_Hydrate                          this object
   */
	public function dmCache($driver = true, $timeToLive = null, $force = false)
	{
		if ($driver !== true)
		{
			if (!$driver)
			{
				$driver = null;
			}
			elseif(is_string($driver))
			{
	      $driver = self::getCacheDriver($driver);
			}
		}

    if ($force || (sfConfig::get('dm_orm_cache_result_enabled') && sfConfig::get('dm_orm_cache_result_activated')))
    {
    	$this->useResultCache($driver, $timeToLive);
    }

    return $this;
	}

	/*
	 * use cache even if disabled
	 * @see dmCache
	 */
	public function dmCacheForce($driver = true, $timeToLive = null)
	{
    return $this->dmCache($driver, $timeToLive, true);
	}

	public static function getCacheDriver($name)
	{
    $driverClass = 'Doctrine_Cache_'.ucfirst($name);

    if (!isset(self::$cacheDrivers[$driverClass]))
    {
      self::$cacheDrivers[$driverClass] = new $driverClass;
    }

    return self::$cacheDrivers[$driverClass];
	}

	/*
	 * Join translation results if they exist
	 * if $model is specified, will verify that it has I18n
	 * return @myDoctrineQuery $this
	 */
	public function withI18n($culture = null, $model = null)
	{
    if (!is_null($model))
    {
      if (!dmDb::table($model)->hasI18n())
      {
        return $this;
      }
    }

		$me       = $this->getRootAlias();
		$culture  = is_null($culture) ? dm::getUser()->getCulture() : $culture;

    return $this
    ->addSelect($me.'.*, translation.*')
    ->leftJoin($me.'.Translation translation ON '.$me.'.id = translation.id AND translation.lang = ?', $culture);
	}


	public function whereIsApproved($boolean = true, $model = null)
	{
    if (!is_null($model))
    {
      if (!dmDb::table($model)->hasField('is_approved'))
      {
        return $this;
      }
    }
		return $this->addWhere($this->getRootAlias().'.is_approved = ?', (bool) $boolean);
	}
	
  /*
   * Will restrict results to $model records
   * associated with $ancestor record
   */
  public function whereAncestor(myDoctrineRecord $ancestorRecord, $model)
  {
    return $this->whereAncestorId(get_class($ancestorRecord), $ancestorRecord->id, $model);
  }

  /*
   * Will restrict results to $model records
   * associated with $ancestorModel->$ancestorId record
   */
  #TODO optimize speed by not fetching $ancestorRecord
  public function whereAncestorId($ancestorRecordModel, $ancestorRecordId, $model)
  {
    if(!$module = dmModuleManager::getModule($model))
    {
      throw new dmException(sprintf('No module %s', $model));
    }
  
    if ($module->hasAssociation($ancestorModule = dmModuleManager::getModuleByModel($ancestorRecordModel)))
    {
      $this->leftJoin(sprintf('%s.%s %s',
        $this->getRootAlias(),
        $module->getTable()->getRelationHolder()->getByClass($ancestorRecordModel)->getAlias(),
        $ancestorModule->getKey()
      ));
    }
    elseif($ancestorModule = $module->getAncestor($ancestorRecordModel))
    {
	    $current      = $module;
	    $currentAlias = $this->getRootAlias();
	    
	    foreach(array_reverse($module->getPath(), true) as $ancestorKey => $ancestor)
	    {
	      if (!$relation = $current->getTable()->getRelationHolder()->getByClass($ancestor->getModel()))
	      {
	        throw new dmRecordException(sprintf('%s has no relation for class %s', $current, $ancestor->getModel()));
	        return null;
	      }
	
	      $this->leftJoin($currentAlias.'.'.$relation['alias'].' '.$ancestorKey);
	
	      if ($ancestor->is($ancestorModule))
	      {
	        break;
	      }
	
	      $current       = $ancestor;
	      $currentAlias  = $ancestor->getKey();
	    }
    }
    else
    {
      throw new dmRecordException(sprintf('%s is not an ancestor of %s, nor associated', $ancestorRecordModel, $module));
      return null;
    }
    
    $this->addWhere($ancestorModule->getKey().'.id = ?', $ancestorRecordId);

    return $this;
  }
  
  /*
   * Will restrict results to $model records
   * associated with $descendant record
   */
  public function whereDescendant(myDoctrineRecord $descendantRecord, $model)
  {
    return $this->whereDescendantId(get_class($descendantRecord), $descendantRecord->id, $model);
  }
  
  /*
   * Will restrict results to $model records
   * associated with $descendantModel->$descendantId record
   */
  public function whereDescendantId($descendantRecordModel, $descendantRecordId, $model)
  {
    if(!$module = dmModuleManager::getModule($model))
    {
      throw new dmException(sprintf('No module %s', $model));
    }
    
    if($descendantRecordModel == $model)
    {
    	return $this->addWhere($this->getRootAlias().'.id = ?', $descendantRecordId);
    }

    if(!$descendantModule = $module->getDescendant($descendantRecordModel))
    {
      throw new dmRecordException(sprintf('%s is not an descendant of %s', $descendantRecordModel, $module));
      return null;
    }
    
    $parent       = $module;
    $parentAlias  = $this->getRootAlias();
    
    foreach($descendantModule->getPathFrom($module, true) as $descendantKey => $descendant)
    {
      if ($descendantKey != $module->getKey())
      {
        if (!$relation = $parent->getTable()->getRelationHolder()->getByClass($descendant->getModel()))
        {
          throw new dmRecordException(sprintf('%s has no relation for class %s', $parent, $descendant->getModel()));
          return null;
        }
        
        $this->leftJoin($parentAlias.'.'.$relation['alias'].' '.$descendantKey);
      
        if ($descendant->is($module))
        {
          break;
        }

        $parent        = $descendant;
        $parentAlias   = $descendantKey;
      }
    }

    $this->addWhere($descendantModule->getKey().'.id = ?', $descendantRecordId);

    return $this;
  }

	/*
	 * Add asc order by position field
   * if $model is specified, will verify that it has I18n
   * @return myDoctrineQuery $this
	 */
  public function orderByPosition($model = null)
  {
    if (!is_null($model))
    {
    	if (!dmDb::table($model)->hasField('position'))
    	{
        return $this;
    	}
    }

    $me = $this->getRootAlias();

    return $this
    ->addOrderBy("$me.position asc");
  }

	/*
	 * @return myDoctrineCollection|null the fetched collection
	 */
	public function fetchRecords($params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
  {
    return $this->execute($params, $hydrationMode);
  }

  /*
   * Add limit(1) to the query,
   * then execute $this->fetchOne()
   * @return myDoctrineRecord|null the fetched record
   */
  public function fetchRecord($params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
  {
  	return $this->limit(1)->fetchOne($params, $hydrationMode);
  }

  public function fetchValue($params = array())
  {
    return $this->execute($params, Doctrine::HYDRATE_SINGLE_SCALAR);
  }

  public function fetchValues($params = array())
  {
    return $this->execute($params, Doctrine::HYDRATE_SCALAR);
  }

  /*
   * fetch brutal PDO array with numeric keys
   * @return array PDO result
   */
  public function fetchPDO($params = array())
  {
    return $this->execute($params, DOCTRINE::HYDRATE_NONE);
  }

  /*
   * fetch brutal flat PDO array with numeric keys
   * @return array PDO result
   */
  public function fetchFlat($params = array())
  {
    return $this->execute($params, 'dmFlat');
  }

	public function exists()
	{
		return $this->count() > 0;
	}

	public function toDebug()
	{
		return $this->getSqlQuery();
	}
	
}