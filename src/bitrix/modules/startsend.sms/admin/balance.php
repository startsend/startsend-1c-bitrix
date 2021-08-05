<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$module_id = "startsend.sms";
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if (!($MODULE_RIGHT >= "R"))
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$APPLICATION->SetTitle(Loc::getMessage("STARTSEND_SMS_BALANCE_TITLE"));

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
$APPLICATION->SetAdditionalCSS("/bitrix/css/" . $module_id . "/style.css");

\Bitrix\Main\Loader::includeModule($module_id);
$smsOb = new \Startsend\Sms\Sender();
$balance = $smsOb->getBalance();

?>

<? if ($balance['main']->error) { ?>
    <div class="balance"><font
                style="color:red;"><?= Loc::getMessage("STARTSEND_SMS_OPT_ERROR1") ?> <?= $balance['main']->error ?>
            .</font><br/><br/>
        <a href="/bitrix/admin/settings.php?mid=startsend.sms&lang=ru&mid_menu=1"><?= Loc::getMessage("STARTSEND_SMS_SETTINGS") ?></a>
    </div>
<? } else { ?>
    <div class="balance">

        <? if (isset($balance['main']['result'])): ?>
            <font style="color:green;">
                <?= Loc::getMessage("STARTSEND_SMS_OPT_BALANCE") ?>
                : <?= number_format($balance['main']['result'][0]['balance'], 2, ',', ' ') ?> <?= $balance['main']['currency'] ?></font>
        <? elseif (isset($balance['main']['error'])): ?>
            <font style="color:red;"><?= $balance['main']['error']; ?></font>
        <? endif; ?>
        <br/>
        <br/>
        <input type="button" name="submit_button" onclick="window.open('https://app.startsend.ru/login')"
               value="<?= Loc::getMessage("STARTSEND_SMS_OPT_BALANCE_ADD") ?>">
    </div>
<? } ?>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>