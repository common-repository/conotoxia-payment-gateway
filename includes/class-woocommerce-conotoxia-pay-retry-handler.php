<?php

use CKPL\Pay\Exception\Api\ValidationErrorException;
use CKPL\Pay\Exception\Exception;
use WC_Gateway_Conotoxia_Pay_Logger as Logger;

class WC_Gateway_Conotoxia_Pay_Retry_Handler
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        add_action('wp_ajax_cx_retry_payment', [$this, 'retry_payment']);
        add_action('wp_ajax_nopriv_cx_retry_payment', [$this, 'retry_payment']);
    }

    /**
     * @return void
     */
    public function retry_payment(): void
    {
        if (empty($_POST['orderKey'])) {
            Logger::log('Could not receive order key when retrying payment.');
            $this->respond_with_problem();
            exit();
        }
        if (empty($_POST['orderId'])) {
            Logger::log('Could not receive order id when retrying payment.');
            $this->respond_with_problem();
            exit();
        }
        $payment_id = $this->resolve_payment_id(
            sanitize_text_field($_POST['orderId']),
            sanitize_text_field($_POST['orderKey'])
        );
        if (!$payment_id) {
            Logger::log('Could not resolve payment id when retrying payment.');
            $this->respond_with_problem();
            exit();
        }
        $gateway = new WC_Gateway_Conotoxia_Pay();
        try {
            $created_payment_response = $gateway->retry_payment($payment_id);
            $approve_url = $created_payment_response->getApproveUrl();
            $this->respond_with_approve_url($approve_url);
        } catch (ValidationErrorException $exception) {
            $log_messages = implode(" ", $exception->getLogMessages());
            Logger::log('Validation problem with retrying payment with id \'%s\': \'%s\'', $payment_id, $log_messages);
            $this->respond_with_problem();
        } catch (Exception $exception) {
            Logger::log(
                'Problem with retrying payment with id \'%s\': \'%s\'; trace: \'%s\'',
                $payment_id,
                $exception->getMessage(),
                $exception->getTraceAsString()
            );
            $this->respond_with_problem();
        } finally {
            exit();
        }
    }

    /**
     * @param string $order_id
     * @param string $order_key
     * @return false|string
     */
    private function resolve_payment_id(string $order_id, string $order_key)
    {
        $order = wc_get_order(wc_get_order_id_by_order_key($order_key));
        return $this->is_valid_order($order, $order_id) ? $order->get_transaction_id() : false;
    }

    /**
     * @param bool|WC_Order|WC_Order_Refund $order
     * @param string $order_id
     * @return bool
     */
    private function is_valid_order($order, string $order_id): bool
    {
        if (!$order) {
            Logger::log('Could not find order with id \'%s\' when retrying payment.', $order_id);
            return false;
        }
        if ($order->get_id() != $order_id) {
            Logger::log('Received invalid order id \'%s\' when retrying payment.', $order_id);
            return false;
        }
        if (!in_array($order->get_payment_method(), Identifier::get_all_ids())) {
            Logger::log(
                'Order with id \'%s\' has invalid payment method \'%s\' when retrying payment.',
                $order->get_order_number(),
                $order->get_payment_method()
            );
            return false;
        }
        $payment_id = $order->get_transaction_id();
        if (!$payment_id || strpos($payment_id, 'PAY') !== 0) {
            Logger::log(
                'Order with id \'%s\' has invalid transaction id \'%s\'.',
                $order->get_order_number(),
                $payment_id
            );
            return false;
        }
        return true;
    }

    /**
     * @param string $approve_url
     * @return void
     */
    private function respond_with_approve_url(string $approve_url): void
    {
        header('Content-Type: application/json');
        echo wp_json_encode(['approveUrl' => $approve_url]);
    }

    /**
     * @return void
     */
    private function respond_with_problem(): void
    {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/problem+json');
        echo wp_json_encode([
            'title' => 'Bad Request',
            'status' => 400
        ]);
    }
}
