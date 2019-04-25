<?php
/*
 * The MIT License
 *
 * Copyright (c) 2019 Ibrahim BinAlshikh, phpStructs.
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
namespace phpStructs\html;
use Exception;
use phpStructs\LinkedList;
use phpStructs\Stack;
use phpStructs\Queue;
/**
 * A class that represents HTML element.
 *
 * @author Ibrahim
 * @version 1.7.4
 */
class HTMLNode {
    /**
     * An array that contains all unpaired (or void) HTML tags.
     * An unpaired tag is a tag that does tot require closing tag. Its 
     * body is empty and does not contain any thing.
     * This array has the following values:
     * <ul>
     * <li>br</li>
     * <li>hr</li>
     * <li>meta</li>
     * <li>img</li>
     * <li>input</li>
     * <li>wbr</li>
     * <li>embed</li>
     * <li>base</li>
     * <li>col</li>
     * <li>link</li>
     * <li>param</li>
     * <li>source</li>
     * <li>track</li>
     * <li>area</li>
     * </ul>
     * @since 1.7.4
     */
    const VOID_TAGS = array(
        'br','hr','meta','img','input','wbr','embed',
        'base','col','link','param','source','track','area'
    );
    private $isFormated;
    /**
     * A null guard for the methods that return null reference.
     * @since 1.6
     */
    private $null;
    /**
     * Default formatting for the code.
     * @var array
     * @since 1.5
     */
    const DEFAULT_CODE_FORMAT = array(
        'tab-spaces'=>4,
        'initial-tab'=>0,
        'with-colors'=>true,
        'use-pre'=>true,
        'colors'=>array(
            'bg-color'=>'rgb(21, 18, 33)',
            'text-color'=>'gray',
            'attribute-color'=>'rgb(0,124,0)',
            'attribute-value-color'=>'rgb(170,85,137)',
            'node-name-color'=>'rgb(204,225,70)',
            'lt-gt-color'=>'rgb(204,225,70)',
            'comment-color'=>'rgb(0,189,36)',
            'operator-color'=>'gray'
        )
    );
    /**
     * A string that represents a tab. Usually 4 spaces.
     * @var string 
     * @since 1.3
     */
    private $tabSpace;
    /**
     * A variable to indicate the number of tabs used (e.g. 1 = 4 spaces 2 = 8).
     * @var int
     * @since 1.3 
     */
    private $tabCount;
    /**
     * A variable that represents new line character.
     * @var string
     * @since 1.3 
     */
    private $nl;
    /**
     * A stack that is used to build HTML representation of the node.
     * @var Stack 
     * @since 1.3
     */
    private $nodesStack;
    /**
     * The node as HTML string.
     * @var string
     * @since 1.3 
     */
    private $htmlString;
    /**
     * The Node as viewable HTML code.
     * @since 1.5
     */
    private $codeString;
    /**
     * The parent node of the instance.
     * @var HTMLNode
     * @since 1.2 
     */
    private $parentNode;
    /**
     * The name of the tag (such as 'div')
     * @var string
     * @since 1.0 
     */
    private $name;
    /**
     * An array of key-value elements. The key acts as the attribute name 
     * and the value acts as the value of the attribute.
     * @var array
     * @since 1.0 
     */
    private $attributes;
    /**
     * A list of child nodes.
     * @var LinkedList
     * @since 1.0 
     */
    private $childrenList;
    /**
     * A boolean value. If set to true, The node must be closed while building 
     * the document.
     * @var boolean
     * @since 1.0 
     */
    private $requireClose;
    /**
     * The text that is located in the node body (applies only if the node is a 
     * text node). 
     * @var string
     * @since 1.0 
     */
    private $text;
    /**
     * Constructs a new instance of the class.
     * @param string $name The name of the node (such as 'div').  If 
     * we want to create a comment node, the name should be '#comment'. If 
     * we want to create a text node, the name should be '#text'. If empty string is 
     * given, default value will be used. The Default value is 'div'.
     * 
     */
    public function __construct($name='div') {
        $this->null = null;
        $nameUpper = strtoupper($name);
        if($nameUpper == '#TEXT' || $nameUpper == '#COMMENT'){
            $this->name = $nameUpper;
            $this->requireClose = false;
        }
        else{
            $this->name = strtolower($name);
            if(!$this->_validateNodeName($this->getNodeName())){
                throw new Exception('Invalid node name: \''.$name.'\'.');
            }
        }
        if($this->isTextNode() === true || $this->isComment()){
            $this->requireClose = false;
        }
        else{
            if(in_array($this->name, self::VOID_TAGS)){
                $this->requireClose = false;
            }
            else{
                $this->requireClose = true;
                $this->childrenList = new LinkedList();
            }
            $this->attributes = array();
        }
    }
    /**
     * Validates the name of the node.
     * @param string $name The name of the node in lower case.
     * @return boolean If the name is valid, the method will return true. If 
     * it is not valid, it will return false.
     * @since 1.7.4
     */
    private function _validateNodeName($name){
        $len = strlen($name);
        if($len > 0){
            for($x = 0 ; $x < $len ; $x++){
                $char = $name[$x];
                if(($char <= 'z' && $char >= 'a') || ($char >= '0' && $char <= '9') || $char=='-'){
                    
                }
                else{
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Converts a string of HTML code to an array that looks like a tree of 
     * HTML elements.
     * @param string $text HTML code.
     * @return array An indexed array. Each index will contain parsed element 
     * information. For example, if the given code is as follows:<br/>
     * <pre>
     &lt;html&gt;&lt;head&gt;&lt;/head&gt;&lt;body&gt;&lt;/body&gt;&lt;/html&gt;
     * </pre>
     * Then the output will be as follows:
     <pre>Array
&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;[0] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[tag-name] =&gt; html
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[is-void-tag] =&gt; 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[attributes] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[children] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[0] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[tag-name] =&gt; head
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[is-void-tag] =&gt; 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[attributes] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[children] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
&nbsp;&nbsp;&nbsp;&nbsp;[1] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[tag-name] =&gt; body
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[is-void-tag] =&gt; 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[attributes] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[children] =&gt; Array
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
&nbsp;&nbsp;&nbsp;&nbsp;)
&nbsp;&nbsp;)
)
</pre>
     * @since 1.7.4
     */
    public static function htmlAsArray($text) {
        $trimmed = trim($text);
        if(strlen($trimmed) != 0){
            $array = explode('<', $trimmed);
            $nodesNames = array();
            $nodesNamesIndex = 0;
            for($x = 0 ; $x < count($array) ; $x++){
                $node = $array[$x];
                if(strlen(trim($node)) != 0){
                    $nodesNames[$nodesNamesIndex] = explode('>', $node);
                    $nodesNames[$nodesNamesIndex]['body-text'] = $nodesNames[$nodesNamesIndex][1];
                    if(strlen($nodesNames[$nodesNamesIndex]['body-text']) == 0){
                        unset($nodesNames[$nodesNamesIndex]['body-text']);
                    }
                    unset($nodesNames[$nodesNamesIndex][1]);
                    $nodeName = '';
                    $count = strlen($nodesNames[$nodesNamesIndex][0]);
                    for($y = 0 ; $y < $count ; $y++){
                        $char = $nodesNames[$nodesNamesIndex][0][$y];
                        if($char == ' '){
                            break;
                        }
                        else{
                            $nodeName .= $char;
                        }
                    }
                    if((isset($nodeName[0]) && $nodeName[0] == '!') && (
                            isset($nodeName[1]) && $nodeName[1] == '-') && 
                            ( isset($nodeName[2]) && $nodeName[2] == '-')){
                        $nodesNames[$nodesNamesIndex]['tag-name'] = '#COMMENT';
                        $nodesNames[$nodesNamesIndex]['body-text'] = trim($nodesNames[$nodesNamesIndex][0],"!--");
                    }
                    else{
                        $nodeName = strtolower($nodeName);
                        $nodesNames[$nodesNamesIndex]['tag-name'] = $nodeName;
                        $nodesNames[$nodesNamesIndex][0] = trim(substr($nodesNames[$nodesNamesIndex][0], strlen($nodeName)));
                        if($nodeName[0] == '/'){
                            $nodesNames[$nodesNamesIndex]['is-closing-tag'] = true;
                        }
                        else{
                            $nodesNames[$nodesNamesIndex]['is-closing-tag'] = false;
                            if(in_array($nodeName, self::VOID_TAGS)){
                                $nodesNames[$nodesNamesIndex]['is-void-tag'] = true;
                            }
                            else if($nodeName == '!doctype'){
                                $nodesNames[$nodesNamesIndex]['tag-name'] = '!DOCTYPE';
                                $nodesNames[$nodesNamesIndex]['is-void-tag'] = true;
                            }
                            else{
                                $nodesNames[$nodesNamesIndex]['is-void-tag'] = false;
                            }
                        }
                        $len = strlen($nodesNames[$nodesNamesIndex][0]);
                        if($len != 0){
                            $nodesNames[$nodesNamesIndex]['attributes'] = self::_parseAttributes($nodesNames[$nodesNamesIndex][0]);
                        }
                        else{
                            $nodesNames[$nodesNamesIndex]['attributes'] = array();
                        }
                    }
                    unset($nodesNames[$nodesNamesIndex][0]);
                    $nodesNamesIndex++;
                }
            }
            $x = 0;
            return self::_buildArrayTree($nodesNames,$x,count($nodesNames),null);
        }
        return array();
    }
    /**
     * A helper method for parsing attributes string.
     * @param Queue $queue
     * @param boolean $isEqualFound
     * @param string $val
     * @since 1.7.4
     */
    private static function _parseAttributesHelper(&$queue,$isEqualFound,&$val){
        if($isEqualFound){
            $equalSign = '=';
            $queue->enqueue($equalSign);
            $queue->enqueue($val);
        }
        else{
            $queue->enqueue($val);
        }
        $val = '';
    }
    /**
     * A helper method to parse a string of HTML element attributes.
     * @param string $attrsStr A string that represents the attributes 
     * of the element (such as 'type=text disabled placeholder="something" class=MyInput')
     * @return array An associative array that contains all the parsed attributes. 
     * The keys are the attributes and the values of the keys are the values 
     * of the attributes.
     * @since 1.7.4
     */
    private static function _parseAttributes($attrsStr) {
        $inQouted = false;
        $isEqualFound = false;
        $queue = new Queue();
        $str = '';
        for($x = 0 ; $x < strlen($attrsStr) ; $x++){
            $char = $attrsStr[$x];
            if($char == '=' && !$inQouted){
                $str = trim($str);
                if(strlen($str) != 0 ){
                    self::_parseAttributesHelper($queue, $isEqualFound, $str);
                }
                $isEqualFound = true;
            }
            else if($char == ' ' && strlen(trim($str)) != 0 && !$inQouted){
                $str = trim($str);
                if(strlen($str) != 0){
                    self::_parseAttributesHelper($queue, $isEqualFound, $str);
                }
                $isEqualFound = false;
            }
            else if(($char == '"' || $char == "'") && $inQouted){
                self::_parseAttributesHelper($queue, $isEqualFound, $str);
                $isEqualFound = false;
                $inQouted = false;
            }
            else if(($char == '"' || $char == "'") && !$inQouted){
                $str = trim($str);
                if(strlen($str) != 0){
                    self::_parseAttributesHelper($queue, $isEqualFound, $str);
                }
                $inQouted = true;
            }
            else{
                $str.= $char;
            }
        }
        $trimmed = trim($str);
        if(strlen($trimmed) != 0){
            $queue->enqueue($trimmed);
        }
        $retVal = array();
        while ($queue->peek()){
            $current = $queue->dequeue();
            $next = $queue->peek();
            if($next == '='){
                $queue->dequeue();
                $retVal[strtolower($current)] = $queue->dequeue();
            }
            else{
                $retVal[strtolower($current)] = '';
            }
        }
        return $retVal;
    }
    /**
     * Build an associative array that represents parsed HTML string.
     * @param array $parsedNodesArr An array that contains the parsed HTML 
     * elements.
     * @param int $x The current element index.
     * @param int $nodesCount Number of parsed nodes.
     * @return array
     * @since 1.7.4
     */
    private static function _buildArrayTree($parsedNodesArr,&$x,$nodesCount) {
        $retVal = array();
        for(; $x < $nodesCount ; $x++){
            $node = $parsedNodesArr[$x];
            $isVoid = isset($node['is-void-tag']) ? $node['is-void-tag'] : false;
            $isClosingTag = isset($node['is-closing-tag']) ? $node['is-closing-tag'] : false;
            if($node['tag-name'] == '#COMMENT'){
                unset($node['is-closing-tag']);
                $retVal[] = $node;
            }
            else if($isVoid){
                unset($node['is-closing-tag']);
                unset($node['body-text']);
                $retVal[] = $node;
            }
            else if($isClosingTag){
                return $retVal;
            }
            else{
                $x++;
                $node['children'] = self::_buildArrayTree($parsedNodesArr, $x, $nodesCount);
                unset($node['is-closing-tag']);
                $retVal[] = $node;
            }
        }
        return $retVal;
    }
    /**
     * Creates HTMLNode object given a string of HTML code.
     * Note that this method is still under implementation.
     * @param string $text A string that represents HTML code.
     * @param boolean $asHTMLDocObj If set to 'true' and given HTML represents a 
     * structured HTML document, the method will convert the code to an object 
     * of type 'HTMLDoc'. Default is 'true'.
     * @return array|HTMLDoc|HTMLNode If the given code represents HTML document 
     * and the parameter <b>$asHTMLDocObj</b> is set to 'true', an object of type 
     * 'HTMLDoc' is returned. If the given code has multiple top level nodes 
     * (e.g. '&lt;div&gt;&lt;/div&gt;&lt;div&gt;&lt;/div&gt;'), 
     * an array that contains an objects of type 'HTMLNode' is returned. If the 
     * given code has one top level node, an object of type 'HTMLNode' is returned. 
     * Note that it is possible that the method will return an instance which 
     * is a sub-class of the class 'HTMLNode'.
     * @since 1.7.4
     */
    public static function fromHTMLText($text,$asHTMLDocObj=true) {
        $nodesArr = self::htmlAsArray($text);
        if(count($nodesArr) >= 1){
            if($asHTMLDocObj && ($nodesArr[0]['tag-name'] == 'html' || $nodesArr[0]['tag-name'] == '!DOCTYPE')){
                $retVal = new HTMLDoc();
                $retVal->getHeadNode()->removeAllChildNodes();
                for($x = 0 ; $x < count($nodesArr) ; $x++){
                    if($nodesArr[$x]['tag-name'] == 'html'){
                        $htmlNode = self::_fromHTMLTextHelper_00($nodesArr[$x]);
                        for($y = 0 ; $y < $htmlNode->childrenCount() ; $y++){
                            $child = $htmlNode->children()->get($y);
                            if($child->getNodeName() == 'head'){
                                $retVal->setHeadNode($child);
                            }
                            else if($child->getNodeName() == 'body'){
                                for($z = 0 ; $z < $child->childrenCount() ; $z++){
                                    $node = $child->children()->get($z);
                                    $retVal->addChild($node);
                                }
                            }
                        }
                    }
                    else if($nodesArr[$x]['tag-name'] != 'head'){
                        $headNode = self::_fromHTMLTextHelper_00($nodesArr[$x]);
                        $retVal->setHeadNode($headNode);
                    }
                }
            }
            else if(count($nodesArr) != 1){
                $retVal = array();
                foreach ($nodesArr as $node){
                    $asHtmlNode = self::_fromHTMLTextHelper_00($node);
                    $retVal[] = $asHtmlNode;
                }
            }
            else if(count($nodesArr) == 1){
                return self::_fromHTMLTextHelper_00($nodesArr[0]);
            }
            return $retVal;
        }
        return null;
    }
    /**
     * Creates an object of type HTMLNode given its properties as an associative 
     * array.
     * @param array $nodeArr An associative array that contains node properties. 
     * This array can have the following indices:
     * <ul>
     * <li>tag-name: An index that contains tag name.</li>
     * <li>attributes: An associative array that contains node attributes. Ignored 
     * if 'tag-name' is '#COMMENT' or '!DOCTYPE'.</li>
     * <li>children: A sub array that contains the info of all node children. 
     * Ignored if 'tag-name' is '#COMMENT' or '!DOCTYPE'.</li>
     * </ul>
     * @return HTMLNode
     */
    private static function _fromHTMLTextHelper_00($nodeArr) {
        if($nodeArr['tag-name'] == '#COMMENT'){
            return self::createComment($nodeArr['body-text']);
        }
        else{
            if($nodeArr['tag-name'] == 'head'){
                $htmlNode = new HeadNode();
                $htmlNode->removeAllChildNodes();
                for($x = 0 ; $x < count($nodeArr['children']) ; $x++){
                    $chNode = $nodeArr['children'][$x];
                    if($chNode['tag-name'] == 'title'){
                        $htmlNode->setTitle($chNode['body-text']);
                        foreach ($chNode['attributes'] as $attr => $val){
                            $htmlNode->getTitleNode()->setAttribute($attr, $val);
                        }
                    }
                    else if($chNode['tag-name'] == 'base'){
                        $isBaseSet = false;
                        foreach ($chNode['attributes'] as $attr => $val){
                            if($attr == 'href'){
                                $isBaseSet = $htmlNode->setBase($val);
                                break;
                            }
                        }
                        if($isBaseSet){
                            foreach ($chNode['attributes'] as $attr => $val){
                                $htmlNode->getBase()->setAttribute($attr, $val);
                            }
                        }
                    }
                    else if($chNode['tag-name'] == 'link'){
                        $isCanonical = false;
                        $tmpNode = new HTMLNode('link');
                        foreach ($chNode['attributes'] as $attr=>$val){
                            $tmpNode->setAttribute($attr, $val);
                            $lower = strtolower($val);
                            if($attr == 'rel' && $lower == 'canonical'){
                                $isCanonical = true;
                                $tmpNode->setAttribute($attr, $lower);
                            }
                            else if($attr == 'rel' && $lower == 'stylesheet'){
                                $tmpNode->setAttribute($attr, $lower);
                            }
                        }
                        if($isCanonical){
                            $isCanonicalSet = $htmlNode->setCanonical($tmpNode->getAttributeValue('href'));
                            if($isCanonicalSet){
                                foreach ($tmpNode->getAttributes() as $attr => $val){
                                    $htmlNode->getCanonicalNode()->setAttribute($attr, $val);
                                }
                            }
                        }
                        else{
                            $htmlNode->addChild($tmpNode);
                        }
                    }
                    else if($chNode['tag-name'] == 'script'){
                        $tmpNode = self::_fromHTMLTextHelper_00($chNode);
                        foreach ($tmpNode->getAttributes() as $attr=>$val){
                            $tmpNode->setAttribute($attr, $val);
                            $lower = strtolower($val);
                            if($attr == 'type' && $lower == 'text/javascript'){
                                $tmpNode->setAttribute($attr, $lower);
                            }
                        }
                        $htmlNode->addChild($tmpNode);
                    }
                    else if($chNode['tag-name'] == 'meta'){
                        if(isset($chNode['attributes']['charset'])){
                            $htmlNode->setCharSet($chNode['attributes']['charset']);
                        }
                        else{
                            $htmlNode->addChild(self::_fromHTMLTextHelper_00($chNode));
                        }
                    }
                    else {
                        $newCh = self::_fromHTMLTextHelper_00($chNode);
                        $htmlNode->addChild($newCh);
                    }
                }
            }
            else if($nodeArr['tag-name'] == '!DOCTYPE'){
                return self::createTextNode('<!DOCTYPE html>',false);
            }
            else{
                $htmlNode = new HTMLNode($nodeArr['tag-name']);
            }
            if(isset($nodeArr['attributes'])){
                foreach ($nodeArr['attributes'] as $key => $value) {
                    $htmlNode->setAttribute($key, $value);
                }
            }
            if($nodeArr['tag-name'] != 'head' && isset($nodeArr['children'])){
                foreach ($nodeArr['children'] as $child){
                    $htmlNode->addChild(self::_fromHTMLTextHelper_00($child));
                }
            }
            if(isset($nodeArr['body-text']) && strlen(trim($nodeArr['body-text'])) != 0){
                $htmlNode->addTextNode($nodeArr['body-text']);
            }
            return $htmlNode;
        }
    }
    /**
     * Checks if the given node represents a comment or not.
     * @return boolean The method will return true if the given 
     * node is a comment.
     * @since 1.5
     */
    public function isComment() {
        return $this->getNodeName() == '#COMMENT';
    }
    /**
     * Returns the parent node.
     * @return HTMLNode|null An object of type HTMLNode if the node 
     * has a parent. If the node has no parent, the method will return null.
     * @since 1.2
     */
    public function &getParent() {
        return $this->parentNode;
    }
    /**
     * 
     * @param HTMLNode $node
     * @since 1.2
     */
    private function _setParent(&$node){
        $this->parentNode = $node;
    }
    /**
     * Returns a linked list of all child nodes.
     * @return LinkedList|null A linked list of all child nodes. if the 
     * given node is a text node, the method will return null.
     * @since 1.0
     */
    public function &children(){
        return $this->childrenList;
    }
    /**
     * Creates new text node.
     * @param string $nodeText The text that will be inserted in the body 
     * of the node.
     * @param boolean $escHtmlEntities If set to true, the method will 
     * replace the characters '&lt;', '&gt;' and 
     * '&amp' with the following HTML entities: '&amp;lt;', '&amp;gt;' and '&amp;amp;' 
     * in the given text.
     * @return HTMLNode An object of type HTMLNode.
     * @since 1.5
     */
    public static function &createTextNode($nodeText,$escHtmlEntities=true){
        $text = new HTMLNode('#TEXT');
        $text->setText($nodeText,$escHtmlEntities);
        return $text;
    }
    /**
     * Creates new comment node.
     * @param string $text The text that will be inserted in the body 
     * of the comment.
     * @return HTMLNode An object of type HTMLNode.
     * @since 1.5
     */
    public static function &createComment($text) {
        $comment = new HTMLNode('#COMMENT');
        $comment->setText($text);
        return $comment;
    }
    /**
     * Checks if the node is a text node or not.
     * @return boolean true if the node is a text node. false otherwise.
     * @since 1.0
     */
    public function isTextNode() {
        return $this->getNodeName() == '#TEXT';
    }
    /**
     * Checks if a given node is a direct child of the instance.
     * @param HTMLNode $node The node that will be checked.
     * @return boolean true is returned if the node is a child 
     * of the instance. false if not. Also if the current instance is a 
     * text node or a comment node, the function will always return false.
     * @since 1.2
     */
    public function hasChild(&$node) {
        if(!$this->isTextNode() && !$this->isComment()){
            if($node instanceof HTMLNode){
                return $this->children()->indexOf($node) != -1;
            }
        }
        return false;
    }
    /**
     * Replace a direct child node with a new one.
     * @param HTMLNode $oldNode The old node. It must be a child of the instance.
     * @param HTMLNode $replacement The replacement node.
     * @return boolean true is returned if the node replaced. false if not.
     * @since 1.2
     */
    public function replaceChild(&$oldNode,&$replacement) {
        if(!$this->isTextNode() && !$this->isComment()){
            if($oldNode instanceof HTMLNode){
                if($this->hasChild($oldNode)){
                    if($replacement instanceof HTMLNode){
                        return $this->children()->replace($oldNode, $replacement);
                    }
                }
            }
        }
        return false;
    }
    /**
     * 
     * @param string $val
     * @param LinkedList $chList
     * @param LinkedList $list
     * @return LinkedList
     */
    private function _getChildrenByTag($val,$chList,$list){
        $chCount = $chList->size();
        for($x = 0 ; $x < $chCount ; $x++){
            $child = $chList->get($x);
            if($child->mustClose()){
                $tmpList = $child->_getChildrenByTag($val,$child->children(),new LinkedList());
                for($y = 0 ; $y < $tmpList->size() ; $y++){
                    $list->add($tmpList->get($y));
                }
            }
        }
        for($x = 0 ; $x < $chCount ; $x++){
            $child = $chList->get($x);
            if($child->getNodeName() == $val){
                $list->add($child);
            }
        }
        return $list;
    }
    /**
     * Returns a linked list that contains all child nodes which has the given 
     * tag name.
     * If the given tag name is empty string or the node has no children which has 
     * the given tag name, the returned list will be empty.
     * @param string $val The name of the tag (such as 'div' or 'a').
     * @return LinkedList A linked list that contains all child nodes which has the given 
     * tag name.
     * @since 1.2
     */
    public function getChildrenByTag($val){
        $valToSearch = strtoupper($val);
        if(!($valToSearch == '#TEXT' || $valToSearch == '#COMMENT')){
            $valToSearch = strtolower($val);
        }
        $list = new LinkedList();
        if(strlen($valToSearch) != 0 && $this->mustClose()){
            return $this->_getChildrenByTag($valToSearch, $this->children(), $list);
        }
        return $list;
    }
    /**
     * 
     * @param type $val
     * @param LinkedList $chNodes
     * @return null|HTMLNode Description
     */
    private function &_getChildByID($val,&$chNodes){
        $chCount = $chNodes !== null ? $chNodes->size() : 0;
        for($x = 0 ; $x < $chCount ; $x++){
            $child = &$chNodes->get($x);
            if(!$child->isVoidNode()){
                $tmpCh = &$child->_getChildByID($val,$child->children());
                if($tmpCh instanceof HTMLNode){
                    return $tmpCh;
                }
            }
        }
        for($x = 0 ; $x < $chCount ; $x++){
            $child = &$chNodes->get($x);
            if($child->hasAttribute('id')){
                $attrVal = $child->getAttributeValue('id');
                if($attrVal == $val){
                    return $child;
                }
            }
        }
        return $this->null;
    }
    /**
     * Returns a child node given its ID.
     * @param string $val The ID of the child.
     * @return null|HTMLNode The method returns an object of type HTMLNode 
     * if found. If no node has the given ID, the method will return null.
     * @since 1.2
     */
    public function &getChildByID($val){
        if(!$this->isTextNode() && !$this->isComment() && $this->mustClose()){
            if(strlen($val) != 0){
                $ch = &$this->_getChildByID($val, $this->children());
                return $ch;
            }
        }
        return $this->null;
    }
    /**
     * Checks if the node require ending tag or not (deprecated).
     * If the node is a comment or its a text node, the method will 
     * always return false. This method is deprecated. Use HTMLNode::isVoidNode() instead.
     * @return boolean true if the node does require ending tag.
     * @since 1.0
     * @deprecated since version 1.7.4
     */
    public function mustClose() {
        return $this->requireClose;
    }
    /**
     * Checks if the given node is a void node.
     * @return boolean If the node is a void node, the method will return true. 
     * False if not.
     */
    public function isVoidNode() {
        return !$this->mustClose();
    }
    /**
     * Updates the name of the node.
     * If the node type is a text or a comment, 
     * developer can only switch between the two types. If the node type is of 
     * another type and has child nodes, type will be changed only if the given 
     * node name is not a void node. If the node is a void node and it has no 
     * children, it will switch without problems.
     * @param string $name The new name.
     * @return boolean The method will return true if the type is updated.
     * @since 1.7
     */
    public function setNodeName($name) {
        if($this->isTextNode() || $this->isComment()){
            $uName = strtoupper($name);
            if(($this->isTextNode() && $uName == '#COMMENT') || ($this->isComment() && $uName == '#TEXT')){
                $this->name = $uName;
                return true;
            }
            else {
                return false;
            }
        }
        else{
            $lName = strtolower($name);
            if($this->_validateNodeName($lName)){
                $reqClose = !in_array($lName, self::VOID_TAGS);
                if($this->mustClose() && $reqClose !== true){
                    if($this->childrenCount() == 0){
                        $this->name = $lName;
                        $this->requireClose = false;
                        return true;
                    }
                }
                else{
                    $this->name = $lName;
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Returns the name of the node.
     * @return string The name of the node. If the node is a text node, the 
     * method will return the value '#TEXT'. If the node is a comment node, the 
     * method will return the value '#COMMENT'.
     * @since 1.0
     */
    public function getNodeName(){
        return $this->name;
    }
    /**
     * Returns an array of all node attributes with the values
     * @return array|null an associative array. The keys will act as the attribute 
     * name and the value will act as the value of the attribute. If the node 
     * is a text node, the method will return null.
     * @since 1.0 
     */
    public function getAttributes() {
        return $this->attributes;
    }
    /**
     * Sets a value for an attribute.
     * @param string $name The name of the attribute. If the attribute does not 
     * exist, it will be created. If already exists, its value will be updated. 
     * Note that if the node type is text node, 
     * the attribute will never be created.
     * @param string $val The value of the attribute. Default is empty string.
     * @return boolean If the attribute is set, the method will return true. The 
     * method will return false only if the given name is empty string 
     * or the name of the attribute is 'dir' and the value is not 'ltr' or 'rtl'.
     * @since 1.0
     */
    public function setAttribute($name,$val=''){
        $trimmedName = trim($name);
        if(!$this->isTextNode() && !$this->isComment() && strlen($trimmedName) != 0){
            $lower = strtolower($trimmedName);
            if($lower == 'dir'){
                $lowerVal = strtolower($val);
                if($lowerVal == 'ltr' || $lowerVal == 'rtl'){
                    $this->attributes[$lower] = $lowerVal;
                    return true;
                }
            }
            else{
                $this->attributes[$lower] = $val;
                return true;
            }
        }
        return false;
    }
    /**
     * Sets the value of the attribute 'id' of the node.
     * @param string $idVal The value to set.
     * @since 1.2
     */
    public function setID($idVal){
        $this->setAttribute('id',$idVal);
    }
    /**
     * Sets the value of the attribute 'tabindex' of the node.
     * @param int $val The value to set. From MDN: An integer attribute indicating if 
     * the element can take input focus. It can takes several values: 
     * <ul>
     * <li>A negative value means that the element should be focusable, but 
     * should not be reachable via sequential keyboard navigation.</li>
     * <li>0 means that the element should be focusable and reachable via sequential 
     * keyboard navigation, but its relative order is defined by the platform convention</li>
     * <li>A positive value means that the element should be focusable 
     * and reachable via sequential keyboard navigation; the order in 
     * which the elements are focused is the increasing value of the 
     * tabindex. If several elements share the same tabindex, their relative 
     * order follows their relative positions in the document.</li>
     * </ul>
     * @since 1.2
     */
    public function setTabIndex($val){
        $this->setAttribute('tabindex', $val);
    }
    /**
     * Sets the value of the attribute 'title' of the node.
     * @param string $val The value to set. From MDN: Contains a 
     * text representing advisory information related to the element 
     * it belongs to. Such information can typically, but not necessarily, 
     * be presented to the user as a tooltip.
     * @since 1.2
     */
    public function setTitle($val){
        $this->setAttribute('title', $val);
    }
    /**
     * Sets the value of the attribute 'dir' of the node.
     * @param string $val The value to set. It can be 'ltr' or 'rtl'.
     * @since 1.2
     */
    public function setWritingDir($val){
        $this->setAttribute('dir', $val);
    }
    /**
     * Sets the value of the attribute 'class' of the node.
     * @param string $val The value to set.
     * @since 1.2
     */
    public function setClassName($val){
        $this->setAttribute('class',$val);
    }
    /**
     * Sets the value of the attribute 'name' of the node.
     * @param string $val The value to set.
     * @since 1.2
     */
    public function setName($val){
        $this->setAttribute('name',$val);
    }
    /**
     * Sets the value of the attribute 'style' of the node.
     * @param array $cssStyles An associative array of CSS declarations. The keys of the array should 
     * be the names of CSS Properties and the values should be the values of 
     * the attributes (e.g. 'color'=>'white').
     * @since 1.7.1
     */
    public function setStyle($cssStyles=array()) {
        $styleStr = '';
        foreach ($cssStyles as $key => $val){
            $styleStr .= $key.':'.$val.';';
        }
        $this->setAttribute('style', $styleStr);
    }
    /**
     * Returns an array that contains in-line CSS declarations.
     * If the attribute is not set, the array will be empty.
     * @return array An associative array of CSS declarations. The keys of the array will 
     * be the names of CSS Properties and the values will be the values of 
     * the attributes (e.g. 'color'=>'white').
     * @since 1.0
     */
    public function getStyle() {
        $styleStr = $this->getAttributeValue('style');
        if($styleStr !== null){
            $retVal = array();
            $arr1 = explode(';', trim($styleStr,';'));
            foreach ($arr1 as $val){
                $exp = explode(':', $val);
                $retVal[$exp[0]] = $exp[1];
            }
            return $retVal;
        }
        return array();
    }
    /**
     * Removes an attribute from the node given its name.
     * @param string $name The name of the attribute.
     * @since 1.0
     */
    public function removeAttribute($name){
        if(!$this->isTextNode() && !$this->isComment()){
            $trimmed = strtolower(trim($name));
            if(isset($this->attributes[$trimmed])){
                unset($this->attributes[$trimmed]);
            }
        }
    }
    /**
     * Removes all child nodes.
     * @since 1.0
     */
    public function removeAllChildNodes() {
        if(!$this->isTextNode() && !$this->isComment() && $this->mustClose()){
            $this->childrenList->clear();
        }
    }
    /**
     * Removes a direct child node.
     * @param HTMLNode $node The node that will be removed.
     * @return HTMLNode|null The method will return the node if removed. 
     * If not removed, the method will return null.
     * @since 1.2
     */
    public function &removeChild(&$node) {
        if($this->mustClose()){
            if($node instanceof HTMLNode){
                $child = &$this->children()->removeElement($node);
                if($child instanceof HTMLNode){
                    $child->_setParent($this->null);
                    return $child;
                }
            }
        }
        return $this->null;
    }
    /**
     * Adds new child node.
     * @param HTMLNode $node The node that will be added. The node can have 
     * child nodes only if 3 conditions are met. If the node is not a text node 
     * , the node is not a comment node and the node must have ending tag.
     * @since 1.0
     */
    public function addChild($node) {
        if(!$this->isTextNode() && !$this->isComment() && $this->mustClose()){
            if(($node instanceof HTMLNode) && $node !== $this){
                $node->_setParent($this);
                $this->childrenList->add($node);
            }
        }
    }
    /**
     * Adds a text node as a child.
     * The text node will be added to the body of the node only 
     * if it is not a void node.
     * @param string $text The text that will be in the node.
     * @param boolean $escHtmlEntities If set to true, the method will 
     * replace the characters '&lt;', '&gt;' and 
     * '&amp' with the following HTML entities: '&amp;lt;', '&amp;gt;' and '&amp;amp;' 
     * in the given text. Default is true.
     * @since 1.6
     */
    public function addTextNode($text,$escHtmlEntities=true) {
        if($this->mustClose()){
            $this->addChild(self::createTextNode($text,$escHtmlEntities));
        }
    }
    /**
     * Adds a comment node as a child.
     * The comment node will be added to the body of the node only 
     * if it is not a void node.
     * @param string $text The text that will be in the node.
     * @since 1.6
     */
    public function addCommentNode($text) {
        if($this->mustClose()){
            $this->addChild(self::createComment($text));
        }
    }
    /**
     * Sets the value of the property $text.
     * Note that if the type of the node is comment, the method will replace 
     * '&lt;!--' and '--&gt;' with ' --' and '-- ' if it was found in the given text.
     * @param string $text The text to set. If the node is not a text node or 
     * a comment node, the value will never be set.
     * @param boolean $escHtmlEntities If set to true, the method will 
     * replace the characters '&lt;', '&gt;' and 
     * '&amp' with the following HTML entities: '&amp;lt;', '&amp;gt;' and '&amp;amp;' 
     * in the given text. Default is true. Ignored in case the node type is comment.
     * @since 1.0
     */
    public function setText($text,$escHtmlEntities=true) {
        if($this->isTextNode() || $this->isComment()){
            if($this->isComment()){
                $text = str_replace('<!--', ' --', str_replace('-->', '-- ', $text));
            }
            else if($escHtmlEntities === true){
                $charsToReplace = array(
                    '&'=>'&amp;',
                    '<'=>'&lt;',
                    '>'=>'&gt;'
                );
                foreach ($charsToReplace as $ch => $rep){
                    $text = str_replace($ch, $rep, $text);
                }
            }
            $this->text = $text;
        }
    }
    /**
     * Returns the value of the text that this node represents.
     * @return string If the node is a text node or a comment node, 
     * the method will return the text in the body of the node. If not, 
     * the method will return empty string.
     * @since 1.0
     */
    public function getText() {
        if($this->isComment() || $this->isTextNode()){
            return $this->text;
        }
        return '';
    }
    /**
     * Returns the node as HTML comment.
     * @return string The node as HTML comment. if the node is not a comment, 
     * the method will return empty string.
     * @since 1.5
     */
    public function getComment() {
        if($this->isComment()){
            return '<!--'.$this->getText().'-->';
        }
        return '';
    }
    /**
     * Returns a string that represents the opening part of the node.
     * @return string A string that represents the opening part of the node. 
     * if the node is a text node or a comment node, the returned value will be an empty string.
     * @since 1.0
     */
    public function open() {
        $retVal = '';
        if(!$this->isTextNode() && !$this->isComment()){
            $retVal .= '<'.$this->getNodeName().'';
            foreach ($this->getAttributes() as $attr => $val){
                $retVal .= ' '.$attr.'="'.$val.'"';
            }
            $retVal .= '>';
        }
        return $retVal;
    }
    /**
     * Returns a string that represents the closing part of the node.
     * @return string A string that represents the closing part of the node. 
     * if the node is a text node, a comment node or a void node the returned
     *  value will be an empty string.
     * @since 1.0
     */
    public function close() {
        if(!$this->isTextNode() && !$this->isComment() && $this->mustClose()){
            return '</'.$this->getNodeName().'>';
        }
        return '';
    }
    /**
     * Returns HTML string that represents the node as a whole.
     * @param boolean $formatted Set to true to return a well formatted 
     * HTML document (has new lines and indentations). Default is false.
     * @param int $initTab Initial tab count (indentation). Used in case of the document is 
     * well formatted. This number represents the size of code indentation.
     * @return string HTML string that represents the node.
     * @since 1.0
     */
    public function toHTML($formatted=false,$initTab=0) {
        if($this->isFormatted() !== null){
            $formatted = $this->isFormatted();
        }
        if(!$formatted){
            $this->nl = '';
            $this->tabSpace = '';
        }
        else{
            $this->nl = HTMLDoc::NL;
            if($initTab > -1){
                $this->tabCount = $initTab;
            }
            else{
                $this->tabCount = 0;
            }
            $this->tabSpace = '';
            for($x = 0 ; $x < 4 ; $x++){
                $this->tabSpace .= ' ';
            }
        }
        $this->htmlString = '';
        $this->nodesStack = new Stack();
        $this->_pushNode($this,$formatted);
        return $this->htmlString;
    }
    /**
     * Returns the value of the property $isFormatted.
     * The property is used to control how the HTML code that will be generated 
     * will look like. If set to true, the code will be user-readable. If set to 
     * false, it will be compact and the load size will be come less since no 
     * new line characters or spaces will be added in the code.
     * @return boolean|null If the property is set, the method will return 
     * its value. If not set, the method will return null.
     * @since 1.7.2
     */
    public function isFormatted() {
        return $this->isFormated;
    }
    /**
     * Sets the value of the property $isFormatted.
     * @param boolean $bool true to make the document that will be generated 
     * from the node user-readable. false to make it compact.
     * @since 1.7.2
     */
    public function setIsFormatted($bool) {
        $this->isFormated = $bool === true;
    }
    /**
     * 
     * @param HTMLNode $node
     */
    private function _pushNode(&$node) {
        if($node->isTextNode()){
            if($node->isFormatted() !== null && $node->isFormatted() === false){
                $this->htmlString .= $node->getText();
            }
            else{
                $parent = $node->getParent();
                if($parent !== null){
                    $parentName = $node->getParent()->getNodeName();
                    if($parentName == 'code' || $parentName == 'pre' || $parentName == 'textarea'){
                        $this->htmlString .= $node->getText();
                    }
                    else{
                        $this->htmlString .= $this->_getTab().$node->getText().$this->nl;
                    }
                }
                else{
                    $this->htmlString .= $this->_getTab().$node->getText().$this->nl;
                }
            }
        }
        else if($node->isComment()){
            if($node->isFormatted() !== null && $node->isFormatted() === false){
                $this->htmlString .= $node->getComment();
            }
            else{
                $this->htmlString .= $this->_getTab().$node->getComment().$this->nl;
            }
        }
        else{
            if($node->mustClose()){
                $chCount = $node->children()->size();
                $this->nodesStack->push($node);
                if($node->isFormatted() !== null && $node->isFormatted() === false){
                    $this->htmlString .= $node->open();
                }
                else{
                    $nodeType = $node->getNodeName();
                    if($nodeType == 'pre' || $nodeType == 'textarea' || $nodeType == 'code'){
                        $this->htmlString .= $this->_getTab().$node->open();
                    }
                    else{
                        $this->htmlString .= $this->_getTab().$node->open().$this->nl;
                    }
                }
                $this->_addTab();
                for($x = 0 ; $x < $chCount ; $x++){
                    $nodeAtx = &$node->children()->get($x);
                    $this->_pushNode($nodeAtx);
                }
                $this->_reduceTab();
                $this->_popNode();
            }
            else{
                $this->htmlString .= $this->_getTab().$node->open().$this->nl;
            }
        }
    }
    private function _popNode(){
        $node = &$this->nodesStack->pop();
        if($node != null){
            if($node->isFormatted() !== null && $node->isFormatted() === false){
                $this->htmlString .= $node->close();
            }
            else{
                $nodeType = $node->getNodeName();
                if($nodeType == 'pre' || $nodeType == 'textarea' || $nodeType == 'code'){
                    $this->htmlString .= $node->close().$this->nl;
                }
                else{
                    $this->htmlString .= $this->_getTab().$node->close().$this->nl;
                }
            }
        }
    }
    /**
     * Increase tab size by 1.
     * @since 1.0
     */
    private function _addTab(){
        $this->tabCount += 1;
    }
    
    /**
     * Reduce tab size by 1.
     * If the tab size is 0, it will not reduce it more.
     * @since 1.0
     */
    private function _reduceTab(){
        if($this->tabCount > 0){
            $this->tabCount -= 1;
        }
    }
    /**
     * Returns the node as readable HTML code wrapped inside 'pre' element.
     * @param array $formattingOptions An associative array which contains 
     * an options for formatting the code. The available options are:
     * <ul>
     * <li><b>tab-spaces</b>: The number of spaces in a tab. Usually 4.</li>
     * <li><b>with-colors</b>: A boolean value. If set to true, the code will 
     * be highlighted with colors.</li>
     * <li><b>initial-tab</b>: Number of initial tabs</li>
     * <li><b>colors</b>: An associative array of highlight colors.</li>
     * </ul>
     * The array 'colors' has the following options:
     * <ul>
     * <li><b>bg-color</b>: The 'pre' block background color.</li>
     * <li><b>attribute-color</b>: HTML attribute name color.</li>
     * <li><b>attribute-value-color</b>: HTML attribute value color.</li>
     * <li><b>text-color</b>: Normal text color.</li>
     * <li><b>comment-color</b>: Comment color.</li>
     * <li><b>operator-color</b>: Assignment operator color.</li>
     * <li><b>lt-gt-color</b>: Less than and greater than color.</li>
     * <li><b>node-name-color</b>: Node name color.</li>
     * </ul>
     * @return string The node as readable HTML code wrapped inside 'pre' element.
     * @since 1.4
     */
    public function asCode($formattingOptions=HTMLNode::DEFAULT_CODE_FORMAT) {
        $formattingOptionsV = $this->_validateFormatAttributes($formattingOptions);
        $this->nl = HTMLDoc::NL;
        //number of spaces in a tab
        $spacesCount = $formattingOptionsV['tab-spaces'];
        $this->tabCount = $formattingOptionsV['initial-tab'];
        $this->tabSpace = '';
        for($x = 0 ; $x < $spacesCount ; $x++){
            $this->tabSpace .= ' ';
        }
        $usePre = isset($formattingOptions['use-pre']) ? $formattingOptions['use-pre'] === true : false;
        if($usePre){
            if($formattingOptionsV['with-colors'] === true){
                $this->codeString = '<pre style="margin:0;background-color:'.$formattingOptionsV['colors']['bg-color'].'; color:'.$formattingOptionsV['colors']['text-color'].'">'.$this->nl;
            }
            else{
                $this->codeString = '<pre style="margin:0">'.$this->nl;
            }
        }
        if($this->getNodeName() == 'html'){
            if($formattingOptionsV['with-colors']){
                $this->codeString .= $this->_getTab().'<span style="color:'.$formattingOptionsV['colors']['lt-gt-color'].'">&lt;</span>'
                        . '<span style="color:'.$formattingOptionsV['colors']['node-name-color'].'">!DOCTYPE html</span>'
                        . '<span style="color:'.$formattingOptionsV['colors']['lt-gt-color'].'">&gt;</span>'.$this->nl;
            }
            else{
                $this->codeString .= $this->_getTab().'&lt;!DOCTYPE html&gt;'.$this->nl;
            }
        }
        $this->nodesStack = new Stack();
        $this->_pushNodeAsCode($this,$formattingOptionsV);
        if($usePre){
            return $this->codeString.'</pre>';
        }
        return $this->codeString;
    }
    /**
     * 
     * @param array $FO Formatting options.
     * @return string
     * @since 1.5
     */
    private function _openAsCode($FO){
        $retVal = '';
        if($FO['with-colors'] === true){
            if(!$this->isTextNode() && !$this->isComment()){
                $retVal .= '<span style="color:'.$FO['colors']['lt-gt-color'].'">&lt;</span>'
                        . '<span style="color:'.$FO['colors']['node-name-color'].'">'.$this->getNodeName().'</span>';
                foreach ($this->getAttributes() as $attr => $val){
                    $retVal .= ' <span style="color:'.$FO['colors']['attribute-color'].'">'.$attr.'</span> '
                            . '<span style="color:'.$FO['colors']['operator-color'].'">=</span> '
                            . '<span style="color:'.$FO['colors']['attribute-value-color'].'">"'.$val.'"</span>';
                }
                $retVal .= '<span style="color:'.$FO['colors']['lt-gt-color'].'">&gt;</span>';
            }
        }
        else{
            if(!$this->isTextNode() && !$this->isComment()){
                $retVal .= '&lt;'.$this->getNodeName();
                foreach ($this->getAttributes() as $attr => $val){
                    $retVal .= ' '.$attr.' = "'.$val.'"';
                }
                $retVal .= '&gt;';
            }
        }
        return $retVal;
    }
    /**
     * 
     * @param array $FO Formatting options.
     * @return string
     * @since 1.5
     */
    private function _closeAsCode($FO){
        if($FO['with-colors'] === true){
            if(!$this->isTextNode() && !$this->isComment()){
                return '<span style="color:'.$FO['colors']['lt-gt-color'].'">&lt;/</span>'
                . '<span style="color:'.$FO['colors']['node-name-color'].'">'.$this->getNodeName().'</span>'
                        . '<span style="color:'.$FO['colors']['lt-gt-color'].'">&gt;</span>';
            }
        }
        else{
            if(!$this->isTextNode() && !$this->isComment()){
                return '&lt;/'.$this->getNodeName().'&gt;';
            }
        }
        return '';
    }
    /**
     * @param HTMLNode $node 
     * @param array $FO Formatting options.
     * @since 1.5
     */
    private function _pushNodeAsCode(&$node,$FO) {
        if($node->isTextNode()){
            $this->codeString .= $this->_getTab().$node->getText().$this->nl;
        }
        else if($node->isComment()){
            if($FO['with-colors'] === true){
                $this->codeString .= $this->_getTab().'<span style="color:'.$FO['colors']['comment-color'].'">&lt!--'.$node->getText().'--&gt;</span>'.$this->nl;
            }
            else{
                $this->codeString .= $this->_getTab().'&lt!--'.$node->getText().'--&gt;'.$this->nl;
            }
        }
        else{
            if($node->mustClose()){
                $chCount = $node->children()->size();
                $this->nodesStack->push($node);
                $name = $node->getNodeName();
                if($name  == 'pre' || $name == 'textarea' || $name == 'code'){
                    $this->codeString .= $this->_getTab().$node->_openAsCode($FO);
                }
                else{
                    $this->codeString .= $this->_getTab().$node->_openAsCode($FO).$this->nl;
                }
                $this->_addTab();
                for($x = 0 ; $x < $chCount ; $x++){
                    $nodeAtx = &$node->children()->get($x);
                    $this->_pushNodeAsCode($nodeAtx,$FO);
                }
                $this->_reduceTab();
                $this->_popNodeAsCode($FO);
            }
            else{
                $this->codeString .= $this->_getTab().$node->_openAsCode($FO).$this->nl;
            }
        }
    }
    /**
     * 
     * @param array $FO Formatting options.
     * @since 1.5
     */
    private function _popNodeAsCode($FO){
        $node = &$this->nodesStack->pop();
        if($node != null){
            $name = $node->getNodeName();
            if($name == 'pre' || $name == 'textarea' || $name == 'code'){
                $this->codeString .= $node->_closeAsCode($FO).$this->nl;
            }
            else{
                $this->codeString .= $this->_getTab().$node->_closeAsCode($FO).$this->nl;
            }
        }
    }
    /**
     * Validate formatting options.
     * @param array $FO An array of formatting options
     * @return array An array of formatting options
     * @since 1.5
     */
    private function _validateFormatAttributes($FO){
        $defaultFormat = self::DEFAULT_CODE_FORMAT;
        if(gettype($FO) == 'array'){
            foreach ($defaultFormat as $key => $value) {
                if(!isset($FO[$key])){
                    $FO[$key] = $value;
                }
            }
            foreach ($defaultFormat['colors'] as $key => $value) {
                if(!isset($FO['colors'][$key])){
                    $FO['colors'][$key] = $value;
                }
            }
        }
        else{
            return $defaultFormat;
        }
        //tab spaces count validation
        if(gettype($FO['tab-spaces']) == 'integer'){
            if($FO['tab-spaces'] < 0){
                $FO['tab-spaces'] = 0;
            }
            else if($FO['tab-spaces'] > 8){
                $FO['tab-spaces'] = 8;
            }
        }
        else{
            $FO['tab-spaces'] = self::DEFAULT_CODE_FORMAT['tab-spaces'];
        }
        //initial tab validation
        if(gettype($FO['initial-tab']) == 'integer'){
            if($FO['initial-tab'] < 0){
                $FO['initial-tab'] = 0;
            }
        }
        else{
            $FO['initial-tab'] = self::DEFAULT_CODE_FORMAT['initial-tab'];
        }
        return $FO;
    }
    /**
     * Returns the number of child nodes attached to the node.
     * If the node is a text node, a comment node or a void node, 
     * the method will return 0.
     * @return int The number of child nodes attached to the node.
     * @since 1.4
     */
    public function childrenCount() {
        if(!$this->isTextNode() && !$this->isComment() && $this->mustClose()){
            return $this->children()->size();
        }
        return 0;
    }
    /**
     * Returns the currently used tag space. 
     * @return string
     * @since 1.0
     */
    private function _getTab(){
        if($this->tabCount == 0){
            return '';
        }
        else{
            $tab = '';
            for($i = 0 ; $i < $this->tabCount ; $i++){
                $tab .= $this->tabSpace;
            }
            return $tab;
        }
    }
    /**
     * Returns a node based on its attribute value (Direct child).
     * @param string $attrName The name of the attribute.
     * @param string $attrVal The value of the attribute.
     * @return HTMLNode|null The method will return an object of type HTMLNode 
     * if a node is found. Other than that, the method will return null. Note 
     * that if there are multiple children with the same attribute and value, 
     * the first occurrence is returned.
     * @since 1.2
     */
    public function &getChildByAttributeValue($attrName,$attrVal) {
        if(!$this->isTextNode() && !$this->isComment()){
            for($x = 0 ; $x < $this->children()->size() ; $x++){
                $ch = $this->children()->get($x);
                if($ch->hasAttribute($attrName)){
                    if($ch->getAttributeValue($attrName) == $attrVal){
                        return $ch;
                    }
                }
            }
        }
        return $this->null;
    }
    /**
     * Returns the value of an attribute.
     * @param string $attrName The name of the attribute.
     * @return string|null The method will return the value of the attribute 
     * if found. If no such attribute, the method will return null.
     * @since 1.1
     */
    public function getAttributeValue($attrName) {
        if($this->hasAttribute($attrName)){
            return $this->attributes[$attrName];
        }
        return null;
    }
    /**
     * Checks if the node has a given attribute or not.
     * @param string $attrName The name of the attribute.
     * @return boolean true if the attribute is set.
     * @since 1.1
     */
    public function hasAttribute($attrName){
        if(!$this->isTextNode() && !$this->isComment()){
            $trimmed = strtolower(trim($attrName));
            return isset($this->attributes[$trimmed]);
        }
        return false;
    }
    /**
     * Returns non-formatted HTML string that represents the node as a whole.
     * @return string HTML string that represents the node as a whole.
     */
    public function __toString() {
        return $this->toHTML(false);
    }
}