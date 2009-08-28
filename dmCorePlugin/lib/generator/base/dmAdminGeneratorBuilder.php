<?php

/*
 * Create a generator.yml for an admin module
 */
class dmAdminGeneratorBuilder
{
	protected static
  $listMaxPerPage             = 8,
  $listExcludedFields    = array('position', 'lang', 'version'),
  $formExcludedFields    = array('position', 'lang', 'version', 'created_at', 'updated_at'),
  $filterExcludedFields  = array('position', 'lang', 'version');

	protected
	$module,
	$table;

	public function __construct(dmModule $module)
	{
    $this->module = $module;
    $this->table = $module->getTable();
	}

	public function getTransformed($generator)
	{
		$yaml = sfYaml::load($generator);

		$yaml['generator']['param']['config'] = $this->getConfig();

		$yaml['generator']['param']['sortable'] = $this->table->isSortable();

		$dumper = new dmYamlDumper();
		$transformed = $dumper->dump($yaml, 6, 0);

		$transformed = preg_replace("|('~')|um", "~", $transformed);

		return $transformed;
	}

	protected function getConfig()
	{
    return array(
      'actions' => $this->getActions(),
      'fields'  => $this->getFields(),
      'list'    => $this->getList(),
      'filter'  => $this->getFilter(),
      'form'    => $this->getForm(),
      'edit'    => $this->getEdit(),
      'new'     => $this->getNew()
    );
	}

  protected function getActions()
  {
    return '~';
  }

  protected function getFields()
  {
    $fields = array();

    /*
     * Assign associated module name to association label
     */
    foreach($this->table->getRelationHolder()->getAssociations() as $alias => $relation)
    {
      $fields[dmString::underscore($alias).'_list'] = array(
        'label' => dmModuleManager::getModuleByModel($relation->getClass())->getPlural()
      );
    }
    
    /*
     * Remove is_ prefix from boolean fields labels
     */
    foreach($this->getBooleanFields() as $booleanField)
    {
    	if (strpos($booleanField, 'is_') === 0)
    	{
	      $fields[dmString::underscore($booleanField)] = array(
	        'label' => dmString::humanize(preg_replace('|^is_(.+)$|', '$1', $booleanField))
	      );
    	}
    }

    return $fields;
  }

  protected function getList()
  {
    return array(
      'display' => $this->getListDisplay(),
      'sort'    => $this->getListSort(),
      'max_per_page' => self::$listMaxPerPage,
      'table_method' => 'joinAll',
      'table_count_method' => '~'
    );
  }

  protected function getListDisplay()
  {
    $display = array(
      '='.$this->table->getIdentifierColumnName()
    );

    $fields = dmArray::valueToKey(array_diff($this->table->getColumnNames(), array_unique(array_merge(
      // always exclude these fields
      self::$listExcludedFields,
      // already included
      array($this->table->getIdentifierColumnName()),
      // exlude primary keys
      $this->table->getPrimaryKeys()
    ))));

//    foreach($this->module->getDmMediaFields() as $mediaField)
//    {
//      $display[] = $mediaField.'_view_little';
//      unset($fields[$mediaFieldName]);
//    }

    foreach($this->table->getRelationHolder()->getLocals() as $alias => $relation)
    {
      $display[] = $relation->getLocalColumnName();
      unset($fields[$relation->getLocalColumnName()]);
    }

    foreach($this->table->getRelationHolder()->getForeigns() as $alias => $relation)
    {
    	if (dmModuleManager::getModuleOrNull($relation->getClass()))
    	{
        $display[] = dmString::underscore($alias).'_list';
    	}
    }

    foreach($this->table->getRelationHolder()->getAssociations() as $alias => $relation)
    {
      $display[] = dmString::underscore($alias).'_list';
    }

    foreach($this->table->getColumnNames() as $field)
    {
      if (in_array($field, $fields))
      {
        $display[] = $field;
        unset($fields[$field]);
      }
    }

    return $display;
  }

  protected function getListSort()
  {
    if ($this->table->hasColumn('position'))
    {
    	$sort = array('position', 'asc');
    }
    elseif($this->table->hasColumn('created_at'))
    {
    	$sort = array('created_at', 'desc');
    }
    else
    {
      $sort = array($this->table->getIdentifierColumnName(), 'asc');
    }
    return $sort;
  }

