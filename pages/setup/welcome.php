<?php

/* 
 * The MIT License
 *
 * Copyright 2018 Ibrahim.
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
if(!defined('ROOT_DIR')){
    header("HTTP/1.1 403 Forbidden");
    die(''
        . '<!DOCTYPE html>'
        . '<html>'
        . '<head>'
        . '<title>Forbidden</title>'
        . '</head>'
        . '<body>'
        . '<h1>403 - Forbidden</h1>'
        . '<hr>'
        . '<p>'
        . 'Direct access not allowed.'
        . '</p>'
        . '</body>'
        . '</html>');
}
if(Config::get()->isConfig()){
    header('location: '.SiteConfig::get()->getHomePage());
}
SystemFunctions::get()->setSetupStage('w');
$page = Page::get();
$page->setHasHeader(FALSE);
$page->setHasAside(FALSE);
$page->usingTheme(SiteConfig::get()->getAdminThemeName());
$pageLbls = $page->getLanguage()->get('pages/setup/welcome');
$translation = $page->getLanguage();
$page->setTitle($translation->get('pages/setup/welcome/title'));
$page->setDescription($translation->get('pages/setup/welcome/description'));
$page->insertNode(stepsCounter($page->getLanguage()->get('pages/setup/setup-steps'),0), 'main-content-area');
$page->insertNode(langSwitch(),'main-content-area');
$page->insertNode(pageBody($pageLbls),'main-content-area');
$page->insertNode(footer($page->getLanguage()),'main-content-area');
echo $page->getDocument();

function pageBody($lbls){
    $body = new HTMLNode();
    $body->setClassName('pa-row');
    $col = new HTMLNode();
    $col->setClassName('pa-'.Page::get()->getWritingDir().'-col-twelve');
    $p1 = new PNode();
    $p1->addText($lbls['help']['h-1']);
    $col->addChild($p1);
    $p2 = new PNode();
    $p2->addText($lbls['help']['h-2']);
    $col->addChild($p2);
    $p3 = new PNode();
    $p3->addText($lbls['help']['h-3']);
    $col->addChild($p3);
    $ul = new UnorderedList();
    $li1 = new ListItem(TRUE,$lbls['help']['h-4']);
    $ul->addChild($li1);
    $li2 = new ListItem(TRUE,$lbls['help']['h-5']);
    $ul->addChild($li2);
    $col->addChild($ul);
    $body->addChild($col);
    return $body;
}

function langSwitch(){
    $node = new HTMLNode();
    $node->setClassName('pa-row');
    $arLang = new LinkNode('pages/setup/welcome?lang=ar', 'العربية');
    $arLang->setClassName('pa-'.Page::get()->getWritingDir().'-col-two');
    $node->addChild($arLang);
    $enLang = new LinkNode('pages/setup/welcome?lang=en', 'English');
    $enLang->setClassName('pa-'.Page::get()->getWritingDir().'-col-two');
    $node->addChild($enLang);
    return $node;
}
/**
 * 
 * @param Language $lang
 * @return HTMLNode
 */
function footer($lang){
    $node = new HTMLNode();
    $node->setClassName('pa-row');
    $nextButton = new HTMLNode('button');
    $nextButton->setAttribute('onclick', 'window.location.href = \'s/database-setup\'');
    $nextButton->setClassName('pa-'.Page::get()->getWritingDir().'-col-three');
    $nextButton->setID('next-button');
    $nextButton->setAttribute('data-action', 'ok');
    $nextButton->addChild(HTMLNode::createTextNode($lang->get('general/next')));
    $node->addChild($nextButton);
    return $node;
}

function stepsCounter($lang,$active){
    $node = new HTMLNode();
    $node->setClassName('pa-row');
    $step1 = new HTMLNode();
    $step1->setClassName('pa-'.Page::get()->getWritingDir().'-col-two');
    $step1->addChild(HTMLNode::createTextNode($lang['welcome']));
    $node->addChild($step1);
    
    $step2 = new HTMLNode();
    $step2->setClassName('pa-'.Page::get()->getWritingDir().'-col-two');
    $step2->addChild(HTMLNode::createTextNode($lang['database-setup']));
    $node->addChild($step2);
    
    $step3 = new HTMLNode();
    $step3->setClassName('pa-'.Page::get()->getWritingDir().'-col-two');
    $step3->addChild(HTMLNode::createTextNode($lang['email-account']));
    $node->addChild($step3);
    
    $step4 = new HTMLNode();
    $step4->setClassName('pa-'.Page::get()->getWritingDir().'-col-two');
    $step4->addChild(HTMLNode::createTextNode($lang['admin-account']));
    $node->addChild($step4);
    
    $step5 = new HTMLNode();
    $step5->setClassName('pa-'.Page::get()->getWritingDir().'-col-two');
    $step5->addChild(HTMLNode::createTextNode($lang['website-config']));
    $node->addChild($step5);
    
    if($active == 0){
        $step1->setAttribute('style', 'background-color:#efaa32');
    }
    else if($active == 1){
        $step2->setAttribute('style', 'background-color:#efaa32');
    }
    else if($active == 2){
        $step3->setAttribute('style', 'background-color:#efaa32');
    }
    else if($active == 3){
        $step4->setAttribute('style', 'background-color:#efaa32');
    }
    else if($active == 4){
        $step5->setAttribute('style', 'background-color:#efaa32');
    }
    return $node;
}