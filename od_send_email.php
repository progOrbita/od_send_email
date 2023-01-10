<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Od_send_email extends Module
{
    public function __construct()
    {
        $this->name = 'od_send_email';
        $this->tab = 'front_office_features'; //organizacion modulos
        $this->version = '1.0.0';
        $this->author = 'Jose Barreiro';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.6.1',
            'max' => '1.7.9',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('od_send_email');
        $this->description = $this->l('Module to send mails.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('MYMODULE_NAME')) {
            $this->warning = $this->l('No name provided');
        }
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionAdminControllerSetMedia')
            && Configuration::updateValue('MYMODULE_NAME', 'mailing');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('MYMODULE_NAME');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $mail1 = (string) Tools::getValue('_OD_SEND_EMAIL_1_', '');
            $mail2 = (string) Tools::getValue('_OD_SEND_EMAIL_2_', '');
            if (empty($mail1) || !Validate::isEmail($mail1) || empty($mail2) || !Validate::isEmail($mail2)) {
                $output = $this->displayError($this->l('Error al enviar el correo'));
            } else {
                Configuration::updateValue('_OD_SEND_EMAIL_1_', $mail1);
                Configuration::updateValue('_OD_SEND_EMAIL_2_', $mail2);
                $output = $this->displayConfirmation($this->l('Correo enviado'));
            }
        }
        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Envio de correo'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Remitente'),
                        'name' => '_OD_SEND_EMAIL_1_',
                        'size' => 20,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Receptor'),
                        'name' => '_OD_SEND_EMAIL_2_',
                        'size' => 20,
                        'required' => true,
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Enviar'),
                    'class' => 'btn btn-default pull-right'
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        $helper->fields_value = [
            '_OD_SEND_EMAIL_1_' => Tools::getValue('_OD_SEND_EMAIL_1_', Configuration::get('_OD_SEND_EMAIL_1_')),
            '_OD_SEND_EMAIL_2_' => Tools::getValue('_OD_SEND_EMAIL_2_', Configuration::get('_OD_SEND_EMAIL_2_'))
        ];

        return $helper->generateForm([$form]);
    }
}
