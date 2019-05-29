<?php
/*
 * The MIT License
 *
 * Copyright 2019 Ibrahim, WebFiori Framework.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace webfiori\entity\mail;
if(!defined('ROOT_DIR')){
    header("HTTP/1.1 404 Not Found");
    die('<!DOCTYPE html><html><head><title>Not Found</title></head><body>'
    . '<h1>404 - Not Found</h1><hr><p>The requested resource was not found on the server.</p></body></html>');
}
use webfiori\entity\File;
/**
 * A class that can be used to send email messages using sockets.
 *
 * @author Ibrahim
 * @version 1.4.7
 */
class SocketMailer {
    /**
     * The priority of the message. Affects 
     * @var int
     * @since 1.4.3
     * @see https://tools.ietf.org/html/rfc4021#page-33
     */
    private $priority;
    /**
     * A constant that colds the possible values for the header 'Priority'. 
     * @since 1.4.3
     * @see https://tools.ietf.org/html/rfc4021#page-33
     */
    const PRIORITIES = array(
        -1=>'non-urgent',
        0=>'normal',
        1=>'urgent'
    );
    const NL = "\r\n";
    /**
     * The resource that is used to fire commands
     * @var resource 
     */
    private $conn;
    /**
     * A boolean that is set to true if authentication succeeded.
     * @var boolean
     * @since 1.2 
     */
    private $isLoggedIn;
    /**
     * The name of mail server host.
     * @var string 
     */
    private $host;
    /**
     * The port number.
     * @var int 
     */
    private $port;
    /**
     * Connection timeout (in minutes)
     * @var int 
     */
    private $timeout;
    /**
     * An associative array of mail receivers. Key represents 
     * receiver address and value represents his name.
     * @var array
     */
    private $receivers;
    /**
     * An associative array of mail receivers (Carbon Copy). Key represents 
     * receiver address and the value represents his name.
     * @var array 
     */
    private $cc;
    /**
     * An associative array of mail receivers (Blind Carbon Copy). Key represents 
     * receiver address and the value represents his name.
     * @var array 
     */
    private $bcc;
    /**
     * The email address of the sender.
     * @var string 
     */
    private $senderAddress;
    /**
     * The name of the sender.
     * @var string 
     */
    private $senderName;
    /**
     * The subject of the email message.
     * @var string 
     */
    private $subject;
    /**
     * If set to true, this means user is in message body writing mode.
     * @var boolean 
     */
    private $writeMode;
    /**
     * A boundary variable used to separate email message parts.
     * @var string
     * @since 1.3 
     */
    private $boundry;
    /**
     * An array that contains an objects of type 'File'. 
     * @var array 
     * @since 1.3
     */
    private $attachments;
    /**
     * The last message that was sent by email server.
     * @var string
     * @since 1.4 
     */
    private $lastResponse;
    /**
     * A boolean value that is set to true if connection uses TLS.
     * @var boolean
     * @since 1.4.1 
     */
    private $useTls;
    /**
     * A boolean value that is set to true if connection uses SSL.
     * @var boolean
     * @since 1.4.1 
     */
    private $useSsl;
    /**
     * Last received code from server after sending some command.
     * @var int 
     */
    private $lastResponseCode;
    /**
     * Creates new instance of the class.
     * @since 1.0
     */
    public function __construct() {
        $this->setTimeout(5);
        $this->receivers = array();
        $this->cc = array();
        $this->bcc = array();
        $this->setSubject('EMAIL MESSAGE');
        $this->writeMode = false;
        $this->isLoggedIn = false;
        $this->boundry = hash('sha256', date(DATE_ISO8601));
        $this->attachments = array();
        $this->lastResponse = '';
        $this->useTls = false;
        $this->setPriority(0);
        $this->lastResponseCode = 0;
    }
    /**
     * Sets the code that was the result of executing SMTP command.
     * @param string $serverResponseMessage The last message which was sent by 
     * the server after executing specific command.
     * @since 1.4.7
     */
    private function _setLastResponseCode($serverResponseMessage) {
        $firstNum = $serverResponseMessage[0];
        $firstAsInt = intval($firstNum);
        if($firstAsInt != 0){
            $secNum = $serverResponseMessage[1];
            $thirdNum = $serverResponseMessage[2];
            $this->lastResponseCode = $firstNum+(intval($secNum*10))+(intval($thirdNum)*100);
        }
    }
    /**
     * Returns last response code that was sent by SMTP server after executing 
     * specific command.
     * @return int The last response code that was sent by SMTP server after executing 
     * specific command. Default return value is 0.
     * @since 1.4.7
     */
    public function getLastResponseCode() {
        return $this->lastResponseCode;
    }
    /**
     * Sets the priority of the message.
     * @param int $priority The priority of the message. -1 for non-urgent, 0 
     * for normal and 1 for urgent. If the passed value is greater than 1, 
     * then 1 will be used. If the passed value is less than -1, then -1 is 
     * used. Other than that, 0 will be used.
     * @since 1.4.3
     */
    public function setPriority($priority){
        $asInt = intval($priority);
        if($asInt <= -1){
            $this->priority = -1;
        }
        else if($asInt >= 1){
            $this->priority = 1;
        }
        else{
            $this->priority = 0;
        }
    }
    /**
     * Returns the priority of the message.
     * @return int The priority of the message. -1 for non-urgent, 0 
     * for normal and 1 for urgent. Default value is 0.
     * @since 1.4.3
     */
    public function getPriority() {
        return $this->priority;
    }
    /**
     * Adds new attachment to the message.
     * @param File $attachment An object of type 'File' which contains all 
     * needed information about the file. It will be added only if the file 
     * exist in the path or the raw data of the file is set.
     * @return boolean If the attachment is added, the method will return true. 
     * false otherwise.
     * @since 1.3
     */
    public function addAttachment($attachment) {
        $retVal = false;
        if(class_exists('webfiori\entity\File')){
            if($attachment instanceof File){
                if(file_exists($attachment->getAbsolutePath()) || file_exists(str_replace('\\', '/', $attachment->getAbsolutePath())) || $attachment->getRawData() !== null){
                    $this->attachments[] = $attachment;
                    $retVal = true;
                }
            }
        }
        return $retVal;
    }
    /**
     * Sets or gets the value of the property 'useTls'.
     * @param boolean|null $bool true if the connection to the server will use TLS. 
     * false if not. If null is given, the property will not updated. Default 
     * is null.
     * @return boolean $bool true if the connection to the server will use TLS. 
     * false if not. Default return value is false
     * @since 1.0.1
     * @deprecated since version 1.4.6
     */
    public function isTLS($bool=null){
        if($bool !== null){
            $this->useTls = $bool === true ? true : false;
            if($this->useTls){
                $this->useSsl = false;
            }
        }
        return $this->useTls;
    }
    /**
     * Sets or gets the value of the property 'useSsl'.
     * @param boolean|null $bool true if the connection to the server will use SSL. 
     * false if not. If null is given, the property will not updated. Default 
     * is null.
     * @return boolean $bool true if the connection to the server will use SSL. 
     * false if not. Default return value is false
     * @since 1.0.1
     * @deprecated since version 1.4.6
     */
    public function isSSL($bool=null){
        if($bool !== null){
            $this->useSsl = $bool === true ? true : false;
            if($this->useSsl){
                $this->useTls = false;
            }
        }
        return $this->useSsl;
    }
    /**
     * Checks if the user is logged in to mail server or not.
     * @return boolean The method will return true if the user is 
     * logged in to the mail server. false if not.
     * @since 1.2
     */
    public function isLoggedIn() {
        return $this->isLoggedIn;
    }
    /**
     * Authenticate the user given email server username and password. 
     * Note that Authentication 
     * must be done after connecting to the server. 
     * The user might not be logged 
     * in in 3 cases:
     * <ul>
     * <li>If the mailer is not connected to the email server.</li>
     * <li>If the sender address is not set.</li>
     * <li>If the given username and password are incorrect.</li>
     * </ul>
     * @param string $username The email server username.
     * @param string $password The user password.
     * @return boolean The method will return true if the user is 
     * logged in to the mail server. false if not.
     * @since 1.2
     */
    public function login($username,$password) {
        if($this->isConnected()){
            if(strlen($this->getSenderAddress()) != 0){
                $this->sendC('AUTH LOGIN');
                $this->sendC(base64_encode($username));
                $this->sendC(base64_encode($password));
                if($this->getLastLogMessage() == '535 Incorrect authentication data'){
                    return false;
                }
                //a command to check if authentication is done
                $this->sendC('MAIL FROM: <'.$this->getSenderAddress().'>');

                if($this->getLastLogMessage() == '235 Authentication succeeded' || $this->getLastLogMessage() == '250 OK'){
                    $this->isLoggedIn = true;
                }
                else{
                    $this->isLoggedIn = false;
                }
            }
        }
        return $this->isLoggedIn;
    }
    /**
     * Returns the last logged message after executing some command.
     * @return string The last logged message after executing some command. Default 
     * value is empty string.
     * @since 1.2
     */
    public function getLastLogMessage(){
        return $this->lastResponse;
    }
    /**
     * Sets the subject of the message.
     * @param string $subject Email subject.
     * @since 1.0
     */
    public function setSubject($subject){
        $trimmed = trim($subject);
        if(strlen($trimmed) > 0){
            $this->subject = $trimmed;
        }
    }
    /**
     * Sets the name and the address of the sender.
     * @param string $name The name of the sender.
     * @param string $address The email address of the sender.
     * @since 1.0
     */
    public function setSender($name, $address){
        $this->senderName = $name;
        $this->senderAddress = $address;
    }
    /**
     * Adds new receiver or updates an existing one.
     * @param string $name The name of the email receiver (such as 'Ibrahim'). It 
     * must be non-empty string.
     * @param string $address The email address of the receiver. It must be 
     * non-empty string.
     * @param boolean $isCC If set to true, the receiver will receive 
     * a carbon copy (CC) of the message. Default is false.
     * @param boolean $isBcc If set to true, the receiver will receive 
     * a blind carbon copy (BCC) of the message. This will override the option $isCC. Default 
     * is false.
     * @since 1.0
     */
    public function addReceiver($name, $address, $isCC=false, $isBcc=false){
        $nameTrimmed = trim($name);
        if(strlen($nameTrimmed) != 0){
            $addressTrimmed = trim($address);
            if(strlen($addressTrimmed) != 0){
                if($isBcc){
                    $this->bcc[$addressTrimmed] = $nameTrimmed;
                }
                else if($isCC){
                    $this->cc[$addressTrimmed] = $nameTrimmed;
                }
                else{
                    $this->receivers[$addressTrimmed] = $nameTrimmed;
                }
                return true;
            }
        }
        return false;
    }
    /**
     * Checks if the mailer is in message writing mode or not.
     * @return boolean true if the mailer is in writing mode. The 
     * mailer will only switch to writing mode after sending the command 'DATA'.
     * @since 1.1
     */
    public function isInWritingMode(){
        return $this->writeMode;
    }
    /**
     * Returns the name of message sender.
     * @return string The name of the sender.
     * @since 1.1
     */
    public function getSenderName(){
        return $this->senderName;
    }
    /**
     * Returns the email address of the sender.
     * @return string The email address of the sender.
     * @since 1.1
     */
    public function getSenderAddress(){
        return $this->senderAddress;
    }
    /**
     * Write a message to the buffer.
     * Note that this method will trim the following character from the string 
     * if they are found in the message: '\t\n\r\0\x0B\0x1B\0x0C'.
     * @param string $msg The message to write. 
     * @param boolean $sendMessage If set to true, The connection will be closed and the 
     * message will be sent.
     * @since 1.0
     */
    public function write($msg,$sendMessage=false){
        if($this->isInWritingMode()){
            $this->sendC(trim($msg,"\t\n\r\0\x0B\0x1B\0x0C"));
            if($sendMessage === true){
                $this->_appendAttachments();
                $this->sendC(self::NL.'.');
                $this->sendC('QUIT');
            }
        }
        else{
            if(strlen($this->getSenderAddress()) != 0){
                foreach ($this->receivers as $address => $name){
                    $this->sendC('RCPT TO: <'.$address.'>');
                }
                foreach ($this->cc as $address => $name){
                    $this->sendC('RCPT TO: <'.$address.'>');
                }
                foreach ($this->bcc as $address => $name){
                    $this->sendC('RCPT TO: <'.$address.'>');
                }
                $this->sendC('DATA');
                $priorityAsInt = $this->getPriority();
                $priorityHeaderVal = self::PRIORITIES[$priorityAsInt];
                if($priorityAsInt == -1){
                    $importanceHeaderVal = 'low';
                }
                else if($priorityAsInt == 1){
                    $importanceHeaderVal = 'High';
                }
                else{
                    $importanceHeaderVal = 'normal';
                }
                $this->sendC('Priority: '.$priorityHeaderVal);
                $this->sendC('Content-Transfer-Encoding: quoted-printable');
                $this->sendC('Importance: '.$importanceHeaderVal);
                $this->sendC('From: "'.$this->getSenderName().'" <'.$this->getSenderAddress().'>');
                $this->sendC('To: '.$this->getReceiversStr());
                $this->sendC('CC: '.$this->getCCStr());
                $this->sendC('BCC: '.$this->getBCCStr());
                $this->sendC('Date:'. date('r (T)'));
                $this->sendC('Subject:'. $this->subject);
                $this->sendC('MIME-Version: 1.0');
                $this->sendC('Content-Type: multipart/mixed; boundary="'.$this->boundry.'"'.self::NL);
                $this->sendC('--'.$this->boundry);
                $this->sendC('Content-Type: text/html; charset="UTF-8"'.self::NL);
                $this->sendC(trim($msg,"\t\n\r\0\x0B\0x1B\0x0C"));
                if($sendMessage === true){
                    $this->_appendAttachments();
                    $this->sendC(self::NL.'.');
                    $this->sendC('QUIT');
                }
            }
        }
    }
    /**
     * A method that is used to include email attachments.
     * @since 1.3
     */
    private function _appendAttachments(){
        if(count($this->attachments) != 0){
            foreach ($this->attachments as $file){
                if($file->getRawData() === null){
                    $file->read();
                }
                $content = $file->getRawData();
                $contentChunk = chunk_split(base64_encode($content));
                $this->sendC('--'.$this->boundry);
                $this->sendC('Content-Type: '.$file->getFileMIMEType().'; name="'.$file->getName().'"');
                $this->sendC('Content-Transfer-Encoding: base64');
                $this->sendC('Content-Disposition: attachment; filename="'.$file->getName().'"'.self::NL);
                $this->sendC($contentChunk);
            }
            $this->sendC('--'.$this->boundry.'--');
        }
    }
    /**
     * Returns an associative array that contains the names and the addresses 
     * of message receivers.
     * The indices of the array will act as the addresses of the receivers and 
     * the value of each index will contain the name of the receiver. The array 
     * will only contain the addresses of the people who will receive an original 
     * copy of the message.
     * @return array An array that contains receivers information.
     * @since 1.4.4
     */
    public function getReceivers() {
        return $this->receivers;
    }
    /**
     * Returns an associative array that contains the names and the addresses 
     * of people who will receive a blind carbon copy of the message.
     * The indices of the array will act as the addresses of the receivers and 
     * the value of each index will contain the name of the receiver.
     * @return array An array that contains receivers information.
     * @since 1.4.4
     */
    public function getBCC(){
        return $this->bcc;
    }
    /**
     * Returns an associative array that contains the names and the addresses 
     * of people who will receive a carbon copy of the message.
     * The indices of the array will act as the addresses of the receivers and 
     * the value of each index will contain the name of the receiver.
     * @return array An array that contains receivers information.
     * @since 1.4.4
     */
    public function getCC(){
        return $this->cc;
    }
    /**
     * Returns a string that contains the names and the addresses 
     * of people who will receive a blind carbon copy of the message.
     * The format of the string will be as follows:
     * <p>NAME_1 &lt;ADDRESS_1&gt;, NAME_2 &lt;ADDRESS_2&gt; ...</p>
     * @return string A string that contains receivers information.
     * @since 1.0
     */
    public function getBCCStr(){
        $arr = array();
        foreach ($this->bcc as $address => $name){
            array_push($arr, $name.' <'.$address.'>');
        }
        return implode(',', $arr);
    }
    /**
     * Returns a string that contains the names and the addresses 
     * of people who will receive a carbon copy of the message.
     * The format of the string will be as follows:
     * <p>NAME_1 &lt;ADDRESS_1&gt;, NAME_2 &lt;ADDRESS_2&gt; ...</p>
     * @return string A string that contains receivers information.
     * @since 1.0
     */
    public function getCCStr(){
        $arr = array();
        foreach ($this->cc as $address => $name){
            array_push($arr, $name.' <'.$address.'>');
        }
        return implode(',', $arr);
    }
    /**
     * Returns a string that contains the names and the addresses 
     * of people who will receive an original copy of the message.
     * The format of the string will be as follows:
     * <p>NAME_1 &lt;ADDRESS_1&gt;, NAME_2 &lt;ADDRESS_2&gt; ...</p>
     * @return string A string that contains receivers information.
     * @since 1.0
     */
    public function getReceiversStr(){
        $arr = array();
        foreach ($this->receivers as $address => $name){
            array_push($arr, $name.' <'.$address.'>');
        }
        return implode(',', $arr);
    }
    /**
     * Checks if the connection is still open or is it closed.
     * @return boolean true if the connection is open.
     * @since 1.0
     */
    public function isConnected() {
        return is_resource($this->conn);
    }
    /**
     * Sets the connection port.
     * @param int $port The port number to set.
     * @since 1.0
     */
    public function setPort($port) {
        if($port > 0){
            $this->port = $port;
        }
    }
    /**
     * Returns the time at which the connection will timeout if no response 
     * was received in minutes.
     * @return int Timeout time in minutes.
     * @since 1.0
     */
    public function getTimeout(){
        return $this->timeout;
    }

