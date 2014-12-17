<?php

namespace Message\Mothership\Voucher\Report;

use Message\Cog\DB\QueryBuilderInterface;
use Message\Cog\DB\QueryBuilderFactory;
use Message\Cog\Routing\UrlGenerator;

use Message\Mothership\Report\Report\AbstractReport;
use Message\Mothership\Report\Chart\TableChart;

class VoucherSummary extends AbstractReport
{
	/**
	 * Constructor.
	 *
	 * @param QueryBuilderFactory   $builderFactory
	 * @param UrlGenerator          $routingGenerator
	 */
	public function __construct(QueryBuilderFactory $builderFactory, UrlGenerator $routingGenerator)
	{
		parent::__construct($builderFactory, $routingGenerator);
		$this->name = 'voucher_summary';
		$this->displayName = 'Voucher Summary';
		$this->reportGroup = 'Discounts & Vouchers';
		$this->_charts = [new TableChart];
	}

	/**
	 * Retrieves JSON representation of the data and columns.
	 * Applies data to chart types set on report.
	 *
	 * @return array  Returns all types of chart set on report with appropriate data.
	 */
	public function getCharts()
	{
		$data = $this->_dataTransform($this->_getQuery()->run(), "json");
		$columns = $this->_parseColumns($this->getColumns());

		foreach ($this->_charts as $chart) {
			$chart->setColumns($columns);
			$chart->setData($data);
		}

		return $this->_charts;
	}

	/**
	 * Set columns for use in reports.
	 *
	 * @return array  Returns array of columns as keys with format for Google Charts as the value.
	 */
	public function getColumns()
	{
		return [
			'Voucher'         => 'string',
			'Order Purchased' => 'string',
			'Created By'      => 'string',
			'Created'         => 'number',
			'Expires'         => 'number',
			'Currency'        => 'string',
			'Initial Value'   => 'number',
			'Used'            => 'number',
			'Balance'         => 'number',
			'Status'          => 'string',
		];
	}

	/**
	 * Gets all voucher data.
	 *
	 * @return Query
	 */
	protected function _getQuery()
	{
		$queryBuilder = $this->_builderFactory->getQueryBuilder();

		$queryBuilder
			->select('v.voucher_id AS "Code"')
			->select('IFNULL(order_item.order_id,"") AS "Order_Purchased"')
			->select('v.created_by AS "Created_By"')
			->select('CONCAT(user_created.forename," ",user_created.surname) AS "Created_By_Name"')
			->select('v.created_at AS "Created"')
			->select('v.expires_at AS "Expires"')
			->select('v.currency_id AS "Currency"')
			->select('v.amount AS "Value"')
			->select('IFNULL(used.amount_used, 0) * -1 AS "Used"')
			->select('v.amount + IFNULL(used.amount_used, 0) AS "Balance"')
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
			->orderBy('v.created_at DESC')
		;

		return $queryBuilder->getQuery();
	}

	/**
	 * Takes the data and transforms it into a useable format.
	 *
	 * @param  $data    DB\Result  The data from the report query.
	 * @param  $output  String     The type of output required.
	 *
	 * @return string|array  Returns data as string in JSON format or array.
	 */
	protected function _dataTransform($data, $output = null)
	{
		$result = [];

		if ($output === "json") {

			foreach ($data as $row) {
				$result[] = [
					'<a href ="'.$this->generateUrl('ms.cp.voucher.view', ['id' => $row->Code]).'">'.$row->Code.'</a>',
					'<a href ="'.$this->generateUrl('ms.commerce.order.detail.view', ['orderID' => (int) $row->Order_Purchased]).'">'.$row->Order_Purchased.'</a>',
					'<a href ="'.$this->generateUrl('ms.cp.user.admin.detail.edit', ['userID' => (int) $row->Created_By]).'">'.$row->Created_By_Name.'</a>',
					[
						'v' => $row->Created,
						'f' => date('Y-m-d H:i', $row->Created)
					],
					[
						'v' => $row->Expires,
						'f' => date('Y-m-d H:i', $row->Expires)
					],
					$row->Currency,
					[
						'v' => (float) $row->Value,
						'f' => $row->Value],
					[
						'v' => (float) $row->Used,
						'f' => $row->Used],
					[
						'v' => (float) $row->Balance,
						'f' => $row->Balance
					],
					$row->Status,
				];
			}
			return json_encode($result);

		} else {

			foreach ($data as $row) {
				$result[] = [
					$row->Code,
					$row->Order_Purchased,
					$row->Created_By,
					date('Y-m-d H:i', $row->Created),
					date('Y-m-d H:i', $row->Expires),
					$row->Currency,
					$row->Value,
					$row->Used,
					$row->Balance,
					$row->Status,
				];
			}
			return $result;
		}
	}
}
