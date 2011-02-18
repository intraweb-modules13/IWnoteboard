<?php
function smarty_function_iwnoteboardusermenulinks($params, &$smarty)
{
	
	$tema = FormUtil::getPassedValue('tema');

	//Get the user permissions in noteboard
	$permisos = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos', array('uid' => UserUtil::getVar('uid')));

	// set some defaults
	if (!isset($params['start'])) {
		$params['start'] = '[';
	}
	if (!isset($params['end'])) {
		$params['end'] = ']';
	}
	if (!isset($params['seperator'])) {
		$params['seperator'] = '|';
	}
	if (!isset($params['class'])) {
		$params['class'] = 'pn-menuitem-title';
	}

	$noteboardusermenulinks = "<span class=\"" . $params['class'] . "\">" . $params['start'] . " ";

	if (SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ) && $permisos['nivell'] >= 3) {
		$noteboardusermenulinks .= "<a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('IWnoteboard', 'user', 'nova',array('m' => 'n', 'tema' => $tema))) . "\">" . $this->__('Add a new note',$dom) . "</a> " . $params['seperator'];
	}

	if (SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
		$noteboardusermenulinks .= " <a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('IWnoteboard', 'user', 'main', array('tema' => $tema))) . "\">" . $this->__('View notes list',$dom) . "</a> ";
	}

	if (SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ) && $permisos['potverificar']) {
		$noteboardusermenulinks .= $params['seperator'] . " <a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('IWnoteboard', 'user', 'main', array('tema' => $tema, 'saved' => '1'))) . "\">" . $this->__('Show the notes stored (expired)',$dom) . "</a> ";
	}

	$noteboardusermenulinks .= $params['end'] . "</span>\n";

	return $noteboardusermenulinks;
}
