<?php

function dm_datetime($datetime)
{
	return trim($datetime, ' CEST');
}

function definition_list($array, $opt = array())
{
  $html = £o('dl', dmString::toArray($opt, true));

  foreach($array as $key => $value)
  {
    $html .= sprintf('<dt>%s</dt><dd>%s</dd>', __($key), $value);
  }

  $html .= '</dl>';

  return $html;
}

function plural($word, $nb, $show_nb = true, $plural_spec = false)
{
  return $show_nb
  ? $nb." ".dmString::pluralizeNb($word, $nb, $plural_spec)
  : dmString::pluralizeNb($word, $nb, $plural_spec);
}


function £media($src)
{
	return dmMediaTag::build($src);
}

/*
 * a, class='tagada ergrg' id=zegf, contenu
 * a class=tagada id=truc, contenu
 * a, contenu
 * a, array(), contenu
 * a#truc.tagada, contenu
 */
function £o($name, array $opt = array())
{
  return dmStaticHelper::£o($name, $opt);
}

function £c($name)
{
  return dmStaticHelper::£c($name);
}

function £($name, $opt = array(), $content = false, $openAndClose = true)
{
  return dmStaticHelper::£($name, $opt, $content, $openAndClose);
}

function toggle($text = "odd")
{
	sfConfig::set('dm_helper_toggle', sfConfig::get('dm_helper_toggle')+1);
  return  sfConfig::get('dm_helper_toggle')%2 ? $text : "";
}

function toggle_init($val = 0)
{
  sfConfig::set('dm_helper_toggle', $val);
}