<?php

class IWnoteboard_Api_User extends Zikula_Api {
    /**
     * Gets all the notes created in the noteboard
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	$tema: it is used for filter the notes of a topyc
     * 			$nid: if only a note is showed
     * @return:	And array with the items information
     */
    public function getall($args) {
        $tema = FormUtil::getPassedValue('tema', isset($args['tema']) ? $args['tema'] : null, 'POST');
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        $marked = FormUtil::getPassedValue('marked', isset($args['marked']) ? $args['marked'] : null, 'POST');
        $public = FormUtil::getPassedValue('public', isset($args['public']) ? $args['public'] : null, 'POST');
        $sv = FormUtil::getPassedValue('sv', isset($args['sv']) ? $args['sv'] : null, 'POST');
        if (!ModUtil::func('IWmain', 'user', 'checkSecurityValue', array('sv' => $sv))) {
            // Security check
            if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
                return LogUtil::registerPermissionError();
            }
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        if (!isset($nid)) {
            $time = time();
            if ($tema > 0) {
                $where = "$c[caduca] > $time AND $c[tid]=$tema";
            } elseif ($tema == '-1') {
                $where = "$c[caduca] > $time AND $c[tid]=0";
            } else {
                $where = "$c[caduca] > $time";
            }
            $orderby = "$c[edited] desc, $c[data] desc, $c[nid] desc";
        } else {
            $registre = ModUtil::apiFunc('IWnoteboard', 'user', 'get', array('nid' => $nid));
            if ($registre == false) {
                LogUtil::registerError($this->__('The note has not been found'));
                return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
            }
            $where = "$c[nid]=$nid";
            $orderby = '';
        }
        if (isset($marked) && $marked == 1) {
            $where .= " AND $c[marca] like '%$" . UserUtil::getVar('uid') . "$%'";
        }
        if (isset($public) && $public == 1) {
            $where .= " AND $c[public] = 1";
        }
        if (ModUtil::getVar('IWnoteboard', 'multiLanguage') == 1) {
            $where .= " AND ($c[lang]='" . UserUtil::getLang() . "' OR $c[lang] = '')";
        }
        // get the objects from the db
        $items = DBUtil::selectObjectArray('IWnoteboard', $where, $orderby, '-1', '-1', 'nid');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the items
        return $items;
    }

    /**
     * Gets all the topics defined in the noteboard
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @return:	And array with the items information
     */
    public function getalltemes() {
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_topics_column'];
        $orderby = "$c[nomtema]";
        // get the objects from the db
        $items = DBUtil::selectObjectArray('IWnoteboard_topics', '', $orderby, '-1', '-1', 'tid');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the items
        return $items;
    }

