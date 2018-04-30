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

//first, load the root file
require_once '../root.php';

//sets the translation
PageAttributes::get()->loadTranslation(TRUE);

//load theme
PageAttributes::get()->loadTheme();

$lang = LANGUAGE['pages']['home'];

PageAttributes::get()->setTitle($lang['title']);

PageAttributes::get()->setDescription($lang['description']);
//end of page setup.

// check if user is logged in
//if not, go to login page
if(WebsiteFunctions::get()->getMainSession()->validateToken() != TRUE){
    header('location: login');
}
?>
<!DOCTYPE html>
<html lang="<?php echo PageAttributes::get()->getLang()?>">
    <head>
        <?php echo staticHeadTag('home', PageAttributes::get()->getLang())?>
    </head>
    <body itemscope itemtype="http://schema.org/WebPage">
        <div class="pa-container">
            <div class="pa-row">
                <div class="pa-row">
                    <?php echo staticAsideNav(PageAttributes::get()->getWritingDir(),1);?>
                    <div id="pa-main-content" itemscope itemtype="http://schema.org/WebPageElement" itemprop="mainContentOfPage" dir="<?php echo PageAttributes::get()->getWritingDir()?>" class="<?php echo 'pa-'.PageAttributes::get()->getWritingDir().'-col-ten'?> show-border">
                        <header id="header" itemscope itemtype="http://schema.org/WPHeader" class="pa-row">
                            <h1 name="page-title" itemprop="name" id="page-title"><?php echo $lang['title']?></h1>
                        </header>
                        <div class="pa-row">
                            Home Page
                        </div>
                        <?php echo staticFooter()?>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>


