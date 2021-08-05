<?php
namespace Startsend\Sms;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class ListTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'startsend_sms_history';
	}

	public static function getMap()
	{
		return array(
			new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				)
			),
			new Entity\StringField('SMSID', array(
				'required' => false,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(null, 100),
					);
				}
				)
			),
			new Entity\StringField('SENDER', array(
				'required' => false,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(null, 50),
					);
				}
				)
			),
			new Entity\StringField('PHONE', array(
				'required' => false,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(null, 20),
					);
				}
				)
			),
			new Entity\DateTimeField('TIME_SEND', array(
				'required' => true
				)
			),
			new Entity\DateTimeField('TIME_STATE', array(
				'required' => false
				)
			),
			new Entity\StringField('MESS', array(
				'required' => true,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(null, 655),
					);
				}
				)
			),
			new Entity\StringField('PRIM', array(
				'required' => false,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(null, 655),
					);
				}
				)
			),
			new Entity\IntegerField('STATUS', array(
				'required' => true,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(null, 20),
					);
				}
				)
			),
			new Entity\StringField('EVENT', array(
				'required' => false,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(null, 100),
					);
				}
				)
			),
			new Entity\StringField('EVENT_NAME', array(
				'required' => false,
				'validation' => function(){
					return array(
						new Entity\Validator\Length(null, 100),
					);
				}
				)
			),
			new Entity\IntegerField('SORT', array(
				'required' => false
				)
			)
		);
	}
	
}