<?php

require_once(DIR_SYSTEM . 'library/opennode/opennode/init.php');

class ControllerExtensionPaymentOpennode extends Controller {
  private $error = array();

  public function index() {
    $this->load->language('extension/payment/opennode');
    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');
    $this->load->model('localisation/order_status');
    $this->load->model('localisation/geo_zone');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_opennode', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
    }

    $data['action']             = $this->url->link('extension/payment/opennode', 'user_token=' . $this->session->data['user_token'], true);
    $data['order_statuses']     = $this->model_localisation_order_status->getOrderStatuses();
    $data['geo_zones']          = $this->model_localisation_geo_zone->getGeoZones();

    if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

    $data['breadcrumbs'] = array();
    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    );
    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_extension'),
        'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
    );
    $data['breadcrumbs'][] = array(
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/payment/opennode', 'user_token=' . $this->session->data['user_token'], true)
    );

    $fields = array('payment_opennode_status', 'payment_opennode_api_auth_token', 'payment_opennode_order_status_id', 'payment_opennode_pending_status_id', 'payment_opennode_processing_status_id', 'payment_opennode_paid_status_id', 'payment_opennode_total', 'payment_opennode_sort_order', 'payment_opennode_geo_zone_id');


    foreach ($fields as $field) {
      if (isset($this->request->post[$field])) {
  			$data[$field] = $this->request->post[$field];
  		} else {
  			$data[$field] = $this->config->get($field);
  		}
    }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/payment/opennode', $data));
  }

  protected function validate() {
    if (!$this->user->hasPermission('modify', 'extension/payment/opennode')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    if (!class_exists('OpenNode\OpenNode')) {
      $this->error['warning'] = $this->language->get('error_composer');
    }

    return true;
  }


	public function install() {
		$this->load->model('extension/payment/opennode');

		$this->model_extension_payment_opennode->install();
	}

	public function uninstall() {
		$this->load->model('extension/payment/opennode');

		$this->model_extension_payment_opennode->uninstall();
	}
}
