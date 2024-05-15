<?php

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine\ORM;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\QueryBuilderProcessorInterface;
use Omines\DataTablesBundle\Column\DateTimeColumn;

class SearchBuilderCriteriaProvider implements QueryBuilderProcessorInterface {

    protected $request;
    protected $query;
    protected $allowedColumns;
    protected $mapColumns;
    protected $sbRules = [
        '=' => '=',
        '>' => '>',
        '>=' => '>=',
        '<' => '<',
        '<=' => '<=',
        '!=' => '!=',
        'starts' => 'LIKE',
        '!starts' => 'NOT LIKE',
        'contains' => 'LIKE',
        '!contains' => 'NOT LIKE',
        'ends' => 'LIKE',
        '!ends' => 'NOT LIKE',
        'null' => 'IS NULL',
        '!null' => 'IS NOT NULL',
        'between' => 'between',
        '!between' => 'not between',
    ];

    public function __construct($request) {
        $this->request = $request;
    }

    public function process(QueryBuilder $queryBuilder, DataTableState $state): void {
        $this->processSearchBuilder($queryBuilder, $state);
    }

    public function processSearchBuilder(QueryBuilder $queryBuilder, DataTableState $state): void {

        if ($this->request->get('searchBuilder')) {

            $searchBuilder = $this->request->get('searchBuilder');
            if ($searchBuilder) {
                $comparisons = $this->searchBuilder($queryBuilder, $state, $searchBuilder);
                if ($comparisons !== null) {
                    $queryBuilder->andWhere($comparisons);
                }
            }
        }
    }

    public function searchBuilder(QueryBuilder $queryBuilder, DataTableState $state, $searchBuilder) {


        $expr = $queryBuilder->expr();

        $sbLogic = [];
        $logic = $searchBuilder['logic'] ?? "AND";
        if ($logic == "AND") {
            $comparisons = $expr->andX();
        } else {
            $comparisons = $expr->orX();
        }
        $logicValid = in_array($logic, ['AND', "OR"]);

        if ($logicValid && isset($searchBuilder['criteria'])) {
            foreach ($searchBuilder['criteria'] as $rule) {

                $col = $rule['origData'] ?? null;

                $searchTerm = (!in_array($rule['condition'] ?? null, ['null', '!null'])) ? $rule['value1'] ?? false : true;

                // If criteria is defined then this must be a group
                if (isset($rule['criteria'])) {

                    $comparisons->add($this->searchBuilder($queryBuilder, $state, $rule));
                } elseif ($col && $searchTerm && array_key_exists($rule['condition'] ?? null, $this->sbRules) && (null !== $state->getDataTable()->getColumnByName($col))) {



                    $column = $state->getDataTable()->getColumnByName($col);

                    if ($rule['condition'] === 'starts' || $rule['condition'] === '!starts') {
                        $searchTerm = $searchTerm . '%';
                    } elseif ($rule['condition'] === 'ends' || $rule['condition'] === '!ends') {
                        $searchTerm = '%' . $searchTerm;
                    } elseif ($rule['condition'] === 'contains' || $rule['condition'] === '!contains') {
                        $searchTerm = '%' . $searchTerm . '%';
                    } elseif ($rule['condition'] === 'between' || $rule['condition'] === '!between') {
                        if (preg_match("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/", $rule['value1'])) {
                            $date2 = $rule['value2'] ?? $rule['value1'];
                            $searchTerm = [$rule['value1'] . " 00:00:00", $date2 . " 23:59:59"];
                        } else {
                            $searchTerm = [$rule['value1'], $rule['value2'] ?? null];
                        }
                    }
                    $col = (!empty($state->getDataTable()->getColumnByName($col)->getField())) ? $state->getDataTable()->getColumnByName($col)->getField() ?? $col : $col;
                    $sbLogic[] = [$col, $this->sbRules[$rule['condition'] ?? null], $searchTerm, $column];
                }
            }
            if ($sbLogic) {

                foreach ($sbLogic as $r) {
                    $column = $r[3];
                    $operator = $r[1];
                    $search = $r[2];

                    if ($column instanceof DateTimeColumn) {
                        if (is_array($search)) {
                            foreach ($search as $s) {
                                $paramsDate = $this->validateDateDatatable($s);
                            }
                        } else {
                            $paramsDate = $this->validateDateDatatable($search);
                        }
                        if ($paramsDate !== null) {
                            $column->setOption('leftExpr', $paramsDate["leftExpr"]);
                            $column->setOption('rightExpr', $paramsDate['rightExpr']);
                        }
                    }

                    if ($r[1] == 'between') {

                        $comparisons_between = $expr->andX();
                        $comparisons_between->add(new Comparison($column->getLeftExpr(), '>=',
                                        $expr->literal($search[0])));
                        $comparisons_between->add(new Comparison($column->getLeftExpr(), '<=',
                                        $expr->literal($search[1])));

                        $comparisons->add($comparisons_between);
                    } elseif ($r[1] == 'not between') {
                        $comparisons_between = $expr->orX();
                        $comparisons_between->add(new Comparison($column->getLeftExpr(), '<',
                                        $expr->literal($search[0])));
                        $comparisons_between->add(new Comparison($column->getLeftExpr(), '>',
                                        $expr->literal($search[1])));

                        $comparisons->add($comparisons_between);
                    } elseif ($r[1] == 'IS NULL') {
                        $comparisons->add(new Comparison($column->getLeftExpr(), 'IS', 'NULL'));
                    } elseif ($r[1] == 'IS NOT NULL') {
                        $comparisons->add(new Comparison($column->getLeftExpr(), 'IS NOT', 'NULL'));
                    } else {
                        if ($column instanceof DateTimeColumn) {
                            $comparisons->add(new Comparison($column->getLeftExpr(), $operator,
                                            $expr->literal($column->getRightExpr($search))));
                        } else {
                            $comparisons->add(new Comparison($column->getLeftExpr(), $operator,
                                            $expr->literal($search)));
                        }
                    }
                }
                return $comparisons;
            }
        }
    }

