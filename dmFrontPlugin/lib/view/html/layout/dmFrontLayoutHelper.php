<?php

class dmFrontLayoutHelper extends dmCoreLayoutHelper
{
	protected
	  $page;

  protected function initialize()
  {
    parent::initialize();
    
    $this->page     = $this->dmContext->getPage();
  }
  
  public function renderBrowserStylesheets()
  {
		$html = '';

		// search in theme_dir/css/browser/ieX.css
		foreach(array(6, 7, 8) as $ieVersion)
		{
		  if (file_exists($this->theme->getFullPath('css/browser/msie'.$ieVersion.'.css')))
		  {
		  	$html .= "\n".sprintf('<!--[if IE %d]><link href="%s" rel="stylesheet" type="text/css" /><![endif]-->',
		  	  $ieVersion,
		  	  $this->theme->getWebPath('css/browser/msie'.$ieVersion.'.css')
		  	);
		  }
		}

		return $html;
  }


  public function renderIeHtml5Fix()
  {
  	return '<!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->';
  }
  
  public function renderBodyTag()
  {
		printf('<body class="%s_%s">',
		  $this->page->module,
		  $this->page->action
		);
  }

  protected function getMetas()
  {
  	$metas = array(
      'description'  => $this->page->description,
      'language'     => $this->user->getCulture(),
  	  'generator'    => 'Diem '.dm::version()
    );
    
    if (sfConfig::get('dm_seo_use_keywords'))
    {
    	$metas['keywords'] = $this->page->keywords;
    }
    
    if (!$this->site->isIndexable)
    {
    	$metas['robots'] = 'noindex, nofollow';
    }
    
    if ($this->site->wtCode && $this->page->Node->isRoot())
    {
    	$metas['verify-v1'] = $this->site->wtCode;
    }
    
    return $metas;
  }
  
  public function renderMetas()
  {
    $metaHtml = array(sprintf('<title>%s</title>', $this->page->title));
    
    foreach($this->getMetas() as $key => $value)
    {
      $metaHtml[] = sprintf('<meta name="%s" content="%s" />', $key, $value);
    }

    return implode(' ', $metaHtml);
  }
  
  
  public function renderEditBars()
  {
  	if (!$this->user->can('admin'))
  	{
  		return '';
  	}
  	
  	$this->dmContext->getSfContext()->getConfiguration()->loadHelpers('Partial');
  
  	$html = '';
  	
		if (sfConfig::get('dm_pageBar_enabled', true) && $this->user->can('page_bar_front'))
		{
		  $html .= get_partial('dmInterface/pageBar');
		}
		
		if (sfConfig::get('dm_mediaBar_enabled', true) && $this->user->can('media_bar_front'))
		{
		  $html .= get_partial('dmInterface/mediaBar');
		}
		
		if ($this->user->can('tool_bar_front'))
		{
		  $html .= get_component('dmInterface', 'toolBar');
    }
    
    return $html;
  }

  public function getJavascriptConfig()
  {
  	return array_merge(parent::getJavascriptConfig(), array(
  	  'page_id' => $this->page->id
  	));
  }
  
  public function renderGoogleAnalytics()
  {
  	if ($this->site->gaCode && !$this->user->can('admin') && !dmOs::isLocalhost())
  	{
  		return str_replace("\n", ' ', sprintf('<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("%s");
pageTracker._trackPageview();
} catch(err) {}</script>', $this->site->gaCode));
  	}
  	
  	return '';
  }
}