<?php

namespace Dockworker\Twig;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Provides methods to interact with a local filesystem.
 */
trait TwigTrait {

  /**
   * Renders a file from a Twig template.
   *
   * @param string $template_name
   * @param array $template_paths
   * @param array $variables
   *
   * @return string
   *   The rendered Twig template.
   *
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  protected function renderTwig(
    string $template_name,
    array $template_paths,
    array $variables
  ) {
    $loader = new FilesystemLoader($this->readMeTemplatePaths);
    $readme_twig = new Environment($loader);
    $this->readMeTemplate = $readme_twig->load($template_name);
    return $this->readMeTemplate->render($variables);
  }

  protected function writeTwig(
    string $file_path,
    string $template_name,
    array $template_paths,
    array $variables
  ) {
    file_put_contents(
      $file_path,
      $this->renderTwig($template_name, $template_paths, $variables)
    );
  }
}
