<?php namespace Tovarparams;

if (!IN_MANAGER_MODE) {
    die('<h1>ERROR:</h1><p>Please use the MODx Content Manager instead of accessing this file directly.</p>');
}

class Tovarparams {
	public function __construct($modx, $tv)
	{
        $this->modx = $modx;
        $this->tv = $tv;
		$this->docid = $this->getDocId();
		$this->path = 'assets/tvs/tovarparams/';
    }
	public function render()
	{
		$content = '';
		if ($this->docid) {
			$plh = array(
				'id' => $this->tv['id'],
				'value' => $this->tv['value'],
				'module_url' => MODX_SITE_URL . $this->path,
				'docid' => $this->docid//,
				//'tvs_list' => $this->modx->runSnippet("multiParams", array("action" => "getParamsToWebix"))
			);
			$content = $this->renderTpl($plh);
		} else {
			echo '0';
		}
		return $content;
	}
	
	public function getDocId()
	{
		return isset($_GET['id']) ? (int)$_GET['id'] : 0;
	}
	
	public function getTpl($tpl = 'tovarparams')
	{
		$tpl = file_get_contents(MODX_BASE_PATH . $this->path . 'tpl/' . $tpl . '.tpl');
		return $tpl;
	}
	
	public function renderTpl($plh = array(), $tpl = 'tovarparams', $left = '[+', $right = '+]')
	{
		$content = file_get_contents(MODX_BASE_PATH . 'assets/tvs/tovarparams/tpl/' . $tpl . '.tpl');
		return $this->modx->parseText($content, $plh, $left, $right);
	}
}
