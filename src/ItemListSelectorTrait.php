<?php

namespace Dockworker;

use Dockworker\ConsoleTableTrait;
use Robo\Symfony\ConsoleIO;

/**
 * Provides methods to assist a user in selecting a value from a list of items.
 */
trait ItemListSelectorTrait {

  use ConsoleTableTrait;

  /**
   * The items to select from.
   *
   * @var array
   */
  protected array $itemListSelectorItems;

  /**
   * The IO to use for output.
   *
   * @var \Robo\Symfony\ConsoleIO
   */
  protected ConsoleIO $itemListSelectorIo;

  /**
   * The value of the array key corresponding to the value to return.
   *
   * @var string
   */
  protected string $itemListSelectorValueKey;

  /**
   * The message to display prompting the user to choose an item.
   *
   * @var string
   */
  protected string $itemListSelectorMessage;

  /**
   * The title to display prior to printing the item list.
   *
   * @var string
   */
  protected string $itemListSelectorTableTitle;

  /**
   * The headers to use when generating the list table.
   *
   * @var array
   */
  protected array $itemListSelectorTableHeaders;


  /**
   * Prompts a user to select a value from a provided list of items.
   *
   * The list can contain supporting information to display and aid the user in
   * selecting the proper item, with only the desired value being returned from
   * the function.
   *
   * @param \Robo\Symfony\ConsoleIO $io
   *   The IO to use when generating output.
   * @param array $items
   *   An array of arrays, with each item being a string.
   * @param string $value_key
   *   The array key corresponding to the value to be returned.
   * @param string $table_title
   *   The title to display prior to printing the item list for selection.
   * @param string $message
   *   The message to display prompting the user to choose an item.
   * @param array $headers
   *   The headers to use when generating the list table.
   *
   * @return string
   *   The selected value. Empty string if selection is aborted.
   */
  protected function selectValueFromTable(
    ConsoleIO $io,
    array $items,
    string $value_key,
    string $table_title,
    string $message,
    array $headers
  ) : string {
    $this->itemListSelectorIo = $io;
    $this->itemListSelectorItems = $items;
    $this->itemListSelectorValueKey = $value_key;
    $this->itemListSelectorTableTitle = $table_title;
    $this->itemListSelectorMessage = $message;
    $this->itemListSelectorTableHeaders = $headers;

    if (empty($this->itemListSelectorItems)) {
      return '';
    }

    $this->appendInternalValuesToItemList();
    $this->setDisplayConsoleTable(
      $this->itemListSelectorIo,
      $this->itemListSelectorTableHeaders,
      $this->itemListSelectorItems,
      $this->itemListSelectorTableTitle
    );

    $selected_item = $this->getSelectedItemKeyFromList();

    if ($selected_item === '0' || !empty($selected_item)) {
      return $items[$selected_item][$this->itemListSelectorValueKey];
    }

    return '';
  }

  /**
   * Appends data to list items and headers towards displaying an ID for each.
   *
   * @return void
   */
  protected function appendInternalValuesToItemList() : void {
    array_unshift($this->itemListSelectorTableHeaders, 'ID');
    array_walk($this->itemListSelectorItems, [$this, 'addIdToListItems']);
  }

  /**
   * Prompts the user to select the desired item within the list.
   *
   * @return string
   *   The value of array key of the selected item.
   */
  protected function getSelectedItemKeyFromList() : string {
    $answer_key = -1;
    while (
      $answer_key < 0
    ) {
      $answer_string = $this->itemListSelectorIo->ask(
        $this->itemListSelectorMessage . ' (ENTER for none)'
      );

      if ($answer_string != "0" && empty($answer_string)) {
        return '';
      }

      $answer_key = $this->auditRawItemListAnswer($answer_string);
    }
    return (string) $answer_key;
  }

  /**
   * Audits the user's input for a valid array key, and returns the value if so.
   *
   * @param string $value
   *   The value of the user's input.
   *
   * @return int
   *   The key of the array value the user selected, -1 if input is invalid.
   */
  protected function auditRawItemListAnswer(string $value) : int {
    $number = filter_var($value, FILTER_VALIDATE_INT);
    if ($number === FALSE) {
      $this->informAuditRawItemListAnswerFailure($value);
      return -1;
    };
    if (
      !array_key_exists(
        $number - 1,
        $this->itemListSelectorItems
      )
    ) {
      $this->informAuditRawItemListAnswerFailure($value);
      return -1;
    }
    return (int) $value - 1;
  }

  /**
   * Prints a generic warning to the user the value entered was invalid.
   *
   * @param $value
   *   The value the user entered.
   *
   */
  protected function informAuditRawItemListAnswerFailure($value) : void {
    $this->itemListSelectorIo->note("Invalid ID value ($value)");
    $this->itemListSelectorIo->note('Enter the ID of the item you wish to select');
  }

  /**
   * Provides a callback for array_walk: append an ID to the list item array.
   *
   * @param $value
   *   The value to modify.
   * @param $key
   *   The key of the value in the parent arrary.
   *
   */
  protected function addIdToListItems(&$value, $key) : void {
    array_unshift($value, $key + 1);
  }

}
