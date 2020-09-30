<?php

namespace Drupal\calendar\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CalendarForm extends FormBase {

  private $tableFieldTitles = [
    'Year',
    'Jan',
    'Feb',
    'Mar',
    'Q1',
    'Apr',
    'May',
    'Jun',
    'Q2',
    'Jul',
    'Aug',
    'Sep',
    'Q3',
    'Oct',
    'Nov',
    'Dec',
    'Q4',
    'YTD',
  ];

  // Number of all tables
  public $tablesCount = 1;

  // This year
  public $currentYear = 0;

  // Number of all months with quarters
  public $tableCountMonthsTitles = 0;

  /**
   * @return string
   */
  public function getFormId() {
    return 'calendar';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#id'] = 'calendar-layout-form';

    $form_state->set("currentCalendar", 1);

    $this->tableCountMonthsTitles = count($this->tableFieldTitles) - 2;

    // Take today's year
    $currentYear = date('Y');

    $this->currentYear = $currentYear;

    $tables = $this->tablesCount;

    // Generate first table
    $this->calendarAddTable($form, $form_state, $tables);

    // If the button was pressed then a new table is added
    if ($form_state->getTriggeringElement()['#name'] == "add_table") {
      $tables = $this->tablesCount;
      $tables++;
      $this->tablesCount = $tables;

      $this->calendarAddTable($form, $form_state, $tables);
    }

    $form['newTables']['addTable'] = [
      '#type' => 'button',
      '#value' => 'Add table',
      '#name' => 'add_table',
      '#ajax' => [
        'callback' => "::calendarAddTableCallback",
        'event' => 'click',
        'wrapper' => "calendar-layout-form",
      ],
    ];

    $form['actions'] =[
      '#type' => 'actions',
    ];

    $form["actions"]["submit"] = [
      '#type' => 'submit',
      '#value' => 'Validate',
      '#ajax' => [
        'callback' => "::calendarValidateFormCallback",
        'event' => 'click',
        'wrapper' => "calendar-layout-form",
      ],
    ];

    return $form;
  }

  /**
   * Method that adds new tables
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param int $tables - number of tables
   */
  public function calendarAddTable(array &$form, FormStateInterface $form_state, $tables = 1) {
    for ($t = 1; $t <= $tables; $t++) {
      $form["calendar-{$t}"] = [
        '#type' => 'table',
        '#caption' => "Calendar {$t}",
        '#header' => $this->tableFieldTitles,
        '#prefix' => "<div id ='calendar-table-{$t}'>",
        '#suffix' => "</div>",
      ];

      $calendarRows = $form_state->get("calendar{$t}CountRows");

      if (empty($calendarRows)) {
        $calendarRows = 1;
        // Variable calendar{$t}CountRows - number of rows in the table {$t}
        $form_state->set("calendar{$t}CountRows", $calendarRows);
      }

      $rows = $calendarRows;

      // Generate first row in the table
      $this->calendarAddRow($form, $form_state, $t, $rows);

      // If the button was pressed then a new row is added
      if ($form_state->getTriggeringElement()['#name'] == "add_row_{$t}") {
        $rows = $form_state->get("calendar{$t}CountRows");
        $rows++;
        $form_state->set("calendar{$t}CountRows", $rows);

        // Table in which to add rows
        $form_state->set("currentCalendar", $t);

        $this->calendarAddRow($form, $form_state, $t, $rows);
      }

      $form["actionAddRow{$t}"]["addRow{$t}"] = [
        '#type' => 'button',
        '#value' => 'Add row',
        '#name' => "add_row_{$t}",
        '#ajax' => [
          'callback' => "::calendarAddRowCallback",
          'event' => 'click',
          'wrapper' => "calendar-table-{$t}",
        ],
      ];
    }
  }

  /**
   * Method that adds new rows
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $currentTable - specific table
   * @param $rows - number of rows in the table '$currentTable'
   */
  public function calendarAddRow(array &$form, FormStateInterface $form_state, $currentTable, $rows) {
    for ($r = 1; $r <= $rows; $r++) {
      $yearValue = $this->currentYear - ($r - 1);

      $form["calendar-{$currentTable}"][$r]["Year"] = [
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#default_value' => $yearValue,
        '#prefix' => "<div id='calendar-{$currentTable}-row-{$r}-field-Year'>",
        '#suffix' => "</div>",
      ];

      for ($f = 1; $f <= $this->tableCountMonthsTitles; $f++) {
        $field = $this->tableFieldTitles[$f];

        $fieldQuarter = $form_state->get('currentQuarter');

        if (empty($fieldQuarter)) {
          $fieldQuarter = 'Q1';
          $form_state->set('currentQuarter', $fieldQuarter);
        }

        $form["calendar-{$currentTable}"][$r][$field] = [
          '#type' => 'number',
          '#prefix' => "<div id='calendar-{$currentTable}-row-{$r}-field-{$field}'>",
          '#suffix' => "</div>",
          '#calendar-table' => $currentTable,
          '#calendar-row' => $r,
          '#calendar-field' => $field,
          '#calendar-quarter' => $fieldQuarter,
          '#step' => '0.01',
          '#ajax' => [
            'callback' => "::calendarQuarterCallback",
            'event' => 'change',
            'wrapper' => "calendar-{$currentTable}-row-{$r}-field-{$fieldQuarter}",
            'progress' => FALSE,
            'disable-refocus' => TRUE,
          ],
        ];

        // Defining a quarter for each month of the year
        if (($f % 4 == 0)) {
          if ($fieldQuarter == 'Q4') {
            $newField = 'Q1';
          } else {
            $newField = $this->tableFieldTitles[$f+4];
          }

          $form_state->set('currentQuarter', $newField);
        }
      }

      $form["calendar-{$currentTable}"][$r]['YTD'] = [
        '#type' => 'number',
        '#prefix' => "<div id='calendar-{$currentTable}-row-{$r}-field-YTD'>",
        '#suffix' => "</div>",
        '#calendar-table' => $currentTable,
        '#calendar-row' => $r,
        '#calendar-field' => 'YTD',
        '#calendar-quarter' => 'YTD',
        '#disabled' => TRUE,
      ];
    }
  }

