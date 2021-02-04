<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Promostickers extends Module
{
    /**
     * @var string
     */
    protected $html = '';

    /**
     * @var string
     */
    protected $confirm = '';

    /**
     * @var string
     */
    protected $inform = '';

    /**
     * @var string
     */
    protected $warn = '';

    /**
     * @var string
     */
    protected $error = '';

    /**
     * @var bool
     */
    protected $config_form = false;

    /**
     * @var array
     */
    private $imageTypes = array();

    /**
     * Promostickers constructor.
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'promostickers';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.3';
        $this->author = 'verts';
        $this->need_instance = 0;
        $this->html = '';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $config = Configuration::getMultiple(
            array(
                'PROMOSTICKERS_TYPES',
                'PROMOSTICKERS_RATIO'
            )
        );
        if (!isset($config['PROMOSTICKERS_TYPES'])) {
            $config['PROMOSTICKERS_TYPES'] = '';
        }
        $tmp = explode(',', $config['PROMOSTICKERS_TYPES']);
        foreach (ImageType::getImagesTypes('products') as $type) {
            if (in_array($type['id_image_type'], $tmp)) {
                $this->imageTypes[] = $type;
            }
        }

        $this->displayName = $this->l('Promotional Stickers Free');
        $this->description = $this->l(
            'allows to promote products by adding sticker on product cover image. 
            Individual Sticker settings for each product'
        );

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Promotional Stickers 
                                            module? All settings for existing stickers will be lost!');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * @return bool
     * @throws PrestaShopException
     *
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {

        include(dirname(__FILE__) . '/sql/install.php');

        $multistore = Shop::isFeatureActive();

        if ($multistore == true) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook('actionProductAdd') ||
            !$this->registerHook('actionProductDelete') ||
            !$this->registerHook('actionProductUpdate') ||
            !$this->registerHook('displayAdminProductsExtra') ||
            !Configuration::updateValue('PROMOSTICKERS_RATIO', 3)
        ) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     * Uninstall
     */
    public function uninstall()
    {

        include(dirname(__FILE__) . '/sql/uninstall.php');

        if (!parent::uninstall() ||
            !Configuration::deleteByName('PROMOSTICKERS_RATIO') ||
            !Configuration::deleteByName('PROMOSTICKERS_TYPES')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Load the configuration form
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if ((((bool)Tools::isSubmit('submitPromostickersModule')) == true)
            || (((bool)Tools::isSubmit('submitUploadSticker')) == true)
            || (((bool)Tools::isSubmit('delete'.$this->name)) == true)
            || (((bool)Tools::isSubmit('submitBulkdelete'.$this->name)) == true)
            || (((bool)Tools::isSubmit('submitReset'.$this->name.'Img')) == true)
            || (((bool)Tools::isSubmit('updateproductstickers')) == true)
            || (((bool)Tools::isSubmit('viewproductstickers')) == true)
            || (((bool)Tools::isSubmit('deleteproductstickers')) == true)
            || (((bool)Tools::isSubmit('submitBulkregenerateproductstickers')) == true)
            || (((bool)Tools::isSubmit('submitBulkdeleteproductstickers')) == true)
            || (((bool)Tools::isSubmit('submitReset'.$this->name.'Prod')) == true)
            || (((bool)Tools::isSubmit('submitRegeneratePromostickers')) == true)
        ) {
            $this->postProcess();
            $this->html .= $this->confirm;
            $this->html .= $this->inform;
            $this->html .= $this->warn;
            $this->html .= $this->error;
        }

        $this->context->smarty->assign('module_dir', $this->_path);

       // $this->html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->renderUploadSticker();
        $this->html .= $this->renderListStickers();
        $this->html .= $this->renderListProducts();
        $this->html .= $this->renderRegeneratePromostickers();

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPromostickersModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Size of Stickers:'),
                        'name' => 'PROMOSTICKERS_RATIO',
                        'class' => 'fixed-width-md',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id' => '4',
                                    'name' => $this->l('Small')
                                ),
                                array(
                                    'id' => '3',
                                    'name' => $this->l('Medium')
                                ),
                                array(
                                    'id' => '2',
                                    'name' => $this->l('Large')
                                )
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'checkbox',
                        'name' => 'PROMOSTICKERS_TYPES',
                        'label' => $this->l('Choose image types for display stickers:'),
                        'values' => array(
                            'query' => $this->getImgProdTypes(),
                            'id' => 'id_image_type',
                            'name' => 'label'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                ),
            ),
        );
    }

    /**
     * Button "helperForm UPLOAD STICKER IMAGE"
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function renderUploadSticker()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('UPLOAD STICKER IMAGE'),
                    'icon' => 'icon-upload'
                ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'label' => $this->l('Upload new Sticker image'),
                        'hint' => 'This feature is available in the PRO Version',
                        'name' => 'PROMOSTICKERS_IMG',
                        'desc' => $this->l('Upload an sticker image (transparent PNG)'),
                        'lang' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Upload'),
                    'icon' => 'process-icon-upload'
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitUploadSticker';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        //return $helper->generateForm(array($fields_form));
    }

    /**
     * Button "regeneratePromostickers"
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function renderRegeneratePromostickers()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('REGENERATE ALL THUMBNAILS WITH STICKERS'),
                    'icon' => 'icon-cogs'
                ),
                'submit' => array(
                    'title' => $this->l('Regenerate thumbnails'),
                    'icon' => 'process-icon-cogs'
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRegeneratePromostickers';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm(array($fields_form));
    }

    /**
     * product image types
     * @return array
     * @throws PrestaShopDatabaseException
     */
    protected function getImgProdTypes()
    {
        $types = ImageType::getImagesTypes('products');
        foreach ($types as $key => $type) {
            $types[$key]['label'] = $type['name'] . ' (' . $type['width'] . ' x ' . $type['height'] . ')';
        }

        return $types;
    }

    /**
     * get StickersList in adminProdExtra (Select)
     * @param null $html
     * @return array
     */
    protected function getListStickers($html = null)
    {
        $dirname = dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR;

        $img = array();

        if ($images = glob($dirname . "*.{png,PNG}", GLOB_BRACE)) {
            foreach ($images as $key => $image) {
                $img[$key]['img'] = basename($image);
                if ($html == 'html') {
                    $img[$key]['picture'] = $this->stickerImgHtml(basename($image));
                } elseif ($html == 'url') {
                    $img[$key]['picture'] = _PS_BASE_URL_ . __PS_BASE_URI__ . _MODULE_DIR_ . $this->name
                        . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'img'
                        . DIRECTORY_SEPARATOR . basename($image);
                }
            }
        }

        return $img;
    }


    /**
     * Fonts in ./views/fonts (Select box)
     * @return array
     */
    protected function getListFonts()
    {
        $dirname = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR;

        $fonts = array();

        if ($files = glob($dirname . "*.{ttf,TTF}", GLOB_BRACE)) {
            foreach ($files as $key => $file) {
                 $fonts[$key]['font'] = basename($file);
            }
        }

        return $fonts;
    }

    /**
     * pagination
     * @param $content
     * @param int $page
     * @param int $pagination
     * @return array
     */
    public function paginateStickers($content, $page = 1, $pagination = 20)
    {
        if (count($content) > $pagination) {
            $content = array_slice($content, $pagination * ($page - 1), $pagination);
        }

        return $content;
    }

    /**
     * Get list of stickers (images)
     * @return string
     * @throws PrestaShopException
     */
    protected function renderListStickers()
    {

        $fields_list = array(
            'img' => array(
                'title' => $this->l('File Name'),
                'align' => 'left',
                'width' => 200,
                'search' => true,
            ),
            'picture' => array(
                'title' => $this->l('Image'),
                'float' => true,
                'align' => 'left',
                'width' => 200,
                'search' => false,
            ),
        );

        $helper_list = new HelperList();
        $helper_list->module = $this;
        $helper_list->title = $this->l('List of Stickers');
        $helper_list->shopLinkType = '';
        $helper_list->no_link = true;
        $helper_list->show_toolbar = true;
        $helper_list->simple_header = false;
        $helper_list->identifier = 'img';
        $helper_list->imageType = 'png';
        $helper_list->table = 'promostickers';
        $helper_list->list_id = $this->name . 'Img';
        $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper_list->token = Tools::getAdminTokenLite('AdminModules');
        $helper_list->list = '';
        $helper_list->actions = array('delete');
        //$helper_list->toolbar_btn = true;
        $helper_list->toolbar_btn = array(
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to Modules list')
            )
        );
        $helper_list->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
        // This is needed for displayEnableLink to avoid code duplication
        $this->_helperlist = $helper_list;

        /* Retrieve list data */
        $stickers = $this->getImgStickers($helper_list);
        $helper_list->listTotal = count($stickers);

        /* Paginate the result */
        $helper_list->_pagination = array(10, 25, 50, 100, 200);
        $helper_list->_default_pagination = 25;
        $page = ($page = Tools::getValue('submitFilter' . $helper_list->list_id)) ? $page : 1;
        $pagination = ($pagination = Tools::getValue($helper_list->list_id.'_pagination')) ? $pagination : 25;
        $stickers = $this->paginateStickers($stickers, $page, $pagination);

        return $helper_list->generateList($stickers, $fields_list);
    }

    /**
     * @param $helper
     * @return array
     */
    public function getImgStickers($helper)
    {

        if (Tools::getValue($helper->list_id . "Orderway") != '') {
            $this->context->cookie->{$helper->list_id . 'Orderway'} = Tools::getValue($helper->list_id . "Orderway");
        }
        if (empty($this->context->cookie->{$helper->list_id . 'Orderway'})) {
            $this->context->cookie->{$helper->list_id . 'Orderway'} = 'asc';
        }

        if (Tools::getValue($helper->list_id . "Filter_img", null) !== null) {
            $strpos_img = pSQL(Tools::getValue($helper->list_id . "Filter_img"));
            $this->context->cookie->{$helper->list_id . 'Filter_img'} = $strpos_img;
        }

        $dirname = dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR;

        $img = array();

        if ($images = glob($dirname . "*.{png,PNG}", GLOB_BRACE)) {
            if ($this->context->cookie->{$helper->list_id . 'Orderway'} !== 'desc') {
                sort($images);
            } else {
                rsort($images);
            }

            foreach ($images as $key => $image) {
                if ((isset($strpos_img)) && (!empty($strpos_img))) {
                    if (Tools::strpos(basename($image), $strpos_img) !== false) {
                        $img[$key]['img'] = basename($image);
                        $img[$key]['picture'] = $this->stickerImgHtml(basename($image));
                    }
                } else {
                    $img[$key]['img'] = basename($image);
                    $img[$key]['picture'] = $this->stickerImgHtml(basename($image));
                }
            }
        }

        return $img;
    }

    /**
     * Get list of products with active stickers
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderListProducts()
    {
        $fields_list = array(
            'id_sticker' => array(
                'title' => $this->l('Sticker ID'),
                'search' => false,
                'type' => 'int',
                'class' => 'col-md-1',
            ),
            'id_product' => array(
                'title' => $this->l('Product ID'),
                'search' => true,
                'type' => 'int',
                'class' => 'col-md-1',
            ),
            'shop' => array(
                'title' => $this->l('Shop'),
                'search' => true,
                'class' => 'col-md-1',
            ),
            'product_name' => array(
                'title' => $this->l('Product Name'),
                'search' => true,
                'class' => 'col-md-3',
            ),
            'promo_img' => array(
                'title' => $this->l('Sticker file'),
                'align' => 'left',
                'orderBy' => true,
                'filter' => true,
                'search' => true,
                'class' => 'col-md-2',
            ),
            'promo_thumb' => array(
                'title' => $this->l('Sticker image'),
                'float' => true,
                'align' => 'center',
                'orderby' => false,
                'filter' => false,
                'search' => false,
                'class' => 'col-md-1',
            ),
            'promo_txt' => array(
                'title' => $this->l('Sticker text'),
                'search' => true,
                'class' => 'col-md-1',
            ),
            'promo_status' => array(
                'title' => $this->l('State'),
                'search' => false,
                'class' => 'col-md-1',
                'icon' => array(
                    0 => 'disabled.gif',
                    1 => 'enabled.gif',
                    'default' => 'disabled.gif'
                )
            ),
        );

        $helper_list = new HelperList();
        $helper_list->module = $this;
        $helper_list->title = $this->l('List of products with installed stickers');
        $helper_list->shopLinkType = '';
        $helper_list->no_link = true;
        $helper_list->show_toolbar = false;
        $helper_list->simple_header = false;
        $helper_list->identifier = 'id_sticker';
        $helper_list->table = 'productstickers';
        $helper_list->list_id = $this->name . 'Prod';
        $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name;
        $helper_list->token = Tools::getAdminTokenLite('AdminModules');
        $helper_list->list = '';
        $helper_list->actions = array('edit', 'view', 'delete');
        //$helper_list->toolbar_btn = true;
        $helper_list->toolbar_btn = array(
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to Modules list')
            )
        );
        $helper_list->bulk_actions = array(
            'regenerate' => array(
                'text' => $this->l('Regenerate thumbnails'),
                'icon' => 'icon-cogs',
                'confirm' => $this->l('Do you want regenerate thumbnails for selected products?')
            ),
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
        // This is needed for displayEnableLink to avoid code duplication
        $this->_helperlist = $helper_list;

        /* Retrieve list data */
        $stickers = $this->getProductStickers($helper_list);

        $helper_list->listTotal = count($stickers);

        /* Paginate the result */
        $helper_list->_pagination = array(10, 25, 50, 100, 200);
        $helper_list->_default_pagination = 25;
        $page = ($page = Tools::getValue('submitFilter' . $helper_list->list_id)) ? $page : 1;
        $pagination = ($pagination = Tools::getValue($helper_list->list_id . '_pagination')) ? $pagination : 25;
        $stickers = $this->paginateStickers($stickers, $page, $pagination);

        return $helper_list->generateList($stickers, $fields_list);
    }

    /**
     * Get stickers where they are installed +filter +order
     * @param $helper
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public function getProductStickers($helper)
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $id_shop = ($this->context->shop->id);
        $allShop = Context::getContext()->cookie->shopContext;
        if ($allShop == false) {
            $sql = 'SELECT count(*) FROM ' . _DB_PREFIX_ . $this->name;
        } else {
            $sql = 'SELECT count(*) FROM ' . _DB_PREFIX_ . $this->name . ' WHERE id_store="' . (int)$id_shop . '"';
        }
        if (Tools::isSubmit('submitFilter')) {
            $sql .= $this->setWhereClause($helper);
        }
        if (Tools::getValue($helper->list_id . "Orderby") != '') {
            $this->context->cookie->{$helper->list_id . 'Orderby'} = Tools::getValue($helper->list_id . "Orderby");
        }
        if (empty($this->context->cookie->{$helper->list_id . 'Orderby'})) {
            $this->context->cookie->{$helper->list_id . 'Orderby'} = 'id_product';
        }
        if (Tools::getValue($helper->list_id . "Orderway") != '') {
            $this->context->cookie->{$helper->list_id . 'Orderway'} = Tools::getValue($helper->list_id . "Orderway");
        }
        if (empty($this->context->cookie->{$helper->list_id . 'Orderway'})) {
            $this->context->cookie->{$helper->list_id . 'Orderway'} = 'asc';
        }
        $orderby = $this->context->cookie->{$helper->list_id . 'Orderby'};
        $orderway = $this->context->cookie->{$helper->list_id . 'Orderway'};
        $helper->orderBy = $orderby;
        $helper->orderWay = Tools::strtoupper($orderway);

        //pagination
        $cookiePagination = $this->context->cookie->{$helper->list_id . '_pagination'};
        $selected_pagination = (int)Tools::getValue($helper->list_id . '_pagination', $cookiePagination);

        if ($selected_pagination <= 0) {
            $selected_pagination = 20;
        }

        $this->context->cookie->{$helper->list_id . '_pagination'} = $selected_pagination;
        $page = (int)Tools::getValue('submitFilter' . $helper->list_id, 1);

        if (!$page) {
            $page = 1;
        }

        $start = ($page - 1) * $selected_pagination;
        $allShop = Context::getContext()->cookie->shopContext;

        if ($allShop == false) {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . $this->name;
            $sql .= $this->setWhereClause($helper);
            $sql .= ' ORDER BY ' . $orderby . ' ' . $orderway . ' LIMIT ' . $start . ',' . $selected_pagination;
            $rows = $db->executeS($sql, true, false);
        } else {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . $this->name . ' WHERE id_store="' . (int)$id_shop . '"';
            $sql .= $this->setWhereClause($helper);
            $sql .= ' ORDER BY ' . $orderby . ' ' . $orderway . ' LIMIT ' . $start . ',' . $selected_pagination;
            $rows = $db->executeS($sql, true, false);
        }

        foreach ($rows as $key => $sticker) {
            $rows[$key]['promo_img'] = $sticker['promo_img'];
            $rows[$key]['promo_thumb'] = $this->stickerImgHtml($sticker['promo_img']);
        }

        return $rows;
    }

    /**
     * 'WHERE' clause for filters renderListProducts
     * @param $helper
     * @return bool|string
     */
    public function setWhereClause($helper)
    {
        $array_where = array();
        $allShop = Context::getContext()->cookie->shopContext;

        if ($allShop == false) {
            $where = ' WHERE ';
        } else {
            $where = ' AND ';
        }
        if (Tools::getValue($helper->list_id . "Filter_id_sticker", null) !== null) {
            $sql_id_sticker = pSQL(Tools::getValue($helper->list_id . "Filter_id_sticker"));
            $this->context->cookie->{$helper->list_id . 'Filter_id_sticker'} = $sql_id_sticker;
        }
        if (isset($sql_id_sticker)) {
            $array_where[] = "id_sticker LIKE '%" . $sql_id_sticker . "%'";
        }
        if (Tools::getValue($helper->list_id . "Filter_id_product", null) !== null) {
            $sql_id_product = pSQL(Tools::getValue($helper->list_id . "Filter_id_product"));
            $this->context->cookie->{$helper->list_id . 'Filter_id_product'} = $sql_id_product;
        }

        if (isset($sql_id_product)) {
            $array_where[] = "id_product LIKE '%" . $sql_id_product . "%'";
        }

        if (Tools::getValue($helper->list_id . "Filter_id_store", null) !== null) {
            $sql_id_store = pSQL(Tools::getValue($helper->list_id . "Filter_id_store"));
            $this->context->cookie->{$helper->list_id . 'Filter_id_store'} = $sql_id_store;
        }

        if (isset($sql_id_store)) {
            $array_where[] = "id_store LIKE '%" . $sql_id_store . "%'";
        }

        if (Tools::getValue($helper->list_id . "Filter_product_name", null) !== null) {
            $sql_product_name = pSQL(Tools::getValue($helper->list_id . "Filter_product_name"));
            $this->context->cookie->{$helper->list_id . 'Filter_product_name'} = $sql_product_name;
        }
        if (isset($sql_product_name)) {
            $array_where[] = "product_name LIKE '%" . $sql_product_name . "%'";
        }
        if (Tools::getValue($helper->list_id . "Filter_shop", null) !== null) {
            $sql_name_shop = pSQL(Tools::getValue($helper->list_id . "Filter_shop"));
            $this->context->cookie->{$helper->list_id . 'Filter_shop'} = $sql_name_shop;
        }
        if (isset($sql_name_shop)) {
            $array_where[] = "id_store LIKE '%" . $sql_name_shop . "%'";
        }
        if (Tools::getValue($helper->list_id . "Filter_promo_img", null) !== null) {
            $sql_promo_img = pSQL(Tools::getValue($helper->list_id . "Filter_promo_img"));
            $this->context->cookie->{$helper->list_id . 'Filter_promo_img'} = $sql_promo_img;
        }
        if (isset($sql_promo_img)) {
            $array_where[] = "promo_img LIKE '%" . $sql_promo_img . "%'";
        }
        if (Tools::getValue($helper->list_id . "Filter_language", null) !== null) {
            $sql_language = pSQL(Tools::getValue($helper->list_id . "Filter_language"));
            $this->context->cookie->{$helper->list_id . 'Filter_language'} = $sql_language;
        }
        if (isset($sql_language)) {
            $array_where[] = "id_lang LIKE '%" . $sql_language . "%'";
        }
        if (Tools::getValue($helper->list_id . "Filter_promo_txt", null) !== null) {
            $sql_promo_txt = pSQL(Tools::getValue($helper->list_id . "Filter_promo_txt"));
            $this->context->cookie->{$helper->list_id . 'Filter_promo_txt'} = $sql_promo_txt;
        }
        if (isset($sql_promo_txt)) {
            $array_where[] = "promo_txt LIKE '%" . $sql_promo_txt . "%'";
        }

        if (empty($array_where)) {
            return false;
        } else {
            $where .= implode(' AND ', $array_where);
        }
        return $where;
    }

    /**
     * display img as thumbs in admin
     * @param $params
     * @return string
     */
    public function stickerImgHtml($params)
    {
        $html = '<img src="'. _MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR
            . 'views' .DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $params . '" height="64">';

        return $html;
    }

    /**
     * Set values for the inputs.
     * @return array
     * @throws PrestaShopDatabaseException
     */
    protected function getConfigFormValues()
    {
        $config_fields = array(
            'PROMOSTICKERS_RATIO' => Configuration::get('PROMOSTICKERS_RATIO', true),
        );

        //get all images type available
        $types = ImageType::getImagesTypes('products');
        $id_image_type = array();
        foreach ($types as $type) {
            $id_image_type[] = $type['id_image_type'];
        }

        //get images type from $_POST
        $id_image_type_post = array();
        foreach ($id_image_type as $id) {
            if (Tools::getValue('PROMOSTICKERS_TYPES_' . (int)$id)) {
                $id_image_type_post['PROMOSTICKERS_TYPES_' . (int)$id] = true;
            }
        }

        //get images type from Configuration
        $id_image_type_config = array();
        if ($confs = Configuration::get('PROMOSTICKERS_TYPES')) {
            $confs = explode(',', Configuration::get('PROMOSTICKERS_TYPES'));
        } else {
            $confs = array();
        }

        foreach ($confs as $conf) {
            $id_image_type_config['PROMOSTICKERS_TYPES_' . (int)$conf] = true;
        }

        //return only common values and value from post
        if (Tools::isSubmit('btnSubmit')) {
            $config_fields = array_merge($config_fields, array_intersect($id_image_type_post, $id_image_type_config));
        } else {
            $config_fields = array_merge($config_fields, $id_image_type_config);
        }

        return $config_fields;
    }

    /**
     * Save form data.
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function postProcess()
    {
        $adminControllers=AdminController::$currentIndex;
        $token='&token='.Tools::getAdminTokenLite('AdminModules');
        $configAndTask='&configure='.$this->name;

        if (Tools::isSubmit('submitPromostickersModule')) {
            $types = ImageType::getImagesTypes('products');
            $id_image_type = array();
            foreach ($types as $type) {
                if (Tools::getValue('PROMOSTICKERS_TYPES_' . (int)$type['id_image_type'])) {
                    $id_image_type[] = $type['id_image_type'];
                }
            }
            Configuration::updateValue('PROMOSTICKERS_TYPES', implode(',', $id_image_type));
            Configuration::updateValue('PROMOSTICKERS_RATIO', Tools::getValue('PROMOSTICKERS_RATIO'), false);
            return $this->confirm = $this->displayConfirmation($this->l('The settings have been updated.'));
        } elseif (Tools::isSubmit('submitRegeneratePromostickers')) {
            if ($result = $this->getSticker()) {
                foreach ($result as $sticker) {
                    $params = array(
                        'id_image' => (int)$sticker['id_image'],
                        'id_product' => (int)$sticker['id_product'],
                        'id_store' => (int)$sticker['id_store'],
                        'id_lang' => (int)$sticker['id_lang'],
                    );
                    $this->actionPromoStickers($params);
                }
                return $this->confirm = $this->displayConfirmation($this->l(
                    'The thumbnails has been regenerated (for products with installed stickers )'
                ));
            } else {
                return $this->warn = $this->displayWarning($this->l('You have no stickers installed. Nothing to do!'));
            }
        } elseif (Tools::isSubmit('submitUploadSticker')) {
            return $this->error = $this->displayWarning($this->l(
                    'This feature is available in the PRO Version'
                ));
        } elseif (Tools::isSubmit('delete'.$this->name)) {
            $file = Tools::getValue('img');
            $this->deletePromostickers($file);
            return $this->confirm = $this->displayConfirmation($this->l(
                'The sticker image deleted. All entries with this sticker also deleted 
                and thumbnails of product cover image regenerated.'
            ));
        } elseif (Tools::isSubmit('submitBulkdelete'.$this->name)) {
            if ($files = Tools::getValue('promostickersImgBox')) {
                foreach ($files as $file) {
                    $this->deletePromostickers($file);
                }
                return $this->confirm = $this->displayConfirmation($this->l(
                    'Selected sticker images deleted. All entries with this stickers also deleted 
                and thumbnails of product cover images regenerated.'
                ));
            } else {
                return $this->warn = $this->displayWarning($this->l('No items selected!'));
            }
        } elseif (Tools::isSubmit('submitBulkregenerateproductstickers')) {
            if ($stickers = Tools::getValue('promostickersProdBox')) {
                foreach ($stickers as $id_sticker) {
                    $sticker_data = $this->getStickerDataById($id_sticker);
                    $params = array(
                        'id_image' => (int)$sticker_data[0]['id_image'],
                        'id_product' => (int)$sticker_data[0]['id_product'],
                        'id_store' => (int)$sticker_data[0]['id_store'],
                        'id_lang' => (int)$sticker_data[0]['id_lang'],
                    );
                    $this->actionPromoStickers($params);
                }
                return $this->confirm = $this->displayConfirmation($this->l(
                    'The thumbnails for selected products has been regenerated'
                ));
            } else {
                return $this->warn = $this->displayWarning($this->l('No items selected!'));
            }
        } elseif (Tools::isSubmit('deleteproductstickers')) {
            return $this->error = $this->displayWarning($this->l(
                'This feature is available in the PRO Version'
            ));
        } elseif (Tools::isSubmit('submitBulkdeleteproductstickers')) {
            return $this->error = $this->displayWarning($this->l(
                'This feature is available in the PRO Version'
            ));
        } elseif (Tools::isSubmit('submitReset'.$this->name.'Img')) {
            $this->resetFilterImg();
            Tools::redirectAdmin($adminControllers . $token . $configAndTask);
        } elseif (Tools::isSubmit('submitReset'.$this->name.'Prod')) {
            $this->resetFilterProd();
            Tools::redirectAdmin($adminControllers.$token.$configAndTask);
        } elseif (Tools::isSubmit('viewproductstickers')) {
            $sticker_data = $this->getStickerDataById(Tools::getValue('id_sticker'));
            // one way to get product link
            $productLink = $this->context->link->getProductLink($sticker_data[0]['id_product']);
            // another way to get product link
            //$linkObj = new Link();
            //$productLink = $linkObj->getProductLink($sticker_data[0]['id_product']);
            Tools::redirect($productLink);
        } elseif (Tools::isSubmit('updateproductstickers')) {
            return $this->error = $this->displayWarning($this->l(
                'This feature is available in the PRO Version'
            ));
        }

        return '';
    }

    /**
     * Generate PromoStickers by ProductAdd
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionProductAdd()
    {
        $this->actionProductSticker();
    }

    /**
     * delete Product -> detele promostickers data from db table for this product
     * @param $params
     */
    public function hookActionProductDelete($params)
    {
        $id_product = (int)$params['id_product'];
        Db::getInstance()->delete($this->name, "id_product=$id_product");
    }

    /**
     * Generate PromoStickers by ProductUpdate
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionProductUpdate()
    {
        $this->actionProductSticker();
    }

    /**
     * Generate PromoStickers and DB data
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function actionProductSticker()
    {

        $shops = array();

        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $shops[] = $this->context->shop->id;
        } else {
            foreach (Shop::getCompleteListOfShopsID() as $shop) {
                $shops[] = (int)$shop;
            }
        }

        foreach ($shops as $id_shop) {
            $id_store = (int)$id_shop;
            $tempShop = new Shop($id_store);
            $id_product = pSQL(Tools::getValue('id_product'));
            $promo_status = pSQL(Tools::getValue('promo_status'));
            $promo_img = pSQL(Tools::getValue('promo_img'));
            $product_name = (new Product($id_product, false, $this->context->language->id, $id_store))->name;
            $id_lang = $this->context->language->id;
            $image = Product::getCover((int)($id_product));
            $params = array(
                'id_image' => (int)$image['id_image'],
                'id_product' => (int)$id_product,
                'id_store' => (int)$id_store,
                'id_lang' => (int)$id_lang,
            );
            $query = array(
                'table' => pSQL($this->name),
                'data' => array(
                    'promo_status' => (int)$promo_status,
                    'promo_img' => pSQL($promo_img),
//                    'promo_img_horizontal' => 1,
//                    'promo_img_vertical' => 0.5,
                    'promo_txt' => pSQL(Tools::getValue('promo_txt')),
                    'promo_txt_font' => pSQL(Tools::getValue('promo_txt_font', 'comic.ttf')),
//                    'promo_txt_size' => 6,
                    'promo_txt_color' => pSQL(Tools::getValue('promo_txt_color')),
                    'promo_txt_shadow' => pSQL(Tools::getValue('promo_txt_shadow')),
                    'promo_txt_horizontal' => pSQL(Tools::getValue('promo_txt_horizontal')),
                    'promo_txt_vertical' => pSQL(Tools::getValue('promo_txt_vertical', 0.5)),
                    'id_image' => (int)$params['id_image'],
                    'id_product' => (int)$id_product,
                    'product_name' => pSQL($product_name),
                    'id_store' => (int)$id_store,
                    'shop' => pSQL($tempShop->name),
                    'id_lang' => (int)$id_lang,
                    'language' => pSQL($this->context->language->name),
                ),
                'where' => 'id_product = ' . (int)$id_product
                    . ' AND id_store = ' . (int)$id_store
                    . ' AND id_lang = ' . (int)$id_lang,
            );
            $sticker_exist = false;

            if ($this->getSticker(false, $id_product, $id_store, $id_lang)) {
                $sticker_exist = true;
            }

            if ((!$sticker_exist) && ($promo_status == 1)) {
                if (!Db::getInstance()->insert($query['table'], $query['data'])) {
                    $this->context->controller->_error = Tools::displayError();
                }
                if (!$this->actionPromoStickers($params)) {
                    $this->context->controller->_error = Tools::displayError();
                }
            } elseif (($sticker_exist) && ($promo_status == 1)) {
                if (!Db::getInstance()->update($query['table'], $query['data'], $query['where'])) {
                    $this->context->controller->_error = Tools::displayError();
                }
                if (!$this->actionPromoStickers($params)) {
                    $this->context->controller->_error = Tools::displayError();
                }
            } elseif (($sticker_exist) && ($promo_status == 0)) {
                if (!Db::getInstance()->update($query['table'], $query['data'], $query['where'])) {
                    $this->context->controller->_error = Tools::displayError();
                }
                if (!$this->restoreThumbs($params)) {
                    $this->context->controller->_error = Tools::displayError();
                }
            }
        }
    }

    /**
     * Display PromoStickers settings on product page (AdminProductsExtra)
     * @param $params
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookdisplayAdminProductsExtra($params)
    {
        // debug
        // $var = '';
        // $this->context->smarty->assign('var', $var );

        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
            $id_product = (int) $params['id_product'];
            $ver = 1;
        } else {
            $id_product = (int) Tools::getValue('id_product');
            $ver = 0;
        }

        $id_store = $this->context->shop->id;
        $id_lang = $this->context->language->id;

        $adminControllers='index.php?controller=AdminProducts';
        $token='&token='.Tools::getAdminTokenLite('AdminProducts');
        $cancel = $adminControllers.$token;
        $this->context->smarty->assign('cancel', $cancel);

        $module = $this->context->link->getAdminLink('AdminModules').
            '&module_name='.$this->name.'&configure='.$this->name;
        $this->context->smarty->assign('module', $module);

        $this->context->smarty->assign(array(
            'id_product' => $id_product,
            'ver' => $ver,
            'sticker' => $this->getSticker(false, $id_product, $id_store, $id_lang),
            'stickerslist' => $this->getListStickers('url'),
            'fontslist' => $this->getListFonts(),
        ));

        $manual_img1 = _MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'do_not_delete_manual-1.gif';
        $manual_img2 = _MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'do_not_delete_manual-2.gif';
        $widget = $this->widget('ps_promo_sticker_free');
        $this->context->smarty->assign('manual_img1', $manual_img1);
        $this->context->smarty->assign('manual_img2', $manual_img2);
        $this->context->smarty->assign('widget', $widget);

        $html = '';
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_.'promostickers/views/templates/admin/ddslick.tpl');
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_.'promostickers/views/templates/admin/tab.tpl');
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_.'promostickers/views/templates/admin/promo_img.tpl');
        $html .= $this->context->smarty->fetch(_PS_MODULE_DIR_.'promostickers/views/templates/admin/widget.tpl');

        return $html;
    }

    /**
     * Get Sticker for product if isset
     * @param bool $html
     * @param bool $id_product
     * @param bool $id_store
     * @param bool $id_lang
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public function getSticker($html = false, $id_product = false, $id_store = false, $id_lang = false)
    {
        $db = Db::getInstance();

        $sql = "SELECT * FROM "._DB_PREFIX_.$this->name;

        if ($id_product !== false) {
            $sql .= " WHERE id_product='" . (int)$id_product . "'";
            if ($id_store !== false) {
                $sql .= " AND id_store='" . (int)$id_store . "'";
            }
            if ($id_lang !== false) {
                $sql .= " AND id_lang='" . (int)$id_lang . "'";
            }
        }

        if ($result = $db->executeS($sql)) {
            if ($html == true) {
                foreach ($result as $key => $sticker) {
                    $result[$key]['promo_file'] = $sticker['promo_img'];
                    $result[$key]['promo_img'] = $this->stickerImgHtml($sticker['promo_img']);
                }
            }
        } else {
            $result = array();
        }

        return $result;
    }

    /**
     * Generate Thumbs with promostickers
     * @param $params
     * @return bool
     */
    public function actionPromoStickers($params)
    {
        if (!$sticker_data = $this->getStickerByImageId($params)) {
            return true;
        }

        if (($sticker_data[0]['promo_status'] == 1) && (!empty($sticker_data[0]['promo_img']))) {
            $image = new Image($params['id_image']);
            $image->id_product = $params['id_product'];
            $sticker = $sticker_data[0]['promo_img'];

            if (!file_exists(_PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.jpg')) {
                return true;
            } else {
                $imagepath = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.jpg';
            }
            // Check ProdImg exists
            if (file_exists(_PS_PROD_IMG_DIR_.$image->getExistingImgPath().'-watermark.jpg')) {
                $imagepath = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'-watermark.jpg';
            }
            // Check Sticker exists
            if (!file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'views'
                    .DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$sticker)) {
                $this->deletePromostickers($sticker);
                return true;
            } else {
                $stickerpath = dirname(__FILE__).DIRECTORY_SEPARATOR.'views'
                    .DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$sticker;
            }
            // Path for ProdImg with Sticker
            $outputpath = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'-promosticker.jpg';
            //first make a sticker image
            $return = $this->stickerByImage($imagepath, $stickerpath, $outputpath, $sticker_data);
            //go through file formats defined for stickers and resize them
            foreach ($this->imageTypes as $imageType) {
                $newFile = _PS_PROD_IMG_DIR_ . $image->getExistingImgPath() . '-'
                    . Tools::stripslashes($imageType['name']) . '.jpg';

                if (!ImageManager::resize($outputpath, $newFile, (int)$imageType['width'], (int)$imageType['height'])) {
                    $return = false;
                }
            }
        }

        return $return;
    }

    public function getStickerByImageId($params)
    {
        $db = Db::getInstance();

        $sql = "SELECT * FROM "._DB_PREFIX_.$this->name;

        if ($params['id_image'] !== false) {
            $sql .= " WHERE id_image='" . (int)$params['id_image'] . "'";
            if (!empty($params['id_store'])) {
                $sql .= " AND id_store='" . (int)$params['id_store'] . "'";
            }
            if (!empty($params['id_lang'])) {
                $sql .= " AND id_lang='" . (int)$params['id_lang'] . "'";
            }
        }

        if (!$result = $db->executeS($sql)) {
            $result = array();
        }

        return $result;
    }

    /**
     * Restore Thumbs if Sticker was turned off
     * @param $params
     * @return bool
     */
    public function restoreThumbs($params)
    {
        $image = new Image($params['id_image']);
        $image->id_product = $params['id_product'];
        $sourcepath = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.jpg';

        foreach ($this->imageTypes as $imageType) {
            $newFile = _PS_PROD_IMG_DIR_ . $image->getExistingImgPath() . '-'
                . Tools::stripslashes($imageType['name']) . '.jpg';

            if (!ImageManager::resize($sourcepath, $newFile, (int)$imageType['width'], (int)$imageType['height'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create Product Img with Sticker
     * @param $imagepath
     * @param $stickerpath
     * @param $outputpath
     * @param $sticker_data
     * @return bool
     */
    public function stickerByImage($imagepath, $stickerpath, $outputpath, $sticker_data)
    {
        $promo_ratio = Configuration::get('PROMOSTICKERS_RATIO');

        if (!list($imageWidth, $imageHeight, $type) = @getimagesize($imagepath)) {
            return false;
        }

        $image = ImageManager::create($type, $imagepath);
        if (!$image) {
            return false;
        }

        if (!list($stickerOrigWidth, $stickerOrigHeight, $stickerType) = @getimagesize($stickerpath)) {
            return false;
        }

        $imagew = ImageManager::create($stickerType, $stickerpath);
        if (!$imagew) {
            return false;
        }

        if ($imageWidth < $imageHeight) {
            $new_width = $imageWidth / $promo_ratio;
            $new_height = $new_width;
        } elseif ($imageWidth > $imageHeight) {
            $new_height = $imageHeight / $promo_ratio;
            $new_width = $new_height;
        } else {
            $new_width = $imageWidth / $promo_ratio;
            $new_height = $imageHeight / $promo_ratio;
        }

        $newImg = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
        $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
        imagefilledrectangle($newImg, 0, 0, $new_width, $new_height, $transparent);
        imagecopyresampled(
            $newImg,
            $imagew,
            0,
            0,
            0,
            0,
            $new_width,
            $new_height,
            $stickerOrigWidth,
            $stickerOrigHeight
        );
        $imagew = $newImg;

        $stickerWidth = imagesx($imagew);
        $stickerHeight = imagesy($imagew);
        //$imagew = imagescale($imagew, $new_width, $new_height);

        // TEXT
        if (!empty($sticker_data[0]['promo_txt'])) {
            // $sticker_data[0]['promo_txt'] = wordwrap($sticker_data[0]['promo_txt'], true); //  wordwrap
            $promo_txt_size = $stickerHeight / 6;
            $promo_txt_marge_up = $stickerHeight / 2 + $promo_txt_size / 2;
            $txt_margin_left = $stickerWidth * 0.15;

            $promo_txt_stamp_font   = dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR
                .'fonts'.DIRECTORY_SEPARATOR.$sticker_data[0]['promo_txt_font'];

            if ($sticker_data[0]['promo_txt_shadow'] == 1) {
                $promo_txt_grey_color  = imagecolorallocate($image, 118, 128, 128); //grey color

                imagettftext(
                    $imagew,
                    $promo_txt_size,
                    0,
                    $txt_margin_left + $sticker_data[0]['promo_txt_horizontal'] - 2,
                    $promo_txt_marge_up + 2,
                    $promo_txt_grey_color,
                    $promo_txt_stamp_font,
                    $sticker_data[0]['promo_txt']
                ); 
            }

            list($color_r, $color_g, $color_b) = sscanf($sticker_data[0]['promo_txt_color'], "#%02x%02x%02x");

            $promo_txt_font_color  = imagecolorallocate($image, $color_r, $color_g, $color_b); //red color

            imagettftext(
                $imagew,
                $promo_txt_size,
                0,
                $txt_margin_left + $sticker_data[0]['promo_txt_horizontal'],
                $promo_txt_marge_up,
                $promo_txt_font_color,
                $promo_txt_stamp_font,
                $sticker_data[0]['promo_txt']
            );
        }

        imagecopy(
            $image,
            $imagew,
            (imagesx($image) - imagesx($imagew)) * 0.5,
            (imagesy($image) - imagesy($imagew)) * 1,
            0,
            0,
            imagesx($imagew),
            imagesy($imagew)
        );

        imagedestroy($newImg);

        switch ($type) {
            case IMAGETYPE_PNG:
                $type = 'png';
                break;
            case IMAGETYPE_GIF:
                $type = 'gif';
                break;
            case IMAGETYPE_JPEG:
                $type = 'jpg';
                break;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        return ImageManager::write($type, $image, $outputpath);
    }

    /**
     * Detele Sticker record for product by id_sticker and Regenerate thumbs for this product
     * @param $id_sticker
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteProductSticker($id_sticker)
    {
        $id_sticker = (int)$id_sticker;
        if ($sticker_data = $this->getStickerDataById($id_sticker)) {
            $params = array(
                'id_image' => $sticker_data[0]['id_image'],
                'id_product' => $sticker_data[0]['id_product']
            );
            if (Db::getInstance()->delete($this->name, "id_sticker=$id_sticker")) {
                // Regenerate thumbs for this product
                if (!$this->restoreThumbs($params)) {
                    $this->context->controller->_error = Tools::displayError();
                }
            } else {
                return $this->error = $this->displayError($this->l(
                    'An error occurred while attempting to delete the sticker.'
                ));
            }
        }
        return '';
    }

    /**
     * Get id_product by id_sticker
     * @param $id_sticker
     * @return array|false|mysqli_result|null|PDOStatement|resource|string
     * @throws PrestaShopDatabaseException
     */
    public function getStickerDataById($id_sticker)
    {
        $db = Db::getInstance();
        $id_sticker = (int)$id_sticker;
        $sql = "SELECT * FROM "._DB_PREFIX_.$this->name;
        $sql .= " WHERE id_sticker='" . $id_sticker . "'";
      //  getValue
        if ($result = $db->ExecuteS($sql)) {
            return $result;
        } else {
            return $this->displayError($sql);
        }
    }

    /**
     * Reset Filter for renderListStickers
     * @return bool
     */
    public function resetFilterImg()
    {
        $search_field = array(
            'img'
        );
        foreach ($search_field as $v) {
            $this->context->cookie->{'promostickersImgFilter_'.$v} = null;
        }
        return true;
    }

    /**
     * Reset Filter for renderListProducts
     * @return bool
     */
    public function resetFilterProd()
    {
        $search_field = array(
            'id_product',
            'product_name',
            'promo_img',
            'promo_thumb',
            'promo_txt',
            'id_store',
            'id_lang',
            'language',
            'shop',
            'promo_state'
        );
        foreach ($search_field as $v) {
            $this->context->cookie->{'promostickersProdFilter_'.$v} = null;
        }
        return true;
    }

    /**
     * Delete Sticker(file) from disk and delete and regenerate all thumbs where this sticker was installed
     * @param $file
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deletePromostickers($file)
    {
        $db = Db::getInstance();

        $sql = "SELECT id_sticker,id_product FROM "._DB_PREFIX_.$this->name;
        $sql .= " WHERE promo_img='" . pSQL($file) . "'";

        //  get entry with this img file in promostickers table
        if ($result = $db->executeS($sql)) {
            foreach ($result as $key) {
                // Delete entry from promosticker table
                $this->deleteProductSticker($key['id_sticker']);
            }
        }
        unlink(dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$file);

        return $this->confirm = $this->displayConfirmation('method 2 ok');
    }

    public function widget($param){
        $send['widget'] = $param;
        $send['http_host'] = $_SERVER['HTTP_HOST'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://tobiksoft.com/market/widget/api.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $send);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5);
        $output = curl_exec ($ch);
        curl_close ($ch);


        return $output;
    }

}
