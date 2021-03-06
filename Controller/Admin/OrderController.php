<?php

namespace PayPlugModule\Controller\Admin;

use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\PaymentService;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;

class OrderController extends BaseAdminController
{
    public function refundAction()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), 'PayPlugModule', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm('payplugmodule_order_action_form_refund');

        try {
            $data = $this->validateForm($form)->getData();
            $order = OrderQuery::create()
                ->findOneById($data['order_id']);

            $amountToRefund = (int)($data['refund_amount'] * 100);

            /** @var PaymentService $paymentService */
            $paymentService = $this->container->get('payplugmodule_payment_service');
            $paymentService->doOrderRefund($order, $amountToRefund);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error",
                    [],
                    PayPlugModule::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
        }

        // Sleep to let time for PayPlug to send validation
        sleep(2);
        $url = $this->retrieveSuccessUrl($form);
        return $this->generateRedirect($url.'#orderPayPlug');
    }

    public function captureAction()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), 'PayPlugModule', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm('payplugmodule_order_action_form');

        try {
            $data = $this->validateForm($form)->getData();
            $order = OrderQuery::create()
                ->findOneById($data['order_id']);

            /** @var PaymentService $paymentService */
            $paymentService = $this->container->get('payplugmodule_payment_service');
            $paymentService->doOrderCapture($order);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error",
                    [],
                    PayPlugModule::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
        }

        // Sleep to let time for PayPlug to send validation
        sleep(2);
        $url = $this->retrieveSuccessUrl($form);
        return $this->generateRedirect($url.'#orderPayPlug');
    }
}