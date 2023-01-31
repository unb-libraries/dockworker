<?php

namespace Dockworker;

use Dockworker\DockworkerException;
use Dockworker\TemporaryDirectoryTrait;
use Robo\Robo;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

/**
 * Provides methods to download data GitHub repositories.
 */
trait GitHubPackageDownloadTrait {

  /**
   * Download the contents of a github repository to a temporary folder.
   *
   * Portions sourced from https://enzo.weknowinc.com/articles/2015/07/09/how-to-download-remote-files-with-silexsymfony-and-drupal-8.
   *
   * @param string $owner
   *   The owner of the github repository.
   * @param string $repo
   *   The github repository name.
   * @param string $refspec
   *   The github repository refspec to download.
   * @param string $path
   *   The sub-path inside the repository to retrieve.
   *
   * @return string
   *   The path to the desired repository data.
   */
  public static function downloadGithubRepositoryContents($owner, $repo, $refspec = 'master', $path = '/') {
    $final_dir = '';
    $remote_zip_file_path = "https://github.com/$owner/$repo/archive/$refspec.zip";

    $tmp_dir = TemporaryDirectoryTrait::tempdir();
    $filename = "$repo.zip";
    $tmp_file = "$tmp_dir/$filename";

    file_put_contents(
      $tmp_file,
      file_get_contents($remote_zip_file_path)
    );

    $zip = new ZipArchive;
    $zip->open($tmp_file);
    $zip->extractTo($tmp_dir);
    $file_source = "$tmp_dir/$repo-$refspec$path";

    try {
      $fs = new Filesystem();
      $final_dir = tempnam(sys_get_temp_dir(), "ghpdlt");
      unlink($final_dir);
      $fs->rename($file_source, $final_dir);
    } catch (IOExceptionInterface) {
      print 'Error renaming folder';
    }
    return $final_dir;
  }

}
