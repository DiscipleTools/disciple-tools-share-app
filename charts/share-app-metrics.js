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

      mapboxgl.accessToken = window.wp_js_object.map_key;
      var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/light-v10',
        center: [-98, 38.88],
        minZoom: 0,
        zoom: 2
      });

      // disable map rotation using right click + drag
      map.dragRotate.disable();

      // disable map rotation using touch rotation gesture
      map.touchZoomRotate.disableRotation();

      map.on('load', function() {
        map.addSource('layer-source-closed', {
          type: 'geojson',
          data: data.closed,
          cluster: true,
          clusterMaxZoom: 5,
          clusterRadius: 50
        });
        map.addSource('layer-source-open', {
          type: 'geojson',
          data: data.open,
          cluster: true,
          clusterMaxZoom: 5,
          clusterRadius: 50
        });
        map.addSource('layer-source-followup', {
          type: 'geojson',
          data: data.followup,
          cluster: true,
          clusterMaxZoom: 5,
          clusterRadius: 50
        });

        // closed
        map.addLayer({
          id: 'closed',
          type: 'circle',
          source: 'layer-source-closed',
          filter: ['has', 'point_count'],
          paint: {
            'circle-color': [
              'step',
              ['get', 'point_count'],
              '#ff2600',
              20,
              '#ff2600',
              150,
              '#ff2600'
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
          id: 'cluster-count-closed',
          type: 'symbol',
          source: 'layer-source-closed',
          filter: ['has', 'point_count'],
          layout: {
            'text-field': '',
            'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
            'text-size': 12
          }
        });
        map.addLayer({
          id: 'unclustered-point-closed',
          type: 'circle',
          source: 'layer-source-closed',
          filter: ['!', ['has', 'point_count']],
          paint: {
            'circle-color': '#ff2600',
            'circle-radius':12,
            'circle-stroke-width': 1,
            'circle-stroke-color': '#fff'
          }
        });

        // open
        map.addLayer({
          id: 'open',
          type: 'circle',
          source: 'layer-source-open',
          filter: ['has', 'point_count'],
          paint: {
            'circle-color': [
              'step',
              ['get', 'point_count'],
              '#00d9ff',
              20,
              '#00d9ff',
              150,
              '#00d9ff'
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
          id: 'cluster-count-open',
          type: 'symbol',
          source: 'layer-source-open',
          filter: ['has', 'point_count'],
          layout: {
            'text-field': '',
            'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
            'text-size': 12
          }
        });
        map.addLayer({
          id: 'unclustered-point-open',
          type: 'circle',
          source: 'layer-source-open',
          filter: ['!', ['has', 'point_count']],
          paint: {
            'circle-color': '#00d9ff',
            'circle-radius':12,
            'circle-stroke-width': 1,
            'circle-stroke-color': '#fff'
          }
        });

        // followup
        map.addLayer({
          id: 'followup',
          type: 'circle',
          source: 'layer-source-followup',
          filter: ['has', 'point_count'],
          paint: {
            'circle-color': [
              'step',
              ['get', 'point_count'],
              '#00ff26',
              20,
              '#00ff26',
              150,
              '#00ff26'
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
          id: 'cluster-count-followup',
          type: 'symbol',
          source: 'layer-source-followup',
          filter: ['has', 'point_count'],
          layout: {
            'text-field': '',
            'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
            'text-size': 12
          }
        });
        map.addLayer({
          id: 'unclustered-point-followup',
          type: 'circle',
          source: 'layer-source-followup',
          filter: ['!', ['has', 'point_count']],
          paint: {
            'circle-color': '#00ff26',
            'circle-radius':12,
            'circle-stroke-width': 1,
            'circle-stroke-color': '#fff'
          }
        });

        spinner.removeClass('active')

        var bounds = new mapboxgl.LngLatBounds();
        data.closed.features.forEach(function(feature) {
          bounds.extend(feature.geometry.coordinates);
        });
        data.open.features.forEach(function(feature) {
          bounds.extend(feature.geometry.coordinates);
        });
        data.followup.features.forEach(function(feature) {
          bounds.extend(feature.geometry.coordinates);
        });
        map.fitBounds(bounds, { padding: {top: 20, bottom:20, left: 20, right: 20 } });
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
