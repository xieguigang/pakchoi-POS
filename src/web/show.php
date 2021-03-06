<?php

include dirname(__DIR__) . "/framework/bootstrap.php";

class App {

    /**
     * 商品详细信息
     * 
     * @uses view
    */
    public function item($no) {
        $info = (new Table("goods"));
        $goods = $info
            ->left_join("admin")
            ->on(["admin" => "id", "goods" => "operator"])
            ->left_join("vendor")
            ->on(["goods" => "vendor_id", "vendor" => "id"])
            ->where(["item_id" => $no])
            ->find([
                "goods.*",
                "`admin`.`realname` as admin",
                "vendor.name as vendor",
                "vendor.tel",
                "vendor.url",
                "vendor.address"
            ]);
        
        if (Utils::isDbNull($goods)) {
            dotnet::PageNotFound("the given goods item is not exists!");
        }

        if (Strings::Empty($goods["vendor"])) {
            $goods["vendor"] = "无供应商";
        }

        if ($goods["gender"] == -1) {
            $goods["gender"] = "未指定或者适用于所有";
        } else if ($goods["gender"] == 0) {
            $goods["gender"] = "女";
        } else {
            $goods["gender"] = "男";
        }

        foreach(["tel", "url", "address", "note"] as $key) {
            if (Strings::Empty($goods[$key])) {
                $goods[$key] = "未填写";
            }
        }

        $inventories = (new Table("inventories"))
            ->left_join("admin")
            ->on(["inventories" => "operator", "admin" => "id"])
            ->where(["item_id" => $goods["id"]])
            ->select([
                "`inventories`.*",
                "`admin`.`realname` as admin"
            ]);

        $goods["batch"] = $inventories;

        View::Display($goods);
    }

    /**
	 * 交易详情
	 * 
	 * @uses view
	*/
	public function trade($transaction) {
		$transaction = (new Table("waterflow"))
			->left_join("admin")
			->on(["waterflow" => "operator", "admin" => "id"])
			->left_join("VIP_members")
			->on(["waterflow" => "buyer", "VIP_members" => "id"])
			->where(["transaction_id" => $transaction])
			->find([
				"waterflow.*",
				"VIP_members.name as vip",
				"admin.realname as admin"
			]);

		if (Utils::isDbNull($transaction)) {
			dotnet::PageNotFound("the given transaction is not exists!");
		}

		if (empty($transaction["vip"])) {
			$transaction["vip"] = "非会员";
		}
		if (Strings::Empty($transaction["note"])) {
			$transaction["note"] = "没有描述信息";
		}

		$goods = json_decode($transaction["goods"]);
		$list = [];
		$goods_info = (new Table("goods"));
		$batch_info = (new Table("trade_items"));

		foreach($goods as $item => $count) {
			$item = $goods_info
				->left_join("vendor")
				->on(["vendor" => "id", "goods" => "vendor_id"])
				->where(["`goods`.`id`" => $item])
				->find([
					"goods.*",
					"vendor.name as vendor"
				]);				

			if (empty($item["vendor"])) {
				$item["vendor"] = "无供应商";
			}

			$batch_id = $batch_info
				->left_join("inventories")
				->on(["trade_items" => "batch_id", "inventories" => "id"])
				->where(["`trade_items`.`item_id`" => $item["id"], "waterflow" => $transaction["id"]])
				->limit(1)
				->select("`inventories`.`batch_id` as batch_id")[0];

			if (Utils::isDbNull($batch_id)) {
				$batch_id = "n/a";
			} else {
				$batch_id = $batch_id["batch_id"];
			}

			$item["batch"] = $batch_id;
			$item["count"] = $count;
			$list[] = $item;
		} 

		$transaction["goods"] = $list;

		View::Display($transaction);
	}
}