    /**
     * Sets the name of mail server host.
     * @param string $host The name of the host (such as mail.mysite.com).
     * @since 1.0
     */
    public function setHost($host){
        $this->host = trim($host);
    }
    /**
     * Sends a command to the mail server.
     * @param string $command Any SMTP command.
     * @return boolean The method will return always true if the command was 
     * sent. The only case that the method will return false is when it is not 
     * connected to the server.
     * @since 1.0
     */
    public function sendC($command){
        if($this->isConnected()){
            if($this->isInWritingMode()){
                fwrite($this->conn, $command.self::NL);
                if($command == self::NL.'.'){
                    $this->writeMode = false;
                }
            }
            else{
                fwrite($this->conn, $command.self::NL);
                $response = trim($this->read());
                $this->lastResponse = $response;
                if($command == 'DATA'){
                    $this->writeMode = true;
                }
            }
            return true;
        }
        else{
            return false;
        }
    }
    /**
     * Read server response after sending a command to the server.
     * @return string
     * @since 1.0
     */
    public function read(){
        $message = '';
        while(!feof($this->conn)){
            $str = fgets($this->conn);
            $message .= $str;
            if (!isset($str[3]) or (isset($str[3]) and $str[3] == ' ')) {
                break;
            }
        }
        $this->_setLastResponseCode($message);
        return $message;
    }
    /**
     * Connect to the mail server.
     * Before calling this method, the developer must make sure that he set 
     * connection information correctly (server address and port number).
     * @return boolean true if the connection established or already 
     * connected. false if not. Once the connection is established, the 
     * method will send the command 'EHLO' to the server. 
     * @since 1.0
     */
    public function connect() {
        $retVal = true;
        if(!$this->isConnected()){
            set_error_handler(function(){});
//            Logger::log('Checking if SSL or TLS will be used...');
            $port = $this->port;
//            if($port == 465){
//                Logger::log('SSL will be used.');
//            }
//            else if($port == 587){
//                Logger::log('TLS will be used.');
//            }
            $err = 0;
            $errStr = '';
            //$protocol = $port == 465 ? "ssl://" : '';
            if(function_exists('stream_socket_client')){
                $context = stream_context_create (array(
                    'ssl'=>array(
                        'verify_peer'=>false,
                        'verify_peer_name'=>false,
                        'allow_self_signed'=>true
                    )
                ));
                $this->conn = stream_socket_client($this->host.':'.$port, $err, $errStr, $this->timeout*60, STREAM_CLIENT_CONNECT, $context);
            }
            else{
                $this->conn = fsockopen($this->host, $port, $err, $errStr, $this->timeout*60);
            }
            set_error_handler(null);
            if(is_resource($this->conn)){
                $response = $this->read();
                if($this->sendC('EHLO '.$this->host)){
                    $retVal = true;
                    if($port == 587){
                        //Logger::log('Using TLS. Sending the command \'STARTTLS\'.');
//                        if($this->sendC('STARTTLS')){
//                            $retVal = stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
//                            if($retVal === true){
//                                Logger::log('Secure connection enabled.');
//                                $this->sendC('EHLO '.$this->host);
//                            }
//                            else{
//                                Logger::log('Unable to make secure connection.','error');
//                            }
//                        }
//                        else{
//                            Logger::log('Error while sending the command \'STARTTLS\'.','error');
//                        }
                    }
                    else if($port == 465){
//                        Logger::log('SSL will be used.');
//                        $retVal = stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
//                        if($retVal === true){
//                            Logger::log('Secure connection enabled.');
//                            $this->sendC('EHLO '.$this->host);
//                        }
//                        else{
//                            Logger::log('Unable to make secure connection.','error');
//                        }
                    }
                    else{
                        //Logger::log('No secure connection will be used.');
                        //$retVal = true;
                    }
                }
                
            }
            else{
                $retVal = false;
            }
        }
        return $retVal;
    }
    /**
     * Sets the timeout time of the connection.
     * @param int $val The value of timeout (in minutes). The timeout will be updated 
     * only if the connection is not yet established and the given value is grater 
     * than 0.
     */
    public function setTimeout($val) {
        if($val >= 1 && !$this->isConnected()){
            $this->timeout = $val;
        }
    }
}
