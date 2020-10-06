<?php

include dirname(__DIR__) . "/../framework/bootstrap.php";

class App {

    /**
     * 进行交易结算
     * 
     * @method POST
     * @uses api
    */
    public function settlement($goods, $discount, $transaction, $vip = -1) {
        # 获取商品信息
        $item_ids = array_keys($goods);
        $items = (new Table("goods"))
            ->where(["id" => in($item_ids)])
            ->limit(count($item_ids))
            ->select();
        $money = 0;
        $counts = 0;

        foreach($items as $goodItem) {
            $price = $goodItem["price"] * $goods[$goodItem["id"]];
            $money = $money + $price;
            $counts = $counts + $goods[$goodItem["id"]];
        }

        if ($vip > 0) {
            $vip_info = (new Table("VIP_members"))->where(["id" => $vip])->find();

            if (Utils::isDbNull($vip_info)) {
                controller::error("会员信息错误");
            }
    
            # 应付金额减去余额得到剩余应该支付的金额
            $left = $money - $vip_info["balance"];
            # 这计算得到通过会员余额支付的金额部分
            $balance_pay = $left <= 0 ? $money : $vip_info["balance"];
        }

        # 添加商品交易信息
        $money = $money * $discount;
        $trade = (new Table("waterflow"))
            ->add([
                "goods" => json_encode($goods),
                "time" => Utils::Now(),
                # 交易的金额是总的
                "money" => $money,
                "buyer" => $vip,
                "operator" => web::login_userId(),
                "count" => $counts,
                "discount" => $discount,
                "note" => ""
            ]);

        if (empty($trade) || $trade == false) {
            controller::error(ERR_MYSQL_INSERT_FAILURE);
        } else {
            $details = new Table("trade_items");

            # 添加详细售卖信息
            foreach($items as $goodItem) {
                $counts = $goods[$goodItem["id"]];
                $details->add([
                    "item_id" => $goodItem["id"],
                    "count" => $counts,
                    "batch_id" => -1,
                    "waterflow" => $trade
                ]);
            }

            if ($vip > 0) {
                # 修改会员余额和记录流水
                (new Table("VIP_members"))->where(["id" => $vip])->save(["balance" => "~balance - $balance_pay"]);
                # 添加流水记录
                (new Table("VIP_waterflow"))->add([
                    "vip" => $vip,
                    "money" => $balance_pay,
                    "waterflow_id" => $trade,
                    "time" => Utils::Now(),
                    "note" => "会员消费",
                    "operator" => web::login_userId()
                ]);

                if ($left > 0) {
                    controller::error($left, 400);
                } else {
                    controller::success(1);
                }
            } else {
                controller::success(1);
            }
        }

        controller::error($items);
    }
}