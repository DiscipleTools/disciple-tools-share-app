var isMobile = false; //initiate as false
// device detection
if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
  || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) {
  isMobile = true;
}
jQuery(document).ready(function($){
  clearInterval(window.fiveMinuteTimer)
  if ( 'map' === jsObject.parts.action ) {
    window.load_map()
  } else {
    window.load_app()
  }
})

window.log = (state) => {
  if ( typeof window.app_location === 'undefined' ) {
    window.set_location()
  }
  let data = {
    location: window.app_location,
    state: state
  }
  return jQuery.ajax({
    type: "POST",
    data: JSON.stringify({ action: 'log', parts: jsObject.parts, data: data }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
    beforeSend: function (xhr) {
      xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
    }
  })
    .fail(function(e) {
      console.log(e)
    })
}

window.load_app = () => {
  let content = jQuery('#content')
  content.empty()
  window.write_form_screen()
}

window.write_form_screen = () => {
  let content = jQuery('#content')
  let height = window.innerHeight / 4

  content.empty().html(`
  <div class="grid-x grid-padding-y" style="padding-top: ${height}px;">
    <div class="cell center">
      <button class="button large actions" data-value="closed">Closed</button>
    </div>
    <div class="cell center">
      <button class="button large actions" data-value="open">Open</button>
    </div>
    <div class="cell center">
      <button class="button large actions" data-value="followup">Follow-Up</button>
    </div>
  </div>
  <div id="manual-map" style="display:none;">
      <br>
      <div id="map-wrapper">
          <div id='map'></div>
      </div>
    </div>
  <div id="custom-style">
    <style>
      #map-wrapper {
          height: ${window.innerHeight / 3}px !important;
      }
      #map {
          height: ${window.innerHeight / 3}px !important;
      }
      </style>
    </div>
  `)


  let action_buttons = jQuery('.actions')
  action_buttons.on('click', function(e){
    let v = jQuery(this).data('value')
    if ( 'followup' === v ) {
      write_follow_up()
    }
    else {
      jQuery('.actions').prop('disabled', true )
      jQuery('.loading-spinner').addClass('active')

      if ( typeof window.app_location === 'undefined' ) {
        console.log('not defined')
        const post_location = async () => {
          const result = await window.set_location()
          window.log( v ).done(function(data){
            console.log(data)
            jQuery('.actions').prop('disabled', false )
            jQuery('.loading-spinner').removeClass('active')
          })
        }
      }
      else {
        console.log('defined')
        window.log( v ).done(function(data){
          console.log(data)
          jQuery('.actions').prop('disabled', false )
          jQuery('.loading-spinner').removeClass('active')
        })
      }
    }
  })

  window.set_location()
}

window.set_location = () => {
  if ( isMobile && navigator.geolocation ) {
    navigator.geolocation.getCurrentPosition(location_success, location_error, {
      enableHighAccuracy: true,
      timeout: 3000,
      maximumAge: 0
    });
  }
  else if ( navigator.geolocation ) {
    navigator.geolocation.getCurrentPosition(location_success, location_error, {
      enableHighAccuracy: true,
      timeout: 3000,
      maximumAge: 0
    });
  }
  else {
    window.load_manual_map()
  }
}

window.location_success = (pos) => {
  var crd = pos.coords;
  window.app_location = { longitude: crd.longitude, latitude: crd.latitude, accuracy: crd.accuracy }

  console.log('Your current position is:');
  console.log(`Latitude : ${crd.latitude}`);
  console.log(`Longitude: ${crd.longitude}`);
  console.log(`More or less ${crd.accuracy} meters.`);
}

window.location_error = (err) => {
  window.load_manual_map()
  console.log(err);
}

window.load_manual_map = () => {

  jQuery('#manual-map').show()

  mapboxgl.accessToken = jsObject.map_key;
  var map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/light-v10',
    center: [-98, 38.88],
    minZoom: 0,
    zoom: 0
  });

  let userGeocode = new mapboxgl.GeolocateControl({
    positionOptions: {
      enableHighAccuracy: true
    },
    marker: {
      color: 'orange'
    },
    trackUserLocation: false,
    showUserLocation: false
  })
  map.addControl(userGeocode, 'top-left' );
  userGeocode.on('geolocate', function(e) { // respond to search
    console.log(e)
    if ( window.active_marker ) {
      window.active_marker.remove()
    }

    let lat = e.coords.latitude
    let lng = e.coords.longitude

    window.active_lnglat = [lng,lat]
    window.active_marker = new mapboxgl.Marker()
      .setLngLat([lng,lat])
      .addTo(map);

    window.app_location = { longitude: lng, latitude: lat, accuracy: 11 }

  })
  map.touchZoomRotate.disableRotation();
  map.dragRotate.disable();

  map.on('load', function() {
    jQuery(".mapboxgl-ctrl-geolocate").click();
  })
}

