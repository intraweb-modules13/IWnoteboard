<?php
/**
 * Define a note as marked
 * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
 * @param:	args   Array with the id of the note
 * @return:	Redirect to the user main page
*/
function IWnoteboard_ajax_marca($args)
{
	
	if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('Sorry! No authorization to access this module.')));
	}
	if(!UserUtil::isLoggedIn()){
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('You are not allowed to do this action')));
	}
	$nid = FormUtil::getPassedValue('nid', -1, 'GET');
	if ($nid == -1) {
		LogUtil::registerError('no block id');
		AjaxUtil::output();
	}
   	// get a note information
	$note = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                            array('nid' => $nid));
	if ($note == false) {
        	LogUtil::registerError('unable to get note info for nid=' . DataUtil::formatForDisplay($nid));
        	AjaxUtil::output();
	}
	if (strpos($note['marca'],'$'.UserUtil::getVar('uid').'$')!=0) {
		$marca = str_replace('$'.UserUtil::getVar('uid').'$','',$note['marca']);
	} else {
		$marca = $note['marca'].'$'.UserUtil::getVar('uid').'$';		
	}
	// Delete or create the mark
	$lid = ModUtil::apiFunc('IWnoteboard', 'user', 'marca',
                            array('nid' => $nid,
							      'marca' => $marca));
	if (!$lid) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('The action has failed')));
	} else {
		$sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
		ModUtil::func('IWmain', 'user', 'userSetVar',
                    array('module' => 'IWmain_block_flagged',
					      'name' => 'have_flags',
						  'value' => 'ta',
						  'sv' => $sv));
	}
	AjaxUtil::output(array('nid' => $nid));
}

/**
 * Hide a note of a user
 * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
 * @param:	args   Array with the id of the note
 * @return:	Redirect to the user main page
*/
function IWnoteboard_ajax_hide($args)
{
	
	if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('Sorry! No authorization to access this module.')));
	}
	if(!UserUtil::isLoggedIn()){
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('You are not allowed to do this action')));
	}
	$nid = FormUtil::getPassedValue('nid', -1, 'GET');
	if ($nid == -1) {
		LogUtil::registerError('no block id');
		AjaxUtil::output();
	}
   	// get a note information
	$note = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                            array('nid' => $nid));
	if ($note == false) {
        	LogUtil::registerError('unable to get note info for nid=' . DataUtil::formatForDisplay($nid));
        	AjaxUtil::output();
	}
	// add the user as the hide note list
	$no_mostrar = $note['no_mostrar'].'$'.UserUtil::getVar('uid').'$';
	// delete the user as a signed note
	$marca = str_replace('$'.UserUtil::getVar('uid').'$','',$note['marca']);
	// hide a note for a user
	$lid = ModUtil::apiFunc('IWnoteboard', 'user', 'no_mostrar',
                            array('nid' => $nid,
							      'no_mostrar' => $no_mostrar,
								  'marca' => $marca));
	if(!$lid){
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('The action has failed')));
	}
	//Delete users headlines var. This renoval the block information
	if($note['titular'] != ''){
		$sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
		ModUtil::apiFunc('IWmain', 'user', 'userDelVar', array('name' => 'nbheadlines',
									'module' => 'IWnoteboard',
									'uid' => UserUtil::getVar('uid'),
									'sv' => $sv));
	}
	AjaxUtil::output(array('nid' => $nid));
}

/**
 * Force the caducity of a note
 * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
 * @param:	args   Array with the id of the note
 * @return:	Redirect to the user main page
*/
function IWnoteboard_ajax_save($args)
{
	
	if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('Sorry! No authorization to access this module.')));
	}

	$permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                                    array('uid' => UserUtil::getVar('uid')));
	// Security check
	if (!$permissions['potverificar']) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('You are not allowed to do this action')));
	}
	$nid = FormUtil::getPassedValue('nid', -1, 'GET');
	if ($nid == -1) {
		LogUtil::registerError('no block id');
		AjaxUtil::output();
	}
   	// get a note information
	$note = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                            array('nid' => $nid));
	if ($note == false) {
        	LogUtil::registerError('unable to get note info for nid=' . DataUtil::formatForDisplay($nid));
        	AjaxUtil::output();
	}
	$security=SecurityUtil::generateAuthKey();
	$save = ModUtil::func('IWnoteboard', 'user', 'nova',
                        array('nid' => $nid,
                              'm' => 'c',
                              'authid' => $security));
	if (!$save) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('The action has failed')));
	}
	//Delete users headlines var. This renoval the block information
	if($note['titular'] != ''){
		$sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
		ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule', array('name' => 'nbheadlines',
																	'module' => 'IWnoteboard',
																	'sv' => $sv));
	}
	AjaxUtil::output(array('nid' => $nid));
}

