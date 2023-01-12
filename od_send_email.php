<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Od_send_email extends Module
{

    private $fields_values;

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

        $this->fields_values = [
            '_OD_SEND_EMAIL_1_' => $this->l('Remitente'),
            '_OD_SEND_EMAIL_2_' => $this->l('Receptor')
        ];
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
        return $this->postProcess() . $this->displayForm();
    }

    /**
     * Post process
     * 
     * @return string error
     */
    public function postProcess(): string
    {
        if (!Tools::isSubmit('submit' . $this->name)) {
            return '';
        }

        $result = $this->updateFieldsValue();
        if (!empty($result)) {
            return $result;
        }

        // TODO enviar email y control de errores

        return $this->mailSender();
    }

    /**
     * Update fields value
     * 
     * @return string error
     */
    public function updateFieldsValue(): string
    {
        foreach ($this->fields_values as $key => $value) {
            if ($this->validateMail($key)) {
                continue;
            }

            return $this->displayError($this->l('Error al actualizar ' . $value));
        }

        return '';
    }

    /**
     * Check if value is corrrect and update
     * 
     * @param string $value is name of input mail 
     * 
     * @return bool
     */
    public function validateMail($value): bool
    {
        $mail = (string) Tools::getValue($value, '');

        if (empty($mail) || !Validate::isEmail($mail)) {
            return false;
        }

        return Configuration::updateValue($value, $mail);
    }

    /**
     * 
     */

    public function mailSender()
    {
        if (!Mail::send(
            3, // TODO mas idiomas  
            'plantilla',
            'prueba mail',
            array(),    // este array le pasa variables al tpl en este caso no lo utilizamos porq utilizamos variables globales del tpl
            Configuration::get('_OD_SEND_EMAIL_2_'),
            Null,
            Configuration::get('_OD_SEND_EMAIL_1_'),
            Null,
            Null,
            Null,
            _PS_MODULE_DIR_ . 'od_send_email/mails'
        )) {
            return $this->displayError($this->l('Error al realizar el envio'));
        }

        return $this->displayConfirmation($this->l('Correo enviado'));
    }

    public function displayForm()
    {
        $form = [[
            'form' => [
                'legend' => [
                    'title' => $this->l('Envio de correo'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->fields_values['_OD_SEND_EMAIL_1_'],
                        'name' => '_OD_SEND_EMAIL_1_',
                        'size' => 20,
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->fields_values['_OD_SEND_EMAIL_2_'],
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
        ]];

        $helper = new HelperForm();
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;
        $helper->fields_value = $this->getFieldsValue();

        return $helper->generateForm($form);
    }

    /**
     * Get fields values of helper form of configuration
     * 
     * @return array
     */
    private function getFieldsValue(): array
    {
        $data = [];

        foreach ($this->fields_values as $key => $value) {
            $data[$key] = Tools::getValue($key, Configuration::get($key));
        }

        return $data;
    }
}
