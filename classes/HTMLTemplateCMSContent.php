<?php


class HTMLTemplateCMSContent extends HTMLTemplate
{
    /**
     * @var CMS
     */
    public $cms;

    /**
     * @param CMS $order_invoice
     * @param Smarty $smarty
     * @param bool $bulk_mode
     *
     * @throws PrestaShopException
     */
    public function __construct(CMS $cms, Smarty $smarty)
    {
        $this->cms = $cms;
        $this->smarty = $smarty;
        $this->title = $this->cms->meta_title;
        $this->shop = Context::getContext()->shop;
    }

    /**
     * Returns the template's HTML header.
     *
     * @return string HTML header
     */
    public function getHeader()
    {
        $this->assignCommonHeaderData();
        $this->smarty->assign(['header' => $this->cms->meta_title]);

        return $this->smarty->fetch($this->getTemplate('header'));
    }

    /**
     * Returns the template's HTML content.
     *
     * @return string HTML content
     */
    public function getContent()
    {
        return $this->cms->content;
    }

    /**
     * Returns the template filename when using bulk rendering.
     *
     * @return string filename
     */
    public function getBulkFilename()
    {
        return 'pages.pdf';
    }

    /**
     * Returns the template filename.
     *
     * @return string filename
     */
    public function getFilename()
    {
        return $this->cms->meta_title . '.pdf';
    }
}
