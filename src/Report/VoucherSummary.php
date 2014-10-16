<?php

namespace Message\Mothership\Voucher\Report;

use Message\Cog\DB\QueryBuilderInterface;
use Message\Report\ReportInterface;
use Message\Mothership\Report\Report\AbstractReport;
use Message\Cog\DB\QueryBuilderFactory;
use Message\Mothership\Report\Chart\TableChart;
use Message\Mothership\Report\Filter\DateFilter;

class VoucherSummary extends AbstractReport
{
	private $_builderFactory;
	private $_charts;
	private $_filters;

	public function __construct(QueryBuilderFactory $builderFactory)
	{
		$this->name = "voucher-summary-report";
		$this->_builderFactory = $builderFactory;
		$this->_charts = [new TableChart];
		$this->_filters = [new DateFilter];
	}

	public function getName()
	{
		return $this->name;
	}

	public function getCharts()
	{
		$data = $this->dataTransform($this->getQuery()->run());

		foreach ($this->_charts as $chart) {
			$chart->setData($data);
		}

		return $this->_charts;
	}

	private function getQuery()
	{
		$queryBuilder = $this->_builderFactory->getQueryBuilder();

		$queryBuilder
			->select('v.voucher_id AS "Code"')
			->select('DATE_FORMAT(from_unixtime(v.created_at),"%d %b %Y %h:%i") AS "Created"')
			->select('DATE_FORMAT(from_unixtime(v.expires_at),"%d %b %Y %h:%i") AS "Expires"')
			->select('v.amount AS "Value"')
			->select('v.amount + IFNULL(used.amount_used, 0) AS "Balance"')
			->select('IFNULL(order_item.order_id,"") AS "Order Purchased"')
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
		;

		return $queryBuilder->getQuery();
	}

	protected function dataTransform($data)
	{
		$result = [];
		$result[] = $data->columns();

		foreach ($data as $row) {
			$result[] = get_object_vars($row);

		}

		return $result;
	}
}

