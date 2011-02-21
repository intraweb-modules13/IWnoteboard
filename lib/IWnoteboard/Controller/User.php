<?php

class IWnoteboard_Controller_User extends Zikula_Controller {

    /**
     * Show the list of notes that an user can read
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the topic that is viewing the user, the id of the note and if the user is in the saved mode
     * @return:	The list of notes that the user can read
     */
    public function main($args) {
        // Get the parameters
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'GET');
        $tema = FormUtil::getPassedValue('tema', isset($args['tema']) ? $args['tema'] : 0, 'REQUEST');
        $saved = FormUtil::getPassedValue('saved', isset($args['saved']) ? $args['saved'] : null, 'REQUEST');
        $marked = FormUtil::getPassedValue('marked', isset($args['marked']) ? $args['marked'] : 0, 'REQUEST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // get user identity
        $uid = UserUtil::getVar('uid');
        if ($uid == '') {
            $uid = '-1';
        }
        $usersList = '';
        $anotacions = array();
        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => $uid));

        // Get all current groups
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $allgroups = ModUtil::func('IWmain', 'user', 'getAllGroups',
                        array('sv' => $sv,
                            'plus' => $this->__('Unregistered')));
        //get notes from shared noteboards
        $currentuser_groups = $permissions['grups'];

        array_walk($currentuser_groups, 'groupidtonamemd5', $allgroups);
        $str_groups = "";
        foreach ($currentuser_groups as $user_group) {
            $str_groups .= "&g[]=" . $user_group;
        }

        // Display shared noteboard notes
        $shared_urls = ModUtil::apiFunc('IWnoteboard', 'user', 'getallSharedURL');
        foreach ($shared_urls as $shared) {
            $url = $shared['url'] . $str_groups;
            $rss_content = ModUtil::apiFunc('IWnoteboard', 'user', 'display_rss',
                            array("url" => $url));
        }

        // Get all the notes that have been sended
        if (isset($saved) &&
                $saved == 1 &&
                $permissions['potverificar']) {
            $registres = ModUtil::apiFunc('IWnoteboard', 'user', 'getallcaducated',
                            array('tema' => $tema,
                                'nid' => $nid));
        } else {
            $registres = ModUtil::apiFunc('IWnoteboard', 'user', 'getall',
                            array('tema' => $tema,
                                'nid' => $nid,
                                'marked' => $marked));
            $saved = 0;
        }

        if (UserUtil::isLoggedIn()) {
            // Get the list of topics
            $temes = ModUtil::apiFunc('IWnoteboard', 'user', 'getalltemes');
            $temes_MS[] = array('id' => '0',
                'name' => $this->__('All the topics'));
            $temes_MS[] = array('id' => '-1',
                'name' => $this->__('Not classified'));

            foreach ($temes as $untema) {
                //Check if user can see the thopic
                $isInArray = in_array($untema['grup'], $permissions['grups']);
                if ($isInArray || $permissions['potverificar']) {
                    $temes_MS[] = array('id' => $untema['tid'],
                        'name' => $untema['nomtema']);
                }
            }

            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            $havist = ModUtil::func('IWmain', 'user', 'userGetVar',
                            array('module' => 'IWnoteboard',
                                'name' => 'viewed',
                                'sv' => $sv));
        }

        $vistes = '$';

        $shareds = ModUtil::apiFunc('IWnoteboard', 'user', 'getallSharedURL');
        $sharedsArray = array();
        foreach ($shareds as $shared) {
            $parsed_url = parse_url($shared['url']);
            $start = strpos($parsed_url['query'], "sid=") + 4;
            $sid = substr($parsed_url['query'], $start);
            $base_url = substr($shared['url'], 0, strpos($shared['url'], "?"));
            //$sharedsArray[$sid] = $shared['name'];
            $sharedsArray[$sid] = array("name" => $shared['descriu'],
                "base_url" => $base_url);
        }

        foreach ($registres as $registre) {
            // insert the list of groups that have access to the note into an array
            $grups_acces = explode('$', $registre['destinataris']);

            $esta_en_grups_acces = array_intersect($grups_acces, $permissions['grups']);
            $pos = strpos($registre['no_mostrar'], '$' . $uid . '$');

            if (isset($saved) &&
                    $saved == 1 &&
                    $permissions['potverificar']) {
                $pos = 0;
            }

            $marca = (strpos($registre['marca'], '$' . $uid . '$') != 0) ? 1 : 0;
            if (UserUtil::isLoggedIn()) {
                $tema_anotacio = ModUtil::apiFunc('IWnoteboard', 'user', 'gettema',
                                array('tid' => $registre['tid']));
            }

            if ((($registre['verifica'] == 1 &&
                    (count($esta_en_grups_acces) >= 1 ||
                    $uid == $registre['informa'])) ||
                    $permissions['potverificar']) &&
                    $pos == 0) {
                // Calc the colour for the list row
                if ($registre['verifica'] == 0) {
                    $bgcolor = 'lightgrey';
                } else {
                    $n++;
                    $pos = 0;
                    if ($n % 2 == 0) {
                        $pos = strpos($havist, '$' . $registre['nid'] . '$');
                        $bgcolor = ($pos == 0 && UserUtil::isLoggedIn() && $saved == 0) ? ModUtil::getVar('IWnoteboard', 'colornewrow1') : ModUtil::getVar('IWnoteboard', 'colorrow1');
                    } else {
                        $pos = strpos($havist, '$' . $registre['nid'] . '$');
                        $bgcolor = ($pos == 0 && UserUtil::isLoggedIn() && $saved == 0) ? ModUtil::getVar('IWnoteboard', 'colornewrow2') : ModUtil::getVar('IWnoteboard', 'colorrow2');
                    }
                }

                $acces_tema = false;

                //Check if user can see the thopic
                $isInArray = in_array($tema_anotacio['grup'], $permissions['grups']);
                if ($isInArray || $registre['potverificar'] || $tema_anotacio['grup'] == 0) {
                    $acces_tema = true;
                }

                $comentaris_array = array();

                // Get the comments associated to a note
                $comentaris = ModUtil::apiFunc('IWnoteboard', 'user', 'getallcomentaris',
                                array('ncid' => $registre['nid']));

                foreach ($comentaris as $comentari) {
                    if ($comentari['verifica'] == 0) {
                        $bgcolorcomentari = 'lightgrey';
                    } else {
                        $bgcolorcomentari = $bgcolor;
                    }
                    $usersList .= $comentari['informa'] . '$$';
                    $comentaris_array[] = array('nid' => $comentari['nid'],
                        'noticia' => DataUtil::formatForDisplayHTML($comentari['noticia']),
                        'verifica' => $comentari['verifica'],
                        'bgcolorcomentari' => $bgcolorcomentari,
                        'data' => date('d/m/y', $comentari['data']),
                        'hora' => date('H:i', $comentari['data']),
                        'usuari' => $usersFullname[$comentari['informa']],
                        'id_user_informa' => $comentari['informa'],
                        'id_user' => $uid);
                }

                $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
                $photo = ModUtil::func('IWmain', 'user', 'getUserPicture',
                                array('uname' => UserUtil::getVar('uname', $registre['informa']),
                                    'sv' => $sv));

                // The user can edit their notes
                $pot_editar = false;
                if (($permissions['nivell'] > 4 &&
                        $registre['informa'] == $uid &&
                        $registre['verifica'] == 1) ||
                        ($permissions['nivell'] == 7 &&
                        $registre['verifica'] == 1) &&
                        $registre['sharedFrom'] == '') {
                    $pot_editar = true;
                }

                // The user can delete his/her notes
                $pot_esborrar = false;
                if (($permissions['nivell'] > 5 &&
                        $registre['informa'] == $uid) ||
                        $permissions['nivell'] == 7 &&
                        $registre['sharedFrom'] == '') {
                    $pot_esborrar = true;
                }

                // Get file extension
                $fileExtension = strtolower(substr(strrchr($registre['fitxer'], "."), 1));

                // get file icon
                $ctypeArray = ModUtil::func('IWmain', 'user', 'getMimetype',
                                array('extension' => $fileExtension));
                $fileIcon = $ctypeArray['icon'];

                $edited = '';

                if ($registre['edited'] != '' &&
                        ModUtil::getVar('IWnoteboard', 'editPrintAfter') != '-1' &&
                        $registre['data'] + ModUtil::getVar('IWnoteboard', 'editPrintAfter') * 60 < $registre['edited']) {
                    $edited = date('d/m/y H:i', $registre['edited']);
                }

                $informa = ($registre['sharedFrom'] === '') ? $registre['informa'] : $sharedsArray[$registre['sharedFrom']][name];
                $usersList .= $registre['informa'] . '$$';
                if ($registre['sharedFrom'] != null)
                    $created_by = $sharedsArray[$registre['sharedFrom']]['name'];
                else
                    $created_by=$informa;

                $anotacions[] = array('nid' => $registre['nid'],
                    'bgcolor' => $bgcolor,
                    'data' => date('d/m/y H:i', $registre['data']),
                    'acces_tema' => $acces_tema,
                    'tema_anotacio' => $tema_anotacio['nomtema'],
                    'noticia' => DataUtil::formatForDisplayHTML($registre['noticia']),
                    'mes_info' => $registre['mes_info'],
                    'text' => $registre['text'],
                    'textfitxer' => $registre['textfitxer'],
                    'fitxer' => $registre['fitxer'],
                    'fileIcon' => $fileIcon,
                    'informa' => $informa,
                    'photo' => $photo,
                    'verifica' => $registre['verifica'],
                    'pot_editar' => $pot_editar,
                    'pot_esborrar' => $pot_esborrar,
                    'admet_comentaris' => $registre['admet_comentaris'],
                    'n_comentaris' => count($comentaris),
                    'comentaris' => $comentaris_array,
                    'marca' => $marca,
                    'edited' => $edited,
                    'created_by' => $created_by,
                    'created_by_url' => $sharedsArray[$registre['sharedFrom']]['base_url'],
                    'edited_by' => UserUtil::getVar('uname', $registre['edited_by']),
                    'public' => $registre['public']);
                $vistes .= '$' . $registre['nid'] . '$';
            }
        }

        if ($saved != 1 && UserUtil::isLoggedIn()) {
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');

            if ($tema == 0 && $nid == 0 && $marked == 0 && ModUtil::getVar('IWnoteboard', 'multiLanguage') == 0) {
                ModUtil::func('IWmain', 'user', 'userSetVar',
                                array('module' => 'IWnoteboard',
                                    'name' => 'viewed',
                                    'value' => $vistes,
                                    'sv' => $sv));
            } else {
                ModUtil::func('IWmain', 'user', 'userSetVar',
                                array('module' => 'IWnoteboard',
                                    'name' => 'viewed',
                                    'value' => $havist . $vistes,
                                    'sv' => $sv));
            }

            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            ModUtil::func('IWmain', 'user', 'userDelVar',
                            array('module' => 'IWmain_block_news',
                                'name' => 'news',
                                'sv' => $sv));
        }

        // Count the use of the module
        if (ModUtil::available('iw_visits') &&
                ModUtil::isHooked('iw_visits', 'IWnoteboard')) {
            // Insert the record
            ModUtil::apiFunc('iw_visits', 'user', 'visita');
        }

        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $users = ModUtil::func('IWmain', 'user', 'getAllUsersInfo',
                        array('info' => 'ccn',
                            'sv' => $sv,
                            'list' => $usersList));

        $view->assign('temes_MS', $temes_MS);
        $view->assign('users', $users);
        $view->assign('tema', $tema);
        $view->assign('saved', $saved);
        $view->assign('permisos', $permissions);
        $view->assign('saved', $saved);
        $view->assign('anotacions', $anotacions);
        $view->assign('loggedIn', UserUtil::isLoggedIn());
        $view->assign('notRegisteredSeeRedactors', ModUtil::getVar('IWnoteboard', 'notRegisteredSeeRedactors'));
        $view->assign('publicAllowed', ModUtil::getVar('IWnoteboard', 'public'));
        $view->assign('publicSharedURL', ModUtil::getVar('IWnoteboard', 'publicSharedURL'));
        $view->assign('showSharedURL', ModUtil::getVar('IWnoteboard', 'showSharedURL'));

        return $view->fetch('IWnoteboard_user_main.htm');
    }

    /**
     * Get a file from a server folder even it is out of the public html directory
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	name of the file that have to be gotten
     * @return:	The file information
     */
    public function getFile($args) {
        // File name with the path
        $fileName = FormUtil::getPassedValue('fileName', isset($args['fileName']) ? $args['fileName'] : 0, 'GET');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        return ModUtil::func('IWmain', 'user', 'getFile',
                array('fileName' => $fileName,
                    'sv' => $sv));
    }

    /**
     * Download a file
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	name of the file that have to be downloaded
     * @return:	The file required
     */
    public function download($args) {
        // Get the parameters
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'GET');
        $fileName = FormUtil::getPassedValue('fileName', isset($args['fileName']) ? $args['fileName'] : 0, 'GET');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Needed argument
        if (!isset($fileName) || !isset($nid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }

        $uid = UserUtil::getVar('uid');
        if ($uid == '') {
            $uid = '-1';
        }

        // Get the record information
        $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));
        if ($registre == false) {
            LogUtil::registerError($this->__('The note has not been found'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => $uid));

        //Check if user really can access to note and file
        $grups_acces = explode('$', $registre['destinataris']);
        $esta_en_grups_acces = array_intersect($grups_acces, $permissions['grups']);
        if ((($registre['verifica'] == 1 &&
                (count($esta_en_grups_acces) >= 1 ||
                $uid == $registre['informa'])) ||
                $permissions['potverificar']) &&
                $pos == 0) {
            // user can download the file
            $fileNameInServer = ModUtil::getVar('IWnoteboard', 'attached') . '/' . $fileName;
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            return ModUtil::func('IWmain', 'user', 'downloadFile',
                    array('fileName' => $fileName,
                        'fileNameInServer' => $fileNameInServer,
                        'sv' => $sv));
        } else {
            // user can't download the file because he/she hasn't access to the note
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }
    }

    /**
     * Show the form needed to create a new note
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the topic that is viewing the user and if the user is in the saved mode
     * @return:	The values input in the form
     */
    public function nova($args) {
        $m = FormUtil::getPassedValue('m', isset($args['m']) ? $args['m'] : null, 'REQUEST');
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'REQUEST');
        $saved = FormUtil::getPassedValue('saved', isset($args['saved']) ? $args['saved'] : null, 'REQUEST');
        $tema = FormUtil::getPassedValue('tema', isset($args['tema']) ? $args['tema'] : null, 'REQUEST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }
        $registre = array();
        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        if (isset($nid)) {
            // Get the record information
            $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                            array('nid' => $nid));
            if ($registre == false) {
                LogUtil::registerError($this->__('The note has not been found'));
                return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
            }
        } else $registre['informa'] = 0;

        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));
        if (!$permissions['potverificar']) {
            $saved = 0;
        }

        if (!$permissions['potverificar'] || ($m != 'c' && $m != 'v' && $m != 'e')) {
            if (empty($permissions) ||
                    $permissions['nivell'] < 3) {
                LogUtil::registerError($this->__('You are not allowed to do this action'));
                return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
            }

            if (empty($permissions) ||
                    ($permissions['nivell'] < 5 &&
                    $m == 'e') ||
                    ($permissions['nivell'] < 7 &&
                    $registre['informa'] != UserUtil::getVar('uid') && $m == 'e')) {
                LogUtil::registerError($this->__('You are not allowed to do this action'));
                return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
            }
        }

        switch ($m) {
            case 'v':
                if (!$permissions['potverificar']) {
                    LogUtil::registerError($this->__('You are not allowed to do this action'));
                    return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
                }
                $submit = $this->__('Validate');
                $titol = $this->__('Validate a note');
                $permissions['verifica'] = 1;
                break;
            case 'e':
                $titol = $this->__('Modify the configuration');
                $submit = $this->__('Modify');
                $permissions['verifica'] = 1;
                break;
            case 'n':
                $titol = $this->__('Add a note');
                $submit = $this->__('Send');
        }

        $temes = ModUtil::apiFunc('IWnoteboard', 'user', 'getalltemes');

        foreach ($temes as $tema1) {
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            if (ModUtil::func('IWmain', 'user', 'isMember',
                            array('uid' => UserUtil::getVar('uid'),
                                'gid' => $tema1['grup'],
                                'sv' => $sv)) ||
                    SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_ADMIN)) {
                $temes_MS[] = array('id' => $tema1['tid'],
                    'name' => $tema1['nomtema']);
            }
        }

        $data = date('d/m/y', time());

        $language = UserUtil::getLang();

        if (isset($nid)) {
            $noticia = $registre['noticia'];
            if ($saved != 1) {
                $data = date('d/m/y', $registre['data']);
                $caduca = $registre['caduca'];
                $titulin = $registre['titulin'];
                $titulout = $registre['titulout'];
            }
            $titular = $registre['titular'];
            $mes_info = $registre['mes_info'];
            $text = $registre['text'];
            $fitxer = $registre['fitxer'];
            $textfitxer = $registre['textfitxer'];
            $destinataris = $registre['destinataris'];
            $admet_comentaris = $registre['admet_comentaris'];
            $verifica = $registre['verifica'];
            $tid = $registre['tid'];
            $language = $registre['lang'];
            $public = $registre['public'];
            if ($m == 'c') {
                // update the record in the database
                $lid = ModUtil::apiFunc('IWnoteboard', 'user', 'update',
                                array('data' => $registre['data'],
                                    'nid' => $nid,
                                    'noticia' => $noticia,
                                    'caduca' => time(),
                                    'titular' => $titular,
                                    'titulin' => $titulin,
                                    'titulout' => $titulout,
                                    'mes_info' => $mes_info,
                                    'text' => $text,
                                    'fitxer' => $fitxer,
                                    'textfitxer' => $textfitxer,
                                    'destinataris' => $destinataris,
                                    'admet_comentaris' => $admet_comentaris,
                                    'verifica' => $verifica,
                                    'v' => $v,
                                    'tema' => $tema,
                                    'saved' => $saved,
                                    'm' => 'c',
                                    'tid' => $tid,
                                    'language' => $language,
                                    'public' => $public));

                if ($lid != false) {
                    //Uptated Successfully
                    //LogUtil::registerStatus ($this->__('Expiration of the note has been forced. Now the note is accessible from the link <strong>Show the stored notes (expired)</strong>'));
                }
                return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main',
                                array('tema' => $tema)));
            }
        }

        if ($registre['informa'] != UserUtil::getVar('uid') &&
                $permissions == 5 &&
                $m == 'e') {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        if ($caduca == '') {
            $caduca = time() + ModUtil::getVar('IWnoteboard', 'caducitat') * 24 * 60 * 60;
        }
        if ($titulin == '') {
            $titulin = time();
        }
        if ($titulout == '') {
            $titulout = time() + 5 * 24 * 60 * 60;
        }
        if ($mes_info == '') {
            $mes_info = 'http://';
        }
        if ($text == '') {
            $text = $this->__('More information');
        }
        $extensions = ModUtil::getVar('IWmain', 'extensions');
        if ($textfitxer == '') {
            $textfitxer = $this->__('More information');
        }

        if ($permissions['nivell'] > 3 || $permissions['potverificar']) {
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            $groups = ModUtil::func('IWmain', 'user', 'getAllGroups',
                            array('sv' => $sv,
                                'plus' => $this->__('Unregistered')));
            $grupsModVar = ModUtil::getVar('IWnoteboard', 'grups');
            $marcatModVar = ModUtil::getVar('IWnoteboard', 'marcat');
            foreach ($groups as $group) {
                if (strpos($grupsModVar, '$' . $group['id'] . '$') != 0) {
                    if (empty($destinataris)) {
                        $select = (strpos($marcatModVar, '$' . $group['id'] . '$') != 0) ? 'checked' : '';
                    } else {
                        $select = (strpos($destinataris, '$' . $group['id'] . '$') != 0) ? 'checked' : '';
                    }
                    $grups_array[] = array('name' => $group['name'],
                        'id' => $group['id'],
                        'select' => $select);
                }
            }
            $tria = true;
        } else {
            $tria = false;
        }
        $view->assign('temes_MS', $temes_MS);
        $view->assign('verifica', $permissions['verifica']);
        $view->assign('data', $data);
        $view->assign('caduca', date('d/m/y', $caduca));
        $view->assign('titulin', date('d/m/y', $titulin));
        $view->assign('titulout', date('d/m/y', $titulout));
        $view->assign('mes_info', $mes_info);
        $view->assign('text', $text);
        $view->assign('extensions', $extensions);
        $view->assign('textfitxer', $textfitxer);
        $view->assign('m', $m);
        $view->assign('titular', $titular);
        $view->assign('noticia', $noticia);
        $view->assign('admet_comentaris', $admet_comentaris);
        $view->assign('fitxer', $fitxer);
        $view->assign('titol', $titol);
        $view->assign('submit', $submit);
        $view->assign('nid', $nid);
        $view->assign('tema', $tema);
        $view->assign('tid', $tid);
        $view->assign('grups_array', $grups_array);
        $view->assign('tria', $tria);
        $view->assign('saved', $saved);
        $view->assign('multiLanguage', ModUtil::getVar('IWnoteboard', 'multiLanguage'));
        $view->assign('language', $language);
        $view->assign('publicAllowed', ModUtil::getVar('IWnoteboard', 'public'));
        $view->assign('public', $public);

        return $view->fetch('IWnoteboard_user_nova.htm');
    }

    /**
     * Create the RSS content with all the notes of a noteboard
     * @author: Sara Arjona Tï¿œllez (sarjona@xtec.cat)
     * @param:	args
     * @return:	The XML with the context of the noteboard
     */
    public function rss($args) {
        // Get the parameters
        $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'GET');
        $request_groups = FormUtil::getPassedValue('g', isset($args['g']) ? $args['g'] : null, 'GET');

        // Security check
        if (ModUtil::getVar('IWnoteboard', 'publicSharedURL') != $sid) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // get user identity
        $uid = UserUtil::getVar('uid');
        if ($uid == '') {
            $uid = '-1';
        }

        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => $uid));

        // Security recipients groups check
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $allgroups = ModUtil::func('IWmain', 'user', 'getAllGroups',
                        array('sv' => $sv,
                            'plus' => $this->__('Unregistered')));

        // Get all the notes
        $registres = ModUtil::apiFunc('IWnoteboard', 'user', 'getall',
                        array('tema' => $tema,
                            'nid' => $nid,
                            'public' => 1));
        foreach ($registres as $registre) {
            //Check if user can see the topic
            $note_groups = explode('$', substr($registre['destinataris'], 2, -1));

            array_walk($note_groups, 'groupidtonamemd5', $allgroups);

            $group_intersect = array_uintersect($request_groups, $note_groups, "strcasecmp");
            if (sizeof($group_intersect) > 0) {
                $edited = '';
                if ($registre['edited'] != '') {
                    $edited = date('d/m/y H:i', $registre['edited']);
                }
                $registreArray[] = array('nid' => $registre['nid'],
                    'titular' => $registre['titular'],
                    'titulin' => $registre['titulin'],
                    'titulout' => $registre['titulout'],
                    'caduca' => $registre['caduca'],
                    'data' => time(),
                    'noticia' => DataUtil::formatForDisplayHTML($registre['noticia']),
                    'mes_info' => $registre['mes_info'],
                    'text' => $registre['text'],
                    'textfitxer' => $registre['textfitxer'],
                    'fitxer' => $registre['fitxer'],
                    'edited' => $registre['edited'],
                    'language' => $registre['language'],
                    'literalGroups' => $registre['literalGroups']);
            }
        }

        //Gather relevent info about file
        $ctype = "text/xml";
        //Begin writing headers
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");

        //Use the switch-generated Content-Type
        header("Content-Type: $ctype");
        //Force the download
        $xml = '<?xml version="1.0" encoding="ISO-8859-15"?>';
        $xml .= '<rss version="2.0">';
        $xml .= '<channel>';
        $xml .= "<title>" . System::getVar('sitename') . "</title>";
        $xml .= "<link><![CDATA[" . System::getBaseUrl() . "?module=IWnoteboard&amp;func=rss&amp;sid=$sid]]></link>";
        $xml .= "<description>Notes compartides del tauler</description>";
        $xml .= "<language>ca</language>";
