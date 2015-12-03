<?php
/**
 * @file
 * Contains \Drupal\zipcoderate\Form\ImportForm.
 */

namespace Drupal\zipcoderate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements an Import form.
 */
class ZipCodeRateImportForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'zipcoderate_import_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $url = Url::fromUri('http://www.webcodigopostal.mx/');
    $url->setOptions(array('attributes'=>array('target'=>'_blank')));
    $link = \Drupal::l(t('http://www.webcodigopostal.mx/'), $url);
    $form['url'] = array(
      '#type' => 'url', 
      '#title' => t('URL'), 
      '#description' => t('Type in the URL of the state and city having all zip codes in !link', array(
        '!link' => $link, 
      )), 
      '#attributes' => array(
        'pattern' => '(http|ftp|https)://[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:/~+#-]*[\w@?^=%&amp;/~+#-])?'
      ), 
      '#required' => TRUE, 
      '#pattern' => 'ignored', 
    );
    $form['state'] = array(
      '#type' => 'select', 
      '#title' => t('State'), 
      '#options' => array(
        '' => t('- Select -'), 
        'aguascalientes' => 'Aguascalientes', 
        'baja-california' => 'Baja California', 
        'baja-california-sur' => 'Baja California Sur', 
        'campeche' => 'Campeche', 
        'chiapas' => 'Chiapas', 
        'chihuahua' => 'Chihuahua', 
        'coahuila' => 'Coahuila de Zaragoza', 
        'colima' => 'Colima', 
        'df' => 'Distrito Federal', 
        'durango' => 'Durango', 
        'guanajuato' => 'Guanajuato', 
        'guerrero' => 'Guerrero', 
        'hidalgo' => 'Hidalgo', 
        'jalisco' => 'Jalisco', 
        'mexico' => 'México', 
        'michoacan' => 'Michoacán de Ocampo', 
        'morelos' => 'Morelos', 
        'nayarit' => 'Nayarit', 
        'nuevo-leon' => 'Nuevo León', 
        'oaxaca' => 'Oaxaca', 
        'puebla' => 'Puebla', 
        'queretaro' => 'Querétaro', 
        'quintana-roo' => 'Quintana Roo', 
        'san-luis-potosi' => 'San Luis Potosí', 
        'sinaloa' => 'Sinaloa', 
        'sonora' => 'Sonora', 
        'tabasco' => 'Tabasco', 
        'tamaulipas' => 'Tamaulipas', 
        'tlaxcala' => 'Tlaxcala', 
        'veracruz' => 'Veracruz de Ignacio de la Llave', 
        'yucatan' => 'Yucatán', 
        'zacatecas' => 'Zacatecas', 
      ), 
      '#required' => TRUE, 
      '#description' => t('Select the State for the rates.'), 
    );
    $form['city'] = array(
      '#type' => 'textfield', 
      '#title' => t('City'), 
      '#required' => TRUE, 
    );
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  /*public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }
  */

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $html_raw = $this->curl_get($form_state->getValue('url'));
    $pattern = '/<div\s*?class="k-grid-content">.*?<tbody[^>]*>(.*?)<\/tbody>/msi';
    if ( !preg_match($pattern, $html_raw, $matches) ) {
      return;
    }
    
    $pattern = '/<a\s*href="\/codigo-postal-([^"]*)">/msi';
    if ( !preg_match_all($pattern, $matches[1], $zipcodes_raw) ) {
      return;
    }
    
    $zipcodes = array();
    foreach($zipcodes_raw[1] as $zipcode_raw) {
      $zipcodes[$zipcode_raw] = $zipcode_raw;
    }
    ksort($zipcodes);
    
    // Build Pairs
    $state = $form_state->getValue('state');
    $city = $form_state->getValue('city');
    $zipcodesb = $zipcodes;
    
    $zipcodes_count = 0;
    foreach($zipcodes as $zipcode_start) {
      foreach($zipcodesb as $zipcode_end) {
        // Validate we don't already have it.
        if ( \Drupal\zipcoderate\Controller\ZipCodeRateController::getRateNode(63175, 63175, false) ) {
          continue;
        }
        
        $zipcodes_count++;
        $node = \Drupal\node\Entity\Node::create([
          'title' => "{$city} {$state} - [{$zipcode_start} - {$zipcode_end}]", 
          'type' => 'rate', 
          'field_zipcode_start' => $zipcode_start, 
          'field_zipcode_end' => $zipcode_end, 
          'field_state' => $state, 
          'field_city' => $city, 
        ]);
        $node->save();
      }
    }
    
    drupal_set_message(t('@zipcodes_count ZipCodes were Imported', array('@zipcodes_count' => $zipcodes_count)), 'status');
  }
  
  /** 
   * Send a GET requst using cURL 
   * @param string $url to request 
   * @param array $get values to send 
   * @param array $options for cURL 
   * @return string 
   */ 
  private function curl_get($url, array $get = NULL, array $options = array()) {    
    $defaults = array( 
      CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get), 
      CURLOPT_HEADER => 0, 
      CURLOPT_RETURNTRANSFER => TRUE, 
      CURLOPT_TIMEOUT => 4 
    ); 
    
    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults)); 
    if( ! $result = curl_exec($ch)) { 
      trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 
  } 

}
?>