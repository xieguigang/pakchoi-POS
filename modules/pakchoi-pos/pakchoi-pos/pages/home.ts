﻿namespace pages {

    export class home extends Bootstrap {

        public get appName(): string {
            return "home";
        }

        private inventories: models.charts.inventories_sparkline = <models.charts.inventories_sparkline>{
            sales: <any>"n/a",
            total: <any>"n/a",
            sparkline: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        };
        private sales: models.charts.sales_sparkline = <models.charts.sales_sparkline>{
            day: <any>"n/a",
            total: <any>"n/a",
            sparkline: [0, 0, 0, 0, 0, 0, 0]
        };

        protected init(): void {
            let vm = this;

            this.showTransactions();

            $(window).on('resizeEnd', function () {
                vm.inventories_sparkline();
                vm.sales_sparkline();
            });

            $ts.get(`@inventories`, function (result) {
                if (result.code != 0) {
                    nifty.errorMsg(<string>result.info);
                } else {
                    vm.inventories = <models.charts.inventories_sparkline>result.info;
                }

                vm.inventories_sparkline();
            });

            $ts.get(`@sales`, function (result) {
                if (result.code != 0) {
                    nifty.errorMsg(<string>result.info);
                } else {
                    vm.sales = <models.charts.sales_sparkline>result.info;
                }

                vm.sales_sparkline();
            });
        }

        private sales_sparkline() {
            let data = this.sales;

            if (isNullOrUndefined(data)) {
                return;
            }

            $ts("#sales-in").display(<any>data.day);
            $ts("#sales-aweek").display(<any>data.total);

            (<any>$("#sales-sparkline-line")).sparkline(data.sparkline, {
                type: 'line',
                width: '100%',
                height: '40',
                spotRadius: 4,
                lineWidth: 1,
                lineColor: '#ffffff',
                fillColor: false,
                minSpotColor: false,
                maxSpotColor: false,
                highlightLineColor: '#ffffff',
                highlightSpotColor: '#ffffff',
                tooltipChartTitle: '销售金额',
                tooltipPrefix: '￥ ',
                spotColor: '#ffffff',
                valueSpots: {
                    '0:': '#ffffff'
                }
            });
        }

        private inventories_sparkline() {
            let data: models.charts.inventories_sparkline = this.inventories;

            if (isNullOrUndefined(data)) {
                return;
            }

            $ts("#inventories-out").display(<any>data.sales);
            $ts("#inventories-all").display(<any>data.total);

            (<any>$("#inventories-sparkline-area")).sparkline(data.sparkline, {
                type: 'line',
                width: '100%',
                height: '40',
                spotRadius: 5,
                lineWidth: 1.5,
                lineColor: 'rgba(255,255,255,.85)',
                fillColor: 'rgba(0,0,0,0.03)',
                spotColor: 'rgba(255,255,255,.5)',
                minSpotColor: 'rgba(255,255,255,.5)',
                maxSpotColor: 'rgba(255,255,255,.5)',
                highlightLineColor: '#ffffff',
                highlightSpotColor: '#ffffff',
                tooltipChartTitle: '库存',
                tooltipSuffix: ' 件'
            });
        }

        private showTransactions(page: number = 1) {
            let vm = this;

            $ts.get(`@load_trades?page=${page}`, function (result) {
                if (result.code != 0) {
                    nifty.errorMsg(<string>result.info);
                } else {
                    let table = $ts("#trades").clear();
                    let tr: IHTMLElement;
                    let link: IHTMLElement;
                    let type: IHTMLElement;

                    for (let trade of <models.tradeInformation[]>result.info) {
                        tr = $ts("<tr>");
                        link = $ts("<a>", {
                            class: "btn-link",
                            href: ""
                        }).display(trade.transaction_id);

                        if (trade.money > 0) {
                            type = $ts("<div>", { class: "label label-table label-info" }).display("消费");
                        } else {
                            type = $ts("<div>", { class: "label label-table label-danger" }).display("退款");
                        }

                        tr.appendElement($ts("<td>").display(link));
                        tr.appendElement($ts("<td>").display(trade.vip));
                        tr.appendElement($ts("<td>").display(trade.time));
                        tr.appendElement($ts("<td>").display(`￥${trade.money}`))
                        tr.appendElement($ts("<td>", { class: "text-center" }).display(type));
                        tr.appendElement($ts("<td>", { class: "text-center" }).display(trade.note));

                        table.appendElement(tr);
                    }
                }
            });
        }
    }
}