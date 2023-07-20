(function () {
  'use strict';

  function ownKeys(object, enumerableOnly) {
    var keys = Object.keys(object);

    if (Object.getOwnPropertySymbols) {
      var symbols = Object.getOwnPropertySymbols(object);
      enumerableOnly && (symbols = symbols.filter(function (sym) {
        return Object.getOwnPropertyDescriptor(object, sym).enumerable;
      })), keys.push.apply(keys, symbols);
    }

    return keys;
  }

  function _objectSpread2(target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = null != arguments[i] ? arguments[i] : {};
      i % 2 ? ownKeys(Object(source), !0).forEach(function (key) {
        _defineProperty(target, key, source[key]);
      }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }

    return target;
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  function _toConsumableArray(arr) {
    return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread();
  }

  function _arrayWithoutHoles(arr) {
    if (Array.isArray(arr)) return _arrayLikeToArray(arr);
  }

  function _iterableToArray(iter) {
    if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter);
  }

  function _unsupportedIterableToArray(o, minLen) {
    if (!o) return;
    if (typeof o === "string") return _arrayLikeToArray(o, minLen);
    var n = Object.prototype.toString.call(o).slice(8, -1);
    if (n === "Object" && o.constructor) n = o.constructor.name;
    if (n === "Map" || n === "Set") return Array.from(o);
    if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
  }

  function _arrayLikeToArray(arr, len) {
    if (len == null || len > arr.length) len = arr.length;

    for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

    return arr2;
  }

  function _nonIterableSpread() {
    throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }

  function _createForOfIteratorHelper(o, allowArrayLike) {
    var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];

    if (!it) {
      if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
        if (it) o = it;
        var i = 0;

        var F = function () {};

        return {
          s: F,
          n: function () {
            if (i >= o.length) return {
              done: true
            };
            return {
              done: false,
              value: o[i++]
            };
          },
          e: function (e) {
            throw e;
          },
          f: F
        };
      }

      throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
    }

    var normalCompletion = true,
        didErr = false,
        err;
    return {
      s: function () {
        it = it.call(o);
      },
      n: function () {
        var step = it.next();
        normalCompletion = step.done;
        return step;
      },
      e: function (e) {
        didErr = true;
        err = e;
      },
      f: function () {
        try {
          if (!normalCompletion && it.return != null) it.return();
        } finally {
          if (didErr) throw err;
        }
      }
    };
  }

  var _WINDOW = {};
  var _DOCUMENT = {};

  try {
    if (typeof window !== 'undefined') _WINDOW = window;
    if (typeof document !== 'undefined') _DOCUMENT = document;
  } catch (e) {}

  var _ref = _WINDOW.navigator || {},
      _ref$userAgent = _ref.userAgent,
      userAgent = _ref$userAgent === void 0 ? '' : _ref$userAgent;
  var WINDOW = _WINDOW;
  var DOCUMENT = _DOCUMENT;
  var IS_BROWSER = !!WINDOW.document;
  var IS_DOM = !!DOCUMENT.documentElement && !!DOCUMENT.head && typeof DOCUMENT.addEventListener === 'function' && typeof DOCUMENT.createElement === 'function';
  var IS_IE = ~userAgent.indexOf('MSIE') || ~userAgent.indexOf('Trident/');

  var _familyProxy, _familyProxy2, _familyProxy3, _familyProxy4, _familyProxy5;

  var NAMESPACE_IDENTIFIER = '___FONT_AWESOME___';
  var PRODUCTION = function () {
    try {
      return "production" === 'production';
    } catch (e) {
      return false;
    }
  }();
  var FAMILY_CLASSIC = 'classic';
  var FAMILY_SHARP = 'sharp';
  var FAMILIES = [FAMILY_CLASSIC, FAMILY_SHARP];

  function familyProxy(obj) {
    // Defaults to the classic family if family is not available
    return new Proxy(obj, {
      get: function get(target, prop) {
        return prop in target ? target[prop] : target[FAMILY_CLASSIC];
      }
    });
  }
  var PREFIX_TO_STYLE = familyProxy((_familyProxy = {}, _defineProperty(_familyProxy, FAMILY_CLASSIC, {
    'fa': 'solid',
    'fas': 'solid',
    'fa-solid': 'solid',
    'far': 'regular',
    'fa-regular': 'regular',
    'fal': 'light',
    'fa-light': 'light',
    'fat': 'thin',
    'fa-thin': 'thin',
    'fad': 'duotone',
    'fa-duotone': 'duotone',
    'fab': 'brands',
    'fa-brands': 'brands',
    'fak': 'kit',
    'fa-kit': 'kit'
  }), _defineProperty(_familyProxy, FAMILY_SHARP, {
    'fa': 'solid',
    'fass': 'solid',
    'fa-solid': 'solid',
    'fasr': 'regular',
    'fa-regular': 'regular',
    'fasl': 'light',
    'fa-light': 'light'
  }), _familyProxy));
  var STYLE_TO_PREFIX = familyProxy((_familyProxy2 = {}, _defineProperty(_familyProxy2, FAMILY_CLASSIC, {
    'solid': 'fas',
    'regular': 'far',
    'light': 'fal',
    'thin': 'fat',
    'duotone': 'fad',
    'brands': 'fab',
    'kit': 'fak'
  }), _defineProperty(_familyProxy2, FAMILY_SHARP, {
    'solid': 'fass',
    'regular': 'fasr',
    'light': 'fasl'
  }), _familyProxy2));
  var PREFIX_TO_LONG_STYLE = familyProxy((_familyProxy3 = {}, _defineProperty(_familyProxy3, FAMILY_CLASSIC, {
    'fab': 'fa-brands',
    'fad': 'fa-duotone',
    'fak': 'fa-kit',
    'fal': 'fa-light',
    'far': 'fa-regular',
    'fas': 'fa-solid',
    'fat': 'fa-thin'
  }), _defineProperty(_familyProxy3, FAMILY_SHARP, {
    'fass': 'fa-solid',
    'fasr': 'fa-regular',
    'fasl': 'fa-light'
  }), _familyProxy3));
  var LONG_STYLE_TO_PREFIX = familyProxy((_familyProxy4 = {}, _defineProperty(_familyProxy4, FAMILY_CLASSIC, {
    'fa-brands': 'fab',
    'fa-duotone': 'fad',
    'fa-kit': 'fak',
    'fa-light': 'fal',
    'fa-regular': 'far',
    'fa-solid': 'fas',
    'fa-thin': 'fat'
  }), _defineProperty(_familyProxy4, FAMILY_SHARP, {
    'fa-solid': 'fass',
    'fa-regular': 'fasr',
    'fa-light': 'fasl'
  }), _familyProxy4));
  var FONT_WEIGHT_TO_PREFIX = familyProxy((_familyProxy5 = {}, _defineProperty(_familyProxy5, FAMILY_CLASSIC, {
    '900': 'fas',
    '400': 'far',
    'normal': 'far',
    '300': 'fal',
    '100': 'fat'
  }), _defineProperty(_familyProxy5, FAMILY_SHARP, {
    '900': 'fass',
    '400': 'fasr',
    '300': 'fasl'
  }), _familyProxy5));
  var oneToTen = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
  var oneToTwenty = oneToTen.concat([11, 12, 13, 14, 15, 16, 17, 18, 19, 20]);
  var DUOTONE_CLASSES = {
    GROUP: 'duotone-group',
    SWAP_OPACITY: 'swap-opacity',
    PRIMARY: 'primary',
    SECONDARY: 'secondary'
  };
  var prefixes = new Set();
  Object.keys(STYLE_TO_PREFIX[FAMILY_CLASSIC]).map(prefixes.add.bind(prefixes));
  Object.keys(STYLE_TO_PREFIX[FAMILY_SHARP]).map(prefixes.add.bind(prefixes));
  var RESERVED_CLASSES = [].concat(FAMILIES, _toConsumableArray(prefixes), ['2xs', 'xs', 'sm', 'lg', 'xl', '2xl', 'beat', 'border', 'fade', 'beat-fade', 'bounce', 'flip-both', 'flip-horizontal', 'flip-vertical', 'flip', 'fw', 'inverse', 'layers-counter', 'layers-text', 'layers', 'li', 'pull-left', 'pull-right', 'pulse', 'rotate-180', 'rotate-270', 'rotate-90', 'rotate-by', 'shake', 'spin-pulse', 'spin-reverse', 'spin', 'stack-1x', 'stack-2x', 'stack', 'ul', DUOTONE_CLASSES.GROUP, DUOTONE_CLASSES.SWAP_OPACITY, DUOTONE_CLASSES.PRIMARY, DUOTONE_CLASSES.SECONDARY]).concat(oneToTen.map(function (n) {
    return "".concat(n, "x");
  })).concat(oneToTwenty.map(function (n) {
    return "w-".concat(n);
  }));

  function bunker(fn) {
    try {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      fn.apply(void 0, args);
    } catch (e) {
      if (!PRODUCTION) {
        throw e;
      }
    }
  }

  var w = WINDOW || {};
  if (!w[NAMESPACE_IDENTIFIER]) w[NAMESPACE_IDENTIFIER] = {};
  if (!w[NAMESPACE_IDENTIFIER].styles) w[NAMESPACE_IDENTIFIER].styles = {};
  if (!w[NAMESPACE_IDENTIFIER].hooks) w[NAMESPACE_IDENTIFIER].hooks = {};
  if (!w[NAMESPACE_IDENTIFIER].shims) w[NAMESPACE_IDENTIFIER].shims = [];
  var namespace = w[NAMESPACE_IDENTIFIER];

  function normalizeIcons(icons) {
    return Object.keys(icons).reduce(function (acc, iconName) {
      var icon = icons[iconName];
      var expanded = !!icon.icon;

      if (expanded) {
        acc[icon.iconName] = icon.icon;
      } else {
        acc[iconName] = icon;
      }

      return acc;
    }, {});
  }

  function defineIcons(prefix, icons) {
    var params = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    var _params$skipHooks = params.skipHooks,
        skipHooks = _params$skipHooks === void 0 ? false : _params$skipHooks;
    var normalized = normalizeIcons(icons);

    if (typeof namespace.hooks.addPack === 'function' && !skipHooks) {
      namespace.hooks.addPack(prefix, normalizeIcons(icons));
    } else {
      namespace.styles[prefix] = _objectSpread2(_objectSpread2({}, namespace.styles[prefix] || {}), normalized);
    }
    /**
     * Font Awesome 4 used the prefix of `fa` for all icons. With the introduction
     * of new styles we needed to differentiate between them. Prefix `fa` is now an alias
     * for `fas` so we'll ease the upgrade process for our users by automatically defining
     * this as well.
     */


    if (prefix === 'fas') {
      defineIcons('fa', icons);
    }
  }

  var icons = {
    
    "light-tag-circle-plus": [640,512,[],"e001","M88 144c0-13.3 10.8-24 24-24c13.3 0 24 10.7 24 24s-10.7 24-24 24c-13.3 0-24-10.7-24-24zM0 80C0 53.5 21.5 32 48 32c49.8 0 99.7 0 149.5 0c17 0 33.2 6.7 45.2 18.8c55 55 110 110 165 165c-9.4 5.5-18.3 11.8-26.5 18.8C327.5 180.8 273.8 127.1 220.1 73.4c-6-6-15-9.4-22.6-9.4C147.7 64 97.8 64 48 64c-8.8 0-16 7.2-16 16c0 49.8 0 99.7 0 149.5c0 7.6 3.4 16.6 9.4 22.6c-7.5 7.5-15.1 15.1-22.6 22.6C6.7 262.7 0 246.5 0 229.5C0 179.7 0 129.8 0 80zM285.3 450.7c-25 25-65.6 25-90.6 0C136 392 77.4 333.4 18.8 274.7c15.2-15.1 7.7-7.7 22.6-22.6c58.7 58.7 117.4 117.3 176 176c12.5 12.5 32.7 12.5 45.2 0c19.1-19.1 38.3-38.3 57.4-57.4c.2 13.8 2 27.2 5.2 40.1c-13.3 13.3-26.6 26.6-39.9 39.9zM496 288c8.8 0 16 7.2 16 16c0 16 0 32 0 48c16 0 32 0 48 0c8.8 0 16 7.2 16 16s-7.2 16-16 16c-16 0-32 0-48 0c0 16 0 32 0 48c0 8.8-7.2 16-16 16s-16-7.2-16-16c0-16 0-32 0-48c-16 0-32 0-48 0c-8.8 0-16-7.2-16-16s7.2-16 16-16c16 0 32 0 48 0c0-16 0-32 0-48c0-8.8 7.2-16 16-16zM352 368c0-79.5 64.5-144 144-144s144 64.5 144 144s-64.5 144-144 144s-144-64.5-144-144zM496 480c61.9 0 112-50.1 112-112s-50.1-112-112-112s-112 50.1-112 112s50.1 112 112 112z"],
    "sharp-regular-envelope-circle-exclamation": [640,512,[],"e003","M48 150.8l208 143c69.3-47.7 138.7-95.3 208-143c0-12.9 0-25.9 0-38.8c-138.7 0-277.3 0-416 0c0 12.9 0 25.9 0 38.8zM256 352L48 209c0 63.7 0 127.3 0 191c91.7 0 183.3 0 274.9 0c3.1 17 8.7 33.1 16.3 48c-97.1 0-194.2 0-291.2 0c-16 0-32 0-48 0c0-16 0-32 0-48c0-74.7 0-149.3 0-224c0-21.3 0-42.7 0-64C0 96 0 80 0 64c16 0 32 0 48 0c138.7 0 277.3 0 416 0c16 0 32 0 48 0c0 16 0 32 0 48c0 21.3 0 42.7 0 64c0 5.6 0 11.1 0 16.7c-5.3-.5-10.6-.7-16-.7c-72.4 0-134.5 43.7-161.6 106.1c-26.1 18-52.3 36-78.4 53.9zm384 16c0 79.5-64.5 144-144 144s-144-64.5-144-144s64.5-144 144-144s144 64.5 144 144zM480 416l0 32c10.7 0 21.3 0 32 0c0-10.7 0-21.3 0-32c-10.7 0-21.3 0-32 0zm32-144l-32 0c0 37.3 0 74.7 0 112c10.7 0 21.3 0 32 0c0-37.3 0-74.7 0-112z"],
    "solid-envelope-circle-exclamation": [640,512,[],"e002","M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4c72.5 54.4 145.1 108.8 217.6 163.2c11.4 8.5 27 8.5 38.4 0c38-28.5 75.9-56.9 113.9-85.4c1-.7 1.9-1.5 2.9-2.2c33.6-25.2 67.2-50.4 100.8-75.6c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48c-138.7 0-277.3 0-416 0zM0 176L0 384c0 35.3 28.7 64 64 64c91.8 0 183.5 0 275.2 0c-12.3-24-19.2-51.2-19.2-80c0-19 3-37.3 8.6-54.4c-11.4 8.5-22.8 17.1-34.2 25.6c-22.8 17.1-54 17.1-76.8 0C145.1 284.8 72.5 230.4 0 176zm512 0l-21.4 16.1c1.8-.1 3.6-.1 5.4-.1c5.4 0 10.7 .2 16 .7c0-5.6 0-11.1 0-16.7zM640 368c0 79.5-64.5 144-144 144s-144-64.5-144-144s64.5-144 144-144s144 64.5 144 144zM496 416c-13.3 0-24 10.7-24 24s10.7 24 24 24s24-10.7 24-24s-10.7-24-24-24zm16-128c0-8.8-7.2-16-16-16s-16 7.2-16 16c0 26.7 0 53.3 0 80c0 8.8 7.2 16 16 16s16-7.2 16-16c0-26.7 0-53.3 0-80z"],
    "solid-envelope-clock": [640,512,[],"e004","M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4c72.5 54.4 145.1 108.8 217.6 163.2c11.4 8.5 27 8.5 38.4 0c38-28.5 75.9-56.9 113.9-85.4c1-.7 1.9-1.5 2.9-2.2c33.6-25.2 67.2-50.4 100.8-75.6c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48c-138.7 0-277.3 0-416 0zM0 176L0 384c0 35.3 28.7 64 64 64c91.8 0 183.5 0 275.2 0c-12.3-24-19.2-51.2-19.2-80c0-19 3-37.3 8.6-54.4c-11.4 8.5-22.8 17.1-34.2 25.6c-22.8 17.1-54 17.1-76.8 0C145.1 284.8 72.5 230.4 0 176zm512 0l-21.4 16.1c1.8-.1 3.6-.1 5.4-.1c5.4 0 10.7 .2 16 .7c0-5.6 0-11.1 0-16.7zM352 368c0-79.5 64.5-144 144-144s144 64.5 144 144s-64.5 144-144 144s-144-64.5-144-144zm160-64c0-8.8-7.2-16-16-16s-16 7.2-16 16c0 21.3 0 42.7 0 64c0 8.8 7.2 16 16 16c16 0 32 0 48 0c8.8 0 16-7.2 16-16s-7.2-16-16-16c-10.7 0-21.3 0-32 0c0-16 0-32 0-48z"],
    "solid-tag-circle-plus": [640,512,[],"e000","M0 80L0 229.5c0 17 6.7 33.3 18.7 45.3c58.7 58.7 117.3 117.3 176 176c25 25 65.5 25 90.5 0c13.3-13.3 26.7-26.7 40-40c-3.4-13.7-5.2-28-5.2-42.8c0-65 35.2-121.8 87.6-152.3c-55-55-110-110-164.9-164.9c-12-12-28.3-18.7-45.3-18.7c-49.8 0-99.6 0-149.4 0C21.5 32 0 53.5 0 80zm112 32c17.7 0 32 14.3 32 32s-14.3 32-32 32s-32-14.3-32-32s14.3-32 32-32zM640 368c0 79.5-64.5 144-144 144s-144-64.5-144-144s64.5-144 144-144s144 64.5 144 144zM480 304l0 48c-16 0-32 0-48 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c16 0 32 0 48 0l0 48c0 8.8 7.2 16 16 16s16-7.2 16-16c0-16 0-32 0-48c16 0 32 0 48 0c8.8 0 16-7.2 16-16s-7.2-16-16-16c-16 0-32 0-48 0c0-16 0-32 0-48c0-8.8-7.2-16-16-16s-16 7.2-16 16z"]

  };
  var prefixes$1 = [null    ,'fak',
    ,'fa-kit'

  ];
  bunker(function () {
    var _iterator = _createForOfIteratorHelper(prefixes$1),
        _step;

    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var prefix = _step.value;
        if (!prefix) continue;
        defineIcons(prefix, icons);
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  });

}());
