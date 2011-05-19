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
        $versionNeeded = '3.0.0';
        if (!ModUtil::func('IWmain', 'admin', 'checkVersion',
                        array('version' => $versionNeeded))) {
            return false;
        }

        // Create module tables
        if (!DBUtil::createTable('IWnoteboard'))
            return false;
        if (!DBUtil::createTable('IWnoteboard_topics'))
            return false;

        //Create module vars
        $this->setVar('grups', '')
                ->setVar('permisos', '')
                ->setVar('marcat', '')
                ->setVar('verifica', '')
                ->setVar('caducitat', '30')
                ->setVar('repperdefecte', '1')
                ->setVar('colorrow1', '#FFFFFF')
                ->setVar('colorrow2', '#FFFFCC')
                ->setVar('colornewrow1', '#FFCC99')
                ->setVar('colornewrow2', '#99FFFF')
                ->setVar('attached', 'noteboard')
                ->setVar('notRegisteredSeeRedactors', '1')
                ->setVar('multiLanguage', '0')
                ->setVar('topicsSystem', '0')
                ->setVar('shipHeadersLines', '0')
                ->setVar('notifyNewEntriesByMail', '0')
                ->setVar('editPrintAfter', '-1');


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

        //Delete module vars
        $this->delVar('grups')
                ->delVar('permisos')
                ->delVar('marcat')
                ->delVar('verifica')
                ->delVar('caducitat')
                ->delVar('repperdefecte')
                ->delVar('colorrow1')
                ->delVar('colorrow2')
                ->delVar('colornewrow1')
                ->delVar('colornewrow2')
                ->delVar('attached')
                ->delVar('notRegisteredSeeRedactors')
                ->delVar('multiLanguage')
                ->delVar('topicsSystem')
                ->delVar('shipHeadersLines')
                ->delVar('notifyNewEntriesByMail')
                ->delVar('editPrintAfter');

        //Deletion successfull
        return true;
    }

    /**
     * Update the IWnoteboard module
     * @author Albert Pérez Monfort (aperezm@xtec.cat)
     * @return bool true if successful, false otherwise
     */
    public function upgrade($oldversion) {

        return true;
    }

}