<?php
namespace Startsend\Sms;

class Agent {
	
	function turnSms() {
		$ob = new \Startsend\Sms\Sender();
		$ob->getTurnSms();
		return '\\Startsend\\Sms\\Agent::turnSms();';
	}

	function statusSms() {
		$ob = new \Startsend\Sms\Sender();
		$ob->getStatusSms();
		return '\\Startsend\\Sms\\Agent::statusSms();';
	}
	
}