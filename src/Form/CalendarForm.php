<?php

namespace Drupal\calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

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
  public $addRows = 1;
  public $year = 0;

  public function getFormId() {
    return 'calendar-form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#id'] = 'calendar-layout-form';

    $currentYear = date('Y');

    $this->year = $currentYear;

    $tables = $this->addTables;

    $this->calendarAddTable($form, $form_state, $tables, $this->year);

    if ($form_state->getTriggeringElement()['#name'] == "add_table") {
      $tables = $this->addTables;
      $tables++;
      $this->addTables = $tables;

      $this->calendarAddTable($form, $form_state, $tables, $this->year);
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

    return $form;
  }

  public function calendarAddTable(array &$form, FormStateInterface $form_state, $tables = 1, $currentYear = 0) {
    for ($t = 1; $t <= $tables; $t++) {
      $form["calendar-{$t}"] = [
        '#type' => 'table',
        '#header' => $this->tableFieldTitles,
        '#prefix' => "<div id ='calendar-table-{$t}'>",
        '#suffix' => "</div>"
      ];

      $rows = $this->addRows;

      $this->calendarAddRow($form, $form_state, $t, $rows, $currentYear);

      if ($form_state->getTriggeringElement()['#name'] == "add_row_{$t}") {
        $rows = $this->addRows;
        $rows++;
        $this->addRows = $rows;

        $form_state->set("currentCalendar", $t);

        $this->calendarAddRow($form, $form_state, $t, $rows, $currentYear);
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

  public function calendarAddRow(array &$form, FormStateInterface $form_state, $currentTable = 1, $rows = 1, $currentYear = 0) {
    for ($r = 1; $r <= $this->addRows; $r++) {
      for ($f = 0; $f <= count($this->tableFieldTitles)-1; $f++) {
        $field = strtolower($this->tableFieldTitles[$f]);

        $disableInput = FALSE;
        $disableValue = '';
        if ($f == 0) {
          $disableInput = TRUE;
          $disableValue = $currentYear - ($r-1);
        }

        $form["calendar-{$currentTable}"][$r]["$field"] = [
          '#type' => 'textfield',
          '#disabled' => $disableInput,
          '#value' => $disableValue,
          '#prefix' => "<div id='calendar-{$currentTable}-row-{$r}-field-{$field}'>",
          '#suffix' => "</div>"
        ];
      }
    }
  }

  public function calendarAddTableCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  public function calendarAddRowCallback(array &$form, FormStateInterface $form_state) {
    $table = $form_state->get('currentCalendar');

    return $form["calendar-{$table}"];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
