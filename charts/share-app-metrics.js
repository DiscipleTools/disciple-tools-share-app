(function() {
  "use strict";
  jQuery(document).ready(function() {

    // expand the current selected menu
    jQuery('#metrics-sidemenu').foundation('down', jQuery(`#${window.wp_js_object.base_slug}-menu`));

    window.load_map()

  })

  window.load_map = () => {
    let content = jQuery('#chart')
    content.empty().html(`
    <span id="share-app-spinner" class="loading-spinner"></span>
    <div id="custom-style"></div>
    <div id="map-wrapper">
        <div id='map'></div>
    </div>
  `)
    let spinner = $('.loading-spinner')

    // /* LOAD */

    /* set vertical size the form column*/
    jQuery('#custom-style').append(`
      <style>
          #wrapper {
              height: ${window.innerHeight}px !important;
          }
          #map-wrapper {
              height: ${window.innerHeight-100}px !important;
          }
          #map {
              height: ${window.innerHeight-100}px !important;
          }
      </style>`)

    console.log('here')
    window.get_geojson().then(function(data){
      console.log(data)
      mapboxgl.accessToken = window.wp_js_object.map_key;
      var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/light-v10',
        center: [-98, 38.88],
        minZoom: 0,
        zoom: 0
      });

      // disable map rotation using right click + drag
      map.dragRotate.disable();

      // disable map rotation using touch rotation gesture
      map.touchZoomRotate.disableRotation();

      map.on('load', function() {
        map.addSource('layer-source-contacts', {
          type: 'geojson',
          data: data,
          cluster: true,
          clusterMaxZoom: 20,
          clusterRadius: 50
        });
        map.addLayer({
          id: 'clusters',
          type: 'circle',
          source: 'layer-source-contacts',
          filter: ['has', 'point_count'],
          paint: {
            'circle-color': [
              'step',
              ['get', 'point_count'],
              '#00d9ff',
              20,
              '#00aeff',
              150,
              '#90C741'
            ],
            'circle-radius': [
              'step',
              ['get', 'point_count'],
              20,
              100,
              30,
              750,
              40
            ]
          }
        });
        map.addLayer({
          id: 'cluster-count-contacts',
          type: 'symbol',
          source: 'layer-source-contacts',
          filter: ['has', 'point_count'],
          layout: {
            'text-field': '{point_count_abbreviated}',
            'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
            'text-size': 12
          }
        });
        map.addLayer({
          id: 'unclustered-point-contacts',
          type: 'circle',
          source: 'layer-source-contacts',
          filter: ['!', ['has', 'point_count']],
          paint: {
            'circle-color': '#00d9ff',
            'circle-radius':12,
            'circle-stroke-width': 1,
            'circle-stroke-color': '#fff'
          }
        });

        spinner.removeClass('active')

        // SET BOUNDS
        window.map_bounds_token = 'share_bound_app'
        window.map_start = get_map_start( window.map_bounds_token )
        if ( window.map_start ) {
          map.fitBounds( window.map_start, {duration: 0});
        }
        map.on('zoomend', function() {
          set_map_start( window.map_bounds_token, map.getBounds() )
        })
        map.on('dragend', function() {
          set_map_start( window.map_bounds_token, map.getBounds() )
        })
        // end set bounds
      });

    })

  }

  window.get_geojson = () => {
    let localizedObject = window.wp_js_object // change this object to the one named in ui-menu-and-enqueue.php
    jQuery('#share-app-spinner').addClass("active")
    return jQuery.ajax({
      type: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: `${localizedObject.rest_endpoints_base}/geojson`,
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', localizedObject.nonce);
      },
    })
      .fail(function (err) {
        jQuery('#share-app-spinner').removeClass("active")
        button.empty().append("error. Something went wrong")
        console.log("error");
        console.log(err);
      })
  }

})();
