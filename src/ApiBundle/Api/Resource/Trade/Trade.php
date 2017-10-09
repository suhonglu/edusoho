<?php

namespace ApiBundle\Api\Resource\Trade;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Exception\ErrorCode;
use ApiBundle\Api\Resource\AbstractResource;
use ApiBundle\Api\Resource\Trade\Factory\BaseTrade;
use ApiBundle\Api\Resource\Trade\Factory\TradeFactory;
use Biz\Cashier\Service\CashierService;
use Biz\OrderFacade\Service\OrderFacadeService;
use Codeages\Biz\Framework\Pay\Service\PayService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Trade extends AbstractResource
{
    public function get(ApiRequest $request, $tradeSn)
    {
        $trade = $this->getPayService()->queryTradeFromPlatform($tradeSn);
        return array(
            'isPaid' => $trade['status'] === 'paid',
            'successUrl' => $this->generateUrl('cashier_pay_success', array('trade_sn' => $tradeSn)),
        );
    }

    public function add(ApiRequest $request)
    {
        $params = $request->request->all();

        if (empty($params['gateway'])
            || empty($params['type'])) {
            throw new BadRequestHttpException('Params missing', null, ErrorCode::INVALID_ARGUMENT);
        }

        $this->fillParams($params);

        if (!empty($params['orderSn'])) {
            $this->getOrderFacadeService()->checkOrderBeforePay($params['orderSn'], $params);
        }

        $tradeIns = $this->getTradeIns($params['gateway']);
        $trade = $tradeIns->create($params);

        if ($trade['cash_amount'] == 0) {
            $trade = $this->getPayService()->notifyPaid('coin', array('trade_sn' => $trade['trade_sn']));
        }

        return $tradeIns->createResponse($trade);
    }

    /**
     * @param $gateway
     * @return BaseTrade
     */
    private function getTradeIns($gateway)
    {
        $factory = new TradeFactory();
        $tradeIns = $factory->create($gateway);
        $tradeIns->setRouter($this->container->get('router'));
        $tradeIns->setBiz($this->biz);
        return $tradeIns;
    }

    private function fillParams(&$params)
    {
        $params['userId'] = $this->getCurrentUser()->getId();
        $params['clientIp'] = $this->getClientIp();
    }

    /**
     * @return PayService
     */
    private function getPayService()
    {
        return $this->service('Pay:PayService');
    }

    /**
     * @return OrderFacadeService
     */
    private function getOrderFacadeService()
    {
        return $this->service('OrderFacade:OrderFacadeService');
    }
}