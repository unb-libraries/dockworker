<?php

namespace Dockworker\Twig;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Provides methods to generate output via Twig.
 */
trait TwigTrait
{
  /**
   * Renders a file from a Twig template.
   *
   * @param string $template_name
   *   The file name of the template to render.
   * @param array $template_paths
   *   The paths to search for templates.
   * @param array $variables
   *   The variables to pass to the template.
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
    ): string {
        $loader = new FilesystemLoader($template_paths);
        $readme_twig = new Environment($loader);
        $template = $readme_twig->load($template_name);
        return $template->render($variables);
    }

    /**
     * Writes a file from a rendered Twig template.
     *
     * @param string $file_path
     *   The path to the file to write.
     * @param string $template_name
     *   The file name of the template to render.
     * @param array $template_paths
     *   The paths to search for templates.
     * @param array $variables
     *   The variables to pass to the template.
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function writeTwig(
        string $file_path,
        string $template_name,
        array $template_paths,
        array $variables
    ): void {
        file_put_contents(
            $file_path,
            $this->renderTwig($template_name, $template_paths, $variables)
        );
    }
}
