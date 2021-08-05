<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$module_id = "startsend.sms";
$showAddOptions = false;
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
$zr = "";
if (!($MODULE_RIGHT >= "R"))
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$APPLICATION->SetTitle(Loc::getMessage("STARTSEND_SMS_OPT_TITLE"));

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

\Bitrix\Main\Loader::includeModule($module_id);

$obCache = \Bitrix\Main\Data\Cache::createInstance();
$obCache->cleanDir("/startsend/sms/admin/");

if ($_SERVER["REQUEST_METHOD"] == "POST" && $MODULE_RIGHT == "W" && strlen($_REQUEST["Update"]) > 0 && check_bitrix_sessid()) {
    \Bitrix\Main\Config\Option::set($module_id, "token", $_REQUEST["token"]);
    \Bitrix\Main\Config\Option::set($module_id, "api", $_REQUEST["api"]);
    \Bitrix\Main\Config\Option::set($module_id, "sender", $_REQUEST["sender"]);
}

$smsOb = new \Startsend\Sms\Sender();

$gates = \Startsend\Sms\Sender::getGatesList();

$balance = $smsOb->getBalance();

try {
    $senderList = $smsOb->getAllSender();
} catch (\Bitrix\Main\ArgumentNullException $e) {
} catch (\Bitrix\Main\ArgumentOutOfRangeException $e) {
}


if (!$_REQUEST["sender"] && !$senderList->error && !\Bitrix\Main\Config\Option::get($module_id, "sender", "", "")) {
    foreach ($senderList as $sender) {
        if ($sender->state == 'completed') \Bitrix\Main\Config\Option::set($module_id, "sender", $sender->sender);
    }
}

$aTabs = array();
$aTabs[] = array("DIV" => "edit3", "TAB" => Loc::getMessage("STARTSEND_SMS_OPT_TAB1"), "ICON" => "vote_settings", "TITLE" => Loc::getMessage("STARTSEND_SMS_OPT_TAB1_T"));
$aTabs[] = array("DIV" => "edit4", "TAB" => Loc::getMessage("STARTSEND_SMS_OPT_TAB2"), "ICON" => "vote_settings2", "TITLE" => Loc::getMessage("STARTSEND_SMS_OPT_TAB2_T"));

$tabControl = new \CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
    <form method="POST"
          action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>&mid_menu=1"
          id="FORMACTION">
        <?
        $tabControl->BeginNextTab();
        ?>
        <tr>
            <td colspan="2">
                <?= Loc::getMessage("STARTSEND_SMS_REGISTER_MESS") ?>
            </td>
        </tr>

        <? if (\Bitrix\Main\Config\Option::get('main', 'sms_default_service', '') != 'startsendsms') { ?>
            <tr class="heading">
                <td colspan="2">
                    <?= Loc::getMessage('STARTSEND_SMS_OPT_SETT_MAIN_MODULE_DESC') ?><br>
                    <a href="/bitrix/admin/settings.php?lang=ru&mid=main&tabControl_active_tab=tab_mail&back_url_settings="><?= Loc::getMessage('STARTSEND_SMS_OPT_SETT_MAIN_MODULE') ?></a>
                </td>
            </tr>
        <? } else { ?>
            <tr class="heading">
                <td colspan="2">
                    <?= Loc::getMessage('STARTSEND_SMS_OPT_SETT_MAIN_MODULE_DESC2') ?><br>
                    <a href="/bitrix/admin/settings.php?lang=ru&mid=main&tabControl_active_tab=tab_mail&back_url_settings="><?= Loc::getMessage('STARTSEND_SMS_OPT_SETT_MAIN_MODULE') ?></a>
                </td>
            </tr>
        <? } ?>

        <tr class="heading">
            <td colspan="2"><?= Loc::getMessage("STARTSEND_SMS_OPT_SHLUZ_MAIN") ?></td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("STARTSEND_SMS_OPT_TOKEN") ?>:</td>
            <td>
                <? $val = \Bitrix\Main\Config\Option::get($module_id, "token", "", ""); ?>
                <input type="text" size="35" maxlength="255" value="<?= $val ?>" name="token">
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("STARTSEND_SMS_OPT_API") ?>:</td>
            <td>
                <? $val = \Bitrix\Main\Config\Option::get($module_id, "api", "sms.startsend.ru", ""); ?>
                <select name="api" id="api">
                    <? foreach ($gates as $gate): ?>
                        <option value="<?= $gate ?>" <? if ($gate == $val) : echo 'selected'; endif ?>><?= $gate ?></option>
                    <? endforeach; ?>
                </select>
            </td>
        </tr>
        <tr class="heading">
            <td colspan="2"><?= Loc::getMessage("STARTSEND_SMS_OPT_BALANCE_TITLE") ?></td>
        </tr>
        <tr>
            <td><?= Loc::getMessage("STARTSEND_SMS_OPT_BALANCE_MESSAGE") ?>:</td>
            <td>
                <ul>
                    <? foreach($balance['main'] as $key=>$value): ?>
                        <li><?=$key?> : <? if (is_array($value) && isset($value['0']['balance'])) : print(number_format($value['0']['balance'],2,',', ' ')); else: print $value; endif ?></li>
                    <? endforeach; ?>
                </ul>
            </td>
        </tr>
        <? if (!$senderList->error): ?>
            <tr class="heading">
                <td colspan="2"><?= Loc::getMessage("STARTSEND_SMS_OPT_SENDER_GETTITLE") ?></td>
            </tr>

            <? if(isset($senderList['error'])): ?>
                <? $style = ' style="color:red;"'; ?>
                <tr>
                    <td colspan="2">
                        <font<?= $style ?>><?= $senderList['error']; ?></font>

                    </td>
                </tr>
            <? else: ?>
                <td><b>Alfaname:</b></td>
                <td>
                    <select name="sender" id="sender">
                        <? $val = \Bitrix\Main\Config\Option::get($module_id, "sender", "0", ""); ?>
                        <? foreach ($senderList as $code => $sender): ?>
                            <option value="<?= $code ?>" <? if ($code == $val) : echo 'selected'; endif ?>><?= $sender ?></option>
                        <? endforeach; ?>
                    </select>
                </td>
            <? endif; ?>
        <? endif; ?>
        <?
        $tabControl->BeginNextTab();
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
        $tabControl->Buttons();
        ?>
        <input <? if ($MODULE_RIGHT < "W") echo "disabled" ?> type="submit" class="adm-btn-green" name="Update"
                                                              value="<?= Loc::getMessage("STARTSEND_SMS_OPT_SEND") ?>"/>
        <input type="hidden" name="Update" value="Y"/>
        <? $tabControl->End();
        ?>
    </form>
<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>