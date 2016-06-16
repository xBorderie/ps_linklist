<?php

class AdminLinkWidgetController extends ModuleAdminController
{
    public $identifier = 'LinkBlock';

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();
        $this->meta_title = $this->module->getTranslator()->trans('Link Widget', array(), 'Modules.LinkList');

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }

        $this->name = 'LinkWidget';

        $this->repository = new LinkBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );
    }

    public function init()
    {
        if (Tools::isSubmit('edit'.$this->identifier)) {
            $this->display = 'edit';
        } elseif (Tools::isSubmit('addLinkBlock')) {
            $this->display = 'add';
        }

        parent::init();
    }

    public function postProcess()
    {
        $this->addNameArrayToPost();
        if (!$this->validateForm($_POST)) {
            return false;
        }

        if (Tools::isSubmit('submit'.$this->identifier)) {
            $block = new LinkBlock(Tools::getValue('id_link_block'));
            $block->name = Tools::getValue('name');
            $block->id_hook = (int)Tools::getValue('id_hook');
            $block->content['cms'] = (array)Tools::getValue('cms');
            $block->content['product'] = (array)Tools::getValue('product');
            $block->content['static'] = (array)Tools::getValue('static');
            $block->save();

            $hook_name = Hook::getNameById(Tools::getValue('id_hook'));
            if (!Hook::isModuleRegisteredOnHook($this->module, $hook_name, $this->context->shop->id)) {
                Hook::registerHook($this->module, $hook_name);
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        } elseif (Tools::isSubmit('delete'.$this->identifier)) {
            $block = new LinkBlock(Tools::getValue('id_link_block'));
            $block->delete();

            if (!$this->repository->getCountByIdHook((int)$block->id_hook)) {
                Hook::unregisterHook($this->module, Hook::getNameById((int)$block->id_hook));
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('Admin'.$this->name));
        }

        return parent::postProcess();
    }

    public function renderView()
    {
        $title = $this->module->getTranslator()->trans('Link block configuration', array(), 'Modules.LinkList');

        $this->fields_form[]['form'] = array(
            'legend' => array(
                'title' => $title,
                'icon' => 'icon-list-alt'
            ),
            'input' => array(
                array(
                    'type' => 'link_blocks',
                    'label' => $this->module->getTranslator()->trans('Link Blocks', array(), 'Modules.LinkList'),
                    'name' => 'link_blocks',
                    'values' => $this->repository->getCMSBlocksSortedByHook(),
                ),
            ),
            'buttons' => array(
                'newBlock' => array(
                    'title' => $this->module->getTranslator()->trans('New block', array(), 'Modules.LinkList'),
                    'href' => $this->context->link->getAdminLink('Admin'.$this->name).'&amp;addLinkBlock',
                    'class' => 'pull-right',
                    'icon' => 'process-icon-new'
                ),
            ),
        );

        $this->getLanguages();


        $helper = $this->buildHelper();
        $helper->submit_action = '';
        $helper->title = $title;

        $helper->fields_value = $this->fields_value;

        return $helper->generateForm($this->fields_form);
    }

    public function renderForm()
    {
        $block = new LinkBlock((int)Tools::getValue('id_link_block'));

        $this->fields_form[0]['form'] = array(
            'tinymce' => true,
            'legend' => array(
                'title' => isset($block) ? $this->getTranslator()->trans('Edit the link block.', array(), 'Modules.LinkList') : $this->getTranslator()->trans('New link block' 'Modules.LinkList'),
                'icon' => isset($block) ? 'icon-edit' : 'icon-plus-square'
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => 'id_link_block',
                ),
                    array(
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Name of the link block', array(), 'Modules.LinkList'),
                        'name' => 'name',
                        'lang' => true,
                        'desc' => $this->getTranslator()->trans('If you leave this field empty, the block name will use the category name by default.', array(), 'Modules.LinkList')
                    ),
                array(
                    'type' => 'select',
                    'label' => $this->getTranslator()->trans('Hook', array(), 'Admin.Global'),
                    'name' => 'id_hook',
                    'class' => 'input-lg',
                    'options' => array(
                        'query' => $this->repository->getDisplayHooksForHelper(),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'cms_pages',
                    'label' => $this->getTranslator()->trans('Content pages', array(), 'Modules.LinkList'),
                    'name' => 'cms[]',
                    'values' => $this->repository->getCmsPages(),
                    'desc' => $this->getTranslator()->trans('Please mark every page that you want to display in this block.', array(), 'Modules.LinkList')
                ),
                array(
                    'type' => 'product_pages',
                    'label' => $this->getTranslator()->trans('Product pages',array(), 'Modules.LinkList'),
                    'name' => 'product[]',
                    'values' => $this->repository->getProductPages(),
                    'desc' => $this->getTranslator()->trans('Please mark every page that you want to display in this block.', array(), 'Modules.LinkList')
                ),
                array(
                    'type' => 'static_pages',
                    'label' => $this->getTranslator()->trans('Static content',array(), 'Modules.LinkList'),
                    'name' => 'static[]',
                    'values' => $this->repository->getStaticPages(),
                    'desc' => $this->getTranslator()->trans('Please mark every page that you want to display in this block.', array(), 'Modules.LinkList')
                ),
            ),
            'buttons' => array(
                'cancelBlock' => array(
                    'title' => $this->getTranslator()->trans('Cancel', array(), 'Admin.Actions'),
                    'href' => (Tools::safeOutput(Tools::getValue('back', false)))
                                ?: $this->context->link->getAdminLink('Admin'.$this->name),
                    'icon' => 'process-icon-cancel'
                )
            ),
            'submit' => array(
                'name' => 'submit'.$this->identifier,
                'title' => $this->getTranslator()->trans('Save', array(), 'Admin.Actions'),
            )
        );

        if ($id_hook = Tools::getValue('id_hook')) {
            $block->id_hook = (int)$id_hook;
        }

        if (Tools::getValue('name')) {
            $block->name = Tools::getValue('name');
        }

        $helper = $this->buildHelper();
        if (isset($id_link_block)) {
            $helper->currentIndex = AdminController::$currentIndex.'&id_link_block='.$id_link_block;
            $helper->submit_action = 'edit'.$this->identifier;
        } else {
            $helper->submit_action = 'addLinkBlock';
        }

        $helper->fields_value = (array)$block;

        return $helper->generateForm($this->fields_form);
    }

    protected function buildHelper()
    {
        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->override_folder = 'linkwidget/';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('Admin'.$this->name);
        $helper->languages = $this->_languages;
        $helper->currentIndex = $this->context->link->getAdminLink('Admin'.$this->name);
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }

    public function validateForm($data)
    {
        return true;
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->module->getTranslator()->trans('Themes', array(), 'Modules.LinkList');
        $this->toolbar_title[] = $this->module->getTranslator()->trans('Link Widget', array(), 'Modules.LinkList');
    }

    public function setMedia()
    {
        $this->addJqueryPlugin('tablednd');
        $this->addJS(_PS_JS_DIR_.'admin/dnd.js');

        return parent::setMedia();
    }

    private function addNameArrayToPost()
    {
        $languages = Language::getLanguages();
        $names = array();
        foreach ($languages as $lang) {
            if ($name = Tools::getValue('name_'.(int)$lang['id_lang'])) {
                $names[(int)$lang['id_lang']] = $name;
            }
        }
        $_POST['name'] = $names;
    }
}
