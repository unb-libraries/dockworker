<?php

namespace UnbLibraries\DockWorker\Robo;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Finder\Finder;
use UnbLibraries\DockWorker\Robo\DrupalCustomEntityCommand;


/**
 * Defines commands in the DrupalGenerateContentEntityFieldCommand namespace.
 */
class DrupalGenerateContentEntityFieldCommand extends DrupalCustomEntityCommand {

  /**
   * The templates available.
   *
   * @var array
   */
  protected $drupalEntityTemplates = [];

  /**
   * The chosen template.
   *
   * @var string
   */
  protected $drupalEntityChosenTemplate = NULL;

  /**
   * The tokens to replace from the templates.
   *
   * @var string[]
   */
  protected $drupalEntityTemplateTokens = [];

  const ENTITY_TEMPLATE_PATH = '/vendor/unb-libraries/dockworker/data/templates/entity_fields';
  const ENTITY_TEMPLATE_FILES = [
    'field.txt',
    'interface.txt',
    'methods.txt',
  ];

  /**
   * Generate the boilerplate necessary to add a field to an entity.
   *
   * @command drupal:generate:entity-field
   */
  public function generateContentEntityField() {
    if (!empty($this->drupalCustomEntities)) {
      $this->setTemplates();
      $this->setChosenTemplate();
      $this->getTokenizedTemplateOutputs();
    }
    else {
      $this->say('No modules containing custom entities found.');
    }
  }

  /**
   * Set the chosen template.
   */
  private function setChosenTemplate() {
    $this->listTemplates();
    $value_chosen = FALSE;
    while ($value_chosen == FALSE) {
      $template = $this->ask('Enter the template name to use');
      if (!empty($template)) {
        if (!empty(($this->drupalEntityTemplates[$template]))) {
          $this->drupalEntityChosenTemplate = $template;
          $value_chosen == TRUE;
          break;
        }
        $this->say('Invalid template name.');
      }
      else {
        $this->say('No template entered.');
      }
    }
  }

  /**
   * Output a formatted list of templates available.
   */
  protected function listTemplates() {
    $wrapped_rows = array_map(
      function ($template_id, $description) {
        return [
          $template_id,
          $description,
        ];
      },
      array_keys($this->drupalEntityTemplates), $this->drupalEntityTemplates
    );
    $table = new Table($this->output());
    $table->setHeaders(['Template Name', 'Description'])
      ->setRows($wrapped_rows);
    $table->setStyle('borderless');
    $table->render();
  }

  /**
   * Set the templates available.
   *
   * @hook post-init
   */
  private function setTemplates() {
    $templates = [];
    $all_templates = new Finder();
    $all_templates->files()->in($this->repoRoot . self::ENTITY_TEMPLATE_PATH)->directories();

    foreach ($all_templates as $template) {
      $this->drupalEntityTemplates[$template->getBasename()] = $this->getTemplateDescription($template->getBasename());
    }
  }

  /**
   * Get a template's description.
   *
   * @param string $template
   *   The template to use
   */
  private function getTemplateDescription($template) {
    $template_description_path = $this->getTemplatePath($template) . '/description.txt';
    var_dump($template_description_path);
    $template_description = NULL;
    if (file_exists($template_description_path)) {
      $template_description = trim(file_get_contents($template_description_path));
    }
    if (empty($template_description)) {
      $template_description = 'No description found.';
    }
    return $template_description;
  }

  private function getTemplatePath($template) {
    return $this->repoRoot . '/' . self::ENTITY_TEMPLATE_PATH . "/$template";
  }

  /**
   * Get the output from all files in this template.
   *
   * @param \PhpParser\Parser $parser
   *   The entity file currently being parsed.
   */
  private function getTokenizedTemplateOutputs() {
    $this->setEntityTemplateTokens();
    foreach (self::ENTITY_TEMPLATE_FILES as $template_file) {
      $this->getOutputTemplateFile($template_file);
    }
  }

  /**
   * Output the tokenized version of a template file.
   *
   * @param string $template_file
   *   The template file to output.
   */
  private function getOutputTemplateFile($template_file) {
    $template_path = $this->getTemplatePath($this->drupalEntityChosenTemplate);
    $file_name = $template_path . "/$template_file";
    $this->io->newLine();
    $this->say($template_file);
    $multiple_file_name = str_replace('.txt', '-multiple.txt', $file_name);
    if (file_exists($file_name)) {
      $multiple_file_name = str_replace('.txt', '-multiple.txt', $file_name);
      if (
        $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_CARDINALITY'] == 'BaseFieldDefinition::CARDINALITY_UNLIMITED' &&
        file_exists($multiple_file_name)
        ) {
        $file_name = $multiple_file_name;
      }
      $contents = file_get_contents($file_name);
      foreach ($this->drupalEntityTemplateTokens as $token => $output_value) {
        $contents = str_replace($token, $output_value, $contents);
      }
      $this->io->text($contents);
    }
  }

