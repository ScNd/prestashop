<?php

if (!defined('_PS_VERSION_'))
  exit;

class QuickSpecificPrice extends Module
{


	public function __construct()
	{
		$this->name = 'quickspecificprice';
		$this->tab = 'administration';
		$this->version = '1.0';
		$this->author = 'ScNd';

		parent::__construct();

		$this->displayName = $this->l('Quick specific price');
		$this->description = $this->l('Quickly set a new specific price for a given Product ID.');

		$this->context->smarty->assign('module_name', $this->name);
		$this->need_instance = 0;
	}


	public function install()
	{
	  if (Shop::isFeatureActive())
		Shop::setContext(Shop::CONTEXT_ALL);
	 
	  return parent::install() &&
		Configuration::updateValue('QUICKSPRICE_PERC', '.2');
		Configuration::updateValue('QUICKSPRICE_SETONSALE', 1);
	}


	public function uninstall()
	{
		return parent::uninstall() && Configuration::deleteByName('QUICKSPRICE_PERC');
		return parent::uninstall() && Configuration::deleteByName('QUICKSPRICE_SETONSALE');
	}


	public function getContent()
	{
		$output = null;
	 
		if (Tools::isSubmit('submit'.$this->name))
		{
		    $quicksprice_perc = floatval(Tools::getValue('QUICKSPRICE_PERC'));
			$quicksprice_pid = Tools::getValue('QUICKSPRICE_PID');
			$quicksprice_setonsale_on = Tools::getValue('QUICKSPRICE_SETONSALE_on') ? 1 : 0;
		    if (!$quicksprice_perc  || empty($quicksprice_perc) || !Validate::isUnsignedInt($quicksprice_pid))
			{
		        $output .= $this->displayError( $this->l('Invalid input'));
			}
		    else
		    {
				Db::getInstance()->Execute("INSERT INTO `"._DB_PREFIX_."specific_price` (`id_product`, `id_shop`, `price`, `from_quantity`, `reduction`, `reduction_type`) VALUES ('".$quicksprice_pid."', '0', '-1', '1', '".$quicksprice_perc."', 'percentage');");
				if ($quicksprice_setonsale_on == 1){
				Db::getInstance()->Execute("UPDATE `"._DB_PREFIX_."ps_product` SET `on_sale` = '1' WHERE `id_product` = '".$quicksprice_pid."';");
				Db::getInstance()->Execute("UPDATE `"._DB_PREFIX_."ps_product_shop` SET `on_sale` = '1' WHERE `id_product` = '".$quicksprice_pid."';");
				}
				Configuration::updateValue('QUICKSPRICE_PERC', $quicksprice_perc);
				Configuration::updateValue('QUICKSPRICE_SETONSALE', $quicksprice_setonsale_on);
		        $output .= $this->displayConfirmation($this->l('Value updated'));
		    }
		}
		return $output.$this->displayForm();
	}


	public function displayForm()
	{
		// Get default Language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fields_form[0]['form'] = array(
		    'legend' => array(
		        'title' => $this->l('Settings'),
		    ),

		    'input' => array(
		        array(
		            'type' => 'text',
		            'label' => $this->l('Product ID'),
		            'name' => 'QUICKSPRICE_PID',
		            'size' => 4,
		            'required' => true
		        ),
				array(
					'type' => 'select',
					'label' => $this->l('Percentage:'),
					'name' => 'QUICKSPRICE_PERC',
					'size' => 8,
					'required' => true,
					'desc' =>$this->l('Percentage of new specific price'),
					'options' => array(
						'query' => array(
							array('key' => '.1', 'name' => '10%'),
							array('key' => '.2', 'name' => '20%'),
							array('key' => '.3', 'name' => '30%'),
							array('key' => '.4', 'name' => '40%'),
							array('key' => '.5', 'name' => '50%'),
							array('key' => '.6', 'name' => '60%'),
							array('key' => '.7', 'name' => '70%'),
							array('key' => '.8', 'name' => '80%')
						),
						'name' => 'name',
						'id' => 'key'
						)
				),
		    
				array(
					'type' => 'checkbox',
					'name' => 'QUICKSPRICE_SETONSALE',
					'values' => array(
						'query' => array(
							array('id' => 'on', 'name' => $this->l('Display "on sale" icon on product page and text on product listing'), 'val' => '1'),
							),
						'id' => 'id',
						'name' => 'name'
					)
				)
			),

		    'submit' => array(
		        'title' => $this->l('Save'),
		        'class' => 'button'
		    )
		);
		 
		$helper = new HelperForm();
		 
		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		 
		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;
		 
		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
		    'save' =>
		    array(
		        'desc' => $this->l('Save'),
		        'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
		        '&token='.Tools::getAdminTokenLite('AdminModules'),
		    ),
		    'back' => array(
		        'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
		        'desc' => $this->l('Back to list')
		    )
		);
		 
		// Load current value
		$helper->fields_value['QUICKSPRICE_PERC'] = Configuration::get('QUICKSPRICE_PERC');
		$helper->fields_value['QUICKSPRICE_SETONSALE_on'] = Configuration::get('QUICKSPRICE_SETONSALE');
		 
		return $helper->generateForm($fields_form);
	}
}
