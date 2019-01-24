<?php

namespace UnbLibraries\DockWorker\Robo;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;
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
      $this->getTemplates();
      foreach ($this->drupalCustomEntities as $real_path => $entity_parser) {
        $template = $this->io()->choice("What type of field?", $this->drupalEntityTemplates);
        $this->getOutputTemplates($template, $entity_parser);
      }
    }
    else {
      $this->say('No modules containing custom entities found.');
    }
  }

  /**
   * Set the templates available.
   *
   * @hook post-init
   */
  private function getTemplates() {
    $templates = [];
    $all_templates = new Finder();
    $all_templates->files()->in($this->repoRoot . self::ENTITY_TEMPLATE_PATH)->directories();

    foreach ($all_templates as $template) {
      $this->drupalEntityTemplates[] = $template->getBasename();
    }
  }

  /**
   * Get the output from all files in this template.
   *
   * @param string $template
   *   The template to use
   * @param \PhpParser\Parser $parser
   *   The entity file currently being parsed.
   */
  private function getOutputTemplates($template, $parser) {
    $this->setEntityTemplateTokens($template);
    $template_path = $this->repoRoot . '/' . self::ENTITY_TEMPLATE_PATH . "/$template";
    foreach (self::ENTITY_TEMPLATE_FILES as $template_file) {
      $this->getOutputTemplateFile($template_path, $template_file);
    }
  }

  /**
   * Output the tokenized version of a template file.
   *
   * @param string $template_path
   *   The path the template file exists within
   * @param string $template_file
   *   The template file to output.
   */
  private function getOutputTemplateFile($template_path, $template_file) {
    $file_name = $template_path . "/$template_file";
    $this->say($file_name);
    if (file_exists($file_name)) {
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
  private function setEntityTemplateTokens($template) {
    $this->setStandardEntityTemplateTokens();
    if ($template == 'string') {
      $this->setStringEntityTemplateTokens();
    }
  }

  /**
   * Set the tokens necessary for all templates.
   */
  private function setStandardEntityTemplateTokens() {
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_MACHINE_NAME'] =
      $this->askDefault('Field Machine Name / Key', 'user_name');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_REQUIRED'] =
      $this->askDefault('Required Field', 'FALSE');

    $cardinality = $this->askDefault('Cardinality (0 for unlimited)', '1');
    $cardinality = $cardinality == 0 ? 'BaseFieldDefinition::CARDINALITY_UNLIMITED' : $cardinality;
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_CARDINALITY'] = $cardinality;

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_LABEL'] =
      $this->askDefault('Field Label', 'User Name');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_CAPSCASE'] =
      $this->askDefault('Field Label', 'UserName');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_DESCRIPTION'] =
      $this->askDefault('Field Description', 'Enter the user name');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_ENTITY_TYPE'] =
      $this->askDefault('Name of the Parent Entity', 'Bibliographic Record');

    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_NAMEDSPACED_CLASS'] =
      $this->askDefault('NameSpaced Parent Class Entity', '\Drupal\yabrm\Entity\BibliographicRecord');
  }

  /**
   * Set the tokens necessary for the string template.
   */
  private function setStringEntityTemplateTokens() {
    $this->drupalEntityTemplateTokens['DOCKWORKER_FIELD_MAX_LENGTH'] =
      $this->askDefault('Field Maximum Length', 512);
  }

}
