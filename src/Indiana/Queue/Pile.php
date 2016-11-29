<?php

/**
 *
 *
 *
 *
 *
 *
 *
 *
 * 
 */

namespace Indiana\Queue;

use Aws\Sqs\SqsClient;
use Respect\Validation\Validator as v;

class Pile
{
	/**
	 * Default AWS SQS API version
	 * @var String
	 */
	private $sqsApiVersion = 'latest';

	/**
	 * Default AWS region
	 * @var String
	 */
	private $awsRegion = 'us-east-1';

	/**
	 * Delay seconds to make message visible in the queue named
	 * @var integer
	 */
	private $delaySeconds = 5;

	/**
	 * 
	 * @var string
	 */
	private $messageId = "";

	/**
	 * Message body to send to the queue.
	 * This is required by the queue, so we need to pass some String value to it.
	 * This var is limited of 256kbps
	 * @var String
	 */
	private $messageBody = 'Empty';

	/**
	 * Object to increment all attributes to send to the queue
	 * This var is limited by 10 attributes
	 * @var Array
	 */
	private $messageAttributes = array();

	/**
	 * Queue name
	 * @var String
	 */
	private $queueName = '';

	/**
	 * URL queue
	 * @var String
	 */
	private $queueUrl = '';

	/**
	 * All configured object queue to send
	 * @var array
	 */
	private $queueObjToSend = array();

	/**
	 * Verifiy te type of DataType and set String or Number
	 * @return Array 	Data will be increased in messageAttributes var
 	 */
	private function populateMsgAttr($attrName, $attrValue)
	{

		if(v::stringType()->notEmpty()->validate($attrValue)){
			$attrTypeValidated = "String";
			$result = $this->addMessageAttribute($attrName,$attrValue,$attrTypeValidated);
		}elseif(v::intType()->notEmpty()->validate($attrValue)){
			$attrTypeValidated = "Number";	
			$result = $this->addMessageAttribute($attrName,$attrValue,$attrTypeValidated);
		}else{
			throw new RuntimeException("Invalid attribute attrType for \'$attrType\'setted.");
		}
		return $result;
	}

	/**
	 * Each interation will be increased in messageAttributes
	 * @param  String      		Must be String
	 * @param  String,Integer  	Must be String or Integer
	 * @param  String  			String with values "string" or "number"
	 * @return Array 			Returns array to be setted on message attribute
	 */
	private function addMessageAttribute($attrName,$attrValue,$attrTypeValidated)
	{
		if(array_key_exists($attrName, $this->messageAttributes)){
			throw new RuntimeException("Invalid attribute \'$attrName\' for messageAttributes array. Key name already setted!");
		}
		$this->messageAttributes[$attrName] = [
			"StringValue" =>$attrValue, 
			"DataType" => $attrTypeValidated
		];
		return $this->messageAttributes; 
	}

	/**
	 * Get url for the queue named
	 * @return Void
	 */
	private function getQueueUrl()
	{
		$sqs = $this->getSqsClient();
		$url = $sqs->getQueueUrl(
			array('QueueName' => $this->queueName)
		);
		$this->queueUrl = $url->get('QueueUrl');
		return $this;
	}

	/**
	 * Configuring SQS object to send
	 * Ps: MessageBody is required by the queue, so if wasn't setted, we configure it with default
	 * @return Void
	 */
	private function configSqsObj()
	{
		if(!v::stringType()->notEmpty()->validate($this->queueUrl)){
			throw new RuntimeException("Invalid queueUrl.  Paramenter not setted!");
		}

		$this->queueObjToSend = array(
			"QueueUrl"=> $this->queueUrl,
			"MessageBody" => $this->messageBody,
			"DelaySeconds" => $this->delaySeconds,
			'MessageAttributes' => $this->messageAttributes
		);
	}

	/**
	 * Prepare SqsClient and return it
	 * @return Object Object for Aws\Sqs\SqsClient
	 */
	private function getSqsClient()
	{
		return new SqsClient(
			array(
				'version' => $this->sqsApiVersion,
			    'region'  => $this->awsRegion
			)
		);
	}