/**
 * Delete a note
 * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
 * @param:	args   Array with the id of the note, the topic that is viewing the user and the information of the saved mode
 * @return:	Thue if success
*/
function IWnoteboard_ajax_delete($args)
{
	
	if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('Sorry! No authorization to access this module.')));
	}
	$nid = FormUtil::getPassedValue('nid', -1, 'GET');
	if ($nid == -1) {
		LogUtil::registerError('no block id');
		AjaxUtil::output();
	}
   	// get a note information
	$note = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                            array('nid' => $nid));
	if ($note == false) {
        	LogUtil::registerError('unable to get note info for nid=' . DataUtil::formatForDisplay($nid));
        	AjaxUtil::output();
	}
	// Get the user permissions in noteboard
	$permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                                    array('uid' => UserUtil::getVar('uid')));
	if ($permissions['nivell'] < 6) {
		LogUtil::registerError($this->__('You are not allowed to do this action'));
		AjaxUtil::output();
	}
	if ($note['informa'] != UserUtil::getVar('uid') &&
		$permissions['nivell'] == 6) {
		LogUtil::registerError($this->__('You are not allowed to do this action'));
		AjaxUtil::output();
	}
	// Check if the note have comments. In this case can't be deleted until the comments not be deleted
	$comentaris = ModUtil::apiFunc('IWnoteboard', 'user', 'getallcomentaris',
                                array('ncid' => $nid));
	if (!empty($comentaris)) {
		LogUtil::registerError($this->__('The note cannot be deleted because it has comments. Remove the comments previously.'));
		AjaxUtil::output();
	}
	$fileName = $note['fitxer'];
	// Proceed with the record deletion
	if (ModUtil::apiFunc('IWnoteboard', 'user', 'delete',
                        array('nid' => $nid))) {
		// Deletion successfully
		LogUtil::registerStatus ($this->__('The note has been deleted'));
		// Delete the attached file in case it exists
		if ($fileName != '') {
			$folder = ModUtil::getVar('IWnoteboard','attached');
			$sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
			$delete = ModUtil::func('IWmain', 'user', 'deleteFile',
                                    array('sv' => $sv,
										  'folder' => $folder,
										  'fileName' => $fileName));
		}
	} else {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('The action has failed')));
	}
	//Delete users headlines var. This renoval the block information
	if ($note['titular'] != '') {
		$sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
		ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule',
                        array('name' => 'nbheadlines',
						      'module' => 'IWnoteboard',
							  'sv' => $sv));
	}
	AjaxUtil::output(array('nid' => $nid));
}

/**
 * Delete a topic
 * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
 * @param:	args   Array with the topic id
 * @return	true if the topic have been deleted
 */
function IWnoteboard_ajax_deletetopic($args)
{
	
	if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_ADMIN)) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('Sorry! No authorization to access this module.')));
	}
	if (!SecurityUtil::confirmAuthKey()) {
		AjaxUtil::error($this->__('Invalid \'authkey\':  this probably means that you pressed the \'Back\' button, or that the page \'authkey\' expired. Please refresh the page and try again.'));
	}
	$tid = FormUtil::getPassedValue('tid', -1, 'GET');
	if ($tid == -1) {
		LogUtil::registerError('no topic id');
		AjaxUtil::output();
	}
   	// get a note information
	$topic = ModUtil::apiFunc('IWnoteboard', 'user', 'gettema',
                            array('tid' => $tid));
	if ($topic == false) {
        	LogUtil::registerError('unable to get topic info for tid=' . DataUtil::formatForDisplay($tid));
        	AjaxUtil::output();
	}	$lid = ModUtil::apiFunc('IWnoteboard', 'admin', 'esborra',
                            array('tid' => $tid));
	if (!$lid) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('The action has failed')));
	} else {
		// delete the record
		// Success
		$sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
		ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule',
                        array('name' => 'nbtopics',
						      'module' => 'IWnoteboard',
							  'sv' => $sv));
	}
 	AjaxUtil::output(array('tid' => $tid));
}

/**
 * Delete a shared URL
 * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
 * @param:	args   Array with the shared id
 * @return	true if the topic have been deleted
 */
function IWnoteboard_ajax_deleteShared($args)
{
	
	if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_ADMIN)) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('Sorry! No authorization to access this module.')));
	}
	if (!SecurityUtil::confirmAuthKey()) {
		AjaxUtil::error($this->__('Invalid \'authkey\':  this probably means that you pressed the \'Back\' button, or that the page \'authkey\' expired. Please refresh the page and try again.'));
	}
	$pid = FormUtil::getPassedValue('pid', -1, 'GET');
	if ($pid == -1) {
		LogUtil::registerError('no shared id');
		AjaxUtil::output();
	}
   	// get a note information
	$shared = ModUtil::apiFunc('IWnoteboard', 'user', 'getShared',
                            array('pid' => $pid));
	if ($shared == false) {
        	LogUtil::registerError('unable to get shared info for pid=' . DataUtil::formatForDisplay($pid));
        	AjaxUtil::output();
	}	$lid = ModUtil::apiFunc('IWnoteboard', 'admin', 'delShared',
                            array('pid' => $pid));
	if (!$lid) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('The action has failed')));
	}
 	AjaxUtil::output(array('pid' => $pid));
}

/**
 * Regenerate a shared URL
 * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
 * @param:	args   Array with the shared id
 * @return	the new content for the url div information
 */
function IWnoteboard_ajax_regenSharedURL()
{
	
	if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_ADMIN)) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('Sorry! No authorization to access this module.')));
	}
	$regenerated = ModUtil::func('IWnoteboard', 'admin', 'regenerateShared');
	if ($regenerated == false) {
        	LogUtil::registerError('unable to regenerate shared URL');
        	AjaxUtil::output();
	}
	$view = Zikula_View::getInstance('IWnoteboard',false);
	$view->assign ('sid', ModUtil::getVar('IWnoteboard', 'publicSharedURL'));
	$content = $view->fetch('IWnoteboard_admin_sharedURL.htm');
 	AjaxUtil::output(array('content' => $content));
}

/**
 * creata a new shared URL
 * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
 * @param:	args   Array with the shared id
 * @return	the new content for the url div information
 */
function IWnoteboard_ajax_createSharedURL()
{
	
	if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_ADMIN)) {
		AjaxUtil::error(DataUtil::formatForDisplayHTML($this->__('Sorry! No authorization to access this module.')));
	}
	$public = FormUtil::getPassedValue('public', -1, 'GET');
	if ($public == -1) {
		LogUtil::registerError('no topic id');
		AjaxUtil::output();
	}
	$content = ModUtil::func('IWnoteboard', 'admin', 'sharedOptions',
                            array('public' => $public));
	AjaxUtil::output(array('content' => $content));
}