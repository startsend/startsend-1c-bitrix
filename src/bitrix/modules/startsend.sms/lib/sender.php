<?php

namespace Startsend\Sms;

use Bitrix\Main\Localization\Loc;
use Events;

class Sender
{

    protected $transport = null;
    public $translit = null;
    public $event = null;
    public $eventName = null;

    /**
     * Sender constructor.
     * @param bool $defaultTransportInterface
     * @param array $params
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    function __construct($defaultTransportInterface = true, $params = array())
    {

        if ($defaultTransportInterface) {

            //основной шлюз
            $classname = \Bitrix\Main\Config\Option::get("startsend.sms", "transport", "main", "");
            if ($classname) {
                if (strpos($classname, '.php') !== false) $classname = str_replace(".php", "", $classname);
                $classname = ToUpper(substr($classname, 0, 1)) . substr($classname, 1);
                $classname = "\\Startsend\\Sms\\Transport\\" . $classname;
                $api = \Bitrix\Main\Config\Option::get("startsend.sms", "api", "sms.startsend.ru", "");
                $paramsAr = array(
                    'token' => ($api ? $api . '||' : '') . \Bitrix\Main\Config\Option::get("startsend.sms", "token", ""),
                    'sender' => \Bitrix\Main\Config\Option::get("startsend.sms", "sender", "")
                );
                if (!$paramsAr['sender']) $paramsAr['sender'] = '.';
                $this->setTransport($classname, $paramsAr);
            }

        }
        $this->event = 'DEFAULT';

    }


    /**
     * Get getes list
     * @return string[]
     */
    public static function getGatesList()
    {
        return [
            'https://app.startsend.ru/',
            'https://app.sms.by',
        ];
    }

    public function setTransport($transport, $params)
    {
        if (!class_exists($transport)) return false;
        $this->transport = new $transport($params);
    }

    /**
     * Метод для проверки номера телефона
     * @param string $phone номер телефона для проверки
     * @param boolean $all необязательный параметр по умолчанию true (весь мир), false (только снг)
     * @return array                phone - номер без мусора, check - результат проверки(boolean)
     */
    public function checkPhoneNumber($phone, $all = true)
    {

        //очистка от лишнего мусора
        $phoneFormat = '+' . preg_replace("/[^0-9A-Za-z]/", "", $phone);

        //проверка номера мир
        $pattern_world = "/^\+?([87](?!95[4-79]|99[^2457]|907|94[^0]|336|986)([348]\d|9[0-689]|7[0247])\d{8}|[1246]\d{9,13}|68\d{7}|5[1-46-9]\d{8,12}|55[1-9]\d{9}|55119\d{8}|500[56]\d{4}|5016\d{6}|5068\d{7}|502[45]\d{7}|5037\d{7}|50[457]\d{8}|50855\d{4}|509[34]\d{7}|376\d{6}|855\d{8}|856\d{10}|85[0-4789]\d{8,10}|8[68]\d{10,11}|8[14]\d{10}|82\d{9,10}|852\d{8}|90\d{10}|96(0[79]|17[01]|13)\d{6}|96[23]\d{9}|964\d{10}|96(5[69]|89)\d{7}|96(65|77)\d{8}|92[023]\d{9}|91[1879]\d{9}|9[34]7\d{8}|959\d{7}|989\d{9}|97\d{8,12}|99[^4568]\d{7,11}|994\d{9}|9955\d{8}|996[57]\d{8}|9989\d{8}|380[34569]\d{8}|381\d{9}|385\d{8,9}|375[234]\d{8}|372\d{7,8}|37[0-4]\d{8}|37[6-9]\d{7,11}|30[69]\d{9}|34[67]\d{8}|3[12359]\d{8,12}|36\d{9}|38[1679]\d{8}|382\d{8,9})$/";
        //проверка номера снг
        $pattern_sng = "/^((\+?7|8)(?!95[4-79]|99[^2457]|907|94[^0]|336)([348]\d|9[0-689]|7[07])\d{8}|\+?(99[^456]\d{7,11}|994\d{9}|9955\d{8}|996[57]\d{8}|380[34569]\d{8}|375[234]\d{8}|372\d{7,8}|37[0-4]\d{8}))$/";

        if ($all) {
            $patt = $pattern_world;
        } else {
            $patt = $pattern_sng;
        }

        if (!preg_match($patt, $phoneFormat)) {
            return array('phone' => $phoneFormat, 'check' => false);
        }

        return array('phone' => $phoneFormat, 'check' => true);

    }

