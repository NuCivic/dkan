<?php

namespace Drupal\Tests\common\Unit\Storage;

use Drupal\common\Storage\Query;

/**
 * Data provider for testing the SelectFactory class.
 *
 * Provides a number of query objects with expected SQL equivalents or exception
 * messages. Methods are public static so that other tests can easily pull them
 * in individually.
 *
 * @see Drupal\Tests\common\Storage\SelectFactoryTest
 * @see Drupal\Tests\datastore\Service\QueryTest
 */
class QueryDataProvider {

  const QUERY_OBJECT = 1;
  const SQL = 2;
  const EXCEPTION = 3;

  public function getAllData($return) {
    $tests = [
      'noPropertiesQuery',
      'propertiesQuery',
      'badPropertyQuery',
      'unsafePropertyQuery',
      'expressionQuery',
      'nestedExpressionQuery',
      'badExpressionOperandQuery',
      'conditionQuery',
      'nestedConditionGroupQuery',
      'sortQuery',
      'badSortQuery',
      'offsetQuery',
      'limitOffsetQuery',
      'joinsQuery',
      'joinWithPropertiesFromBothQuery',
      'countQuery',
    ];
    $data = [];
    foreach ($tests as $test) {
      $data[] = [
        self::$test(self::QUERY_OBJECT),
        self::$test(self::SQL),
        self::$test(self::EXCEPTION),
      ];
    }
    return $data;
  }

  public static function noPropertiesQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        return $query;

      case self::SQL:
        return "SELECT t.* FROM {table} t LIMIT 500 OFFSET 0";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function propertiesQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->properties = ["field1", "field2"];
        return $query;

      case self::SQL:
        return "SELECT t.field1 AS field1, t.field2 AS field2 FROM {table} t";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function badPropertyQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->properties = [(object) ["collection" => "t"]];
        return $query;

      case self::SQL:
        return FALSE;

      case self::EXCEPTION:
        return "Bad query property";

    }
  }

  public static function unsafePropertyQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->properties = ["l.field3"];
        return $query;

      case self::SQL:
        return FALSE;

      case self::EXCEPTION:
        return "Unsafe property name: l.field3";

    }
  }

  public static function expressionQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->properties = [
          (object) [
            "alias" => "add_one",
            "expression" => (object) [
              "operator" => "+",
              "operands" => ["field1", 1],
            ],
          ],
          (object) [
            "alias" => "add_two",
            "expression" => (object) [
              "operator" => "+",
              "operands" => [
                (object) ["property" => "field2", "collection" => "t"],
                2,
              ],
            ],
          ],
        ];
        return $query;

      case self::SQL:
        return "SELECT (t.field1 + 1) AS add_one, (t.field2 + 2) AS add_two FROM {table} t";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function nestedExpressionQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->properties = [
          (object) [
            "alias" => "nested",
            "expression" => (object) [
              "operator" => "*",
              "operands" => [
                (object) [
                  "operator" => "+",
                  "operands" => ["field1", "field2"],
                ],
                (object) [
                  "operator" => "+",
                  "operands" => ["field3", "field4"],
                ],
              ],
            ],
          ],
        ];
        return $query;

      case self::SQL:
        return "SELECT ((t.field1 + t.field2) * (t.field3 + t.field4)) AS nested FROM {table} t";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function badExpressionOperandQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->properties = [
          (object) [
            "alias" => "bad_expression",
            "expression" => (object) [
              "operator" => "AVG",
              "operands" => ["field1", "field2"],
            ],
          ],
        ];
        return $query;

      case self::SQL:
        return FALSE;

      case self::EXCEPTION:
        return "Only basic arithmetic expressions currently supported.";

    }
  }

  public static function conditionQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->conditions = [
          (object) [
            "property" => "field1",
            "collection" => "t",
            "value" => "value",
          ],
        ];
        return $query;

      case self::SQL:
        return "WHERE t.field1 = :db_condition_placeholder_0";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function nestedConditionGroupQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->conditions = [
          (object) [
            "groupOperator" => "or",
            "conditions" => [
              (object) [
                "collection" => "t",
                "property" => "field1",
                "value" => "value1",
                "operator" => "<",
              ],
              (object) [
                "groupOperator" => "and",
                "conditions" => [
                  (object) [
                    "collection" => "t",
                    "property" => "field2",
                    "value" => "value2",
                  ],
                  (object) [
                    "collection" => "t",
                    "property" => "field3",
                    "value" => "value3",
                  ],
                ],
              ]
            ],
          ],
        ];
        return $query;

      case self::SQL:
        return "WHERE (t.field1 < :db_condition_placeholder_0) OR ((t.field2 = :db_condition_placeholder_1) AND (t.field3 = :db_condition_placeholder_2))";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function sortQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->sort = [
          "asc" => [],
          "desc" => ["field1"],
        ];
        return $query;

      case self::SQL:
        return "ORDER BY t.field1 DESC";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function badSortQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->sort = ["foo" => ["field1"]];
        return $query;

      case self::SQL:
        return FALSE;

      case self::EXCEPTION:
        return "Invalid sort.";

    }
  }

  public static function offsetQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->offset = 5;
        return $query;

      case self::SQL:
        return "LIMIT 500 OFFSET 5";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function limitOffsetQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->offset = 5;
        $query->limit = 15;
        return $query;

      case self::SQL:
        return "LIMIT 15 OFFSET 5";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function joinsQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->joins = [
          (object) [
            "collection" => "table2",
            "alias" => "l",
            "on" => [
              (object) ["collection" => "t", "property" => "field1"],
              (object) ["collection" => "l", "property" => "field1"],
            ],
          ],
        ];
        return $query;

      case self::SQL:
        return "INNER JOIN {table2} l ON t.field1 = l.field1";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function joinWithPropertiesFromBothQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->properties = [
          (object) [
            "property" => "field2",
            "collection" => "t",
          ],
          (object) [
            "property" => "field3",
            "collection" => "l",
          ],
        ];
        $query->joins = [
          (object) [
            "collection" => "table2",
            "alias" => "l",
            "on" => [
              (object) ["collection" => "t", "property" => "field1"],
              (object) ["collection" => "l", "property" => "field1"],
            ],
          ],
        ];
        return $query;

      case self::SQL:
        return "SELECT t.field2 AS field2, l.field3 AS field3 FROM {table} t INNER JOIN {table2} l";

      case self::EXCEPTION:
        return FALSE;
    }
  }

  public static function countQuery($return) {
    switch ($return) {
      case self::QUERY_OBJECT:
        $query = new Query();
        $query->count = TRUE;
        return $query;

      case self::SQL:
        return "SELECT COUNT(*) AS expression";

      case self::EXCEPTION:
        return FALSE;
    }
  }

}