  /**
   * Method that counts quarters and specific year
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $table - specific table
   * @param $row - specific row (year) in the variable '$table'
   * @param $currentField - specific field in the variables '$table' & '$row'
   * @param $quarter - specific quarter in the variable '$table' & '$row' & '$currentField'
   */
  public function calendarUpdateQuarters(array &$form, FormStateInterface $form_state, $table, $row, $currentField, $quarter) {
    $currentFieldValue = 0;
    $sumQuarters = 0;

    for ($f = 1; $f <= count($this->tableFieldTitles); $f++) {
      $field = $this->tableFieldTitles[$f];

      // Sum of all months in a given quarter in variable '$quarter'
      $sumMonths = $form_state->get("sumForQuarter{$quarter}");
      if (empty($sumMonths)) {
        $sumMonths = 0;
        $form_state->set("sumForQuarter{$quarter}", $sumMonths);
      }

      // Quarter of a certain field
      $currentQuarterField = $form["calendar-{$table}"][$row][$field]['#calendar-quarter'];
      $currentValue = $form["calendar-{$table}"][$row][$field]['#value'];

      if (empty($currentValue)) {
        $currentValue = 0;
      }

      // If '$f' field is not a quarter
      if (($f % 4 != 0) && ($currentQuarterField == $quarter)) {
        $sumMonths += $currentValue;
        $form_state->set("sumForQuarter{$quarter}", $sumMonths);
      }

      // If '$f' field is a quarter
      if ($f % 4 == 0) {
        $currentFieldValue = $form["calendar-{$table}"][$row][$currentField]['#value'];
      }

      // Counts quarters
      if (($f % 4 == 0) && ($currentQuarterField != $quarter)) {
        $sumQuarters += $currentValue;
      }
    }

    if ($sumMonths != 0) {
      $sumMonths++;
      $sumMonths = $sumMonths / 3;

      $sumQuarters += $sumMonths;

      $sumQuarters++;
      $sumQuarters = $sumQuarters / 4;

      // Check for change the quarter to 0.05
      if ($currentFieldValue != 0) {
        $newSumMonths = $currentFieldValue - $sumMonths;

        if ($newSumMonths <= 0.05 && $newSumMonths >= -0.05) {
          $sumMonths = $currentFieldValue;
        }
      }
    }

    $form["calendar-{$table}"][$row][$quarter]["#value"] = round($sumMonths, 2);
    $form["calendar-{$table}"][$row]["YTD"]["#value"] = round($sumQuarters, 2);
  }

  /**
   * Method checks all fields (months) from first to last occurrence
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $table - specific table
   * @param $firstValidRow - first row (year) in which the entry occurs
   * @param $firstValidField - first field (month) in which the entry occurs
   * @param $lastValidRow - last row (year) in which the entry ends
   * @param $lastValidField - last field (month) in which the entry ends
   *
   * @return string 'Valid' or 'Invalid'
   */
  public function calendarValidateCheckGap(array &$form, FormStateInterface $form_state, $table, $firstValidRow, $firstValidField, $lastValidRow, $lastValidField) {
    if ($lastValidField == 0 && $firstValidField != 0) {
      $conclusion = 'Valid';
    } else {
      for ($r = $firstValidRow; $r <= $lastValidRow; $r++) {
        if ($firstValidRow == $r && $lastValidRow == $r) {
          $firstField = $firstValidField;
          $lastField = $lastValidField;
        } elseif ($firstValidRow == $r && $lastValidRow != $r) {
          $firstField = $firstValidField;
          $lastField = $this->tableCountMonthsTitles;
        } elseif ($firstValidRow != $r && $lastValidRow == $r) {
          $firstField = 1;
          $lastField = $lastValidField;
        } else {
          $firstField = 1;
          $lastField = $this->tableCountMonthsTitles;
        }

        for ($f = $firstField; $f <= $lastField; $f++) {
          $field = $this->tableFieldTitles[$f];
          $fieldValue = $form["calendar-{$table}"][$r][$field]['#value'];

          if ($fieldValue != 0) {
            $conclusion = 'Valid';
          } elseif ($fieldValue == 0) {
            $conclusion = 'Invalid';
            return $conclusion;
          }
        }
      }
    }

    return $conclusion;
  }

