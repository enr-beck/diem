<?php

$helper = new myAuthLayoutHelper(dmContext::getInstance());

echo 
$helper->renderDoctype(),
$helper->renderHtmlTag(),

  "\n<head>\n",
    $helper->renderHttpMetas(),
    $helper->renderMetas(),
    $helper->renderStylesheets(),
    $helper->renderFavicon(),
  "\n</head>\n",
  
  $helper->renderBodyTag('bg_2'),

    $sf_content,

    $helper->renderJavascriptConfig(),
      
    $helper->renderJavascripts(),
      
  '</body>',
'</html>';