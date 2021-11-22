<?php
// -----
// Part of the "Sms77" plugin for Zen Cart v1.5.7 or later
//
// Copyright (c) 2021-present, sms77 e.K.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[200][] = [
    'autoType'  => 'init_script',
    'loadFile'  => 'init_sms77.php'];
