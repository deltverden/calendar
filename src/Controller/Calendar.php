<?php

namespace Drupal\calendar\Controller;

use Drupal\Core\Controller\ControllerBase;

class Calendar extends ControllerBase {
  public function get() {
    $calendar = \Drupal::formBuilder()->getForm(
      'Drupal\calendar\Form\CalendarForm'
    );

    return [
      '#theme' => 'calendar_theme',
      '#calendar' => $calendar
    ];
  }
}
