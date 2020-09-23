<?php

namespace Drupal\calendar\Form;

use Drupal\Core\Form\FormBase;
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

  public function getFormId() {
    return 'addyear';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#id'] = 'calendar-layout-form';

    $form['actionMain']['addTable'] = [
      '#type' => 'button',
      '#value' => 'Add table',
      '#name' => 'add_table',
      '#ajax' => [
        'callback' => "::addTableCallback",
        'event' => 'click',
        'wrapper' => "calendar-layout-form",
      ],
    ];

    $addTable = $form_state->get('calendarAddTables');
    if (empty($addTable)) {
      $addTable = 0;
      $form_state->set('calendarAddTables', $addTable);
    }

    $this->addTable($form, $form_state, $addTable);

    if ($form_state->getTriggeringElement()['#name'] == "add_table") {
      $addTable = $form_state->get('calendarAddTables');
      $addTable++;
      $form_state->set('calendarAddTables', $addTable);
      $this->addTable($form, $form_state, $addTable);
    }

    return $form;
  }

  public function addTable(array &$form, FormStateInterface $form_state, $tables = 0) {
    for ($i = 0; $i <= $tables; $i++) {
      $form["calendar-{$i}"] = [
        '#type' => 'table',
        '#header' => $this->tableFieldTitles,
        '#prefix' => "<div id='calendar-table-{$i}'>",
        '#suffix' => "</div>"
      ];

      $preYear = date('Y');

      $addRow = $form_state->get('calendarAddRows');
      if (empty($addRow)) {
        $addRow = 0;
        $form_state->set('calendarAddRows', $addRow);
      }

      $this->addYear($form, $i, $addRow, $preYear);

      $updateTable = $form_state->get('calendarUpdateTableID');
      if (empty($updateTable)) {
        $updateTable = 0;
        $form_state->set('calendarUpdateTableID', $updateTable);
      }

      if ($form_state->getTriggeringElement()['#name'] == "update_table_{$i}") {
        $updateTable = $form_state->get('calendarUpdateTableID', $i);
        $updateTable++;
        $form_state->set('calendarUpdateTableID', $updateTable);
        $this->monthsSum($form, $i, $addRow);
      }

      if ($form_state->getTriggeringElement()['#name'] == "add_year_{$i}") {
        $addRow = $form_state->get('calendarAddRows');
        $addRow++;
        $form_state->set('calendarAddRows', $addRow);
        $this->addYear($form, $i, $addRow, $preYear);
      }

      $form["actionTable{$i}"]["updateTable{$i}"] = [
        '#type' => 'button',
        '#value' => 'Update',
        '#name' => "update_table_{$i}",
        '#ajax' => [
          'callback' => "::updateQuartersCallback",
          'event' => 'click',
          'wrapper' => "calendar-table-{$i}",
        ],
      ];

      $form["actionYear{$i}"]["addYear{$i}"] = [
        '#type' => 'button',
        '#value' => 'Add year',
        '#name' => "add_year_{$i}",
        '#ajax' => [
          'callback' => "::addYearCallback",
          'event' => 'click',
          'wrapper' => "calendar-layout-form",
        ],
      ];
    }
  }

  public function addYear(array &$form, $numOfTables = 0,$numOfRows = 0, $preYear = 0) {
    for ($t = 0; $t <= $numOfTables; $t++) {
      for ($i = 0; $i <= $numOfRows; $i++) {
        $form["calendar-{$t}"][$i]['year'] = [
          '#type' => 'textfield',
          '#title' => $this
            ->t('Year'),
          '#title_display' => 'invisible',
          '#prefix' => "<div id='calendar-{$t}-field-year-{$i}'>",
          '#suffix' => "</div>",
          '#value' => $preYear - $numOfRows,
          '#attributes' => [
            'readonly' => TRUE,
          ],
        ];

        for ($f = 1; $f < count($this->tableFieldTitles); $f++) {

          $field = strtolower($this->tableFieldTitles[$f]);

          $form["calendar-{$t}"][$i][$field] = [
            '#type' => 'number',
            '#title_display' => 'invisible',
            '#prefix' => "<div id='calendar-{$i}-field-$field'>",
            '#suffix' => "</div>",
          ];
        }
      }
    }
  }

  public function addTableCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  public function updateQuartersCallback(array &$form, FormStateInterface $form_state) {
    $table = $form_state->get('calendarUpdateTableID');

    return $form["calendar-{$table}"];
  }

  public function monthsSum(array &$form, $table = 0, $numRows = 0) {
    for ($row = 0; $row <= $numRows; $row++) {
      $quartals = 0;

      for ($i = 4; $i < count($this->tableFieldTitles); $i += 4) {
        $fieldQuarter = strtolower($this->tableFieldTitles[$i]);
        $fieldMonth = [
          strtolower($this->tableFieldTitles[$i - 3]),
          strtolower($this->tableFieldTitles[$i - 2]),
          strtolower($this->tableFieldTitles[$i - 1]),
        ];

        $sumMonths = $form["calendar-{$table}"][$row][$fieldMonth[0]]['#value'] +
          $form["calendar-{$table}"][$row][$fieldMonth[1]]['#value'] +
          $form["calendar-{$table}"][$row][$fieldMonth[2]]['#value'];

        if ($sumMonths != 0) {
          $sumMonths = ($sumMonths + 1) / 3;
        }

        $form["calendar-{$table}"][$row][$fieldQuarter]['#value'] = $sumMonths;
        $quartals += $sumMonths;
      }

      if ($quartals != 0) {
        $quartals = ($quartals + 1) / 4;
      }

      $form["calendar-{$table}"][$row]['ytd']['#value'] = $quartals;
    }

    return $form["calendar-{$table}"];
  }

  public function addYearCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