    /**
     * Sending SMS, post sending, adding a record to the SMS history
     * @param $phones
     * @param $MEWSS
     * @param int $time
     * @param false $sender
     * @param string $prim
     * @param bool $addHistory
     * @param false $update
     * @param false $error
     * @return mixed|\stdClass
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function sendSms($phones, $MEWSS, $time = 0, $sender = false, $prim = '', $addHistory = true, $update = false, $error = false)
    {

        $smsId = '-';
        $smsStatus = 'ERROR';

        $addInfo = $this->event;
        $msgRu = Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_STATUSUPDATE");
        if ($msgRu && ($msgRu == $this->eventName)) $addInfo = 'ISMS_STATUSUPDATE';
        $msgRu = Loc::getMessage("STARTSEND_SMS_EVENTCODE_ISMS_NEWORDER");
        if ($msgRu && ($msgRu == $this->eventName)) $addInfo = 'ISMS_STATUSUPDATE';

        $arParamsTranslit = array("max_len" => "1000", "change_case" => "false", "replace_space" => " ", "replace_other" => "", "", "delete_repeat_replace" => false,
            "safe_chars" => '$%&*()_+=-#@!\'"./\\,<>?;:|~`№');
        if ($this->translit === true) $MEWSS = \CUtil::translit($MEWSS, "ru", $arParamsTranslit);

        if (($time == 0 || $time < time()) && !$error) {

            if ($this->transport === null) {
                $send = new \stdClass();
                $send->error = 'Transport not found';
                $send->error_code = '9998';
            } else {

                foreach (explode(',', $phones) as $phone) {
                    $send = $this->transport->_sendSms($phone, $MEWSS, $time, $sender, $addInfo);
                }
                $smsId = isset($send['sms_id']) ? $send['sms_id'] : '-';
                $smsStatus = isset($send['status']) ? $send['status'] : 'ERROR';
            }
        }

        if ($addHistory) {

            if (!$sender) {
                $sender = \Bitrix\Main\Config\Option::get("startsend.sms", "sender", "DEFAULT", "");
            }

            $arFields = array(
                'SMSID' => $smsId,
                'SENDER' => $sender,
                'PHONE' => $phones,
                'TIME_SEND' => \Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                'TIME_STATE' => null,
                'MESS' => $MEWSS,
                'PRIM' => $prim,
                'STATUS' => $smsStatus,
                'EVENT' => $this->event,
                'EVENT_NAME' => $this->eventName
            );

            $res = \Startsend\Sms\ListTable::add($arFields);

            if (!$error) {
                return $send;
            } else {
                return $error['send'];
            }
        }

        if ($update) {

            if ($error) {
                $status = $error['status'];
                $sendid = '-';
            } else {
                $status = 2;
                $sendid = $send->id;
            }
            $arData = array(
                "STATUS" => $status,
                "SMSID" => $sendid
            );
            \Startsend\Sms\ListTable::update(array("ID" => $update['id']), $arData);
        }

        if (!$error) {
            return $send;
        } else {
            return $error['send'];
        }

    }


    /**
     * Get Balance
     * @return false[]
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function getBalance()
    {

        $arBalance = array(
            'main' => $this->getBalanceTransport(true),
        );

        return $arBalance;

    }

    //

    /**
     * Method gets senders + caches response
     * @param bool $main
     * @return false
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function getAllSender($main = true)
    {
        if ($this->transport === null) return false;
        return $this->transport->_getAllSender();
    }

    /**
     * Method for getting balance + response caching
     * @param bool $main
     * @return false
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    private function getBalanceTransport($main = true)
    {

        if ($this->transport === null) return false;

        $obCache = \Bitrix\Main\Data\Cache::createInstance();
        $cache_time = \Bitrix\Main\Config\Option::get("startsend.sms", "cachebalance", "120", "");
        $cache_id = 'balance.' . $this->transport_name;

        if ($obCache->initCache($cache_time, $cache_id, "/incoredev/sms/admin/")) {
            $vars = $obCache->GetVars();
        } elseif ($obCache->startDataCache()) {
            $vars = $this->transport->_getBalance();

            if (!$vars->error) {
                $obCache->endDataCache($vars);
            } else {
                $obCache->abortDataCache();
            }
        }
        return $vars;
    }


    /**
     * Get a list of unsent SMS and send them
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getTurnSms()
    {

        $arFilter = array();
        $arFilter["STATUS"] = 1;
        $arFilter["<TIME_SEND"] = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());

        $res = \Startsend\Sms\ListTable::getList(
            array(
                'select' => array('ID', 'SENDER', 'PHONE', 'MESS', 'EVENT', 'EVENT_NAME'),
                'filter' => array($arFilter),
                'limit' => \Bitrix\Main\Config\Option::get("startsend.sms", "limitsms", 10, ""),
                'order' => array("SORT" => "DESC")
            )
        );
        while ($data = $res->fetch()) {
            usleep(100000); //на всякий случай не более 10 запросов в секунду
            $this->event = $data['EVENT'];
            $this->eventName = $data['EVENT_NAME'];
            $send = $this->sendSms($data['PHONE'], $data['MESS'], 0, $data['SENDER'], '', false, array('id' => $data['ID']));
        }

    }

    //получаем необновленные статусы и обновляем
    public function getStatusSms()
    {

        $arFilter = array();
        $arFilter["STATUS"] = array(0, 2, 3, 6);

        $res = \Startsend\Sms\ListTable::getList(
            array(
                'select' => array('ID', 'PHONE', 'SMSID'),
                'filter' => array($arFilter),
                'limit' => (\Bitrix\Main\Config\Option::get("startsend.sms", "limitsms", 10, "") * 4)
            )
        );

        $arAll = array();
        while ($data = $res->fetch()) {
            $arAll[] = $data;
        }

        if (!empty($arAll)) {

            //получаем статус со шлюза
            $allResp = $this->transport->_getStatusSms($arAll);
            foreach ($allResp as $resp) {
                //если нет ошибок обновляем в базе
                if (!$resp->error_code && in_array(intval($resp->status), array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12))) {
                    if (!$resp->last_timestamp) $resp->last_timestamp = time();
                    \Startsend\Sms\ListTable::update(array("ID" => $resp->baseId), array("STATUS" => $resp->status, "TIME_STATE" => \Bitrix\Main\Type\DateTime::createFromTimestamp($resp->last_timestamp)));
                } else {
                    if (!$resp->last_timestamp) $resp->last_timestamp = time();
                    \Startsend\Sms\ListTable::update(array("ID" => $resp->baseId), array("STATUS" => 12, "TIME_STATE" => \Bitrix\Main\Type\DateTime::createFromTimestamp($resp->last_timestamp)));
                }
            }

        }

    }

}