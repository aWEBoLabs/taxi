<?php
/**
 * @file
 * Contains \Drupal\zipcode_import\Controller\ZipCodeRateController.
 */

namespace Drupal\zipcoderate\Controller;

use Drupal\Core\Url;

/**
 * Controller routines for book routes.
 */
class ZipCodeRateController {

  /**
   * Displays a welcome page for the .
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function adminOverview() {
    $build = array(
      '#type' => 'table', 
      '#header' => array(t('State'), t('City'), t('Rates'), t('Ready'), t('Left'), t('Operations')), 
      '#empty' => t('There are not Rates registered right now.'), 
      '#tableselect' => TRUE, 
    );
    $stats = $this->getRates();
    foreach($stats as $state => $cities) {
      foreach($cities as $city => $item) {
        $operations = '';
        // Link to Edit
        //$url = Url::fromRoute('zipcoderate.admin.state_city', ['state' => $state, 'city' => $city]);
        $url = "/admin/content/zipcoderate/{$state}/{$city}";
        //$operations = \Drupal::l(t('Edit'), $url);
        $operations .= "<a href=\"{$url}\">" . t('Edit') . "</a>";
        
        
        $id = "{$state}-{$city}";
        $build[$id]['state'] = array(
          '#plain_text' => $this->getStateLabel($state), 
        );
        $build[$id]['city'] = array(
          '#plain_text' => $city, 
        );
        $build[$id]['total'] = array(
          '#plain_text' => number_format($item['total']), 
        );
        $build[$id]['ready'] = array(
          '#plain_text' => number_format($item['ready']), 
        );
        $build[$id]['left'] = array(
          '#plain_text' => number_format($item['left']), 
        );
        $build[$id]['operations'] = array(
          '#markup' => $operations, 
        );
      }
    }
    return $build;
  }
  
  /**
   *
   */
  public function adminStateCity($state, $city) {
    $build = array(
      '#theme' => 'zipcoderate-rate_map', 
      '#state' => $state, 
      '#city' => $city, 
      '#attached' => array(
        'library' => array(
          "zipcoderate/google.maps", 
        ), 
        'drupalSettings' => array(
          'zipcoderate' => array(
            'state' => $state, 
            'city' => $city, 
          ), 
        ), 
      ), 
    );
        
    return $build;
  }
  
  /**
   * Gets a rate node located by zipcode start and zipcode end.
   */
  public function getRateNode($zipcode_start, $zipcode_end, $node_load = TRUE) {
    $query = db_select('node', 'n');
    $query->join('node__field_zipcode_start', 'zs', 'n.nid = zs.entity_id');
    $query->join('node__field_zipcode_end', 'ze', 'n.nid = ze.entity_id');
    $query->fields('n', array('nid'));
    $query->condition('zs.field_zipcode_start_value', $zipcode_start, '=');
    $query->condition('ze.field_zipcode_end_value', $zipcode_end, '=');
    if ( !($result = $query->execute()->fetchAssoc()) ) {
      return false;
    }
    
    if ( $node_load ) {
      return \Drupal\node\Entity\Node::load($result['nid']);
    }
    return true;
  }
  
  /**
   * Gets a list of all Rates.
   */
  private function getRates() {
    $rates = $this->getStateCityRatesStats();
    $rates_ready = $this->getStateCityRatesStats(true);
    foreach($rates as $state => $cities) {
      foreach($cities as $city => $count) {
        $ready = 0;
        $left = $count;
        if ( isset($rates_ready[$state][$city]) ) {
          $ready = $rates_ready[$state][$city];
          $left = $count - $ready;
        }
        $rates[$state][$city] = array(
          'total' => $count, 
          'ready' => $ready, 
          'left' => $left, 
        );
      }
    }
    return $rates;
  }
  
  /**
   * Gets a list of all State, City and Rates Stats.
   */
  private function getStateCityRatesStats($ready = false) {
    $query = db_select('node', 'n');
    if ( $ready ) {
      $query->join('node__field_rate_day', 'rd', 'n.nid = rd.entity_id');
      $query->join('node__field_rate_night', 'rn', 'n.nid = rn.entity_id');
    }
    $query->join('node__field_state', 'st', 'n.nid = st.entity_id');
    $query->join('node__field_city', 'ct', 'n.nid = ct.entity_id');
    $query->addField('st', 'field_state_value', 'state');
    $query->addField('ct', 'field_city_value', 'city');
    if ( $ready ) {
      $query->condition('rd.field_rate_day_value', 0, '>');
      $query->condition('rn.field_rate_night_value', 0, '>');
    }
    $query->groupBy('st.field_state_value');
    $query->groupBy('ct.field_city_value');
    $query->addExpression('COUNT(n.nid)', 'count');
    $query->countQuery();
    $result = $query->execute()->fetchAll();
    $rates = array();
    foreach($result as $row) {
      $rates[$row->state][$row->city] = $row->count;
    }
    return $rates;
  }
  
  /**
   * Get the Label State name.
   */
  private function getStateLabel($state_machine) {
    $states = array(
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
    );
    if ( isset($states[$state_machine]) ) {
      return $states[$state_machine];
    }
    return false;
  }
}
?>