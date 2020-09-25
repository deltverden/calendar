<?php

namespace Drupal\calendar\Form;

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

  public function getFormId() {
    return 'calendar-form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#id'] = 'calendar-layout-form';

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

//    var_dump($form); die();

    return $form;
  }

  public function calendarAddTable(array &$form, FormStateInterface $form_state, $tables = 1) {
    for ($t = 1; $t <= $tables; $t++) {
      $form["calendar-{$t}"] = [
        '#type' => 'table',
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

  public function calendarAddRow(array &$form, FormStateInterface $form_state, $currentTable = 1, $rows = 1) {
    for ($r = 1; $r <= $rows; $r++) {
      $yearValue = $this->year - ($r - 1);

      $form["calendar-{$currentTable}"][$r]["year"] = [
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#default_value' => $yearValue,
        '#prefix' => "<div id='calendar-{$currentTable}-row-{$r}-field-year'>",
        '#suffix' => "</div>",
      ];

      for ($f = 1; $f <= count($this->tableFieldTitles) - 1; $f++) {
        $field = strtolower($this->tableFieldTitles[$f]);

        $form["calendar-{$currentTable}"][$r]["$field"] = [
          '#type' => 'textfield',
          '#prefix' => "<div id='calendar-{$currentTable}-row-{$r}-field-{$field}'>",
          '#suffix' => "</div>",
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