window.write_follow_up = () => {
  let content = jQuery('#content')

  content.empty().html(`
    <style>#email {display:none;}</style>
    <div class="grid-x" style="width:90%; margin: 0 auto;">
        <div class="cell">
            <label for="name">Name</label>
            <input type="text" id="name" class="required" placeholder="Name" />
            <span id="name-error" class="form-error">
                You're name is required.
            </span>
        </div>
        <div class="cell">
            <label for="tel">Phone</label>
            <input type="tel" id="phone" name="phone" class="required" placeholder="Phone" />
            <span id="phone-error" class="form-error">
                You're phone is required.
            </span>
        </div>
        <div class="cell">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" />
            <input type="email" id="e2" name="email" class="required" placeholder="Email" />
            <span id="email-error" class="form-error">
                You're email is required.
            </span>
        </div>
        <div class="cell">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" ></textarea>
            <span id="email-error" class="form-error">
                You're notes is required.
            </span>
        </div>
        <div class="cell center">
         <button class="button large" onclick="write_form_screen()">Cancel</button> <button class="button large" id="submit-log">Save</button>
        </div>
    </div>
    `)

 // let u_location = new mapboxgl.GeolocateControl({positionOptions: {enableHighAccuracy: true},trackUserLocation: true})

  let submit_button = jQuery('#submit-log')
  submit_button.on('click', function(){
    let spinner = jQuery('.loading-spinner')
    spinner.addClass('active')
    submit_button.prop('disabled', true)

    let honey = jQuery('#email').val()
    if ( honey ) {
      submit_button.html('Shame, shame, shame. We know your name ... ROBOT!').prop('disabled', true )
      spinner.removeClass('active')
      return;
    }

    let name_input = jQuery('#name')
    let name = name_input.val()
    if ( ! name ) {
      jQuery('#name-error').show()
      submit_button.removeClass('loading')
      name_input.focus(function(){
        jQuery('#name-error').hide()
      })
      submit_button.prop('disabled', false)
      spinner.removeClass('active')
      return;
    }

    let email_input = jQuery('#e2')
    let email = email_input.val()
    if ( ! email ) {
      jQuery('#email-error').show()
      submit_button.removeClass('loading')
      email_input.focus(function(){
        jQuery('#email-error').hide()
      })
      submit_button.prop('disabled', false)
      spinner.removeClass('active')
      return;
    }

    let phone_input = jQuery('#phone')
    let phone = phone_input.val()
    if ( ! phone ) {
      jQuery('#phone-error').show()
      submit_button.removeClass('loading')
      email_input.focus(function(){
        jQuery('#phone-error').hide()
      })
      submit_button.prop('disabled', false)
      spinner.removeClass('active')
      return;
    }

    let notes = jQuery('#notes').val()

    let form_data = {
      name: name,
      email: email,
      phone: phone,
      notes: notes
    }

    jQuery.ajax({
      type: "POST",
      data: JSON.stringify({ action: 'followup', parts: jsObject.parts, data: form_data }),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
      }
    })
      .done(function(response){
        jQuery('.loading-spinner').removeClass('active')
        window.write_form_screen()
        console.log(response)

      })
      .fail(function(e) {
        console.log(e)
        jQuery('#error').html(e)
      })
  })

}

window.load_map = () => {
  let content = jQuery('#content')
  content.empty().html(`
    <div id="map-wrapper">
        <div id='map'></div>
    </div>
  `)
  let spinner = jQuery('.loading-spinner')

  /* set vertical size the form column*/
  jQuery('#custom-style').append(`
      <style>
          #map-wrapper {
              height: ${window.innerHeight-100}px !important;
          }
          #map {
              height: ${window.innerHeight-100}px !important;
          }
      </style>`)


  window.get_geojson().then(function(data){

    mapboxgl.accessToken = jsObject.map_key;
    var map = new mapboxgl.Map({
      container: 'map',
      style: 'mapbox://styles/mapbox/light-v10',
      center: [-98, 38.88],
      minZoom: 0,
      zoom: 0
    });

    map.dragRotate.disable();
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

      var bounds = new mapboxgl.LngLatBounds();
      data.features.forEach(function(feature) {
        bounds.extend(feature.geometry.coordinates);
      });
      map.fitBounds(bounds, { padding: {top: 20, bottom:20, left: 20, right: 20 } });

    });
  })
}

window.get_geojson = () => {
  return jQuery.ajax({
    type: "POST",
    data: JSON.stringify({ action: 'geojson', parts: jsObject.parts }),
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
    beforeSend: function (xhr) {
      xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce )
    }
  })
    .fail(function(e) {
      console.log(e)
      jQuery('#error').html(e)
    })
}