  public function calendarAddTableCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Returns a specific table with a new row (year)
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed Specific table
   */
  public function calendarAddRowCallback(array &$form, FormStateInterface $form_state) {
    $table = $form_state->get('currentCalendar');

    return $form["calendar-{$table}"];
  }

  /**
   * Returns all counted quarters and YTD
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function calendarQuarterCallback(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getTriggeringElement()['#calendar-table'];
    $row = $form_state->getTriggeringElement()['#calendar-row'];
    $field = $form_state->getTriggeringElement()['#calendar-field'];
    $quarter = $form_state->getTriggeringElement()['#calendar-quarter'];

    $this->calendarUpdateQuarters($form, $form_state, $table, $row, $field, $quarter);

    $response = new AjaxResponse();

    $response->addCommand(new ReplaceCommand("#calendar-{$table}-row-{$row}-field-{$quarter}", $form["calendar-{$table}"][$row][$quarter]));
    $response->addCommand(new ReplaceCommand("#calendar-{$table}-row-{$row}-field-YTD", $form["calendar-{$table}"][$row]['YTD']));

    return $response;
  }

  /**
   * Returns 'Invalid' or 'Valid' and submit the form
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function calendarValidateFormCallback(array &$form, FormStateInterface $form_state) {

    $message = $form_state->get('calendarValidationStatus');

    $response = new AjaxResponse();

    if ($message == 'Valid') {
      $response->addCommand(new RedirectCommand('/calendar'));
    } else {
      $response->addCommand(new ReplaceCommand('#calendar-messages', "<p id='calendar-error'>$message</p>"));
    }

    return $response;
  }

  /**
   * Checks all fields in the table for validity and also checks the validity between tables
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|void
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $tables = $this->tablesCount;

    // First occerence of a row (year) in the first table
    $firstTableFirstRow = 0;

    // First occerence of a field (month) in the first table
    $firstTableFirstField = 0;

    // Last occurence of a row (year) in the first table
    $firstTableLastRow = 0;

    // Last occurence of a field (month) in the first table
    $firstTableLastField = 0;

    for ($t = 1; $t <= $tables; $t++) {
      $rows = $form_state->get("calendar{$t}CountRows");

      // First and last occurrences of all tables in turn
      $nextTableFirstRow = 0;
      $nextTableFirstField = 0;
      $nextTableLastRow = 0;
      $nextTableLastField = 0;

      for ($r = 1; $r <= $rows; $r++) {
        for ($f = 1; $f <= $this->tableCountMonthsTitles; $f++) {
          $field = $this->tableFieldTitles[$f];
          $fieldValue = $form["calendar-{$t}"][$r][$field]['#value'];

          // Skipping all quarters to check only months
          if ($f % 4 == 0) {
            continue;
          }

          // Filling occurrences of only the first table
          if ($t == 1) {
            if ($fieldValue != 0 && $firstTableFirstField == 0) {
              $firstTableFirstRow = $r;
              $firstTableFirstField = $f;
            } elseif ($fieldValue != 0) {
              $firstTableLastRow = $r;
              $firstTableLastField = $f;
            }
          }

          // Filling in all occurrences of subsequent tables
          if ($fieldValue != 0 && $nextTableFirstField == 0) {
            $nextTableFirstRow = $r;
            $nextTableFirstField = $f;
          } elseif ($fieldValue != 0) {
            $nextTableLastRow = $r;
            $nextTableLastField = $f;
          }

          // Checking for similarity of occurrences of the first and subsequent tables
          if ($f == $this->tableCountMonthsTitles-1 && $r == $rows) {
            if (($firstTableFirstRow != $nextTableFirstRow) ||
              ($firstTableFirstField != $nextTableFirstField) ||
              ($firstTableLastRow != $nextTableLastRow) ||
              ($firstTableLastField != $nextTableLastField)) {
              $conclusion = 'Invalid';
              $form_state->set('calendarValidationStatus', $conclusion);

              return $form;
            } else {
              $conclusion = $this->calendarValidateCheckGap(
                $form,
                $form_state,
                $t,
                $nextTableFirstRow,
                $nextTableFirstField,
                $nextTableLastRow,
                $nextTableLastField
              );

              $form_state->set('calendarValidationStatus', $conclusion);
            }

            if ($conclusion == 'Invalid') {
              $form_state->set('calendarValidationStatus', $conclusion);

              return $form;
            }
          }
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = $form_state->get('calendarValidationStatus');

    if ($message == 'Valid') {
      \Drupal::messenger()->addMessage($message, 'status');
    }
  }

}
