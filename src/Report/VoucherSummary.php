<?php

namespace Message\Mothership\Voucher\Report;

use Message\Cog\DB\QueryBuilderInterface;
use Message\Cog\DB\QueryBuilderFactory;
use Message\Cog\Localisation\Translator;

use Message\Mothership\Report\Report\AbstractReport;
use Message\Mothership\Report\Chart\TableChart;

use Message\Report\ReportInterface;

class VoucherSummary extends AbstractReport
{
	private $_builderFactory;
	private $_charts;

	public function __construct(QueryBuilderFactory $builderFactory, Translator $trans)
	{
		$this->name = 'voucher_summary';
		$this->reportGroup = 'Discounts & Vouchers';
		//$this->reportGroup = $trans->trans('ms.voucher.report.group.vouchers-discounts');
		$this->_builderFactory = $builderFactory;
		$this->_charts = [new TableChart];
	}

	public function getName()
	{
		return $this->name;
	}

	public function getReportGroup()
	{
		return $this->reportGroup;
	}

	public function getCharts()
	{
		$data = $this->dataTransform($this->getQuery()->run());
		$columns = $this->getColumns();

		foreach ($this->_charts as $chart) {
			$chart->setColumns($columns);
			$chart->setData($data);
		}

		return $this->_charts;
	}

	public function getColumns()
	{
		$columns = [
			['type' => 'string', 	'name' => "Voucher",	],
			['type' => 'number',	'name' => "Created",	],
			['type' => 'number',	'name' => "Expires",	],
			['type' => 'string',	'name' => "Currency",	],
			['type' => 'number',	'name' => "Value",		],
			['type' => 'number',	'name' => "Used",		],
			['type' => 'number',	'name' => "Balance",	],
			['type' => 'string',	'name' => "Order Purchased",	],
			['type' => 'string',	'name' => "Status",		],
		];

		return json_encode($columns);
	}

	private function getQuery()
	{
		$queryBuilder = $this->_builderFactory->getQueryBuilder();

		$queryBuilder
			->select('v.voucher_id AS "Code"')
			->select('v.created_at AS "Created"')
			->select('v.expires_at AS "Expires"')
			->select('v.currency_id AS "Currency"')
			->select('v.amount AS "Value"')
			->select('IFNULL(used.amount_used, 0) * -1 AS "Used"')
			->select('v.amount + IFNULL(used.amount_used, 0) AS "Balance"')
			->select('IFNULL(order_item.order_id,"") AS "OrderPurchased"')
			->select('IF(v.used_at > 0, "Used",IF(from_unixtime(v.expires_at) < NOW(), "Expired","Valid")) AS "Status"')
			->from('v','voucher')
			->leftJoin("used","used.reference = v.voucher_id",
				$this->_builderFactory->getQueryBuilder()
					->select('-sum(amount) AS amount_used')
					->select('reference')
					->from('payment')
					->where('method = "voucher"')
					->groupBy('reference')
				)
			->leftJoin('user_created','user_created.user_id = v.created_by','user')
			->leftJoin('order_item','v.purchased_as_item_id = order_item.item_id')
			->leftJoin('order_summary','order_item.order_id = order_summary.order_id')
			->leftJoin('payment','v.voucher_id = payment.reference')
			->leftJoin('order_payment','order_payment.payment_id = payment.payment_id')
			->leftJoin('purchase','purchase.order_id = order_payment.order_id','order_summary')
			->orderBy('v.created_at')
		;

		return $queryBuilder->getQuery();
	}

	protected function dataTransform($data)
	{
		$result = [];

		foreach ($data as $row) {
			$result[] = [
				$row->Code,
				[ 'v' => $row->Created, 'f' => date('Y-m-d H:i', $row->Created)],
				[ 'v' => $row->Expires, 'f' => date('Y-m-d H:i', $row->Expires)],
				$row->Currency,
				[ 'v' => (float) $row->Value, 'f' => $row->Value],
				[ 'v' => (float) $row->Used, 'f' => $row->Used],
				[ 'v' => (float) $row->Balance, 'f' => $row->Balance],
				$row->OrderPurchased,
				$row->Status,
			];
		}

		return json_encode($result);
	}
}
