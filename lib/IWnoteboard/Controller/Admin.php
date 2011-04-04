<?php

class IWnoteboard_Controller_Admin extends Zikula_AbstractController {
    /**
     * Show the manage module site
     * @author		Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @return		The configuration information
     */
    public function main() {
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Checks if module IWmain is installed. If not returns error
        $modid = ModUtil::getIdFromName('IWmain');
        $modinfo = ModUtil::getInfo($modid);

        if ($modinfo['state'] != 3) {
            return LogUtil::registerError($this->__('Module IWmain is required. You have to install the IWmain module previously to install it.'));
        }

        // Check if the version needed is correct. If not return error
        $versionNeeded = '0.3';
        if (!ModUtil::func('IWmain', 'admin', 'checkVersion',
                        array('version' => $versionNeeded))) {
            return false;
        }
        $temes_array = array();
        $sharedsArray = array();
        $noFolder = false;
        $noWriteable = false;
        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        // Get the themes from the database
        $themes = ModUtil::apiFunc('IWnoteboard', 'user', 'getalltemes');

        // Get all the groups information
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $groupsInfo = ModUtil::func('IWmain', 'user', 'getAllGroupsInfo',
                        array('sv' => $sv));

        //Agefim la llista de temes on classificar les notÃ­cies
        foreach ($themes as $theme) {
            if ($theme['descriu'] == '') {
                $theme['descriu'] = '---';
            }
            $grup = ($theme['grup'] == 0) ? $this->__('All', $dom) : $groupsInfo[$theme['grup']];
            if ($grup == '') {
                $grup = '???';
            }
            $temes_array[] = array('tid' => $theme['tid'],
                'nomtema' => $theme['nomtema'],
                'descriu' => $theme['descriu'],
                'grup' => $grup);
        }

        $shareds = ModUtil::apiFunc('IWnoteboard', 'user', 'getallSharedURL');
        $grupsModVar = ModUtil::getVar('IWnoteboard', 'grups');
        $permisosModVar = ModUtil::getVar('IWnoteboard', 'permisos');
        $marcatModVar = ModUtil::getVar('IWnoteboard', 'marcat');
        $verificaModVar = ModUtil::getVar('IWnoteboard', 'verifica');
        $quiverifica = ModUtil::getVar('IWnoteboard', 'quiverifica');
        $caducitat = ModUtil::getVar('IWnoteboard', 'caducitat');
        $colorrow1 = ModUtil::getVar('IWnoteboard', 'colorrow1');
        $colorrow2 = ModUtil::getVar('IWnoteboard', 'colorrow2');
        $colornewrow1 = ModUtil::getVar('IWnoteboard', 'colornewrow1');
        $colornewrow2 = ModUtil::getVar('IWnoteboard', 'colornewrow2');
        $attached = ModUtil::getVar('IWnoteboard', 'attached');
        $directoriroot = ModUtil::getVar('IWmain', 'documentRoot');
        $notRegisteredSeeRedactors = ModUtil::getVar('IWnoteboard', 'notRegisteredSeeRedactors');
        $multiLanguage = ModUtil::getVar('IWnoteboard', 'multiLanguage');
        $public = ModUtil::getVar('IWnoteboard', 'public');
        $showSharedURL = ModUtil::getVar('IWnoteboard', 'showSharedURL');
        $topicsSystem = ModUtil::getVar('IWnoteboard', 'topicsSystem');
        $publicSharedURL = ModUtil::getVar('IWnoteboard', 'publicSharedURL');
        $sharedName = ModUtil::getVar('IWnoteboard', 'sharedName');
        $editPrintAfter = ModUtil::getVar('IWnoteboard', 'editPrintAfter');
        $repperdefecte = ModUtil::getVar('IWnoteboard', 'repperdefecte');

        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $groups = ModUtil::func('IWmain', 'user', 'getAllGroups',
                array('sv' => $sv,
                    'plus' => $this->__('Unregistered'),
                    'less' => ModUtil::getVar('iw_myrole', 'rolegroup')));

        if (!file_exists(ModUtil::getVar('IWmain', 'documentRoot') . '/' . ModUtil::getVar('IWnoteboard', 'attached')) || ModUtil::getVar('IWnoteboard', 'attached') == '') {
            $noFolder = true;
        } else {
            if (!is_writeable(ModUtil::getVar('IWmain', 'documentRoot') . '/' . ModUtil::getVar('IWnoteboard', 'attached'))) {
                $noWriteable = true;
            }
        }

        foreach ($groups as $group) {
            if (strpos($grupsModVar, '$' . $group['id'] . '$') != 0) {
                $select = true;
            } else {
                $select = false;
            }
            if (strpos($marcatModVar, '$' . $group['id'] . '$') != 0) {
                $select1 = true;
            } else {
                $select1 = false;
            }
            if (strpos($verificaModVar, '$' . $group['id'] . '$') != 0) {
                $select2 = true;
            } else {
                $select2 = false;
            }
            $permis = substr($permisosModVar, strpos($permisosModVar, '$' . $group['id'] . '-') + strlen($group['id']) + 2, 1);
            $grups_array[] = array('id' => $group['id'],
                'select' => $select,
                'name' => $group['name'],
                'select1' => $select1,
                'select2' => $select2,
                'permis' => $permis);
        }

        foreach ($shareds as $shared) {
            $url = str_replace('http://', '*******', $shared['url']);
            $url = str_replace('/', '/<br>', $url);
            $url = str_replace('*******', 'http://', $url);
            $sharedsArray[] = array('pid' => $shared['pid'],
                'name' => $shared['name'],
                'url' => $url,
                'descriu' => $shared['descriu'],
                'testDate' => $shared['testDate']);
        }
        $multizk = (isset($GLOBALS['PNConfig']['Multisites']['multi']) && $GLOBALS['PNConfig']['Multisites']['multi'] == 1) ? 1 : 0;
        $view->assign('multizk', $multizk);
        $view->assign('temes', $temes_array);
        $view->assign('grups', $grups_array);
        $view->assign('quiverifica', $quiverifica);
        $view->assign('caducitat', $caducitat);
        $view->assign('repperdefecte', $repperdefecte);
        $view->assign('colorrow1', $colorrow1);
        $view->assign('colorrow2', $colorrow2);
        $view->assign('colornewrow1', $colornewrow1);
        $view->assign('colornewrow2', $colornewrow2);
        $view->assign('attached', $attached);
        $view->assign('directoriroot', $directoriroot);
        $view->assign('notRegisteredSeeRedactors', $notRegisteredSeeRedactors);
        $view->assign('multiLanguage', $multiLanguage);
        $view->assign('public', $public);
        $view->assign('showSharedURL', $showSharedURL);
        $view->assign('topicsSystem', $topicsSystem);
        $view->assign('shareds', $sharedsArray);
        $view->assign('publicSharedURL', $publicSharedURL);
        $view->assign('sharedName', $sharedName);
        $view->assign('editPrintAfter', $editPrintAfter);
        $view->assign('noFolder', $noFolder);
        $view->assign('noWriteable', $noWriteable);

        if ($topicsSystem == 1) {
            // load necessary classes
            Loader::loadClass('CategoryUtil');
            // get categories
            $cats = CategoryUtil::getCategoriesByParentID(30);
            $view->assign('cats', $cats);
        }

        return $view->fetch('IWnoteboard_admin_conf.htm');
    }

