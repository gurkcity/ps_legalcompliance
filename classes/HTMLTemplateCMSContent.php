<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */
class HTMLTemplateCMSContent extends HTMLTemplate
{
    public $cms;

    public function __construct(CMS $cms, Smarty $smarty)
    {
        $this->cms = $cms;
        $this->smarty = $smarty;
        $this->title = $this->cms->meta_title;
        $this->shop = Context::getContext()->shop;
    }

    public function getHeader()
    {
        $this->assignCommonHeaderData();
        $this->smarty->assign([
            'header' => $this->cms->meta_title,
        ]);

        return $this->smarty->fetch($this->getTemplate('header'));
    }

    public function getContent()
    {
        return $this->cms->content;
    }

    public function getBulkFilename()
    {
        return 'pages.pdf';
    }

    public function getFilename()
    {
        return $this->cms->meta_title . '.pdf';
    }
}