  protected function getFilter()
  {
    return array(
      'display' => $this->getFilterDisplay()
    );
  }

  protected function getFilterDisplay()
  {
    $display = array(
      $this->table->getIdentifierColumnName()
    );

    $fields = dmArray::valueToKey(array_diff($this->table->getColumnNames(), array_unique(array_merge(
      // always exclude these fields
      self::$filterExcludedFields,
      // already included
      array($this->table->getIdentifierColumnName()),
      // exlude primary keys
      $this->table->getPrimaryKeys()
    ))));

    foreach($this->getBooleanFields() as $field)
    {
      if (in_array($field, $fields))
      {
        $display[] = $field;
        unset($fields[$field]);
      }
    }

    foreach($fields as $field)
    {
      $display[] = $field;
      unset($fields[$field]);
    }

    return $display;
  }

  protected function getForm()
  {
    return array(
      'display' => $this->getFormDisplay(),
      'class' => $this->module->getModel().'AdminForm',
      'fields' => $this->getFormFields()
    );
  }

  protected function getFormDisplay()
  {
    $fields = dmArray::valueToKey(array_diff($this->table->getColumnNames(), array_unique(array_merge(
      // always exclude these fields
      self::$formExcludedFields,
      // exlude primary keys
      $this->table->getPrimaryKeys()
    ))));

    /*
     * Remove media fields not to see them in foreigns fields
     */
    foreach($this->table->getRelationHolder()->getLocalMedias() as $alias => $relation)
    {
      if (in_array($relation['local'], $fields))
      {
        unset($fields[$relation['local']]);
      }
    }

    $sets = array();

    $sets['NONE'] = array();

    if (in_array($this->table->getIdentifierColumnName(), $fields))
    {
    	$sets['NONE'][] = $this->table->getIdentifierColumnName();
    	unset($fields[$this->table->getIdentifierColumnName()]);
    }

    foreach($this->getBooleanFields($fields) as $field)
    {
      if (in_array($field, $fields))
      {
        $sets['NONE'][] = $field;
        unset($fields[$field]);
      }
    }

    foreach($this->table->getRelationHolder()->getLocals() as $relation)
    {
      $sets['NONE'][] = $relation->getLocalColumnName();
      unset($fields[$relation->getLocalColumnName()]);
    }


    foreach($this->table->getRelationHolder()->getLocalMedias() as $alias => $relation)
    {
    	$sets[dmString::humanize($relation['local'])] = array(
        $relation['local'].'_form',
        $relation['local'].'_view'
      );
    }

    foreach($this->getTextFields($fields) as $field)
    {
      if (in_array($field, $fields))
      {
        $sets[dmString::humanize($field)][] = $field;
        unset($fields[$field]);
      }
    }

    foreach($this->table->getRelationHolder()->getAssociations() as $alias => $relation)
    {
    	$associationModule = dmModuleManager::getModuleByModel($relation->getClass());
      $sets[$associationModule->getPlural()][] = dmString::underscore($alias).'_list';
    }

    $sets['Others'] = array();

    foreach($fields as $field)
    {
    	$sets['Others'][] = $field;
    	unset($fields[$field]);
    }

    return $this->removeEmptyValues($sets);
  }

  protected function getFormFields()
  {
    $fields = array();

//    foreach($this->table->getRelationHolder()->getAssociations() as $alias => $relation)
//    {
//      $fields[dmString::underscore($alias).'_list'] = array(
//        'label' => false
//      );
//    }

    return $fields;
  }

  protected function getEdit()
  {
    return '~';
  }

  protected function getNew()
  {
    return '~';
  }

  protected function getTextFields($fields = null)
  {
    return $this->filterFields($fields, array('clob', 'blob'));
  }

  protected function getBooleanFields($fields = null)
  {
    return $this->filterFields($fields, array('boolean'));
  }

  protected function filterFields($fields = null, $types)
  {
  	$fields = is_null($fields) ? $this->table->getColumnNames() : $fields;

    foreach($fields as $key => $field)
    {
      if(!in_array(dmArray::get($this->table->getColumn($field), 'type'), $types))
      {
        unset($fields[$key]);
      }
    }

    return $fields;
  }

  protected function removeEmptyValues($values)
  {
  	foreach($values as $key => $value)
  	{
      if (empty($value))
      {
      	unset($values[$key]);
      }
  	}
  	return $values;
  }

}