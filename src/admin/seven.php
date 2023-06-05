<?php
// -----
// Part of the "Seven" plugin for Zen Cart v1.5.7 or later
//
// Copyright (c) 2021-2022, sms77 e.K.
// Copyright (c) 2023-present, seven communications GmbH & Co. KG
//
global $db;
require 'includes/application_top.php';
$messages = [];
$apiKey = $db->Execute('SELECT configuration_value FROM ' . TABLE_CONFIGURATION
    . ' WHERE configuration_key = "SEVEN_API_KEY"')->fields['configuration_value'];
$hasApiKey = 0 !== strlen($apiKey);
if (!$hasApiKey) {
    $messages[] = ['type' => 'warning', 'text' => MSG_MISSING_API_KEY];
}
?>
    <!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
    <html <?php echo HTML_PARAMS; ?>>
    <head>
        <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
    </head>
    <body>
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>

    <?php if (!empty($_POST) && 'bulk_sms' === $_GET['action']): ?>
        <?php
        $whereCountries = '';
        $countryCustomerIds = [];
        $to = $_POST['to'];

        if (!strlen($to)) {
            $proceed = true;
            $to = [];
            $countries = $_POST['countries'];
            $customerSQL = "SELECT customers_firstname, customers_lastname,
                customers_telephone FROM " . TABLE_CUSTOMERS;

            if (count($countries)) {
                $whereCountries = implode(',', $countries);

                foreach ($db->Execute("SELECT customers_id FROM "
                    . TABLE_ADDRESS_BOOK . " WHERE entry_country_id IN($whereCountries)")
                         as $c) {
                    $countryCustomerIds[] = $c['customers_id'];
                }

                if (count($countryCustomerIds)) {
                    $countryCustomerIds = implode(',', $countryCustomerIds);
                    $customerSQL .= " WHERE customers_id IN($countryCustomerIds)";
                } else {
                    $proceed = false;
                }
            }

            if ($proceed) {
                foreach ($db->Execute($customerSQL) as $customer) {
                    $to[] = $customer['customers_telephone'];
                }
            }

            $to = implode(',', $to);
        }

        if (strlen($to)) {
            $payload = [
                'from' => $_POST['from'],
                'json' => 1,
                'text' => $_POST['text'],
                'to' => $to,
            ];
            foreach (['debug', 'delay', 'no_reload', 'utf8', 'flash', 'udh', 'ttl',
                         'label', 'performance_tracking', 'foreign_id'] as $key) {
                if (array_key_exists($key, $_POST)) {
                    $payload[$key] = $_POST[$key];
                }
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://gateway.seven.io/api/sms');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Basic $apiKey",
                'SentWith: ZenCart',
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $sql = "INSERT INTO seven_sms (config, response) VALUES (:config, :response);";
            $sql = $db->bindVars($sql,
                ':config', json_encode($_POST), 'string');
            $sql = $db->bindVars($sql,
                ':response', $response, 'string');
            $db->Execute($sql);
            $messages[] = ['type' => 'info', 'text' => MSG_SENT];
        } else {
            $messages[] = ['type' => 'warning', 'text' => MSG_MISSING_RECIPIENTS];
        }
        ?>
        <pre><?php var_dump(compact('to', 'whereCountries', 'countryCustomerIds', '_POST')) ?></pre>
    <?php endif ?>

    <h2><?php echo SECTION_HEADING_SMS_BULK ?></h2>

    <?php foreach ($messages as $message): ?>
        <div class="alert alert-<?php echo $message['type'] ?> alert-dismissible"
             role="alert">
            <button type="button" class="close" data-dismiss="alert"
                    aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <?php echo $message['text'] ?>
        </div>
    <?php endforeach; ?>

    <?php if ($hasApiKey): ?>
        <?php echo zen_draw_form('seven_bulk_sms', FILENAME_SEVEN,
            'action=bulk_sms', 'POST', 'class="form-horizontal"'); ?>
        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='countries'><?php echo HEADING_FILTER_COUNTRIES ?></label>
            <div class="col-sm-10">
                <select class="form-control" id='countries' multiple name='countries[]'>
                    <?php foreach (
                        $db->Execute("SELECT countries_id, countries_name FROM "
                            . TABLE_COUNTRIES) as $country): ?>
                        <option value='<?php echo $country['countries_id'] ?>'>
                            <?php echo $country['countries_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='from'><?php echo HEADING_INPUT_FROM ?></label>
            <div class="col-sm-10">
                <input class="form-control" maxlength='16' id='from' name='from'
                       type="text"
                       value='<?php echo $db->Execute('SELECT configuration_value FROM '
                           . TABLE_CONFIGURATION . ' WHERE configuration_key = "SEVEN_FROM"')
                           ->fields['configuration_value'] ?>'/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='label'><?php echo HEADING_INPUT_LABEL ?></label>
            <div class="col-sm-10">
                <input class="form-control" maxlength='100' id='label' name='label'
                       type="text"/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='foreign_id'><?php echo HEADING_INPUT_FOREIGN_ID ?></label>
            <div class="col-sm-10">
                <input class="form-control" maxlength='64' id='foreign_id'
                       name='foreign_id'
                       type="text"/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='delay'><?php echo HEADING_INPUT_DELAY ?></label>
            <div class="col-sm-10">
                <input class="form-control" id='delay' name='delay' type="text"/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='ttl'><?php echo HEADING_INPUT_TTL ?></label>
            <div class="col-sm-10">
                <input class="form-control" id='ttl' name='ttl' type="number"/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='udh'><?php echo HEADING_INPUT_UDH ?></label>
            <div class="col-sm-10">
                <input class="form-control" id='udh' name='udh' type="text"/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='to'><?php echo HEADING_INPUT_TO ?></label>
            <div class="col-sm-10">
                <input class="form-control" id='to' name='to' type="text"/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='text'><?php echo HEADING_INPUT_TEXT ?></label>
            <div class="col-sm-10">
                         <textarea maxlength='1520' class="form-control" id='text'
                                   name='text'
                                   required></textarea>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='debug'><?php echo HEADING_INPUT_DEBUG ?></label>
            <div class="col-sm-10">
                <input id='debug' name='debug' type="checkbox"
                       value='1'/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='no_reload'><?php echo HEADING_INPUT_NO_RELOAD ?></label>
            <div class="col-sm-10">
                <input id='no_reload' name='no_reload'
                       type="checkbox" value='1'/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='flash'><?php echo HEADING_INPUT_FLASH ?></label>
            <div class="col-sm-10">
                <input id='flash' name='flash' type="checkbox"
                       value='1'/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"
                   for='utf8'><?php echo HEADING_INPUT_UTF8 ?></label>
            <div class="col-sm-10">
                <input id='utf8' name='utf8' type="checkbox"
                       value='1'/>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label" for='performance_tracking'>
                <?php echo HEADING_INPUT_PERFORMANCE_TRACKING ?></label>
            <div class="col-sm-10">
                <input id='performance_tracking'
                       name='performance_tracking' type="checkbox" value='1'/>
            </div>
        </div>

        <button class='btn btn-primary center-block'
                type='submit'><?php echo HEADING_BTN_SEND_SMS ?></button>
        </form>
    <?php endif ?>

    <table class="table table-striped">
        <caption><?php echo SECTION_HEADING_SMS_HISTORY ?></caption>
        <thead>
        <tr>
            <th><?php echo TABLE_HEADING_ID ?></th>
            <th><?php echo TABLE_HEADING_CONFIG ?></th>
            <th><?php echo TABLE_HEADING_RESPONSE ?></th>
            <th><?php echo TABLE_HEADING_CREATED ?></th>
            <th><?php echo TABLE_HEADING_UPDATED ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($db->Execute('SELECT * FROM seven_sms') as $sms): ?>
            <tr>
                <td><?php echo $sms['id'] ?></td>
                <td><?php echo $sms['config'] ?></td>
                <td><?php echo $sms['response'] ?></td>
                <td><?php echo $sms['created'] ?></td>
                <td><?php echo $sms['updated'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
    </body>
    </html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
