<?php

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase {
    private $adminPages = ['toolsSms77'];

    protected function executeInstall() {
        zen_deregister_admin_pages($this->adminPages);

        zen_register_admin_page($this->adminPages[0],
            'BOX_TOOLS_SMS77',
            'FILENAME_SMS77',
            '', 'tools', 'Y');

        $storeName = $this->dbConn->Execute('SELECT c.configuration_value FROM '
            . TABLE_CONFIGURATION . ' c WHERE c.configuration_key = "STORE_NAME"')
            ->fields['configuration_value'];

        $sql =
            'INSERT IGNORE INTO ' . TABLE_CONFIGURATION . " 
            ( configuration_title, configuration_key, configuration_value, 
            configuration_description, configuration_group_id, sort_order, date_added, 
            use_function, set_function ) VALUES 
            ('Sms77: API Key', 'SMS77_API_KEY', '',
             'Your API Key from Sms77.io. (<b>Required</b>)', 1, 100, now(), NULL, NULL),
            ('Sms77: Caller ID', 'SMS77_FROM', '$storeName', 
            'The sender identifier.', 1, 101, now(), NULL, NULL);
            ";

        $this->executeInstallerSql($sql);
    }

    protected function executeUninstall() {
        zen_deregister_admin_pages($this->adminPages);

        $deleteMap = "'SMS77_API_KEY', 'SMS77_FROM'";
        $this->executeInstallerSql('DELETE FROM ' . TABLE_CONFIGURATION
            . " WHERE configuration_key IN ($deleteMap)");
    }
}