//	$xml .= "<docs>http://ca.wikipedia.org/wiki/RSS</docs>";
        foreach ($registreArray as $registre) {
            $xml .= "<item>";
            $xml .= "<title><![CDATA[" . $registre['titular'] . "]]></title>";
            $xml .= "<description><![CDATA[" . $registre['noticia'] . "]]></description>";
            $xml .= "<pubDate>" . $registre['edited'] . "</pubDate>";
            if ($registre['mes_info'] != 'http://' && $registre['mes_info'] != '')
                $xml .= "<link>" . $registre['mes_info'] . "</link>";
            $xml .= "<author>" . $registre['informa'] . "</author>";
            $xml .= "<category>" . $registre['tema_anotacio'] . "</category>";
            //Afegit
            $xml .= "<shared_id>" . $registre['nid'] . "</shared_id>";
            $xml .= "<data>" . $registre['data'] . "</data>";
            $xml .= "<caduca>" . $registre['caduca'] . "</caduca>";
//			$xml .= "<noticia><![CDATA[".$registre['noticia']."]]></noticia>";
            $xml .= "<mes_info>" . $registre['mes_info'] . "</mes_info>";
            $xml .= "<text>" . $registre['text'] . "</text>";
            $xml .= "<caduca>" . $registre['caduca'] . "</caduca>";
//			$xml .= "<titular><![CDATA[".$registre['titular']."]]></titular>";
            $xml .= "<titulin>" . $registre['titulin'] . "</titulin>";
            $xml .= "<titulout>" . $registre['titulout'] . "</titulout>";
            $xml .= "<fitxer>" . $registre['fitxer'] . "</fitxer>";
            $xml .= "<textfitxer>" . $registre['textfitxer'] . "</textfitxer>";
            $xml .= "<language>" . $registre['language'] . "</language>";
            $xml .= "<edited>" . $registre['edited'] . "</edited>";
            $xml .= "<shared_from>" . $sid . "</shared_from>";
            $xml .= "<literal_groups><![CDATA[" . $registre['literalGroups'] . "]]></literal_groups>";
            //final
            $xml .= "</item>";
        }

        $xml .= "</channel>";
        $xml .= "</rss>";
        echo $xml;
        exit;
    }

    /**
     * Receive the information from the form and create a new entry in the database
     * @author:     Albert Pï¿œrez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the values sended from the form
     * @return:	Thue if success
     */
    public function crear($args) {
        // Get parameters from whatever input we need
        $noticia = FormUtil::getPassedValue('noticia', isset($args['noticia']) ? $args['noticia'] : null, 'POST');
        $data = FormUtil::getPassedValue('data', isset($args['data']) ? $args['data'] : null, 'POST');
        $caduca = FormUtil::getPassedValue('caduca', isset($args['caduca']) ? $args['caduca'] : null, 'POST');
        $titular = FormUtil::getPassedValue('titular', isset($args['titular']) ? $args['titular'] : null, 'POST');
        $titulin = FormUtil::getPassedValue('titulin', isset($args['titulin']) ? $args['titulin'] : null, 'POST');
        $titulout = FormUtil::getPassedValue('titulout', isset($args['titulout']) ? $args['titulout'] : null, 'POST');
        $mes_info = FormUtil::getPassedValue('mes_info', isset($args['mes_info']) ? $args['mes_info'] : null, 'POST');
        $text = FormUtil::getPassedValue('text', isset($args['text']) ? $args['text'] : null, 'POST');
        $fitxer = FormUtil::getPassedValue('fitxer', isset($args['fitxer']) ? $args['fitxer'] : null, 'POST');
        $textfitxer = FormUtil::getPassedValue('textfitxer', isset($args['textfitxer']) ? $args['textfitxer'] : null, 'POST');
        $destinataris = FormUtil::getPassedValue('destinataris', isset($args['destinataris']) ? $args['destinataris'] : null, 'POST');
        $admet_comentaris = FormUtil::getPassedValue('admet_comentaris', isset($args['admet_comentaris']) ? $args['admet_comentaris'] : null, 'POST');
        $verifica = FormUtil::getPassedValue('verifica', isset($args['verifica']) ? $args['verifica'] : null, 'POST');
        $tema = FormUtil::getPassedValue('tema', isset($args['tema']) ? $args['tema'] : null, 'POST');
        $tid = FormUtil::getPassedValue('tid', isset($args['tid']) ? $args['tid'] : null, 'POST');
        $language = FormUtil::getPassedValue('language', isset($args['language']) ? $args['language'] : null, 'POST');
        $public = FormUtil::getPassedValue('public', isset($args['public']) ? $args['public'] : null, 'POST');

        //gets the attached file array
        $fileName = $_FILES['fitxer']['name'];

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));

        // Separate the groups with the dolar symbol
        $toUsers = $destinataris;
        if (!empty($destinataris)) {
            $desti1 = '$$';
            for ($i = 0; $i < 100; $i++) {
                if (isset($destinataris[$i])) {
                    $desti1 .= $destinataris[$i] . '$';
                }
            }
            $destinataris = $desti1;
            if ($public == 1) {
                // Get current user groups
                $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
                $allGroupsInfo = ModUtil::func('IWmain', 'user', 'getAllGroupsInfo',
                                array('sv' => $sv));
                $desti1 = '$$';
                foreach ($toUsers as $dest) {
                    $desti1 .= ( $dest != 0) ? $allGroupsInfo[$dest] . '$' : '0$';
                }
                $literalGroups = $desti1;
            } else {
                $literalGroups = '';
            }
        }

        // If user can select the groups that are going to receive the note
        if ($permissions['nivell'] < 4 &&
                ModUtil::getVar('IWnoteboard', 'repperdefecte') == 1) {
            $destinataris = ModUtil::getVar('IWnoteboard', 'marcat');
        }

        if ($permissions['nivell'] < 4 &&
                ModUtil::getVar('IWnoteboard', 'repperdefecte') == 0) {
            $destinataris = ModUtil::getVar('IWnoteboard', 'grups');
        }

        if ($permissions['nivell'] < 4 &&
                ModUtil::getVar('IWnoteboard', 'repperdefecte') == 2) {
            $destinataris = '$$';
        }

        $dataerror = false;
        // check the date values
        if (!empty($caduca)) {
            $dia = substr($caduca, 0, 2);
            $mes = substr($caduca, 3, 2);
            $any = '20' . substr($caduca, -2);
            $caduca = mktime('23', '59', '00', $mes, $dia, $any);
        }

        if (!empty($titulin)) {
            $dia = substr($titulin, 0, 2);
            $mes = substr($titulin, 3, 2);
            $any = '20' . substr($titulin, -2);
            $titulin = mktime('00', '00', '00', $mes, $dia, $any);
        }

        if (!empty($titulout)) {
            $dia = substr($titulout, 0, 2);
            $mes = substr($titulout, 3, 2);
            $any = '20' . substr($titulout, -2);
            $titulout = mktime('23', '59', '00', $mes, $dia, $any);
        }

        // check the needed values
        $nom_fitxer = (empty($fileName)) ? '' : $fileName;

        // update the attached file to the server
        if ($fileName != '') {
            $folder = ModUtil::getVar('IWnoteboard', 'attached');
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            $update = ModUtil::func('IWmain', 'user', 'updateFile',
                            array('sv' => $sv,
                                'folder' => $folder,
                                'file' => $_FILES['fitxer']));
            //the function returns the error string if the update fails and and empty string if success
            if ($update['msg'] != '') {
                LogUtil::registerError($update['msg'] . ' ' . $this->__('Probably the note have been sent without the attached file'));
                $nom_fitxer = '';
            } else {
                $nom_fitxer = $update['fileName'];
            }
        }

        // create a new record
        $lid = ModUtil::apiFunc('IWnoteboard', 'user', 'crear',
                        array('noticia' => $noticia,
                            'data' => $data,
                            'caduca' => $caduca,
                            'titular' => $titular,
                            'titulin' => $titulin,
                            'titulout' => $titulout,
                            'mes_info' => $mes_info,
                            'text' => $text,
                            'fitxer' => $nom_fitxer,
                            'textfitxer' => $textfitxer,
                            'destinataris' => $destinataris,
                            'admet_comentaris' => $admet_comentaris,
                            'verifica' => $verifica,
                            'tid' => $tid,
                            'language' => $language,
                            'public' => $public,
                            'literalGroups' => $literalGroups));

        if (!$lid) {
            // error
            return LogUtil::registerError($this->__('The note has been created successfully'));
        }

        // Creation successfully. Inform to user in case the note needs verification
        if ($permissions['verifica'] == 1) {
            LogUtil::registerStatus($this->__('The note has been created successfully'));
        } else {
            LogUtil::registerStatus($this->__('The note has been sent successfully, but is waiting for administrator\'s validation.'));
        }

        //Delete users headlines var. This renoval the block information
        if ($titular != '') {
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule',
                            array('name' => 'nbheadlines',
                                'module' => 'IWnoteboard',
                                'sv' => $sv));
        }

        // redirect user to noteboard main page
        return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main',
                        array('tema' => $tema)));
    }

    /**
     * Receive the information from the form and update a entry in the database
     * @author:     Albert Pï¿œrez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the values sended from the form
     * @return:	Thue if success
     */
    public function update($args) {
        // Get parameters from whatever input we need
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        $noticia = FormUtil::getPassedValue('noticia', isset($args['noticia']) ? $args['noticia'] : null, 'POST');
        $data = FormUtil::getPassedValue('data', isset($args['data']) ? $args['data'] : null, 'POST');
        $caduca = FormUtil::getPassedValue('caduca', isset($args['caduca']) ? $args['caduca'] : null, 'POST');
        $titular = FormUtil::getPassedValue('titular', isset($args['titular']) ? $args['titular'] : null, 'POST');
        $titulin = FormUtil::getPassedValue('titulin', isset($args['titulin']) ? $args['titulin'] : null, 'POST');
        $titulout = FormUtil::getPassedValue('titulout', isset($args['titulout']) ? $args['titulout'] : null, 'POST');
        $mes_info = FormUtil::getPassedValue('mes_info', isset($args['mes_info']) ? $args['mes_info'] : null, 'POST');
        $text = FormUtil::getPassedValue('text', isset($args['text']) ? $args['text'] : null, 'POST');
        $fitxer = FormUtil::getPassedValue('fitxer', isset($args['fitxer']) ? $args['fitxer'] : null, 'POST');
        $textfitxer = FormUtil::getPassedValue('textfitxer', isset($args['textfitxer']) ? $args['textfitxer'] : null, 'POST');
        $destinataris = FormUtil::getPassedValue('destinataris', isset($args['destinataris']) ? $args['destinataris'] : null, 'POST');
        $admet_comentaris = FormUtil::getPassedValue('admet_comentaris', isset($args['admet_comentaris']) ? $args['admet_comentaris'] : null, 'POST');
        $verifica = FormUtil::getPassedValue('verifica', isset($args['verifica']) ? $args['verifica'] : null, 'POST');
        $tema = FormUtil::getPassedValue('tema', isset($args['tema']) ? $args['tema'] : null, 'POST');
        $tid = FormUtil::getPassedValue('tid', isset($args['tid']) ? $args['tid'] : null, 'POST');
        $v = FormUtil::getPassedValue('v', isset($args['v']) ? $args['v'] : null, 'POST');
        $saved = FormUtil::getPassedValue('saved', isset($args['saved']) ? $args['saved'] : null, 'POST');
        $modremitent = FormUtil::getPassedValue('modremitent', isset($args['modremitent']) ? $args['modremitent'] : null, 'POST');
        $segur = FormUtil::getPassedValue('segur', isset($args['segur']) ? $args['segur'] : null, 'POST');
        $new_file = FormUtil::getPassedValue('new_file', isset($args['new_file']) ? $args['new_file'] : null, 'POST');
        $language = FormUtil::getPassedValue('language', isset($args['language']) ? $args['language'] : null, 'POST');
        $public = FormUtil::getPassedValue('public', isset($args['public']) ? $args['public'] : null, 'POST');

        //get the file name
        $fileName = $_FILES['fitxer']['name'];

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));

        // Get the record information
        $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));
        if ($registre == false) {
            LogUtil::registerError($this->__('The note has not been found'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        if (!$permissions['potverificar']) {
            if (empty($permissions) ||
                    ($permissions['nivell'] < 5 &&
                    $m == 'e') ||
                    ($permissions['nivell'] < 7 &&
                    $registre['informa'] != UserUtil::getVar('uid'))) {
                LogUtil::registerError($this->__('You are not allowed to do this action'));
                return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
            }
        }

        $toUsers = $destinataris;
        // Separate the groups with the dolar symbol
        if (!empty($destinataris)) {
            $desti1 = '$$';
            foreach ($destinataris as $dest) {
                $desti1 .= $dest . '$';
            }
            $destinataris = $desti1;

            // Separate the groups with the dolar symbol
            if ($public == 1) {
                // Get current user groups
                $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
                $allGroupsInfo = ModUtil::func('IWmain', 'user', 'getAllGroupsInfo',
                                array('sv' => $sv));
                $desti1 = '$$';
                foreach ($toUsers as $dest) {
                    $desti1 .= ( $dest != 0) ? $allGroupsInfo[$dest] . '$' : '0$';
                }
                $literalGroups = $desti1;
            } else {
                $literalGroups = '';
            }
        }

        if (!$permissions['potverificar']) {
            // if user can select the groups that are going to see the note
            if ($permissions['nivell'] < 4 &&
                    ModUtil::getVar('IWnoteboard', 'repperdefecte') == 1) {
                $destinataris = ModUtil::getVar('IWnoteboard', 'marcat');
            }

            if ($permissions['nivell'] < 4 &&
                    ModUtil::getVar('IWnoteboard', 'repperdefecte') == 0) {
                $destinataris = ModUtil::getVar('IWnoteboard', 'grups');
            }

            if ($permissions['nivell'] < 4 &&
                    ModUtil::getVar('IWnoteboard', 'repperdefecte') == 2) {
                $destinataris = '$$';
            }
        }

        // check the date values
        if (!empty($data)) {
            $dia = substr($data, 0, 2);
            $mes = substr($data, 3, 2);
            $any = '20' . substr($data, -2);
            $data = mktime('00', '00', '00', $mes, $dia, $any);
        }

        if (!empty($caduca)) {
            $dia = substr($caduca, 0, 2);
            $mes = substr($caduca, 3, 2);
            $any = '20' . substr($caduca, -2);
            $caduca = mktime('00', '00', '00', $mes, $dia, $any);
        }

        if (!empty($titulin)) {
            $dia = substr($titulin, 0, 2);
            $mes = substr($titulin, 3, 2);
            $any = '20' . substr($titulin, -2);
            $titulin = mktime('00', '00', '00', $mes, $dia, $any);
        }

        if (!empty($titulout)) {
            $dia = substr($titulout, 0, 2);
            $mes = substr($titulout, 3, 2);
            $any = '20' . substr($titulout, -2);
            $titulout = mktime('00', '00', '00', $mes, $dia, $any);
        }

        if ($segur == 1) {
            $folder = ModUtil::getVar('IWnoteboard', 'attached');
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            $delete = ModUtil::func('IWmain', 'user', 'deleteFile',
                            array('sv' => $sv,
                                'folder' => $folder,
                                'fileName' => $registre['fitxer']));
            if ($delete) {
                $fitxer = '';
            }
        }

        //If there is attached file updates it
        if ($fileName != '' && $new_file == '1') {
            $folder = ModUtil::getVar('IWnoteboard', 'attached');
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            $update = ModUtil::func('IWmain', 'user', 'updateFile',
                            array('sv' => $sv,
                                'folder' => $folder,
                                'file' => $_FILES['fitxer']));
            //the function returns the error string if the update fails and and empty string if success
            if ($update['msg'] != '') {
                LogUtil::registerError($update['msg'] . ' ' . $this->__('Probably the note have been sent without the attached file'));
            }
            $fileName = $update['fileName'];
        }

        if ($fileName == '' && $fitxer != '') {
            $fileName = $fitxer;
        }

        // Update a note
        $lid = ModUtil::apiFunc('IWnoteboard', 'user', 'update',
                        array('data' => $data,
                            'nid' => $nid,
                            'noticia' => $noticia,
                            'caduca' => $caduca,
                            'titular' => $titular,
                            'titulin' => $titulin,
                            'titulout' => $titulout,
                            'mes_info' => $mes_info,
                            'text' => $text,
                            'fitxer' => $fileName,
                            'textfitxer' => $textfitxer,
                            'destinataris' => $destinataris,
                            'admet_comentaris' => $admet_comentaris,
                            'verifica' => $verifica,
                            'v' => $v,
                            'tid' => $tid,
                            'saved' => $saved,
                            'modremitent' => $modremitent,
                            'language' => $language,
                            'public' => $public,
                            'literalGroups' => $literalGroups));

        if ($lid != false) {
            //The note has been modified
            LogUtil::registerStatus($this->__('The note has been modified'));
            //Delete users headlines var. This renoval the block information
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            ModUtil::apiFunc('IWmain', 'user', 'usersVarsDelModule',
                            array('name' => 'nbheadlines',
                                'module' => 'IWnoteboard',
                                'sv' => $sv));
        }

        // Redirect user to main page
        return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main',
                        array('tema' => $tema,
                            'saved' => $saved)));
    }

    /**
     * Show a form that allow to create a comment associate with a note
     * @author:     Albert Pï¿œrez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the id of the note where a comment is going to be created
     * @return:	The form
     */
    public function comenta($args) {
        // Get parameters from whatever input we need
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'GET');
        $objectid = FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'GET');
        if (!empty($objectid)) {
            $nid = $objectid;
        }

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        // get a note informtion
        $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));
        if ($registre == false) {
            LogUtil::registerError($this->__('The note has not been found'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));
        if (empty($permissions) ||
                $permissions['nivell'] < 2) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        $security = SecurityUtil::generateAuthKey();

        $view->assign('security', $security);
        $view->assign('nid', $nid);
        $view->assign('textnota', DataUtil::formatForDisplayHTML($registre['noticia']));
        $view->assign('titol', $this->__('Send the comment'));
        $view->assign('submit', $this->__('Send the comment'));
        $view->assign('m', 'n');
        $view->assign('verifica', $permissions['verifica']);

        return $view->fetch('IWnoteboard_user_comenta.htm');
    }

    /**
     * Receive the information from the form and create a new comment for a note
     * @author:     Albert Pï¿œrez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the values sended from the form
     * @return:	Thue if success
     */
    public function comenta_crear($args) {
        // Get parameters from whatever input we need
        $noticia = FormUtil::getPassedValue('noticia', isset($args['noticia']) ? $args['noticia'] : null, 'POST');
        $data = FormUtil::getPassedValue('data', isset($args['data']) ? $args['data'] : null, 'POST');
        $verifica = FormUtil::getPassedValue('verifica', isset($args['verifica']) ? $args['verifica'] : null, 'POST');
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));
        if (empty($permissions) ||
                $permissions['nivell'] < 2) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        // create a comment
        $lid = ModUtil::apiFunc('IWnoteboard', 'user', 'crear',
                        array('noticia' => $noticia,
                            'data' => time(),
                            'verifica' => $verifica,
                            'ncid' => $nid));

        if ($lid != false) {
            // creation succesfully
            if ($permissions['verifica'] == 1) {
                LogUtil::registerStatus($this->__('A new comment has been created'));
            } else {
                LogUtil::registerStatus($this->__('The comment has been sent successfully, but is waiting for administrator\'s validation.'));
            }
        }

        return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
    }

    /**
     * Show a form needed to modify a comment
     * @author:     Albert Pï¿œrez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the id of the comment
     * @return:	The form
     */
    public function editacomentari($args) {
        // Get parameters from whatever input we need
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'GET');
        $v = FormUtil::getPassedValue('v', isset($args['v']) ? $args['v'] : null, 'GET');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        // get comment information
        $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));
        if ($registre == false) {
            LogUtil::registerError($this->__('The note has not been found'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        // get note information
        $note = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $registre['ncid']));
        if ($note == false) {
            LogUtil::registerError($this->__('The note has not been found'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }


        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));

        if (empty($permissions) ||
                $permissions['nivell'] < 5 ||
                ($permissions['nivell'] < 7 &&
                $registre['informa'] != UserUtil::getVar('uid'))) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        if (isset($v) &&
                $v != '') {
            if (!$permissions['potverificar']) {
                LogUtil::registerError($this->__('You are not allowed to do this action'));
                return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
            }
            $submit = $this->__('Validate');
            $titol = $this->__('Comment validation');
        } else {
            $titol = $this->__('Modify a comment');
            $submit = $this->__('Modify');
        }

        $verifica = 1;

        if ($registre['informa'] != UserUtil::getVar('uid') &&
                $permissions == 5) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        $security = SecurityUtil::generateAuthKey();

        $view->assign('security', $security);
        $view->assign('titol', $titol);
        $view->assign('submit', $submit);
        $view->assign('m', 'e');
        $view->assign('textnota', DataUtil::formatForDisplayHTML($note['noticia']));
        $view->assign('noticia', $registre['noticia']);
        $view->assign('nid', $nid);
        $view->assign('verifica', $permissions['verifica']);

        return $view->fetch('IWnoteboard_user_comenta.htm');
    }

    /**
     * Receive the information from the form and modify the comment
     * @author:     Albert Pï¿œrez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the values sended from the form
     * @return:	Thue if success
     */
    public function updatecomentari($args) {
        // Get parameters from whatever input we need
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        $v = FormUtil::getPassedValue('v', isset($args['v']) ? $args['v'] : null, 'POST');
        $noticia = FormUtil::getPassedValue('noticia', isset($args['noticia']) ? $args['noticia'] : null, 'POST');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        // Confirm authorisation code
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        // get a note information
        $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));

        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));
        if (empty($permissions) ||
                $permissions['nivell'] < 5 ||
                ($permissions['nivell'] < 7 &&
                $registre['informa'] != UserUtil::getVar('uid'))) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }


        // get the comment information
        $lid = ModUtil::apiFunc('IWnoteboard', 'user', 'updatecomentari',
                        array('nid' => $nid,
                            'noticia' => $noticia,
                            'verifica' => 1));

        if ($lid != false) {
            // Update successfully
            LogUtil::registerStatus($this->__('The comment has been modified'));
        }

        return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
    }

    /**
     * Show the list of users that have seen a note
     * @author:     Albert Pï¿œrez Monfort (aperezm@xtec.cat)
     * @param:	args   Array with the id of the note and the topic that is been viewed
     * @return:	The list of users that have seen a note
     */
    public function hanvist($args) {
        // Get parameters from whatever input we need
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'GET');
        $tema = FormUtil::getPassedValue('tema', isset($args['tema']) ? $args['tema'] : null, 'GET');

        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        if (!UserUtil::isLoggedIn()) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }

        // Create output object
        $view = Zikula_View::getInstance('IWnoteboard', false);

        $registres = ModUtil::apiFunc('IWnoteboard', 'user', 'hanvist',
                        array('nid' => $nid));

        $view->assign('registres', $registres);
        $view->assign('tema', $tema);

        return $view->fetch('IWnoteboard_user_hanvist.htm');
    }

}

/**
 * Convert the items of specified array to md5 (it's used with array_walk function)
 * @author: Sara Arjona Tï¿œllez (sarjona@xtec.cat)
 * @param:	value the identifier of the group
 * @param:	key
 * @return:	The array after apply md5 function to the name of the group
 */
function groupidtonamemd5(&$value, $key, $allgroups) {
    if ($value != '')
        $value = md5($allgroups[$value]['name']);
}