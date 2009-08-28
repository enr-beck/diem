<?php

class dmServerCheck
{
	protected $checks;

	const WARNING = 1;
	const ERROR = 2;

	public function __construct()
	{
		$this->checks = array(
		  'server' => array(
		new dmServerCheckUnit('unix', DIRECTORY_SEPARATOR == '/', true, self::ERROR)
		),
	    'php config' => array(
		new dmServerCheckUnit('version', phpversion(), '5.2.4', self::ERROR),
		new dmServerCheckUnit('memory', ini_get('memory_limit'), 48, self::ERROR),
		new dmServerCheckUnit('magic quotes', ini_get('magic_quotes_gpc'), false),
		new dmServerCheckUnit('upload max filesize', ini_get('upload_max_filesize'), 4),
		new dmServerCheckUnit('post max size', ini_get('post_max_size'), 4),
		new dmServerCheckUnit('register globals', ini_get('register_globals'), false),
		new dmServerCheckUnit('session auto_start', ini_get('session.auto_start'), false)
		),
	     'symfony' => array(
		new dmServerCheckUnit('version', SYMFONY_VERSION, '1.3.0-DEV', self::ERROR)
		),
       'php extensions' => array(
//		new dmServerCheckUnit('mysql', extension_loaded('mysql'), true, self::ERROR),
    new dmServerCheckUnit('spl', extension_loaded('spl'), true, self::ERROR),
		new dmServerCheckUnit('pdo', extension_loaded('pdo'), true, self::ERROR),
    new dmServerCheckUnit('pdo_mysql', extension_loaded('pdo_mysql'), true, self::ERROR),
		new dmServerCheckUnit('json', extension_loaded('json') ? phpversion('json') : false, '1.0', self::ERROR),
		new dmServerCheckUnit('gd', extension_loaded('gd'), true, self::ERROR),
		new dmServerCheckUnit('date', extension_loaded('date'), true, self::ERROR),
    new dmServerCheckUnit('ctype', extension_loaded('ctype'), true, self::ERROR),
    new dmServerCheckUnit('dom', extension_loaded('dom'), true, self::ERROR),
    new dmServerCheckUnit('iconv', extension_loaded('iconv'), true, self::ERROR),
    new dmServerCheckUnit('pcre', extension_loaded('pcre'), true, self::ERROR),
    new dmServerCheckUnit('reflection', extension_loaded('Reflection'), true, self::ERROR),
    new dmServerCheckUnit('session', extension_loaded('session'), true, self::ERROR),
    new dmServerCheckUnit('simplexml', extension_loaded('SimpleXML'), true, self::ERROR),
		new dmServerCheckUnit('bitset', extension_loaded('bitset'), true),
		new dmServerCheckUnit('apc', function_exists('apc_store') ? phpversion('apc') : false, '3.0'),
		new dmServerCheckUnit('mbstring', extension_loaded('mbstring'), true),
		new dmServerCheckUnit('curl', extension_loaded('curl'), true),
		new dmServerCheckUnit('xml', extension_loaded('xml'), true),
		new dmServerCheckUnit('xsl', extension_loaded('xsl'), true),
		new dmServerCheckUnit('ftp', extension_loaded('ftp'), true),
		new dmServerCheckUnit('tidy', extension_loaded('tidy'), true)
		)
		);
	}

	public function getChecks()
	{
		return $this->checks;
	}

	public function render()
	{
		return $this->renderHead().
		$this->renderContent().
		$this->renderFoot();
	}

	public function renderContent()
	{
		return
		sprintf('<h1>Diem %s System Check</h1>', sfConfig::get('dm_version')).
		'<div class="clearfix">'.
		sprintf('<div class="half">%s%s%s</div>',
		$this->renderTable('server'),
		$this->renderTable('php config'),
		$this->renderTable('symfony')
		).
		sprintf('<div class="half">%s</div>', $this->renderTable('php extensions')).
    '</div>';
	}

	protected function renderTable($name)
	{
		return
		'<table>'.
		sprintf('<thead><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr></thead>', $name, 'Diem requirement', 'Server state', 'Diagnostic').
		sprintf('<tbody>%s</tbody>', $this->renderRows($this->checks[$name])).
	  '</table>';
	}

	protected function renderRows(array $checks)
	{
		$html = '';
		foreach($checks as $check)
		{
			$html .= sprintf('<tr class="%s"><th>%s</th><td>%s</td><td>%s</td><td>%s</td></tr>',
			$check->getDiagnostic(),
			$check->renderName(),
			$check->renderRequirement(),
			$check->renderState(),
			$check->renderDiagnostic()
			);
		}
		return $html;
	}

	protected function renderHead()
	{
		return sprintf('<html>
<head>
<title>Diem %s System Check</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="language" content="en" />
<style type="text/css">%s</style>
</head>',
		sfConfig::get('dm_version'),
		file_get_contents(dmOs::join(dm::getDir(), 'dmCorePlugin/web/lib/blueprint/screen.css')).
		file_get_contents(dmOs::join(dm::getDir(), 'dmCorePlugin/web/css/serverCheck.css'))
		);
	}

	protected function renderFoot()
	{
		return '</html>';
	}
}

class dmServerCheckUnit
{
	const TYPE_BOOL = 1;
	const TYPE_BYTE = 2;
	const TYPE_VERSION = 3;

	protected
	$name,
	$requirement,
	$state,
	$level;

	public function __construct($name, $state, $requirement, $level = dmServerCheck::WARNING)
	{
		$this->name = $name;
		$this->state = $state;
		$this->requirement = $requirement;
		$this->level = $level;
	}

	public function renderName()
	{
		return $this->name;
	}

	public function renderRequirement()
	{
		return $this->renderValue($this->requirement);
	}

	public function renderState()
	{
		return $this->renderValue($this->state);
	}

	protected function renderValue($value)
	{
		switch($this->getType())
		{
			case self::TYPE_BOOL: $response = $value ? 'ON' : 'OFF'; break;
			case self::TYPE_BYTE: $response = dmOs::humanizeSize($this->realSize($value)); break;
			default:              $response = $value ? $value : '-';
		}

		return $response;
	}

	public function renderDiagnostic()
	{
		$diagnostic = $this->getDiagnostic();
		return sprintf('<img src="dm/core/images/24/%s.png" alt="%s" />', $diagnostic, strtoupper($diagnostic));
	}

	public function getDiagnostic()
	{
		if ($this->pass())
		{
			$diagnostic = 'valid';
		}
		else
		{
			$diagnostic = $this->level == dmServerCheck::WARNING ? 'warning' : 'error';
		}

		return $diagnostic;
	}

	public function pass()
	{
		if($this->isType(self::TYPE_BOOL))
		{
			return $this->state == $this->requirement;
		}

		return version_compare($this->state, $this->requirement) >= 0;
	}

	public function isType($type)
	{
		return $this->getType() == $type;
	}

	public function getType()
	{
		if(is_bool($this->requirement))
		{
			return self::TYPE_BOOL;
		}
		if(is_integer($this->requirement))
		{
			return self::TYPE_BYTE;
		}

		return self::TYPE_VERSION;
	}

	protected function realsize($val)
	{
		if (!is_numeric($val{strlen( $val ) - 1}))
		{
			return $val*1024*1024;
		}
		switch ( $val{strlen( $val ) - 1} )
		{
			case 'G':
				$val *= 1024;
			case 'M':
				$val *= 1024;
			case 'K':
				$val *= 1024;
		}

		return round($val / (1024 * 1024));
	}
}