	/**
	 * Set queue name
	 * @param String 	Queue name will be setted here
	 * @return Object 	Itself
	 */
	public function setSqsApiVerison($sqsApiVersion)
	{
		if(v::stringType()->notEmpty()->validate($sqsApiVersion)){
			$this->sqsApiVersion = $sqsApiVersion;
		} else {
			throw new RuntimeException("Invalid string value to sqsApiVersion parameter.");
		}
		return $this;
	}

	/**
	 * Set aws region name
	 * @param String 	AWS Region name
	 * @return Object 	Itself.
	 */
	public function setAwsRegion($region)
	{
		if(v::stringType()->notEmpty()->validate($region)){
			$this->awsRegion = $region;
		} else {
			throw new RuntimeException("Invalid string value to region parameter.");
		}
		return $this;
	}

	/**
	 * Set aws region name
	 * @param Integer 	AWS Region name
	 * @return Object 	Itself.
	 */
	public function setDelaySeconds($delaySeconds)
	{
		if(v::intType()->notEmpty()->validate($delaySeconds)){
			$this->delaySeconds = $delaySeconds;
		} else {
			throw new RuntimeException("Invalid integer value to delaySeconds parameter.");
		}
		return $this;
	}
	
	/**
	 * Set queue name
	 * @param String 	Queue name will be setted here
	 */
	public function setQueueName($queueName)
	{
		if(v::stringType()->notEmpty()->validate($queueName)){
			$this->queueName = $queueName;
		} else {
			throw new RuntimeException("Invalid string value to queueName parameter.");
		}
		return $this;
	}

	/**
	 * Validate paramters before insert to the queue
	 * @param  String 			Only accepts string
	 * @param  String,Integer  	Only accepts string on integer
	 * @return Object 			Itself
	 */
	public function setAttr($name, $value)
	{		
		if(v::stringType()->notEmpty()->validate($name) && v::notEmpty()->validate($value)){
			if(v::stringType()->validate($value)){
				$this->populateMsgAttr($name, $value);	
			}else if(v::intType()->intVal()->validate($value)){
				$this->populateMsgAttr($name, $value);
			}else{
				throw new RuntimeException("Invalid attribute name for $name: $value setted.");
			}
		}else{
			throw new RuntimeException("Invalid attributes name and value. Was setted: '$name' and '$value'");
		}
		return $this;
	}


	public function configSqsBatch(){

		if(!v::stringType()->notEmpty()->validate($this->queueUrl)){
			throw new RuntimeException("Invalid queueUrl.  Paramenter not setted!");
		}
		
					$this->messageAttributes;
	}

	public function setBatchMessage(){
	
		$this->getQueueUrl()
		 ->configSqsBatch();
	}

	/**
	 * Construct the array to be setted and sended by aws
	 * @return [type] [description]
	 */
	public function configBatch(){
		$idmd5 = md5($this->messageId = rand(10,100)); 

		$this->queueObjToSendBatch = array(
			"QueueUrl"=> $this->queueUrl,
			"Entries" => array(
					array(
					"Id" => $idmd5,
					"MessageBody" => $this->messageBody,
					"DelaySeconds" => $this->delaySeconds,
					"MessageAttributes" => $this->messageAttributes)
			));
		return $this->queueObjToSendBatch;
	}

	/**
	 * Send message attributes and message body to queue up to 10 attributtes
	 * @return Object returned by Aws\Sqs\SqsClient sendMessage method
	 */
	public function sendBatch(){

		$batch = $this->configBatch();	
		
		$sqs      = $this->getSqsClient();
		$callback = $sqs->sendMessageBatch($this->queueObjToSendBatch);

		return $callback;	
	}
	/**
	 * Send message attributes and message body to the queue named
	 * @return Object 	Object returned by Aws\Sqs\SqsClient sendMessage method
	 */
	public function send(){
		$teste = $this->getQueueUrl()
			->configSqsObj();

		$sqs      = $this->getSqsClient();
		$callback = $sqs->sendMessage($this->queueObjToSend);

		return $callback;
	}

}
