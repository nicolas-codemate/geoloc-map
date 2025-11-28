// assets/controllers/mymap_controller.js

import {Controller} from '@hotwired/stimulus';

// Module-level state: tracks popups manually closed by user (click on X button)
// Persists across Live Component re-renders, cleared on page refresh
const userClosedPopups = new Set();

const getMarkersFromMap = (map) => {
  const markers = [];

  // Iterate over each layer on the map
  map.eachLayer(function (layer) {
    // Check if the layer is a marker
    if (layer instanceof L.Marker) {
      markers.push(layer);
    }
  });

  return markers;
}

const openPopupsWhenMapReady = (map, markers) => {
  // Wait for the map to finish all animations and rendering
  const openPopups = () => {
    // Use requestAnimationFrame to ensure the DOM is fully rendered
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        markers.forEach(marker => {
          if (marker.getPopup()) {
            marker.openPopup();
          }
        });
      });
    });
  };

  // Check if map is currently animating
  if (map._animatingZoom || map._panAnim) {
    // Wait for the map to finish moving/zooming
    map.once('moveend', openPopups);
  } else {
    // Map is already stable, open popups immediately
    openPopups();
  }
};

// Padding for map bounds: [top, right, bottom, left] or [vertical, horizontal]
// Extra top padding to ensure tooltips/popups above markers are visible
const MAP_BOUNDS_PADDING = [120, 80];

const centerMapOnMarkers = (map, L, newMarker, shouldOpenPopups = true) => {
  const markers = getMarkersFromMap(map);

  if (markers.length && map) {
    const bounds = L.latLngBounds();

    // Add all markers to bounds
    markers.forEach(marker => {
      bounds.extend(marker.getLatLng());
    });

    if (bounds.isValid()) {
      if (markers.length === 1) {

        const currentBounds = map.getBounds().pad(0.05);

        if (newMarker) {
          // Check if the new marker is already in the current view
          const newMarkerPosition = newMarker.getLatLng();
          const isMarkerInView = currentBounds.contains(newMarkerPosition);

          if (isMarkerInView) {
            // Marker is already visible, just open popups without moving map
            if (shouldOpenPopups) {
              openPopupsWhenMapReady(map, markers);
            }
            return;
          }
        }

        map.flyToBounds(bounds, {
          padding: MAP_BOUNDS_PADDING,
          duration: 0.5,
          maxZoom: map._zoom,
          animate: true,
        });
      } else {
        map.fitBounds(bounds, {
          padding: MAP_BOUNDS_PADDING,
          maxZoom: map._zoom,
          animate: true,
        });
      }

      // Open popups after map finishes moving (only on initial load)
      if (shouldOpenPopups) {
        openPopupsWhenMapReady(map, markers);
      }
    }
  }
}

export default class extends Controller {

  // define the properties you want to use
  map = null;
  L = null;
  initialLoadComplete = false;

  // Bound event handlers (arrow functions preserve 'this' context)
  _boundOnConnect = (event) => this._onConnect(event);
  _boundOnMarkerAfterCreate = (event) => this._onMarkerAfterCreate(event);
  _boundOnInfoWindowAfterCreate = (event) => this._onInfoWindowAfterCreate(event);

  connect() {
    // this.element.addEventListener('ux:map:pre-connect', this._onPreConnect);
    this.element.addEventListener('ux:map:connect', this._boundOnConnect);
    // this.element.addEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
    this.element.addEventListener('ux:map:marker:after-create', this._boundOnMarkerAfterCreate);
    // this.element.addEventListener('ux:map:info-window:before-create', this._onInfoWindowBeforeCreate);
    this.element.addEventListener('ux:map:info-window:after-create', this._boundOnInfoWindowAfterCreate);
    // this.element.addEventListener('ux:map:polygon:before-create', this._onPolygonBeforeCreate);
    // this.element.addEventListener('ux:map:polygon:after-create', this._onPolygonAfterCreate);
    // this.element.addEventListener('ux:map:polyline:before-create', this._onPolylineBeforeCreate);
    // this.element.addEventListener('ux:map:polyline:after-create', this._onPolylineAfterCreate);
  }

