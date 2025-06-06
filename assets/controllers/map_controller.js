import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {

  tmpElement = null;

  async initialize() {
    console.log('MapController initialized');
    console.log(this.element);
    this.tmpElement = this.element;
    // this.component = await getComponent(this.element);
  }

  connect() {
    console.log('MapController connected');
    console.log(this.tmpElement);
    // this.element.addEventListener('ux:map:pre-connect', this._onPreConnect);
    // this.element.addEventListener('ux:map:connect', this._onConnect);
    this.element.addEventListener('ux:map:connect', this._onConnect.bind(this));
    // this.element.addEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
    // this.element.addEventListener('ux:map:marker:after-create', this._onMarkerAfterCreate);
    // this.element.addEventListener('ux:map:info-window:before-create', this._onInfoWindowBeforeCreate);
    // this.element.addEventListener('ux:map:info-window:after-create', this._onInfoWindowAfterCreate);
    // this.element.addEventListener('ux:map:polygon:before-create', this._onPolygonBeforeCreate);
    // this.element.addEventListener('ux:map:polygon:after-create', this._onPolygonAfterCreate);
    // this.element.addEventListener('ux:map:polyline:before-create', this._onPolylineBeforeCreate);
    // this.element.addEventListener('ux:map:polyline:after-create', this._onPolylineAfterCreate);
  }

  disconnect() {
    // You should always remove listeners when the controller is disconnected to avoid side effects
    this.element.removeEventListener('ux:map:pre-connect', this._onPreConnect);
    this.element.removeEventListener('ux:map:connect', this._onConnect);
    this.element.removeEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
    this.element.removeEventListener('ux:map:marker:after-create', this._onMarkerAfterCreate);
    this.element.removeEventListener('ux:map:info-window:before-create', this._onInfoWindowBeforeCreate);
    this.element.removeEventListener('ux:map:info-window:after-create', this._onInfoWindowAfterCreate);
    this.element.removeEventListener('ux:map:polygon:before-create', this._onPolygonBeforeCreate);
    this.element.removeEventListener('ux:map:polygon:after-create', this._onPolygonAfterCreate);
    this.element.removeEventListener('ux:map:polyline:before-create', this._onPolylineBeforeCreate);
    this.element.removeEventListener('ux:map:polyline:after-create', this._onPolylineAfterCreate);
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
  async _onConnect(event) {
    console.log(event);
    // console.log(event.detail.map);
    // console.log(event.detail.markers);
    // console.log(event.detail.infoWindows);
    // console.log(event.detail.polygons);
    // console.log(event.detail.polylines);
    console.log('ahaaaaaaa');
    console.log(this.tmpElement);
    console.log(this.component);
    // this.component = await getComponent(this.tmpElement);

    console.log(this.component);
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
    // The marker instance
    // console.log(event.detail.marker);
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
    // The info window instance
    // console.log(event.detail.infoWindow);

    // The associated element instance is also available, e.g. a marker...
    // console.log(event.detail.marker);
    // ... or a polygon
    // console.log(event.detail.polygon);
    // ... or a polyline
    // console.log(event.detail.polyline);
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
