<?php

namespace Biz\Coupon\State;

use Biz\System\Service\LogService;
use Biz\User\Service\UserService;

class UsingCoupon extends Coupon implements CouponInterface
{
    public function using($params)
    {
        throw new \Exception('Can not using coupon which status is using!');
    }

    public function used()
    {
        $coupon = $this->getCouponService()->updateCoupon(
            $this->coupon['id'],
            array(
                'status' => 'used',
            )
        );

        $card = $this->getCardService()->getCardByCardIdAndCardType($coupon['id'], 'coupon');

        if (!empty($card)) {
            $this->getCardService()->updateCardByCardIdAndCardType($coupon['id'], 'coupon', array(
                'status' => 'used',
                'useTime' => $coupon['orderTime'],
            ));
        }

        $user = $this->getUserService()->getUser($coupon['userId']);

        $this->getLogService()->info(
            'coupon',
            'use',
            "用户{$user['nickname']}(#{$user['id']})使用了优惠券 {$coupon['code']}",
            $coupon
        );
    }

    public function cancelUsing()
    {
        $this->getCouponService()->updateCoupon($this->coupon['id'], array(
            'status' => 'receive',
            'targetType' => '',
            'targetId' => 0,
            'orderTime' => time(),
            'orderId' => 0,
        ));
    }

    /**
     * @return LogService
     */
    private function getLogService()
    {
        return $this->biz->service('System:LogService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->biz->service('User:UserService');
    }
}