  disconnect() {
    // You should always remove listeners when the controller is disconnected to avoid side effects
    // this.element.removeEventListener('ux:map:pre-connect', this._onPreConnect);
    this.element.removeEventListener('ux:map:connect', this._boundOnConnect);
    // this.element.removeEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
    this.element.removeEventListener('ux:map:marker:after-create', this._boundOnMarkerAfterCreate);
    // this.element.removeEventListener('ux:map:info-window:before-create', this._onInfoWindowBeforeCreate);
    this.element.removeEventListener('ux:map:info-window:after-create', this._boundOnInfoWindowAfterCreate);
    // this.element.removeEventListener('ux:map:polygon:before-create', this._onPolygonBeforeCreate);
    // this.element.removeEventListener('ux:map:polygon:after-create', this._onPolygonAfterCreate);
    // this.element.removeEventListener('ux:map:polyline:before-create', this._onPolylineBeforeCreate);
    // this.element.removeEventListener('ux:map:polyline:after-create', this._onPolylineAfterCreate);
  }

  /**
   * This event is triggered when the map is not created yet
   * You can use this event to configure the map before it is created
   */
  _onPreConnect(event) {
    // console.log(event.detail.options);
  }

  /**
   * This event is triggered when the map and all its elements (markers, info windows, ...) are created.
   * The instances depend on the renderer you are using.
   */
  _onConnect(event) {
    this.map = event.detail.map;
    this.L = event.detail.L;

    centerMapOnMarkers(this.map, this.L, null, true);
    this.initialLoadComplete = true;
  }

  /**
   * This event is triggered before creating a marker.
   * You can use this event to fine-tune it before its creation.
   */
  _onMarkerBeforeCreate(event) {
    // console.log(event.detail.definition);

    // { title: 'Paris', position: { lat: 48.8566, lng: 2.3522 }, ... }

    // Example: uppercase the marker title
    // event.detail.definition.title = event.detail.definition.title.toUpperCase();
  }

  /**
   * This event is triggered after creating a marker.
   * You can access the created marker instance, which depends on the renderer you are using.
   */
  _onMarkerAfterCreate(event) {

    if (!this.map) {
      return;
    }

    const marker = event.detail.marker;
    const markerId = marker.options.title;

    // When user clicks on marker, they want to see the popup again
    marker.on('click', () => {
      userClosedPopups.delete(markerId);
    });

    // Attach close button listener when popup opens to track manual closure
    marker.on('popupopen', () => {
      const popup = marker.getPopup();
      if (popup && popup._closeButton) {
        popup._closeButton.onclick = () => {
          userClosedPopups.add(markerId);
        };
      }
    });

    // On refresh, only open popup if user hasn't manually closed it
    if (this.initialLoadComplete) {
      if (marker.getPopup() && !userClosedPopups.has(markerId)) {
        marker.openPopup();
      }
      return;
    }

    // On initial load, center map and open popups
    centerMapOnMarkers(this.map, this.L, marker, true);
  }

  /**
   * This event is triggered before creating an info window.
   * You can use this event to fine-tune the info window before its creation.
   */
  _onInfoWindowBeforeCreate(event) {
    // console.log(event.detail.definition);
    // { headerContent: 'Paris', content: 'The capital of France', ... }
  }

  /**
   * This event is triggered after creating an info window.
   * You can access the created info window instance, which depends on the renderer you are using.
   */
  _onInfoWindowAfterCreate(event) {
    const popup = event.detail.infoWindow;
    // Set options to prevent auto-closing
    if (popup) {
      popup.options.autoClose = false;
      popup.options.closeOnClick = false;
    }

  }

  /**
   * This event is triggered before creating a polygon.
   * You can use this event to fine-tune it before its creation.
   */
  _onPolygonBeforeCreate(event) {
    // console.log(event.detail.definition);
    // { title: 'My polygon', points: [ { lat: 48.8566, lng: 2.3522 }, { lat: 45.7640, lng: 4.8357 }, { lat: 43.2965, lng: 5.3698 }, ... ], ... }
  }

  /**
   * This event is triggered after creating a polygon.
   * You can access the created polygon instance, which depends on the renderer you are using.
   */
  _onPolygonAfterCreate(event) {
    // The polygon instance
    // console.log(event.detail.polygon);
  }

  /**
   * This event is triggered before creating a polyline.
   * You can use this event to fine-tune it before its creation.
   */
  _onPolylineBeforeCreate(event) {
    // console.log(event.detail.definition);
    // { title: 'My polyline', points: [ { lat: 48.8566, lng: 2.3522 }, { lat: 45.7640, lng: 4.8357 }, { lat: 43.2965, lng: 5.3698 }, ... ], ... }
  }

  /**
   * This event is triggered after creating a polyline.
   * You can access the created polyline instance, which depends on the renderer you are using.
   */
  _onPolylineAfterCreate(event) {
    // The polyline instance
    // console.log(event.detail.polyline);
  }
}


