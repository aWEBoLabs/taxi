(function ($) {
  /**
   * Globals
   */
  var $key = 'AIzaSyAwWXJ2NY9dIov1dHNkfzYeQKRh_W_3BE8';
  var $apiUrl = 'https://maps.googleapis.com/maps/api';
  var $mapUrl = $apiUrl + '/js?key=' + $key + '&signed_in=true&libraries=drawing&callback=initMap';

  /**
   * Generates a Map.
   */
  Drupal.behaviors.zipCodeRateMap = {
    attach: function (context, settings) {
      $('#map', context).once('zipcoderate').each(function () {
        $.ajax({
           type: 'GET',
            url: $mapUrl,
            async: true,
            jsonpCallback: 'initMap',
            contentType: "application/json",
            dataType: 'jsonp',
            success: function(data) {
              initMap(settings.zipcoderate);
            },
            error: function(e) {
              console.log(e.message);
            }
        });
      });
    }
  };
  
  /**
   * Initializes the Map.
   */
  function initMap(data) {
    // Geocode
    $geoCodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json?components=country:MX|administrative_area:'+ data.state +'|locality:'+ data.city;
    $.post($geoCodingUrl, {}, function(data){
      // Set Map
      point = data.results[0].geometry.location;
      var map = new google.maps.Map(document.getElementById('map'), {
        center: {
          lat: parseFloat(point.lat), 
          lng: parseFloat(point.lng)
        },
        zoom: 16
      });
      
      var drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: google.maps.drawing.OverlayType.MARKER,
        drawingControl: true,
        drawingControlOptions: {
          position: google.maps.ControlPosition.TOP_CENTER,
          drawingModes: [
            google.maps.drawing.OverlayType.MARKER,
            google.maps.drawing.OverlayType.CIRCLE,
            google.maps.drawing.OverlayType.POLYGON,
            google.maps.drawing.OverlayType.POLYLINE,
            google.maps.drawing.OverlayType.RECTANGLE
          ]
        },
        circleOptions: {
          fillColor: '#ffff00',
          fillOpacity: 1,
          strokeWeight: 5,
          clickable: false,
          editable: true,
          zIndex: 1
        }
      });
      drawingManager.setMap(map);
    }, 'json');
  }

})(jQuery);