    /**
     * Update the configuration values
     * @author	Albert Pï¿œrez Monfort (aperezm@xtec.cat)
     * @params	The config values from the form
     * @return	Thue if success
     */
    public function confupdate($args) {
        $g = FormUtil::getPassedValue('g', isset($args['g']) ? $args['g'] : null, 'POST');
        $p = FormUtil::getPassedValue('p', isset($args['p']) ? $args['p'] : null, 'POST');
        $m = FormUtil::getPassedValue('m', isset($args['m']) ? $args['mm'] : null, 'POST');
        $v = FormUtil::getPassedValue('v', isset($args['v']) ? $args['v'] : null, 'POST');
        $q = FormUtil::getPassedValue('q', isset($args['q']) ? $args['q'] : null, 'POST');
        $c = FormUtil::getPassedValue('c', isset($args['c']) ? $args['c'] : null, 'POST');
        $r = FormUtil::getPassedValue('r', isset($args['r']) ? $args['r'] : null, 'POST');
        $ext = FormUtil::getPassedValue('ext', isset($args['ext']) ? $args['ext'] : null, 'POST');
        $mida = FormUtil::getPassedValue('mida', isset($args['mida']) ? $args['mida'] : null, 'POST');
        $color1 = FormUtil::getPassedValue('color1', isset($args['color1']) ? $args['color1'] : null, 'POST');
        $color2 = FormUtil::getPassedValue('color2', isset($args['color2']) ? $args['color2'] : null, 'POST');
        $colornew1 = FormUtil::getPassedValue('colornew1', isset($args['colornew1']) ? $args['colornew1'] : null, 'POST');
        $colornew2 = FormUtil::getPassedValue('colornew2', isset($args['colornew2']) ? $args['colornew2'] : null, 'POST');
        $attached = FormUtil::getPassedValue('attached', isset($args['attached']) ? $args['attached'] : null, 'POST');
        $notRegisteredSeeRedactors = FormUtil::getPassedValue('notRegisteredSeeRedactors', isset($args['notRegisteredSeeRedactors']) ? $args['notRegisteredSeeRedactors'] : null, 'POST');
        $multiLanguage = FormUtil::getPassedValue('multiLanguage', isset($args['multiLanguage']) ? $args['multiLanguage'] : null, 'POST');
        $public = FormUtil::getPassedValue('public', isset($args['public']) ? $args['public'] : null, 'POST');
        $showSharedURL = FormUtil::getPassedValue('showSharedURL', isset($args['showSharedURL']) ? $args['showSharedURL'] : null, 'POST');
        $topicsSystem = FormUtil::getPassedValue('topicsSystem', isset($args['topicsSystem']) ? $args['topicsSystem'] : null, 'POST');
        $regenerateShared = FormUtil::getPassedValue('regenerateShared', isset($args['regenerateShared']) ? $args['regenerateShared'] : null, 'POST');
        $sharedName = FormUtil::getPassedValue('sharedName', isset($args['sharedName']) ? $args['sharedName'] : null, 'POST');
        $editPrintAfter = FormUtil::getPassedValue('editPrintAfter', isset($args['editPrintAfter']) ? $args['editPrintAfter'] : null, 'POST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        if (empty($mida)) {
            $mida = 0;
        }

        $select = '$$';
        foreach ($g as $g1) {
            $select .= $g1 . '$';
        }

        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $groups = ModUtil::func('IWmain', 'user', 'getAllGroups',
                array('sv' => $sv,
                    'plus' => $this->__('Unregistered'),
                    'less' => ModUtil::getVar('iw_myrole', 'rolegroup')));

        $i = 0;
        $permisos = '$$';
        foreach ($groups as $group) {
            $permisos .= $group['id'] . '-' . $p[$i] . '$';
            $i++;
        }

        $marcat = '$$';
        foreach ($m as $m1) {
            $marcat .= $m1 . '$';
        }

        $verifica = '$$';
        foreach ($v as $v1) {
            $verifica .= $v1 . '$';
        }

        if ($regenerateShared == 1 || ModUtil::getVar('IWnoteboard', 'publicSharedURL') == '') {
            $publicSharedValue = ModUtil::func('IWnoteboard', 'admin', 'regenerateShared');
            LogUtil::registerStatus($this->__('The shared URL has been modified. Remember to notify it to everybody you need'));
        }

        ModUtil::setVar('IWnoteboard', 'grups', $select);
        ModUtil::setVar('IWnoteboard', 'permisos', $permisos);
        ModUtil::setVar('IWnoteboard', 'marcat', $marcat);
        ModUtil::setVar('IWnoteboard', 'verifica', $verifica);
        ModUtil::setVar('IWnoteboard', 'quiverifica', $q);
        ModUtil::setVar('IWnoteboard', 'caducitat', $c);
        ModUtil::setVar('IWnoteboard', 'repperdefecte', $r);
        ModUtil::setVar('IWnoteboard', 'colorrow1', $color1);
        ModUtil::setVar('IWnoteboard', 'colorrow2', $color2);
        ModUtil::setVar('IWnoteboard', 'colornewrow1', $colornew1);
        ModUtil::setVar('IWnoteboard', 'colornewrow2', $colornew2);
        ModUtil::setVar('IWnoteboard', 'attached', $attached);
        ModUtil::setVar('IWnoteboard', 'notRegisteredSeeRedactors', $notRegisteredSeeRedactors);
        ModUtil::setVar('IWnoteboard', 'multiLanguage', $multiLanguage);
        ModUtil::setVar('IWnoteboard', 'public', $public);
        ModUtil::setVar('IWnoteboard', 'showSharedURL', $showSharedURL);
        ModUtil::setVar('IWnoteboard', 'topicsSystem', $topicsSystem);
        ModUtil::setVar('IWnoteboard', 'sharedName', $sharedName);
        ModUtil::setVar('IWnoteboard', 'editPrintAfter', $editPrintAfter);

        LogUtil::registerStatus($this->__('The configuration has been modified'));
        return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
    }

