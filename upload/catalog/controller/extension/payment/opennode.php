<?php

require_once(DIR_SYSTEM . 'library/opennode/opennode/init.php');
require_once(DIR_SYSTEM . 'library/opennode/version.php');
define('OPENNODE_CHECKOUT_PATH', 'https://checkout.opennode.com/');

class ControllerExtensionPaymentOpennode extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/opennode');
        $this->load->model('checkout/order');

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['action'] = $this->url->link('extension/payment/opennode/checkout', '', true);

        return $this->load->view('extension/payment/opennode', $data);
    }

    public function checkout()
    {
        \OpenNode\OpenNode::config(array(
          'auth_token'  =>  $this->config->get('payment_opennode_api_auth_token'),
          'environment' =>  'live',
          'user_agent'  =>  'Opennode - OpenCart v' . VERSION . ' Extension v' . OPENNODE_OPENCART_EXTENSION_VERSION
        ));
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/opennode');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $description = [];

        foreach ($this->cart->getProducts() as $product) {
            $description[] = $product['quantity'] . ' × ' . $product['name'];
        }

        try {
          $charge = \OpenNode\Merchant\Charge::create(array(
            'order_id'      =>  $order_info['order_id'],
            'amount'        =>  (strtoupper($order_info['currency_code'])) === 'BTC' ? convertToSats($order_info['total']) : $order_info['total'],
            'currency'      =>  $order_info['currency_code'],
            'auto_settle'   =>  true,
            'callback_url'  =>  $this->url->link('extension/payment/opennode/callback', true),
            'success_url'   =>  $this->url->link('extension/payment/opennode/success', true),
            'description'   =>  join($description, ', '),
            'name'          =>  $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
            'email'         =>  $order_info['email']
          ));

          $this->model_extension_payment_opennode->addOrder(array(
            'order_id' => $order_info['order_id'],
            'charge_id' => $charge->id
          ));

          $this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_opennode_order_status_id'));
          $this->response->redirect(OPENNODE_CHECKOUT_PATH . $charge->id);

        } catch (Exception $e) {
          $this->log->write("Order #" . $order_info['order_id'] . " is not valid. (" . $e->getMessage() . ")");
          $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }
    }

    public function success()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/opennode');

        $order = $this->model_extension_payment_opennode->getOrder($this->session->data['order_id']);

        if (empty($order)) {
            $this->response->redirect($this->url->link('common/home', '', true));
        } else {
            $this->response->redirect($this->url->link('checkout/success', '', true));
        }
    }

    public function callback()
    {
      try {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/opennode');

        $auth_token = $this->config->get('payment_opennode_api_auth_token');

        $order_id = $this->request->post['order_id'];
        $charge_id = $this->request->post['id'];
        $hashedOrder = $this->request->post['hashed_order'];

        $order_info = $this->model_checkout_order->getOrder($order_id);
        $on_order = $this->model_extension_payment_opennode->getOrder($order_id);

        if (strcmp(hash_hmac('sha256', $charge_id, $auth_token), $hashedOrder) != 0) {
          $this->log->write("Request is not signed with the same API Key, ignoring");
        }
        else {
          if (!empty($order_info) && !empty($on_order)) {
            $apiConfig = array(
              'auth_token' => $auth_token,
              'environment' => 'live',
              'user_agent' => 'Opennode - OpenCart v' . VERSION . ' Extension v' . OPENNODE_OPENCART_EXTENSION_VERSION
            );
            \OpenNode\OpenNode::config($apiConfig);

            $charge = \OpenNode\Merchant\Charge::find($on_order['charge_id']);

            switch ($charge->status) {
                case 'paid':
                    $charge_status = 'payment_opennode_paid_status_id';
                    break;
                case 'processing':
                    $charge_status = 'payment_opennode_processing_status_id';
                    break;
                default:
                    $charge_status = NULL;
            }

            if (!is_null($charge_status)) {
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get($charge_status));
            }

          }
        }
        $this->response->addHeader('HTTP/1.1 200 OK');
      } catch (Exception $e) {
        $this->log->write($e->getMessage());
        $this->response->addHeader('HTTP/1.1 200 OK');
      }
    }

    private function convertToSats($amount) {
      number_format($amount*100000000, 8, '.', '');
    }

}
