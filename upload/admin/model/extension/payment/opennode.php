<?php

class ModelExtensionPaymentOpennode extends Model {
  public function install() {
    $this->db->query("
      CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "opennode_order` (
        `opennode_order_id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` INT(11) NOT NULL,
        `charge_id` VARCHAR(120),
        PRIMARY KEY (`opennode_order_id`)
      ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;
    ");

    $this->load->model('setting/setting');

    $defaults = array();

    $defaults['payment_opennode_order_status_id'] = 1;
    $defaults['payment_opennode_pending_status_id'] = 1;
    $defaults['payment_opennode_processing_status_id'] = 1;
    $defaults['payment_opennode_paid_status_id'] = 2;

    $this->model_setting_setting->editSetting('payment_opennode', $defaults);
  }

  public function uninstall() {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "opennode_order`;");
  }
}
