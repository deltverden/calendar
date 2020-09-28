<?php

namespace Drupal\calendar\Form;

use Drupal\Core\Ajax\AjaxResponse;
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

  public $addTables = 1;
  public $year = 0;
  public $tableCountMonthsTitles = 0;

  public function getFormId() {
    return 'calendar';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#id'] = 'calendar-layout-form';

    $form_state->set("currentCalendar", 1);

    $this->tableCountMonthsTitles = count($this->tableFieldTitles) - 2;

    $currentYear = date('Y');

    $this->year = $currentYear;

    $tables = $this->addTables;

    $this->calendarAddTable($form, $form_state, $tables);

    if ($form_state->getTriggeringElement()['#name'] == "add_table") {
      $tables = $this->addTables;
      $tables++;
      $this->addTables = $tables;

      $this->calendarAddTable($form, $form_state, $tables);
    }

    $form['actions']['addTable'] = [
      '#type' => 'button',
      '#value' => 'Add table',
      '#name' => 'add_table',
      '#ajax' => [
        'callback' => "::calendarAddTableCallback",
        'event' => 'click',
        'wrapper' => "calendar-layout-form",
      ],
    ];

    $form["actionsValidateForm"]["submitForm"] = [
      '#type' => 'button',
      '#value' => 'Validate',
      '#name' => "validate_form",
      '#ajax' => [
        'callback' => "::calendarValidateFormCallback",
        'event' => 'click',
        'wrapper' => "calendar-layout-form",
      ],
    ];

//    var_dump($form["calendar-1"]); die();

    return $form;
  }

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
        $form_state->set("calendar{$t}CountRows", $calendarRows);
      }

      $rows = $calendarRows;

      $this->calendarAddRow($form, $form_state, $t, $rows);

      if ($form_state->getTriggeringElement()['#name'] == "add_row_{$t}") {
        $rows = $form_state->get("calendar{$t}CountRows");
        $rows++;
        $form_state->set("calendar{$t}CountRows", $rows);

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

  public function calendarAddRow(array &$form, FormStateInterface $form_state, $currentTable, $rows) {
    for ($r = 1; $r <= $rows; $r++) {
      $yearValue = $this->year - ($r - 1);

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
            'disable-refocus' => TRUE
          ],
        ];

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
        '#type' => 'textfield',
        '#prefix' => "<div id='calendar-{$currentTable}-row-{$r}-field-YTD'>",
        '#suffix' => "</div>",
        '#calendar-table' => $currentTable,
        '#calendar-row' => $r,
        '#calendar-field' => 'YTD',
        '#calendar-quarter' => 'YTD',
      ];
    }
  }

  public function calendarUpdateQuarters(array &$form, FormStateInterface $form_state, $table, $row, $currentField, $quarter) {
    for ($f = 1; $f <= $this->tableCountMonthsTitles; $f++) {
      $field = $this->tableFieldTitles[$f];

      $sumMonths = $form_state->get("sumForQuarter{$quarter}");
      if (empty($sumMonths)) {
        $sumMonths = 0;
        $form_state->set("sumForQuarter{$quarter}", $sumMonths);
      }

      $currentQuarterField = $form["calendar-{$table}"][$row][$field]['#calendar-quarter'];
      $currentValue = $form["calendar-{$table}"][$row][$field]['#value'];

      if (empty($currentValue)) {
        $currentValue = 0;
      }

      if (($f % 4 != 0) && ($currentQuarterField == $quarter)) {
        $sumMonths += $currentValue;
        $form_state->set("sumForQuarter{$quarter}", $sumMonths);
      }

      if (($f % 4 == 0) && ($currentQuarterField != $quarter)) {
        $sumQuarters += $currentValue;
      }
    }

    if ($sumMonths != 0) {
      $sumMonths++;
      $sumMonths = $sumMonths / 3;
    }

    $sumQuarters += $sumMonths;

    $sumQuarters++;
    $sumQuarters = $sumQuarters / 4;

    $form["calendar-{$table}"][$row][$quarter]["#value"] = round($sumMonths, 2);
    $form["calendar-{$table}"][$row]["YTD"]["#value"] = round($sumQuarters, 2);
  }

  public function calendarValidate(array &$form, FormStateInterface $form_state, $tables) {
    $firstValidField = 0;
    $lastValidField = 0;
    $firstValidRow = 0;
    $lastValidRow = 0;

    for ($t = 1; $t <= $tables; $t++) {
      $rows = $form_state->get("calendar{$t}CountRows");

      for ($r = 1; $r <= $rows; $r++) {
        for ($f = 1; $f <= $this->tableCountMonthsTitles; $f++) {
          $field = $this->tableFieldTitles[$f];
          $fieldValue = $form["calendar-{$tables}"][$r][$field]['#value'];

          if ($f % 4 == 0) {
            continue;
          }

          if ($fieldValue != 0 && $firstValidField == 0) {
            $firstValidRow = $r;
            $firstValidField = $f;
          } elseif ($fieldValue != 0) {
            $lastValidRow = $r;
            $lastValidField = $f;
          }

          if ($f == $this->tableCountMonthsTitles-1) {
            $message = $this->calendarValidateCheckGap($form, $form_state, $t, $firstValidRow, $firstValidField, $lastValidRow, $lastValidField);
          }
        }
      }
    }

    $validMessages = [
      'firstValidField' => $firstValidField,
      'lastValidField' => $lastValidField,
      'conclusion' => $message,
      'firstValidRow' => $firstValidRow,
      'lastValidRow' => $lastValidRow
    ];

    return $validMessages;
  }

  public function calendarValidateCheckGap(array &$form, FormStateInterface $form_state, $table, $firstValidRow, $firstValidField, $lastValidRow, $lastValidField) {

    if ($lastValidField == 0 && $lastValidRow != 0) {
      $message = 'Valid';
    } else {
      for ($r = $firstValidRow; $r <= $lastValidRow; $r++) {
        if ($lastValidRow != 1 && $firstValidRow != $r && $lastValidRow != $r) {
          $firstField = 1;
          $lastField = $this->tableCountMonthsTitles;
        }

        if ($firstValidRow != $r && $lastValidRow == $r) {
          $firstField = 1;
          $lastField = $lastValidField;
        }

        if ($firstValidRow == $r && $lastValidRow != $r) {
          $firstField = $firstValidField;
          $lastField = $this->tableCountMonthsTitles;
        }

        for ($f = $firstField; $f <= $lastField; $f++) {
          $field = $this->tableFieldTitles[$f];
          $fieldValue = $form["calendar-{$table}"][$r][$field]['#value'];

          if ($fieldValue != 0) {
            $message = 'Valid';
          } elseif ($fieldValue == 0) {
            $message = 'Invalid';
            break;
          }
        }
      }
    }

    return $message;
  }

  public function calendarAddTableCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  public function calendarAddRowCallback(array &$form, FormStateInterface $form_state) {
    $table = $form_state->get('currentCalendar');

    return $form["calendar-{$table}"];
  }

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

  public function calendarValidateFormCallback(array &$form, FormStateInterface $form_state) {
    $tables = $form_state->get('currentCalendar');

    $response = new AjaxResponse();

    $message = $this->calendarValidate($form, $form_state, $tables);

    $response->addCommand(new ReplaceCommand("#calendar-messages", "First valid row: {$message['firstValidRow']}<br>First valid field: {$message['firstValidField']}<br>Last valid row: {$message['lastValidRow']}<br>Last valid field: {$message['lastValidField']}<br>Conclusion: {$message['conclusion']}"));

    return $response;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