    /**
     * Show a form needed to create a new topic
     * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @return	The form fields
     */
    public function noutema() {

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Gets the groups
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $groups = ModUtil::func('IWmain', 'user', 'getAllGroups',
                array('plus' => $this->__('All'),
                    'less' => ModUtil::getVar('iw_myrole', 'rolegroup'),
                    'sv' => $sv));

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        $view->assign('grups', $groups);
        $view->assign('title', $this->__('Create a new topic'));
        $view->assign('submit', $this->__('Create the topic'));
        $view->assign('nomtema', '');
        $view->assign('descriu', '');
        $view->assign('grup', 0);
        $view->assign('tid', 0);
        return $view->fetch('IWnoteboard_admin_noutema.htm');
    }

    /**
     * Create a new topic
     * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the topic information
     * @return	redirect the user to the main admin page
     */
    public function crear($args) {

        // get the parameters sended from the form
        $nomtema = FormUtil::getPassedValue('nomtema', isset($args['nomtema']) ? $args['nomtema'] : null, 'POST');
        $descriu = FormUtil::getPassedValue('descriu', isset($args['descriu']) ? $args['descriu'] : null, 'POST');
        $grup = FormUtil::getPassedValue('grup', isset($args['grup']) ? $args['grup'] : null, 'POST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        // create the new topic
        $lid = ModUtil::apiFunc('IWnoteboard', 'admin', 'crear',
                        array('nomtema' => $nomtema,
                            'descriu' => $descriu,
                            'grup' => $grup));

        if ($lid != false) {
            // Success
            LogUtil::registerStatus($this->__('A new topic has been created'));
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule',
                            array('name' => 'nbtopics',
                                'module' => 'IWnoteboard',
                                'sv' => $sv));
        }

        // Redirect to the main site for the admin
        return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
    }

