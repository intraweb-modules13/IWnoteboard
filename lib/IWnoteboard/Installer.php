<?php

class IWnoteboard_Installer extends Zikula_AbstractInstaller {
    /**
     * Initialise the IWnoteboard module creating module tables and module vars
     * @author Albert Pérez Monfort (aperezm@xtec.cat)
     * @return bool true if successful, false otherwise
     */
    public function Install() {
        // Checks if module IWmain is installed. If not returns error
        $modid = ModUtil::getIdFromName('IWmain');
        $modinfo = ModUtil::getInfo($modid);

        if ($modinfo['state'] != 3) {
            return LogUtil::registerError($this->__('Module IWmain is required. You have to install the IWmain module previously to install it.'));
        }

        // Check if the version needed is correct
        $versionNeeded = '2.0';
        if (!ModUtil::func('IWmain', 'admin', 'checkVersion',
                array('version' => $versionNeeded))) {
            return false;
        }

        // Create module tables
        if (!DBUtil::createTable('IWnoteboard'))
            return false;
        if (!DBUtil::createTable('IWnoteboard_topics'))
            return false;
        if (!DBUtil::createTable('IWnoteboard_public'))
            return false;

        //Create module vars
        ModUtil::setVar('IWnoteboard', 'grups', '');
        ModUtil::setVar('IWnoteboard', 'permisos', '');
        ModUtil::setVar('IWnoteboard', 'marcat', '');
        ModUtil::setVar('IWnoteboard', 'verifica', '');
        ModUtil::setVar('IWnoteboard', 'caducitat', '30');
        ModUtil::setVar('IWnoteboard', 'repperdefecte', '1');
        ModUtil::setVar('IWnoteboard', 'colorrow1', '#FFFFFF');
        ModUtil::setVar('IWnoteboard', 'colorrow2', '#FFFFCC');
        ModUtil::setVar('IWnoteboard', 'colornewrow1', '#FFCC99');
        ModUtil::setVar('IWnoteboard', 'colornewrow2', '#99FFFF');
        ModUtil::setVar('IWnoteboard', 'attached', 'noteboard');
        ModUtil::setVar('IWnoteboard', 'notRegisteredSeeRedactors', '1');
        ModUtil::setVar('IWnoteboard', 'multiLanguage', '0');
        ModUtil::setVar('IWnoteboard', 'public', '0');
        ModUtil::setVar('IWnoteboard', 'topicsSystem', '0');
        ModUtil::setVar('IWnoteboard', 'publicSharedURL', '');
        ModUtil::setVar('IWnoteboard', 'showSharedURL', '1');
        ModUtil::setVar('IWnoteboard', 'sharedName', ModUtil::getVar('/PNConfig', 'sitename'));
        ModUtil::setVar('IWnoteboard', 'editPrintAfter', '-1');

        //Initialation successfull
        return true;
    }

    /**
     * Delete the IWnoteboard module
     * @author Albert Pérez Monfort (aperezm@xtec.cat)
     * @return bool true if successful, false otherwise
     */
    public function Uninstall() {
        // Delete module table
        DBUtil::dropTable('IWnoteboard');
        DBUtil::dropTable('IWnoteboard_topics');
        DBUtil::dropTable('IWnoteboard_public');

        //Delete module vars
        ModUtil::delVar('IWnoteboard', 'grups');
        ModUtil::delVar('IWnoteboard', 'permisos');
        ModUtil::delVar('IWnoteboard', 'marcat');
        ModUtil::delVar('IWnoteboard', 'verifica');
        ModUtil::delVar('IWnoteboard', 'caducitat');
        ModUtil::delVar('IWnoteboard', 'repperdefecte');
        ModUtil::delVar('IWnoteboard', 'colorrow1');
        ModUtil::delVar('IWnoteboard', 'colorrow2');
        ModUtil::delVar('IWnoteboard', 'colornewrow1');
        ModUtil::delVar('IWnoteboard', 'colornewrow2');
        ModUtil::delVar('IWnoteboard', 'attached');
        ModUtil::delVar('IWnoteboard', 'notRegisteredSeeRedactors');
        ModUtil::delVar('IWnoteboard', 'multiLanguage');
        ModUtil::delVar('IWnoteboard', 'public');
        ModUtil::delVar('IWnoteboard', 'topicsSystem');
        ModUtil::delVar('IWnoteboard', 'publicSharedURL');
        ModUtil::delVar('IWnoteboard', 'showSharedURL');
        ModUtil::delVar('IWnoteboard', 'sharedName');
        ModUtil::delVar('IWnoteboard', 'editPrintAfter');

        //Deletion successfull
        return true;
    }

    /**
     * Update the IWnoteboard module
     * @author Albert Pérez Monfort (aperezm@xtec.cat)
     * @return bool true if successful, false otherwise
     */
    public function upgrade($oldversion) {
        // Checks if module IWmain is installed. If not returns error
        $modid = ModUtil::getIdFromName('IWmain');
        $modinfo = ModUtil::getInfo($modid);

        if ($modinfo['state'] != 3) {
            return LogUtil::registerError($this->__('Module IWmain is required. You have to install the IWmain module previously to install it.'));
        }

        // Check if the version needed is correct
        $versionNeeded = '2.0';
        if (!ModUtil::func('IWmain', 'admin', 'checkVersion', array('version' => $versionNeeded))) {
            return false;
        }

        if (!DBUtil::changeTable('IWnoteboard'))
            return false;

        if ($oldversion < '1.2') {
            if (!DBUtil::createTable('IWnoteboard_public'))
                return false;

            ModUtil::setVar('IWnoteboard', 'multiLanguage', '0');
            ModUtil::setVar('IWnoteboard', 'public', '0');
            ModUtil::setVar('IWnoteboard', 'topicsSystem', '0');
            ModUtil::setVar('IWnoteboard', 'publicSharedURL', '');
            ModUtil::setVar('IWnoteboard', 'showSharedURL', '1');
            ModUtil::setVar('IWnoteboard', 'sharedName', ModUtil::getVar('/PNConfig', 'sitename'));
        }

        if ($oldversion < '1.3') {
            if (!DBUtil::renameTable('IWnoteboard_themes', 'IWnoteboard_topics'))
                return false;
        }

        return true;
    }
}