  /**
   * Set the tokens necessary for generating the templates.
   *
   * @param string $template
   *   The template to use
   */
  private function setEntityTemplateTokens() {
    $chosen_template = $this->drupalEntityChosenTemplate;
    if (
      $chosen_template == 'string' ||
      $chosen_template == 'text' ||
      $chosen_template == 'string_long' ||
      $chosen_template == 'text_long'
    ) {
      $this->setTextTypeFieldTemplateTokens();
    }
    if ($chosen_template == 'string' || $chosen_template == 'text') {
      $this->setShortTextTypeFieldTemplateTokens();
    }
    if ($chosen_template == 'string_long' || $chosen_template == 'text_long') {
      $this->setLongFieldTemplateTokens();
    }
    if ($chosen_template == 'taxonomy_reference_select' || $chosen_template == 'taxonomy_reference_autocomplete') {
      $this->setTaxonomyTermTemplateTokens();
    }
    if ($chosen_template == 'taxonomy_reference_autocomplete') {
      $this->setTaxonomyTermAutocompleteTemplateTokens();
    }
    $this->setStandardEntityTemplateTokens();
  }

  /**
   * Set the tokens necessary for all templates.
   */
  private function setStandardEntityTemplateTokens() {
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_MACHINE_NAME'] =
      $this->askDefault('Enter the field\'s machine_name/key: ', 'user_name');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_REQUIRED'] =
      $this->confirm('Is this field required?') ? 'TRUE' : 'FALSE';

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_REVISIONABLE'] =
      $this->confirm('Is this field revisionable?') ? 'TRUE' : 'FALSE';

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_TRANSLATABLE'] =
      $this->confirm('Is this field translatable?') ? 'TRUE' : 'FALSE';

    $cardinality = $this->askDefault('Enter the field\'s cardinality (0 for unlimited):', '1');
    $cardinality = $cardinality == 0 ? 'BaseFieldDefinition::CARDINALITY_UNLIMITED' : $cardinality;
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_CARDINALITY'] = $cardinality;

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_LABEL'] =
      $this->askDefault('Enter the field\'s form label:', 'User Name');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_WEIGHT'] =
      $this->askDefault('Enter the field\'s weight:', '0');

    $field_class_guess = preg_replace(
      "/[^A-Za-z0-9]/",
      '',
      ucwords(
        strtolower($this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_LABEL'])
      )
    );
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_CAPSCASE'] =
      $this->askDefault('Enter the pseudo-ClassName to use for the field:', $field_class_guess);

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_DESCRIPTION'] =
      $this->askDefault('Enter the description (form call to action) for the field:', 'Enter the user name');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_ENTITY_TYPE'] =
      $this->askDefault('Enter the parent entity label:', 'Bibliographic Record');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_NAMEDSPACED_CLASS'] =
      $this->askDefault('Enter the parent entity\'s fully namespaced class:', '\Drupal\yabrm\Entity\BibliographicRecord');
  }

  /**
   * Set the tokens necessary for the string_long and text_long templates.
   */
  private function setTextTypeFieldTemplateTokens() {
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_DEFAULT_VALUE'] =
      $this->askDefault('Enter the field\'s default value (empty for no default):', '');
  }

  /**
   * Set the tokens necessary for the string_long and text_long templates.
   */
  private function setShortTextTypeFieldTemplateTokens() {
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_MAX_LENGTH'] =
      $this->askDefault('Enter the field\'s maximum length:', 512);
  }

  /**
   * Set the tokens necessary for the string_long and text_long templates.
   */
  private function setLongFieldTemplateTokens() {
    $this->drupalEntityTemplateTokens['DOCKWORKER_LONG_TEXT_FIELD_ROWS'] =
      $this->askDefault('Enter the number of input rows to display on forms:', 4);
  }

  /**
   * Set the tokens necessary for taxonomy term reference templates.
   */
  private function setTaxonomyTermTemplateTokens() {
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_TAXONOMY_VID'] =
      $this->askDefault('Enter the target taxonomy VID:', '');
  }

  /**
   * Set the tokens necessary for taxonomy term reference templates.
   */
  private function setTaxonomyTermAutocompleteTemplateTokens() {
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_TAXONOMY_AUTO_CREATE'] =
      $this->confirm('Should new terms entered in this field be auto-created?') ? 'TRUE' : 'FALSE';

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_TAXONOMY_AUTOCOMPLETE_SIZE'] =
      $this->askDefault('Enter the field\'s autocomplete widget width:', 60);
  }

}