    /**
     * Give access to a form from where the topics information can be edited
     * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the topic id
     * @return	The topics edit form
     */
    public function editar($args) {

        // Get parameters from whatever input we need
        $tid = FormUtil::getPassedValue('tid', isset($args['tid']) ? $args['tid'] : null, 'GET');
        $objectid = FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'GET');
        if (!empty($objectid)) {
            $tid = $objectid;
        }

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'gettema', array('tid' => $tid));

        if ($registre == false) {
            LogUtil::registerError($this->__('The topic has not been found'));
            return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        // Get all the groups
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $groups = ModUtil::func('IWmain', 'user', 'getAllGroups',
                array('plus' => $this->__('All'),
                    'sv' => $sv,
                    'less' => ModUtil::getVar('iw_myrole', 'rolegroup')));

        $view->assign('tid', $tid);
        $view->assign('title', $this->__('Edit a topic'));
        $view->assign('nomtema', $registre['nomtema']);
        $view->assign('descriu', $registre['descriu']);
        $view->assign('grup', $registre['grup']);
        $view->assign('grups', $groups);
        $view->assign('submit', $this->__('Modify the topic'));
        $view->assign('m', 1);

        return $view->fetch('IWnoteboard_admin_noutema.htm');
    }

    /**
     * Update a topic information
     * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the arguments needed
     * @return	Redirect the user to the admin main page
     */
    public function modificar($args) {

        // Get parameters from whatever input we need
        $tid = FormUtil::getPassedValue('tid', isset($args['tid']) ? $args['tid'] : null, 'POST');
        $nomtema = FormUtil::getPassedValue('nomtema', isset($args['nomtema']) ? $args['nomtema'] : null, 'POST');
        $descriu = FormUtil::getPassedValue('descriu', isset($args['descriu']) ? $args['descriu'] : null, 'POST');
        $grup = FormUtil::getPassedValue('grup', isset($args['grup']) ? $args['grup'] : null, 'POST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        $lid = ModUtil::apiFunc('IWnoteboard', 'admin', 'modificar',
                array('tid' => $tid,
                    'nomtema' => $nomtema,
                    'descriu' => $descriu,
                    'grup' => $grup));
        if ($lid != false) {
            // Success
            LogUtil::registerStatus($this->__('The topic has been modified'));
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule',
                    array('name' => 'nbtopics',
                        'module' => 'IWnoteboard',
                        'sv' => $sv));
        }

        // Return to admin pannel
        return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
    }

    /**
     * Show a form needed to create a new shared link
     * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @return	The form fields
     */
    public function newShared() {

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }


        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        $view->assign('title', $this->__('Create a new linked noteboard'));
        $view->assign('submit', $this->__('Create'));

        return $view->fetch('IWnoteboard_admin_newShared.htm');
    }

    /**
     * Create a new shared url
     * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the topic information
     * @return	redirect the user to the main admin page
     */
    public function createShared($args) {

        // get the parameters sended from the form
        $url = FormUtil::getPassedValue('url', isset($args['url']) ? $args['url'] : null, 'POST');
        $descriu = FormUtil::getPassedValue('descriu', isset($args['descriu']) ? $args['descriu'] : null, 'POST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        // create the new shared url
        $lid = ModUtil::apiFunc('IWnoteboard', 'admin', 'createShared',
                array('url' => $url,
                    'descriu' => $descriu));

        //Check if the shared nateboard is available and shared. If not returns false
        $available = true;

        if (!$available) {
            LogUtil::registerError($this->__('The noteboard is not available or is not shared'));
            return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        if ($lid != false) {
            // Success
            LogUtil::registerStatus($this->__('A new topic has been created'));
            /* 		$sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
              ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule', array('name' => 'nbtopics',
              'module' => 'IWnoteboard',
              'sv' => $sv)); */
        }

        // Redirect to the main site for the admin
        return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
    }

    /**
     * Give access to a form from where the shared information can be edited
     * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the topic id
     * @return	The topics edit form
     */
    public function editShared($args) {

        // Get parameters from whatever input we need
        $pid = FormUtil::getPassedValue('pid', isset($args['pid']) ? $args['pid'] : null, 'GET');
        $objectid = FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'GET');
        if (!empty($objectid)) {
            $pid = $objectid;
        }

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'getShared',
                array('pid' => $pid));

        if ($registre == false) {
            LogUtil::registerError($this->__('The topic has not been found'));
            return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        $view->assign('pid', $pid);
        $view->assign('title', $this->__('Edit a topic'));
        $view->assign('url', $registre['url']);
        $view->assign('descriu', $registre['descriu']);
        $view->assign('submit', $this->__('Modify the topic'));
        $view->assign('m', 1);

        return $view->fetch('IWnoteboard_admin_newShared.htm');
    }

    /**
     * Update a shared URL information
     * @author	Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the arguments needed
     * @return	Redirect the user to the admin main page
     */
    public function updateShared($args) {


        // Get parameters from whatever input we need
        $pid = FormUtil::getPassedValue('pid', isset($args['pid']) ? $args['pid'] : null, 'POST');
        $url = FormUtil::getPassedValue('url', isset($args['url']) ? $args['url'] : null, 'POST');
        $descriu = FormUtil::getPassedValue('descriu', isset($args['descriu']) ? $args['descriu'] : null, 'POST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        $lid = ModUtil::apiFunc('IWnoteboard', 'admin', 'editShared',
                array('pid' => $pid,
                    'url' => $url,
                    'descriu' => $descriu));
        if ($lid != false) {
            // Success
            LogUtil::registerStatus($this->__('The shared noteboard URL has been modified'));
            /* 		$sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
              ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule', array('name' => 'nbtopics',
              'module' => 'IWnoteboard',
              'sv' => $sv)); */
        }

        // Return to admin pannel
        return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
    }

    /**
     * Check if a noteboard is ahared and available
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the shared noteboard
     * @return:	The shared parameters of false if the noteboard requested is not shared
     */
    public function checkShared($args) {

        $pid = FormUtil::getPassedValue('pid', isset($args['pid']) ? $args['pid'] : null, 'GET');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        // Needed argument
        if (!isset($pid)) {
            LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
            return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
        }

        // Get the item
        $item = ModUtil::apiFunc('IWnoteboard', 'user', 'getShared',
                array('pid' => $pid));
        if (!$item) {
            return LogUtil::registerError($this->__('No such item found.'));
        }

        $shared = ModUtil::func('IWnoteboard', 'admin', 'requestShared',
                array('url' => $item['url']));

        if ($shared) {
            LogUtil::registerStatus($this->__('The noteboard is shared and it is available'));
        } else {
            LogUtil::registerError($this->__('The noteboard is not shared or it is not available in this moment'));
        }

        return System::redirect(ModUtil::url('IWnoteboard', 'admin', 'main'));
    }

    /**
     * Get the values of the request shared
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the shared noteboard
     * @return:	The shared parameters of false if the noteboard requested is not shared
     */
    public function requestShared($args) {
        $url = FormUtil::getPassedValue('url', isset($args['url']) ? $args['url'] : null, 'POST');

        //AquÃ­ hi hauria d'anar la part que consulta al tauler d'un altre espai. Caldrï¿œ construir un servlet o alguna cosa de l'estil

        return true;
    }

    /**
     * Regenerate shared url with another shared value
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @return:	Thue if success and false otherwise
     */
    public function regenerateShared() {
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        $randomValue = rand(0, 350000) . time();

        ModUtil::setVar('IWnoteboard', 'publicSharedURL', md5($randomValue));

        return md5($randomValue);
    }

    public function sharedOptions($args) {

        $public = FormUtil::getPassedValue('public', isset($args['public']) ? $args['public'] : null, 'POST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        //get shared sid
        $publicSharedURL = ModUtil::getVar('IWnoteboard', 'publicSharedURL');

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);
        $view->assign('public', $public);
        $view->assign('publicSharedURL', $publicSharedURL);

        return $view->fetch('IWnoteboard_admin_confSharedOptions.htm');
    }
}