    /**
     * Create a new note into database
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	array with the note information
     * @return:	identity of the new record created or false if error
     */
    public function crear($args) {
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        extract($args);
        // Needed argument
        if (!isset($noticia)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Optional argument
        if (!isset($titular)) {
            $titular = '';
        }
        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));
        if (empty($permissions) ||
                $permissions['nivell'] < 3) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }
        $informa = ($sharedFrom == '') ? UserUtil::getVar('uid') : 0;
        $item = array('noticia' => $noticia,
            'data' => time(),
            'edited' => time(),
            'caduca' => $caduca,
            'titular' => $titular,
            'titulin' => $titulin,
            'titulout' => $titulout,
            'mes_info' => $mes_info,
            'text' => $text,
            'fitxer' => $fitxer,
            'textfitxer' => $textfitxer,
            'destinataris' => $destinataris,
            'admet_comentaris' => $admet_comentaris,
            'tid' => $tid,
            'informa' => $informa,
            'verifica' => $verifica,
            'no_mostrar' => '$',
            'marca' => '$',
            'ncid' => $ncid,
            'lang' => $language,
            'public' => $public,
            'literalGroups' => $literalGroups,
            'sharedFrom' => $sharedFrom,
            'sharedId' => $sharedId);
        if (!DBUtil::insertObject($item, 'IWnoteboard', 'nid')) {
            return LogUtil::registerError($this->__('Error! Creation attempt failed.'));
        }
        // Let any hooks know that we have created a new item.
        ModUtil::callHooks('item', 'create', $item['nid'],
                        array('module' => 'IWnoteboard'));
        // Return the id of the newly created item to the calling process
        return $item['nid'];
    }

    /**
     * Gets the informacion of a note
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note
     * @return:	An array with the note information
     */
    public function get($args) {
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        // Needed argument
        if (!isset($nid) || !is_numeric($nid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        $items = DBUtil::selectObjectByID('IWnoteboard', $nid, 'nid');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the items
        return $items;
    }

    /**
     * Delete a note from the database
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note
     * @return:	true if success and false if fails
     */
    public function delete($args) {
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        // Argument check
        if (!isset($nid) || !is_numeric($nid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Get the item
        $item = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));
        if (!$item) {
            return LogUtil::registerError($this->__('No such item found.'));
        }
        // check that user really can delete this the note
        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));
        if ($permissions['nivell'] < 6) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }
        if ($item['informa'] != UserUtil::getVar('uid') &&
                $permissions['nivell'] < 7) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }
        if (!DBUtil::deleteObjectByID('IWnoteboard', $nid, 'nid')) {
            return LogUtil::registerError($this->__('Error! Sorry! Deletion attempt failed.'));
        }
        // Let any hooks know that we have deleted an item
        ModUtil::callHooks('item', 'delete', $args['nid'],
                        array('module' => 'IWnoteboard'));
        // The item has been deleted, so we clear all cached pages of this item.
        $view = Zikula_View::getInstance('IWnoteboard');
        $view->clear_cache(null, $nid);
        return true;
    }

    /**
     * Update a note from the database
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note
     * 			saved in case the note was expired
     * @return:	true if success and false if fails
     */
    public function update($args) {
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        extract($args);
        // Needed argument
        if (!isset($noticia) || !isset($nid) || !is_numeric($nid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Get the item
        $item = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));
        if (!$item) {
            return LogUtil::registerError($this->__('No such item found.'));
        }
        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos',
                        array('uid' => UserUtil::getVar('uid')));
        if (!$permissions['potverificar']) {
            if (empty($permissions) ||
                    $permissions['nivell'] < 5 ||
                    ($permissions['nivell'] < 7 &&
                    $item['informa'] != UserUtil::getVar('uid'))) {
                LogUtil::registerError($this->__('You are not allowed to do this action'));
                return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
            }
        }
        $item = array('noticia' => $noticia,
            'caduca' => $caduca,
            'titular' => $titular,
            'titulin' => $titulin,
            'titulout' => $titulout,
            'mes_info' => $mes_info,
            'text' => $text,
            'fitxer' => $fitxer,
            'textfitxer' => $textfitxer,
            'destinataris' => $destinataris,
            'edited' => time(),
            'edited_by' => UserUtil::getVar('uid'),
            'admet_comentaris' => $admet_comentaris,
            'tid' => $tid,
            'verifica' => $verifica,
            'lang' => $language,
            'public' => $public,
            'literalGroups' => $literalGroups);
        if (isset($saved) && $saved == 1) {
            if ($modremitent == 1) {
                $item['informa'] = UserUtil::getVar('uid');
            }
            $item['no_mostrar'] = '$';
            $item['marca'] = '$';
            $item['data'] = time();
            $item['edited'] = '';
            $item['edited_by'] = time();
        }
        if ($m == 'c') {
            $item['marca'] = '$';
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $where = "$c[nid]=$nid";
        if (!DBUTil::updateObject($item, 'IWnoteboard', $where)) {
            return LogUtil::registerError($this->__('Error! Update attempt failed.'));
        }
        //Put all users as they have not seen the note
        if (isset($saved) && $saved == 1) {
            $c = $pntable['IWmain_column'];
            // get all users who have seen the note
            $where = "$c[module] = 'IWnoteboard'
					AND $c[name] = 'viewed'
					AND $c[value] like '%$" . $nid . "$%'";
            $items = DBUtil::selectObjectArray('IWmain', $where, '', '-1', '-1', 'id');
            // Check for an error with the database code, and if so set an appropriate
            // error message and return
            if ($items === false) {
                return LogUtil::registerError($this->__('Error! Could not load items.'));
            }
            //Update the seen note for each user who have seen it
            foreach ($items as $i) {
                $haveSeen = str_replace('$' . $nid . '$', '', $i['value']);
                $item = array('value' => $haveSeen);
                $where = "$c[id]=$i[id]";
                if (!DBUTil::updateObject($item, 'IWmain', $where)) {
                    return LogUtil::registerError($this->__('Error! Update attempt failed.'));
                }
            }
        }
        return true;
    }

    /**
     * Get the characteristics of a topic
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the topic
     * @return:	The topic information
     */
    public function gettema($args) {
        $tid = FormUtil::getPassedValue('tid', isset($args['tid']) ? $args['tid'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        extract($args);
        // Needed argument
        if (!isset($tid) || !is_numeric($tid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_topics_column'];
        $where = "$c[tid]=$tid";
        // get the objects from the db
        $item = DBUtil::selectObjectArray('IWnoteboard_topics', $where);
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($item === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the item
        return $item[0];
    }

    /**
     * Get all the comments associate with a note
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note
     * @return:	The topic information
     */
    public function getallcomentaris($args) {
        $ncid = FormUtil::getPassedValue('ncid', isset($args['ncid']) ? $args['ncid'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        // Needed argument
        if (!isset($ncid) || !is_numeric($ncid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $where = "$c[ncid]=$ncid";
        $orderby = "$c[data]";
        $items = DBUtil::selectObjectArray('IWnoteboard', $where, $orderby, '-1', '-1', 'nid');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the items
        return $items;
    }

    /**
     * update a comment
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note
     * 			The comment text
     * 			The validation value
     * @return:	True if success and false otherwise
     */
    public function updatecomentari($args) {
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        $noticia = FormUtil::getPassedValue('noticia', isset($args['noticia']) ? $args['noticia'] : null, 'POST');
        $verifica = FormUtil::getPassedValue('verifica', isset($args['verifica']) ? $args['verifica'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        // Argument check
        if (!isset($nid) || !is_numeric($nid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Get the item
        $item = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));
        if (!$item) {
            return LogUtil::registerError($this->__('No such item found.'));
        }
        $item = array('noticia' => $noticia,
            'verifica' => $verifica);
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $where = "$c[nid]=$nid";
        if (!DBUTil::updateObject($item, 'IWnoteboard', $where)) {
            return LogUtil::registerError($this->__('Error! Update attempt failed.'));
        }
        return true;
    }

    /**
     * Hide a note to an user
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note
     * 			The string of hide notes of the user
     * @return:	True if success and false otherwise
     */
    public function no_mostrar($args) {
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        $no_mostrar = FormUtil::getPassedValue('no_mostrar', isset($args['no_mostrar']) ? $args['no_mostrar'] : null, 'POST');
        $marca = FormUtil::getPassedValue('marca', isset($args['marca']) ? $args['marca'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        if (!UserUtil::isLoggedIn()) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }
        // Argument check
        if (!isset($nid) || !is_numeric($nid) || !isset($no_mostrar)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Get the item
        $item = ModUtil::apiFunc('IWnoteboard', 'user', 'get', array('nid' => $nid));
        if (!$item) {
            return LogUtil::registerError($this->__('No such item found.'));
        }
        $item = array('no_mostrar' => $no_mostrar, 'marca' => $marca);
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $where = "$c[nid]=$nid";
        if (!DBUTil::updateObject($item, 'IWnoteboard', $where)) {
            return LogUtil::registerError($this->__('Error! Update attempt failed.'));
        }
        return true;
    }

    /**
     * Mach a note with a flag
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note
     * 			The string of mached notes by the user
     * @return:	True if success and false otherwise
     */
    public function marca($args) {
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        $marca = FormUtil::getPassedValue('marca', isset($args['marca']) ? $args['marca'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        if (!UserUtil::isLoggedIn()) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }
        // Argument check
        if (!isset($nid) || !is_numeric($nid) || !isset($marca)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Get the item
        $item = ModUtil::apiFunc('IWnoteboard', 'user', 'get', array('nid' => $nid));
        if (!$item) {
            return LogUtil::registerError($this->__('No such item found.'));
        }
        $item = array('marca' => $marca);
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $where = "$c[nid]=$nid";
        if (!DBUTil::updateObject($item, 'IWnoteboard', $where)) {
            return LogUtil::registerError($this->__('Error! Update attempt failed.'));
        }
        return true;
    }

    /**
     * Get all the headlines of the notes
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @return:	An array with the headlines information
     */
    public function getalltitulars() {
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $time = time();
        $where = "$c[titulin]<$time AND $c[titulout]>$time AND $c[titular]<>'' AND $c[caduca]>$time";
        $orderby = "$c[titulin] desc";
        if (ModUtil::getVar('IWnoteboard', 'multiLanguage') == 1) {
            $where .= " AND $c[lang]='" . UserUtil::getLang() . "' OR $c[lang] = ''";
        }
        $items = DBUtil::selectObjectArray('IWnoteboard', $where, $orderby, '-1', '-1', 'nid');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the items
        return $items;
    }

    /**
     * Get all the expired notes
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of topic
     * @return:	An array with the headlines information
     */
    public function getallcaducated($args) {
        $tema = FormUtil::getPassedValue('tema', isset($args['tema']) ? $args['tema'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        // Get the user permissions in noteboard
        $permissions = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos', array('uid' => UserUtil::getVar('uid')));
        if (empty($permissions) ||
                !$permissions['potverificar']) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $time = time();
        if ($tema > 0) {
            $where = "$c[caduca] < $time AND $c[tid]=$tema AND $c[ncid]=0";
        } elseif ($tema == '-1') {
            $where = "$c[caduca] < $time AND $c[tid]=0 AND $c[ncid]=0";
        } else {
            $where = "$c[caduca] < $time AND $c[ncid]=0";
        }
        $orderby = "$c[data] desc,$c[nid] desc";
        $items = DBUtil::selectObjectArray('IWnoteboard', $where, $orderby, '-1', '-1', 'nid');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the items
        return $items;
    }

    /**
     * Get the number of notes that haven't been seen by an user
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	A string with the notes seen by the user
     * @return:	The number of notes that the user haven't seen
     */
    public function noves($args) {
        $uid = FormUtil::getPassedValue('uid', isset($args['uid']) ? $args['uid'] : UserUtil::getVar('uid'), 'POST');
        $sv = FormUtil::getPassedValue('sv', isset($args['sv']) ? $args['sv'] : null, 'POST');
        if (!ModUtil::func('IWmain', 'user', 'checkSecurityValue', array('sv' => $sv))) {
            // Security check
            if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
                return LogUtil::registerPermissionError();
            }
        } else {
            $requestByCron = true;
        }
        $nombrevistes = array();
        if ($uid != UserUtil::getVar('uid') && !$requestByCron) {
            return $nombrevistes;
        }
        $nombre = 0;
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $registres = ModUtil::apiFunc('IWnoteboard', 'user', 'getall', array('sv' => $sv));
        //Check the user permisions
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $permisos = ModUtil::apiFunc('IWnoteboard', 'user', 'permisos', array('uid' => $uid,
                    'sv' => $sv));
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $vistes = ModUtil::func('IWmain', 'user', 'userGetVar', array('name' => 'viewed',
                    'uid' => $uid,
                    'module' => 'IWnoteboard',
                    'sv' => $sv));
        foreach ($registres as $registre) {
            //separem la llista de grups que tenen accï¿œs a la notï¿œcia i ho posem en una matriu
            $grups_acces = explode('$', $registre['destinataris']);
            $esta_en_grups_acces = array_intersect($grups_acces, $permisos['grups']);
            if (($registre['verifica'] == 1 &&
                    (count($esta_en_grups_acces) >= 1 ||
                    $uid == $registre['informa'])) ||
                    $permisos['potverificar']) {
                //Comprovem si el registre estï¿œ dins del text
                $pos1 = strpos($registre['no_mostrar'], '$' . $uid . '$');
                $pos = strpos($vistes, '$' . $registre['nid'] . '$');
                if ($pos1 == 0) {
                    ($pos == 0 ? $nombre++ : '');
                }
            }
        }
        $nombrevistes = array('nombre' => $nombre,
            'vistes' => $vistes);
        return $nombrevistes;
    }

    /**
     * Get the users that have seen the note
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	Id of the note
     * @return:	An array with the full names of the users that have seen the note
     */
    public function hanvist($args) {
        $nid = FormUtil::getPassedValue('nid', isset($args['nid']) ? $args['nid'] : null, 'POST');
        $no_mostrar = FormUtil::getPassedValue('no_mostrar', isset($args['no_mostrar']) ? $args['no_mostrar'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        if (!UserUtil::isLoggedIn()) {
            LogUtil::registerError($this->__('You are not allowed to do this action'));
            return System::redirect(ModUtil::url('IWnoteboard', 'user', 'main'));
        }
        // Argument check
        if (!isset($nid) || !is_numeric($nid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Get the item
        $item = ModUtil::apiFunc('IWnoteboard', 'user', 'get',
                        array('nid' => $nid));
        if (!$item) {
            return LogUtil::registerError($this->__('No such item found.'));
        }
        $hanvist = $item['no_mostrar'];
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $usersFullname = ModUtil::func('IWmain', 'user', 'getAllUsersInfo',
                        array('info' => 'ccn',
                            'sv' => $sv));
        $pntable = DBUtil::getTables();
        $c = $pntable['IWmain_column'];
        $where = "$c[value] like '$%" . $nid . "%$'";
        $items = DBUtil::selectObjectArray('IWmain', $where, '', '-1', '-1', 'id');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        foreach ($items as $item) {
            $registres[] = array('usuari' => $usersFullname[$item['uid']]);
        }

        //Delete the last characther because it is a $
        $hanvist = substr($hanvist, 0, -1);
        //Unjoin the array elements and add them into the users full name array
        $hanvist = explode('$$', $hanvist);
        //Delete the first element of the array because it is nothing
        array_shift($hanvist);
        foreach ($hanvist as $hanvist1) {
            $registres[] = array('usuari' => $usersFullname[$hanvist1]);
        }
        //Reorder the array
        sort($registres);
        //Return the array with the users full names
        return $registres;
    }

    /**
     * Get the user permissions for the noteboard
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note
     * 			The string of mached notes by the user
     * @return:	True if success and false otherwise
     */
    public function permisos($args) {
        $uid = FormUtil::getPassedValue('uid', isset($args['uid']) ? $args['uid'] : UserUtil::getVar('uid'), 'POST');
        $sv = FormUtil::getPassedValue('sv', isset($args['sv']) ? $args['sv'] : null, 'POST');
        if (!ModUtil::func('IWmain', 'user', 'checkSecurityValue', array('sv' => $sv))) {
            // Security check
            if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
                return LogUtil::registerPermissionError();
            }
        } else {
            $requestByCron = true;
        }
        $nivell_permisos = array();
        //if user is not registered have a fixed permissions
        if (!UserUtil::isLoggedIn() && !$requestByCron) {
            $nivell_permisos = array('nivell' => 1,
                'verifica' => 2,
                'potverificar' => false,
                'grups' => array(0));
            //return not registered permissions
            return $nivell_permisos;
        }
        // Arguments needed
        if (!isset($uid) || ($uid != UserUtil::getVar('uid') && !$requestByCron)) {
            SessionUtil::setVar('errormsg', $this->__('Error! Could not do what you wanted. Please check your input.'));
            return $nivell_permisos;
        }
        $myJoin = array();
        $myJoin[] = array('join_table' => 'groups',
            'join_field' => array('gid'),
            'object_field_name' => array('gid'),
            'compare_field_table' => 'gid',
            'compare_field_join' => 'gid');
        $myJoin[] = array('join_table' => 'group_membership',
            'join_field' => array(),
            'object_field_name' => array(),
            'compare_field_table' => 'gid',
            'compare_field_join' => 'gid');
        $pntables = DBUtil::getTables();
        $ccolumn = $pntables['groups_column'];
        $ocolumn = $pntables['group_membership_column'];
        $where = "b.$ocolumn[gid] = a.$ccolumn[gid] AND b.$ocolumn[uid] = $uid";
        $items = DBUtil::selectExpandedObjectArray('groups', $myJoin, $where, '');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return $nivell_permisos;
        }
        $verifica = 2;
        $potverificar = false;
        $permisosModVar = ModUtil::getVar('IWnoteboard', 'permisos');
        $verificaModVar = ModUtil::getVar('IWnoteboard', 'verifica');
        $quiverificaModVar = ModUtil::getVar('IWnoteboard', 'quiverifica');
        foreach ($items as $item) {
            // get user permissions level
            $permis = substr($permisosModVar, strpos($permisosModVar, '$' . $item['gid'] . '-') + strlen($item['gid']) + 2, 1);
            $verifica = (strpos($verificaModVar, '$' . $item['gid'] . '$') != 0 && $verifica != 1) ? 0 : 1;
            if ($permis > $n_permisos) {
                $n_permisos = $permis;
            }
            if ($quiverificaModVar == $item['gid']) {
                $potverificar = true;
            }
            $grups[] = $item['gid'];
        }
        $nivell_permisos = array('nivell' => $n_permisos,
            'verifica' => $verifica,
            'potverificar' => $potverificar,
            'grups' => $grups);
        return $nivell_permisos;
    }

    /**
     * Gets all the topics defined in the noteboard
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @return:	And array with the items information
     */
    public function getallSharedURL() {
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_public_column'];
        $orderby = "$c[url]";
        // get the objects from the db
        $items = DBUtil::selectObjectArray('IWnoteboard_public', '', $orderby, '-1', '-1', 'pid');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the items
        return $items;
    }

    /**
     * Get the characteristics of a shared url
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the topic
     * @return:	The topic information
     */
    public function getShared($args) {
        $pid = FormUtil::getPassedValue('pid', isset($args['pid']) ? $args['pid'] : null, 'POST');
        $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        // Needed argument
        if ((!isset($pid) || !is_numeric($pid)) && !isset($sid)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_public_column'];
        if ($sid == null) {
            $where = "$c[pid]=$pid";
        } else {
            $where = "$c[url] like '%" . $sid . "%'";
        }
        // get the objects from the db
        $item = DBUtil::selectObjectArray('IWnoteboard_public', $where);
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($item === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the item
        return $item[0];
    }

    /**
     * Check if a shared note exists
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @param:	args	The id of the note in the shared noteboard and the shared noteboard sid
     * @return:	An array with the note information
     */
    public function sharedItemExists($args) {
        $sharedId = FormUtil::getPassedValue('sharedId', isset($args['sharedId']) ? $args['sharedId'] : null, 'POST');
        $sharedFrom = FormUtil::getPassedValue('sharedFrom', isset($args['sharedFrom']) ? $args['sharedFrom'] : null, 'POST');
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        // Needed argument
        if (!isset($sharedId) || !is_numeric($sharedId) || !isset($sharedFrom)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $where = "$c[sharedId]=$sharedId AND $c[sharedFrom]='$sharedFrom'";
        // get the objects from the db
        $item = DBUtil::selectObjectArray('IWnoteboard', $where);
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($item === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        if (count($item) == 0) {
            return false;
        } else {
            return array('edited' => $item[0]['edited'],
                'nid' => $item[0]['nid']);
        }
    }

    /**
     * Gets all the notes where that the user has flagged
     * @author:     Albert PÃ©rez Monfort (aperezm@xtec.cat)
     * @return:	And array with the items information
     */
    public function getFlagged() {
        // Security check
        if (!SecurityUtil::checkPermission('IWnoteboard::', '::', ACCESS_READ)) {
            return LogUtil::registerPermissionError();
        }
        $pntable = DBUtil::getTables();
        $c = $pntable['IWnoteboard_column'];
        $where = "$c[marca] like '%$" . UserUtil::getVar('uid') . "$%'";
        $orderby = "$c[data] desc";
        // get the objects from the db
        $items = DBUtil::selectObjectArray('IWnoteboard', $where, $orderby, '-1', '-1', 'nid');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($items === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }
        // Return the items
        return $items;
    }

    /**
     * Get and display the RSS content with all the notes of a noteboard
     * @author: Sara Arjona Tï¿œllez (sarjona@xtec.cat)
     * @param:	args
     * @return:	The XML with the context of the noteboard
     */
    public function display_rss($args) {
        // Get the parameters
        $url = FormUtil::getPassedValue('url', isset($args['url']) ? $args['url'] : null, 'POST');
        $parsed_url = parse_url($url);
        $start = strpos($parsed_url['query'], "sid=") + 4;
        $length = strpos($parsed_url['query'], "&", $start + 1);
        if ($length === FALSE)
            $length = strlen($parsed_url['query']) - $start;
        else
            $length = $length - $start;
        $sid = substr($parsed_url['query'], $start, $length);
        // get user identity
        $uid = UserUtil::getVar('uid');
        if ($uid == '') {
            $uid = '-1';
        }
        // Check if rss content already exists for current user
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $exists = ModUtil::apiFunc('IWmain', 'user', 'userVarExists',
                        array('name' => 'rss_' . $sid,
                            'module' => 'IWnoteboard',
                            'uid' => $uid,
                            'sv' => $sv));
        if (!$exists) {
            //Get the rss content
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            $rss_content = ModUtil::apiFunc('IWnoteboard', 'user', 'call_servlet',
                            array('url' => $url,
                                'sv' => $sv));
            ModUtil::apiFunc('IWnoteboard', 'user', 'setdata',
                            array('xml' => $rss_content));
            //Set 1 to cache var to indicate that it is not necessary a new sql request for some time
            $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
            ModUtil::func('IWmain', 'user', 'userSetVar',
                            array('uid' => $uid,
                                'name' => 'rss_' . $sid,
                                'module' => 'IWnoteboard',
                                'sv' => $sv,
                                'value' => 1,
                                'lifetime' => '1500'));
        }
        return true;
    }

    /**
     * Open a socket to get the content of the specified URL
     * @author: Sara Arjona Tï¿œllez (sarjona@xtec.cat)
     * @param:	args
     * @return:	The XML with the context of the noteboard
     */
    public function call_servlet($args) {
        $sv = FormUtil::getPassedValue('sv', isset($args['sv']) ? $args['sv'] : null, 'POST');
        if (!ModUtil::func('IWmain', 'user', 'checkSecurityValue', array('sv' => $sv))) {
            return LogUtil::registerError(_IWMAININFORMATIONNOTACCESS);
        }
        $url = FormUtil::getPassedValue('url', isset($args['url']) ? $args['url'] : null, 'POST');
        if (!isset($url)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        $result = "";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($params))
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function setdata($args) {
        // Get the parameters
        $xml = FormUtil::getPassedValue('xml', isset($args['xml']) ? $args['xml'] : null, 'POST');
        if (!isset($xml)) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        // Get current user groups
        $sv = ModUtil::func('IWmain', 'user', 'genSecurityValue');
        $allGroupsInfo = ModUtil::func('IWmain', 'user', 'getAllGroupsInfo', array('sv' => $sv));
        $sharedIds = array();
        $items = $doc->getElementsByTagName("item");
        foreach ($items as $item) {
            $shared_id = $item->getElementsByTagName('shared_id')->item(0)->nodeValue;
            $shared_from = utf8_decode($item->getElementsByTagName('shared_from')->item(0)->nodeValue);
            $literal_groups = $item->getElementsByTagName('literal_groups')->item(0)->nodeValue;
            $noticia = utf8_decode($item->getElementsByTagName('description')->item(0)->nodeValue);
            $caduca = $item->getElementsByTagName('caduca')->item(0)->nodeValue;
            $titular = utf8_decode($item->getElementsByTagName('title')->item(0)->nodeValue);
            $titulin = utf8_decode($item->getElementsByTagName('titulin')->item(0)->nodeValue);
            $titulout = utf8_decode($item->getElementsByTagName('titulout')->item(0)->nodeValue);
            $mes_info = utf8_decode($item->getElementsByTagName('mes_info')->item(0)->nodeValue);
            $text = utf8_decode($item->getElementsByTagName('text')->item(0)->nodeValue);
            $nom_fitxer = utf8_decode($item->getElementsByTagName('nom_fitxer')->item(0)->nodeValue);
            $textfitxer = utf8_decode($item->getElementsByTagName('textfitxer')->item(0)->nodeValue);
            $language = $item->getElementsByTagName('language')->item(0)->nodeValue;
            $edited = $item->getElementsByTagName('edited')->item(0)->nodeValue;
            //Check if the item exists. If the item exists the function returns the creation date and the note identity
            $sharedItemExists = ModUtil::apiFunc('IWnoteboard', 'user', 'sharedItemExists',
                            array('sharedId' => $shared_id,
                                'sharedFrom' => $shared_from));
            //adapt the groups that can access the note
            $groups = explode('$', substr($literal_groups, 2, -1));
            $desti = '$$';
            foreach ($groups as $group) {
                if ($group == '0') {
                    $desti .= '0$';
                }
                if ($key = array_search($group, $allGroupsInfo)) {
                    $desti .= $key . '$';
                }
            }
            if (!$sharedItemExists) {
                //create the note
                ModUtil::apiFunc('IWnoteboard', 'user', 'crear',
                                array('noticia' => $noticia,
                                    'data' => time(),
                                    'caduca' => $caduca,
                                    'titular' => $titular,
                                    'titulin' => $titulin,
                                    'titulout' => $titulout,
                                    'mes_info' => $mes_info,
                                    'text' => $text,
                                    'fitxer' => $nom_fitxer,
                                    'textfitxer' => $textfitxer,
                                    'destinataris' => $desti,
                                    'admet_comentaris' => 0,
                                    'verifica' => 1,
                                    'tid' => $tid,
                                    'language' => $language,
                                    'public' => 0,
                                    'literalGroups' => '',
                                    'sharedFrom' => $shared_from,
                                    'sharedId' => $shared_id));
            } else {
                //check if the note date is the older than the edited shared note
                if ($sharedItemExists['edited'] < $edited) {
                    ModUtil::apiFunc('IWnoteboard', 'user', 'update',
                                    array('data' => time(),
                                        'nid' => $sharedItemExists['nid'],
                                        'noticia' => $noticia,
                                        'caduca' => $caduca,
                                        'titular' => $titular,
                                        'titulin' => $titulin,
                                        'titulout' => $titulout,
                                        'mes_info' => $mes_info,
                                        'text' => $text,
                                        'fitxer' => $nom_fitxer,
                                        'textfitxer' => $textfitxer,
                                        'destinataris' => $desti,
                                        'admet_comentaris' => 0,
                                        'verifica' => 1,
                                        'tid' => $tid,
                                        'language' => $language,
                                        'public' => 0,
                                        'literalGroups' => '',
                                        'sharedFrom' => $shared_from,
                                        'sharedId' => $shared_id));
                }
            }
            //include the notes into an array necessary to detect the notes deleted in shared noteboards
            array_push($sharedIds, $shared_id);
        }
        //get all the shared notes that cames from a specific noteboard
        //filter the deleted notes in shared noteboard
        //delete from the noteboard the deleted notes in shared noteboard
        return true;
    }
}