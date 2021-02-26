<?php
/*
 * The MIT License
 *
 * Copyright 2020 Ibrahim, WebFiori Framework.
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
namespace webfiori\framework\ui;

use Throwable;
use webfiori\framework\Page;
use webfiori\framework\session\SessionsManager;
use webfiori\framework\Util;
use webfiori\framework\WebFioriApp;
use webfiori\http\Response;
use webfiori\ui\HTMLNode;
/**
 * A page which is used to display exception information when it is thrown or 
 * any other errors.
 *
 * @author Ibrahim
 * @version 1.0.1
 */
class ServerErrView {
    /**
     *
     * @var Throwable|Error
     * @since 1.0 
     */
    private $errOrThrowable;
    /**
     * Creates a new instance of the class.
     * @param Throwable|array $throwableOrErr This can be an instance of the 
     * interface 'Throwable' or it can be an array that contains error information 
     * which was returned from the method 'error_get_last()'.
     * @since 1.0
     */
    public function __construct($throwableOrErr) {
        $this->errOrThrowable = $throwableOrErr;
    }
    /**
     * Show the view.
     * @param int $responseCode A response code to send before showing the view. 
     * default is 500 - Server Error.
     * @since 1.0
     */
    public function show($responseCode = 500) {
        $responseExist = class_exists('webfiori\http\Response');

        if ($responseExist) {
            Response::setCode($responseCode);
        } else {
            http_response_code(500);
        }

        if (class_exists('webfiori\ui\HTMLNode')) {
            $this->_phpStructsExist($this->errOrThrowable);
            $page = Page::render(false, true);
        } else {
            $page = $this->_phpStructsDoesNotexist($this->errOrThrowable);
        }

        if ($responseExist) {
            Response::write($page);
            Response::send();
        } else {
            echo $page;
            die;
        }
    }
    /**
     * 
     * @param string $label
     * @param string $info
     * @return HTMLNode
     * @since 1.0
     */
    private function _createMessageLine($label, $info) {
        $node = new HTMLNode('p');
        $labelNode = new HTMLNode('b');
        $labelNode->setClassName('nice-red mono');
        $labelNode->addTextNode($label.' ');
        $node->addChild($labelNode);
        $infoNode = new HTMLNode('span');
        $infoNode->setClassName('mono');
        $infoNode->addTextNode($info, false);
        $node->addChild($infoNode);

        return $node;
    }
    private function _getSiteName() {
        $siteNames = WebFioriApp::getSiteConfig()->getWebsiteNames();
        $session = SessionsManager::getActiveSession();

        if ($session !== null) {
            $currentLang = $session->getLangCode(true);
        } else {
            $currentLang = WebFioriApp::getSiteConfig()->getPrimaryLanguage();
        }

        if (isset($siteNames[$currentLang])) {
            return $siteNames[$currentLang];
        }

        return '';
    }
    private function _phpStructsDoesNotexist($throwableOrErr) {
        //this is a fall back if the library php-structs does not exist. 
        //Output HTML as string.
        $retVal = '<!DOCTYPE html>'
            .'<html>'
            .'<head>';

        if ($throwableOrErr instanceof Throwable) {
            $retVal .= '<title>Uncaught Exception</title>'
            .'<link href="'.Util::getBaseURL().'/assets/css/server-err.css" rel="stylesheet">'
            .'</head>'
            .'<body>'
            .'<h1>500 - Server Error: Uncaught Exception.</h1>'
            .'<hr>'
            .'<p>'
            .'<b class="nice-red mono">Exception Class:</b> <span class="mono">'.get_class($throwableOrErr)."</span><br/>"
            .'<b class="nice-red mono">Exception Message:</b> <span class="mono">'.$throwableOrErr->getMessage()."</span><br/>"
            .'<b class="nice-red mono">Exception Code:</b> <span class="mono">'.$throwableOrErr->getCode()."</span><br/>";
 
            if (defined('WF_VERBOSE') && WF_VERBOSE) {
                $retVal .= '<b class="nice-red mono">File:</b> <span class="mono">'.$throwableOrErr->getFile()."</span><br/>"
                .'<b class="nice-red mono">Line:</b> <span class="mono">'.$throwableOrErr->getLine()."</span><br>"
                .'<b class="nice-red mono">Stack Trace:</b> '."<br/>"
                .'</p>'
                .'<pre>'.$throwableOrErr->getTraceAsString().'</pre>';
            } else {
                $retVal .= $this->_showTip();
            }
            $retVal .= '</body></html>';
        } else {
            $retVal .= '<title>Server Error - 500</title>'
                .'<link href="'.Util::getBaseURL().'/assets/css/server-err.css" rel="stylesheet">'
                .'</head>'
                .'<body style="color:white;background-color:#1a000d;">'
                .'<h1 style="color:#ff4d4d">500 - Server Error</h1>'
                .'<hr>'
                .'<p>'
                .'<b class="nice-red mono">Type:</b> <span class="mono">'.Util::ERR_TYPES[$throwableOrErr["type"]]['type']."</span><br/>"
                .'<b class="nice-red mono">Description:</b> <span class="mono">'.Util::ERR_TYPES[$throwableOrErr["type"]]['description']."</span><br/>"
                .'<b class="nice-red mono">Message:</b> <span class="mono">'.$throwableOrErr["message"]."</span><br>";

            if (defined('WF_VERBOSE') && WF_VERBOSE) {
                $retVal .= '<b class="nice-red mono">File:</b> <span class="mono">'.$throwableOrErr["file"]."</span><br/>"
                .'<b class="nice-red mono">Line:</b> <span class="mono">'.$throwableOrErr["line"]."</span><br/>";
            } else {
                $retVal .= $this->_showTip();
            }
        }
        $retVal .= '</body></html>';

        return $retVal;
    }
    private function _phpStructsExist($throwableOrErr) {
        Page::reset();
        Page::title('Uncaught Exception');
        Page::siteName($this->_getSiteName());
        Page::separator(WebFioriApp::getSiteConfig()->getTitleSep());
        Page::document()->getHeadNode()->addCSS(Util::getBaseURL().'/assets/css/server-err.css',[],false);
        $hNode = new HTMLNode('h1');

        if ($throwableOrErr instanceof Throwable) {
            $hNode->addTextNode('500 - Server Error: Uncaught Exception.');

            Page::insert($hNode);
            Page::insert($this->_createMessageLine('Exception Class:', get_class($throwableOrErr)));
            Page::insert($this->_createMessageLine('Exception Message:', $throwableOrErr->getMessage()));
            Page::insert($this->_createMessageLine('Exception Code:', $throwableOrErr->getCode()));

            if (defined('WF_VERBOSE') && WF_VERBOSE) {
                Page::insert($this->_createMessageLine('File:', $throwableOrErr->getFile()));
                Page::insert($this->_createMessageLine('Line:', $throwableOrErr->getLine()));
                Page::insert($this->_createMessageLine('Stack Trace:', ''));
                $stackTrace = new HTMLNode('pre');
                $stackTrace->addTextNode($throwableOrErr->getTraceAsString());
                Page::insert($stackTrace);
            } else {
                $this->_showTip();
            }
        } else {
            $hNode->addTextNode('500 - Server Error');
            Page::insert($hNode);
            Page::insert($this->_createMessageLine('Type:', Util::ERR_TYPES[$throwableOrErr["type"]]['type']));
            Page::insert($this->_createMessageLine('Description:', Util::ERR_TYPES[$throwableOrErr["type"]]['description']));
            Page::insert($this->_createMessageLine('Message: ', '<pre>'.$throwableOrErr["message"].'</pre>'));

            if (defined('WF_VERBOSE') && WF_VERBOSE) {
                Page::insert($this->_createMessageLine('File: ', $throwableOrErr["file"]));
                Page::insert($this->_createMessageLine('Line: ', $throwableOrErr["line"]));
            } else {
                $this->_showTip();
            }
        }
    }
    private function _showTip() {
        if (class_exists('webfiori\ui\HTMLNode')) {
            $paragraph = new HTMLNode('p');
            $paragraph->setClassName('mono');
            $paragraph->addTextNode('<b style="color:yellow">Tip</b>: To'
                .' display more details about the error, '
                .'define the constant "WF_VERBOSE" and set its value to "true" in '
                .'the class "GlobalConstants"', false);
            Page::insert($paragraph);
        } else {
            return '<p class="mono"><b style="color:yellow">Tip</b>: To'
                .' display more details about the error, '
                .'define the constant "WF_VERBOSE" and set its value to "true" in '
                .'the class "GlobalConstants".</p>';
        }
    }
}