    function isValidDate($date, $format = 'Y-m-d') {
        $dateTime = \DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    public function validateDateDatatable($value) {

        if (is_string($value)) {
            $value = trim($value);

            if ($this->isValidDate($value, "Y")) {
                return [
                    'leftExpr' => function ($arg) {
                        return 'YEAR(' . $arg . ')';
                    },
                    'rightExpr' => $value
                ];
            } elseif ($this->isValidDate($value, "d-m-Y")) {
                $dateString = date_format(\DateTime::createFromFormat('d-m-Y', $value), 'Y-m-d');
                return [
                    'leftExpr' => function ($arg) {
                        return 'DATE(' . $arg . ')';
                    },
                    'rightExpr' => $dateString];
            } elseif ($this->isValidDate($value, "Y-m-d")) {
                $dateString = date_format(\DateTime::createFromFormat('Y-m-d', $value), 'Y-m-d');
                return [
                    'leftExpr' => function ($arg) {
                        return 'DATE(' . $arg . ')';
                    },
                    'rightExpr' => $dateString];
            } elseif ($this->isValidDate($value, "Y-m")) {
                $dateString = date_format(\DateTime::createFromFormat('Y-m', $value), 'Ym');
                return [
                    'leftExpr' => function ($arg) {
                        return 'YEARMONTH(' . $arg . ')';
                    },
                    'rightExpr' => $dateString
                ];
            } elseif ($this->isValidDate($value, "m-Y")) {
                $dateString = date_format(\DateTime::createFromFormat('m-Y', $value), 'Ym');
                return [
                    'leftExpr' => function ($arg) {
                        return 'YEARMONTH(' . $arg . ')';
                    },
                    'rightExpr' => $dateString
                ];
            } else
                return null;
        } else
            return null;
    }
}
