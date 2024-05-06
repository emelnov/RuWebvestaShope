<?php

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Implements hook_form_FORM_ID_alter().
 */

function ruwebvestashope_form_install_configure_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $form['site_information']['site_name']['#default_value'] = 'RuWebvestaShope';
  
}




function ruwebvestashope_install_tasks_alter(&$tasks, $install_state) {
  $tasks['install_configure_form']['display_name'] = 'RuWebvestaShope';
  $tasks['install_configure_form']['site_default_country'] = 'RU';
  $tasks['install_configure_form']['parameters']['langcode'] = 'ru';
  $tasks['install_select_locale']['parameters']['langcode'] = 'ru';
ruwebvestashope_copy_translations();
  

  
}



function ruwebvestashope_preprocess_page(&$variables) {
  if (\Drupal::service('router.admin_context')->isAdminRoute()) {
    $variables['site_name'] = 'WebVesta';
  }
}


function ruwebvestashope_install_tasks(&$install_state) {
  $tasks['ruwebvestashope_import_config'] = [
    'display_name' => 'Import demo content',
    'type' => 'batch',
    'run' => INSTALL_TASK_RUN_IF_REACHED,
    'function' => 'ruwebvestashope_import_config',
  ];
  return $tasks;
}


function ruwebvestashope_import_config() {
  \Drupal::service('module_installer')->install(['default_content_webvesta'], TRUE);
}	

/**
 * Copies translation files from the profile's translations folder to the public files directory.
 */
function ruwebvestashope_copy_translations() {
    $file_system = \Drupal::service('file_system');
    $symfony_fs = new Filesystem();

    // Define the source and destination directories
    $profile_path = \Drupal::service('extension.list.profile')->getPath('ruwebvestashope');
    $source_directory = $profile_path . '/translations/ru';
    $destination_directory = $file_system->realpath('public://translations');

    // Ensure the destination directory exists
    if (!$file_system->prepareDirectory($destination_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        drupal_set_message(t('Failed to prepare the destination directory for translations.'), 'error');
        return;
    }

    // Copy files from source to destination
    if (is_dir($source_directory) && $handle = opendir($source_directory)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $source_file = $source_directory . '/' . $entry;
                $destination_file = $destination_directory . '/' . $entry;
                $symfony_fs->copy($source_file, $destination_file, true);
            }
        }
        closedir($handle);
    } else {
        drupal_set_message(t('Source directory is not accessible or does not exist.'), 'error');
    }
}
