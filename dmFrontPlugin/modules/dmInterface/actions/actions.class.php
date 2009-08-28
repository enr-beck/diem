<?php

include_once(dmOs::join(sfConfig::get('dm_core_dir'), 'modules/dmInterface/lib/baseDmInterfaceActions.php'));

class dmInterfaceActions extends baseDmInterfaceActions
{

  public function executeLoadPageTree(sfWebRequest $request)
  {
    $tree = new dmFrontRecursivePageList();

    $jsTree =
      file_get_contents(dmOs::join(sfConfig::get('sf_web_dir'), sfConfig::get('dm_core_asset'), 'lib/jsTree/source/tree_component.min.js')).
      file_get_contents(dmOs::join(sfConfig::get('sf_web_dir'), sfConfig::get('dm_core_asset'), 'lib/jsTree/source/css.js'))
    ;

    return $this->renderText($tree->render().'__DM_SPLIT__'.$jsTree);
  }

}