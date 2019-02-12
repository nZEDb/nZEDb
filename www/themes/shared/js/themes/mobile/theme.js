/**
 * Copyright (c) Tiny Technologies, Inc. All rights reserved.
 * Licensed under the LGPL or a commercial license.
 * For LGPL see License.txt in the project root for license information.
 * For commercial licenses see https://www.tiny.cloud/
 *
 * Version: 5.0.0-1 (2019-02-04)
 */
(function () {
var mobile = (function (exports) {
    'use strict';

    var __assign = function () {
      __assign = Object.assign || function __assign(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
          s = arguments[i];
          for (var p in s)
            if (Object.prototype.hasOwnProperty.call(s, p))
              t[p] = s[p];
        }
        return t;
      };
      return __assign.apply(this, arguments);
    };
    function __rest(s, e) {
      var t = {};
      for (var p in s)
        if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
          t[p] = s[p];
      if (s != null && typeof Object.getOwnPropertySymbols === 'function')
        for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++)
          if (e.indexOf(p[i]) < 0)
            t[p[i]] = s[p[i]];
      return t;
    }

    var noop = function () {
      var args = [];
      for (var _i = 0; _i < arguments.length; _i++) {
        args[_i] = arguments[_i];
      }
    };
    var compose = function (fa, fb) {
      return function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        return fa(fb.apply(null, args));
      };
    };
    var constant = function (value) {
      return function () {
        return value;
      };
    };
    var identity = function (x) {
      return x;
    };
    function curry(fn) {
      var initialArgs = [];
      for (var _i = 1; _i < arguments.length; _i++) {
        initialArgs[_i - 1] = arguments[_i];
      }
      return function () {
        var restArgs = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          restArgs[_i] = arguments[_i];
        }
        var all = initialArgs.concat(restArgs);
        return fn.apply(null, all);
      };
    }
    var not = function (f) {
      return function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        return !f.apply(null, args);
      };
    };
    var die = function (msg) {
      return function () {
        throw new Error(msg);
      };
    };
    var apply = function (f) {
      return f();
    };
    var never = constant(false);
    var always = constant(true);

    var never$1 = never;
    var always$1 = always;
    var none = function () {
      return NONE;
    };
    var NONE = function () {
      var eq = function (o) {
        return o.isNone();
      };
      var call$$1 = function (thunk) {
        return thunk();
      };
      var id = function (n) {
        return n;
      };
      var noop$$1 = function () {
      };
      var nul = function () {
        return null;
      };
      var undef = function () {
        return undefined;
      };
      var me = {
        fold: function (n, s) {
          return n();
        },
        is: never$1,
        isSome: never$1,
        isNone: always$1,
        getOr: id,
        getOrThunk: call$$1,
        getOrDie: function (msg) {
          throw new Error(msg || 'error: getOrDie called on none.');
        },
        getOrNull: nul,
        getOrUndefined: undef,
        or: id,
        orThunk: call$$1,
        map: none,
        ap: none,
        each: noop$$1,
        bind: none,
        flatten: none,
        exists: never$1,
        forall: always$1,
        filter: none,
        equals: eq,
        equals_: eq,
        toArray: function () {
          return [];
        },
        toString: constant('none()')
      };
      if (Object.freeze)
        Object.freeze(me);
      return me;
    }();
    var some = function (a) {
      var constant_a = function () {
        return a;
      };
      var self = function () {
        return me;
      };
      var map = function (f) {
        return some(f(a));
      };
      var bind = function (f) {
        return f(a);
      };
      var me = {
        fold: function (n, s) {
          return s(a);
        },
        is: function (v) {
          return a === v;
        },
        isSome: always$1,
        isNone: never$1,
        getOr: constant_a,
        getOrThunk: constant_a,
        getOrDie: constant_a,
        getOrNull: constant_a,
        getOrUndefined: constant_a,
        or: self,
        orThunk: self,
        map: map,
        ap: function (optfab) {
          return optfab.fold(none, function (fab) {
            return some(fab(a));
          });
        },
        each: function (f) {
          f(a);
        },
        bind: bind,
        flatten: constant_a,
        exists: bind,
        forall: bind,
        filter: function (f) {
          return f(a) ? me : NONE;
        },
        equals: function (o) {
          return o.is(a);
        },
        equals_: function (o, elementEq) {
          return o.fold(never$1, function (b) {
            return elementEq(a, b);
          });
        },
        toArray: function () {
          return [a];
        },
        toString: function () {
          return 'some(' + a + ')';
        }
      };
      return me;
    };
    var from = function (value) {
      return value === null || value === undefined ? NONE : some(value);
    };
    var Option = {
      some: some,
      none: none,
      from: from
    };

    var keys = Object.keys;
    var hasOwnProperty = Object.hasOwnProperty;
    var each = function (obj, f) {
      var props = keys(obj);
      for (var k = 0, len = props.length; k < len; k++) {
        var i = props[k];
        var x = obj[i];
        f(x, i, obj);
      }
    };
    var map = function (obj, f) {
      return tupleMap(obj, function (x, i, obj) {
        return {
          k: i,
          v: f(x, i, obj)
        };
      });
    };
    var tupleMap = function (obj, f) {
      var r = {};
      each(obj, function (x, i) {
        var tuple = f(x, i, obj);
        r[tuple.k] = tuple.v;
      });
      return r;
    };
    var mapToArray = function (obj, f) {
      var r = [];
      each(obj, function (value, name) {
        r.push(f(value, name));
      });
      return r;
    };
    var has = function (obj, key) {
      return hasOwnProperty.call(obj, key);
    };

    var touchstart = constant('touchstart');
    var touchmove = constant('touchmove');
    var touchend = constant('touchend');
    var mousedown = constant('mousedown');
    var mousemove = constant('mousemove');
    var mouseup = constant('mouseup');
    var mouseover = constant('mouseover');
    var keydown = constant('keydown');
    var keyup = constant('keyup');
    var input = constant('input');
    var change = constant('change');
    var click = constant('click');
    var transitionend = constant('transitionend');
    var selectstart = constant('selectstart');

    var cached = function (f) {
      var called = false;
      var r;
      return function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        if (!called) {
          called = true;
          r = f.apply(null, args);
        }
        return r;
      };
    };

    var firstMatch = function (regexes, s) {
      for (var i = 0; i < regexes.length; i++) {
        var x = regexes[i];
        if (x.test(s))
          return x;
      }
      return undefined;
    };
    var find$1 = function (regexes, agent) {
      var r = firstMatch(regexes, agent);
      if (!r)
        return {
          major: 0,
          minor: 0
        };
      var group = function (i) {
        return Number(agent.replace(r, '$' + i));
      };
      return nu(group(1), group(2));
    };
    var detect = function (versionRegexes, agent) {
      var cleanedAgent = String(agent).toLowerCase();
      if (versionRegexes.length === 0)
        return unknown();
      return find$1(versionRegexes, cleanedAgent);
    };
    var unknown = function () {
      return nu(0, 0);
    };
    var nu = function (major, minor) {
      return {
        major: major,
        minor: minor
      };
    };
    var Version = {
      nu: nu,
      detect: detect,
      unknown: unknown
    };

    var edge = 'Edge';
    var chrome = 'Chrome';
    var ie = 'IE';
    var opera = 'Opera';
    var firefox = 'Firefox';
    var safari = 'Safari';
    var isBrowser = function (name, current) {
      return function () {
        return current === name;
      };
    };
    var unknown$1 = function () {
      return nu$1({
        current: undefined,
        version: Version.unknown()
      });
    };
    var nu$1 = function (info) {
      var current = info.current;
      var version = info.version;
      return {
        current: current,
        version: version,
        isEdge: isBrowser(edge, current),
        isChrome: isBrowser(chrome, current),
        isIE: isBrowser(ie, current),
        isOpera: isBrowser(opera, current),
        isFirefox: isBrowser(firefox, current),
        isSafari: isBrowser(safari, current)
      };
    };
    var Browser = {
      unknown: unknown$1,
      nu: nu$1,
      edge: constant(edge),
      chrome: constant(chrome),
      ie: constant(ie),
      opera: constant(opera),
      firefox: constant(firefox),
      safari: constant(safari)
    };

    var windows = 'Windows';
    var ios = 'iOS';
    var android = 'Android';
    var linux = 'Linux';
    var osx = 'OSX';
    var solaris = 'Solaris';
    var freebsd = 'FreeBSD';
    var isOS = function (name, current) {
      return function () {
        return current === name;
      };
    };
    var unknown$2 = function () {
      return nu$2({
        current: undefined,
        version: Version.unknown()
      });
    };
    var nu$2 = function (info) {
      var current = info.current;
      var version = info.version;
      return {
        current: current,
        version: version,
        isWindows: isOS(windows, current),
        isiOS: isOS(ios, current),
        isAndroid: isOS(android, current),
        isOSX: isOS(osx, current),
        isLinux: isOS(linux, current),
        isSolaris: isOS(solaris, current),
        isFreeBSD: isOS(freebsd, current)
      };
    };
    var OperatingSystem = {
      unknown: unknown$2,
      nu: nu$2,
      windows: constant(windows),
      ios: constant(ios),
      android: constant(android),
      linux: constant(linux),
      osx: constant(osx),
      solaris: constant(solaris),
      freebsd: constant(freebsd)
    };

    var DeviceType = function (os, browser, userAgent) {
      var isiPad = os.isiOS() && /ipad/i.test(userAgent) === true;
      var isiPhone = os.isiOS() && !isiPad;
      var isAndroid3 = os.isAndroid() && os.version.major === 3;
      var isAndroid4 = os.isAndroid() && os.version.major === 4;
      var isTablet = isiPad || isAndroid3 || isAndroid4 && /mobile/i.test(userAgent) === true;
      var isTouch = os.isiOS() || os.isAndroid();
      var isPhone = isTouch && !isTablet;
      var iOSwebview = browser.isSafari() && os.isiOS() && /safari/i.test(userAgent) === false;
      return {
        isiPad: constant(isiPad),
        isiPhone: constant(isiPhone),
        isTablet: constant(isTablet),
        isPhone: constant(isPhone),
        isTouch: constant(isTouch),
        isAndroid: os.isAndroid,
        isiOS: os.isiOS,
        isWebView: constant(iOSwebview)
      };
    };

    var typeOf = function (x) {
      if (x === null)
        return 'null';
      var t = typeof x;
      if (t === 'object' && Array.prototype.isPrototypeOf(x))
        return 'array';
      if (t === 'object' && String.prototype.isPrototypeOf(x))
        return 'string';
      return t;
    };
    var isType = function (type) {
      return function (value) {
        return typeOf(value) === type;
      };
    };
    var isString = isType('string');
    var isObject = isType('object');
    var isArray = isType('array');
    var isBoolean = isType('boolean');
    var isFunction = isType('function');
    var isNumber = isType('number');

    var rawIndexOf = function () {
      var pIndexOf = Array.prototype.indexOf;
      var fastIndex = function (xs, x) {
        return pIndexOf.call(xs, x);
      };
      var slowIndex = function (xs, x) {
        return slowIndexOf(xs, x);
      };
      return pIndexOf === undefined ? slowIndex : fastIndex;
    }();
    var contains = function (xs, x) {
      return rawIndexOf(xs, x) > -1;
    };
    var exists = function (xs, pred) {
      return findIndex(xs, pred).isSome();
    };
    var map$1 = function (xs, f) {
      var len = xs.length;
      var r = new Array(len);
      for (var i = 0; i < len; i++) {
        var x = xs[i];
        r[i] = f(x, i, xs);
      }
      return r;
    };
    var each$1 = function (xs, f) {
      for (var i = 0, len = xs.length; i < len; i++) {
        var x = xs[i];
        f(x, i, xs);
      }
    };
    var eachr = function (xs, f) {
      for (var i = xs.length - 1; i >= 0; i--) {
        var x = xs[i];
        f(x, i, xs);
      }
    };
    var filter = function (xs, pred) {
      var r = [];
      for (var i = 0, len = xs.length; i < len; i++) {
        var x = xs[i];
        if (pred(x, i, xs)) {
          r.push(x);
        }
      }
      return r;
    };
    var foldr = function (xs, f, acc) {
      eachr(xs, function (x) {
        acc = f(acc, x);
      });
      return acc;
    };
    var foldl = function (xs, f, acc) {
      each$1(xs, function (x) {
        acc = f(acc, x);
      });
      return acc;
    };
    var find$2 = function (xs, pred) {
      for (var i = 0, len = xs.length; i < len; i++) {
        var x = xs[i];
        if (pred(x, i, xs)) {
          return Option.some(x);
        }
      }
      return Option.none();
    };
    var findIndex = function (xs, pred) {
      for (var i = 0, len = xs.length; i < len; i++) {
        var x = xs[i];
        if (pred(x, i, xs)) {
          return Option.some(i);
        }
      }
      return Option.none();
    };
    var slowIndexOf = function (xs, x) {
      for (var i = 0, len = xs.length; i < len; ++i) {
        if (xs[i] === x) {
          return i;
        }
      }
      return -1;
    };
    var push = Array.prototype.push;
    var flatten = function (xs) {
      var r = [];
      for (var i = 0, len = xs.length; i < len; ++i) {
        if (!Array.prototype.isPrototypeOf(xs[i]))
          throw new Error('Arr.flatten item ' + i + ' was not an array, input: ' + xs);
        push.apply(r, xs[i]);
      }
      return r;
    };
    var bind = function (xs, f) {
      var output = map$1(xs, f);
      return flatten(output);
    };
    var forall = function (xs, pred) {
      for (var i = 0, len = xs.length; i < len; ++i) {
        var x = xs[i];
        if (pred(x, i, xs) !== true) {
          return false;
        }
      }
      return true;
    };
    var slice = Array.prototype.slice;
    var reverse = function (xs) {
      var r = slice.call(xs, 0);
      r.reverse();
      return r;
    };
    var difference = function (a1, a2) {
      return filter(a1, function (x) {
        return !contains(a2, x);
      });
    };
    var pure = function (x) {
      return [x];
    };
    var from$1 = isFunction(Array.from) ? Array.from : function (x) {
      return slice.call(x);
    };

    var detect$1 = function (candidates, userAgent) {
      var agent = String(userAgent).toLowerCase();
      return find$2(candidates, function (candidate) {
        return candidate.search(agent);
      });
    };
    var detectBrowser = function (browsers, userAgent) {
      return detect$1(browsers, userAgent).map(function (browser) {
        var version = Version.detect(browser.versionRegexes, userAgent);
        return {
          current: browser.name,
          version: version
        };
      });
    };
    var detectOs = function (oses, userAgent) {
      return detect$1(oses, userAgent).map(function (os) {
        var version = Version.detect(os.versionRegexes, userAgent);
        return {
          current: os.name,
          version: version
        };
      });
    };
    var UaString = {
      detectBrowser: detectBrowser,
      detectOs: detectOs
    };

    var checkRange = function (str, substr, start) {
      if (substr === '')
        return true;
      if (str.length < substr.length)
        return false;
      var x = str.substr(start, start + substr.length);
      return x === substr;
    };
    var supplant = function (str, obj) {
      var isStringOrNumber = function (a) {
        var t = typeof a;
        return t === 'string' || t === 'number';
      };
      return str.replace(/\$\{([^{}]*)\}/g, function (fullMatch, key) {
        var value = obj[key];
        return isStringOrNumber(value) ? value.toString() : fullMatch;
      });
    };
    var contains$1 = function (str, substr) {
      return str.indexOf(substr) !== -1;
    };
    var endsWith = function (str, suffix) {
      return checkRange(str, suffix, str.length - suffix.length);
    };
    var trim = function (str) {
      return str.replace(/^\s+|\s+$/g, '');
    };

    var normalVersionRegex = /.*?version\/\ ?([0-9]+)\.([0-9]+).*/;
    var checkContains = function (target) {
      return function (uastring) {
        return contains$1(uastring, target);
      };
    };
    var browsers = [
      {
        name: 'Edge',
        versionRegexes: [/.*?edge\/ ?([0-9]+)\.([0-9]+)$/],
        search: function (uastring) {
          var monstrosity = contains$1(uastring, 'edge/') && contains$1(uastring, 'chrome') && contains$1(uastring, 'safari') && contains$1(uastring, 'applewebkit');
          return monstrosity;
        }
      },
      {
        name: 'Chrome',
        versionRegexes: [
          /.*?chrome\/([0-9]+)\.([0-9]+).*/,
          normalVersionRegex
        ],
        search: function (uastring) {
          return contains$1(uastring, 'chrome') && !contains$1(uastring, 'chromeframe');
        }
      },
      {
        name: 'IE',
        versionRegexes: [
          /.*?msie\ ?([0-9]+)\.([0-9]+).*/,
          /.*?rv:([0-9]+)\.([0-9]+).*/
        ],
        search: function (uastring) {
          return contains$1(uastring, 'msie') || contains$1(uastring, 'trident');
        }
      },
      {
        name: 'Opera',
        versionRegexes: [
          normalVersionRegex,
          /.*?opera\/([0-9]+)\.([0-9]+).*/
        ],
        search: checkContains('opera')
      },
      {
        name: 'Firefox',
        versionRegexes: [/.*?firefox\/\ ?([0-9]+)\.([0-9]+).*/],
        search: checkContains('firefox')
      },
      {
        name: 'Safari',
        versionRegexes: [
          normalVersionRegex,
          /.*?cpu os ([0-9]+)_([0-9]+).*/
        ],
        search: function (uastring) {
          return (contains$1(uastring, 'safari') || contains$1(uastring, 'mobile/')) && contains$1(uastring, 'applewebkit');
        }
      }
    ];
    var oses = [
      {
        name: 'Windows',
        search: checkContains('win'),
        versionRegexes: [/.*?windows\ nt\ ?([0-9]+)\.([0-9]+).*/]
      },
      {
        name: 'iOS',
        search: function (uastring) {
          return contains$1(uastring, 'iphone') || contains$1(uastring, 'ipad');
        },
        versionRegexes: [
          /.*?version\/\ ?([0-9]+)\.([0-9]+).*/,
          /.*cpu os ([0-9]+)_([0-9]+).*/,
          /.*cpu iphone os ([0-9]+)_([0-9]+).*/
        ]
      },
      {
        name: 'Android',
        search: checkContains('android'),
        versionRegexes: [/.*?android\ ?([0-9]+)\.([0-9]+).*/]
      },
      {
        name: 'OSX',
        search: checkContains('os x'),
        versionRegexes: [/.*?os\ x\ ?([0-9]+)_([0-9]+).*/]
      },
      {
        name: 'Linux',
        search: checkContains('linux'),
        versionRegexes: []
      },
      {
        name: 'Solaris',
        search: checkContains('sunos'),
        versionRegexes: []
      },
      {
        name: 'FreeBSD',
        search: checkContains('freebsd'),
        versionRegexes: []
      }
    ];
    var PlatformInfo = {
      browsers: constant(browsers),
      oses: constant(oses)
    };

    var detect$2 = function (userAgent) {
      var browsers = PlatformInfo.browsers();
      var oses = PlatformInfo.oses();
      var browser = UaString.detectBrowser(browsers, userAgent).fold(Browser.unknown, Browser.nu);
      var os = UaString.detectOs(oses, userAgent).fold(OperatingSystem.unknown, OperatingSystem.nu);
      var deviceType = DeviceType(os, browser, userAgent);
      return {
        browser: browser,
        os: os,
        deviceType: deviceType
      };
    };
    var PlatformDetection = { detect: detect$2 };

    var detect$3 = cached(function () {
      var userAgent = navigator.userAgent;
      return PlatformDetection.detect(userAgent);
    });
    var PlatformDetection$1 = { detect: detect$3 };

    var alloy = { tap: constant('alloy.tap') };
    var focus$1 = constant('alloy.focus');
    var postBlur = constant('alloy.blur.post');
    var postPaste = constant('alloy.paste.post');
    var receive = constant('alloy.receive');
    var execute = constant('alloy.execute');
    var focusItem = constant('alloy.focus.item');
    var tap = alloy.tap;
    var tapOrClick = PlatformDetection$1.detect().deviceType.isTouch() ? alloy.tap : click;
    var longpress = constant('alloy.longpress');
    var systemInit = constant('alloy.system.init');
    var attachedToDom = constant('alloy.system.attached');
    var detachedFromDom = constant('alloy.system.detached');
    var focusShifted = constant('alloy.focusmanager.shifted');
    var highlight = constant('alloy.highlight');
    var dehighlight = constant('alloy.dehighlight');

    var emit = function (component, event) {
      dispatchWith(component, component.element(), event, {});
    };
    var emitWith = function (component, event, properties) {
      dispatchWith(component, component.element(), event, properties);
    };
    var emitExecute = function (component) {
      emit(component, execute());
    };
    var dispatch = function (component, target, event) {
      dispatchWith(component, target, event, {});
    };
    var dispatchWith = function (component, target, event, properties) {
      var data = __assign({ target: target }, properties);
      component.getSystem().triggerEvent(event, target, map(data, constant));
    };
    var dispatchEvent = function (component, target, event, simulatedEvent) {
      component.getSystem().triggerEvent(event, target, simulatedEvent.event());
    };
    var dispatchFocus = function (component, target) {
      component.getSystem().triggerFocus(target, component.element());
    };

    var fromHtml = function (html, scope) {
      var doc = scope || document;
      var div = doc.createElement('div');
      div.innerHTML = html;
      if (!div.hasChildNodes() || div.childNodes.length > 1) {
        console.error('HTML does not have a single root node', html);
        throw new Error('HTML must have a single root node');
      }
      return fromDom(div.childNodes[0]);
    };
    var fromTag = function (tag, scope) {
      var doc = scope || document;
      var node = doc.createElement(tag);
      return fromDom(node);
    };
    var fromText = function (text, scope) {
      var doc = scope || document;
      var node = doc.createTextNode(text);
      return fromDom(node);
    };
    var fromDom = function (node) {
      if (node === null || node === undefined) {
        throw new Error('Node cannot be null or undefined');
      }
      return { dom: constant(node) };
    };
    var fromPoint = function (docElm, x, y) {
      var doc = docElm.dom();
      return Option.from(doc.elementFromPoint(x, y)).map(fromDom);
    };
    var Element$$1 = {
      fromHtml: fromHtml,
      fromTag: fromTag,
      fromText: fromText,
      fromDom: fromDom,
      fromPoint: fromPoint
    };

    var ATTRIBUTE = Node.ATTRIBUTE_NODE;
    var CDATA_SECTION = Node.CDATA_SECTION_NODE;
    var COMMENT = Node.COMMENT_NODE;
    var DOCUMENT = Node.DOCUMENT_NODE;
    var DOCUMENT_TYPE = Node.DOCUMENT_TYPE_NODE;
    var DOCUMENT_FRAGMENT = Node.DOCUMENT_FRAGMENT_NODE;
    var ELEMENT = Node.ELEMENT_NODE;
    var TEXT = Node.TEXT_NODE;
    var PROCESSING_INSTRUCTION = Node.PROCESSING_INSTRUCTION_NODE;
    var ENTITY_REFERENCE = Node.ENTITY_REFERENCE_NODE;
    var ENTITY = Node.ENTITY_NODE;
    var NOTATION = Node.NOTATION_NODE;

    var name = function (element) {
      var r = element.dom().nodeName;
      return r.toLowerCase();
    };
    var type = function (element) {
      return element.dom().nodeType;
    };
    var isType$1 = function (t) {
      return function (element) {
        return type(element) === t;
      };
    };
    var isElement = isType$1(ELEMENT);
    var isText = isType$1(TEXT);

    var inBody = function (element) {
      var dom = isText(element) ? element.dom().parentNode : element.dom();
      return dom !== undefined && dom !== null && dom.ownerDocument.body.contains(dom);
    };
    var body = cached(function () {
      return getBody(Element$$1.fromDom(document));
    });
    var getBody = function (doc) {
      var b = doc.dom().body;
      if (b === null || b === undefined) {
        throw new Error('Body is not available yet');
      }
      return Element$$1.fromDom(b);
    };

    var Immutable = function () {
      var fields = [];
      for (var _i = 0; _i < arguments.length; _i++) {
        fields[_i] = arguments[_i];
      }
      return function () {
        var values = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          values[_i] = arguments[_i];
        }
        if (fields.length !== values.length) {
          throw new Error('Wrong number of arguments to struct. Expected "[' + fields.length + ']", got ' + values.length + ' arguments');
        }
        var struct = {};
        each$1(fields, function (name, i) {
          struct[name] = constant(values[i]);
        });
        return struct;
      };
    };

    var sort$1 = function (arr) {
      return arr.slice(0).sort();
    };
    var reqMessage = function (required, keys) {
      throw new Error('All required keys (' + sort$1(required).join(', ') + ') were not specified. Specified keys were: ' + sort$1(keys).join(', ') + '.');
    };
    var unsuppMessage = function (unsupported) {
      throw new Error('Unsupported keys for object: ' + sort$1(unsupported).join(', '));
    };
    var validateStrArr = function (label, array) {
      if (!isArray(array))
        throw new Error('The ' + label + ' fields must be an array. Was: ' + array + '.');
      each$1(array, function (a) {
        if (!isString(a))
          throw new Error('The value ' + a + ' in the ' + label + ' fields was not a string.');
      });
    };
    var checkDupes = function (everything) {
      var sorted = sort$1(everything);
      var dupe = find$2(sorted, function (s, i) {
        return i < sorted.length - 1 && s === sorted[i + 1];
      });
      dupe.each(function (d) {
        throw new Error('The field: ' + d + ' occurs more than once in the combined fields: [' + sorted.join(', ') + '].');
      });
    };

    var MixedBag = function (required, optional) {
      var everything = required.concat(optional);
      if (everything.length === 0)
        throw new Error('You must specify at least one required or optional field.');
      validateStrArr('required', required);
      validateStrArr('optional', optional);
      checkDupes(everything);
      return function (obj) {
        var keys$$1 = keys(obj);
        var allReqd = forall(required, function (req) {
          return contains(keys$$1, req);
        });
        if (!allReqd)
          reqMessage(required, keys$$1);
        var unsupported = filter(keys$$1, function (key) {
          return !contains(everything, key);
        });
        if (unsupported.length > 0)
          unsuppMessage(unsupported);
        var r = {};
        each$1(required, function (req) {
          r[req] = constant(obj[req]);
        });
        each$1(optional, function (opt) {
          r[opt] = constant(Object.prototype.hasOwnProperty.call(obj, opt) ? Option.some(obj[opt]) : Option.none());
        });
        return r;
      };
    };

    var Global = typeof window !== 'undefined' ? window : Function('return this;')();

    var path = function (parts, scope) {
      var o = scope !== undefined && scope !== null ? scope : Global;
      for (var i = 0; i < parts.length && o !== undefined && o !== null; ++i)
        o = o[parts[i]];
      return o;
    };
    var resolve = function (p, scope) {
      var parts = p.split('.');
      return path(parts, scope);
    };

    var unsafe = function (name, scope) {
      return resolve(name, scope);
    };
    var getOrDie = function (name, scope) {
      var actual = unsafe(name, scope);
      if (actual === undefined || actual === null)
        throw name + ' not available on this browser';
      return actual;
    };
    var Global$1 = { getOrDie: getOrDie };

    var node = function () {
      var f = Global$1.getOrDie('Node');
      return f;
    };
    var compareDocumentPosition = function (a, b, match) {
      return (a.compareDocumentPosition(b) & match) !== 0;
    };
    var documentPositionPreceding = function (a, b) {
      return compareDocumentPosition(a, b, node().DOCUMENT_POSITION_PRECEDING);
    };
    var documentPositionContainedBy = function (a, b) {
      return compareDocumentPosition(a, b, node().DOCUMENT_POSITION_CONTAINED_BY);
    };
    var Node$1 = {
      documentPositionPreceding: documentPositionPreceding,
      documentPositionContainedBy: documentPositionContainedBy
    };

    var ELEMENT$1 = ELEMENT;
    var DOCUMENT$1 = DOCUMENT;
    var is = function (element, selector) {
      var elem = element.dom();
      if (elem.nodeType !== ELEMENT$1) {
        return false;
      } else if (elem.matches !== undefined) {
        return elem.matches(selector);
      } else if (elem.msMatchesSelector !== undefined) {
        return elem.msMatchesSelector(selector);
      } else if (elem.webkitMatchesSelector !== undefined) {
        return elem.webkitMatchesSelector(selector);
      } else if (elem.mozMatchesSelector !== undefined) {
        return elem.mozMatchesSelector(selector);
      } else {
        throw new Error('Browser lacks native selectors');
      }
    };
    var bypassSelector = function (dom) {
      return dom.nodeType !== ELEMENT$1 && dom.nodeType !== DOCUMENT$1 || dom.childElementCount === 0;
    };
    var all = function (selector, scope) {
      var base = scope === undefined ? document : scope.dom();
      return bypassSelector(base) ? [] : map$1(base.querySelectorAll(selector), Element$$1.fromDom);
    };
    var one = function (selector, scope) {
      var base = scope === undefined ? document : scope.dom();
      return bypassSelector(base) ? Option.none() : Option.from(base.querySelector(selector)).map(Element$$1.fromDom);
    };

    var eq = function (e1, e2) {
      return e1.dom() === e2.dom();
    };
    var regularContains = function (e1, e2) {
      var d1 = e1.dom();
      var d2 = e2.dom();
      return d1 === d2 ? false : d1.contains(d2);
    };
    var ieContains = function (e1, e2) {
      return Node$1.documentPositionContainedBy(e1.dom(), e2.dom());
    };
    var browser = PlatformDetection$1.detect().browser;
    var contains$2 = browser.isIE() ? ieContains : regularContains;

    var owner = function (element) {
      return Element$$1.fromDom(element.dom().ownerDocument);
    };
    var parent = function (element) {
      var dom = element.dom();
      return Option.from(dom.parentNode).map(Element$$1.fromDom);
    };
    var parents = function (element, isRoot) {
      var stop = isFunction(isRoot) ? isRoot : constant(false);
      var dom = element.dom();
      var ret = [];
      while (dom.parentNode !== null && dom.parentNode !== undefined) {
        var rawParent = dom.parentNode;
        var p = Element$$1.fromDom(rawParent);
        ret.push(p);
        if (stop(p) === true) {
          break;
        } else {
          dom = rawParent;
        }
      }
      return ret;
    };
    var siblings = function (element) {
      var filterSelf = function (elements) {
        return filter(elements, function (x) {
          return !eq(element, x);
        });
      };
      return parent(element).map(children).map(filterSelf).getOr([]);
    };
    var nextSibling = function (element) {
      var dom = element.dom();
      return Option.from(dom.nextSibling).map(Element$$1.fromDom);
    };
    var children = function (element) {
      var dom = element.dom();
      return map$1(dom.childNodes, Element$$1.fromDom);
    };
    var child = function (element, index) {
      var cs = element.dom().childNodes;
      return Option.from(cs[index]).map(Element$$1.fromDom);
    };
    var firstChild = function (element) {
      return child(element, 0);
    };
    var spot = Immutable('element', 'offset');

    var before = function (marker, element) {
      var parent$$1 = parent(marker);
      parent$$1.each(function (v) {
        v.dom().insertBefore(element.dom(), marker.dom());
      });
    };
    var after = function (marker, element) {
      var sibling = nextSibling(marker);
      sibling.fold(function () {
        var parent$$1 = parent(marker);
        parent$$1.each(function (v) {
          append(v, element);
        });
      }, function (v) {
        before(v, element);
      });
    };
    var prepend = function (parent$$1, element) {
      var firstChild$$1 = firstChild(parent$$1);
      firstChild$$1.fold(function () {
        append(parent$$1, element);
      }, function (v) {
        parent$$1.dom().insertBefore(element.dom(), v.dom());
      });
    };
    var append = function (parent$$1, element) {
      parent$$1.dom().appendChild(element.dom());
    };
    var appendAt = function (parent$$1, element, index) {
      child(parent$$1, index).fold(function () {
        append(parent$$1, element);
      }, function (v) {
        before(v, element);
      });
    };

    var append$1 = function (parent, elements) {
      each$1(elements, function (x) {
        append(parent, x);
      });
    };

    var empty = function (element) {
      element.dom().textContent = '';
      each$1(children(element), function (rogue) {
        remove(rogue);
      });
    };
    var remove = function (element) {
      var dom = element.dom();
      if (dom.parentNode !== null) {
        dom.parentNode.removeChild(dom);
      }
    };

    var fireDetaching = function (component) {
      emit(component, detachedFromDom());
      var children$$1 = component.components();
      each$1(children$$1, fireDetaching);
    };
    var fireAttaching = function (component) {
      var children$$1 = component.components();
      each$1(children$$1, fireAttaching);
      emit(component, attachedToDom());
    };
    var attach = function (parent$$1, child$$1) {
      attachWith(parent$$1, child$$1, append);
    };
    var attachWith = function (parent$$1, child$$1, insertion) {
      parent$$1.getSystem().addToWorld(child$$1);
      insertion(parent$$1.element(), child$$1.element());
      if (inBody(parent$$1.element())) {
        fireAttaching(child$$1);
      }
      parent$$1.syncComponents();
    };
    var doDetach = function (component) {
      fireDetaching(component);
      remove(component.element());
      component.getSystem().removeFromWorld(component);
    };
    var detach = function (component) {
      var parent$$1 = parent(component.element()).bind(function (p) {
        return component.getSystem().getByDom(p).fold(Option.none, Option.some);
      });
      doDetach(component);
      parent$$1.each(function (p) {
        p.syncComponents();
      });
    };
    var detachChildren = function (component) {
      var subs = component.components();
      each$1(subs, doDetach);
      empty(component.element());
      component.syncComponents();
    };
    var attachSystem = function (element, guiSystem) {
      attachSystemInternal(element, guiSystem, append);
    };
    var attachSystemInternal = function (element, guiSystem, inserter) {
      inserter(element, guiSystem.element());
      var children$$1 = children(guiSystem.element());
      each$1(children$$1, function (child$$1) {
        guiSystem.getByDom(child$$1).each(fireAttaching);
      });
    };

    var value$1 = function (o) {
      var is = function (v) {
        return o === v;
      };
      var or = function (opt) {
        return value$1(o);
      };
      var orThunk = function (f) {
        return value$1(o);
      };
      var map = function (f) {
        return value$1(f(o));
      };
      var mapError = function (f) {
        return value$1(o);
      };
      var each = function (f) {
        f(o);
      };
      var bind = function (f) {
        return f(o);
      };
      var fold = function (_, onValue) {
        return onValue(o);
      };
      var exists = function (f) {
        return f(o);
      };
      var forall = function (f) {
        return f(o);
      };
      var toOption = function () {
        return Option.some(o);
      };
      return {
        is: is,
        isValue: always,
        isError: never,
        getOr: constant(o),
        getOrThunk: constant(o),
        getOrDie: constant(o),
        or: or,
        orThunk: orThunk,
        fold: fold,
        map: map,
        mapError: mapError,
        each: each,
        bind: bind,
        exists: exists,
        forall: forall,
        toOption: toOption
      };
    };
    var error = function (message) {
      var getOrThunk = function (f) {
        return f();
      };
      var getOrDie = function () {
        return die(String(message))();
      };
      var or = function (opt) {
        return opt;
      };
      var orThunk = function (f) {
        return f();
      };
      var map = function (f) {
        return error(message);
      };
      var mapError = function (f) {
        return error(f(message));
      };
      var bind = function (f) {
        return error(message);
      };
      var fold = function (onError, _) {
        return onError(message);
      };
      return {
        is: never,
        isValue: never,
        isError: always,
        getOr: identity,
        getOrThunk: getOrThunk,
        getOrDie: getOrDie,
        or: or,
        orThunk: orThunk,
        fold: fold,
        map: map,
        mapError: mapError,
        each: noop,
        bind: bind,
        exists: never,
        forall: always,
        toOption: Option.none
      };
    };
    var Result = {
      value: value$1,
      error: error
    };

    var generate = function (cases) {
      if (!isArray(cases)) {
        throw new Error('cases must be an array');
      }
      if (cases.length === 0) {
        throw new Error('there must be at least one case');
      }
      var constructors = [];
      var adt = {};
      each$1(cases, function (acase, count) {
        var keys$$1 = keys(acase);
        if (keys$$1.length !== 1) {
          throw new Error('one and only one name per case');
        }
        var key = keys$$1[0];
        var value = acase[key];
        if (adt[key] !== undefined) {
          throw new Error('duplicate key detected:' + key);
        } else if (key === 'cata') {
          throw new Error('cannot have a case named cata (sorry)');
        } else if (!isArray(value)) {
          throw new Error('case arguments must be an array');
        }
        constructors.push(key);
        adt[key] = function () {
          var argLength = arguments.length;
          if (argLength !== value.length) {
            throw new Error('Wrong number of arguments to case ' + key + '. Expected ' + value.length + ' (' + value + '), got ' + argLength);
          }
          var args = new Array(argLength);
          for (var i = 0; i < args.length; i++)
            args[i] = arguments[i];
          var match = function (branches) {
            var branchKeys = keys(branches);
            if (constructors.length !== branchKeys.length) {
              throw new Error('Wrong number of arguments to match. Expected: ' + constructors.join(',') + '\nActual: ' + branchKeys.join(','));
            }
            var allReqd = forall(constructors, function (reqKey) {
              return contains(branchKeys, reqKey);
            });
            if (!allReqd)
              throw new Error('Not all branches were specified when using match. Specified: ' + branchKeys.join(', ') + '\nRequired: ' + constructors.join(', '));
            return branches[key].apply(null, args);
          };
          return {
            fold: function () {
              if (arguments.length !== cases.length) {
                throw new Error('Wrong number of arguments to fold. Expected ' + cases.length + ', got ' + arguments.length);
              }
              var target = arguments[count];
              return target.apply(null, args);
            },
            match: match,
            log: function (label) {
              console.log(label, {
                constructors: constructors,
                constructor: key,
                params: args
              });
            }
          };
        };
      });
      return adt;
    };
    var Adt = { generate: generate };

    var hasOwnProperty$1 = Object.prototype.hasOwnProperty;
    var shallow = function (old, nu) {
      return nu;
    };
    var deep = function (old, nu) {
      var bothObjects = isObject(old) && isObject(nu);
      return bothObjects ? deepMerge(old, nu) : nu;
    };
    var baseMerge = function (merger) {
      return function () {
        var objects = new Array(arguments.length);
        for (var i = 0; i < objects.length; i++)
          objects[i] = arguments[i];
        if (objects.length === 0)
          throw new Error('Can\'t merge zero objects');
        var ret = {};
        for (var j = 0; j < objects.length; j++) {
          var curObject = objects[j];
          for (var key in curObject)
            if (hasOwnProperty$1.call(curObject, key)) {
              ret[key] = merger(ret[key], curObject[key]);
            }
        }
        return ret;
      };
    };
    var deepMerge = baseMerge(deep);
    var merge = baseMerge(shallow);

    var adt = Adt.generate([
      { strict: [] },
      { defaultedThunk: ['fallbackThunk'] },
      { asOption: [] },
      { asDefaultedOptionThunk: ['fallbackThunk'] },
      { mergeWithThunk: ['baseThunk'] }
    ]);
    var defaulted = function (fallback) {
      return adt.defaultedThunk(constant(fallback));
    };
    var mergeWith = function (base) {
      return adt.mergeWithThunk(constant(base));
    };
    var strict = adt.strict;
    var asOption = adt.asOption;
    var defaultedThunk = adt.defaultedThunk;
    var asDefaultedOptionThunk = adt.asDefaultedOptionThunk;
    var mergeWithThunk = adt.mergeWithThunk;

    var exclude = function (obj, fields) {
      var r = {};
      each(obj, function (v, k) {
        if (!contains(fields, k)) {
          r[k] = v;
        }
      });
      return r;
    };

    var readOpt = function (key) {
      return function (obj) {
        return has(obj, key) ? Option.from(obj[key]) : Option.none();
      };
    };
    var readOr = function (key, fallback) {
      return function (obj) {
        return has(obj, key) ? obj[key] : fallback;
      };
    };
    var readOptFrom = function (obj, key) {
      return readOpt(key)(obj);
    };
    var hasKey = function (obj, key) {
      return has(obj, key) && obj[key] !== undefined && obj[key] !== null;
    };

    var wrap$1 = function (key, value) {
      var r = {};
      r[key] = value;
      return r;
    };
    var wrapAll = function (keyvalues) {
      var r = {};
      each$1(keyvalues, function (kv) {
        r[kv.key] = kv.value;
      });
      return r;
    };

    var comparison = Adt.generate([
      {
        bothErrors: [
          'error1',
          'error2'
        ]
      },
      {
        firstError: [
          'error1',
          'value2'
        ]
      },
      {
        secondError: [
          'value1',
          'error2'
        ]
      },
      {
        bothValues: [
          'value1',
          'value2'
        ]
      }
    ]);
    var partition$1 = function (results) {
      var errors = [];
      var values = [];
      each$1(results, function (result) {
        result.fold(function (err) {
          errors.push(err);
        }, function (value) {
          values.push(value);
        });
      });
      return {
        errors: errors,
        values: values
      };
    };

    var exclude$1 = function (obj, fields) {
      return exclude(obj, fields);
    };
    var readOpt$1 = function (key) {
      return readOpt(key);
    };
    var readOr$1 = function (key, fallback) {
      return readOr(key, fallback);
    };
    var readOptFrom$1 = function (obj, key) {
      return readOptFrom(obj, key);
    };
    var wrap$2 = function (key, value) {
      return wrap$1(key, value);
    };
    var wrapAll$1 = function (keyvalues) {
      return wrapAll(keyvalues);
    };
    var mergeValues = function (values, base) {
      return values.length === 0 ? Result.value(base) : Result.value(deepMerge(base, merge.apply(undefined, values)));
    };
    var mergeErrors = function (errors) {
      return compose(Result.error, flatten)(errors);
    };
    var consolidate = function (objs, base) {
      var partitions = partition$1(objs);
      return partitions.errors.length > 0 ? mergeErrors(partitions.errors) : mergeValues(partitions.values, base);
    };
    var hasKey$1 = function (obj, key) {
      return hasKey(obj, key);
    };

    var SimpleResultType;
    (function (SimpleResultType) {
      SimpleResultType[SimpleResultType['Error'] = 0] = 'Error';
      SimpleResultType[SimpleResultType['Value'] = 1] = 'Value';
    }(SimpleResultType || (SimpleResultType = {})));
    var fold = function (res, onError, onValue) {
      return res.stype === SimpleResultType.Error ? onError(res.serror) : onValue(res.svalue);
    };
    var partition$2 = function (results) {
      var values = [];
      var errors = [];
      each$1(results, function (obj) {
        fold(obj, function (err) {
          return errors.push(err);
        }, function (val) {
          return values.push(val);
        });
      });
      return {
        values: values,
        errors: errors
      };
    };
    var mapError = function (res, f) {
      if (res.stype === SimpleResultType.Error) {
        return {
          stype: SimpleResultType.Error,
          serror: f(res.serror)
        };
      } else {
        return res;
      }
    };
    var map$2 = function (res, f) {
      if (res.stype === SimpleResultType.Value) {
        return {
          stype: SimpleResultType.Value,
          svalue: f(res.svalue)
        };
      } else {
        return res;
      }
    };
    var bind$1 = function (res, f) {
      if (res.stype === SimpleResultType.Value) {
        return f(res.svalue);
      } else {
        return res;
      }
    };
    var bindError = function (res, f) {
      if (res.stype === SimpleResultType.Error) {
        return f(res.serror);
      } else {
        return res;
      }
    };
    var svalue = function (v) {
      return {
        stype: SimpleResultType.Value,
        svalue: v
      };
    };
    var serror = function (e) {
      return {
        stype: SimpleResultType.Error,
        serror: e
      };
    };
    var toResult = function (res) {
      return fold(res, Result.error, Result.value);
    };
    var fromResult = function (res) {
      return res.fold(serror, svalue);
    };
    var SimpleResult = {
      fromResult: fromResult,
      toResult: toResult,
      svalue: svalue,
      partition: partition$2,
      serror: serror,
      bind: bind$1,
      bindError: bindError,
      map: map$2,
      mapError: mapError,
      fold: fold
    };

    var mergeValues$1 = function (values, base) {
      return values.length > 0 ? SimpleResult.svalue(deepMerge(base, merge.apply(undefined, values))) : SimpleResult.svalue(base);
    };
    var mergeErrors$1 = function (errors) {
      return compose(SimpleResult.serror, flatten)(errors);
    };
    var consolidateObj = function (objects, base) {
      var partition$$1 = SimpleResult.partition(objects);
      return partition$$1.errors.length > 0 ? mergeErrors$1(partition$$1.errors) : mergeValues$1(partition$$1.values, base);
    };
    var consolidateArr = function (objects) {
      var partitions = SimpleResult.partition(objects);
      return partitions.errors.length > 0 ? mergeErrors$1(partitions.errors) : SimpleResult.svalue(partitions.values);
    };
    var ResultCombine = {
      consolidateObj: consolidateObj,
      consolidateArr: consolidateArr
    };

    var typeAdt = Adt.generate([
      {
        setOf: [
          'validator',
          'valueType'
        ]
      },
      { arrOf: ['valueType'] },
      { objOf: ['fields'] },
      { itemOf: ['validator'] },
      {
        choiceOf: [
          'key',
          'branches'
        ]
      },
      { thunk: ['description'] },
      {
        func: [
          'args',
          'outputSchema'
        ]
      }
    ]);
    var fieldAdt = Adt.generate([
      {
        field: [
          'name',
          'presence',
          'type'
        ]
      },
      { state: ['name'] }
    ]);

    var json = function () {
      return Global$1.getOrDie('JSON');
    };
    var parse = function (text) {
      return json().parse(text);
    };
    var stringify = function (obj, replacer, space) {
      return json().stringify(obj, replacer, space);
    };
    var Json = {
      parse: parse,
      stringify: stringify
    };

    var formatObj = function (input) {
      return isObject(input) && keys(input).length > 100 ? ' removed due to size' : Json.stringify(input, null, 2);
    };
    var formatErrors = function (errors) {
      var es = errors.length > 10 ? errors.slice(0, 10).concat([{
          path: [],
          getErrorInfo: function () {
            return '... (only showing first ten failures)';
          }
        }]) : errors;
      return map$1(es, function (e) {
        return 'Failed path: (' + e.path.join(' > ') + ')\n' + e.getErrorInfo();
      });
    };

    var nu$3 = function (path, getErrorInfo) {
      return SimpleResult.serror([{
          path: path,
          getErrorInfo: getErrorInfo
        }]);
    };
    var missingStrict = function (path, key, obj) {
      return nu$3(path, function () {
        return 'Could not find valid *strict* value for "' + key + '" in ' + formatObj(obj);
      });
    };
    var missingKey = function (path, key) {
      return nu$3(path, function () {
        return 'Choice schema did not contain choice key: "' + key + '"';
      });
    };
    var missingBranch = function (path, branches, branch) {
      return nu$3(path, function () {
        return 'The chosen schema: "' + branch + '" did not exist in branches: ' + formatObj(branches);
      });
    };
    var unsupportedFields = function (path, unsupported) {
      return nu$3(path, function () {
        return 'There are unsupported fields: [' + unsupported.join(', ') + '] specified';
      });
    };
    var custom = function (path, err) {
      return nu$3(path, function () {
        return err;
      });
    };

    var adt$1 = Adt.generate([
      {
        field: [
          'key',
          'okey',
          'presence',
          'prop'
        ]
      },
      {
        state: [
          'okey',
          'instantiator'
        ]
      }
    ]);
    var strictAccess = function (path, obj, key) {
      return readOptFrom(obj, key).fold(function () {
        return missingStrict(path, key, obj);
      }, SimpleResult.svalue);
    };
    var fallbackAccess = function (obj, key, fallbackThunk) {
      var v = readOptFrom(obj, key).fold(function () {
        return fallbackThunk(obj);
      }, identity);
      return SimpleResult.svalue(v);
    };
    var optionAccess = function (obj, key) {
      return SimpleResult.svalue(readOptFrom(obj, key));
    };
    var optionDefaultedAccess = function (obj, key, fallback) {
      var opt = readOptFrom(obj, key).map(function (val) {
        return val === true ? fallback(obj) : val;
      });
      return SimpleResult.svalue(opt);
    };
    var cExtractOne = function (path, obj, field, strength) {
      return field.fold(function (key, okey, presence, prop) {
        var bundle = function (av) {
          var result = prop.extract(path.concat([key]), strength, av);
          return SimpleResult.map(result, function (res) {
            return wrap$1(okey, strength(res));
          });
        };
        var bundleAsOption = function (optValue) {
          return optValue.fold(function () {
            var outcome = wrap$1(okey, strength(Option.none()));
            return SimpleResult.svalue(outcome);
          }, function (ov) {
            var result = prop.extract(path.concat([key]), strength, ov);
            return SimpleResult.map(result, function (res) {
              return wrap$1(okey, strength(Option.some(res)));
            });
          });
        };
        return function () {
          return presence.fold(function () {
            return SimpleResult.bind(strictAccess(path, obj, key), bundle);
          }, function (fallbackThunk) {
            return SimpleResult.bind(fallbackAccess(obj, key, fallbackThunk), bundle);
          }, function () {
            return SimpleResult.bind(optionAccess(obj, key), bundleAsOption);
          }, function (fallbackThunk) {
            return SimpleResult.bind(optionDefaultedAccess(obj, key, fallbackThunk), bundleAsOption);
          }, function (baseThunk) {
            var base = baseThunk(obj);
            var result = SimpleResult.map(fallbackAccess(obj, key, constant({})), function (v) {
              return deepMerge(base, v);
            });
            return SimpleResult.bind(result, bundle);
          });
        }();
      }, function (okey, instantiator) {
        var state = instantiator(obj);
        return SimpleResult.svalue(wrap$1(okey, strength(state)));
      });
    };
    var cExtract = function (path, obj, fields, strength) {
      var results = map$1(fields, function (field) {
        return cExtractOne(path, obj, field, strength);
      });
      return ResultCombine.consolidateObj(results, {});
    };
    var value$2 = function (validator) {
      var extract = function (path, strength, val) {
        return SimpleResult.bindError(validator(val, strength), function (err) {
          return custom(path, err);
        });
      };
      var toString$$1 = function () {
        return 'val';
      };
      var toDsl = function () {
        return typeAdt.itemOf(validator);
      };
      return {
        extract: extract,
        toString: toString$$1,
        toDsl: toDsl
      };
    };
    var getSetKeys = function (obj) {
      var keys$$1 = keys(obj);
      return filter(keys$$1, function (k) {
        return hasKey$1(obj, k);
      });
    };
    var objOfOnly = function (fields) {
      var delegate = objOf(fields);
      var fieldNames = foldr(fields, function (acc, f) {
        return f.fold(function (key) {
          return deepMerge(acc, wrap$2(key, true));
        }, constant(acc));
      }, {});
      var extract = function (path, strength, o) {
        var keys$$1 = isBoolean(o) ? [] : getSetKeys(o);
        var extra = filter(keys$$1, function (k) {
          return !hasKey$1(fieldNames, k);
        });
        return extra.length === 0 ? delegate.extract(path, strength, o) : unsupportedFields(path, extra);
      };
      return {
        extract: extract,
        toString: delegate.toString,
        toDsl: delegate.toDsl
      };
    };
    var objOf = function (fields) {
      var extract = function (path, strength, o) {
        return cExtract(path, o, fields, strength);
      };
      var toString$$1 = function () {
        var fieldStrings = map$1(fields, function (field) {
          return field.fold(function (key, okey, presence, prop) {
            return key + ' -> ' + prop.toString();
          }, function (okey, instantiator) {
            return 'state(' + okey + ')';
          });
        });
        return 'obj{\n' + fieldStrings.join('\n') + '}';
      };
      var toDsl = function () {
        return typeAdt.objOf(map$1(fields, function (f) {
          return f.fold(function (key, okey, presence, prop) {
            return fieldAdt.field(key, presence, prop);
          }, function (okey, instantiator) {
            return fieldAdt.state(okey);
          });
        }));
      };
      return {
        extract: extract,
        toString: toString$$1,
        toDsl: toDsl
      };
    };
    var arrOf = function (prop) {
      var extract = function (path, strength, array) {
        var results = map$1(array, function (a, i) {
          return prop.extract(path.concat(['[' + i + ']']), strength, a);
        });
        return ResultCombine.consolidateArr(results);
      };
      var toString$$1 = function () {
        return 'array(' + prop.toString() + ')';
      };
      var toDsl = function () {
        return typeAdt.arrOf(prop);
      };
      return {
        extract: extract,
        toString: toString$$1,
        toDsl: toDsl
      };
    };
    var setOf = function (validator, prop) {
      var validateKeys = function (path, keys$$1) {
        return arrOf(value$2(validator)).extract(path, identity, keys$$1);
      };
      var extract = function (path, strength, o) {
        var keys$$1 = keys(o);
        var validatedKeys = validateKeys(path, keys$$1);
        return SimpleResult.bind(validatedKeys, function (validKeys) {
          var schema = map$1(validKeys, function (vk) {
            return adt$1.field(vk, vk, strict(), prop);
          });
          return objOf(schema).extract(path, strength, o);
        });
      };
      var toString$$1 = function () {
        return 'setOf(' + prop.toString() + ')';
      };
      var toDsl = function () {
        return typeAdt.setOf(validator, prop);
      };
      return {
        extract: extract,
        toString: toString$$1,
        toDsl: toDsl
      };
    };
    var anyValue = constant(value$2(SimpleResult.svalue));
    var state = adt$1.state;
    var field = adt$1.field;

    var chooseFrom = function (path, strength, input, branches, ch) {
      var fields = readOptFrom$1(branches, ch);
      return fields.fold(function () {
        return missingBranch(path, branches, ch);
      }, function (fs) {
        return objOf(fs).extract(path.concat(['branch: ' + ch]), strength, input);
      });
    };
    var choose = function (key, branches) {
      var extract = function (path, strength, input) {
        var choice = readOptFrom$1(input, key);
        return choice.fold(function () {
          return missingKey(path, key);
        }, function (chosen) {
          return chooseFrom(path, strength, input, branches, chosen);
        });
      };
      var toString$$1 = function () {
        return 'chooseOn(' + key + '). Possible values: ' + keys(branches);
      };
      var toDsl = function () {
        return typeAdt.choiceOf(key, branches);
      };
      return {
        extract: extract,
        toString: toString$$1,
        toDsl: toDsl
      };
    };

    var _anyValue = value$2(SimpleResult.svalue);
    var valueOf = function (validator) {
      return value$2(function (v) {
        return validator(v).fold(SimpleResult.serror, SimpleResult.svalue);
      });
    };
    var setOf$1 = function (validator, prop) {
      return setOf(function (v) {
        return SimpleResult.fromResult(validator(v));
      }, prop);
    };
    var extract = function (label, prop, strength, obj) {
      var res = prop.extract([label], strength, obj);
      return SimpleResult.mapError(res, function (errs) {
        return {
          input: obj,
          errors: errs
        };
      });
    };
    var asRaw = function (label, prop, obj) {
      return SimpleResult.toResult(extract(label, prop, identity, obj));
    };
    var getOrDie$1 = function (extraction) {
      return extraction.fold(function (errInfo) {
        throw new Error(formatError(errInfo));
      }, identity);
    };
    var asRawOrDie = function (label, prop, obj) {
      return getOrDie$1(asRaw(label, prop, obj));
    };
    var formatError = function (errInfo) {
      return 'Errors: \n' + formatErrors(errInfo.errors) + '\n\nInput object: ' + formatObj(errInfo.input);
    };
    var choose$1 = function (key, branches) {
      return choose(key, branches);
    };
    var anyValue$1 = constant(_anyValue);
    var typedValue = function (validator, expectedType) {
      return value$2(function (a) {
        var actualType = typeof a;
        return validator(a) ? SimpleResult.svalue(a) : SimpleResult.serror('Expected type: ' + expectedType + ' but got: ' + actualType);
      });
    };
    var functionProcessor = typedValue(isFunction, 'function');

    var strict$1 = function (key) {
      return field(key, key, strict(), anyValue());
    };
    var strictOf = function (key, schema) {
      return field(key, key, strict(), schema);
    };
    var strictFunction = function (key) {
      return strictOf(key, functionProcessor);
    };
    var forbid = function (key, message) {
      return field(key, key, asOption(), value$2(function (v) {
        return SimpleResult.serror('The field: ' + key + ' is forbidden. ' + message);
      }));
    };
    var strictObjOf = function (key, objSchema) {
      return field(key, key, strict(), objOf(objSchema));
    };
    var option = function (key) {
      return field(key, key, asOption(), anyValue());
    };
    var optionOf = function (key, schema) {
      return field(key, key, asOption(), schema);
    };
    var optionObjOf = function (key, objSchema) {
      return field(key, key, asOption(), objOf(objSchema));
    };
    var optionObjOfOnly = function (key, objSchema) {
      return field(key, key, asOption(), objOfOnly(objSchema));
    };
    var defaulted$1 = function (key, fallback) {
      return field(key, key, defaulted(fallback), anyValue());
    };
    var defaultedOf = function (key, fallback, schema) {
      return field(key, key, defaulted(fallback), schema);
    };
    var defaultedObjOf = function (key, fallback, objSchema) {
      return field(key, key, defaulted(fallback), objOf(objSchema));
    };
    var state$1 = function (okey, instantiator) {
      return state(okey, instantiator);
    };

    var isSource = function (component, simulatedEvent) {
      return eq(component.element(), simulatedEvent.event().target());
    };

    var nu$4 = function (parts) {
      if (!hasKey$1(parts, 'can') && !hasKey$1(parts, 'abort') && !hasKey$1(parts, 'run')) {
        throw new Error('EventHandler defined by: ' + Json.stringify(parts, null, 2) + ' does not have can, abort, or run!');
      }
      return asRawOrDie('Extracting event.handler', objOfOnly([
        defaulted$1('can', constant(true)),
        defaulted$1('abort', constant(false)),
        defaulted$1('run', noop)
      ]), parts);
    };
    var all$1 = function (handlers, f) {
      return function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        return foldl(handlers, function (acc, handler) {
          return acc && f(handler).apply(undefined, args);
        }, true);
      };
    };
    var any = function (handlers, f) {
      return function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        return foldl(handlers, function (acc, handler) {
          return acc || f(handler).apply(undefined, args);
        }, false);
      };
    };
    var read = function (handler) {
      return isFunction(handler) ? {
        can: constant(true),
        abort: constant(false),
        run: handler
      } : handler;
    };
    var fuse = function (handlers) {
      var can = all$1(handlers, function (handler) {
        return handler.can;
      });
      var abort = any(handlers, function (handler) {
        return handler.abort;
      });
      var run = function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        each$1(handlers, function (handler) {
          handler.run.apply(undefined, args);
        });
      };
      return nu$4({
        can: can,
        abort: abort,
        run: run
      });
    };

    function ClosestOrAncestor (is, ancestor, scope, a, isRoot) {
      return is(scope, a) ? Option.some(scope) : isFunction(isRoot) && isRoot(scope) ? Option.none() : ancestor(scope, a, isRoot);
    }

    var ancestor = function (scope, predicate, isRoot) {
      var element = scope.dom();
      var stop = isFunction(isRoot) ? isRoot : constant(false);
      while (element.parentNode) {
        element = element.parentNode;
        var el = Element$$1.fromDom(element);
        if (predicate(el)) {
          return Option.some(el);
        } else if (stop(el)) {
          break;
        }
      }
      return Option.none();
    };
    var closest = function (scope, predicate, isRoot) {
      var is = function (s) {
        return predicate(s);
      };
      return ClosestOrAncestor(is, ancestor, scope, predicate, isRoot);
    };
    var descendant = function (scope, predicate) {
      var descend = function (node) {
        for (var i = 0; i < node.childNodes.length; i++) {
          if (predicate(Element$$1.fromDom(node.childNodes[i]))) {
            return Option.some(Element$$1.fromDom(node.childNodes[i]));
          }
          var res = descend(node.childNodes[i]);
          if (res.isSome()) {
            return res;
          }
        }
        return Option.none();
      };
      return descend(scope.dom());
    };

    var closest$1 = function (target, transform, isRoot) {
      var delegate = closest(target, function (elem) {
        return transform(elem).isSome();
      }, isRoot);
      return delegate.bind(transform);
    };

    var derive = function (configs) {
      return wrapAll$1(configs);
    };
    var abort = function (name, predicate) {
      return {
        key: name,
        value: nu$4({ abort: predicate })
      };
    };
    var can = function (name, predicate) {
      return {
        key: name,
        value: nu$4({ can: predicate })
      };
    };
    var run = function (name, handler) {
      return {
        key: name,
        value: nu$4({ run: handler })
      };
    };
    var runActionExtra = function (name, action, extra) {
      return {
        key: name,
        value: nu$4({
          run: function (component) {
            action.apply(undefined, [component].concat(extra));
          }
        })
      };
    };
    var runOnName = function (name) {
      return function (handler) {
        return run(name, handler);
      };
    };
    var runOnSourceName = function (name) {
      return function (handler) {
        return {
          key: name,
          value: nu$4({
            run: function (component, simulatedEvent) {
              if (isSource(component, simulatedEvent)) {
                handler(component, simulatedEvent);
              }
            }
          })
        };
      };
    };
    var redirectToUid = function (name, uid) {
      return run(name, function (component, simulatedEvent) {
        component.getSystem().getByUid(uid).each(function (redirectee) {
          dispatchEvent(redirectee, redirectee.element(), name, simulatedEvent);
        });
      });
    };
    var redirectToPart = function (name, detail, partName) {
      var uid = detail.partUids[partName];
      return redirectToUid(name, uid);
    };
    var cutter = function (name) {
      return run(name, function (component, simulatedEvent) {
        simulatedEvent.cut();
      });
    };
    var stopper = function (name) {
      return run(name, function (component, simulatedEvent) {
        simulatedEvent.stop();
      });
    };
    var runOnSource = function (name, f) {
      return runOnSourceName(name)(f);
    };
    var runOnAttached = runOnSourceName(attachedToDom());
    var runOnDetached = runOnSourceName(detachedFromDom());
    var runOnInit = runOnSourceName(systemInit());
    var runOnExecute = runOnName(execute());

    var markAsBehaviourApi = function (f, apiName, apiFunction) {
      var delegate = apiFunction.toString();
      var endIndex = delegate.indexOf(')') + 1;
      var openBracketIndex = delegate.indexOf('(');
      var parameters = delegate.substring(openBracketIndex + 1, endIndex - 1).split(/,\s*/);
      f.toFunctionAnnotation = function () {
        return {
          name: apiName,
          parameters: cleanParameters(parameters.slice(0, 1).concat(parameters.slice(3)))
        };
      };
      return f;
    };
    var cleanParameters = function (parameters) {
      return map$1(parameters, function (p) {
        return endsWith(p, '/*') ? p.substring(0, p.length - '/*'.length) : p;
      });
    };
    var markAsExtraApi = function (f, extraName) {
      var delegate = f.toString();
      var endIndex = delegate.indexOf(')') + 1;
      var openBracketIndex = delegate.indexOf('(');
      var parameters = delegate.substring(openBracketIndex + 1, endIndex - 1).split(/,\s*/);
      f.toFunctionAnnotation = function () {
        return {
          name: extraName,
          parameters: cleanParameters(parameters)
        };
      };
      return f;
    };
    var markAsSketchApi = function (f, apiFunction) {
      var delegate = apiFunction.toString();
      var endIndex = delegate.indexOf(')') + 1;
      var openBracketIndex = delegate.indexOf('(');
      var parameters = delegate.substring(openBracketIndex + 1, endIndex - 1).split(/,\s*/);
      f.toFunctionAnnotation = function () {
        return {
          name: 'OVERRIDE',
          parameters: cleanParameters(parameters.slice(1))
        };
      };
      return f;
    };

    var nu$5 = function (s) {
      return {
        classes: s.classes !== undefined ? s.classes : [],
        attributes: s.attributes !== undefined ? s.attributes : {},
        styles: s.styles !== undefined ? s.styles : {}
      };
    };
    var merge$1 = function (defnA, mod) {
      return __assign({}, defnA, {
        attributes: __assign({}, defnA.attributes, mod.attributes),
        styles: __assign({}, defnA.styles, mod.styles),
        classes: defnA.classes.concat(mod.classes)
      });
    };

    var executeEvent = function (bConfig, bState, executor) {
      return runOnExecute(function (component) {
        executor(component, bConfig, bState);
      });
    };
    var loadEvent = function (bConfig, bState, f) {
      return runOnInit(function (component, simulatedEvent) {
        f(component, bConfig, bState);
      });
    };
    var create = function (schema, name, active, apis, extra, state) {
      var configSchema = objOfOnly(schema);
      var schemaSchema = optionObjOf(name, [optionObjOfOnly('config', schema)]);
      return doCreate(configSchema, schemaSchema, name, active, apis, extra, state);
    };
    var createModes = function (modes, name, active, apis, extra, state) {
      var configSchema = modes;
      var schemaSchema = optionObjOf(name, [optionOf('config', modes)]);
      return doCreate(configSchema, schemaSchema, name, active, apis, extra, state);
    };
    var wrapApi = function (bName, apiFunction, apiName) {
      var f = function (component) {
        var rest = [];
        for (var _i = 1; _i < arguments.length; _i++) {
          rest[_i - 1] = arguments[_i];
        }
        var args = [component].concat(rest);
        return component.config({ name: constant(bName) }).fold(function () {
          throw new Error('We could not find any behaviour configuration for: ' + bName + '. Using API: ' + apiName);
        }, function (info) {
          var rest = Array.prototype.slice.call(args, 1);
          return apiFunction.apply(undefined, [
            component,
            info.config,
            info.state
          ].concat(rest));
        });
      };
      return markAsBehaviourApi(f, apiName, apiFunction);
    };
    var revokeBehaviour = function (name) {
      return {
        key: name,
        value: undefined
      };
    };
    var doCreate = function (configSchema, schemaSchema, name, active, apis, extra, state) {
      var getConfig = function (info) {
        return hasKey$1(info, name) ? info[name]() : Option.none();
      };
      var wrappedApis = map(apis, function (apiF, apiName) {
        return wrapApi(name, apiF, apiName);
      });
      var wrappedExtra = map(extra, function (extraF, extraName) {
        return markAsExtraApi(extraF, extraName);
      });
      var me = __assign({}, wrappedExtra, wrappedApis, {
        revoke: curry(revokeBehaviour, name),
        config: function (spec) {
          var prepared = asRawOrDie(name + '-config', configSchema, spec);
          return {
            key: name,
            value: {
              config: prepared,
              me: me,
              configAsRaw: cached(function () {
                return asRawOrDie(name + '-config', configSchema, spec);
              }),
              initialConfig: spec,
              state: state
            }
          };
        },
        schema: function () {
          return schemaSchema;
        },
        exhibit: function (info, base) {
          return getConfig(info).bind(function (behaviourInfo) {
            return readOptFrom$1(active, 'exhibit').map(function (exhibitor) {
              return exhibitor(base, behaviourInfo.config, behaviourInfo.state);
            });
          }).getOr(nu$5({}));
        },
        name: function () {
          return name;
        },
        handlers: function (info) {
          return getConfig(info).map(function (behaviourInfo) {
            var getEvents = readOr$1('events', function (a, b) {
              return {};
            })(active);
            return getEvents(behaviourInfo.config, behaviourInfo.state);
          }).getOr({});
        }
      });
      return me;
    };

    var NoState = {
      init: function () {
        return nu$6({
          readState: function () {
            return 'No State required';
          }
        });
      }
    };
    var nu$6 = function (spec) {
      return spec;
    };

    var derive$1 = function (capabilities) {
      return wrapAll$1(capabilities);
    };
    var simpleSchema = objOfOnly([
      strict$1('fields'),
      strict$1('name'),
      defaulted$1('active', {}),
      defaulted$1('apis', {}),
      defaulted$1('state', NoState),
      defaulted$1('extra', {})
    ]);
    var create$1 = function (data) {
      var value = asRawOrDie('Creating behaviour: ' + data.name, simpleSchema, data);
      return create(value.fields, value.name, value.active, value.apis, value.extra, value.state);
    };
    var modeSchema = objOfOnly([
      strict$1('branchKey'),
      strict$1('branches'),
      strict$1('name'),
      defaulted$1('active', {}),
      defaulted$1('apis', {}),
      defaulted$1('state', NoState),
      defaulted$1('extra', {})
    ]);
    var createModes$1 = function (data) {
      var value = asRawOrDie('Creating behaviour: ' + data.name, modeSchema, data);
      return createModes(choose$1(value.branchKey, value.branches), value.name, value.active, value.apis, value.extra, value.state);
    };
    var revoke = constant(undefined);

    var rawSet = function (dom, key, value$$1) {
      if (isString(value$$1) || isBoolean(value$$1) || isNumber(value$$1)) {
        dom.setAttribute(key, value$$1 + '');
      } else {
        console.error('Invalid call to Attr.set. Key ', key, ':: Value ', value$$1, ':: Element ', dom);
        throw new Error('Attribute value was not simple');
      }
    };
    var set = function (element, key, value$$1) {
      rawSet(element.dom(), key, value$$1);
    };
    var setAll = function (element, attrs) {
      var dom = element.dom();
      each(attrs, function (v, k) {
        rawSet(dom, k, v);
      });
    };
    var get$1 = function (element, key) {
      var v = element.dom().getAttribute(key);
      return v === null ? undefined : v;
    };
    var has$1 = function (element, key) {
      var dom = element.dom();
      return dom && dom.hasAttribute ? dom.hasAttribute(key) : false;
    };
    var remove$1 = function (element, key) {
      element.dom().removeAttribute(key);
    };

    var read$1 = function (element, attr) {
      var value = get$1(element, attr);
      return value === undefined || value === '' ? [] : value.split(' ');
    };
    var add = function (element, attr, id) {
      var old = read$1(element, attr);
      var nu = old.concat([id]);
      set(element, attr, nu.join(' '));
      return true;
    };
    var remove$2 = function (element, attr, id) {
      var nu = filter(read$1(element, attr), function (v) {
        return v !== id;
      });
      if (nu.length > 0) {
        set(element, attr, nu.join(' '));
      } else {
        remove$1(element, attr);
      }
      return false;
    };

    var supports = function (element) {
      return element.dom().classList !== undefined;
    };
    var get$2 = function (element) {
      return read$1(element, 'class');
    };
    var add$1 = function (element, clazz) {
      return add(element, 'class', clazz);
    };
    var remove$3 = function (element, clazz) {
      return remove$2(element, 'class', clazz);
    };

    var add$2 = function (element, clazz) {
      if (supports(element)) {
        element.dom().classList.add(clazz);
      } else {
        add$1(element, clazz);
      }
    };
    var cleanClass = function (element) {
      var classList = supports(element) ? element.dom().classList : get$2(element);
      if (classList.length === 0) {
        remove$1(element, 'class');
      }
    };
    var remove$4 = function (element, clazz) {
      if (supports(element)) {
        var classList = element.dom().classList;
        classList.remove(clazz);
      } else {
        remove$3(element, clazz);
      }
      cleanClass(element);
    };
    var has$2 = function (element, clazz) {
      return supports(element) && element.dom().classList.contains(clazz);
    };

    var swap = function (element, addCls, removeCls) {
      remove$4(element, removeCls);
      add$2(element, addCls);
    };
    var toAlpha = function (component, swapConfig, swapState) {
      swap(component.element(), swapConfig.alpha, swapConfig.omega);
    };
    var toOmega = function (component, swapConfig, swapState) {
      swap(component.element(), swapConfig.omega, swapConfig.alpha);
    };
    var clear = function (component, swapConfig, swapState) {
      remove$4(component.element(), swapConfig.alpha);
      remove$4(component.element(), swapConfig.omega);
    };
    var isAlpha = function (component, swapConfig, swapState) {
      return has$2(component.element(), swapConfig.alpha);
    };
    var isOmega = function (component, swapConfig, swapState) {
      return has$2(component.element(), swapConfig.omega);
    };

    var SwapApis = /*#__PURE__*/Object.freeze({
        toAlpha: toAlpha,
        toOmega: toOmega,
        isAlpha: isAlpha,
        isOmega: isOmega,
        clear: clear
    });

    var SwapSchema = [
      strict$1('alpha'),
      strict$1('omega')
    ];

    var Swapping = create$1({
      fields: SwapSchema,
      name: 'swapping',
      apis: SwapApis
    });

    var Cell = function (initial) {
      var value = initial;
      var get = function () {
        return value;
      };
      var set = function (v) {
        value = v;
      };
      var clone = function () {
        return Cell(get());
      };
      return {
        get: get,
        set: set,
        clone: clone
      };
    };

    var focus$2 = function (element) {
      element.dom().focus();
    };
    var blur$$1 = function (element) {
      element.dom().blur();
    };
    var hasFocus = function (element) {
      var doc = owner(element).dom();
      return element.dom() === doc.activeElement;
    };
    var active = function (_DOC) {
      var doc = _DOC !== undefined ? _DOC.dom() : document;
      return Option.from(doc.activeElement).map(Element$$1.fromDom);
    };
    var search = function (element) {
      return active(owner(element)).filter(function (e) {
        return element.dom().contains(e.dom());
      });
    };

    var global = tinymce.util.Tools.resolve('tinymce.dom.DOMUtils');

    var global$1 = tinymce.util.Tools.resolve('tinymce.ThemeManager');

    var openLink = function (target) {
      var link = document.createElement('a');
      link.target = '_blank';
      link.href = target.href;
      link.rel = 'noreferrer noopener';
      var nuEvt = document.createEvent('MouseEvents');
      nuEvt.initMouseEvent('click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
      document.body.appendChild(link);
      link.dispatchEvent(nuEvt);
      document.body.removeChild(link);
    };
    var TinyCodeDupe = { openLink: openLink };

    var isSkinDisabled = function (editor) {
      return editor.settings.skin === false;
    };
    var readOnlyOnInit = function (editor) {
      return false;
    };

    var formatChanged = 'formatChanged';
    var orientationChanged = 'orientationChanged';
    var dropupDismissed = 'dropupDismissed';
    var TinyChannels = {
      formatChanged: constant(formatChanged),
      orientationChanged: constant(orientationChanged),
      dropupDismissed: constant(dropupDismissed)
    };

    var fromHtml$1 = function (html, scope) {
      var doc = scope || document;
      var div = doc.createElement('div');
      div.innerHTML = html;
      return children(Element$$1.fromDom(div));
    };

    var get$3 = function (element) {
      return element.dom().innerHTML;
    };
    var set$1 = function (element, content) {
      var owner$$1 = owner(element);
      var docDom = owner$$1.dom();
      var fragment = Element$$1.fromDom(docDom.createDocumentFragment());
      var contentElements = fromHtml$1(content, docDom);
      append$1(fragment, contentElements);
      empty(element);
      append(element, fragment);
    };
    var getOuter = function (element) {
      var container = Element$$1.fromTag('div');
      var clone = Element$$1.fromDom(element.dom().cloneNode(true));
      append(container, clone);
      return get$3(container);
    };

    var clone$1 = function (original, isDeep) {
      return Element$$1.fromDom(original.dom().cloneNode(isDeep));
    };
    var shallow$1 = function (original) {
      return clone$1(original, false);
    };

    var getHtml = function (element) {
      var clone = shallow$1(element);
      return getOuter(clone);
    };

    var element = function (elem) {
      return getHtml(elem);
    };

    var chooseChannels = function (channels, message) {
      return message.universal() ? channels : filter(channels, function (ch) {
        return contains(message.channels(), ch);
      });
    };
    var events = function (receiveConfig) {
      return derive([run(receive(), function (component, message) {
          var channelMap = receiveConfig.channels;
          var channels = keys(channelMap);
          var targetChannels = chooseChannels(channels, message);
          each$1(targetChannels, function (ch) {
            var channelInfo = channelMap[ch];
            var channelSchema = channelInfo.schema;
            var data = asRawOrDie('channel[' + ch + '] data\nReceiver: ' + element(component.element()), channelSchema, message.data());
            channelInfo.onReceive(component, data);
          });
        })]);
    };

    var ActiveReceiving = /*#__PURE__*/Object.freeze({
        events: events
    });

    var cat = function (arr) {
      var r = [];
      var push = function (x) {
        r.push(x);
      };
      for (var i = 0; i < arr.length; i++) {
        arr[i].each(push);
      }
      return r;
    };
    var findMap = function (arr, f) {
      for (var i = 0; i < arr.length; i++) {
        var r = f(arr[i], i);
        if (r.isSome()) {
          return r;
        }
      }
      return Option.none();
    };

    var unknown$3 = 'unknown';
    var EventConfiguration;
    (function (EventConfiguration) {
      EventConfiguration[EventConfiguration['STOP'] = 0] = 'STOP';
      EventConfiguration[EventConfiguration['NORMAL'] = 1] = 'NORMAL';
      EventConfiguration[EventConfiguration['LOGGING'] = 2] = 'LOGGING';
    }(EventConfiguration || (EventConfiguration = {})));
    var eventConfig = Cell({});
    var makeEventLogger = function (eventName, initialTarget) {
      var sequence = [];
      var startTime = new Date().getTime();
      return {
        logEventCut: function (name$$1, target, purpose) {
          sequence.push({
            outcome: 'cut',
            target: target,
            purpose: purpose
          });
        },
        logEventStopped: function (name$$1, target, purpose) {
          sequence.push({
            outcome: 'stopped',
            target: target,
            purpose: purpose
          });
        },
        logNoParent: function (name$$1, target, purpose) {
          sequence.push({
            outcome: 'no-parent',
            target: target,
            purpose: purpose
          });
        },
        logEventNoHandlers: function (name$$1, target) {
          sequence.push({
            outcome: 'no-handlers-left',
            target: target
          });
        },
        logEventResponse: function (name$$1, target, purpose) {
          sequence.push({
            outcome: 'response',
            purpose: purpose,
            target: target
          });
        },
        write: function () {
          var finishTime = new Date().getTime();
          if (contains([
              'mousemove',
              'mouseover',
              'mouseout',
              systemInit()
            ], eventName)) {
            return;
          }
          console.log(eventName, {
            event: eventName,
            time: finishTime - startTime,
            target: initialTarget.dom(),
            sequence: map$1(sequence, function (s) {
              if (!contains([
                  'cut',
                  'stopped',
                  'response'
                ], s.outcome)) {
                return s.outcome;
              } else {
                return '{' + s.purpose + '} ' + s.outcome + ' at (' + element(s.target) + ')';
              }
            })
          });
        }
      };
    };
    var processEvent = function (eventName, initialTarget, f) {
      var status$$1 = readOptFrom$1(eventConfig.get(), eventName).orThunk(function () {
        var patterns = keys(eventConfig.get());
        return findMap(patterns, function (p) {
          return eventName.indexOf(p) > -1 ? Option.some(eventConfig.get()[p]) : Option.none();
        });
      }).getOr(EventConfiguration.NORMAL);
      switch (status$$1) {
      case EventConfiguration.NORMAL:
        return f(noLogger());
      case EventConfiguration.LOGGING: {
          var logger = makeEventLogger(eventName, initialTarget);
          var output = f(logger);
          logger.write();
          return output;
        }
      case EventConfiguration.STOP:
        return true;
      }
    };
    var path$1 = [
      'alloy/data/Fields',
      'alloy/debugging/Debugging'
    ];
    var getTrace = function () {
      var err = new Error();
      if (err.stack !== undefined) {
        var lines = err.stack.split('\n');
        return find$2(lines, function (line) {
          return line.indexOf('alloy') > 0 && !exists(path$1, function (p) {
            return line.indexOf(p) > -1;
          });
        }).getOr(unknown$3);
      } else {
        return unknown$3;
      }
    };
    var ignoreEvent = {
      logEventCut: noop,
      logEventStopped: noop,
      logNoParent: noop,
      logEventNoHandlers: noop,
      logEventResponse: noop,
      write: noop
    };
    var monitorEvent = function (eventName, initialTarget, f) {
      return processEvent(eventName, initialTarget, f);
    };
    var noLogger = constant(ignoreEvent);

    var menuFields = constant([
      strict$1('menu'),
      strict$1('selectedMenu')
    ]);
    var itemFields = constant([
      strict$1('item'),
      strict$1('selectedItem')
    ]);
    var schema = constant(objOf(itemFields().concat(menuFields())));
    var itemSchema = constant(objOf(itemFields()));

    var _initSize = strictObjOf('initSize', [
      strict$1('numColumns'),
      strict$1('numRows')
    ]);
    var itemMarkers = function () {
      return strictOf('markers', itemSchema());
    };
    var tieredMenuMarkers = function () {
      return strictObjOf('markers', [strict$1('backgroundMenu')].concat(menuFields()).concat(itemFields()));
    };
    var markers = function (required) {
      return strictObjOf('markers', map$1(required, strict$1));
    };
    var onPresenceHandler = function (label, fieldName, presence) {
      var trace = getTrace();
      return field(fieldName, fieldName, presence, valueOf(function (f) {
        return Result.value(function () {
          var args = [];
          for (var _i = 0; _i < arguments.length; _i++) {
            args[_i] = arguments[_i];
          }
          return f.apply(undefined, args);
        });
      }));
    };
    var onHandler = function (fieldName) {
      return onPresenceHandler('onHandler', fieldName, defaulted(noop));
    };
    var onKeyboardHandler = function (fieldName) {
      return onPresenceHandler('onKeyboardHandler', fieldName, defaulted(Option.none));
    };
    var onStrictHandler = function (fieldName) {
      return onPresenceHandler('onHandler', fieldName, strict());
    };
    var onStrictKeyboardHandler = function (fieldName) {
      return onPresenceHandler('onKeyboardHandler', fieldName, strict());
    };
    var output$1 = function (name, value) {
      return state$1(name, constant(value));
    };
    var snapshot$1 = function (name) {
      return state$1(name, identity);
    };
    var initSize = constant(_initSize);

    var ReceivingSchema = [strictOf('channels', setOf$1(Result.value, objOfOnly([
        onStrictHandler('onReceive'),
        defaulted$1('schema', anyValue$1())
      ])))];

    var Receiving = create$1({
      fields: ReceivingSchema,
      name: 'receiving',
      active: ActiveReceiving
    });

    var updateAriaState = function (component, toggleConfig, toggleState) {
      var ariaInfo = toggleConfig.aria;
      ariaInfo.update(component, ariaInfo, toggleState.get());
    };
    var updateClass = function (component, toggleConfig, toggleState) {
      toggleConfig.toggleClass.each(function (toggleClass) {
        if (toggleState.get()) {
          add$2(component.element(), toggleClass);
        } else {
          remove$4(component.element(), toggleClass);
        }
      });
    };
    var toggle$2 = function (component, toggleConfig, toggleState) {
      set$2(component, toggleConfig, toggleState, !toggleState.get());
    };
    var on = function (component, toggleConfig, toggleState) {
      toggleState.set(true);
      updateClass(component, toggleConfig, toggleState);
      updateAriaState(component, toggleConfig, toggleState);
    };
    var off = function (component, toggleConfig, toggleState) {
      toggleState.set(false);
      updateClass(component, toggleConfig, toggleState);
      updateAriaState(component, toggleConfig, toggleState);
    };
    var set$2 = function (component, toggleConfig, toggleState, state) {
      var action = state ? on : off;
      action(component, toggleConfig, toggleState);
    };
    var isOn = function (component, toggleConfig, toggleState) {
      return toggleState.get();
    };
    var onLoad = function (component, toggleConfig, toggleState) {
      set$2(component, toggleConfig, toggleState, toggleConfig.selected);
    };

    var ToggleApis = /*#__PURE__*/Object.freeze({
        onLoad: onLoad,
        toggle: toggle$2,
        isOn: isOn,
        on: on,
        off: off,
        set: set$2
    });

    var exhibit = function (base, toggleConfig, toggleState) {
      return nu$5({});
    };
    var events$1 = function (toggleConfig, toggleState) {
      var execute = executeEvent(toggleConfig, toggleState, toggle$2);
      var load = loadEvent(toggleConfig, toggleState, onLoad);
      return derive(flatten([
        toggleConfig.toggleOnExecute ? [execute] : [],
        [load]
      ]));
    };

    var ActiveToggle = /*#__PURE__*/Object.freeze({
        exhibit: exhibit,
        events: events$1
    });

    var init = function (spec) {
      var cell = Cell(false);
      var set = function (state) {
        return cell.set(state);
      };
      var clear = function () {
        return cell.set(false);
      };
      var get = function () {
        return cell.get();
      };
      var readState = function () {
        return cell.get();
      };
      return {
        readState: readState,
        get: get,
        set: set,
        clear: clear
      };
    };

    var TogglingState = /*#__PURE__*/Object.freeze({
        init: init
    });

    var updatePressed = function (component, ariaInfo, status) {
      set(component.element(), 'aria-pressed', status);
      if (ariaInfo.syncWithExpanded) {
        updateExpanded(component, ariaInfo, status);
      }
    };
    var updateSelected = function (component, ariaInfo, status) {
      set(component.element(), 'aria-selected', status);
    };
    var updateChecked = function (component, ariaInfo, status) {
      set(component.element(), 'aria-checked', status);
    };
    var updateExpanded = function (component, ariaInfo, status) {
      set(component.element(), 'aria-expanded', status);
    };

    var ToggleSchema = [
      defaulted$1('selected', false),
      option('toggleClass'),
      defaulted$1('toggleOnExecute', true),
      defaultedOf('aria', { mode: 'none' }, choose$1('mode', {
        pressed: [
          defaulted$1('syncWithExpanded', false),
          output$1('update', updatePressed)
        ],
        checked: [output$1('update', updateChecked)],
        expanded: [output$1('update', updateExpanded)],
        selected: [output$1('update', updateSelected)],
        none: [output$1('update', noop)]
      }))
    ];

    var Toggling = create$1({
      fields: ToggleSchema,
      name: 'toggling',
      active: ActiveToggle,
      apis: ToggleApis,
      state: TogglingState
    });

    var format = function (command, update) {
      return Receiving.config({
        channels: wrap$2(TinyChannels.formatChanged(), {
          onReceive: function (button, data) {
            if (data.command === command) {
              update(button, data.state);
            }
          }
        })
      });
    };
    var orientation = function (onReceive) {
      return Receiving.config({ channels: wrap$2(TinyChannels.orientationChanged(), { onReceive: onReceive }) });
    };
    var receive$1 = function (channel, onReceive) {
      return {
        key: channel,
        value: { onReceive: onReceive }
      };
    };
    var Receivers = {
      format: format,
      orientation: orientation,
      receive: receive$1
    };

    var prefix = 'tinymce-mobile';
    var resolve$1 = function (p) {
      return prefix + '-' + p;
    };
    var Styles = {
      resolve: resolve$1,
      prefix: constant(prefix)
    };

    var events$2 = function (optAction) {
      var executeHandler = function (action) {
        return run(execute(), function (component, simulatedEvent) {
          action(component);
          simulatedEvent.stop();
        });
      };
      var onClick = function (component, simulatedEvent) {
        simulatedEvent.stop();
        emitExecute(component);
      };
      var onMousedown = function (component, simulatedEvent) {
        simulatedEvent.cut();
      };
      var pointerEvents = PlatformDetection$1.detect().deviceType.isTouch() ? [run(tap(), onClick)] : [
        run(click(), onClick),
        run(mousedown(), onMousedown)
      ];
      return derive(flatten([
        optAction.map(executeHandler).toArray(),
        pointerEvents
      ]));
    };

    var focus$3 = function (component, focusConfig) {
      if (!focusConfig.ignore) {
        focus$2(component.element());
        focusConfig.onFocus(component);
      }
    };
    var blur$1 = function (component, focusConfig) {
      if (!focusConfig.ignore) {
        blur$$1(component.element());
      }
    };
    var isFocused = function (component) {
      return hasFocus(component.element());
    };

    var FocusApis = /*#__PURE__*/Object.freeze({
        focus: focus$3,
        blur: blur$1,
        isFocused: isFocused
    });

    var exhibit$1 = function (base, focusConfig) {
      var mod = focusConfig.ignore ? {} : { attributes: { tabindex: '-1' } };
      return nu$5(mod);
    };
    var events$3 = function (focusConfig) {
      return derive([run(focus$1(), function (component, simulatedEvent) {
          focus$3(component, focusConfig);
          simulatedEvent.stop();
        })].concat(focusConfig.stopMousedown ? [run(mousedown(), function (_, simulatedEvent) {
          simulatedEvent.event().prevent();
        })] : []));
    };

    var ActiveFocus = /*#__PURE__*/Object.freeze({
        exhibit: exhibit$1,
        events: events$3
    });

    var FocusSchema = [
      onHandler('onFocus'),
      defaulted$1('stopMousedown', false),
      defaulted$1('ignore', false)
    ];

    var Focusing = create$1({
      fields: FocusSchema,
      name: 'focusing',
      active: ActiveFocus,
      apis: FocusApis
    });

    var isSupported = function (dom) {
      return dom.style !== undefined;
    };

    var internalSet = function (dom, property, value$$1) {
      if (!isString(value$$1)) {
        console.error('Invalid call to CSS.set. Property ', property, ':: Value ', value$$1, ':: Element ', dom);
        throw new Error('CSS value must be a string: ' + value$$1);
      }
      if (isSupported(dom)) {
        dom.style.setProperty(property, value$$1);
      }
    };
    var internalRemove = function (dom, property) {
      if (isSupported(dom)) {
        dom.style.removeProperty(property);
      }
    };
    var set$3 = function (element, property, value$$1) {
      var dom = element.dom();
      internalSet(dom, property, value$$1);
    };
    var setAll$1 = function (element, css) {
      var dom = element.dom();
      each(css, function (v, k) {
        internalSet(dom, k, v);
      });
    };
    var get$4 = function (element, property) {
      var dom = element.dom();
      var styles = window.getComputedStyle(dom);
      var r = styles.getPropertyValue(property);
      var v = r === '' && !inBody(element) ? getUnsafeProperty(dom, property) : r;
      return v === null ? undefined : v;
    };
    var getUnsafeProperty = function (dom, property) {
      return isSupported(dom) ? dom.style.getPropertyValue(property) : '';
    };
    var getRaw = function (element, property) {
      var dom = element.dom();
      var raw = getUnsafeProperty(dom, property);
      return Option.from(raw).filter(function (r) {
        return r.length > 0;
      });
    };
    var remove$5 = function (element, property) {
      var dom = element.dom();
      internalRemove(dom, property);
      if (has$1(element, 'style') && trim(get$1(element, 'style')) === '') {
        remove$1(element, 'style');
      }
    };
    var reflow = function (e) {
      return e.dom().offsetWidth;
    };

    function Dimension (name, getOffset) {
      var set = function (element, h) {
        if (!isNumber(h) && !h.match(/^[0-9]+$/)) {
          throw new Error(name + '.set accepts only positive integer values. Value was ' + h);
        }
        var dom = element.dom();
        if (isSupported(dom)) {
          dom.style[name] = h + 'px';
        }
      };
      var get = function (element) {
        var r = getOffset(element);
        if (r <= 0 || r === null) {
          var css = get$4(element, name);
          return parseFloat(css) || 0;
        }
        return r;
      };
      var getOuter = get;
      var aggregate = function (element, properties) {
        return foldl(properties, function (acc, property) {
          var val = get$4(element, property);
          var value = val === undefined ? 0 : parseInt(val, 10);
          return isNaN(value) ? acc : acc + value;
        }, 0);
      };
      var max = function (element, value, properties) {
        var cumulativeInclusions = aggregate(element, properties);
        var absoluteMax = value > cumulativeInclusions ? value - cumulativeInclusions : 0;
        return absoluteMax;
      };
      return {
        set: set,
        get: get,
        getOuter: getOuter,
        aggregate: aggregate,
        max: max
      };
    }

    var api = Dimension('height', function (element) {
      var dom = element.dom();
      return inBody(element) ? dom.getBoundingClientRect().height : dom.offsetHeight;
    });
    var get$5 = function (element) {
      return api.get(element);
    };

    var ancestors = function (scope, predicate, isRoot) {
      return filter(parents(scope, isRoot), predicate);
    };
    var siblings$1 = function (scope, predicate) {
      return filter(siblings(scope), predicate);
    };

    var all$3 = function (selector) {
      return all(selector);
    };
    var ancestors$1 = function (scope, selector, isRoot) {
      return ancestors(scope, function (e) {
        return is(e, selector);
      }, isRoot);
    };
    var siblings$2 = function (scope, selector) {
      return siblings$1(scope, function (e) {
        return is(e, selector);
      });
    };
    var descendants$1 = function (scope, selector) {
      return all(selector, scope);
    };

    var first$2 = function (selector) {
      return one(selector);
    };
    var ancestor$2 = function (scope, selector, isRoot) {
      return ancestor(scope, function (e) {
        return is(e, selector);
      }, isRoot);
    };
    var descendant$2 = function (scope, selector) {
      return one(selector, scope);
    };
    var closest$3 = function (scope, selector, isRoot) {
      return ClosestOrAncestor(is, ancestor$2, scope, selector, isRoot);
    };

    var BACKSPACE = function () {
      return [8];
    };
    var TAB = function () {
      return [9];
    };
    var ENTER = function () {
      return [13];
    };
    var ESCAPE = function () {
      return [27];
    };
    var SPACE = function () {
      return [32];
    };
    var LEFT = function () {
      return [37];
    };
    var UP = function () {
      return [38];
    };
    var RIGHT = function () {
      return [39];
    };
    var DOWN = function () {
      return [40];
    };

    var cyclePrev = function (values, index, predicate) {
      var before = reverse(values.slice(0, index));
      var after = reverse(values.slice(index + 1));
      return find$2(before.concat(after), predicate);
    };
    var tryPrev = function (values, index, predicate) {
      var before = reverse(values.slice(0, index));
      return find$2(before, predicate);
    };
    var cycleNext = function (values, index, predicate) {
      var before = values.slice(0, index);
      var after = values.slice(index + 1);
      return find$2(after.concat(before), predicate);
    };
    var tryNext = function (values, index, predicate) {
      var after = values.slice(index + 1);
      return find$2(after, predicate);
    };

    var inSet = function (keys) {
      return function (event) {
        var raw = event.raw();
        return contains(keys, raw.which);
      };
    };
    var and = function (preds) {
      return function (event) {
        return forall(preds, function (pred) {
          return pred(event);
        });
      };
    };
    var isShift = function (event) {
      var raw = event.raw();
      return raw.shiftKey === true;
    };
    var isControl = function (event) {
      var raw = event.raw();
      return raw.ctrlKey === true;
    };
    var isNotShift = not(isShift);

    var rule = function (matches, action) {
      return {
        matches: matches,
        classification: action
      };
    };
    var choose$2 = function (transitions, event) {
      var transition = find$2(transitions, function (t) {
        return t.matches(event);
      });
      return transition.map(function (t) {
        return t.classification;
      });
    };

    var cycleBy = function (value, delta, min, max) {
      var r = value + delta;
      if (r > max) {
        return min;
      } else {
        return r < min ? max : r;
      }
    };
    var cap = function (value, min, max) {
      if (value <= min) {
        return min;
      } else {
        return value >= max ? max : value;
      }
    };

    var dehighlightAllExcept = function (component, hConfig, hState, skip) {
      var highlighted = descendants$1(component.element(), '.' + hConfig.highlightClass);
      each$1(highlighted, function (h) {
        if (!exists(skip, function (skipComp) {
            return skipComp.element() === h;
          })) {
          remove$4(h, hConfig.highlightClass);
          component.getSystem().getByDom(h).each(function (target) {
            hConfig.onDehighlight(component, target);
            emit(target, dehighlight());
          });
        }
      });
    };
    var dehighlightAll = function (component, hConfig, hState) {
      return dehighlightAllExcept(component, hConfig, hState, []);
    };
    var dehighlight$1 = function (component, hConfig, hState, target) {
      if (isHighlighted(component, hConfig, hState, target)) {
        remove$4(target.element(), hConfig.highlightClass);
        hConfig.onDehighlight(component, target);
        emit(target, dehighlight());
      }
    };
    var highlight$1 = function (component, hConfig, hState, target) {
      dehighlightAllExcept(component, hConfig, hState, [target]);
      if (!isHighlighted(component, hConfig, hState, target)) {
        add$2(target.element(), hConfig.highlightClass);
        hConfig.onHighlight(component, target);
        emit(target, highlight());
      }
    };
    var highlightFirst = function (component, hConfig, hState) {
      getFirst(component, hConfig, hState).each(function (firstComp) {
        highlight$1(component, hConfig, hState, firstComp);
      });
    };
    var highlightLast = function (component, hConfig, hState) {
      getLast(component, hConfig, hState).each(function (lastComp) {
        highlight$1(component, hConfig, hState, lastComp);
      });
    };
    var highlightAt = function (component, hConfig, hState, index) {
      getByIndex(component, hConfig, hState, index).fold(function (err) {
        throw new Error(err);
      }, function (firstComp) {
        highlight$1(component, hConfig, hState, firstComp);
      });
    };
    var highlightBy = function (component, hConfig, hState, predicate) {
      var candidates = getCandidates(component, hConfig, hState);
      var targetComp = find$2(candidates, predicate);
      targetComp.each(function (c) {
        highlight$1(component, hConfig, hState, c);
      });
    };
    var isHighlighted = function (component, hConfig, hState, queryTarget) {
      return has$2(queryTarget.element(), hConfig.highlightClass);
    };
    var getHighlighted = function (component, hConfig, hState) {
      return descendant$2(component.element(), '.' + hConfig.highlightClass).bind(function (e) {
        return component.getSystem().getByDom(e).toOption();
      });
    };
    var getByIndex = function (component, hConfig, hState, index) {
      var items = descendants$1(component.element(), '.' + hConfig.itemClass);
      return Option.from(items[index]).fold(function () {
        return Result.error('No element found with index ' + index);
      }, component.getSystem().getByDom);
    };
    var getFirst = function (component, hConfig, hState) {
      return descendant$2(component.element(), '.' + hConfig.itemClass).bind(function (e) {
        return component.getSystem().getByDom(e).toOption();
      });
    };
    var getLast = function (component, hConfig, hState) {
      var items = descendants$1(component.element(), '.' + hConfig.itemClass);
      var last$$1 = items.length > 0 ? Option.some(items[items.length - 1]) : Option.none();
      return last$$1.bind(function (c) {
        return component.getSystem().getByDom(c).toOption();
      });
    };
    var getDelta = function (component, hConfig, hState, delta) {
      var items = descendants$1(component.element(), '.' + hConfig.itemClass);
      var current = findIndex(items, function (item) {
        return has$2(item, hConfig.highlightClass);
      });
      return current.bind(function (selected) {
        var dest = cycleBy(selected, delta, 0, items.length - 1);
        return component.getSystem().getByDom(items[dest]).toOption();
      });
    };
    var getPrevious = function (component, hConfig, hState) {
      return getDelta(component, hConfig, hState, -1);
    };
    var getNext = function (component, hConfig, hState) {
      return getDelta(component, hConfig, hState, +1);
    };
    var getCandidates = function (component, hConfig, hState) {
      var items = descendants$1(component.element(), '.' + hConfig.itemClass);
      return cat(map$1(items, function (i) {
        return component.getSystem().getByDom(i).toOption();
      }));
    };

    var HighlightApis = /*#__PURE__*/Object.freeze({
        dehighlightAll: dehighlightAll,
        dehighlight: dehighlight$1,
        highlight: highlight$1,
        highlightFirst: highlightFirst,
        highlightLast: highlightLast,
        highlightAt: highlightAt,
        highlightBy: highlightBy,
        isHighlighted: isHighlighted,
        getHighlighted: getHighlighted,
        getFirst: getFirst,
        getLast: getLast,
        getPrevious: getPrevious,
        getNext: getNext,
        getCandidates: getCandidates
    });

    var HighlightSchema = [
      strict$1('highlightClass'),
      strict$1('itemClass'),
      onHandler('onHighlight'),
      onHandler('onDehighlight')
    ];

    var Highlighting = create$1({
      fields: HighlightSchema,
      name: 'highlighting',
      apis: HighlightApis
    });

    var reportFocusShifting = function (component, prevFocus, newFocus) {
      var noChange = prevFocus.exists(function (p) {
        return newFocus.exists(function (n) {
          return eq(n, p);
        });
      });
      if (!noChange) {
        emitWith(component, focusShifted(), {
          prevFocus: prevFocus,
          newFocus: newFocus
        });
      }
    };
    var dom = function () {
      var get = function (component) {
        return search(component.element());
      };
      var set = function (component, focusee) {
        var prevFocus = get(component);
        component.getSystem().triggerFocus(focusee, component.element());
        var newFocus = get(component);
        reportFocusShifting(component, prevFocus, newFocus);
      };
      return {
        get: get,
        set: set
      };
    };
    var highlights = function () {
      var get = function (component) {
        return Highlighting.getHighlighted(component).map(function (item) {
          return item.element();
        });
      };
      var set = function (component, element) {
        var prevFocus = get(component);
        component.getSystem().getByDom(element).fold(noop, function (item) {
          Highlighting.highlight(component, item);
        });
        var newFocus = get(component);
        reportFocusShifting(component, prevFocus, newFocus);
      };
      return {
        get: get,
        set: set
      };
    };

    var FocusInsideModes;
    (function (FocusInsideModes) {
      FocusInsideModes['OnFocusMode'] = 'onFocus';
      FocusInsideModes['OnEnterOrSpaceMode'] = 'onEnterOrSpace';
      FocusInsideModes['OnApiMode'] = 'onApi';
    }(FocusInsideModes || (FocusInsideModes = {})));

    var typical = function (infoSchema, stateInit, getKeydownRules, getKeyupRules, optFocusIn) {
      var schema = function () {
        return infoSchema.concat([
          defaulted$1('focusManager', dom()),
          defaultedOf('focusInside', 'onFocus', valueOf(function (val) {
            return contains([
              'onFocus',
              'onEnterOrSpace',
              'onApi'
            ], val) ? Result.value(val) : Result.error('Invalid value for focusInside');
          })),
          output$1('handler', me),
          output$1('state', stateInit),
          output$1('sendFocusIn', optFocusIn)
        ]);
      };
      var processKey = function (component, simulatedEvent, getRules, keyingConfig, keyingState) {
        var rules = getRules(component, simulatedEvent, keyingConfig, keyingState);
        return choose$2(rules, simulatedEvent.event()).bind(function (rule$$1) {
          return rule$$1(component, simulatedEvent, keyingConfig, keyingState);
        });
      };
      var toEvents = function (keyingConfig, keyingState) {
        var onFocusHandler = keyingConfig.focusInside !== FocusInsideModes.OnFocusMode ? Option.none() : optFocusIn(keyingConfig).map(function (focusIn) {
          return run(focus$1(), function (component, simulatedEvent) {
            focusIn(component, keyingConfig, keyingState);
            simulatedEvent.stop();
          });
        });
        var tryGoInsideComponent = function (component, simulatedEvent) {
          var isEnterOrSpace = inSet(SPACE().concat(ENTER()))(simulatedEvent.event());
          if (keyingConfig.focusInside === FocusInsideModes.OnEnterOrSpaceMode && isEnterOrSpace && isSource(component, simulatedEvent)) {
            optFocusIn(keyingConfig).each(function (focusIn) {
              focusIn(component, keyingConfig, keyingState);
              simulatedEvent.stop();
            });
          }
        };
        return derive(onFocusHandler.toArray().concat([
          run(keydown(), function (component, simulatedEvent) {
            processKey(component, simulatedEvent, getKeydownRules, keyingConfig, keyingState).fold(function () {
              tryGoInsideComponent(component, simulatedEvent);
            }, function (_) {
              simulatedEvent.stop();
            });
          }),
          run(keyup(), function (component, simulatedEvent) {
            processKey(component, simulatedEvent, getKeyupRules, keyingConfig, keyingState).each(function (_) {
              simulatedEvent.stop();
            });
          })
        ]));
      };
      var me = {
        schema: schema,
        processKey: processKey,
        toEvents: toEvents
      };
      return me;
    };

    var create$2 = function (cyclicField) {
      var schema = [
        option('onEscape'),
        option('onEnter'),
        defaulted$1('selector', '[data-alloy-tabstop="true"]'),
        defaulted$1('firstTabstop', 0),
        defaulted$1('useTabstopAt', constant(true)),
        option('visibilitySelector')
      ].concat([cyclicField]);
      var isVisible = function (tabbingConfig, element) {
        var target = tabbingConfig.visibilitySelector.bind(function (sel) {
          return closest$3(element, sel);
        }).getOr(element);
        return get$5(target) > 0;
      };
      var findInitial = function (component, tabbingConfig) {
        var tabstops = descendants$1(component.element(), tabbingConfig.selector);
        var visibles = filter(tabstops, function (elem) {
          return isVisible(tabbingConfig, elem);
        });
        return Option.from(visibles[tabbingConfig.firstTabstop]);
      };
      var findCurrent = function (component, tabbingConfig) {
        return tabbingConfig.focusManager.get(component).bind(function (elem) {
          return closest$3(elem, tabbingConfig.selector);
        });
      };
      var isTabstop = function (tabbingConfig, element) {
        return isVisible(tabbingConfig, element) && tabbingConfig.useTabstopAt(element);
      };
      var focusIn = function (component, tabbingConfig) {
        findInitial(component, tabbingConfig).each(function (target) {
          tabbingConfig.focusManager.set(component, target);
        });
      };
      var goFromTabstop = function (component, tabstops, stopIndex, tabbingConfig, cycle) {
        return cycle(tabstops, stopIndex, function (elem) {
          return isTabstop(tabbingConfig, elem);
        }).fold(function () {
          return tabbingConfig.cyclic ? Option.some(true) : Option.none();
        }, function (target) {
          tabbingConfig.focusManager.set(component, target);
          return Option.some(true);
        });
      };
      var go = function (component, simulatedEvent, tabbingConfig, cycle) {
        var tabstops = descendants$1(component.element(), tabbingConfig.selector);
        return findCurrent(component, tabbingConfig).bind(function (tabstop) {
          var optStopIndex = findIndex(tabstops, curry(eq, tabstop));
          return optStopIndex.bind(function (stopIndex) {
            return goFromTabstop(component, tabstops, stopIndex, tabbingConfig, cycle);
          });
        });
      };
      var goBackwards = function (component, simulatedEvent, tabbingConfig, tabbingState) {
        var navigate = tabbingConfig.cyclic ? cyclePrev : tryPrev;
        return go(component, simulatedEvent, tabbingConfig, navigate);
      };
      var goForwards = function (component, simulatedEvent, tabbingConfig, tabbingState) {
        var navigate = tabbingConfig.cyclic ? cycleNext : tryNext;
        return go(component, simulatedEvent, tabbingConfig, navigate);
      };
      var execute = function (component, simulatedEvent, tabbingConfig, tabbingState) {
        return tabbingConfig.onEnter.bind(function (f) {
          return f(component, simulatedEvent);
        });
      };
      var exit = function (component, simulatedEvent, tabbingConfig, tabbingState) {
        return tabbingConfig.onEscape.bind(function (f) {
          return f(component, simulatedEvent);
        });
      };
      var getKeydownRules = constant([
        rule(and([
          isShift,
          inSet(TAB())
        ]), goBackwards),
        rule(inSet(TAB()), goForwards),
        rule(inSet(ESCAPE()), exit),
        rule(and([
          isNotShift,
          inSet(ENTER())
        ]), execute)
      ]);
      var getKeyupRules = constant([]);
      return typical(schema, NoState.init, getKeydownRules, getKeyupRules, function () {
        return Option.some(focusIn);
      });
    };

    var AcyclicType = create$2(state$1('cyclic', constant(false)));

    var CyclicType = create$2(state$1('cyclic', constant(true)));

    var inside = function (target) {
      return name(target) === 'input' && get$1(target, 'type') !== 'radio' || name(target) === 'textarea';
    };

    var doDefaultExecute = function (component, simulatedEvent, focused) {
      dispatch(component, focused, execute());
      return Option.some(true);
    };
    var defaultExecute = function (component, simulatedEvent, focused) {
      return inside(focused) && inSet(SPACE())(simulatedEvent.event()) ? Option.none() : doDefaultExecute(component, simulatedEvent, focused);
    };
    var stopEventForFirefox = function (component, simulatedEvent) {
      return Option.some(true);
    };

    var schema$1 = [
      defaulted$1('execute', defaultExecute),
      defaulted$1('useSpace', false),
      defaulted$1('useEnter', true),
      defaulted$1('useControlEnter', false),
      defaulted$1('useDown', false)
    ];
    var execute$1 = function (component, simulatedEvent, executeConfig) {
      return executeConfig.execute(component, simulatedEvent, component.element());
    };
    var getKeydownRules = function (component, simulatedEvent, executeConfig, executeState) {
      var spaceExec = executeConfig.useSpace && !inside(component.element()) ? SPACE() : [];
      var enterExec = executeConfig.useEnter ? ENTER() : [];
      var downExec = executeConfig.useDown ? DOWN() : [];
      var execKeys = spaceExec.concat(enterExec).concat(downExec);
      return [rule(inSet(execKeys), execute$1)].concat(executeConfig.useControlEnter ? [rule(and([
          isControl,
          inSet(ENTER())
        ]), execute$1)] : []);
    };
    var getKeyupRules = function (component, simulatedEvent, executeConfig, executeState) {
      return executeConfig.useSpace && !inside(component.element()) ? [rule(inSet(SPACE()), stopEventForFirefox)] : [];
    };
    var ExecutionType = typical(schema$1, NoState.init, getKeydownRules, getKeyupRules, function () {
      return Option.none();
    });

    var flatgrid = function (spec) {
      var dimensions = Cell(Option.none());
      var setGridSize = function (numRows, numColumns) {
        dimensions.set(Option.some({
          numRows: constant(numRows),
          numColumns: constant(numColumns)
        }));
      };
      var getNumRows = function () {
        return dimensions.get().map(function (d) {
          return d.numRows();
        });
      };
      var getNumColumns = function () {
        return dimensions.get().map(function (d) {
          return d.numColumns();
        });
      };
      return nu$6({
        readState: function () {
          return dimensions.get().map(function (d) {
            return {
              numRows: d.numRows(),
              numColumns: d.numColumns()
            };
          }).getOr({
            numRows: '?',
            numColumns: '?'
          });
        },
        setGridSize: setGridSize,
        getNumRows: getNumRows,
        getNumColumns: getNumColumns
      });
    };
    var init$1 = function (spec) {
      return spec.state(spec);
    };

    var KeyingState = /*#__PURE__*/Object.freeze({
        flatgrid: flatgrid,
        init: init$1
    });

    var onDirection = function (isLtr, isRtl) {
      return function (element) {
        return getDirection(element) === 'rtl' ? isRtl : isLtr;
      };
    };
    var getDirection = function (element) {
      return get$4(element, 'direction') === 'rtl' ? 'rtl' : 'ltr';
    };

    var useH = function (movement) {
      return function (component, simulatedEvent, config, state) {
        var move = movement(component.element());
        return use(move, component, simulatedEvent, config, state);
      };
    };
    var west = function (moveLeft, moveRight) {
      var movement = onDirection(moveLeft, moveRight);
      return useH(movement);
    };
    var east = function (moveLeft, moveRight) {
      var movement = onDirection(moveRight, moveLeft);
      return useH(movement);
    };
    var useV = function (move) {
      return function (component, simulatedEvent, config, state) {
        return use(move, component, simulatedEvent, config, state);
      };
    };
    var use = function (move, component, simulatedEvent, config, state) {
      var outcome = config.focusManager.get(component).bind(function (focused) {
        return move(component.element(), focused, config, state);
      });
      return outcome.map(function (newFocus) {
        config.focusManager.set(component, newFocus);
        return true;
      });
    };
    var north = useV;
    var south = useV;
    var move = useV;

    var isHidden = function (dom) {
      return dom.offsetWidth <= 0 && dom.offsetHeight <= 0;
    };
    var isVisible = function (element) {
      var dom = element.dom();
      return !isHidden(dom);
    };

    var indexInfo = MixedBag([
      'index',
      'candidates'
    ], []);
    var locate = function (candidates, predicate) {
      return findIndex(candidates, predicate).map(function (index) {
        return indexInfo({
          index: index,
          candidates: candidates
        });
      });
    };

    var locateVisible = function (container, current, selector) {
      var filter$$1 = isVisible;
      return locateIn(container, current, selector, filter$$1);
    };
    var locateIn = function (container, current, selector, filter$$1) {
      var predicate = curry(eq, current);
      var candidates = descendants$1(container, selector);
      var visible = filter(candidates, isVisible);
      return locate(visible, predicate);
    };
    var findIndex$2 = function (elements, target) {
      return findIndex(elements, function (elem) {
        return eq(target, elem);
      });
    };

    var withGrid = function (values, index, numCols, f) {
      var oldRow = Math.floor(index / numCols);
      var oldColumn = index % numCols;
      return f(oldRow, oldColumn).bind(function (address) {
        var newIndex = address.row() * numCols + address.column();
        return newIndex >= 0 && newIndex < values.length ? Option.some(values[newIndex]) : Option.none();
      });
    };
    var cycleHorizontal = function (values, index, numRows, numCols, delta) {
      return withGrid(values, index, numCols, function (oldRow, oldColumn) {
        var onLastRow = oldRow === numRows - 1;
        var colsInRow = onLastRow ? values.length - oldRow * numCols : numCols;
        var newColumn = cycleBy(oldColumn, delta, 0, colsInRow - 1);
        return Option.some({
          row: constant(oldRow),
          column: constant(newColumn)
        });
      });
    };
    var cycleVertical = function (values, index, numRows, numCols, delta) {
      return withGrid(values, index, numCols, function (oldRow, oldColumn) {
        var newRow = cycleBy(oldRow, delta, 0, numRows - 1);
        var onLastRow = newRow === numRows - 1;
        var colsInRow = onLastRow ? values.length - newRow * numCols : numCols;
        var newCol = cap(oldColumn, 0, colsInRow - 1);
        return Option.some({
          row: constant(newRow),
          column: constant(newCol)
        });
      });
    };
    var cycleRight = function (values, index, numRows, numCols) {
      return cycleHorizontal(values, index, numRows, numCols, +1);
    };
    var cycleLeft = function (values, index, numRows, numCols) {
      return cycleHorizontal(values, index, numRows, numCols, -1);
    };
    var cycleUp = function (values, index, numRows, numCols) {
      return cycleVertical(values, index, numRows, numCols, -1);
    };
    var cycleDown = function (values, index, numRows, numCols) {
      return cycleVertical(values, index, numRows, numCols, +1);
    };

    var schema$2 = [
      strict$1('selector'),
      defaulted$1('execute', defaultExecute),
      onKeyboardHandler('onEscape'),
      defaulted$1('captureTab', false),
      initSize()
    ];
    var focusIn = function (component, gridConfig, gridState) {
      descendant$2(component.element(), gridConfig.selector).each(function (first) {
        gridConfig.focusManager.set(component, first);
      });
    };
    var findCurrent = function (component, gridConfig) {
      return gridConfig.focusManager.get(component).bind(function (elem) {
        return closest$3(elem, gridConfig.selector);
      });
    };
    var execute$2 = function (component, simulatedEvent, gridConfig, gridState) {
      return findCurrent(component, gridConfig).bind(function (focused) {
        return gridConfig.execute(component, simulatedEvent, focused);
      });
    };
    var doMove = function (cycle) {
      return function (element, focused, gridConfig, gridState) {
        return locateVisible(element, focused, gridConfig.selector).bind(function (identified) {
          return cycle(identified.candidates(), identified.index(), gridState.getNumRows().getOr(gridConfig.initSize.numRows), gridState.getNumColumns().getOr(gridConfig.initSize.numColumns));
        });
      };
    };
    var handleTab = function (component, simulatedEvent, gridConfig, gridState) {
      return gridConfig.captureTab ? Option.some(true) : Option.none();
    };
    var doEscape = function (component, simulatedEvent, gridConfig, gridState) {
      return gridConfig.onEscape(component, simulatedEvent);
    };
    var moveLeft = doMove(cycleLeft);
    var moveRight = doMove(cycleRight);
    var moveNorth = doMove(cycleUp);
    var moveSouth = doMove(cycleDown);
    var getKeydownRules$1 = constant([
      rule(inSet(LEFT()), west(moveLeft, moveRight)),
      rule(inSet(RIGHT()), east(moveLeft, moveRight)),
      rule(inSet(UP()), north(moveNorth)),
      rule(inSet(DOWN()), south(moveSouth)),
      rule(and([
        isShift,
        inSet(TAB())
      ]), handleTab),
      rule(and([
        isNotShift,
        inSet(TAB())
      ]), handleTab),
      rule(inSet(ESCAPE()), doEscape),
      rule(inSet(SPACE().concat(ENTER())), execute$2)
    ]);
    var getKeyupRules$1 = constant([rule(inSet(SPACE()), stopEventForFirefox)]);
    var FlatgridType = typical(schema$2, flatgrid, getKeydownRules$1, getKeyupRules$1, function () {
      return Option.some(focusIn);
    });

    var horizontal = function (container, selector, current, delta) {
      var isDisabledButton = function (candidate) {
        return name(candidate) === 'button' && get$1(candidate, 'disabled') === 'disabled';
      };
      var tryCycle = function (initial, index, candidates) {
        var newIndex = cycleBy(index, delta, 0, candidates.length - 1);
        if (newIndex === initial) {
          return Option.none();
        } else {
          return isDisabledButton(candidates[newIndex]) ? tryCycle(initial, newIndex, candidates) : Option.from(candidates[newIndex]);
        }
      };
      return locateVisible(container, current, selector).bind(function (identified) {
        var index = identified.index();
        var candidates = identified.candidates();
        return tryCycle(index, index, candidates);
      });
    };

    var schema$3 = [
      strict$1('selector'),
      defaulted$1('getInitial', Option.none),
      defaulted$1('execute', defaultExecute),
      onKeyboardHandler('onEscape'),
      defaulted$1('executeOnMove', false),
      defaulted$1('allowVertical', true)
    ];
    var findCurrent$1 = function (component, flowConfig) {
      return flowConfig.focusManager.get(component).bind(function (elem) {
        return closest$3(elem, flowConfig.selector);
      });
    };
    var execute$3 = function (component, simulatedEvent, flowConfig) {
      return findCurrent$1(component, flowConfig).bind(function (focused) {
        return flowConfig.execute(component, simulatedEvent, focused);
      });
    };
    var focusIn$1 = function (component, flowConfig) {
      flowConfig.getInitial(component).orThunk(function () {
        return descendant$2(component.element(), flowConfig.selector);
      }).each(function (first) {
        flowConfig.focusManager.set(component, first);
      });
    };
    var moveLeft$1 = function (element, focused, info) {
      return horizontal(element, info.selector, focused, -1);
    };
    var moveRight$1 = function (element, focused, info) {
      return horizontal(element, info.selector, focused, +1);
    };
    var doMove$1 = function (movement) {
      return function (component, simulatedEvent, flowConfig) {
        return movement(component, simulatedEvent, flowConfig).bind(function () {
          return flowConfig.executeOnMove ? execute$3(component, simulatedEvent, flowConfig) : Option.some(true);
        });
      };
    };
    var doEscape$1 = function (component, simulatedEvent, flowConfig, _flowState) {
      return flowConfig.onEscape(component, simulatedEvent);
    };
    var getKeydownRules$2 = function (_component, _se, flowConfig, _flowState) {
      var westMovers = LEFT().concat(flowConfig.allowVertical ? UP() : []);
      var eastMovers = RIGHT().concat(flowConfig.allowVertical ? DOWN() : []);
      return [
        rule(inSet(westMovers), doMove$1(west(moveLeft$1, moveRight$1))),
        rule(inSet(eastMovers), doMove$1(east(moveLeft$1, moveRight$1))),
        rule(inSet(ENTER()), execute$3),
        rule(inSet(SPACE()), execute$3),
        rule(inSet(ESCAPE()), doEscape$1)
      ];
    };
    var getKeyupRules$2 = constant([rule(inSet(SPACE()), stopEventForFirefox)]);
    var FlowType = typical(schema$3, NoState.init, getKeydownRules$2, getKeyupRules$2, function () {
      return Option.some(focusIn$1);
    });

    var outcome = MixedBag([
      'rowIndex',
      'columnIndex',
      'cell'
    ], []);
    var toCell = function (matrix, rowIndex, columnIndex) {
      return Option.from(matrix[rowIndex]).bind(function (row) {
        return Option.from(row[columnIndex]).map(function (cell) {
          return outcome({
            rowIndex: rowIndex,
            columnIndex: columnIndex,
            cell: cell
          });
        });
      });
    };
    var cycleHorizontal$1 = function (matrix, rowIndex, startCol, deltaCol) {
      var row = matrix[rowIndex];
      var colsInRow = row.length;
      var newColIndex = cycleBy(startCol, deltaCol, 0, colsInRow - 1);
      return toCell(matrix, rowIndex, newColIndex);
    };
    var cycleVertical$1 = function (matrix, colIndex, startRow, deltaRow) {
      var nextRowIndex = cycleBy(startRow, deltaRow, 0, matrix.length - 1);
      var colsInNextRow = matrix[nextRowIndex].length;
      var nextColIndex = cap(colIndex, 0, colsInNextRow - 1);
      return toCell(matrix, nextRowIndex, nextColIndex);
    };
    var moveHorizontal = function (matrix, rowIndex, startCol, deltaCol) {
      var row = matrix[rowIndex];
      var colsInRow = row.length;
      var newColIndex = cap(startCol + deltaCol, 0, colsInRow - 1);
      return toCell(matrix, rowIndex, newColIndex);
    };
    var moveVertical = function (matrix, colIndex, startRow, deltaRow) {
      var nextRowIndex = cap(startRow + deltaRow, 0, matrix.length - 1);
      var colsInNextRow = matrix[nextRowIndex].length;
      var nextColIndex = cap(colIndex, 0, colsInNextRow - 1);
      return toCell(matrix, nextRowIndex, nextColIndex);
    };
    var cycleRight$1 = function (matrix, startRow, startCol) {
      return cycleHorizontal$1(matrix, startRow, startCol, +1);
    };
    var cycleLeft$1 = function (matrix, startRow, startCol) {
      return cycleHorizontal$1(matrix, startRow, startCol, -1);
    };
    var cycleUp$1 = function (matrix, startRow, startCol) {
      return cycleVertical$1(matrix, startCol, startRow, -1);
    };
    var cycleDown$1 = function (matrix, startRow, startCol) {
      return cycleVertical$1(matrix, startCol, startRow, +1);
    };
    var moveLeft$2 = function (matrix, startRow, startCol) {
      return moveHorizontal(matrix, startRow, startCol, -1);
    };
    var moveRight$2 = function (matrix, startRow, startCol) {
      return moveHorizontal(matrix, startRow, startCol, +1);
    };
    var moveUp = function (matrix, startRow, startCol) {
      return moveVertical(matrix, startCol, startRow, -1);
    };
    var moveDown = function (matrix, startRow, startCol) {
      return moveVertical(matrix, startCol, startRow, +1);
    };

    var schema$4 = [
      strictObjOf('selectors', [
        strict$1('row'),
        strict$1('cell')
      ]),
      defaulted$1('cycles', true),
      defaulted$1('previousSelector', Option.none),
      defaulted$1('execute', defaultExecute)
    ];
    var focusIn$2 = function (component, matrixConfig) {
      var focused = matrixConfig.previousSelector(component).orThunk(function () {
        var selectors = matrixConfig.selectors;
        return descendant$2(component.element(), selectors.cell);
      });
      focused.each(function (cell) {
        matrixConfig.focusManager.set(component, cell);
      });
    };
    var execute$4 = function (component, simulatedEvent, matrixConfig) {
      return search(component.element()).bind(function (focused) {
        return matrixConfig.execute(component, simulatedEvent, focused);
      });
    };
    var toMatrix = function (rows, matrixConfig) {
      return map$1(rows, function (row) {
        return descendants$1(row, matrixConfig.selectors.cell);
      });
    };
    var doMove$2 = function (ifCycle, ifMove) {
      return function (element, focused, matrixConfig) {
        var move$$1 = matrixConfig.cycles ? ifCycle : ifMove;
        return closest$3(focused, matrixConfig.selectors.row).bind(function (inRow) {
          var cellsInRow = descendants$1(inRow, matrixConfig.selectors.cell);
          return findIndex$2(cellsInRow, focused).bind(function (colIndex) {
            var allRows = descendants$1(element, matrixConfig.selectors.row);
            return findIndex$2(allRows, inRow).bind(function (rowIndex) {
              var matrix = toMatrix(allRows, matrixConfig);
              return move$$1(matrix, rowIndex, colIndex).map(function (next) {
                return next.cell();
              });
            });
          });
        });
      };
    };
    var moveLeft$3 = doMove$2(cycleLeft$1, moveLeft$2);
    var moveRight$3 = doMove$2(cycleRight$1, moveRight$2);
    var moveNorth$1 = doMove$2(cycleUp$1, moveUp);
    var moveSouth$1 = doMove$2(cycleDown$1, moveDown);
    var getKeydownRules$3 = constant([
      rule(inSet(LEFT()), west(moveLeft$3, moveRight$3)),
      rule(inSet(RIGHT()), east(moveLeft$3, moveRight$3)),
      rule(inSet(UP()), north(moveNorth$1)),
      rule(inSet(DOWN()), south(moveSouth$1)),
      rule(inSet(SPACE().concat(ENTER())), execute$4)
    ]);
    var getKeyupRules$3 = constant([rule(inSet(SPACE()), stopEventForFirefox)]);
    var MatrixType = typical(schema$4, NoState.init, getKeydownRules$3, getKeyupRules$3, function () {
      return Option.some(focusIn$2);
    });

    var schema$5 = [
      strict$1('selector'),
      defaulted$1('execute', defaultExecute),
      defaulted$1('moveOnTab', false)
    ];
    var execute$5 = function (component, simulatedEvent, menuConfig) {
      return menuConfig.focusManager.get(component).bind(function (focused) {
        return menuConfig.execute(component, simulatedEvent, focused);
      });
    };
    var focusIn$3 = function (component, menuConfig) {
      descendant$2(component.element(), menuConfig.selector).each(function (first) {
        menuConfig.focusManager.set(component, first);
      });
    };
    var moveUp$1 = function (element, focused, info) {
      return horizontal(element, info.selector, focused, -1);
    };
    var moveDown$1 = function (element, focused, info) {
      return horizontal(element, info.selector, focused, +1);
    };
    var fireShiftTab = function (component, simulatedEvent, menuConfig) {
      return menuConfig.moveOnTab ? move(moveUp$1)(component, simulatedEvent, menuConfig) : Option.none();
    };
    var fireTab = function (component, simulatedEvent, menuConfig) {
      return menuConfig.moveOnTab ? move(moveDown$1)(component, simulatedEvent, menuConfig) : Option.none();
    };
    var getKeydownRules$4 = constant([
      rule(inSet(UP()), move(moveUp$1)),
      rule(inSet(DOWN()), move(moveDown$1)),
      rule(and([
        isShift,
        inSet(TAB())
      ]), fireShiftTab),
      rule(and([
        isNotShift,
        inSet(TAB())
      ]), fireTab),
      rule(inSet(ENTER()), execute$5),
      rule(inSet(SPACE()), execute$5)
    ]);
    var getKeyupRules$4 = constant([rule(inSet(SPACE()), stopEventForFirefox)]);
    var MenuType = typical(schema$5, NoState.init, getKeydownRules$4, getKeyupRules$4, function () {
      return Option.some(focusIn$3);
    });

    var schema$6 = [
      onKeyboardHandler('onSpace'),
      onKeyboardHandler('onEnter'),
      onKeyboardHandler('onShiftEnter'),
      onKeyboardHandler('onLeft'),
      onKeyboardHandler('onRight'),
      onKeyboardHandler('onTab'),
      onKeyboardHandler('onShiftTab'),
      onKeyboardHandler('onUp'),
      onKeyboardHandler('onDown'),
      onKeyboardHandler('onEscape'),
      defaulted$1('stopSpaceKeyup', false),
      option('focusIn')
    ];
    var getKeydownRules$5 = function (component, simulatedEvent, specialInfo) {
      return [
        rule(inSet(SPACE()), specialInfo.onSpace),
        rule(and([
          isNotShift,
          inSet(ENTER())
        ]), specialInfo.onEnter),
        rule(and([
          isShift,
          inSet(ENTER())
        ]), specialInfo.onShiftEnter),
        rule(and([
          isShift,
          inSet(TAB())
        ]), specialInfo.onShiftTab),
        rule(and([
          isNotShift,
          inSet(TAB())
        ]), specialInfo.onTab),
        rule(inSet(UP()), specialInfo.onUp),
        rule(inSet(DOWN()), specialInfo.onDown),
        rule(inSet(LEFT()), specialInfo.onLeft),
        rule(inSet(RIGHT()), specialInfo.onRight),
        rule(inSet(SPACE()), specialInfo.onSpace),
        rule(inSet(ESCAPE()), specialInfo.onEscape)
      ];
    };
    var getKeyupRules$5 = function (component, simulatedEvent, specialInfo) {
      return specialInfo.stopSpaceKeyup ? [rule(inSet(SPACE()), stopEventForFirefox)] : [];
    };
    var SpecialType = typical(schema$6, NoState.init, getKeydownRules$5, getKeyupRules$5, function (specialInfo) {
      return specialInfo.focusIn;
    });

    var acyclic = AcyclicType.schema();
    var cyclic = CyclicType.schema();
    var flow = FlowType.schema();
    var flatgrid$1 = FlatgridType.schema();
    var matrix = MatrixType.schema();
    var execution = ExecutionType.schema();
    var menu = MenuType.schema();
    var special = SpecialType.schema();

    var KeyboardBranches = /*#__PURE__*/Object.freeze({
        acyclic: acyclic,
        cyclic: cyclic,
        flow: flow,
        flatgrid: flatgrid$1,
        matrix: matrix,
        execution: execution,
        menu: menu,
        special: special
    });

    var Keying = createModes$1({
      branchKey: 'mode',
      branches: KeyboardBranches,
      name: 'keying',
      active: {
        events: function (keyingConfig, keyingState) {
          var handler = keyingConfig.handler;
          return handler.toEvents(keyingConfig, keyingState);
        }
      },
      apis: {
        focusIn: function (component, keyConfig, keyState) {
          keyConfig.sendFocusIn(keyConfig).fold(function () {
            component.getSystem().triggerFocus(component.element(), component.element());
          }, function (sendFocusIn) {
            sendFocusIn(component, keyConfig, keyState);
          });
        },
        setGridSize: function (component, keyConfig, keyState, numRows, numColumns) {
          if (!hasKey$1(keyState, 'setGridSize')) {
            console.error('Layout does not support setGridSize');
          } else {
            keyState.setGridSize(numRows, numColumns);
          }
        }
      },
      state: KeyingState
    });

    var field$1 = function (name, forbidden) {
      return defaultedObjOf(name, {}, map$1(forbidden, function (f) {
        return forbid(f.name(), 'Cannot configure ' + f.name() + ' for ' + name);
      }).concat([state$1('dump', identity)]));
    };
    var get$6 = function (data) {
      return data.dump;
    };
    var augment = function (data, original) {
      return __assign({}, data.dump, derive$1(original));
    };
    var SketchBehaviours = {
      field: field$1,
      augment: augment,
      get: get$6
    };

    var _placeholder = 'placeholder';
    var adt$2 = Adt.generate([
      {
        single: [
          'required',
          'valueThunk'
        ]
      },
      {
        multiple: [
          'required',
          'valueThunks'
        ]
      }
    ]);
    var subPlaceholder = function (owner, detail, compSpec, placeholders) {
      if (owner.exists(function (o) {
          return o !== compSpec.owner;
        })) {
        return adt$2.single(true, constant(compSpec));
      }
      return readOptFrom$1(placeholders, compSpec.name).fold(function () {
        throw new Error('Unknown placeholder component: ' + compSpec.name + '\nKnown: [' + keys(placeholders) + ']\nNamespace: ' + owner.getOr('none') + '\nSpec: ' + Json.stringify(compSpec, null, 2));
      }, function (newSpec) {
        return newSpec.replace();
      });
    };
    var scan = function (owner, detail, compSpec, placeholders) {
      if (compSpec.uiType === _placeholder) {
        return subPlaceholder(owner, detail, compSpec, placeholders);
      } else {
        return adt$2.single(false, constant(compSpec));
      }
    };
    var substitute = function (owner, detail, compSpec, placeholders) {
      var base = scan(owner, detail, compSpec, placeholders);
      return base.fold(function (req, valueThunk) {
        var value = valueThunk(detail, compSpec.config, compSpec.validated);
        var childSpecs = readOptFrom$1(value, 'components').getOr([]);
        var substituted = bind(childSpecs, function (c) {
          return substitute(owner, detail, c, placeholders);
        });
        return [__assign({}, value, { components: substituted })];
      }, function (req, valuesThunk) {
        var values$$1 = valuesThunk(detail, compSpec.config, compSpec.validated);
        var preprocessor = compSpec.validated.preprocess.getOr(identity);
        return preprocessor(values$$1);
      });
    };
    var substituteAll = function (owner, detail, components, placeholders) {
      return bind(components, function (c) {
        return substitute(owner, detail, c, placeholders);
      });
    };
    var oneReplace = function (label, replacements) {
      var called = false;
      var used = function () {
        return called;
      };
      var replace = function () {
        if (called === true) {
          throw new Error('Trying to use the same placeholder more than once: ' + label);
        }
        called = true;
        return replacements;
      };
      var required = function () {
        return replacements.fold(function (req, _) {
          return req;
        }, function (req, _) {
          return req;
        });
      };
      return {
        name: constant(label),
        required: required,
        used: used,
        replace: replace
      };
    };
    var substitutePlaces = function (owner, detail, components, placeholders) {
      var ps = map(placeholders, function (ph, name) {
        return oneReplace(name, ph);
      });
      var outcome = substituteAll(owner, detail, components, ps);
      each(ps, function (p) {
        if (p.used() === false && p.required()) {
          throw new Error('Placeholder: ' + p.name() + ' was not found in components list\nNamespace: ' + owner.getOr('none') + '\nComponents: ' + Json.stringify(detail.components, null, 2));
        }
      });
      return outcome;
    };
    var single = adt$2.single;
    var multiple = adt$2.multiple;
    var placeholder = constant(_placeholder);

    var unique = 0;
    var generate$1 = function (prefix) {
      var date = new Date();
      var time = date.getTime();
      var random = Math.floor(Math.random() * 1000000000);
      unique++;
      return prefix + '_' + random + unique + String(time);
    };

    var adt$3 = Adt.generate([
      { required: ['data'] },
      { external: ['data'] },
      { optional: ['data'] },
      { group: ['data'] }
    ]);
    var fFactory = defaulted$1('factory', { sketch: identity });
    var fSchema = defaulted$1('schema', []);
    var fName = strict$1('name');
    var fPname = field('pname', 'pname', defaultedThunk(function (typeSpec) {
      return '<alloy.' + generate$1(typeSpec.name) + '>';
    }), anyValue$1());
    var fGroupSchema = state$1('schema', function () {
      return [option('preprocess')];
    });
    var fDefaults = defaulted$1('defaults', constant({}));
    var fOverrides = defaulted$1('overrides', constant({}));
    var requiredSpec = objOf([
      fFactory,
      fSchema,
      fName,
      fPname,
      fDefaults,
      fOverrides
    ]);
    var optionalSpec = objOf([
      fFactory,
      fSchema,
      fName,
      fPname,
      fDefaults,
      fOverrides
    ]);
    var groupSpec = objOf([
      fFactory,
      fGroupSchema,
      fName,
      strict$1('unit'),
      fPname,
      fDefaults,
      fOverrides
    ]);
    var asNamedPart = function (part) {
      return part.fold(Option.some, Option.none, Option.some, Option.some);
    };
    var name$1 = function (part) {
      var get = function (data) {
        return data.name;
      };
      return part.fold(get, get, get, get);
    };
    var convert = function (adtConstructor, partSchema) {
      return function (spec) {
        var data = asRawOrDie('Converting part type', partSchema, spec);
        return adtConstructor(data);
      };
    };
    var required = convert(adt$3.required, requiredSpec);
    var optional = convert(adt$3.optional, optionalSpec);
    var group = convert(adt$3.group, groupSpec);
    var original = constant('entirety');

    var combine = function (detail, data, partSpec, partValidated) {
      return deepMerge(data.defaults(detail, partSpec, partValidated), partSpec, { uid: detail.partUids[data.name] }, data.overrides(detail, partSpec, partValidated));
    };
    var subs = function (owner, detail, parts) {
      var internals = {};
      var externals = {};
      each$1(parts, function (part) {
        part.fold(function (data) {
          internals[data.pname] = single(true, function (detail, partSpec, partValidated) {
            return data.factory.sketch(combine(detail, data, partSpec, partValidated));
          });
        }, function (data) {
          var partSpec = detail.parts[data.name];
          externals[data.name] = constant(data.factory.sketch(combine(detail, data, partSpec[original()]), partSpec));
        }, function (data) {
          internals[data.pname] = single(false, function (detail, partSpec, partValidated) {
            return data.factory.sketch(combine(detail, data, partSpec, partValidated));
          });
        }, function (data) {
          internals[data.pname] = multiple(true, function (detail, _partSpec, _partValidated) {
            var units = detail[data.name];
            return map$1(units, function (u) {
              return data.factory.sketch(deepMerge(data.defaults(detail, u, _partValidated), u, data.overrides(detail, u)));
            });
          });
        });
      });
      return {
        internals: constant(internals),
        externals: constant(externals)
      };
    };

    var generate$2 = function (owner, parts) {
      var r = {};
      each$1(parts, function (part) {
        asNamedPart(part).each(function (np) {
          var g = doGenerateOne(owner, np.pname);
          r[np.name] = function (config) {
            var validated = asRawOrDie('Part: ' + np.name + ' in ' + owner, objOf(np.schema), config);
            return __assign({}, g, {
              config: config,
              validated: validated
            });
          };
        });
      });
      return r;
    };
    var doGenerateOne = function (owner, pname) {
      return {
        uiType: placeholder(),
        owner: owner,
        name: pname
      };
    };
    var generateOne = function (owner, pname, config) {
      return {
        uiType: placeholder(),
        owner: owner,
        name: pname,
        config: config,
        validated: {}
      };
    };
    var schemas = function (parts) {
      return bind(parts, function (part) {
        return part.fold(Option.none, Option.some, Option.none, Option.none).map(function (data) {
          return strictObjOf(data.name, data.schema.concat([snapshot$1(original())]));
        }).toArray();
      });
    };
    var names = function (parts) {
      return map$1(parts, name$1);
    };
    var substitutes = function (owner, detail, parts) {
      return subs(owner, detail, parts);
    };
    var components = function (owner, detail, internals) {
      return substitutePlaces(Option.some(owner), detail, detail.components, internals);
    };
    var getPart = function (component, detail, partKey) {
      var uid = detail.partUids[partKey];
      return component.getSystem().getByUid(uid).toOption();
    };
    var getPartOrDie = function (component, detail, partKey) {
      return getPart(component, detail, partKey).getOrDie('Could not find part: ' + partKey);
    };
    var getAllParts = function (component, detail) {
      var system = component.getSystem();
      return map(detail.partUids, function (pUid, k) {
        return constant(system.getByUid(pUid));
      });
    };
    var defaultUids = function (baseUid, partTypes) {
      var partNames = names(partTypes);
      return wrapAll$1(map$1(partNames, function (pn) {
        return {
          key: pn,
          value: baseUid + '-' + pn
        };
      }));
    };
    var defaultUidsSchema = function (partTypes) {
      return field('partUids', 'partUids', mergeWithThunk(function (spec) {
        return defaultUids(spec.uid, partTypes);
      }), anyValue$1());
    };

    var premadeTag = generate$1('alloy-premade');
    var premade = function (comp) {
      return wrap$2(premadeTag, comp);
    };
    var getPremade = function (spec) {
      return readOptFrom$1(spec, premadeTag);
    };
    var makeApi = function (f) {
      return markAsSketchApi(function (component) {
        var rest = [];
        for (var _i = 1; _i < arguments.length; _i++) {
          rest[_i - 1] = arguments[_i];
        }
        return f.apply(undefined, [component.getApis()].concat([component].concat(rest)));
      }, f);
    };

    var prefix$1 = constant('alloy-id-');
    var idAttr = constant('data-alloy-id');

    var prefix$2 = prefix$1();
    var idAttr$1 = idAttr();
    var write = function (label, elem) {
      var id = generate$1(prefix$2 + label);
      writeOnly(elem, id);
      return id;
    };
    var writeOnly = function (elem, uid) {
      Object.defineProperty(elem.dom(), idAttr$1, {
        value: uid,
        writable: true
      });
    };
    var read$2 = function (elem) {
      var id = isElement(elem) ? elem.dom()[idAttr$1] : null;
      return Option.from(id);
    };
    var generate$3 = function (prefix) {
      return generate$1(prefix);
    };

    var base = function (label, partSchemas, partUidsSchemas, spec) {
      var ps = partSchemas.length > 0 ? [strictObjOf('parts', partSchemas)] : [];
      return ps.concat([
        strict$1('uid'),
        defaulted$1('dom', {}),
        defaulted$1('components', []),
        snapshot$1('originalSpec'),
        defaulted$1('debug.sketcher', {})
      ]).concat(partUidsSchemas);
    };
    var asRawOrDie$1 = function (label, schema, spec, partSchemas, partUidsSchemas) {
      var baseS = base(label, partSchemas, partUidsSchemas, spec);
      return asRawOrDie(label + ' [SpecSchema]', objOfOnly(baseS.concat(schema)), spec);
    };

    var single$1 = function (owner, schema, factory, spec) {
      var specWithUid = supplyUid(spec);
      var detail = asRawOrDie$1(owner, schema, specWithUid, [], []);
      return factory(detail, specWithUid);
    };
    var composite = function (owner, schema, partTypes, factory, spec) {
      var specWithUid = supplyUid(spec);
      var partSchemas = schemas(partTypes);
      var partUidsSchema = defaultUidsSchema(partTypes);
      var detail = asRawOrDie$1(owner, schema, specWithUid, partSchemas, [partUidsSchema]);
      var subs = substitutes(owner, detail, partTypes);
      var components$$1 = components(owner, detail, subs.internals());
      return factory(detail, components$$1, specWithUid, subs.externals());
    };
    var supplyUid = function (spec) {
      return spec.hasOwnProperty('uid') ? spec : __assign({}, spec, { uid: generate$3('uid') });
    };

    function isSketchSpec(spec) {
      return spec.uid !== undefined;
    }
    var singleSchema = objOfOnly([
      strict$1('name'),
      strict$1('factory'),
      strict$1('configFields'),
      defaulted$1('apis', {}),
      defaulted$1('extraApis', {})
    ]);
    var compositeSchema = objOfOnly([
      strict$1('name'),
      strict$1('factory'),
      strict$1('configFields'),
      strict$1('partFields'),
      defaulted$1('apis', {}),
      defaulted$1('extraApis', {})
    ]);
    var single$2 = function (rawConfig) {
      var config = asRawOrDie('Sketcher for ' + rawConfig.name, singleSchema, rawConfig);
      var sketch = function (spec) {
        return single$1(config.name, config.configFields, config.factory, spec);
      };
      var apis = map(config.apis, makeApi);
      var extraApis = map(config.extraApis, function (f, k) {
        return markAsExtraApi(f, k);
      });
      return __assign({
        name: constant(config.name),
        partFields: constant([]),
        configFields: constant(config.configFields),
        sketch: sketch
      }, apis, extraApis);
    };
    var composite$1 = function (rawConfig) {
      var config = asRawOrDie('Sketcher for ' + rawConfig.name, compositeSchema, rawConfig);
      var sketch = function (spec) {
        return composite(config.name, config.configFields, config.partFields, config.factory, spec);
      };
      var parts = generate$2(config.name, config.partFields);
      var apis = map(config.apis, makeApi);
      var extraApis = map(config.extraApis, function (f, k) {
        return markAsExtraApi(f, k);
      });
      return __assign({
        name: constant(config.name),
        partFields: constant(config.partFields),
        configFields: constant(config.configFields),
        sketch: sketch,
        parts: constant(parts)
      }, apis, extraApis);
    };

    var factory = function (detail) {
      var events = events$2(detail.action);
      var tag = detail.dom.tag;
      var lookupAttr = function (attr) {
        return readOptFrom$1(detail.dom, 'attributes').bind(function (attrs) {
          return readOptFrom$1(attrs, attr);
        });
      };
      var getModAttributes = function () {
        if (tag === 'button') {
          var type = lookupAttr('type').getOr('button');
          var roleAttrs = lookupAttr('role').map(function (role) {
            return { role: role };
          }).getOr({});
          return __assign({ type: type }, roleAttrs);
        } else {
          var role = lookupAttr('role').getOr('button');
          return { role: role };
        }
      };
      return {
        uid: detail.uid,
        dom: detail.dom,
        components: detail.components,
        events: events,
        behaviours: SketchBehaviours.augment(detail.buttonBehaviours, [
          Focusing.config({}),
          Keying.config({
            mode: 'execution',
            useSpace: true,
            useEnter: true
          })
        ]),
        domModification: { attributes: getModAttributes() },
        eventOrder: detail.eventOrder
      };
    };
    var Button = single$2({
      name: 'Button',
      factory: factory,
      configFields: [
        defaulted$1('uid', undefined),
        strict$1('dom'),
        defaulted$1('components', []),
        SketchBehaviours.field('buttonBehaviours', [
          Focusing,
          Keying
        ]),
        option('action'),
        option('role'),
        defaulted$1('eventOrder', {})
      ]
    });

    var exhibit$2 = function (base, unselectConfig) {
      return nu$5({
        styles: {
          '-webkit-user-select': 'none',
          'user-select': 'none',
          '-ms-user-select': 'none',
          '-moz-user-select': '-moz-none'
        },
        attributes: { unselectable: 'on' }
      });
    };
    var events$4 = function (unselectConfig) {
      return derive([abort(selectstart(), constant(true))]);
    };

    var ActiveUnselecting = /*#__PURE__*/Object.freeze({
        events: events$4,
        exhibit: exhibit$2
    });

    var Unselecting = create$1({
      fields: [],
      name: 'unselecting',
      active: ActiveUnselecting
    });

    var getAttrs = function (elem) {
      var attributes = elem.dom().attributes !== undefined ? elem.dom().attributes : [];
      return foldl(attributes, function (b, attr) {
        var _a;
        if (attr.name === 'class') {
          return b;
        } else {
          return __assign({}, b, (_a = {}, _a[attr.name] = attr.value, _a));
        }
      }, {});
    };
    var getClasses = function (elem) {
      return Array.prototype.slice.call(elem.dom().classList, 0);
    };
    var fromHtml$2 = function (html) {
      var elem = Element$$1.fromHtml(html);
      var children$$1 = children(elem);
      var attrs = getAttrs(elem);
      var classes = getClasses(elem);
      var contents = children$$1.length === 0 ? {} : { innerHtml: get$3(elem) };
      return __assign({
        tag: name(elem),
        classes: classes,
        attributes: attrs
      }, contents);
    };

    var dom$2 = function (rawHtml) {
      var html = supplant(rawHtml, { prefix: Styles.prefix() });
      return fromHtml$2(html);
    };
    var spec = function (rawHtml) {
      var sDom = dom$2(rawHtml);
      return { dom: sDom };
    };

    var getAll = function () {
      return {
        'accessibility-check': '<svg width="24" height="24"><path d="M12 2a2 2 0 0 1 2 2 2 2 0 0 1-2 2 2 2 0 0 1-2-2c0-1.1.9-2 2-2zm8 7h-5v12c0 .6-.4 1-1 1a1 1 0 0 1-1-1v-5c0-.6-.4-1-1-1a1 1 0 0 0-1 1v5c0 .6-.4 1-1 1a1 1 0 0 1-1-1V9H4a1 1 0 1 1 0-2h16c.6 0 1 .4 1 1s-.4 1-1 1z" fill-rule="nonzero"/></svg>',
        'align-center': '<svg width="24" height="24"><path d="M5 5h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 1 1 0-2zm3 4h8c.6 0 1 .4 1 1s-.4 1-1 1H8a1 1 0 1 1 0-2zm0 8h8c.6 0 1 .4 1 1s-.4 1-1 1H8a1 1 0 0 1 0-2zm-3-4h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 0 1 0-2z" fill-rule="evenodd"/></svg>',
        'align-justify': '<svg width="24" height="24"><path d="M5 5h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 1 1 0-2zm0 4h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 1 1 0-2zm0 4h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 0 1 0-2zm0 4h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 0 1 0-2z" fill-rule="evenodd"/></svg>',
        'align-left': '<svg width="24" height="24"><path d="M5 5h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 1 1 0-2zm0 4h8c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 1 1 0-2zm0 8h8c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 0 1 0-2zm0-4h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 0 1 0-2z" fill-rule="evenodd"/></svg>',
        'align-none': '<svg width="24" height="24"><path d="M14.2 5L13 7H5a1 1 0 1 1 0-2h9.2zm4 0h.8a1 1 0 0 1 0 2h-2l1.2-2zm-6.4 4l-1.2 2H5a1 1 0 0 1 0-2h6.8zm4 0H19a1 1 0 0 1 0 2h-4.4l1.2-2zm-6.4 4l-1.2 2H5a1 1 0 0 1 0-2h4.4zm4 0H19a1 1 0 0 1 0 2h-6.8l1.2-2zM7 17l-1.2 2H5a1 1 0 0 1 0-2h2zm4 0h8a1 1 0 0 1 0 2H9.8l1.2-2zm5.2-13.5l1.3.7-9.7 16.3-1.3-.7 9.7-16.3z" fill-rule="evenodd"/></svg>',
        'align-right': '<svg width="24" height="24"><path d="M5 5h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 1 1 0-2zm6 4h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm0 8h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm-6-4h14c.6 0 1 .4 1 1s-.4 1-1 1H5a1 1 0 0 1 0-2z" fill-rule="evenodd"/></svg>',
        'arrow-left': '<svg width="24" height="24"><path d="M5.6 13l12 6a1 1 0 0 0 1.4-1V6a1 1 0 0 0-1.4-.9l-12 6a1 1 0 0 0 0 1.8z" fill-rule="evenodd"/></svg>',
        'arrow-right': '<svg width="24" height="24"><path d="M18.5 13l-12 6A1 1 0 0 1 5 18V6a1 1 0 0 1 1.4-.9l12 6a1 1 0 0 1 0 1.8z" fill-rule="evenodd"/></svg>',
        'bold': '<svg width="24" height="24"><path d="M7.8 19c-.3 0-.5 0-.6-.2l-.2-.5V5.7c0-.2 0-.4.2-.5l.6-.2h5c1.5 0 2.7.3 3.5 1 .7.6 1.1 1.4 1.1 2.5a3 3 0 0 1-.6 1.9c-.4.6-1 1-1.6 1.2.4.1.9.3 1.3.6s.8.7 1 1.2c.4.4.5 1 .5 1.6 0 1.3-.4 2.3-1.3 3-.8.7-2.1 1-3.8 1H7.8zm5-8.3c.6 0 1.2-.1 1.6-.5.4-.3.6-.7.6-1.3 0-1.1-.8-1.7-2.3-1.7H9.3v3.5h3.4zm.5 6c.7 0 1.3-.1 1.7-.4.4-.4.6-.9.6-1.5s-.2-1-.7-1.4c-.4-.3-1-.4-2-.4H9.4v3.8h4z" fill-rule="evenodd"/></svg>',
        'bookmark': '<svg width="24" height="24"><path d="M6 4v17l6-4 6 4V4c0-.6-.4-1-1-1H7a1 1 0 0 0-1 1z" fill-rule="nonzero"/></svg>',
        'border-width': '<svg width="24" height="24"><path d="M5 14.8h14a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2zm-.5 3.7h15c.3 0 .5.2.5.5s-.2.5-.5.5h-15a.5.5 0 1 1 0-1zm.5-8.3h14c.6 0 1 .4 1 1v1c0 .5-.4 1-1 1H5a1 1 0 0 1-1-1v-1c0-.6.4-1 1-1zm0-5.7h14c.6 0 1 .4 1 1v2c0 .6-.4 1-1 1H5a1 1 0 0 1-1-1v-2c0-.6.4-1 1-1z" fill-rule="evenodd"/></svg>',
        'brightness': '<svg width="24" height="24"><path d="M12 17c.3 0 .5.1.7.3.2.2.3.4.3.7v1c0 .3-.1.5-.3.7a1 1 0 0 1-.7.3 1 1 0 0 1-.7-.3 1 1 0 0 1-.3-.7v-1c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3zm0-10a1 1 0 0 1-.7-.3A1 1 0 0 1 11 6V5c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3.3 0 .5.1.7.3.2.2.3.4.3.7v1c0 .3-.1.5-.3.7a1 1 0 0 1-.7.3zm7 4c.3 0 .5.1.7.3.2.2.3.4.3.7 0 .3-.1.5-.3.7a1 1 0 0 1-.7.3h-1a1 1 0 0 1-.7-.3 1 1 0 0 1-.3-.7c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3h1zM7 12c0 .3-.1.5-.3.7a1 1 0 0 1-.7.3H5a1 1 0 0 1-.7-.3A1 1 0 0 1 4 12c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3h1c.3 0 .5.1.7.3.2.2.3.4.3.7zm10 3.5l.7.8c.2.1.3.4.3.6 0 .3-.1.6-.3.8a1 1 0 0 1-.8.3 1 1 0 0 1-.6-.3l-.8-.7a1 1 0 0 1-.3-.8c0-.2.1-.5.3-.7a1 1 0 0 1 1.4 0zm-10-7l-.7-.8a1 1 0 0 1-.3-.6c0-.3.1-.6.3-.8.2-.2.5-.3.8-.3.2 0 .5.1.7.3l.7.7c.2.2.3.5.3.8 0 .2-.1.5-.3.7a1 1 0 0 1-.7.3 1 1 0 0 1-.8-.3zm10 0a1 1 0 0 1-.8.3 1 1 0 0 1-.7-.3 1 1 0 0 1-.3-.7c0-.3.1-.6.3-.8l.8-.7c.1-.2.4-.3.6-.3.3 0 .6.1.8.3.2.2.3.5.3.8 0 .2-.1.5-.3.7l-.7.7zm-10 7c.2-.2.5-.3.8-.3.2 0 .5.1.7.3a1 1 0 0 1 0 1.4l-.8.8a1 1 0 0 1-.6.3 1 1 0 0 1-.8-.3 1 1 0 0 1-.3-.8c0-.2.1-.5.3-.6l.7-.8zM12 8a4 4 0 0 1 3.7 2.4 4 4 0 0 1 0 3.2A4 4 0 0 1 12 16a4 4 0 0 1-3.7-2.4 4 4 0 0 1 0-3.2A4 4 0 0 1 12 8zm0 6.5c.7 0 1.3-.2 1.8-.7.5-.5.7-1.1.7-1.8s-.2-1.3-.7-1.8c-.5-.5-1.1-.7-1.8-.7s-1.3.2-1.8.7c-.5.5-.7 1.1-.7 1.8s.2 1.3.7 1.8c.5.5 1.1.7 1.8.7z" fill-rule="evenodd"/></svg>',
        'browse': '<svg width="24" height="24"><path d="M19 4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4v-2h4V8H5v10h4v2H5a2 2 0 0 1-2-2V6c0-1.1.9-2 2-2h14zm-8 9.4l-2.3 2.3a1 1 0 1 1-1.4-1.4l4-4a1 1 0 0 1 1.4 0l4 4a1 1 0 0 1-1.4 1.4L13 13.4V20a1 1 0 0 1-2 0v-6.6z" fill-rule="nonzero"/></svg>',
        'cancel': '<svg width="24" height="24"><path d="M12 4.6a7.4 7.4 0 1 1 0 14.8 7.4 7.4 0 0 1 0-14.8zM12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm0 8L14.8 8l1 1.1-2.7 2.8 2.7 2.7-1.1 1.1-2.7-2.7-2.7 2.7-1-1.1 2.6-2.7-2.7-2.7 1-1.1 2.8 2.7z" fill-rule="nonzero"/></svg>',
        'change-case': '<svg width="24" height="24"><path d="M18.4 18.2v-.6c-.5.8-1.3 1.2-2.4 1.2-2.2 0-3.3-1.6-3.3-4.8 0-3.1 1-4.7 3.3-4.7 1.1 0 1.8.3 2.4 1.1v-.6c0-.5.4-.8.8-.8s.8.3.8.8v8.4c0 .5-.4.8-.8.8a.8.8 0 0 1-.8-.8zm-2-7.4c-1.3 0-1.8.9-1.8 3.2 0 2.4.5 3.3 1.7 3.3 1.3 0 1.8-.9 1.8-3.2 0-2.4-.5-3.3-1.7-3.3zM10 15.7H5.5l-.8 2.6a1 1 0 0 1-1 .7h-.2a.7.7 0 0 1-.7-1l4-12a1 1 0 1 1 2 0l4 12a.7.7 0 0 1-.8 1h-.2a1 1 0 0 1-1-.7l-.8-2.6zm-.3-1.5l-2-6.5-1.9 6.5h3.9z" fill-rule="evenodd"/></svg>',
        'character-count': '<svg width="24" height="24"><path d="M4 11.5h16v1H4v-1zm4.8-6.8V10H7.7V5.8h-1v-1h2zM11 8.3V9h2v1h-3V7.7l2-1v-.9h-2v-1h3v2.4l-2 1zm6.3-3.4V10h-3.1V9h2.1V8h-2.1V6.8h2.1v-1h-2.1v-1h3.1zM5.8 16.4c0-.5.2-.8.5-1 .2-.2.6-.3 1.2-.3l.8.1c.2 0 .4.2.5.3l.4.4v2.8l.2.3H8.2v-.1-.2l-.6.3H7c-.4 0-.7 0-1-.2a1 1 0 0 1-.3-.9c0-.3 0-.6.3-.8.3-.2.7-.4 1.2-.4l.6-.2h.3v-.2l-.1-.2a.8.8 0 0 0-.5-.1 1 1 0 0 0-.4 0l-.3.4h-1zm2.3.8h-.2l-.2.1-.4.1a1 1 0 0 0-.4.2l-.2.2.1.3.5.1h.4l.4-.4v-.6zm2-3.4h1.2v1.7l.5-.3h.5c.5 0 .9.1 1.2.5.3.4.5.8.5 1.4 0 .6-.2 1.1-.5 1.5-.3.4-.7.6-1.3.6l-.6-.1-.4-.4v.4h-1.1v-5.4zm1.1 3.3c0 .3 0 .6.2.8a.7.7 0 0 0 1.2 0l.2-.8c0-.4 0-.6-.2-.8a.7.7 0 0 0-.6-.3l-.6.3-.2.8zm6.1-.5c0-.2 0-.3-.2-.4a.8.8 0 0 0-.5-.2c-.3 0-.5.1-.6.3l-.2.9c0 .3 0 .6.2.8.1.2.3.3.6.3.2 0 .4 0 .5-.2l.2-.4h1.1c0 .5-.3.8-.6 1.1a2 2 0 0 1-1.3.4c-.5 0-1-.2-1.3-.6a2 2 0 0 1-.5-1.4c0-.6.1-1.1.5-1.5.3-.4.8-.5 1.4-.5.5 0 1 0 1.2.3.4.3.5.7.5 1.2h-1v-.1z" fill-rule="evenodd"/></svg>',
        'checklist': '<svg width="24" height="24"><path d="M11 17h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm0-6h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm0-6h8a1 1 0 0 1 0 2h-8a1 1 0 0 1 0-2zM7.2 16c.2-.4.6-.5.9-.3.3.2.4.6.2 1L6 20c-.2.3-.7.4-1 0l-1.3-1.3a.7.7 0 0 1 0-1c.3-.2.7-.2 1 0l.7.9 1.7-2.8zm0-6c.2-.4.6-.5.9-.3.3.2.4.6.2 1L6 14c-.2.3-.7.4-1 0l-1.3-1.3a.7.7 0 0 1 0-1c.3-.2.7-.2 1 0l.7.9 1.7-2.8zm0-6c.2-.4.6-.5.9-.3.3.2.4.6.2 1L6 8c-.2.3-.7.4-1 0L3.8 6.9a.7.7 0 0 1 0-1c.3-.2.7-.2 1 0l.7.9 1.7-2.8z" fill-rule="evenodd"/></svg>',
        'checkmark': '<svg width="24" height="24"><path d="M18.2 5.4a1 1 0 0 1 1.6 1.2l-8 12a1 1 0 0 1-1.5.1l-5-5a1 1 0 1 1 1.4-1.4l4.1 4.1 7.4-11z" fill-rule="nonzero"/></svg>',
        'chevron-down': '<svg width="10" height="10"><path d="M8.7 2.2c.3-.3.8-.3 1 0 .4.4.4.9 0 1.2L5.7 7.8c-.3.3-.9.3-1.2 0L.2 3.4a.8.8 0 0 1 0-1.2c.3-.3.8-.3 1.1 0L5 6l3.7-3.8z" fill-rule="nonzero"/></svg>',
        'chevron-left': '<svg width="10" height="10"><path d="M7.8 1.3L4 5l3.8 3.7c.3.3.3.8 0 1-.4.4-.9.4-1.2 0L2.2 5.7a.8.8 0 0 1 0-1.2L6.6.2C7 0 7.4 0 7.8.2c.3.3.3.8 0 1.1z" fill-rule="nonzero"/></svg>',
        'chevron-right': '<svg width="10" height="10"><path d="M2.2 1.3a.8.8 0 0 1 0-1c.4-.4.9-.4 1.2 0l4.4 4.1c.3.4.3.9 0 1.2L3.4 9.8c-.3.3-.8.3-1.2 0a.8.8 0 0 1 0-1.1L6 5 2.2 1.3z" fill-rule="nonzero"/></svg>',
        'chevron-up': '<svg width="10" height="10"><path d="M8.7 7.8L5 4 1.3 7.8c-.3.3-.8.3-1 0a.8.8 0 0 1 0-1.2l4.1-4.4c.3-.3.9-.3 1.2 0l4.2 4.4c.3.3.3.9 0 1.2-.3.3-.8.3-1.1 0z" fill-rule="nonzero"/></svg>',
        'close': '<svg width="24" height="24"><path d="M17.3 8.2L13.4 12l3.9 3.8a1 1 0 0 1-1.5 1.5L12 13.4l-3.8 3.9a1 1 0 0 1-1.5-1.5l3.9-3.8-3.9-3.8a1 1 0 0 1 1.5-1.5l3.8 3.9 3.8-3.9a1 1 0 0 1 1.5 1.5z" fill-rule="evenodd"/></svg>',
        'code-sample': '<svg width="24" height="26"><path d="M7.1 11a2.8 2.8 0 0 1-.8 2 2.8 2.8 0 0 1 .8 2v1.7c0 .3.1.6.4.8.2.3.5.4.8.4.3 0 .4.2.4.4v.8c0 .2-.1.4-.4.4-.7 0-1.4-.3-2-.8-.5-.6-.8-1.3-.8-2V15c0-.3-.1-.6-.4-.8-.2-.3-.5-.4-.8-.4a.4.4 0 0 1-.4-.4v-.8c0-.2.2-.4.4-.4.3 0 .6-.1.8-.4.3-.2.4-.5.4-.8V9.3c0-.7.3-1.4.8-2 .6-.5 1.3-.8 2-.8.3 0 .4.2.4.4v.8c0 .2-.1.4-.4.4-.3 0-.6.1-.8.4-.3.2-.4.5-.4.8V11zm9.8 0V9.3c0-.3-.1-.6-.4-.8-.2-.3-.5-.4-.8-.4a.4.4 0 0 1-.4-.4V7c0-.2.1-.4.4-.4.7 0 1.4.3 2 .8.5.6.8 1.3.8 2V11c0 .3.1.6.4.8.2.3.5.4.8.4.2 0 .4.2.4.4v.8c0 .2-.2.4-.4.4-.3 0-.6.1-.8.4-.3.2-.4.5-.4.8v1.7c0 .7-.3 1.4-.8 2-.6.5-1.3.8-2 .8a.4.4 0 0 1-.4-.4v-.8c0-.2.1-.4.4-.4.3 0 .6-.1.8-.4.3-.2.4-.5.4-.8V15a2.8 2.8 0 0 1 .8-2 2.8 2.8 0 0 1-.8-2zm-3.3-.4c0 .4-.1.8-.5 1.1-.3.3-.7.5-1.1.5-.4 0-.8-.2-1.1-.5-.4-.3-.5-.7-.5-1.1 0-.5.1-.9.5-1.2.3-.3.7-.4 1.1-.4.4 0 .8.1 1.1.4.4.3.5.7.5 1.2zM12 13c.4 0 .8.1 1.1.5.4.3.5.7.5 1.1 0 1-.1 1.6-.5 2a3 3 0 0 1-1.1 1c-.4.3-.8.4-1.1.4a.5.5 0 0 1-.5-.5V17a3 3 0 0 0 1-.2l.6-.6c-.6 0-1-.2-1.3-.5-.2-.3-.3-.7-.3-1 0-.5.1-1 .5-1.2.3-.4.7-.5 1.1-.5z" fill-rule="evenodd"/></svg>',
        'color-levels': '<svg width="24" height="24"><path d="M17.5 11.4A9 9 0 0 1 18 14c0 .5 0 1-.2 1.4 0 .4-.3.9-.5 1.3a6.2 6.2 0 0 1-3.7 3 5.7 5.7 0 0 1-3.2 0A5.9 5.9 0 0 1 7.6 18a6.2 6.2 0 0 1-1.4-2.6 6.7 6.7 0 0 1 0-2.8c0-.4.1-.9.3-1.3a13.6 13.6 0 0 1 2.3-4A20 20 0 0 1 12 4a26.4 26.4 0 0 1 3.2 3.4 18.2 18.2 0 0 1 2.3 4zm-2 4.5c.4-.7.5-1.4.5-2a7.3 7.3 0 0 0-1-3.2c.2.6.2 1.2.2 1.9a4.5 4.5 0 0 1-1.3 3 5.3 5.3 0 0 1-2.3 1.5 4.9 4.9 0 0 1-2 .1 4.3 4.3 0 0 0 2.4.8 4 4 0 0 0 2-.6 4 4 0 0 0 1.5-1.5z" fill-rule="evenodd"/></svg>',
        'color-picker': '<svg width="24" height="24"><path d="M12 3a9 9 0 0 0 0 18 1.5 1.5 0 0 0 1.1-2.5c-.2-.3-.4-.6-.4-1 0-.8.7-1.5 1.5-1.5H16a5 5 0 0 0 5-5c0-4.4-4-8-9-8zm-5.5 9a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm3-4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm3 4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z" fill-rule="nonzero"/></svg>',
        'color-swatch-remove-color': '<svg width="24" height="24"><path stroke="#000" stroke-width="2" d="M21 3L3 21" fill-rule="evenodd"/></svg>',
        'color-swatch': '<svg width="24" height="24"><rect x="3" y="3" width="18" height="18" rx="1" fill-rule="evenodd"/></svg>',
        'comment': '<svg width="24" height="24"><path d="M9 19l3-2h7c.6 0 1-.4 1-1V6c0-.6-.4-1-1-1H5a1 1 0 0 0-1 1v10c0 .6.4 1 1 1h4v2zm-2 4v-4H5a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-6.4L7 23z" fill-rule="nonzero"/></svg>',
        'contrast': '<svg width="24" height="24"><path d="M12 4a7.8 7.8 0 0 1 5.7 2.3A8 8 0 1 1 12 4zm-6 8a6 6 0 0 0 6 6V6a6 6 0 0 0-6 6z" fill-rule="evenodd"/></svg>',
        'copy': '<svg width="24" height="24"><path d="M16 3H6a2 2 0 0 0-2 2v11h2V5h10V3zm1 4a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2V9c0-1.2.9-2 2-2h7zm0 12V9h-7v10h7z" fill-rule="nonzero"/></svg>',
        'crop': '<svg width="24" height="24"><path d="M17 8v7h2c.6 0 1 .4 1 1s-.4 1-1 1h-2v2c0 .6-.4 1-1 1a1 1 0 0 1-1-1v-2H7V9H5a1 1 0 1 1 0-2h2V5c0-.6.4-1 1-1s1 .4 1 1v2h7l3-3 1 1-3 3zM9 9v5l5-5H9zm1 6h5v-5l-5 5z" fill-rule="evenodd"/></svg>',
        'cut': '<svg width="24" height="24"><path d="M18 15c.6.7 1 1.4 1 2.3 0 .8-.2 1.5-.7 2l-.8.5-1 .2c-.4 0-.8 0-1.2-.3a3.9 3.9 0 0 1-2.1-2.2c-.2-.5-.3-1-.2-1.5l-1-1-1 1c0 .5 0 1-.2 1.5-.1.5-.4 1-.9 1.4-.3.4-.7.6-1.2.8l-1.2.3c-.4 0-.7 0-1-.2-.3 0-.6-.3-.8-.5-.5-.5-.8-1.2-.7-2 0-.9.4-1.6 1-2.2A3.7 3.7 0 0 1 8.6 14H9l1-1-4-4-.5-1a3.3 3.3 0 0 1 0-2c0-.4.3-.7.5-1l6 6 6-6 .5 1a3.3 3.3 0 0 1 0 2c0 .4-.3.7-.5 1l-4 4 1 1h.5c.4 0 .8 0 1.2.3.5.2.9.4 1.2.8zm-8.5 2.2l.1-.4v-.3-.4a1 1 0 0 0-.2-.5 1 1 0 0 0-.4-.2 1.6 1.6 0 0 0-.8 0 2.6 2.6 0 0 0-.8.3 2.5 2.5 0 0 0-.9 1.1l-.1.4v.7l.2.5.5.2h.7a2.5 2.5 0 0 0 .8-.3 2.8 2.8 0 0 0 1-1zm2.5-2.8c.4 0 .7-.1 1-.4.3-.3.4-.6.4-1s-.1-.7-.4-1c-.3-.3-.6-.4-1-.4s-.7.1-1 .4c-.3.3-.4.6-.4 1s.1.7.4 1c.3.3.6.4 1 .4zm5.4 4l.2-.5v-.4-.3a2.6 2.6 0 0 0-.3-.8 2.4 2.4 0 0 0-.7-.7 2.5 2.5 0 0 0-.8-.3 1.5 1.5 0 0 0-.8 0 1 1 0 0 0-.4.2 1 1 0 0 0-.2.5 1.5 1.5 0 0 0 0 .7v.4l.3.4.3.4a2.8 2.8 0 0 0 .8.5l.4.1h.7l.5-.2z" fill-rule="evenodd"/></svg>',
        'document-properties': '<svg width="24" height="24"><path d="M14.4 3H7a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h10a2 2 0 0 0 2-2V7.6L14.4 3zM17 19H7V5h6v4h4v10z" fill-rule="nonzero"/></svg>',
        'drag': '<svg width="24" height="24"><path d="M13 5h2v2h-2V5zm0 4h2v2h-2V9zM9 9h2v2H9V9zm4 4h2v2h-2v-2zm-4 0h2v2H9v-2zm0 4h2v2H9v-2zm4 0h2v2h-2v-2zM9 5h2v2H9V5z" fill-rule="evenodd"/></svg>',
        'duplicate': '<svg width="24" height="24"><g fill-rule="nonzero"><path d="M16 3v2H6v11H4V5c0-1.1.9-2 2-2h10zm3 8h-2V9h-7v10h9a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2V9c0-1.2.9-2 2-2h7a2 2 0 0 1 2 2v2z"/><path d="M17 14h1a1 1 0 0 1 0 2h-1v1a1 1 0 0 1-2 0v-1h-1a1 1 0 0 1 0-2h1v-1a1 1 0 0 1 2 0v1z"/></g></svg>',
        'edit-image': '<svg width="24" height="24"><path d="M18 16h2V7a2 2 0 0 0-2-2H7v2h11v9zM6 17h15a1 1 0 0 1 0 2h-1v1a1 1 0 0 1-2 0v-1H6a2 2 0 0 1-2-2V7H3a1 1 0 1 1 0-2h1V4a1 1 0 1 1 2 0v13zm3-5.3l1.3 2 3-4.7 3.7 6H7l2-3.3z" fill-rule="nonzero"/></svg>',
        'embed-page': '<svg width="24" height="24"><path d="M19 6V5H5v14h2A13 13 0 0 1 19 6zm0 1.4c-.8.8-1.6 2.4-2.2 4.6H19V7.4zm0 5.6h-2.4c-.4 1.8-.6 3.8-.6 6h3v-6zm-4 6c0-2.2.2-4.2.6-6H13c-.7 1.8-1.1 3.8-1.1 6h3zm-4 0c0-2.2.4-4.2 1-6H9.6A12 12 0 0 0 8 19h3zM4 3h16c.6 0 1 .4 1 1v16c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V4c0-.6.4-1 1-1zm11.8 9c.4-1.9 1-3.4 1.8-4.5a9.2 9.2 0 0 0-4 4.5h2.2zm-3.4 0a12 12 0 0 1 2.8-4 12 12 0 0 0-5 4h2.2z" fill-rule="nonzero"/></svg>',
        'embed': '<svg width="24" height="24"><path d="M4 3h16c.6 0 1 .4 1 1v16c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V4c0-.6.4-1 1-1zm1 2v14h14V5H5zm4.8 2.6l5.6 4a.5.5 0 0 1 0 .8l-5.6 4A.5.5 0 0 1 9 16V8a.5.5 0 0 1 .8-.4z" fill-rule="nonzero"/></svg>',
        'emoji': '<svg width="24" height="24"><path d="M9 11c.6 0 1-.4 1-1s-.4-1-1-1a1 1 0 0 0-1 1c0 .6.4 1 1 1zm6 0c.6 0 1-.4 1-1s-.4-1-1-1a1 1 0 0 0-1 1c0 .6.4 1 1 1zm-3 5.5c2.1 0 4-1.5 4.4-3.5H7.6c.5 2 2.3 3.5 4.4 3.5zM12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm0 14.5a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13z" fill-rule="nonzero"/></svg>',
        'fill': '<svg width="24" height="26"><path d="M16.6 12l-9-9-1.4 1.4 2.4 2.4-5.2 5.1c-.5.6-.5 1.6 0 2.2L9 19.6a1.5 1.5 0 0 0 2.2 0l5.5-5.5c.5-.6.5-1.6 0-2.2zM5.2 13L10 8.2l4.8 4.8H5.2zM19 14.5s-2 2.2-2 3.5c0 1.1.9 2 2 2a2 2 0 0 0 2-2c0-1.3-2-3.5-2-3.5z" fill-rule="nonzero"/></svg>',
        'flip-horizontally': '<svg width="24" height="24"><path d="M14 19h2v-2h-2v2zm4-8h2V9h-2v2zM4 7v10c0 1.1.9 2 2 2h3v-2H6V7h3V5H6a2 2 0 0 0-2 2zm14-2v2h2a2 2 0 0 0-2-2zm-7 16h2V3h-2v18zm7-6h2v-2h-2v2zm-4-8h2V5h-2v2zm4 12a2 2 0 0 0 2-2h-2v2z" fill-rule="nonzero"/></svg>',
        'flip-vertically': '<svg width="24" height="24"><path d="M5 14v2h2v-2H5zm8 4v2h2v-2h-2zm4-14H7a2 2 0 0 0-2 2v3h2V6h10v3h2V6a2 2 0 0 0-2-2zm2 14h-2v2a2 2 0 0 0 2-2zM3 11v2h18v-2H3zm6 7v2h2v-2H9zm8-4v2h2v-2h-2zM5 18c0 1.1.9 2 2 2v-2H5z" fill-rule="nonzero"/></svg>',
        'format-painter': '<svg width="24" height="24"><path d="M18 5V4c0-.5-.4-1-1-1H5a1 1 0 0 0-1 1v4c0 .6.5 1 1 1h12c.6 0 1-.4 1-1V7h1v4H9v9c0 .6.4 1 1 1h2c.6 0 1-.4 1-1v-7h8V5h-3z" fill-rule="nonzero"/></svg>',
        'fullscreen': '<svg width="24" height="24"><path d="M15.3 10l-1.2-1.3 2.9-3h-2.3a.9.9 0 1 1 0-1.7H19c.5 0 .9.4.9.9v4.4a.9.9 0 1 1-1.8 0V7l-2.9 3zm0 4l3 3v-2.3a.9.9 0 1 1 1.7 0V19c0 .5-.4.9-.9.9h-4.4a.9.9 0 1 1 0-1.8H17l-3-2.9 1.3-1.2zM10 15.4l-2.9 3h2.3a.9.9 0 1 1 0 1.7H5a.9.9 0 0 1-.9-.9v-4.4a.9.9 0 1 1 1.8 0V17l2.9-3 1.2 1.3zM8.7 10L5.7 7v2.3a.9.9 0 0 1-1.7 0V5c0-.5.4-.9.9-.9h4.4a.9.9 0 0 1 0 1.8H7l3 2.9-1.3 1.2z" fill-rule="nonzero"/></svg>',
        'gamma': '<svg width="24" height="24"><path d="M4 3h16c.6 0 1 .4 1 1v16c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V4c0-.6.4-1 1-1zm1 2v14h14V5H5zm6.5 11.8V14L9.2 8.7a5.1 5.1 0 0 0-.4-.8l-.1-.2H8 8v-1l.3-.1.3-.1h.7a1 1 0 0 1 .6.5l.1.3a8.5 8.5 0 0 1 .3.6l1.9 4.6 2-5.2a1 1 0 0 1 1-.6.5.5 0 0 1 .5.6L13 14v2.8a.7.7 0 0 1-1.4 0z" fill-rule="nonzero"/></svg>',
        'help': '<svg width="24" height="24"><g fill-rule="evenodd"><path d="M12 5.5a6.5 6.5 0 0 0-6 9 6.3 6.3 0 0 0 1.4 2l1 1a6.3 6.3 0 0 0 3.6 1 6.5 6.5 0 0 0 6-9 6.3 6.3 0 0 0-1.4-2l-1-1a6.3 6.3 0 0 0-3.6-1zM12 4a7.8 7.8 0 0 1 5.7 2.3A8 8 0 1 1 12 4z"/><path d="M9.6 9.7a.7.7 0 0 1-.7-.8c0-1.1 1.5-1.8 3.2-1.8 1.8 0 3.2.8 3.2 2.4 0 1.4-.4 2.1-1.5 2.8-.2 0-.3.1-.3.2a2 2 0 0 0-.8.8.8.8 0 0 1-1.4-.6c.3-.7.8-1 1.3-1.5l.4-.2c.7-.4.8-.6.8-1.5 0-.5-.6-.9-1.7-.9-.5 0-1 .1-1.4.3-.2 0-.3.1-.3.2v-.2c0 .4-.4.8-.8.8z" fill-rule="nonzero"/><circle cx="12" cy="16" r="1"/></g></svg>',
        'highlight-bg-color': '<svg width="24" height="24"><g fill-rule="evenodd"><path id="tox-icon-highlight-bg-color__color" d="M3 18h18v3H3z"/><path fill-rule="nonzero" d="M7.7 16.7H3l3.3-3.3-.7-.8L10.2 8l4 4.1-4 4.2c-.2.2-.6.2-.8 0l-.6-.7-1.1 1.1zm5-7.5L11 7.4l3-2.9a2 2 0 0 1 2.6 0L18 6c.7.7.7 2 0 2.7l-2.9 2.9-1.8-1.8-.5-.6"/></g></svg>',
        'home': '<svg width="24" height="24"><path fill-rule="nonzero" d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
        'horizontal-rule': '<svg width="24" height="24"><path d="M4 11h16v2H4z" fill-rule="evenodd"/></svg>',
        'image-options': '<svg width="24" height="24"><path d="M6 10a2 2 0 0 0-2 2c0 1.1.9 2 2 2a2 2 0 0 0 2-2 2 2 0 0 0-2-2zm12 0a2 2 0 0 0-2 2c0 1.1.9 2 2 2a2 2 0 0 0 2-2 2 2 0 0 0-2-2zm-6 0a2 2 0 0 0-2 2c0 1.1.9 2 2 2a2 2 0 0 0 2-2 2 2 0 0 0-2-2z" fill-rule="nonzero"/></svg>',
        'image': '<svg width="24" height="24"><path d="M5 15.7l3.3-3.2c.3-.3.7-.3 1 0L12 15l4.1-4c.3-.4.8-.4 1 0l2 1.9V5H5v10.7zM5 18V19h3l2.8-2.9-2-2L5 17.9zm14-3l-2.5-2.4-6.4 6.5H19v-4zM4 3h16c.6 0 1 .4 1 1v16c0 .6-.4 1-1 1H4a1 1 0 0 1-1-1V4c0-.6.4-1 1-1zm6 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" fill-rule="nonzero"/></svg>',
        'indent': '<svg width="24" height="24"><path d="M7 5h12c.6 0 1 .4 1 1s-.4 1-1 1H7a1 1 0 1 1 0-2zm5 4h7c.6 0 1 .4 1 1s-.4 1-1 1h-7a1 1 0 0 1 0-2zm0 4h7c.6 0 1 .4 1 1s-.4 1-1 1h-7a1 1 0 0 1 0-2zm-5 4h12a1 1 0 0 1 0 2H7a1 1 0 0 1 0-2zm-2.6-3.8L6.2 12l-1.8-1.2a1 1 0 0 1 1.2-1.6l3 2a1 1 0 0 1 0 1.6l-3 2a1 1 0 1 1-1.2-1.6z" fill-rule="evenodd"/></svg>',
        'indeterminate': '<svg width="24" height="24"><path d="M12 21a9 9 0 1 1 0-18 9 9 0 0 1 0 18zM9 11a1 1 0 0 0 0 2h6a1 1 0 0 0 0-2H9z" fill-rule="evenodd"/></svg>',
        'info': '<svg width="24" height="24"><path d="M12 4a7.8 7.8 0 0 1 5.7 2.3A8 8 0 1 1 12 4zm-1 3v2h2V7h-2zm3 10v-1h-1v-5h-3v1h1v4h-1v1h4z" fill-rule="evenodd"/></svg>',
        'insert-character': '<svg width="24" height="24"><path d="M15 18h4l1-2v4h-6v-3.3l1.4-1a6 6 0 0 0 1.8-2.9 6.3 6.3 0 0 0-.1-4.1 5.8 5.8 0 0 0-3-3.2c-.6-.3-1.3-.5-2.1-.5a5.1 5.1 0 0 0-3.9 1.8 6.3 6.3 0 0 0-1.3 6 6.2 6.2 0 0 0 1.8 3l1.4.9V20H4v-4l1 2h4v-.5l-2-1L5.4 15A6.5 6.5 0 0 1 4 11c0-1 .2-1.9.6-2.7A7 7 0 0 1 6.3 6C7.1 5.4 8 5 9 4.5c1-.3 2-.5 3.1-.5a8.8 8.8 0 0 1 5.7 2 7 7 0 0 1 1.7 2.3 6 6 0 0 1 .2 4.8c-.2.7-.6 1.3-1 1.9a7.6 7.6 0 0 1-3.6 2.5v.5z" fill-rule="evenodd"/></svg>',
        'insert-time': '<svg width="24" height="24"><g fill-rule="nonzero"><path d="M19 2H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V5a3 3 0 0 0-3-3zm-7 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/><path d="M15 12h-3V7a.5.5 0 0 0-1 0v6h4a.5.5 0 0 0 0-1z"/></g></svg>',
        'invert': '<svg width="24" height="24"><path d="M18 19.3L16.5 18a5.8 5.8 0 0 1-3.1 1.9 6.1 6.1 0 0 1-5.5-1.6A5.8 5.8 0 0 1 6 14v-.3l.1-1.2A13.9 13.9 0 0 1 7.7 9l-3-3 .7-.8 2.8 2.9 9 8.9 1.5 1.6-.7.6zm0-5.5v.3l-.1 1.1-.4 1-1.2-1.2a4.3 4.3 0 0 0 .2-1v-.2c0-.4 0-.8-.2-1.3l-.5-1.4a14.8 14.8 0 0 0-3-4.2L12 6a26.1 26.1 0 0 0-2.2 2.5l-1-1a20.9 20.9 0 0 1 2.9-3.3L12 4l1 .8a22.2 22.2 0 0 1 4 5.4c.6 1.2 1 2.4 1 3.6z" fill-rule="evenodd"/></svg>',
        'italic': '<svg width="24" height="24"><path d="M16.7 4.7l-.1.9h-.3c-.6 0-1 0-1.4.3-.3.3-.4.6-.5 1.1l-2.1 9.8v.6c0 .5.4.8 1.4.8h.2l-.2.8H8l.2-.8h.2c1.1 0 1.8-.5 2-1.5l2-9.8.1-.5c0-.6-.4-.8-1.4-.8h-.3l.2-.9h5.8z" fill-rule="evenodd"/></svg>',
        'line': '<svg width="24" height="24"><path d="M15 9l-8 8H4v-3l8-8 3 3zm1-1l-3-3 1-1h1c-.2 0 0 0 0 0l2 2s0 .2 0 0v1l-1 1zM4 18h16v2H4v-2z" fill-rule="evenodd"/></svg>',
        'link': '<svg width="24" height="24"><path d="M6.2 12.3a1 1 0 0 1 1.4 1.4l-2.1 2a2 2 0 1 0 2.7 2.8l4.8-4.8a1 1 0 0 0 0-1.4 1 1 0 1 1 1.4-1.3 2.9 2.9 0 0 1 0 4L9.6 20a3.9 3.9 0 0 1-5.5-5.5l2-2zm11.6-.6a1 1 0 0 1-1.4-1.4l2-2a2 2 0 1 0-2.6-2.8L11 10.3a1 1 0 0 0 0 1.4A1 1 0 1 1 9.6 13a2.9 2.9 0 0 1 0-4L14.4 4a3.9 3.9 0 0 1 5.5 5.5l-2 2z" fill-rule="nonzero"/></svg>',
        'list-bull-circle': '<svg width="48" height="48"><g fill-rule="evenodd"><path d="M11 16a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 1a3 3 0 1 1 0-6 3 3 0 0 1 0 6zM11 26a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 1a3 3 0 1 1 0-6 3 3 0 0 1 0 6zM11 36a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 1a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" fill-rule="nonzero"/><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/></g></svg>',
        'list-bull-default': '<svg width="48" height="48"><g fill-rule="evenodd"><circle cx="11" cy="14" r="3"/><circle cx="11" cy="24" r="3"/><circle cx="11" cy="34" r="3"/><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/></g></svg>',
        'list-bull-square': '<svg width="48" height="48"><g fill-rule="evenodd"><path d="M8 11h6v6H8zM8 21h6v6H8zM8 31h6v6H8z"/><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/></g></svg>',
        'list-num-default': '<svg width="48" height="48"><g fill-rule="evenodd"><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/><path d="M10 17v-4.8l-1.5 1v-1.1l1.6-1h1.2V17h-1.2zm3.6.1c-.4 0-.7-.3-.7-.7 0-.4.3-.7.7-.7.5 0 .7.3.7.7 0 .4-.2.7-.7.7zm-5 5.7c0-1.2.8-2 2.1-2s2.1.8 2.1 1.8c0 .7-.3 1.2-1.4 2.2l-1.1 1v.2h2.6v1H8.6v-.9l2-1.9c.8-.8 1-1.1 1-1.5 0-.5-.4-.8-1-.8-.5 0-.9.3-.9.9H8.5zm6.3 4.3c-.5 0-.7-.3-.7-.7 0-.4.2-.7.7-.7.4 0 .7.3.7.7 0 .4-.3.7-.7.7zM10 34.4v-1h.7c.6 0 1-.3 1-.8 0-.4-.4-.7-1-.7s-1 .3-1 .8H8.6c0-1.1 1-1.8 2.2-1.8 1.3 0 2.1.6 2.1 1.6 0 .7-.4 1.2-1 1.3v.1c.8.1 1.3.7 1.3 1.4 0 1-1 1.9-2.4 1.9-1.3 0-2.2-.8-2.3-2h1.2c0 .6.5 1 1.1 1 .7 0 1-.4 1-1 0-.5-.3-.8-1-.8h-.7zm4.7 2.7c-.4 0-.7-.3-.7-.7 0-.4.3-.7.7-.7.5 0 .8.3.8.7 0 .4-.3.7-.8.7z"/></g></svg>',
        'list-num-lower-alpha': '<svg width="48" height="48"><g fill-rule="evenodd"><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/><path d="M10.3 15.2c.5 0 1-.4 1-.9V14h-1c-.5.1-.8.3-.8.6 0 .4.3.6.8.6zm-.4.9c-1 0-1.5-.6-1.5-1.4 0-.8.6-1.3 1.7-1.4h1.1v-.4c0-.4-.2-.6-.7-.6-.5 0-.8.1-.9.4h-1c0-.8.8-1.4 2-1.4 1.1 0 1.8.6 1.8 1.6V16h-1.1v-.6h-.1c-.2.4-.7.7-1.3.7zm4.6 0c-.5 0-.7-.3-.7-.7 0-.4.2-.7.7-.7.4 0 .7.3.7.7 0 .4-.3.7-.7.7zm-3.2 10c-.6 0-1.2-.3-1.4-.8v.7H8.5v-6.3H10v2.5c.3-.5.8-.9 1.4-.9 1.2 0 1.9 1 1.9 2.4 0 1.5-.7 2.4-1.9 2.4zm-.4-3.7c-.7 0-1 .5-1 1.3s.3 1.4 1 1.4c.6 0 1-.6 1-1.4 0-.8-.4-1.3-1-1.3zm4 3.7c-.5 0-.7-.3-.7-.7 0-.4.2-.7.7-.7.4 0 .7.3.7.7 0 .4-.3.7-.7.7zm-2.2 7h-1.2c0-.5-.4-.8-.9-.8-.6 0-1 .5-1 1.4 0 1 .4 1.4 1 1.4.5 0 .8-.2 1-.7h1c0 1-.8 1.7-2 1.7-1.4 0-2.2-.9-2.2-2.4s.8-2.4 2.2-2.4c1.2 0 2 .7 2 1.7zm1.8 3c-.5 0-.8-.3-.8-.7 0-.4.3-.7.8-.7.4 0 .7.3.7.7 0 .4-.3.7-.7.7z"/></g></svg>',
        'list-num-lower-greek': '<svg width="48" height="48"><g fill-rule="evenodd"><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/><path d="M10.5 15c.7 0 1-.5 1-1.3s-.3-1.3-1-1.3c-.5 0-.9.5-.9 1.3s.4 1.4 1 1.4zm-.3 1c-1.1 0-1.8-.8-1.8-2.3 0-1.5.7-2.4 1.8-2.4.7 0 1.1.4 1.3 1h.1v-.9h1.2v3.2c0 .4.1.5.4.5h.2v.9h-.6c-.6 0-1-.2-1.1-.7h-.1c-.2.4-.7.8-1.4.8zm5 .1c-.5 0-.8-.3-.8-.7 0-.4.3-.7.7-.7.5 0 .8.3.8.7 0 .4-.3.7-.8.7zm-4.9 7v-1h.3c.6 0 1-.2 1-.7 0-.5-.4-.8-1-.8-.5 0-.8.3-.8 1v2.2c0 .8.4 1.3 1.1 1.3.6 0 1-.4 1-1s-.5-1-1.3-1h-.3zM8.6 22c0-1.5.7-2.3 2-2.3 1.2 0 2 .6 2 1.6 0 .6-.3 1-.8 1.3.8.3 1.3.8 1.3 1.7 0 1.2-.8 1.9-1.9 1.9-.6 0-1.1-.3-1.3-.8v2.2H8.5V22zm6.2 4.2c-.4 0-.7-.3-.7-.7 0-.4.3-.7.7-.7.5 0 .7.3.7.7 0 .4-.2.7-.7.7zm-4.5 8.5L8 30h1.4l1.7 3.5 1.7-3.5h1.1l-2.2 4.6v.1c.5.8.7 1.4.7 1.8 0 .4-.1.8-.4 1-.2.2-.6.3-1 .3-.9 0-1.3-.4-1.3-1.2 0-.5.2-1 .5-1.7l.1-.2zm.7 1a2 2 0 0 0-.4.9c0 .3.1.4.4.4.3 0 .4-.1.4-.4 0-.2-.1-.6-.4-1zm4.5.5c-.5 0-.8-.3-.8-.7 0-.4.3-.7.8-.7.4 0 .7.3.7.7 0 .4-.3.7-.7.7z"/></g></svg>',
        'list-num-lower-roman': '<svg width="48" height="48"><g fill-rule="evenodd"><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/><path d="M15.1 16v-1.2h1.3V16H15zm0 10v-1.2h1.3V26H15zm0 10v-1.2h1.3V36H15z"/><path fill-rule="nonzero" d="M12 21h1.5v5H12zM12 31h1.5v5H12zM9 21h1.5v5H9zM9 31h1.5v5H9zM6 31h1.5v5H6zM12 11h1.5v5H12zM12 19h1.5v1H12zM12 29h1.5v1H12zM9 19h1.5v1H9zM9 29h1.5v1H9zM6 29h1.5v1H6zM12 9h1.5v1H12z"/></g></svg>',
        'list-num-upper-alpha': '<svg width="48" height="48"><g fill-rule="evenodd"><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/><path d="M12.6 17l-.5-1.4h-2L9.5 17H8.3l2-6H12l2 6h-1.3zM11 12.3l-.7 2.3h1.6l-.8-2.3zm4.7 4.8c-.4 0-.7-.3-.7-.7 0-.4.3-.7.7-.7.5 0 .7.3.7.7 0 .4-.2.7-.7.7zM11.4 27H8.7v-6h2.6c1.2 0 1.9.6 1.9 1.5 0 .6-.5 1.2-1 1.3.7.1 1.3.7 1.3 1.5 0 1-.8 1.7-2 1.7zM10 22v1.5h1c.6 0 1-.3 1-.8 0-.4-.4-.7-1-.7h-1zm0 4H11c.7 0 1.1-.3 1.1-.8 0-.6-.4-.9-1.1-.9H10V26zm5.4 1.1c-.5 0-.8-.3-.8-.7 0-.4.3-.7.8-.7.4 0 .7.3.7.7 0 .4-.3.7-.7.7zm-4.1 10c-1.8 0-2.8-1.1-2.8-3.1s1-3.1 2.8-3.1c1.4 0 2.5.9 2.6 2.2h-1.3c0-.7-.6-1.1-1.3-1.1-1 0-1.6.7-1.6 2s.6 2 1.6 2c.7 0 1.2-.4 1.4-1h1.2c-.1 1.3-1.2 2.2-2.6 2.2zm4.5 0c-.5 0-.8-.3-.8-.7 0-.4.3-.7.8-.7.4 0 .7.3.7.7 0 .4-.3.7-.7.7z"/></g></svg>',
        'list-num-upper-roman': '<svg width="48" height="48"><g fill-rule="evenodd"><path opacity=".2" d="M18 12h22v4H18zM18 22h22v4H18zM18 32h22v4H18z"/><path d="M15.1 17v-1.2h1.3V17H15zm0 10v-1.2h1.3V27H15zm0 10v-1.2h1.3V37H15z"/><path fill-rule="nonzero" d="M12 20h1.5v7H12zM12 30h1.5v7H12zM9 20h1.5v7H9zM9 30h1.5v7H9zM6 30h1.5v7H6zM12 10h1.5v7H12z"/></g></svg>',
        'lock': '<svg width="24" height="24"><path d="M16.3 11c.2 0 .3 0 .5.2l.2.6v7.4c0 .3 0 .4-.2.6l-.6.2H7.8c-.3 0-.4 0-.6-.2a.7.7 0 0 1-.2-.6v-7.4c0-.3 0-.4.2-.6l.5-.2H8V8c0-.8.3-1.5.9-2.1.6-.6 1.3-.9 2.1-.9h2c.8 0 1.5.3 2.1.9.6.6.9 1.3.9 2.1v3h.3zM10 8v3h4V8a1 1 0 0 0-.3-.7A1 1 0 0 0 13 7h-2a1 1 0 0 0-.7.3 1 1 0 0 0-.3.7z" fill-rule="evenodd"/></svg>',
        'ltr': '<svg width="24" height="24"><path d="M11 5h7a1 1 0 0 1 0 2h-1v11a1 1 0 0 1-2 0V7h-2v11a1 1 0 0 1-2 0v-6c-.5 0-1 0-1.4-.3A3.4 3.4 0 0 1 7.8 10a3.3 3.3 0 0 1 0-2.8 3.4 3.4 0 0 1 1.8-1.8L11 5zM4.4 16.2L6.2 15l-1.8-1.2a1 1 0 0 1 1.2-1.6l3 2a1 1 0 0 1 0 1.6l-3 2a1 1 0 1 1-1.2-1.6z" fill-rule="evenodd"/></svg>',
        'new-document': '<svg width="24" height="24"><path d="M14.4 3H7a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h10a2 2 0 0 0 2-2V7.6L14.4 3zM17 19H7V5h6v4h4v10z" fill-rule="nonzero"/></svg>',
        'new-tab': '<svg width="24" height="24"><path d="M15 13l2-2v8H5V7h8l-2 2H7v8h8v-4zm4-8v5.5l-2-2-5.6 5.5H10v-1.4L15.5 7l-2-2H19z" fill-rule="evenodd"/></svg>',
        'non-breaking': '<svg width="24" height="24"><path d="M11 11H8a1 1 0 1 1 0-2h3V6c0-.6.4-1 1-1s1 .4 1 1v3h3c.6 0 1 .4 1 1s-.4 1-1 1h-3v3c0 .6-.4 1-1 1a1 1 0 0 1-1-1v-3zm10 4v5H3v-5c0-.6.4-1 1-1s1 .4 1 1v3h14v-3c0-.6.4-1 1-1s1 .4 1 1z" fill-rule="evenodd"/></svg>',
        'notice': '<svg width="24" height="24"><path d="M17.8 9.8L15.4 4 20 8.5v7L15.5 20h-7L4 15.5v-7L8.5 4h7l2.3 5.8zm0 0l2.2 5.7-2.3-5.8zM13 17v-2h-2v2h2zm0-4V7h-2v6h2z" fill-rule="evenodd"/></svg>',
        'ordered-list': '<svg width="24" height="24"><path d="M10 17h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm0-6h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm0-6h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 1 1 0-2zM6 4v3.5c0 .3-.2.5-.5.5a.5.5 0 0 1-.5-.5V5h-.5a.5.5 0 0 1 0-1H6zm-1 8.8l.2.2h1.3c.3 0 .5.2.5.5s-.2.5-.5.5H4.9a1 1 0 0 1-.9-1V13c0-.4.3-.8.6-1l1.2-.4.2-.3a.2.2 0 0 0-.2-.2H4.5a.5.5 0 0 1-.5-.5c0-.3.2-.5.5-.5h1.6c.5 0 .9.4.9 1v.1c0 .4-.3.8-.6 1l-1.2.4-.2.3zM7 17v2c0 .6-.4 1-1 1H4.5a.5.5 0 0 1 0-1h1.2c.2 0 .3-.1.3-.3 0-.2-.1-.3-.3-.3H4.4a.4.4 0 1 1 0-.8h1.3c.2 0 .3-.1.3-.3 0-.2-.1-.3-.3-.3H4.5a.5.5 0 1 1 0-1H6c.6 0 1 .4 1 1z" fill-rule="evenodd"/></svg>',
        'orientation': '<svg width="24" height="24"><path d="M7.3 6.4L1 13l6.4 6.5 6.5-6.5-6.5-6.5zM3.7 13l3.6-3.7L11 13l-3.7 3.7-3.6-3.7zM12 6l2.8 2.7c.3.3.3.8 0 1-.3.4-.9.4-1.2 0L9.2 5.7a.8.8 0 0 1 0-1.2L13.6.2c.3-.3.9-.3 1.2 0 .3.3.3.8 0 1.1L12 4h1a9 9 0 1 1-4.3 16.9l1.5-1.5A7 7 0 1 0 13 6h-1z" fill-rule="nonzero"/></svg>',
        'outdent': '<svg width="24" height="24"><path d="M7 5h12c.6 0 1 .4 1 1s-.4 1-1 1H7a1 1 0 1 1 0-2zm5 4h7c.6 0 1 .4 1 1s-.4 1-1 1h-7a1 1 0 0 1 0-2zm0 4h7c.6 0 1 .4 1 1s-.4 1-1 1h-7a1 1 0 0 1 0-2zm-5 4h12a1 1 0 0 1 0 2H7a1 1 0 0 1 0-2zm1.6-3.8a1 1 0 0 1-1.2 1.6l-3-2a1 1 0 0 1 0-1.6l3-2a1 1 0 0 1 1.2 1.6L6.8 12l1.8 1.2z" fill-rule="evenodd"/></svg>',
        'page-break': '<svg width="24" height="24"><g fill-rule="evenodd"><path d="M5 11c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 0 1 0-2zm3 0h1c.6 0 1 .4 1 1s-.4 1-1 1H8a1 1 0 0 1 0-2zm4 0c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 0 1 0-2zm3 0h1c.6 0 1 .4 1 1s-.4 1-1 1h-1a1 1 0 0 1 0-2zm4 0c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 0 1 0-2zM7 3v5h10V3c0-.6.4-1 1-1s1 .4 1 1v7H5V3c0-.6.4-1 1-1s1 .4 1 1zM6 22a1 1 0 0 1-1-1v-7h14v7c0 .6-.4 1-1 1a1 1 0 0 1-1-1v-5H7v5c0 .6-.4 1-1 1z"/></g></svg>',
        'paragraph': '<svg width="24" height="24"><path d="M10 5h7a1 1 0 0 1 0 2h-1v11a1 1 0 0 1-2 0V7h-2v11a1 1 0 0 1-2 0v-6c-.5 0-1 0-1.4-.3A3.4 3.4 0 0 1 6.8 10a3.3 3.3 0 0 1 0-2.8 3.4 3.4 0 0 1 1.8-1.8L10 5z" fill-rule="evenodd"/></svg>',
        'paste-text': '<svg width="24" height="24"><path d="M18 9V5h-2v1c0 .6-.4 1-1 1H9a1 1 0 0 1-1-1V5H6v13h3V9h9zM9 20H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h3.2A3 3 0 0 1 12 1a3 3 0 0 1 2.8 2H18a2 2 0 0 1 2 2v4h1v12H9v-1zm1.5-9.5v9h9v-9h-9zM12 3a1 1 0 0 0-1 1c0 .5.4 1 1 1s1-.5 1-1-.4-1-1-1zm0 9h6v2h-.5l-.5-1h-1v4h.8v1h-3.6v-1h.8v-4h-1l-.5 1H12v-2z" fill-rule="nonzero"/></svg>',
        'paste': '<svg width="24" height="24"><path d="M18 9V5h-2v1c0 .6-.4 1-1 1H9a1 1 0 0 1-1-1V5H6v13h3V9h9zM9 20H6a2 2 0 0 1-2-2V5c0-1.1.9-2 2-2h3.2A3 3 0 0 1 12 1a3 3 0 0 1 2.8 2H18a2 2 0 0 1 2 2v4h1v12H9v-1zm1.5-9.5v9h9v-9h-9zM12 3a1 1 0 0 0-1 1c0 .5.4 1 1 1s1-.5 1-1-.4-1-1-1z" fill-rule="nonzero"/></svg>',
        'permanent-pen': '<svg width="24" height="24"><path d="M10.5 17.5L8 20H3v-3l3.5-3.5a2 2 0 0 1 0-3L14 3l1 1-7.3 7.3a1 1 0 0 0 0 1.4l3.6 3.6c.4.4 1 .4 1.4 0L20 9l1 1-7.6 7.6a2 2 0 0 1-2.8 0l-.1-.1z" fill-rule="nonzero"/></svg>',
        'plus': '<svg width="24" height="24"><g fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round" stroke="#000" stroke-width="2"><path d="M12 5v14M5 12h14"/></g></svg>',
        'preferences': '<svg width="24" height="24"><path d="M20.1 13.5l-1.9.2a5.8 5.8 0 0 1-.6 1.5l1.2 1.5c.4.4.3 1 0 1.4l-.7.7a1 1 0 0 1-1.4 0l-1.5-1.2a6.2 6.2 0 0 1-1.5.6l-.2 1.9c0 .5-.5.9-1 .9h-1a1 1 0 0 1-1-.9l-.2-1.9a5.8 5.8 0 0 1-1.5-.6l-1.5 1.2a1 1 0 0 1-1.4 0l-.7-.7a1 1 0 0 1 0-1.4l1.2-1.5a6.2 6.2 0 0 1-.6-1.5l-1.9-.2a1 1 0 0 1-.9-1v-1c0-.5.4-1 .9-1l1.9-.2a5.8 5.8 0 0 1 .6-1.5L5.2 7.3a1 1 0 0 1 0-1.4l.7-.7a1 1 0 0 1 1.4 0l1.5 1.2a6.2 6.2 0 0 1 1.5-.6l.2-1.9c0-.5.5-.9 1-.9h1c.5 0 1 .4 1 .9l.2 1.9a5.8 5.8 0 0 1 1.5.6l1.5-1.2a1 1 0 0 1 1.4 0l.7.7c.3.4.4 1 0 1.4l-1.2 1.5a6.2 6.2 0 0 1 .6 1.5l1.9.2c.5 0 .9.5.9 1v1c0 .5-.4 1-.9 1zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" fill-rule="evenodd"/></svg>',
        'preview': '<svg width="24" height="24"><path d="M3.5 12.5c.5.8 1.1 1.6 1.8 2.3 2 2 4.2 3.2 6.7 3.2s4.7-1.2 6.7-3.2a16.2 16.2 0 0 0 2.1-2.8 15.7 15.7 0 0 0-2.1-2.8c-2-2-4.2-3.2-6.7-3.2a9.3 9.3 0 0 0-6.7 3.2A16.2 16.2 0 0 0 3.2 12c0 .2.2.3.3.5zm-2.4-1l.7-1.2L4 7.8C6.2 5.4 8.9 4 12 4c3 0 5.8 1.4 8.1 3.8a18.2 18.2 0 0 1 2.8 3.7v1l-.7 1.2-2.1 2.5c-2.3 2.4-5 3.8-8.1 3.8-3 0-5.8-1.4-8.1-3.8a18.2 18.2 0 0 1-2.8-3.7 1 1 0 0 1 0-1zm12-3.3a2 2 0 1 0 2.7 2.6 4 4 0 1 1-2.6-2.6z" fill-rule="nonzero"/></svg>',
        'print': '<svg width="24" height="24"><path d="M18 8H6a3 3 0 0 0-3 3v6h2v3h14v-3h2v-6a3 3 0 0 0-3-3zm-1 10H7v-4h10v4zm.5-5c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5zm.5-8H6v2h12V5z" fill-rule="nonzero"/></svg>',
        'quote': '<svg width="24" height="24"><path d="M7.5 17h.9c.4 0 .7-.2.9-.6L11 13V8c0-.6-.4-1-1-1H6a1 1 0 0 0-1 1v4c0 .6.4 1 1 1h2l-1.3 2.7a1 1 0 0 0 .8 1.3zm8 0h.9c.4 0 .7-.2.9-.6L19 13V8c0-.6-.4-1-1-1h-4a1 1 0 0 0-1 1v4c0 .6.4 1 1 1h2l-1.3 2.7a1 1 0 0 0 .8 1.3z" fill-rule="nonzero"/></svg>',
        'redo': '<svg width="24" height="24"><path d="M17.6 10H12c-2.8 0-4.4 1.4-4.9 3.5-.4 2 .3 4 1.4 4.6a1 1 0 1 1-1 1.8c-2-1.2-2.9-4.1-2.3-6.8.6-3 3-5.1 6.8-5.1h5.6l-3.3-3.3a1 1 0 1 1 1.4-1.4l5 5a1 1 0 0 1 0 1.4l-5 5a1 1 0 0 1-1.4-1.4l3.3-3.3z" fill-rule="nonzero"/></svg>',
        'reload': '<svg width="24" height="24"><g fill-rule="nonzero"><path d="M5 22.1l-1.2-4.7v-.2a1 1 0 0 1 1-1l5 .4a1 1 0 1 1-.2 2l-2.2-.2a7.8 7.8 0 0 0 8.4.2 7.5 7.5 0 0 0 3.5-6.4 1 1 0 1 1 2 0 9.5 9.5 0 0 1-4.5 8 9.9 9.9 0 0 1-10.2 0l.4 1.4a1 1 0 1 1-2 .5zM13.6 7.4c0-.5.5-1 1-.9l2.8.2a8 8 0 0 0-9.5-1 7.5 7.5 0 0 0-3.6 7 1 1 0 0 1-2 0 9.5 9.5 0 0 1 4.5-8.6 10 10 0 0 1 10.9.3l-.3-1a1 1 0 0 1 2-.5l1.1 4.8a1 1 0 0 1-1 1.2l-5-.4a1 1 0 0 1-.9-1z"/></g></svg>',
        'remove-formatting': '<svg width="24" height="24"><path d="M13.2 6a1 1 0 0 1 0 .2l-2.6 10a1 1 0 0 1-1 .8h-.2a.8.8 0 0 1-.8-1l2.6-10H8a1 1 0 1 1 0-2h9a1 1 0 0 1 0 2h-3.8zM5 18h7a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2zm13 1.5L16.5 18 15 19.5a.7.7 0 0 1-1-1l1.5-1.5-1.5-1.5a.7.7 0 0 1 1-1l1.5 1.5 1.5-1.5a.7.7 0 0 1 1 1L17.5 17l1.5 1.5a.7.7 0 0 1-1 1z" fill-rule="evenodd"/></svg>',
        'remove': '<svg width="24" height="24"><path d="M16 7h3a1 1 0 0 1 0 2h-1v9a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3V9H5a1 1 0 1 1 0-2h3V6a3 3 0 0 1 3-3h2a3 3 0 0 1 3 3v1zm-2 0V6c0-.6-.4-1-1-1h-2a1 1 0 0 0-1 1v1h4zm2 2H8v9c0 .6.4 1 1 1h6c.6 0 1-.4 1-1V9zm-7 3a1 1 0 0 1 2 0v4a1 1 0 0 1-2 0v-4zm4 0a1 1 0 0 1 2 0v4a1 1 0 0 1-2 0v-4z" fill-rule="nonzero"/></svg>',
        'resize-handle': '<svg width="10" height="10"><g fill-rule="nonzero"><path d="M8.1 1.1A.5.5 0 1 1 9 2l-7 7A.5.5 0 1 1 1 8l7-7zM8.1 5.1A.5.5 0 1 1 9 6l-3 3A.5.5 0 1 1 5 8l3-3z"/></g></svg>',
        'resize': '<svg width="24" height="24"><path d="M4 5c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3h6c.3 0 .5.1.7.3.2.2.3.4.3.7 0 .3-.1.5-.3.7a1 1 0 0 1-.7.3H7.4L18 16.6V13c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3.3 0 .5.1.7.3.2.2.3.4.3.7v6c0 .3-.1.5-.3.7a1 1 0 0 1-.7.3h-6a1 1 0 0 1-.7-.3 1 1 0 0 1-.3-.7c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3h3.6L6 7.4V11c0 .3-.1.5-.3.7a1 1 0 0 1-.7.3 1 1 0 0 1-.7-.3A1 1 0 0 1 4 11V5z" fill-rule="evenodd"/></svg>',
        'restore-draft': '<svg width="24" height="24"><g fill-rule="evenodd"><path d="M17 13c0 .6-.4 1-1 1h-4V8c0-.6.4-1 1-1s1 .4 1 1v4h2c.6 0 1 .4 1 1z"/><path d="M4.7 10H9a1 1 0 0 1 0 2H3a1 1 0 0 1-1-1V5a1 1 0 1 1 2 0v3l2.5-2.4a9.2 9.2 0 0 1 10.8-1.5A9 9 0 0 1 13.4 21c-2.4.1-4.7-.7-6.5-2.2a1 1 0 1 1 1.3-1.5 7.2 7.2 0 0 0 11.6-3.7 7 7 0 0 0-3.5-7.7A7.2 7.2 0 0 0 8 7L4.7 10z" fill-rule="nonzero"/></g></svg>',
        'rotate-left': '<svg width="24" height="24"><path d="M4.7 10H9a1 1 0 0 1 0 2H3a1 1 0 0 1-1-1V5a1 1 0 1 1 2 0v3l2.5-2.4a9.2 9.2 0 0 1 10.8-1.5A9 9 0 0 1 13.4 21c-2.4.1-4.7-.7-6.5-2.2a1 1 0 1 1 1.3-1.5 7.2 7.2 0 0 0 11.6-3.7 7 7 0 0 0-3.5-7.7A7.2 7.2 0 0 0 8 7L4.7 10z" fill-rule="nonzero"/></svg>',
        'rotate-right': '<svg width="24" height="24"><path d="M20 8V5a1 1 0 0 1 2 0v6c0 .6-.4 1-1 1h-6a1 1 0 0 1 0-2h4.3L16 7A7.2 7.2 0 0 0 7.7 6a7 7 0 0 0 3 13.1c1.9.1 3.7-.5 5-1.7a1 1 0 0 1 1.4 1.5A9.2 9.2 0 0 1 2.2 14c-.9-3.9 1-8 4.5-9.9 3.5-1.9 8-1.3 10.8 1.5L20 8z" fill-rule="nonzero"/></svg>',
        'rtl': '<svg width="24" height="24"><path d="M8 5h8v2h-2v12h-2V7h-2v12H8v-7c-.5 0-1 0-1.4-.3A3.4 3.4 0 0 1 4.8 10a3.3 3.3 0 0 1 0-2.8 3.4 3.4 0 0 1 1.8-1.8L8 5zm12 11.2a1 1 0 1 1-1 1.6l-3-2a1 1 0 0 1 0-1.6l3-2a1 1 0 1 1 1 1.6L18.4 15l1.8 1.2z" fill-rule="evenodd"/></svg>',
        'save': '<svg width="24" height="24"><path d="M5 16h14a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2c0-1.1.9-2 2-2zm0 2v2h14v-2H5zm10 0h2v2h-2v-2zm-4-6.4L8.7 9.3a1 1 0 1 0-1.4 1.4l4 4c.4.4 1 .4 1.4 0l4-4a1 1 0 1 0-1.4-1.4L13 11.6V4a1 1 0 0 0-2 0v7.6z" fill-rule="nonzero"/></svg>',
        'search': '<svg width="24" height="24"><path d="M16 17.3a8 8 0 1 1 1.4-1.4l4.3 4.4a1 1 0 0 1-1.4 1.4l-4.4-4.3zm-5-.3a6 6 0 1 0 0-12 6 6 0 0 0 0 12z" fill-rule="nonzero"/></svg>',
        'select-all': '<svg width="24" height="24"><path d="M3 5h2V3a2 2 0 0 0-2 2zm0 8h2v-2H3v2zm4 8h2v-2H7v2zM3 9h2V7H3v2zm10-6h-2v2h2V3zm6 0v2h2a2 2 0 0 0-2-2zM5 21v-2H3c0 1.1.9 2 2 2zm-2-4h2v-2H3v2zM9 3H7v2h2V3zm2 18h2v-2h-2v2zm8-8h2v-2h-2v2zm0 8a2 2 0 0 0 2-2h-2v2zm0-12h2V7h-2v2zm0 8h2v-2h-2v2zm-4 4h2v-2h-2v2zm0-16h2V3h-2v2zM7 17h10V7H7v10zm2-8h6v6H9V9z" fill-rule="nonzero"/></svg>',
        'selected': '<svg width="24" height="24"><path d="M12 21a9 9 0 1 1 0-18 9 9 0 0 1 0 18zm-2.4-6.1L7 12.3a.7.7 0 0 0-1 1L9.6 17 18 8.6a.7.7 0 0 0 0-1 .7.7 0 0 0-1 0l-7.4 7.3z" fill-rule="evenodd"/></svg>',
        'settings': '<svg width="24" height="24"><path d="M11 6h8c.6 0 1 .4 1 1s-.4 1-1 1h-8v.3c0 .2 0 .3-.2.5l-.6.2H7.8c-.3 0-.4 0-.6-.2a.7.7 0 0 1-.2-.6V8H5a1 1 0 1 1 0-2h2v-.3c0-.2 0-.3.2-.5l.5-.2h2.5c.3 0 .4 0 .6.2l.2.5V6zM8 8h2V6H8v2zm9 2.8v.2h2c.6 0 1 .4 1 1s-.4 1-1 1h-2v.3c0 .2 0 .3-.2.5l-.6.2h-2.4c-.3 0-.4 0-.6-.2a.7.7 0 0 1-.2-.6V13H5a1 1 0 0 1 0-2h8v-.3c0-.2 0-.3.2-.5l.6-.2h2.4c.3 0 .4 0 .6.2l.2.6zM14 13h2v-2h-2v2zm-3 2.8v.2h8c.6 0 1 .4 1 1s-.4 1-1 1h-8v.3c0 .2 0 .3-.2.5l-.6.2H7.8c-.3 0-.4 0-.6-.2a.7.7 0 0 1-.2-.6V18H5a1 1 0 0 1 0-2h2v-.3c0-.2 0-.3.2-.5l.5-.2h2.5c.3 0 .4 0 .6.2l.2.6zM8 18h2v-2H8v2z" fill-rule="evenodd"/></svg>',
        'sharpen': '<svg width="24" height="24"><path d="M16 6l4 4-8 9-8-9 4-4h8zm-4 10.2l5.5-6.2-.1-.1H12v-.3h5.1l-.2-.2H12V9h4.6l-.2-.2H12v-.3h4.1l-.2-.2H12V8h3.6l-.2-.2H8.7L6.5 10l.1.1H12v.3H6.9l.2.2H12v.3H7.3l.2.2H12v.3H7.7l.3.2h4v.3H8.2l.2.2H12v.3H8.6l.3.2H12v.3H9l.3.2H12v.3H9.5l.2.2H12v.3h-2l.2.2H12v.3h-1.6l.2.2H12v.3h-1.1l.2.2h.9v.3h-.7l.2.2h.5v.3h-.3l.3.2z" fill-rule="evenodd"/></svg>',
        'sourcecode': '<svg width="24" height="24"><g fill-rule="nonzero"><path d="M9.8 15.7c.3.3.3.8 0 1-.3.4-.9.4-1.2 0l-4.4-4.1a.8.8 0 0 1 0-1.2l4.4-4.2c.3-.3.9-.3 1.2 0 .3.3.3.8 0 1.1L6 12l3.8 3.7zM14.2 15.7c-.3.3-.3.8 0 1 .4.4.9.4 1.2 0l4.4-4.1c.3-.3.3-.9 0-1.2l-4.4-4.2a.8.8 0 0 0-1.2 0c-.3.3-.3.8 0 1.1L18 12l-3.8 3.7z"/></g></svg>',
        'spell-check': '<svg width="24" height="24"><path d="M6 8v3H5V5c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3h2c.3 0 .5.1.7.3.2.2.3.4.3.7v6H8V8H6zm0-3v2h2V5H6zm13 0h-3v5h3v1h-3a1 1 0 0 1-.7-.3 1 1 0 0 1-.3-.7V5c0-.3.1-.5.3-.7.2-.2.4-.3.7-.3h3v1zm-5 1.5l-.1.7c-.1.2-.3.3-.6.3.3 0 .5.1.6.3l.1.7V10c0 .3-.1.5-.3.7a1 1 0 0 1-.7.3h-3V4h3c.3 0 .5.1.7.3.2.2.3.4.3.7v1.5zM13 10V8h-2v2h2zm0-3V5h-2v2h2zm3 5l1 1-6.5 7L7 15.5l1.3-1 2.2 2.2L16 12z" fill-rule="evenodd"/></svg>',
        'strike-through': '<svg width="24" height="24"><g fill-rule="evenodd"><path d="M15.6 8.5c-.5-.7-1-1.1-1.3-1.3-.6-.4-1.3-.6-2-.6-2.7 0-2.8 1.7-2.8 2.1 0 1.6 1.8 2 3.2 2.3 4.4.9 4.6 2.8 4.6 3.9 0 1.4-.7 4.1-5 4.1A6.2 6.2 0 0 1 7 16.4l1.5-1.1c.4.6 1.6 2 3.7 2 1.6 0 2.5-.4 3-1.2.4-.8.3-2-.8-2.6-.7-.4-1.6-.7-2.9-1-1-.2-3.9-.8-3.9-3.6C7.6 6 10.3 5 12.4 5c2.9 0 4.2 1.6 4.7 2.4l-1.5 1.1z"/><path d="M5 11h14a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2z" fill-rule="nonzero"/></g></svg>',
        'subscript': '<svg width="24" height="24"><path d="M10.4 10l4.6 4.6-1.4 1.4L9 11.4 4.4 16 3 14.6 7.6 10 3 5.4 4.4 4 9 8.6 13.6 4 15 5.4 10.4 10zM21 19h-5v-1l1-.8 1.7-1.6c.3-.4.5-.8.5-1.2 0-.3 0-.6-.2-.7-.2-.2-.5-.3-.9-.3a2 2 0 0 0-.8.2l-.7.3-.4-1.1 1-.6 1.2-.2c.8 0 1.4.3 1.8.7.4.4.6.9.6 1.5s-.2 1.1-.5 1.6a8 8 0 0 1-1.3 1.3l-.6.6h2.6V19z" fill-rule="nonzero"/></svg>',
        'superscript': '<svg width="24" height="24"><path d="M15 9.4L10.4 14l4.6 4.6-1.4 1.4L9 15.4 4.4 20 3 18.6 7.6 14 3 9.4 4.4 8 9 12.6 13.6 8 15 9.4zm5.9 1.6h-5v-1l1-.8 1.7-1.6c.3-.5.5-.9.5-1.3 0-.3 0-.5-.2-.7-.2-.2-.5-.3-.9-.3l-.8.2-.7.4-.4-1.2c.2-.2.5-.4 1-.5.3-.2.8-.2 1.2-.2.8 0 1.4.2 1.8.6.4.4.6 1 .6 1.6 0 .5-.2 1-.5 1.5l-1.3 1.4-.6.5h2.6V11z" fill-rule="nonzero"/></svg>',
        'table-cell-properties': '<svg width="24" height="24"><path d="M4 5h16v14H4V5zm10 10h-4v3h4v-3zm0-8h-4v3h4V7zM9 7H5v3h4V7zm-4 4v3h4v-3H5zm10 0v3h4v-3h-4zm0-1h4V7h-4v3zM5 15v3h4v-3H5zm10 3h4v-3h-4v3z" fill-rule="evenodd"/></svg>',
        'table-cell-select-all': '<svg width="24" height="24"><path d="M12.5 5.5v6h6v-6h-6zm-1 0h-6v6h6v-6zm1 13h6v-6h-6v6zm-1 0v-6h-6v6h6zm-7-14h15v15h-15v-15z" fill-rule="nonzero"/></svg>',
        'table-cell-select-inner': '<svg width="24" height="24"><g fill-rule="nonzero"><path d="M5.5 5.5v13h13v-13h-13zm-1-1h15v15h-15v-15z" opacity=".2"/><path d="M11.5 11.5v-7h1v7h7v1h-7v7h-1v-7h-7v-1h7z"/></g></svg>',
        'table-delete-column': '<svg width="24" height="24"><path d="M9 11.2l1 1v.2l-1 1v-2.2zm5 1l1-1v2.2l-1-1v-.2zM20 5v14H4V5h16zm-1 2h-4v.8l-.2-.2-.8.8V7h-4v1.4l-.8-.8-.2.2V7H5v11h4v-1.8l.5.5.5-.4V18h4v-1.8l.8.8.2-.3V18h4V7zm-3.9 3.4l-1.8 1.9 1.8 1.9c.4.3.4.9 0 1.2-.3.3-.8.3-1.2 0L12 13.5l-1.8 1.9a.8.8 0 0 1-1.2 0 .9.9 0 0 1 0-1.2l1.8-1.9-1.9-2a.9.9 0 0 1 1.2-1.2l2 2 1.8-1.8c.3-.4.9-.4 1.2 0a.8.8 0 0 1 0 1.1z" fill-rule="evenodd"/></svg>',
        'table-delete-row': '<svg width="24" height="24"><path d="M16.7 8.8l1.1 1.2-2.4 2.5L18 15l-1.2 1.2-2.5-2.5-2.4 2.5-1.3-1.2 2.5-2.5-2.5-2.5 1.2-1.3 2.6 2.6 2.4-2.5zM4 5h16v14H4V5zm15 5V7H5v3h4.8l1 1H5v3h5.8l-1 1H5v3h14v-3h-.4l-1-1H19v-3h-1.3l1-1h.3z" fill-rule="evenodd"/></svg>',
        'table-delete-table': '<svg width="24" height="26"><path d="M4 6h16v14H4V6zm1 2v11h14V8H5zm11.7 8.7l-1.5 1.5L12 15l-3.3 3.2-1.4-1.5 3.2-3.2-3.3-3.2 1.5-1.5L12 12l3.2-3.2 1.5 1.5-3.2 3.2 3.2 3.2z" fill-rule="evenodd"/></svg>',
        'table-insert-column-after': '<svg width="24" height="24"><path d="M14.3 9c.4 0 .7.3.7.6v2.2h2.1c.4 0 .7.3.7.7 0 .4-.3.7-.7.7H15v2.2c0 .3-.3.6-.7.6a.7.7 0 0 1-.6-.6v-2.2h-2.2a.7.7 0 0 1 0-1.4h2.2V9.6c0-.3.3-.6.6-.6zM4 5h16v14H4V5zm5 13v-3H5v3h4zm0-4v-3H5v3h4zm0-4V7H5v3h4zm10 8V7h-9v11h9z" fill-rule="evenodd"/></svg>',
        'table-insert-column-before': '<svg width="24" height="24"><path d="M9.7 16a.7.7 0 0 1-.7-.6v-2.2H6.9a.7.7 0 0 1 0-1.4H9V9.6c0-.3.3-.6.7-.6.3 0 .6.3.6.6v2.2h2.2c.4 0 .8.3.8.7 0 .4-.4.7-.8.7h-2.2v2.2c0 .3-.3.6-.6.6zM4 5h16v14H4V5zm10 13V7H5v11h9zm5 0v-3h-4v3h4zm0-4v-3h-4v3h4zm0-4V7h-4v3h4z" fill-rule="evenodd"/></svg>',
        'table-insert-row-above': '<svg width="24" height="24"><path d="M14.8 10.5c0 .3-.2.5-.5.5h-1.8v1.8c0 .3-.2.5-.5.5a.5.5 0 0 1-.5-.6V11H9.7a.5.5 0 0 1 0-1h1.8V8.3c0-.3.2-.6.5-.6s.5.3.5.6V10h1.8c.3 0 .5.2.5.5zM4 5h16v14H4V5zm5 13v-3H5v3h4zm5 0v-3h-4v3h4zm5 0v-3h-4v3h4zm0-4V7H5v7h14z" fill-rule="evenodd"/></svg>',
        'table-insert-row-after': '<svg width="24" height="24"><path d="M9.2 14.5c0-.3.2-.5.5-.5h1.8v-1.8c0-.3.2-.5.5-.5s.5.2.5.6V14h1.8c.3 0 .5.2.5.5s-.2.5-.5.5h-1.8v1.7c0 .3-.2.6-.5.6a.5.5 0 0 1-.5-.6V15H9.7a.5.5 0 0 1-.5-.5zM4 5h16v14H4V5zm6 2v3h4V7h-4zM5 7v3h4V7H5zm14 11v-7H5v7h14zm0-8V7h-4v3h4z" fill-rule="evenodd"/></svg>',
        'table-left-header': '<svg width="24" height="24"><path d="M4 5h16v13H4V5zm10 12v-3h-4v3h4zm0-4v-3h-4v3h4zm0-4V6h-4v3h4zm5 8v-3h-4v3h4zm0-4v-3h-4v3h4zm0-4V6h-4v3h4z" fill-rule="evenodd"/></svg>',
        'table-merge-cells': '<svg width="24" height="24"><path d="M4 5h16v14H4V5zm6 13h9v-7h-9v7zm4-11h-4v3h4V7zM9 7H5v3h4V7zm-4 4v3h4v-3H5zm10-1h4V7h-4v3zM5 15v3h4v-3H5z" fill-rule="evenodd"/></svg>',
        'table-row-properties': '<svg width="24" height="24"><path d="M4 5h16v14H4V5zm10 10h-4v3h4v-3zm0-8h-4v3h4V7zM9 7H5v3h4V7zm6 3h4V7h-4v3zM5 15v3h4v-3H5zm10 3h4v-3h-4v3z" fill-rule="evenodd"/></svg>',
        'table-split-cells': '<svg width="24" height="24"><path d="M4 5h16v14H4V5zm6 2v3h4V7h-4zM9 18v-3H5v3h4zm0-4v-3H5v3h4zm0-4V7H5v3h4zm10 8v-7h-9v7h9zm0-8V7h-4v3h4zm-3.5 4.5l1.5 1.6c.3.2.3.7 0 1-.2.2-.7.2-1 0l-1.5-1.6-1.6 1.5c-.2.3-.7.3-1 0a.7.7 0 0 1 0-1l1.6-1.5-1.5-1.6a.7.7 0 0 1 1-1l1.5 1.6 1.6-1.5c.2-.3.7-.3 1 0 .2.2.2.7 0 1l-1.6 1.5z" fill-rule="evenodd"/></svg>',
        'table-top-header': '<svg width="24" height="24"><path d="M4 5h16v13H4V5zm5 12v-3H5v3h4zm0-4v-3H5v3h4zm5 4v-3h-4v3h4zm0-4v-3h-4v3h4zm5 4v-3h-4v3h4zm0-4v-3h-4v3h4z" fill-rule="evenodd"/></svg>',
        'table': '<svg width="24" height="24"><path d="M4 5h16v14H4V5zm6 9h4v-3h-4v3zm4 1h-4v3h4v-3zm0-8h-4v3h4V7zM9 7H5v3h4V7zm-4 4v3h4v-3H5zm10 0v3h4v-3h-4zm0-1h4V7h-4v3zM5 15v3h4v-3H5zm10 3h4v-3h-4v3z" fill-rule="evenodd"/></svg>',
        'template': '<svg width="24" height="24"><path d="M19 19v-1H5v1h14zM9 16v-4a5 5 0 1 1 6 0v4h4a2 2 0 0 1 2 2v3H3v-3c0-1.1.9-2 2-2h4zm4 0v-5l.8-.6a3 3 0 1 0-3.6 0l.8.6v5h2z" fill-rule="nonzero"/></svg>',
        'temporary-placeholder': '<svg width="24" height="24"><path d="M20.5 2.5c-.8 0-1.5.7-1.5 1.5a1.5 1.5 0 0 1-3 0 3 3 0 0 0-6 0v2H8.5c-.3 0-.5.2-.5.5v1a8 8 0 1 0 6 0v-1c0-.3-.2-.5-.5-.5H11V4a2 2 0 0 1 4 0 2.5 2.5 0 0 0 5 0c0-.3.2-.5.5-.5a.5.5 0 0 0 0-1zM8.1 10.9a5 5 0 0 0-1.2 7 .5.5 0 0 1-.8.5 6 6 0 0 1 1.5-8.3.5.5 0 1 1 .5.8z" fill-rule="nonzero"/></svg>',
        'text-color': '<svg width="24" height="24"><g fill-rule="evenodd"><path id="tox-icon-text-color__color" d="M3 18h18v3H3z"/><path d="M8.7 16h-.8a.5.5 0 0 1-.5-.6l2.7-9c.1-.3.3-.4.5-.4h2.8c.2 0 .4.1.5.4l2.7 9a.5.5 0 0 1-.5.6h-.8a.5.5 0 0 1-.4-.4l-.7-2.2c0-.3-.3-.4-.5-.4h-3.4c-.2 0-.4.1-.5.4l-.7 2.2c0 .3-.2.4-.4.4zm2.6-7.6l-.6 2a.5.5 0 0 0 .5.6h1.6a.5.5 0 0 0 .5-.6l-.6-2c0-.3-.3-.4-.5-.4h-.4c-.2 0-.4.1-.5.4z"/></g></svg>',
        'toc': '<svg width="24" height="24"><path d="M5 5c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 1 1 0-2zm3 0h11c.6 0 1 .4 1 1s-.4 1-1 1H8a1 1 0 1 1 0-2zm-3 8c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 0 1 0-2zm3 0h11c.6 0 1 .4 1 1s-.4 1-1 1H8a1 1 0 0 1 0-2zm0-4c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 1 1 0-2zm3 0h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm-3 8c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 0 1 0-2zm3 0h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2z" fill-rule="evenodd"/></svg>',
        'translate': '<svg width="24" height="24"><path d="M12.7 14.3l-.3.7-.4.7-2.2-2.2-3.1 3c-.3.4-.8.4-1 0a.7.7 0 0 1 0-1l3.1-3A12.4 12.4 0 0 1 6.7 9H8a10.1 10.1 0 0 0 1.7 2.4c.5-.5 1-1.1 1.4-1.8l.9-2H4.7a.7.7 0 1 1 0-1.5h4.4v-.7c0-.4.3-.8.7-.8.4 0 .7.4.7.8v.7H15c.4 0 .8.3.8.7 0 .4-.4.8-.8.8h-1.4a12.3 12.3 0 0 1-1 2.4 13.5 13.5 0 0 1-1.7 2.3l1.9 1.8zm4.3-3l2.7 7.3a.5.5 0 0 1-.4.7 1 1 0 0 1-1-.7l-.6-1.5h-3.4l-.6 1.5a1 1 0 0 1-1 .7.5.5 0 0 1-.4-.7l2.7-7.4a1 1 0 1 1 2 0zm-2.2 4.4h2.4L16 12.5l-1.2 3.2z" fill-rule="evenodd"/></svg>',
        'underline': '<svg width="24" height="24"><path d="M16 5c.6 0 1 .4 1 1v5.5a4 4 0 0 1-.4 1.8l-1 1.4a5.3 5.3 0 0 1-5.5 1 5 5 0 0 1-1.6-1c-.5-.4-.8-.9-1.1-1.4a4 4 0 0 1-.4-1.8V6c0-.6.4-1 1-1s1 .4 1 1v5.5c0 .3 0 .6.2 1l.6.7a3.3 3.3 0 0 0 2.2.8 3.4 3.4 0 0 0 2.2-.8c.3-.2.4-.5.6-.8l.2-.9V6c0-.6.4-1 1-1zM8 17h8c.6 0 1 .4 1 1s-.4 1-1 1H8a1 1 0 0 1 0-2z" fill-rule="evenodd"/></svg>',
        'undo': '<svg width="24" height="24"><path d="M6.4 8H12c3.7 0 6.2 2 6.8 5.1.6 2.7-.4 5.6-2.3 6.8a1 1 0 0 1-1-1.8c1.1-.6 1.8-2.7 1.4-4.6-.5-2.1-2.1-3.5-4.9-3.5H6.4l3.3 3.3a1 1 0 1 1-1.4 1.4l-5-5a1 1 0 0 1 0-1.4l5-5a1 1 0 0 1 1.4 1.4L6.4 8z" fill-rule="nonzero"/></svg>',
        'unlink': '<svg width="24" height="24"><path d="M6.2 12.3a1 1 0 0 1 1.4 1.4l-2 2a2 2 0 1 0 2.6 2.8l4.8-4.8a1 1 0 0 0 0-1.4 1 1 0 1 1 1.4-1.3 2.9 2.9 0 0 1 0 4L9.6 20a3.9 3.9 0 0 1-5.5-5.5l2-2zm11.6-.6a1 1 0 0 1-1.4-1.4l2.1-2a2 2 0 1 0-2.7-2.8L11 10.3a1 1 0 0 0 0 1.4A1 1 0 1 1 9.6 13a2.9 2.9 0 0 1 0-4L14.4 4a3.9 3.9 0 0 1 5.5 5.5l-2 2zM7.6 6.3a.8.8 0 0 1-1 1.1L3.3 4.2a.7.7 0 1 1 1-1l3.2 3.1zM5.1 8.6a.8.8 0 0 1 0 1.5H3a.8.8 0 0 1 0-1.5H5zm5-3.5a.8.8 0 0 1-1.5 0V3a.8.8 0 0 1 1.5 0V5zm6 11.8a.8.8 0 0 1 1-1l3.2 3.2a.8.8 0 0 1-1 1L16 17zm-2.2 2a.8.8 0 0 1 1.5 0V21a.8.8 0 0 1-1.5 0V19zm5-3.5a.7.7 0 1 1 0-1.5H21a.8.8 0 0 1 0 1.5H19z" fill-rule="nonzero"/></svg>',
        'unlock': '<svg width="24" height="24"><path d="M16 5c.8 0 1.5.3 2.1.9.6.6.9 1.3.9 2.1v3h-2V8a1 1 0 0 0-.3-.7A1 1 0 0 0 16 7h-2a1 1 0 0 0-.7.3 1 1 0 0 0-.3.7v3h.3c.2 0 .3 0 .5.2l.2.6v7.4c0 .3 0 .4-.2.6l-.6.2H4.8c-.3 0-.4 0-.6-.2a.7.7 0 0 1-.2-.6v-7.4c0-.3 0-.4.2-.6l.5-.2H11V8c0-.8.3-1.5.9-2.1.6-.6 1.3-.9 2.1-.9h2z" fill-rule="evenodd"/></svg>',
        'unordered-list': '<svg width="24" height="24"><path d="M11 5h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm0 6h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zm0 6h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2zM4.5 6c0-.4.1-.8.4-1 .3-.4.7-.5 1.1-.5.4 0 .8.1 1 .4.4.3.5.7.5 1.1 0 .4-.1.8-.4 1-.3.4-.7.5-1.1.5-.4 0-.8-.1-1-.4-.4-.3-.5-.7-.5-1.1zm0 6c0-.4.1-.8.4-1 .3-.4.7-.5 1.1-.5.4 0 .8.1 1 .4.4.3.5.7.5 1.1 0 .4-.1.8-.4 1-.3.4-.7.5-1.1.5-.4 0-.8-.1-1-.4-.4-.3-.5-.7-.5-1.1zm0 6c0-.4.1-.8.4-1 .3-.4.7-.5 1.1-.5.4 0 .8.1 1 .4.4.3.5.7.5 1.1 0 .4-.1.8-.4 1-.3.4-.7.5-1.1.5-.4 0-.8-.1-1-.4-.4-.3-.5-.7-.5-1.1z" fill-rule="evenodd"/></svg>',
        'unselected': '<svg width="24" height="24"><path d="M12 21a9 9 0 1 1 0-18 9 9 0 0 1 0 18zm0-1a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" fill-rule="evenodd"/></svg>',
        'upload': '<svg width="24" height="24"><path d="M18 19v-2a1 1 0 0 1 2 0v3c0 .6-.4 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 2 0v2h12zM11 6.4L8.7 8.7a1 1 0 0 1-1.4-1.4l4-4a1 1 0 0 1 1.4 0l4 4a1 1 0 1 1-1.4 1.4L13 6.4V16a1 1 0 0 1-2 0V6.4z" fill-rule="nonzero"/></svg>',
        'user': '<svg width="24" height="24"><path d="M12 24a12 12 0 1 1 0-24 12 12 0 0 1 0 24zm-8.7-5.3a11 11 0 0 0 17.4 0C19.4 16.3 14.6 15 12 15c-2.6 0-7.4 1.3-8.7 3.7zM12 13c2.2 0 4-2 4-4.5S14.2 4 12 4 8 6 8 8.5 9.8 13 12 13z" fill-rule="nonzero"/></svg>',
        'warning': '<svg width="24" height="24"><path d="M19.8 18.3c.2.5.3.9 0 1.2-.1.3-.5.5-1 .5H5.2c-.5 0-.9-.2-1-.5-.3-.3-.2-.7 0-1.2L11 4.7l.5-.5.5-.2c.2 0 .3 0 .5.2.2 0 .3.3.5.5l6.8 13.6zM12 18c.3 0 .5-.1.7-.3.2-.2.3-.4.3-.7a1 1 0 0 0-.3-.7 1 1 0 0 0-.7-.3 1 1 0 0 0-.7.3 1 1 0 0 0-.3.7c0 .3.1.5.3.7.2.2.4.3.7.3zm.7-3l.3-4a1 1 0 0 0-.3-.7 1 1 0 0 0-.7-.3 1 1 0 0 0-.7.3 1 1 0 0 0-.3.7l.3 4h1.4z" fill-rule="evenodd"/></svg>',
        'zoom-in': '<svg width="24" height="24"><path d="M16 17.3a8 8 0 1 1 1.4-1.4l4.3 4.4a1 1 0 0 1-1.4 1.4l-4.4-4.3zm-5-.3a6 6 0 1 0 0-12 6 6 0 0 0 0 12zm-1-9a1 1 0 0 1 2 0v6a1 1 0 0 1-2 0V8zm-2 4a1 1 0 0 1 0-2h6a1 1 0 0 1 0 2H8z" fill-rule="nonzero"/></svg>',
        'zoom-out': '<svg width="24" height="24"><path d="M16 17.3a8 8 0 1 1 1.4-1.4l4.3 4.4a1 1 0 0 1-1.4 1.4l-4.4-4.3zm-5-.3a6 6 0 1 0 0-12 6 6 0 0 0 0 12zm-3-5a1 1 0 0 1 0-2h6a1 1 0 0 1 0 2H8z" fill-rule="nonzero"/></svg>'
      };
    };

    var forToolbarCommand = function (editor, command) {
      return forToolbar(command, function () {
        editor.execCommand(command);
      }, {}, editor);
    };
    var getToggleBehaviours = function (command) {
      return derive$1([
        Toggling.config({
          toggleClass: Styles.resolve('toolbar-button-selected'),
          toggleOnExecute: false,
          aria: { mode: 'pressed' }
        }),
        Receivers.format(command, function (button, status) {
          var toggle = status ? Toggling.on : Toggling.off;
          toggle(button);
        })
      ]);
    };
    var forToolbarStateCommand = function (editor, command) {
      var extraBehaviours = getToggleBehaviours(command);
      return forToolbar(command, function () {
        editor.execCommand(command);
      }, extraBehaviours, editor);
    };
    var forToolbarStateAction = function (editor, clazz, command, action) {
      var extraBehaviours = getToggleBehaviours(command);
      return forToolbar(clazz, action, extraBehaviours, editor);
    };
    var defaultIcons = getAll();
    var getIcon = function (name, icons) {
      return Option.from(icons[name]).or(Option.from(defaultIcons[name]));
    };
    var getToolbarIconButton = function (clazz, editor) {
      var icons = editor.ui.registry.getAll().icons;
      var optOxideIcon = getIcon(clazz, icons);
      return optOxideIcon.fold(function () {
        return dom$2('<span class="${prefix}-toolbar-button ${prefix}-toolbar-group-item ${prefix}-icon-' + clazz + ' ${prefix}-icon"></span>');
      }, function (icon) {
        return dom$2('<span class="${prefix}-toolbar-button ${prefix}-toolbar-group-item">' + icon + '</span>');
      });
    };
    var forToolbar = function (clazz, action, extraBehaviours, editor) {
      return Button.sketch({
        dom: getToolbarIconButton(clazz, editor),
        action: action,
        buttonBehaviours: deepMerge(derive$1([Unselecting.config({})]), extraBehaviours)
      });
    };
    var Buttons = {
      forToolbar: forToolbar,
      forToolbarCommand: forToolbarCommand,
      forToolbarStateAction: forToolbarStateAction,
      forToolbarStateCommand: forToolbarStateCommand,
      getToolbarIconButton: getToolbarIconButton
    };

    var platform = PlatformDetection$1.detect();
    var isTouch = platform.deviceType.isTouch();
    var labelPart = optional({
      schema: [strict$1('dom')],
      name: 'label'
    });
    var edgePart = function (name) {
      return optional({
        name: '' + name + '-edge',
        overrides: function (detail) {
          var action = detail.model.manager.edgeActions[name];
          return action.fold(function () {
            return {};
          }, function (a) {
            var touchEvents = derive([runActionExtra(touchstart(), a, [detail])]);
            var mouseEvents = derive([
              runActionExtra(mousedown(), a, [detail]),
              runActionExtra(mousemove(), function (l, det) {
                if (det.mouseIsDown.get()) {
                  a(l, det);
                }
              }, [detail])
            ]);
            return { events: isTouch ? touchEvents : mouseEvents };
          });
        }
      });
    };
    var tlEdgePart = edgePart('top-left');
    var tedgePart = edgePart('top');
    var trEdgePart = edgePart('top-right');
    var redgePart = edgePart('right');
    var brEdgePart = edgePart('bottom-right');
    var bedgePart = edgePart('bottom');
    var blEdgePart = edgePart('bottom-left');
    var ledgePart = edgePart('left');
    var thumbPart = required({
      name: 'thumb',
      defaults: constant({ dom: { styles: { position: 'absolute' } } }),
      overrides: function (detail) {
        return {
          events: derive([
            redirectToPart(touchstart(), detail, 'spectrum'),
            redirectToPart(touchmove(), detail, 'spectrum'),
            redirectToPart(touchend(), detail, 'spectrum'),
            redirectToPart(mousedown(), detail, 'spectrum'),
            redirectToPart(mousemove(), detail, 'spectrum'),
            redirectToPart(mouseup(), detail, 'spectrum')
          ])
        };
      }
    });
    var spectrumPart = required({
      schema: [state$1('mouseIsDown', function () {
          return Cell(false);
        })],
      name: 'spectrum',
      overrides: function (detail) {
        var modelDetail = detail.model;
        var model = modelDetail.manager;
        var setValueFrom = function (component, simulatedEvent) {
          return model.getValueFromEvent(simulatedEvent).map(function (value) {
            return model.setValueFrom(component, detail, value);
          });
        };
        var touchEvents = derive([
          run(touchstart(), setValueFrom),
          run(touchmove(), setValueFrom)
        ]);
        var mouseEvents = derive([
          run(mousedown(), setValueFrom),
          run(mousemove(), function (spectrum, se) {
            if (detail.mouseIsDown.get()) {
              setValueFrom(spectrum, se);
            }
          })
        ]);
        return {
          behaviours: derive$1(isTouch ? [] : [
            Keying.config({
              mode: 'special',
              onLeft: function (spectrum) {
                return model.onLeft(spectrum, detail);
              },
              onRight: function (spectrum) {
                return model.onRight(spectrum, detail);
              },
              onUp: function (spectrum) {
                return model.onUp(spectrum, detail);
              },
              onDown: function (spectrum) {
                return model.onDown(spectrum, detail);
              }
            }),
            Focusing.config({})
          ]),
          events: isTouch ? touchEvents : mouseEvents
        };
      }
    });
    var SliderParts = [
      labelPart,
      ledgePart,
      redgePart,
      tedgePart,
      bedgePart,
      tlEdgePart,
      trEdgePart,
      blEdgePart,
      brEdgePart,
      thumbPart,
      spectrumPart
    ];

    var onLoad$1 = function (component, repConfig, repState) {
      repConfig.store.manager.onLoad(component, repConfig, repState);
    };
    var onUnload = function (component, repConfig, repState) {
      repConfig.store.manager.onUnload(component, repConfig, repState);
    };
    var setValue = function (component, repConfig, repState, data) {
      repConfig.store.manager.setValue(component, repConfig, repState, data);
    };
    var getValue = function (component, repConfig, repState) {
      return repConfig.store.manager.getValue(component, repConfig, repState);
    };
    var getState = function (component, repConfig, repState) {
      return repState;
    };

    var RepresentApis = /*#__PURE__*/Object.freeze({
        onLoad: onLoad$1,
        onUnload: onUnload,
        setValue: setValue,
        getValue: getValue,
        getState: getState
    });

    var events$5 = function (repConfig, repState) {
      var es = repConfig.resetOnDom ? [
        runOnAttached(function (comp, se) {
          onLoad$1(comp, repConfig, repState);
        }),
        runOnDetached(function (comp, se) {
          onUnload(comp, repConfig, repState);
        })
      ] : [loadEvent(repConfig, repState, onLoad$1)];
      return derive(es);
    };

    var ActiveRepresenting = /*#__PURE__*/Object.freeze({
        events: events$5
    });

    var memory = function () {
      var data = Cell(null);
      var readState = function () {
        return {
          mode: 'memory',
          value: data.get()
        };
      };
      var isNotSet = function () {
        return data.get() === null;
      };
      var clear = function () {
        data.set(null);
      };
      return nu$6({
        set: data.set,
        get: data.get,
        isNotSet: isNotSet,
        clear: clear,
        readState: readState
      });
    };
    var manual = function () {
      var readState = function () {
      };
      return nu$6({ readState: readState });
    };
    var dataset = function () {
      var dataByValue = Cell({});
      var dataByText = Cell({});
      var readState = function () {
        return {
          mode: 'dataset',
          dataByValue: dataByValue.get(),
          dataByText: dataByText.get()
        };
      };
      var clear = function () {
        dataByValue.set({});
        dataByText.set({});
      };
      var lookup = function (itemString) {
        return readOptFrom$1(dataByValue.get(), itemString).orThunk(function () {
          return readOptFrom$1(dataByText.get(), itemString);
        });
      };
      var update = function (items) {
        var currentDataByValue = dataByValue.get();
        var currentDataByText = dataByText.get();
        var newDataByValue = {};
        var newDataByText = {};
        each$1(items, function (item) {
          newDataByValue[item.value] = item;
          readOptFrom$1(item, 'meta').each(function (meta) {
            readOptFrom$1(meta, 'text').each(function (text) {
              newDataByText[text] = item;
            });
          });
        });
        dataByValue.set(__assign({}, currentDataByValue, newDataByValue));
        dataByText.set(__assign({}, currentDataByText, newDataByText));
      };
      return nu$6({
        readState: readState,
        lookup: lookup,
        update: update,
        clear: clear
      });
    };
    var init$2 = function (spec) {
      return spec.store.manager.state(spec);
    };

    var RepresentState = /*#__PURE__*/Object.freeze({
        memory: memory,
        dataset: dataset,
        manual: manual,
        init: init$2
    });

    var setValue$1 = function (component, repConfig, repState, data) {
      var store = repConfig.store;
      repState.update([data]);
      store.setValue(component, data);
      repConfig.onSetValue(component, data);
    };
    var getValue$1 = function (component, repConfig, repState) {
      var store = repConfig.store;
      var key = store.getDataKey(component);
      return repState.lookup(key).fold(function () {
        return store.getFallbackEntry(key);
      }, function (data) {
        return data;
      });
    };
    var onLoad$2 = function (component, repConfig, repState) {
      var store = repConfig.store;
      store.initialValue.each(function (data) {
        setValue$1(component, repConfig, repState, data);
      });
    };
    var onUnload$1 = function (component, repConfig, repState) {
      repState.clear();
    };
    var DatasetStore = [
      option('initialValue'),
      strict$1('getFallbackEntry'),
      strict$1('getDataKey'),
      strict$1('setValue'),
      output$1('manager', {
        setValue: setValue$1,
        getValue: getValue$1,
        onLoad: onLoad$2,
        onUnload: onUnload$1,
        state: dataset
      })
    ];

    var getValue$2 = function (component, repConfig, repState) {
      return repConfig.store.getValue(component);
    };
    var setValue$2 = function (component, repConfig, repState, data) {
      repConfig.store.setValue(component, data);
      repConfig.onSetValue(component, data);
    };
    var onLoad$3 = function (component, repConfig, repState) {
      repConfig.store.initialValue.each(function (data) {
        repConfig.store.setValue(component, data);
      });
    };
    var ManualStore = [
      strict$1('getValue'),
      defaulted$1('setValue', noop),
      option('initialValue'),
      output$1('manager', {
        setValue: setValue$2,
        getValue: getValue$2,
        onLoad: onLoad$3,
        onUnload: noop,
        state: NoState.init
      })
    ];

    var setValue$3 = function (component, repConfig, repState, data) {
      repState.set(data);
      repConfig.onSetValue(component, data);
    };
    var getValue$3 = function (component, repConfig, repState) {
      return repState.get();
    };
    var onLoad$4 = function (component, repConfig, repState) {
      repConfig.store.initialValue.each(function (initVal) {
        if (repState.isNotSet()) {
          repState.set(initVal);
        }
      });
    };
    var onUnload$2 = function (component, repConfig, repState) {
      repState.clear();
    };
    var MemoryStore = [
      option('initialValue'),
      output$1('manager', {
        setValue: setValue$3,
        getValue: getValue$3,
        onLoad: onLoad$4,
        onUnload: onUnload$2,
        state: memory
      })
    ];

    var RepresentSchema = [
      defaultedOf('store', { mode: 'memory' }, choose$1('mode', {
        memory: MemoryStore,
        manual: ManualStore,
        dataset: DatasetStore
      })),
      onHandler('onSetValue'),
      defaulted$1('resetOnDom', false)
    ];

    var Representing = create$1({
      fields: RepresentSchema,
      name: 'representing',
      active: ActiveRepresenting,
      apis: RepresentApis,
      extra: {
        setValueFrom: function (component, source) {
          var value = Representing.getValue(source);
          Representing.setValue(component, value);
        }
      },
      state: RepresentState
    });

    var r = function (left, top) {
      var translate = function (x, y) {
        return r(left + x, top + y);
      };
      return {
        left: constant(left),
        top: constant(top),
        translate: translate
      };
    };
    var Position = r;

    var isTouch$1 = PlatformDetection$1.detect().deviceType.isTouch();
    var _sliderChangeEvent = 'slider.change.value';
    var sliderChangeEvent = constant(_sliderChangeEvent);
    var getEventSource = function (simulatedEvent) {
      var evt = simulatedEvent.event().raw();
      if (isTouch$1) {
        var touchEvent = evt;
        return touchEvent.touches !== undefined && touchEvent.touches.length === 1 ? Option.some(touchEvent.touches[0]).map(function (t) {
          return Position(t.clientX, t.clientY);
        }) : Option.none();
      } else {
        var mouseEvent = evt;
        return mouseEvent.clientX !== undefined ? Option.some(mouseEvent).map(function (me) {
          return Position(me.clientX, me.clientY);
        }) : Option.none();
      }
    };

    var reduceBy = function (value, min, max, step) {
      if (value < min) {
        return value;
      } else if (value > max) {
        return max;
      } else if (value === min) {
        return min - 1;
      } else {
        return Math.max(min, value - step);
      }
    };
    var increaseBy = function (value, min, max, step) {
      if (value > max) {
        return value;
      } else if (value < min) {
        return min;
      } else if (value === max) {
        return max + 1;
      } else {
        return Math.min(max, value + step);
      }
    };
    var capValue = function (value, min, max) {
      return Math.max(min, Math.min(max, value));
    };
    var snapValueOf = function (value, min, max, step, snapStart) {
      return snapStart.fold(function () {
        var initValue = value - min;
        var extraValue = Math.round(initValue / step) * step;
        return capValue(min + extraValue, min - 1, max + 1);
      }, function (start) {
        var remainder = (value - start) % step;
        var adjustment = Math.round(remainder / step);
        var rawSteps = Math.floor((value - start) / step);
        var maxSteps = Math.floor((max - start) / step);
        var numSteps = Math.min(maxSteps, rawSteps + adjustment);
        var r = start + numSteps * step;
        return Math.max(start, r);
      });
    };
    var findOffsetOf = function (value, min, max) {
      return Math.min(max, Math.max(value, min)) - min;
    };
    var findValueOf = function (args) {
      var min = args.min, max = args.max, range = args.range, value = args.value, step = args.step, snap = args.snap, snapStart = args.snapStart, rounded = args.rounded, hasMinEdge = args.hasMinEdge, hasMaxEdge = args.hasMaxEdge, minBound = args.minBound, maxBound = args.maxBound, screenRange = args.screenRange;
      var capMin = hasMinEdge ? min - 1 : min;
      var capMax = hasMaxEdge ? max + 1 : max;
      if (value < minBound) {
        return capMin;
      } else if (value > maxBound) {
        return capMax;
      } else {
        var offset = findOffsetOf(value, minBound, maxBound);
        var newValue = capValue(offset / screenRange * range + min, capMin, capMax);
        if (snap && newValue >= min && newValue <= max) {
          return snapValueOf(newValue, min, max, step, snapStart);
        } else if (rounded) {
          return Math.round(newValue);
        } else {
          return newValue;
        }
      }
    };
    var findOffsetOfValue = function (args) {
      var min = args.min, max = args.max, range = args.range, value = args.value, hasMinEdge = args.hasMinEdge, hasMaxEdge = args.hasMaxEdge, maxBound = args.maxBound, maxOffset = args.maxOffset, centerMinEdge = args.centerMinEdge, centerMaxEdge = args.centerMaxEdge;
      if (value < min) {
        return hasMinEdge ? 0 : centerMinEdge;
      } else if (value > max) {
        return hasMaxEdge ? maxBound : centerMaxEdge;
      } else {
        return (value - min) / range * maxOffset;
      }
    };

    var api$1 = Dimension('width', function (element) {
      return element.dom().offsetWidth;
    });
    var set$5 = function (element, h) {
      api$1.set(element, h);
    };
    var get$7 = function (element) {
      return api$1.get(element);
    };

    var t = 'top', r$1 = 'right', b = 'bottom', l = 'left';
    var minX = function (detail) {
      return detail.model.minX;
    };
    var minY = function (detail) {
      return detail.model.minY;
    };
    var min1X = function (detail) {
      return detail.model.minX - 1;
    };
    var min1Y = function (detail) {
      return detail.model.minY - 1;
    };
    var maxX = function (detail) {
      return detail.model.maxX;
    };
    var maxY = function (detail) {
      return detail.model.maxY;
    };
    var max1X = function (detail) {
      return detail.model.maxX + 1;
    };
    var max1Y = function (detail) {
      return detail.model.maxY + 1;
    };
    var range$1 = function (detail, max, min) {
      return max(detail) - min(detail);
    };
    var xRange = function (detail) {
      return range$1(detail, maxX, minX);
    };
    var yRange = function (detail) {
      return range$1(detail, maxY, minY);
    };
    var halfX = function (detail) {
      return xRange(detail) / 2;
    };
    var halfY = function (detail) {
      return yRange(detail) / 2;
    };
    var step$1 = function (detail) {
      return detail.stepSize;
    };
    var snap = function (detail) {
      return detail.snapToGrid;
    };
    var snapStart = function (detail) {
      return detail.snapStart;
    };
    var rounded = function (detail) {
      return detail.rounded;
    };
    var hasEdge = function (detail, edgeName) {
      return detail[edgeName + '-edge'] !== undefined;
    };
    var hasLEdge = function (detail) {
      return hasEdge(detail, l);
    };
    var hasREdge = function (detail) {
      return hasEdge(detail, r$1);
    };
    var hasTEdge = function (detail) {
      return hasEdge(detail, t);
    };
    var hasBEdge = function (detail) {
      return hasEdge(detail, b);
    };
    var currentValue = function (detail) {
      return detail.model.value.get();
    };

    var xValue = function (x) {
      return { x: constant(x) };
    };
    var yValue = function (y) {
      return { y: constant(y) };
    };
    var xyValue = function (x, y) {
      return {
        x: constant(x),
        y: constant(y)
      };
    };
    var fireSliderChange = function (component, value) {
      emitWith(component, sliderChangeEvent(), { value: value });
    };
    var setToTLEdgeXY = function (edge, detail) {
      fireSliderChange(edge, xyValue(min1X(detail), min1Y(detail)));
    };
    var setToTEdge = function (edge, detail) {
      fireSliderChange(edge, yValue(min1Y(detail)));
    };
    var setToTEdgeXY = function (edge, detail) {
      fireSliderChange(edge, xyValue(halfX(detail), min1Y(detail)));
    };
    var setToTREdgeXY = function (edge, detail) {
      fireSliderChange(edge, xyValue(max1X(detail), min1Y(detail)));
    };
    var setToREdge = function (edge, detail) {
      fireSliderChange(edge, xValue(max1X(detail)));
    };
    var setToREdgeXY = function (edge, detail) {
      fireSliderChange(edge, xyValue(max1X(detail), halfY(detail)));
    };
    var setToBREdgeXY = function (edge, detail) {
      fireSliderChange(edge, xyValue(max1X(detail), max1Y(detail)));
    };
    var setToBEdge = function (edge, detail) {
      fireSliderChange(edge, yValue(max1Y(detail)));
    };
    var setToBEdgeXY = function (edge, detail) {
      fireSliderChange(edge, xyValue(halfX(detail), max1Y(detail)));
    };
    var setToBLEdgeXY = function (edge, detail) {
      fireSliderChange(edge, xyValue(min1X(detail), max1Y(detail)));
    };
    var setToLEdge = function (edge, detail) {
      fireSliderChange(edge, xValue(min1X(detail)));
    };
    var setToLEdgeXY = function (edge, detail) {
      fireSliderChange(edge, xyValue(min1X(detail), halfY(detail)));
    };

    var top = 'top', right = 'right', bottom = 'bottom', left = 'left', width = 'width', height = 'height';
    var getBounds = function (component) {
      return component.element().dom().getBoundingClientRect();
    };
    var getBoundsProperty = function (bounds, property) {
      return bounds[property];
    };
    var getMinXBounds = function (component) {
      var bounds = getBounds(component);
      return getBoundsProperty(bounds, left);
    };
    var getMaxXBounds = function (component) {
      var bounds = getBounds(component);
      return getBoundsProperty(bounds, right);
    };
    var getMinYBounds = function (component) {
      var bounds = getBounds(component);
      return getBoundsProperty(bounds, top);
    };
    var getMaxYBounds = function (component) {
      var bounds = getBounds(component);
      return getBoundsProperty(bounds, bottom);
    };
    var getXScreenRange = function (component) {
      var bounds = getBounds(component);
      return getBoundsProperty(bounds, width);
    };
    var getYScreenRange = function (component) {
      var bounds = getBounds(component);
      return getBoundsProperty(bounds, height);
    };
    var getCenterOffsetOf = function (componentMinEdge, componentMaxEdge, spectrumMinEdge) {
      return (componentMinEdge + componentMaxEdge) / 2 - spectrumMinEdge;
    };
    var getXCenterOffSetOf = function (component, spectrum) {
      var componentBounds = getBounds(component);
      var spectrumBounds = getBounds(spectrum);
      var componentMinEdge = getBoundsProperty(componentBounds, left);
      var componentMaxEdge = getBoundsProperty(componentBounds, right);
      var spectrumMinEdge = getBoundsProperty(spectrumBounds, left);
      return getCenterOffsetOf(componentMinEdge, componentMaxEdge, spectrumMinEdge);
    };
    var getYCenterOffSetOf = function (component, spectrum) {
      var componentBounds = getBounds(component);
      var spectrumBounds = getBounds(spectrum);
      var componentMinEdge = getBoundsProperty(componentBounds, top);
      var componentMaxEdge = getBoundsProperty(componentBounds, bottom);
      var spectrumMinEdge = getBoundsProperty(spectrumBounds, top);
      return getCenterOffsetOf(componentMinEdge, componentMaxEdge, spectrumMinEdge);
    };

    var fireSliderChange$1 = function (spectrum, value) {
      emitWith(spectrum, sliderChangeEvent(), { value: value });
    };
    var sliderValue = function (x) {
      return { x: constant(x) };
    };
    var findValueOfOffset = function (spectrum, detail, left) {
      var args = {
        min: minX(detail),
        max: maxX(detail),
        range: xRange(detail),
        value: left,
        step: step$1(detail),
        snap: snap(detail),
        snapStart: snapStart(detail),
        rounded: rounded(detail),
        hasMinEdge: hasLEdge(detail),
        hasMaxEdge: hasREdge(detail),
        minBound: getMinXBounds(spectrum),
        maxBound: getMaxXBounds(spectrum),
        screenRange: getXScreenRange(spectrum)
      };
      return findValueOf(args);
    };
    var setValueFrom = function (spectrum, detail, value) {
      var xValue = findValueOfOffset(spectrum, detail, value);
      var sliderVal = sliderValue(xValue);
      fireSliderChange$1(spectrum, sliderVal);
      return xValue;
    };
    var setToMin = function (spectrum, detail) {
      var min = minX(detail);
      fireSliderChange$1(spectrum, sliderValue(min));
    };
    var setToMax = function (spectrum, detail) {
      var max = maxX(detail);
      fireSliderChange$1(spectrum, sliderValue(max));
    };
    var moveBy = function (direction, spectrum, detail) {
      var f = direction > 0 ? increaseBy : reduceBy;
      var xValue = f(currentValue(detail).x(), minX(detail), maxX(detail), step$1(detail));
      fireSliderChange$1(spectrum, sliderValue(xValue));
      return Option.some(xValue);
    };
    var handleMovement = function (direction) {
      return function (spectrum, detail) {
        return moveBy(direction, spectrum, detail).map(function () {
          return true;
        });
      };
    };
    var getValueFromEvent = function (simulatedEvent) {
      var pos = getEventSource(simulatedEvent);
      return pos.map(function (p) {
        return p.left();
      });
    };
    var findOffsetOfValue$1 = function (spectrum, detail, value, minEdge, maxEdge) {
      var minOffset = 0;
      var maxOffset = getXScreenRange(spectrum);
      var centerMinEdge = minEdge.bind(function (edge) {
        return Option.some(getXCenterOffSetOf(edge, spectrum));
      }).getOr(minOffset);
      var centerMaxEdge = maxEdge.bind(function (edge) {
        return Option.some(getXCenterOffSetOf(edge, spectrum));
      }).getOr(maxOffset);
      var args = {
        min: minX(detail),
        max: maxX(detail),
        range: xRange(detail),
        value: value,
        hasMinEdge: hasLEdge(detail),
        hasMaxEdge: hasREdge(detail),
        minBound: getMinXBounds(spectrum),
        minOffset: minOffset,
        maxBound: getMaxXBounds(spectrum),
        maxOffset: maxOffset,
        centerMinEdge: centerMinEdge,
        centerMaxEdge: centerMaxEdge
      };
      return findOffsetOfValue(args);
    };
    var findPositionOfValue = function (slider, spectrum, value, minEdge, maxEdge, detail) {
      var offset = findOffsetOfValue$1(spectrum, detail, value, minEdge, maxEdge);
      return getMinXBounds(spectrum) - getMinXBounds(slider) + offset;
    };
    var setPositionFromValue = function (slider, thumb, detail, edges) {
      var value = currentValue(detail);
      var pos = findPositionOfValue(slider, edges.getSpectrum(slider), value.x(), edges.getLeftEdge(slider), edges.getRightEdge(slider), detail);
      var thumbRadius = get$7(thumb.element()) / 2;
      set$3(thumb.element(), 'left', pos - thumbRadius + 'px');
    };
    var onLeft = handleMovement(-1);
    var onRight = handleMovement(1);
    var onUp = Option.none;
    var onDown = Option.none;
    var edgeActions = {
      'top-left': Option.none(),
      'top': Option.none(),
      'top-right': Option.none(),
      'right': Option.some(setToREdge),
      'bottom-right': Option.none(),
      'bottom': Option.none(),
      'bottom-left': Option.none(),
      'left': Option.some(setToLEdge)
    };

    var HorizontalModel = /*#__PURE__*/Object.freeze({
        setValueFrom: setValueFrom,
        setToMin: setToMin,
        setToMax: setToMax,
        findValueOfOffset: findValueOfOffset,
        getValueFromEvent: getValueFromEvent,
        findPositionOfValue: findPositionOfValue,
        setPositionFromValue: setPositionFromValue,
        onLeft: onLeft,
        onRight: onRight,
        onUp: onUp,
        onDown: onDown,
        edgeActions: edgeActions
    });

    var fireSliderChange$2 = function (spectrum, value) {
      emitWith(spectrum, sliderChangeEvent(), { value: value });
    };
    var sliderValue$1 = function (y) {
      return { y: constant(y) };
    };
    var findValueOfOffset$1 = function (spectrum, detail, top) {
      var args = {
        min: minY(detail),
        max: maxY(detail),
        range: yRange(detail),
        value: top,
        step: step$1(detail),
        snap: snap(detail),
        snapStart: snapStart(detail),
        rounded: rounded(detail),
        hasMinEdge: hasTEdge(detail),
        hasMaxEdge: hasBEdge(detail),
        minBound: getMinYBounds(spectrum),
        maxBound: getMaxYBounds(spectrum),
        screenRange: getYScreenRange(spectrum)
      };
      return findValueOf(args);
    };
    var setValueFrom$1 = function (spectrum, detail, value) {
      var yValue = findValueOfOffset$1(spectrum, detail, value);
      var sliderVal = sliderValue$1(yValue);
      fireSliderChange$2(spectrum, sliderVal);
      return yValue;
    };
    var setToMin$1 = function (spectrum, detail) {
      var min = minY(detail);
      fireSliderChange$2(spectrum, sliderValue$1(min));
    };
    var setToMax$1 = function (spectrum, detail) {
      var max = maxY(detail);
      fireSliderChange$2(spectrum, sliderValue$1(max));
    };
    var moveBy$1 = function (direction, spectrum, detail) {
      var f = direction > 0 ? increaseBy : reduceBy;
      var yValue = f(currentValue(detail).y(), minY(detail), maxY(detail), step$1(detail));
      fireSliderChange$2(spectrum, sliderValue$1(yValue));
      return Option.some(yValue);
    };
    var handleMovement$1 = function (direction) {
      return function (spectrum, detail) {
        return moveBy$1(direction, spectrum, detail).map(function () {
          return true;
        });
      };
    };
    var getValueFromEvent$1 = function (simulatedEvent) {
      var pos = getEventSource(simulatedEvent);
      return pos.map(function (p) {
        return p.top();
      });
    };
    var findOffsetOfValue$2 = function (spectrum, detail, value, minEdge, maxEdge) {
      var minOffset = 0;
      var maxOffset = getYScreenRange(spectrum);
      var centerMinEdge = minEdge.bind(function (edge) {
        return Option.some(getYCenterOffSetOf(edge, spectrum));
      }).getOr(minOffset);
      var centerMaxEdge = maxEdge.bind(function (edge) {
        return Option.some(getYCenterOffSetOf(edge, spectrum));
      }).getOr(maxOffset);
      var args = {
        min: minY(detail),
        max: maxY(detail),
        range: yRange(detail),
        value: value,
        hasMinEdge: hasTEdge(detail),
        hasMaxEdge: hasBEdge(detail),
        minBound: getMinYBounds(spectrum),
        minOffset: minOffset,
        maxBound: getMaxYBounds(spectrum),
        maxOffset: maxOffset,
        centerMinEdge: centerMinEdge,
        centerMaxEdge: centerMaxEdge
      };
      return findOffsetOfValue(args);
    };
    var findPositionOfValue$1 = function (slider, spectrum, value, minEdge, maxEdge, detail) {
      var offset = findOffsetOfValue$2(spectrum, detail, value, minEdge, maxEdge);
      return getMinYBounds(spectrum) - getMinYBounds(slider) + offset;
    };
    var setPositionFromValue$1 = function (slider, thumb, detail, edges) {
      var value = currentValue(detail);
      var pos = findPositionOfValue$1(slider, edges.getSpectrum(slider), value.y(), edges.getTopEdge(slider), edges.getBottomEdge(slider), detail);
      var thumbRadius = get$5(thumb.element()) / 2;
      set$3(thumb.element(), 'top', pos - thumbRadius + 'px');
    };
    var onLeft$1 = Option.none;
    var onRight$1 = Option.none;
    var onUp$1 = handleMovement$1(-1);
    var onDown$1 = handleMovement$1(1);
    var edgeActions$1 = {
      'top-left': Option.none(),
      'top': Option.some(setToTEdge),
      'top-right': Option.none(),
      'right': Option.none(),
      'bottom-right': Option.none(),
      'bottom': Option.some(setToBEdge),
      'bottom-left': Option.none(),
      'left': Option.none()
    };

    var VerticalModel = /*#__PURE__*/Object.freeze({
        setValueFrom: setValueFrom$1,
        setToMin: setToMin$1,
        setToMax: setToMax$1,
        findValueOfOffset: findValueOfOffset$1,
        getValueFromEvent: getValueFromEvent$1,
        findPositionOfValue: findPositionOfValue$1,
        setPositionFromValue: setPositionFromValue$1,
        onLeft: onLeft$1,
        onRight: onRight$1,
        onUp: onUp$1,
        onDown: onDown$1,
        edgeActions: edgeActions$1
    });

    var fireSliderChange$3 = function (spectrum, value) {
      emitWith(spectrum, sliderChangeEvent(), { value: value });
    };
    var sliderValue$2 = function (x, y) {
      return {
        x: constant(x),
        y: constant(y)
      };
    };
    var setValueFrom$2 = function (spectrum, detail, value) {
      var xValue = findValueOfOffset(spectrum, detail, value.left());
      var yValue = findValueOfOffset$1(spectrum, detail, value.top());
      var val = sliderValue$2(xValue, yValue);
      fireSliderChange$3(spectrum, val);
      return val;
    };
    var moveBy$2 = function (direction, isVerticalMovement, spectrum, detail) {
      var f = direction > 0 ? increaseBy : reduceBy;
      var xValue = isVerticalMovement ? currentValue(detail).x() : f(currentValue(detail).x(), minX(detail), maxX(detail), step$1(detail));
      var yValue = !isVerticalMovement ? currentValue(detail).y() : f(currentValue(detail).y(), minY(detail), maxY(detail), step$1(detail));
      fireSliderChange$3(spectrum, sliderValue$2(xValue, yValue));
      return Option.some(xValue);
    };
    var handleMovement$2 = function (direction, isVerticalMovement) {
      return function (spectrum, detail) {
        return moveBy$2(direction, isVerticalMovement, spectrum, detail).map(function () {
          return true;
        });
      };
    };
    var setToMin$2 = function (spectrum, detail) {
      var mX = minX(detail);
      var mY = minY(detail);
      fireSliderChange$3(spectrum, sliderValue$2(mX, mY));
    };
    var setToMax$2 = function (spectrum, detail) {
      var mX = maxX(detail);
      var mY = maxY(detail);
      fireSliderChange$3(spectrum, sliderValue$2(mX, mY));
    };
    var getValueFromEvent$2 = function (simulatedEvent) {
      return getEventSource(simulatedEvent);
    };
    var setPositionFromValue$2 = function (slider, thumb, detail, edges) {
      var value = currentValue(detail);
      var xPos = findPositionOfValue(slider, edges.getSpectrum(slider), value.x(), edges.getLeftEdge(slider), edges.getRightEdge(slider), detail);
      var yPos = findPositionOfValue$1(slider, edges.getSpectrum(slider), value.y(), edges.getTopEdge(slider), edges.getBottomEdge(slider), detail);
      var thumbXRadius = get$7(thumb.element()) / 2;
      var thumbYRadius = get$5(thumb.element()) / 2;
      set$3(thumb.element(), 'left', xPos - thumbXRadius + 'px');
      set$3(thumb.element(), 'top', yPos - thumbYRadius + 'px');
    };
    var onLeft$2 = handleMovement$2(-1, false);
    var onRight$2 = handleMovement$2(1, false);
    var onUp$2 = handleMovement$2(-1, true);
    var onDown$2 = handleMovement$2(1, true);
    var edgeActions$2 = {
      'top-left': Option.some(setToTLEdgeXY),
      'top': Option.some(setToTEdgeXY),
      'top-right': Option.some(setToTREdgeXY),
      'right': Option.some(setToREdgeXY),
      'bottom-right': Option.some(setToBREdgeXY),
      'bottom': Option.some(setToBEdgeXY),
      'bottom-left': Option.some(setToBLEdgeXY),
      'left': Option.some(setToLEdgeXY)
    };

    var TwoDModel = /*#__PURE__*/Object.freeze({
        setValueFrom: setValueFrom$2,
        setToMin: setToMin$2,
        setToMax: setToMax$2,
        getValueFromEvent: getValueFromEvent$2,
        setPositionFromValue: setPositionFromValue$2,
        onLeft: onLeft$2,
        onRight: onRight$2,
        onUp: onUp$2,
        onDown: onDown$2,
        edgeActions: edgeActions$2
    });

    var isTouch$2 = PlatformDetection$1.detect().deviceType.isTouch();
    var SliderSchema = [
      defaulted$1('stepSize', 1),
      defaulted$1('onChange', noop),
      defaulted$1('onChoose', noop),
      defaulted$1('onInit', noop),
      defaulted$1('onDragStart', noop),
      defaulted$1('onDragEnd', noop),
      defaulted$1('snapToGrid', false),
      defaulted$1('rounded', true),
      option('snapStart'),
      strictOf('model', choose$1('mode', {
        x: [
          defaulted$1('minX', 0),
          defaulted$1('maxX', 100),
          state$1('value', function (spec) {
            return Cell(spec.mode.minX);
          }),
          strict$1('getInitialValue'),
          output$1('manager', HorizontalModel)
        ],
        y: [
          defaulted$1('minY', 0),
          defaulted$1('maxY', 100),
          state$1('value', function (spec) {
            return Cell(spec.mode.minY);
          }),
          strict$1('getInitialValue'),
          output$1('manager', VerticalModel)
        ],
        xy: [
          defaulted$1('minX', 0),
          defaulted$1('maxX', 100),
          defaulted$1('minY', 0),
          defaulted$1('maxY', 100),
          state$1('value', function (spec) {
            return Cell({
              x: constant(spec.mode.minX),
              y: constant(spec.mode.minY)
            });
          }),
          strict$1('getInitialValue'),
          output$1('manager', TwoDModel)
        ]
      })),
      field$1('sliderBehaviours', [
        Keying,
        Representing
      ])
    ].concat(!isTouch$2 ? [state$1('mouseIsDown', function () {
        return Cell(false);
      })] : []);

    var isTouch$3 = PlatformDetection$1.detect().deviceType.isTouch();
    var sketch$1 = function (detail, components$$1, _spec, _externals) {
      var getThumb = function (component) {
        return getPartOrDie(component, detail, 'thumb');
      };
      var getSpectrum = function (component) {
        return getPartOrDie(component, detail, 'spectrum');
      };
      var getLeftEdge = function (component) {
        return getPart(component, detail, 'left-edge');
      };
      var getRightEdge = function (component) {
        return getPart(component, detail, 'right-edge');
      };
      var getTopEdge = function (component) {
        return getPart(component, detail, 'top-edge');
      };
      var getBottomEdge = function (component) {
        return getPart(component, detail, 'bottom-edge');
      };
      var modelDetail = detail.model;
      var model = modelDetail.manager;
      var refresh = function (slider, thumb) {
        model.setPositionFromValue(slider, thumb, detail, {
          getLeftEdge: getLeftEdge,
          getRightEdge: getRightEdge,
          getTopEdge: getTopEdge,
          getBottomEdge: getBottomEdge,
          getSpectrum: getSpectrum
        });
      };
      var changeValue = function (slider, newValue) {
        modelDetail.value.set(newValue);
        var thumb = getThumb(slider);
        refresh(slider, thumb);
        detail.onChange(slider, thumb, newValue);
        return Option.some(true);
      };
      var resetToMin = function (slider) {
        model.setToMin(slider, detail);
      };
      var resetToMax = function (slider) {
        model.setToMax(slider, detail);
      };
      var touchEvents = [
        run(touchstart(), function (slider, _simulatedEvent) {
          detail.onDragStart(slider, getThumb(slider));
        }),
        run(touchend(), function (slider, _simulatedEvent) {
          detail.onDragEnd(slider, getThumb(slider));
        })
      ];
      var mouseEvents = [
        run(mousedown(), function (slider, simulatedEvent) {
          simulatedEvent.stop();
          detail.onDragStart(slider, getThumb(slider));
          detail.mouseIsDown.set(true);
        }),
        run(mouseup(), function (slider, _simulatedEvent) {
          detail.onDragEnd(slider, getThumb(slider));
        })
      ];
      var uiEventsArr = isTouch$3 ? touchEvents : mouseEvents;
      return {
        uid: detail.uid,
        dom: detail.dom,
        components: components$$1,
        behaviours: augment(detail.sliderBehaviours, flatten([
          !isTouch$3 ? [Keying.config({
              mode: 'special',
              focusIn: function (slider) {
                return getPart(slider, detail, 'spectrum').map(Keying.focusIn).map(constant(true));
              }
            })] : [],
          [
            Representing.config({
              store: {
                mode: 'manual',
                getValue: function (_) {
                  return modelDetail.value.get();
                }
              }
            }),
            Receiving.config({
              channels: {
                'mouse.released': {
                  onReceive: function (slider, se) {
                    var wasDown = detail.mouseIsDown.get();
                    detail.mouseIsDown.set(false);
                    if (wasDown) {
                      getPart(slider, detail, 'thumb').each(function (thumb) {
                        var value = modelDetail.value.get();
                        detail.onChoose(slider, thumb, value);
                      });
                    }
                  }
                }
              }
            })
          ]
        ])),
        events: derive([
          run(sliderChangeEvent(), function (slider, simulatedEvent) {
            changeValue(slider, simulatedEvent.event().value());
          }),
          runOnAttached(function (slider, simulatedEvent) {
            var getInitial = modelDetail.getInitialValue();
            modelDetail.value.set(getInitial);
            var thumb = getThumb(slider);
            refresh(slider, thumb);
            var spectrum = getSpectrum(slider);
            detail.onInit(slider, thumb, spectrum, modelDetail.value.get());
          })
        ].concat(uiEventsArr)),
        apis: {
          resetToMin: resetToMin,
          resetToMax: resetToMax,
          changeValue: changeValue,
          refresh: refresh
        },
        domModification: { styles: { position: 'relative' } }
      };
    };

    var Slider = composite$1({
      name: 'Slider',
      configFields: SliderSchema,
      partFields: SliderParts,
      factory: sketch$1,
      apis: {
        resetToMin: function (apis, slider) {
          apis.resetToMin(slider);
        },
        resetToMax: function (apis, slider) {
          apis.resetToMax(slider);
        },
        refresh: function (apis, slider) {
          apis.refresh(slider);
        }
      }
    });

    var button = function (realm, clazz, makeItems, editor) {
      return Buttons.forToolbar(clazz, function () {
        var items = makeItems();
        realm.setContextToolbar([{
            label: clazz + ' group',
            items: items
          }]);
      }, {}, editor);
    };

    var BLACK = -1;
    var makeSlider = function (spec$$1) {
      var getColor = function (hue) {
        if (hue < 0) {
          return 'black';
        } else if (hue > 360) {
          return 'white';
        } else {
          return 'hsl(' + hue + ', 100%, 50%)';
        }
      };
      var onInit = function (slider, thumb, spectrum, value) {
        var color = getColor(value.x());
        set$3(thumb.element(), 'background-color', color);
      };
      var onChange = function (slider, thumb, value) {
        var color = getColor(value.x());
        set$3(thumb.element(), 'background-color', color);
        spec$$1.onChange(slider, thumb, color);
      };
      return Slider.sketch({
        dom: dom$2('<div class="${prefix}-slider ${prefix}-hue-slider-container"></div>'),
        components: [
          Slider.parts()['left-edge'](spec('<div class="${prefix}-hue-slider-black"></div>')),
          Slider.parts().spectrum({
            dom: dom$2('<div class="${prefix}-slider-gradient-container"></div>'),
            components: [spec('<div class="${prefix}-slider-gradient"></div>')],
            behaviours: derive$1([Toggling.config({ toggleClass: Styles.resolve('thumb-active') })])
          }),
          Slider.parts()['right-edge'](spec('<div class="${prefix}-hue-slider-white"></div>')),
          Slider.parts().thumb({
            dom: dom$2('<div class="${prefix}-slider-thumb"></div>'),
            behaviours: derive$1([Toggling.config({ toggleClass: Styles.resolve('thumb-active') })])
          })
        ],
        onChange: onChange,
        onDragStart: function (slider, thumb) {
          Toggling.on(thumb);
        },
        onDragEnd: function (slider, thumb) {
          Toggling.off(thumb);
        },
        onInit: onInit,
        stepSize: 10,
        model: {
          mode: 'x',
          minX: 0,
          maxX: 360,
          getInitialValue: function () {
            return {
              x: function () {
                return spec$$1.getInitialValue();
              }
            };
          }
        },
        sliderBehaviours: derive$1([Receivers.orientation(Slider.refresh)])
      });
    };
    var makeItems = function (spec$$1) {
      return [makeSlider(spec$$1)];
    };
    var sketch$2 = function (realm, editor) {
      var spec$$1 = {
        onChange: function (slider, thumb, color) {
          editor.undoManager.transact(function () {
            editor.formatter.apply('forecolor', { value: color });
            editor.nodeChanged();
          });
        },
        getInitialValue: function () {
          return BLACK;
        }
      };
      return button(realm, 'color-levels', function () {
        return makeItems(spec$$1);
      }, editor);
    };
    var ColorSlider = {
      makeItems: makeItems,
      sketch: sketch$2
    };

    var schema$7 = objOfOnly([
      strict$1('getInitialValue'),
      strict$1('onChange'),
      strict$1('category'),
      strict$1('sizes')
    ]);
    var sketch$3 = function (rawSpec) {
      var spec$$1 = asRawOrDie('SizeSlider', schema$7, rawSpec);
      var isValidValue = function (valueIndex) {
        return valueIndex >= 0 && valueIndex < spec$$1.sizes.length;
      };
      var onChange = function (slider, thumb, valueIndex) {
        var index = valueIndex.x();
        if (isValidValue(index)) {
          spec$$1.onChange(index);
        }
      };
      return Slider.sketch({
        dom: {
          tag: 'div',
          classes: [
            Styles.resolve('slider-' + spec$$1.category + '-size-container'),
            Styles.resolve('slider'),
            Styles.resolve('slider-size-container')
          ]
        },
        onChange: onChange,
        onDragStart: function (slider, thumb) {
          Toggling.on(thumb);
        },
        onDragEnd: function (slider, thumb) {
          Toggling.off(thumb);
        },
        model: {
          mode: 'x',
          minX: 0,
          maxX: spec$$1.sizes.length - 1,
          getInitialValue: function () {
            return {
              x: function () {
                return spec$$1.getInitialValue();
              }
            };
          }
        },
        stepSize: 1,
        snapToGrid: true,
        sliderBehaviours: derive$1([Receivers.orientation(Slider.refresh)]),
        components: [
          Slider.parts().spectrum({
            dom: dom$2('<div class="${prefix}-slider-size-container"></div>'),
            components: [spec('<div class="${prefix}-slider-size-line"></div>')]
          }),
          Slider.parts().thumb({
            dom: dom$2('<div class="${prefix}-slider-thumb"></div>'),
            behaviours: derive$1([Toggling.config({ toggleClass: Styles.resolve('thumb-active') })])
          })
        ]
      });
    };
    var SizeSlider = { sketch: sketch$3 };

    var candidates = [
      '9px',
      '10px',
      '11px',
      '12px',
      '14px',
      '16px',
      '18px',
      '20px',
      '24px',
      '32px',
      '36px'
    ];
    var defaultSize = 'medium';
    var defaultIndex = 2;
    var indexToSize = function (index) {
      return Option.from(candidates[index]);
    };
    var sizeToIndex = function (size) {
      return findIndex(candidates, function (v) {
        return v === size;
      });
    };
    var getRawOrComputed = function (isRoot, rawStart) {
      var optStart = isElement(rawStart) ? Option.some(rawStart) : parent(rawStart);
      return optStart.map(function (start) {
        var inline = closest(start, function (elem) {
          return getRaw(elem, 'font-size').isSome();
        }, isRoot).bind(function (elem) {
          return getRaw(elem, 'font-size');
        });
        return inline.getOrThunk(function () {
          return get$4(start, 'font-size');
        });
      }).getOr('');
    };
    var getSize = function (editor) {
      var node = editor.selection.getStart();
      var elem = Element$$1.fromDom(node);
      var root = Element$$1.fromDom(editor.getBody());
      var isRoot = function (e) {
        return eq(root, e);
      };
      var elemSize = getRawOrComputed(isRoot, elem);
      return find$2(candidates, function (size) {
        return elemSize === size;
      }).getOr(defaultSize);
    };
    var applySize = function (editor, value$$1) {
      var currentValue = getSize(editor);
      if (currentValue !== value$$1) {
        editor.execCommand('fontSize', false, value$$1);
      }
    };
    var get$8 = function (editor) {
      var size = getSize(editor);
      return sizeToIndex(size).getOr(defaultIndex);
    };
    var apply$1 = function (editor, index) {
      indexToSize(index).each(function (size) {
        applySize(editor, size);
      });
    };
    var FontSizes = {
      candidates: constant(candidates),
      get: get$8,
      apply: apply$1
    };

    var sizes = FontSizes.candidates();
    var makeSlider$1 = function (spec$$1) {
      return SizeSlider.sketch({
        onChange: spec$$1.onChange,
        sizes: sizes,
        category: 'font',
        getInitialValue: spec$$1.getInitialValue
      });
    };
    var makeItems$1 = function (spec$$1) {
      return [
        spec('<span class="${prefix}-toolbar-button ${prefix}-icon-small-font ${prefix}-icon"></span>'),
        makeSlider$1(spec$$1),
        spec('<span class="${prefix}-toolbar-button ${prefix}-icon-large-font ${prefix}-icon"></span>')
      ];
    };
    var sketch$4 = function (realm, editor) {
      var spec$$1 = {
        onChange: function (value) {
          FontSizes.apply(editor, value);
        },
        getInitialValue: function () {
          return FontSizes.get(editor);
        }
      };
      return button(realm, 'font-size', function () {
        return makeItems$1(spec$$1);
      }, editor);
    };

    var record = function (spec) {
      var uid = isSketchSpec(spec) && hasKey$1(spec, 'uid') ? spec.uid : generate$3('memento');
      var get = function (anyInSystem) {
        return anyInSystem.getSystem().getByUid(uid).getOrDie();
      };
      var getOpt = function (anyInSystem) {
        return anyInSystem.getSystem().getByUid(uid).fold(Option.none, Option.some);
      };
      var asSpec = function () {
        return __assign({}, spec, { uid: uid });
      };
      return {
        get: get,
        getOpt: getOpt,
        asSpec: asSpec
      };
    };

    function create$3(width, height) {
      return resize(document.createElement('canvas'), width, height);
    }
    function clone$2(canvas) {
      var tCanvas, ctx;
      tCanvas = create$3(canvas.width, canvas.height);
      ctx = get2dContext(tCanvas);
      ctx.drawImage(canvas, 0, 0);
      return tCanvas;
    }
    function get2dContext(canvas) {
      return canvas.getContext('2d');
    }
    function get3dContext(canvas) {
      var gl = null;
      try {
        gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
      } catch (e) {
      }
      if (!gl) {
        gl = null;
      }
      return gl;
    }
    function resize(canvas, width, height) {
      canvas.width = width;
      canvas.height = height;
      return canvas;
    }
    var Canvas = {
      create: create$3,
      clone: clone$2,
      resize: resize,
      get2dContext: get2dContext,
      get3dContext: get3dContext
    };

    function getWidth(image) {
      return image.naturalWidth || image.width;
    }
    function getHeight(image) {
      return image.naturalHeight || image.height;
    }
    var ImageSize = {
      getWidth: getWidth,
      getHeight: getHeight
    };

    var promise = function () {
      var Promise = function (fn) {
        if (typeof this !== 'object')
          throw new TypeError('Promises must be constructed via new');
        if (typeof fn !== 'function')
          throw new TypeError('not a function');
        this._state = null;
        this._value = null;
        this._deferreds = [];
        doResolve(fn, bind(resolve, this), bind(reject, this));
      };
      var asap = Promise.immediateFn || typeof window.setImmediate === 'function' && window.setImmediate || function (fn) {
        setTimeout(fn, 1);
      };
      function bind(fn, thisArg) {
        return function () {
          fn.apply(thisArg, arguments);
        };
      }
      var isArray = Array.isArray || function (value) {
        return Object.prototype.toString.call(value) === '[object Array]';
      };
      function handle(deferred) {
        var me = this;
        if (this._state === null) {
          this._deferreds.push(deferred);
          return;
        }
        asap(function () {
          var cb = me._state ? deferred.onFulfilled : deferred.onRejected;
          if (cb === null) {
            (me._state ? deferred.resolve : deferred.reject)(me._value);
            return;
          }
          var ret;
          try {
            ret = cb(me._value);
          } catch (e) {
            deferred.reject(e);
            return;
          }
          deferred.resolve(ret);
        });
      }
      function resolve(newValue) {
        try {
          if (newValue === this)
            throw new TypeError('A promise cannot be resolved with itself.');
          if (newValue && (typeof newValue === 'object' || typeof newValue === 'function')) {
            var then = newValue.then;
            if (typeof then === 'function') {
              doResolve(bind(then, newValue), bind(resolve, this), bind(reject, this));
              return;
            }
          }
          this._state = true;
          this._value = newValue;
          finale.call(this);
        } catch (e) {
          reject.call(this, e);
        }
      }
      function reject(newValue) {
        this._state = false;
        this._value = newValue;
        finale.call(this);
      }
      function finale() {
        for (var i = 0, len = this._deferreds.length; i < len; i++) {
          handle.call(this, this._deferreds[i]);
        }
        this._deferreds = null;
      }
      function Handler(onFulfilled, onRejected, resolve, reject) {
        this.onFulfilled = typeof onFulfilled === 'function' ? onFulfilled : null;
        this.onRejected = typeof onRejected === 'function' ? onRejected : null;
        this.resolve = resolve;
        this.reject = reject;
      }
      function doResolve(fn, onFulfilled, onRejected) {
        var done = false;
        try {
          fn(function (value) {
            if (done)
              return;
            done = true;
            onFulfilled(value);
          }, function (reason) {
            if (done)
              return;
            done = true;
            onRejected(reason);
          });
        } catch (ex) {
          if (done)
            return;
          done = true;
          onRejected(ex);
        }
      }
      Promise.prototype['catch'] = function (onRejected) {
        return this.then(null, onRejected);
      };
      Promise.prototype.then = function (onFulfilled, onRejected) {
        var me = this;
        return new Promise(function (resolve, reject) {
          handle.call(me, new Handler(onFulfilled, onRejected, resolve, reject));
        });
      };
      Promise.all = function () {
        var args = Array.prototype.slice.call(arguments.length === 1 && isArray(arguments[0]) ? arguments[0] : arguments);
        return new Promise(function (resolve, reject) {
          if (args.length === 0)
            return resolve([]);
          var remaining = args.length;
          function res(i, val) {
            try {
              if (val && (typeof val === 'object' || typeof val === 'function')) {
                var then = val.then;
                if (typeof then === 'function') {
                  then.call(val, function (val) {
                    res(i, val);
                  }, reject);
                  return;
                }
              }
              args[i] = val;
              if (--remaining === 0) {
                resolve(args);
              }
            } catch (ex) {
              reject(ex);
            }
          }
          for (var i = 0; i < args.length; i++) {
            res(i, args[i]);
          }
        });
      };
      Promise.resolve = function (value) {
        if (value && typeof value === 'object' && value.constructor === Promise) {
          return value;
        }
        return new Promise(function (resolve) {
          resolve(value);
        });
      };
      Promise.reject = function (value) {
        return new Promise(function (resolve, reject) {
          reject(value);
        });
      };
      Promise.race = function (values) {
        return new Promise(function (resolve, reject) {
          for (var i = 0, len = values.length; i < len; i++) {
            values[i].then(resolve, reject);
          }
        });
      };
      return Promise;
    };
    var Promise$1 = window.Promise ? window.Promise : promise();

    function Blob (parts, properties) {
      var f = Global$1.getOrDie('Blob');
      return new f(parts, properties);
    }

    function FileReader () {
      var f = Global$1.getOrDie('FileReader');
      return new f();
    }

    function Uint8Array (arr) {
      var f = Global$1.getOrDie('Uint8Array');
      return new f(arr);
    }

    var requestAnimationFrame = function (callback) {
      var f = Global$1.getOrDie('requestAnimationFrame');
      f(callback);
    };
    var atob = function (base64) {
      var f = Global$1.getOrDie('atob');
      return f(base64);
    };
    var Window = {
      atob: atob,
      requestAnimationFrame: requestAnimationFrame
    };

    function imageToBlob(image) {
      var src = image.src;
      if (src.indexOf('data:') === 0) {
        return dataUriToBlob(src);
      }
      return anyUriToBlob(src);
    }
    function blobToImage(blob) {
      return new Promise$1(function (resolve, reject) {
        var blobUrl = URL.createObjectURL(blob);
        var image = new Image();
        var removeListeners = function () {
          image.removeEventListener('load', loaded);
          image.removeEventListener('error', error);
        };
        function loaded() {
          removeListeners();
          resolve(image);
        }
        function error() {
          removeListeners();
          reject('Unable to load data of type ' + blob.type + ': ' + blobUrl);
        }
        image.addEventListener('load', loaded);
        image.addEventListener('error', error);
        image.src = blobUrl;
        if (image.complete) {
          loaded();
        }
      });
    }
    function anyUriToBlob(url) {
      return new Promise$1(function (resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.responseType = 'blob';
        xhr.onload = function () {
          if (this.status == 200) {
            resolve(this.response);
          }
        };
        xhr.onerror = function () {
          var _this = this;
          var corsError = function () {
            var obj = new Error('No access to download image');
            obj.code = 18;
            obj.name = 'SecurityError';
            return obj;
          };
          var genericError = function () {
            return new Error('Error ' + _this.status + ' downloading image');
          };
          reject(this.status === 0 ? corsError() : genericError());
        };
        xhr.send();
      });
    }
    function dataUriToBlobSync(uri) {
      var data = uri.split(',');
      var matches = /data:([^;]+)/.exec(data[0]);
      if (!matches)
        return Option.none();
      var mimetype = matches[1];
      var base64 = data[1];
      var sliceSize = 1024;
      var byteCharacters = Window.atob(base64);
      var bytesLength = byteCharacters.length;
      var slicesCount = Math.ceil(bytesLength / sliceSize);
      var byteArrays = new Array(slicesCount);
      for (var sliceIndex = 0; sliceIndex < slicesCount; ++sliceIndex) {
        var begin = sliceIndex * sliceSize;
        var end = Math.min(begin + sliceSize, bytesLength);
        var bytes = new Array(end - begin);
        for (var offset = begin, i = 0; offset < end; ++i, ++offset) {
          bytes[i] = byteCharacters[offset].charCodeAt(0);
        }
        byteArrays[sliceIndex] = Uint8Array(bytes);
      }
      return Option.some(Blob(byteArrays, { type: mimetype }));
    }
    function dataUriToBlob(uri) {
      return new Promise$1(function (resolve, reject) {
        dataUriToBlobSync(uri).fold(function () {
          reject('uri is not base64: ' + uri);
        }, resolve);
      });
    }
    function uriToBlob(url) {
      if (url.indexOf('blob:') === 0) {
        return anyUriToBlob(url);
      }
      if (url.indexOf('data:') === 0) {
        return dataUriToBlob(url);
      }
      return null;
    }
    function canvasToBlob(canvas, type, quality) {
      type = type || 'image/png';
      if (HTMLCanvasElement.prototype.toBlob) {
        return new Promise$1(function (resolve) {
          canvas.toBlob(function (blob) {
            resolve(blob);
          }, type, quality);
        });
      } else {
        return dataUriToBlob(canvas.toDataURL(type, quality));
      }
    }
    function canvasToDataURL(getCanvas, type, quality) {
      type = type || 'image/png';
      return getCanvas.then(function (canvas) {
        return canvas.toDataURL(type, quality);
      });
    }
    function blobToCanvas(blob) {
      return blobToImage(blob).then(function (image) {
        revokeImageUrl(image);
        var context, canvas;
        canvas = Canvas.create(ImageSize.getWidth(image), ImageSize.getHeight(image));
        context = Canvas.get2dContext(canvas);
        context.drawImage(image, 0, 0);
        return canvas;
      });
    }
    function blobToDataUri(blob) {
      return new Promise$1(function (resolve) {
        var reader = FileReader();
        reader.onloadend = function () {
          resolve(reader.result);
        };
        reader.readAsDataURL(blob);
      });
    }
    function blobToArrayBuffer(blob) {
      return new Promise$1(function (resolve) {
        var reader = FileReader();
        reader.onloadend = function () {
          resolve(reader.result);
        };
        reader.readAsArrayBuffer(blob);
      });
    }
    function blobToBase64(blob) {
      return blobToDataUri(blob).then(function (dataUri) {
        return dataUri.split(',')[1];
      });
    }
    function revokeImageUrl(image) {
      URL.revokeObjectURL(image.src);
    }
    var Conversions = {
      blobToImage: blobToImage,
      imageToBlob: imageToBlob,
      blobToArrayBuffer: blobToArrayBuffer,
      blobToDataUri: blobToDataUri,
      blobToBase64: blobToBase64,
      dataUriToBlobSync: dataUriToBlobSync,
      canvasToBlob: canvasToBlob,
      canvasToDataURL: canvasToDataURL,
      blobToCanvas: blobToCanvas,
      uriToBlob: uriToBlob
    };

    var blobToImage$1 = function (image) {
      return Conversions.blobToImage(image);
    };
    var imageToBlob$1 = function (blob) {
      return Conversions.imageToBlob(blob);
    };
    var blobToDataUri$1 = function (blob) {
      return Conversions.blobToDataUri(blob);
    };
    var blobToBase64$1 = function (blob) {
      return Conversions.blobToBase64(blob);
    };
    var dataUriToBlobSync$1 = function (uri) {
      return Conversions.dataUriToBlobSync(uri);
    };
    var uriToBlob$1 = function (uri) {
      return Option.from(Conversions.uriToBlob(uri));
    };
    var BlobConversions = {
      blobToImage: blobToImage$1,
      imageToBlob: imageToBlob$1,
      blobToDataUri: blobToDataUri$1,
      blobToBase64: blobToBase64$1,
      dataUriToBlobSync: dataUriToBlobSync$1,
      uriToBlob: uriToBlob$1
    };

    var addImage = function (editor, blob) {
      BlobConversions.blobToBase64(blob).then(function (base64) {
        editor.undoManager.transact(function () {
          var cache = editor.editorUpload.blobCache;
          var info = cache.create(generate$1('mceu'), blob, base64);
          cache.add(info);
          var img = editor.dom.createHTML('img', { src: info.blobUri() });
          editor.insertContent(img);
        });
      });
    };
    var extractBlob = function (simulatedEvent) {
      var event = simulatedEvent.event();
      var files = event.raw().target.files || event.raw().dataTransfer.files;
      return Option.from(files[0]);
    };
    var sketch$5 = function (editor) {
      var pickerDom = {
        tag: 'input',
        attributes: {
          accept: 'image/*',
          type: 'file',
          title: ''
        },
        styles: {
          visibility: 'hidden',
          position: 'absolute'
        }
      };
      var memPicker = record({
        dom: pickerDom,
        events: derive([
          cutter(click()),
          run(change(), function (picker, simulatedEvent) {
            extractBlob(simulatedEvent).each(function (blob) {
              addImage(editor, blob);
            });
          })
        ])
      });
      return Button.sketch({
        dom: Buttons.getToolbarIconButton('image', editor),
        components: [memPicker.asSpec()],
        action: function (button) {
          var picker = memPicker.get(button);
          picker.element().dom().click();
        }
      });
    };

    var get$9 = function (element) {
      return element.dom().textContent;
    };
    var set$6 = function (element, value) {
      element.dom().textContent = value;
    };

    var isNotEmpty = function (val) {
      return val.length > 0;
    };
    var defaultToEmpty = function (str) {
      return str === undefined || str === null ? '' : str;
    };
    var noLink = function (editor) {
      var text = editor.selection.getContent({ format: 'text' });
      return {
        url: '',
        text: text,
        title: '',
        target: '',
        link: Option.none()
      };
    };
    var fromLink = function (link) {
      var text = get$9(link);
      var url = get$1(link, 'href');
      var title = get$1(link, 'title');
      var target = get$1(link, 'target');
      return {
        url: defaultToEmpty(url),
        text: text !== url ? defaultToEmpty(text) : '',
        title: defaultToEmpty(title),
        target: defaultToEmpty(target),
        link: Option.some(link)
      };
    };
    var getInfo = function (editor) {
      return query(editor).fold(function () {
        return noLink(editor);
      }, function (link) {
        return fromLink(link);
      });
    };
    var wasSimple = function (link) {
      var prevHref = get$1(link, 'href');
      var prevText = get$9(link);
      return prevHref === prevText;
    };
    var getTextToApply = function (link, url, info) {
      return info.text.toOption().filter(isNotEmpty).fold(function () {
        return wasSimple(link) ? Option.some(url) : Option.none();
      }, Option.some);
    };
    var unlinkIfRequired = function (editor, info) {
      var activeLink = info.link.bind(identity);
      activeLink.each(function (link) {
        editor.execCommand('unlink');
      });
    };
    var getAttrs$1 = function (url, info) {
      var attrs = {};
      attrs.href = url;
      info.title.toOption().filter(isNotEmpty).each(function (title) {
        attrs.title = title;
      });
      info.target.toOption().filter(isNotEmpty).each(function (target) {
        attrs.target = target;
      });
      return attrs;
    };
    var applyInfo = function (editor, info) {
      info.url.toOption().filter(isNotEmpty).fold(function () {
        unlinkIfRequired(editor, info);
      }, function (url) {
        var attrs = getAttrs$1(url, info);
        var activeLink = info.link.bind(identity);
        activeLink.fold(function () {
          var text = info.text.toOption().filter(isNotEmpty).getOr(url);
          editor.insertContent(editor.dom.createHTML('a', attrs, editor.dom.encode(text)));
        }, function (link) {
          var text = getTextToApply(link, url, info);
          setAll(link, attrs);
          text.each(function (newText) {
            set$6(link, newText);
          });
        });
      });
    };
    var query = function (editor) {
      var start = Element$$1.fromDom(editor.selection.getStart());
      return closest$3(start, 'a');
    };
    var LinkBridge = {
      getInfo: getInfo,
      applyInfo: applyInfo,
      query: query
    };

    var platform$1 = PlatformDetection$1.detect();
    var preserve$1 = function (f, editor) {
      var rng = editor.selection.getRng();
      f();
      editor.selection.setRng(rng);
    };
    var forAndroid = function (editor, f) {
      var wrapper = platform$1.os.isAndroid() ? preserve$1 : apply;
      wrapper(f, editor);
    };
    var RangePreserver = { forAndroid: forAndroid };

    var events$6 = function (name, eventHandlers) {
      var events = derive(eventHandlers);
      return create$1({
        fields: [strict$1('enabled')],
        name: name,
        active: { events: constant(events) }
      });
    };
    var config = function (name, eventHandlers) {
      var me = events$6(name, eventHandlers);
      return {
        key: name,
        value: {
          config: {},
          me: me,
          configAsRaw: constant({}),
          initialConfig: {},
          state: NoState
        }
      };
    };

    var getCurrent = function (component, composeConfig, composeState) {
      return composeConfig.find(component);
    };

    var ComposeApis = /*#__PURE__*/Object.freeze({
        getCurrent: getCurrent
    });

    var ComposeSchema = [strict$1('find')];

    var Composing = create$1({
      fields: ComposeSchema,
      name: 'composing',
      apis: ComposeApis
    });

    var factory$1 = function (detail) {
      var _a = detail.dom, attributes = _a.attributes, domWithoutAttributes = __rest(_a, ['attributes']);
      return {
        uid: detail.uid,
        dom: __assign({
          tag: 'div',
          attributes: __assign({ role: 'presentation' }, attributes)
        }, domWithoutAttributes),
        components: detail.components,
        behaviours: get$6(detail.containerBehaviours),
        events: detail.events,
        domModification: detail.domModification,
        eventOrder: detail.eventOrder
      };
    };
    var Container = single$2({
      name: 'Container',
      factory: factory$1,
      configFields: [
        defaulted$1('components', []),
        field$1('containerBehaviours', []),
        defaulted$1('events', {}),
        defaulted$1('domModification', {}),
        defaulted$1('eventOrder', {})
      ]
    });

    var factory$2 = function (detail) {
      return {
        uid: detail.uid,
        dom: detail.dom,
        behaviours: SketchBehaviours.augment(detail.dataBehaviours, [
          Representing.config({
            store: {
              mode: 'memory',
              initialValue: detail.getInitialValue()
            }
          }),
          Composing.config({ find: Option.some })
        ]),
        events: derive([runOnAttached(function (component, simulatedEvent) {
            Representing.setValue(component, detail.getInitialValue());
          })])
      };
    };
    var DataField = single$2({
      name: 'DataField',
      factory: factory$2,
      configFields: [
        strict$1('uid'),
        strict$1('dom'),
        strict$1('getInitialValue'),
        SketchBehaviours.field('dataBehaviours', [
          Representing,
          Composing
        ])
      ]
    });

    var get$a = function (element) {
      return element.dom().value;
    };
    var set$7 = function (element, value) {
      if (value === undefined) {
        throw new Error('Value.set was undefined');
      }
      element.dom().value = value;
    };

    var schema$8 = constant([
      option('data'),
      defaulted$1('inputAttributes', {}),
      defaulted$1('inputStyles', {}),
      defaulted$1('tag', 'input'),
      defaulted$1('inputClasses', []),
      onHandler('onSetValue'),
      defaulted$1('styles', {}),
      defaulted$1('eventOrder', {}),
      field$1('inputBehaviours', [
        Representing,
        Focusing
      ]),
      defaulted$1('selectOnFocus', true)
    ]);
    var focusBehaviours = function (detail) {
      return derive$1([Focusing.config({
          onFocus: detail.selectOnFocus === false ? noop : function (component) {
            var input = component.element();
            var value = get$a(input);
            input.dom().setSelectionRange(0, value.length);
          }
        })]);
    };
    var behaviours = function (detail) {
      return __assign({}, focusBehaviours(detail), augment(detail.inputBehaviours, [Representing.config({
          store: {
            mode: 'manual',
            initialValue: detail.data.getOr(undefined),
            getValue: function (input) {
              return get$a(input.element());
            },
            setValue: function (input, data) {
              var current = get$a(input.element());
              if (current !== data) {
                set$7(input.element(), data);
              }
            }
          },
          onSetValue: detail.onSetValue
        })]));
    };
    var dom$3 = function (detail) {
      return {
        tag: detail.tag,
        attributes: __assign({ type: 'input' }, detail.inputAttributes),
        styles: detail.inputStyles,
        classes: detail.inputClasses
      };
    };

    var factory$3 = function (detail, spec) {
      return {
        uid: detail.uid,
        dom: dom$3(detail),
        components: [],
        behaviours: behaviours(detail),
        eventOrder: detail.eventOrder
      };
    };
    var Input = single$2({
      name: 'Input',
      configFields: schema$8(),
      factory: factory$3
    });

    var exhibit$3 = function (base, tabConfig) {
      return nu$5({
        attributes: wrapAll$1([{
            key: tabConfig.tabAttr,
            value: 'true'
          }])
      });
    };

    var ActiveTabstopping = /*#__PURE__*/Object.freeze({
        exhibit: exhibit$3
    });

    var TabstopSchema = [defaulted$1('tabAttr', 'data-alloy-tabstop')];

    var Tabstopping = create$1({
      fields: TabstopSchema,
      name: 'tabstopping',
      active: ActiveTabstopping
    });

    var clearInputBehaviour = 'input-clearing';
    var field$2 = function (name, placeholder) {
      var inputSpec = record(Input.sketch({
        inputAttributes: { placeholder: placeholder },
        onSetValue: function (input$$1, data) {
          emit(input$$1, input());
        },
        inputBehaviours: derive$1([
          Composing.config({ find: Option.some }),
          Tabstopping.config({}),
          Keying.config({ mode: 'execution' })
        ]),
        selectOnFocus: false
      }));
      var buttonSpec = record(Button.sketch({
        dom: dom$2('<button class="${prefix}-input-container-x ${prefix}-icon-cancel-circle ${prefix}-icon"></button>'),
        action: function (button) {
          var input$$1 = inputSpec.get(button);
          Representing.setValue(input$$1, '');
        }
      }));
      return {
        name: name,
        spec: Container.sketch({
          dom: dom$2('<div class="${prefix}-input-container"></div>'),
          components: [
            inputSpec.asSpec(),
            buttonSpec.asSpec()
          ],
          containerBehaviours: derive$1([
            Toggling.config({ toggleClass: Styles.resolve('input-container-empty') }),
            Composing.config({
              find: function (comp) {
                return Option.some(inputSpec.get(comp));
              }
            }),
            config(clearInputBehaviour, [run(input(), function (iContainer) {
                var input$$1 = inputSpec.get(iContainer);
                var val = Representing.getValue(input$$1);
                var f = val.length > 0 ? Toggling.off : Toggling.on;
                f(iContainer);
              })])
          ])
        })
      };
    };
    var hidden = function (name) {
      return {
        name: name,
        spec: DataField.sketch({
          dom: {
            tag: 'span',
            styles: { display: 'none' }
          },
          getInitialValue: function () {
            return Option.none();
          }
        })
      };
    };

    var nativeDisabled = [
      'input',
      'button',
      'textarea'
    ];
    var onLoad$5 = function (component, disableConfig, disableState) {
      if (disableConfig.disabled) {
        disable(component, disableConfig, disableState);
      }
    };
    var hasNative = function (component) {
      return contains(nativeDisabled, name(component.element()));
    };
    var nativeIsDisabled = function (component) {
      return has$1(component.element(), 'disabled');
    };
    var nativeDisable = function (component) {
      set(component.element(), 'disabled', 'disabled');
    };
    var nativeEnable = function (component) {
      remove$1(component.element(), 'disabled');
    };
    var ariaIsDisabled = function (component) {
      return get$1(component.element(), 'aria-disabled') === 'true';
    };
    var ariaDisable = function (component) {
      set(component.element(), 'aria-disabled', 'true');
    };
    var ariaEnable = function (component) {
      set(component.element(), 'aria-disabled', 'false');
    };
    var disable = function (component, disableConfig, disableState) {
      disableConfig.disableClass.each(function (disableClass) {
        add$2(component.element(), disableClass);
      });
      var f = hasNative(component) ? nativeDisable : ariaDisable;
      f(component);
    };
    var enable = function (component, disableConfig, disableState) {
      disableConfig.disableClass.each(function (disableClass) {
        remove$4(component.element(), disableClass);
      });
      var f = hasNative(component) ? nativeEnable : ariaEnable;
      f(component);
    };
    var isDisabled = function (component) {
      return hasNative(component) ? nativeIsDisabled(component) : ariaIsDisabled(component);
    };

    var DisableApis = /*#__PURE__*/Object.freeze({
        enable: enable,
        disable: disable,
        isDisabled: isDisabled,
        onLoad: onLoad$5
    });

    var exhibit$4 = function (base, disableConfig, disableState) {
      return nu$5({ classes: disableConfig.disabled ? disableConfig.disableClass.map(pure).getOr([]) : [] });
    };
    var events$7 = function (disableConfig, disableState) {
      return derive([
        abort(execute(), function (component, simulatedEvent) {
          return isDisabled(component);
        }),
        loadEvent(disableConfig, disableState, onLoad$5)
      ]);
    };

    var ActiveDisable = /*#__PURE__*/Object.freeze({
        exhibit: exhibit$4,
        events: events$7
    });

    var DisableSchema = [
      defaulted$1('disabled', false),
      option('disableClass')
    ];

    var Disabling = create$1({
      fields: DisableSchema,
      name: 'disabling',
      active: ActiveDisable,
      apis: DisableApis
    });

    var owner$1 = 'form';
    var schema$9 = [field$1('formBehaviours', [Representing])];
    var getPartName = function (name) {
      return '<alloy.field.' + name + '>';
    };
    var sketch$6 = function (fSpec) {
      var parts = function () {
        var record = [];
        var field = function (name, config) {
          record.push(name);
          return generateOne(owner$1, getPartName(name), config);
        };
        return {
          field: field,
          record: function () {
            return record;
          }
        };
      }();
      var spec = fSpec(parts);
      var partNames = parts.record();
      var fieldParts = map$1(partNames, function (n) {
        return required({
          name: n,
          pname: getPartName(n)
        });
      });
      return composite(owner$1, schema$9, fieldParts, make, spec);
    };
    var toResult$1 = function (o, e) {
      return o.fold(function () {
        return Result.error(e);
      }, Result.value);
    };
    var make = function (detail, components$$1, spec) {
      return {
        'uid': detail.uid,
        'dom': detail.dom,
        'components': components$$1,
        'behaviours': augment(detail.formBehaviours, [Representing.config({
            store: {
              mode: 'manual',
              getValue: function (form) {
                var resPs = getAllParts(form, detail);
                return map(resPs, function (resPThunk, pName) {
                  return resPThunk().bind(function (v) {
                    var opt = Composing.getCurrent(v);
                    return toResult$1(opt, 'missing current');
                  }).map(Representing.getValue);
                });
              },
              setValue: function (form, values$$1) {
                each(values$$1, function (newValue, key) {
                  getPart(form, detail, key).each(function (wrapper) {
                    Composing.getCurrent(wrapper).each(function (field) {
                      Representing.setValue(field, newValue);
                    });
                  });
                });
              }
            }
          })]),
        'apis': {
          getField: function (form, key) {
            return getPart(form, detail, key).bind(Composing.getCurrent);
          }
        }
      };
    };
    var Form = {
      getField: makeApi(function (apis, component, key) {
        return apis.getField(component, key);
      }),
      sketch: sketch$6
    };

    var api$2 = function () {
      var subject = Cell(Option.none());
      var revoke = function () {
        subject.get().each(function (s) {
          s.destroy();
        });
      };
      var clear = function () {
        revoke();
        subject.set(Option.none());
      };
      var set = function (s) {
        revoke();
        subject.set(Option.some(s));
      };
      var run = function (f) {
        subject.get().each(f);
      };
      var isSet = function () {
        return subject.get().isSome();
      };
      return {
        clear: clear,
        isSet: isSet,
        set: set,
        run: run
      };
    };
    var value$3 = function () {
      var subject = Cell(Option.none());
      var clear = function () {
        subject.set(Option.none());
      };
      var set = function (s) {
        subject.set(Option.some(s));
      };
      var on = function (f) {
        subject.get().each(f);
      };
      var isSet = function () {
        return subject.get().isSome();
      };
      return {
        clear: clear,
        set: set,
        isSet: isSet,
        on: on
      };
    };

    var SWIPING_LEFT = 1;
    var SWIPING_RIGHT = -1;
    var SWIPING_NONE = 0;
    var init$3 = function (xValue) {
      return {
        xValue: xValue,
        points: []
      };
    };
    var move$1 = function (model, xValue) {
      if (xValue === model.xValue) {
        return model;
      }
      var currentDirection = xValue - model.xValue > 0 ? SWIPING_LEFT : SWIPING_RIGHT;
      var newPoint = {
        direction: currentDirection,
        xValue: xValue
      };
      var priorPoints = function () {
        if (model.points.length === 0) {
          return [];
        } else {
          var prev = model.points[model.points.length - 1];
          return prev.direction === currentDirection ? model.points.slice(0, model.points.length - 1) : model.points;
        }
      }();
      return {
        xValue: xValue,
        points: priorPoints.concat([newPoint])
      };
    };
    var complete = function (model) {
      if (model.points.length === 0) {
        return SWIPING_NONE;
      } else {
        var firstDirection = model.points[0].direction;
        var lastDirection = model.points[model.points.length - 1].direction;
        return firstDirection === SWIPING_RIGHT && lastDirection === SWIPING_RIGHT ? SWIPING_RIGHT : firstDirection === SWIPING_LEFT && lastDirection === SWIPING_LEFT ? SWIPING_LEFT : SWIPING_NONE;
      }
    };
    var SwipingModel = {
      init: init$3,
      move: move$1,
      complete: complete
    };

    var sketch$7 = function (rawSpec) {
      var navigateEvent = 'navigateEvent';
      var wrapperAdhocEvents = 'serializer-wrapper-events';
      var formAdhocEvents = 'form-events';
      var schema = objOf([
        strict$1('fields'),
        defaulted$1('maxFieldIndex', rawSpec.fields.length - 1),
        strict$1('onExecute'),
        strict$1('getInitialValue'),
        state$1('state', function () {
          return {
            dialogSwipeState: value$3(),
            currentScreen: Cell(0)
          };
        })
      ]);
      var spec$$1 = asRawOrDie('SerialisedDialog', schema, rawSpec);
      var navigationButton = function (direction, directionName, enabled) {
        return Button.sketch({
          dom: dom$2('<span class="${prefix}-icon-' + directionName + ' ${prefix}-icon"></span>'),
          action: function (button) {
            emitWith(button, navigateEvent, { direction: direction });
          },
          buttonBehaviours: derive$1([Disabling.config({
              disableClass: Styles.resolve('toolbar-navigation-disabled'),
              disabled: !enabled
            })])
        });
      };
      var reposition = function (dialog, message) {
        descendant$2(dialog.element(), '.' + Styles.resolve('serialised-dialog-chain')).each(function (parent) {
          set$3(parent, 'left', -spec$$1.state.currentScreen.get() * message.width + 'px');
        });
      };
      var navigate = function (dialog, direction) {
        var screens = descendants$1(dialog.element(), '.' + Styles.resolve('serialised-dialog-screen'));
        descendant$2(dialog.element(), '.' + Styles.resolve('serialised-dialog-chain')).each(function (parent) {
          if (spec$$1.state.currentScreen.get() + direction >= 0 && spec$$1.state.currentScreen.get() + direction < screens.length) {
            getRaw(parent, 'left').each(function (left) {
              var currentLeft = parseInt(left, 10);
              var w = get$7(screens[0]);
              set$3(parent, 'left', currentLeft - direction * w + 'px');
            });
            spec$$1.state.currentScreen.set(spec$$1.state.currentScreen.get() + direction);
          }
        });
      };
      var focusInput = function (dialog) {
        var inputs = descendants$1(dialog.element(), 'input');
        var optInput = Option.from(inputs[spec$$1.state.currentScreen.get()]);
        optInput.each(function (input$$1) {
          dialog.getSystem().getByDom(input$$1).each(function (inputComp) {
            dispatchFocus(dialog, inputComp.element());
          });
        });
        var dotitems = memDots.get(dialog);
        Highlighting.highlightAt(dotitems, spec$$1.state.currentScreen.get());
      };
      var resetState = function () {
        spec$$1.state.currentScreen.set(0);
        spec$$1.state.dialogSwipeState.clear();
      };
      var memForm = record(Form.sketch(function (parts) {
        return {
          dom: dom$2('<div class="${prefix}-serialised-dialog"></div>'),
          components: [Container.sketch({
              dom: dom$2('<div class="${prefix}-serialised-dialog-chain" style="left: 0px; position: absolute;"></div>'),
              components: map$1(spec$$1.fields, function (field$$1, i) {
                return i <= spec$$1.maxFieldIndex ? Container.sketch({
                  dom: dom$2('<div class="${prefix}-serialised-dialog-screen"></div>'),
                  components: [
                    navigationButton(-1, 'previous', i > 0),
                    parts.field(field$$1.name, field$$1.spec),
                    navigationButton(+1, 'next', i < spec$$1.maxFieldIndex)
                  ]
                }) : parts.field(field$$1.name, field$$1.spec);
              })
            })],
          formBehaviours: derive$1([
            Receivers.orientation(function (dialog, message) {
              reposition(dialog, message);
            }),
            Keying.config({
              mode: 'special',
              focusIn: function (dialog) {
                focusInput(dialog);
              },
              onTab: function (dialog) {
                navigate(dialog, +1);
                return Option.some(true);
              },
              onShiftTab: function (dialog) {
                navigate(dialog, -1);
                return Option.some(true);
              }
            }),
            config(formAdhocEvents, [
              runOnAttached(function (dialog, simulatedEvent) {
                resetState();
                var dotitems = memDots.get(dialog);
                Highlighting.highlightFirst(dotitems);
                spec$$1.getInitialValue(dialog).each(function (v) {
                  Representing.setValue(dialog, v);
                });
              }),
              runOnExecute(spec$$1.onExecute),
              run(transitionend(), function (dialog, simulatedEvent) {
                var event = simulatedEvent.event();
                if (event.raw().propertyName === 'left') {
                  focusInput(dialog);
                }
              }),
              run(navigateEvent, function (dialog, simulatedEvent) {
                var event = simulatedEvent.event();
                var direction = event.direction();
                navigate(dialog, direction);
              })
            ])
          ])
        };
      }));
      var memDots = record({
        dom: dom$2('<div class="${prefix}-dot-container"></div>'),
        behaviours: derive$1([Highlighting.config({
            highlightClass: Styles.resolve('dot-active'),
            itemClass: Styles.resolve('dot-item')
          })]),
        components: bind(spec$$1.fields, function (_f, i) {
          return i <= spec$$1.maxFieldIndex ? [spec('<div class="${prefix}-dot-item ${prefix}-icon-full-dot ${prefix}-icon"></div>')] : [];
        })
      });
      return {
        dom: dom$2('<div class="${prefix}-serializer-wrapper"></div>'),
        components: [
          memForm.asSpec(),
          memDots.asSpec()
        ],
        behaviours: derive$1([
          Keying.config({
            mode: 'special',
            focusIn: function (wrapper) {
              var form = memForm.get(wrapper);
              Keying.focusIn(form);
            }
          }),
          config(wrapperAdhocEvents, [
            run(touchstart(), function (wrapper, simulatedEvent) {
              var event = simulatedEvent.event();
              spec$$1.state.dialogSwipeState.set(SwipingModel.init(event.raw().touches[0].clientX));
            }),
            run(touchmove(), function (wrapper, simulatedEvent) {
              var event = simulatedEvent.event();
              spec$$1.state.dialogSwipeState.on(function (state) {
                simulatedEvent.event().prevent();
                spec$$1.state.dialogSwipeState.set(SwipingModel.move(state, event.raw().touches[0].clientX));
              });
            }),
            run(touchend(), function (wrapper) {
              spec$$1.state.dialogSwipeState.on(function (state) {
                var dialog = memForm.get(wrapper);
                var direction = -1 * SwipingModel.complete(state);
                navigate(dialog, direction);
              });
            })
          ])
        ])
      };
    };

    var getGroups = cached(function (realm, editor) {
      return [{
          label: 'the link group',
          items: [sketch$7({
              fields: [
                field$2('url', 'Type or paste URL'),
                field$2('text', 'Link text'),
                field$2('title', 'Link title'),
                field$2('target', 'Link target'),
                hidden('link')
              ],
              maxFieldIndex: [
                'url',
                'text',
                'title',
                'target'
              ].length - 1,
              getInitialValue: function () {
                return Option.some(LinkBridge.getInfo(editor));
              },
              onExecute: function (dialog) {
                var info = Representing.getValue(dialog);
                LinkBridge.applyInfo(editor, info);
                realm.restoreToolbar();
                editor.focus();
              }
            })]
        }];
    });
    var sketch$8 = function (realm, editor) {
      return Buttons.forToolbarStateAction(editor, 'link', 'link', function () {
        var groups = getGroups(realm, editor);
        realm.setContextToolbar(groups);
        RangePreserver.forAndroid(editor, function () {
          realm.focusToolbar();
        });
        LinkBridge.query(editor).each(function (link) {
          editor.selection.select(link.dom());
        });
      });
    };

    var DefaultStyleFormats = [
      {
        title: 'Headings',
        items: [
          {
            title: 'Heading 1',
            format: 'h1'
          },
          {
            title: 'Heading 2',
            format: 'h2'
          },
          {
            title: 'Heading 3',
            format: 'h3'
          },
          {
            title: 'Heading 4',
            format: 'h4'
          },
          {
            title: 'Heading 5',
            format: 'h5'
          },
          {
            title: 'Heading 6',
            format: 'h6'
          }
        ]
      },
      {
        title: 'Inline',
        items: [
          {
            title: 'Bold',
            icon: 'bold',
            format: 'bold'
          },
          {
            title: 'Italic',
            icon: 'italic',
            format: 'italic'
          },
          {
            title: 'Underline',
            icon: 'underline',
            format: 'underline'
          },
          {
            title: 'Strikethrough',
            icon: 'strikethrough',
            format: 'strikethrough'
          },
          {
            title: 'Superscript',
            icon: 'superscript',
            format: 'superscript'
          },
          {
            title: 'Subscript',
            icon: 'subscript',
            format: 'subscript'
          },
          {
            title: 'Code',
            icon: 'code',
            format: 'code'
          }
        ]
      },
      {
        title: 'Blocks',
        items: [
          {
            title: 'Paragraph',
            format: 'p'
          },
          {
            title: 'Blockquote',
            format: 'blockquote'
          },
          {
            title: 'Div',
            format: 'div'
          },
          {
            title: 'Pre',
            format: 'pre'
          }
        ]
      },
      {
        title: 'Alignment',
        items: [
          {
            title: 'Left',
            icon: 'alignleft',
            format: 'alignleft'
          },
          {
            title: 'Center',
            icon: 'aligncenter',
            format: 'aligncenter'
          },
          {
            title: 'Right',
            icon: 'alignright',
            format: 'alignright'
          },
          {
            title: 'Justify',
            icon: 'alignjustify',
            format: 'alignjustify'
          }
        ]
      }
    ];

    var isRecursive = function (component, originator, target) {
      return eq(originator, component.element()) && !eq(originator, target);
    };
    var events$8 = derive([can(focus$1(), function (component, simulatedEvent) {
        var originator = simulatedEvent.event().originator();
        var target = simulatedEvent.event().target();
        if (isRecursive(component, originator, target)) {
          console.warn(focus$1() + ' did not get interpreted by the desired target. ' + '\nOriginator: ' + element(originator) + '\nTarget: ' + element(target) + '\nCheck the ' + focus$1() + ' event handlers');
          return false;
        } else {
          return true;
        }
      })]);

    var DefaultEvents = /*#__PURE__*/Object.freeze({
        events: events$8
    });

    var make$1 = identity;

    var NoContextApi = function (getComp) {
      var fail = function (event) {
        return function () {
          throw new Error('The component must be in a context to send: ' + event + '\n' + element(getComp().element()) + ' is not in context.');
        };
      };
      return {
        debugInfo: constant('fake'),
        triggerEvent: fail('triggerEvent'),
        triggerFocus: fail('triggerFocus'),
        triggerEscape: fail('triggerEscape'),
        build: fail('build'),
        addToWorld: fail('addToWorld'),
        removeFromWorld: fail('removeFromWorld'),
        addToGui: fail('addToGui'),
        removeFromGui: fail('removeFromGui'),
        getByUid: fail('getByUid'),
        getByDom: fail('getByDom'),
        broadcast: fail('broadcast'),
        broadcastOn: fail('broadcastOn'),
        broadcastEvent: fail('broadcastEvent'),
        isConnected: constant(false)
      };
    };
    var singleton = NoContextApi();

    var generateFrom = function (spec, all) {
      var schema = map$1(all, function (a) {
        return optionObjOf(a.name(), [
          strict$1('config'),
          defaulted$1('state', NoState)
        ]);
      });
      var validated = asRaw('component.behaviours', objOf(schema), spec.behaviours).fold(function (errInfo) {
        throw new Error(formatError(errInfo) + '\nComplete spec:\n' + Json.stringify(spec, null, 2));
      }, function (v) {
        return v;
      });
      return {
        list: all,
        data: map(validated, function (optBlobThunk) {
          var optBlob = optBlobThunk;
          var output = optBlob.map(function (blob) {
            return {
              config: blob.config,
              state: blob.state.init(blob.config)
            };
          });
          return function () {
            return output;
          };
        })
      };
    };
    var getBehaviours = function (bData) {
      return bData.list;
    };
    var getData = function (bData) {
      return bData.data;
    };

    var byInnerKey = function (data, tuple) {
      var r = {};
      each(data, function (detail, key) {
        each(detail, function (value, indexKey) {
          var chain = readOr$1(indexKey, [])(r);
          r[indexKey] = chain.concat([tuple(key, value)]);
        });
      });
      return r;
    };

    var combine$1 = function (info, baseMod, behaviours, base) {
      var modsByBehaviour = __assign({}, baseMod);
      each$1(behaviours, function (behaviour) {
        modsByBehaviour[behaviour.name()] = behaviour.exhibit(info, base);
      });
      var nameAndMod = function (name, modification) {
        return {
          name: name,
          modification: modification
        };
      };
      var byAspect = byInnerKey(modsByBehaviour, nameAndMod);
      var combineObjects = function (objects) {
        return foldr(objects, function (b, a) {
          return __assign({}, a.modification, b);
        }, {});
      };
      var combinedClasses = foldr(byAspect.classes, function (b, a) {
        return a.modification.concat(b);
      }, []);
      var combinedAttributes = combineObjects(byAspect.attributes);
      var combinedStyles = combineObjects(byAspect.styles);
      return nu$5({
        classes: combinedClasses,
        attributes: combinedAttributes,
        styles: combinedStyles
      });
    };

    var sortKeys = function (label, keyName, array, order) {
      var sliced = array.slice(0);
      try {
        var sorted = sliced.sort(function (a, b) {
          var aKey = a[keyName]();
          var bKey = b[keyName]();
          var aIndex = order.indexOf(aKey);
          var bIndex = order.indexOf(bKey);
          if (aIndex === -1) {
            throw new Error('The ordering for ' + label + ' does not have an entry for ' + aKey + '.\nOrder specified: ' + Json.stringify(order, null, 2));
          }
          if (bIndex === -1) {
            throw new Error('The ordering for ' + label + ' does not have an entry for ' + bKey + '.\nOrder specified: ' + Json.stringify(order, null, 2));
          }
          if (aIndex < bIndex) {
            return -1;
          } else if (bIndex < aIndex) {
            return 1;
          } else {
            return 0;
          }
        });
        return Result.value(sorted);
      } catch (err) {
        return Result.error([err]);
      }
    };

    var uncurried = function (handler, purpose) {
      return {
        handler: handler,
        purpose: constant(purpose)
      };
    };
    var curried = function (handler, purpose) {
      return {
        cHandler: handler,
        purpose: constant(purpose)
      };
    };
    var curryArgs = function (descHandler, extraArgs) {
      return curried(curry.apply(undefined, [descHandler.handler].concat(extraArgs)), descHandler.purpose());
    };
    var getCurried = function (descHandler) {
      return descHandler.cHandler;
    };

    var behaviourTuple = function (name, handler) {
      return {
        name: constant(name),
        handler: constant(handler)
      };
    };
    var nameToHandlers = function (behaviours, info) {
      var r = {};
      each$1(behaviours, function (behaviour) {
        r[behaviour.name()] = behaviour.handlers(info);
      });
      return r;
    };
    var groupByEvents = function (info, behaviours, base) {
      var behaviourEvents = __assign({}, base, nameToHandlers(behaviours, info));
      return byInnerKey(behaviourEvents, behaviourTuple);
    };
    var combine$2 = function (info, eventOrder, behaviours, base) {
      var byEventName = groupByEvents(info, behaviours, base);
      return combineGroups(byEventName, eventOrder);
    };
    var assemble = function (rawHandler) {
      var handler = read(rawHandler);
      return function (component, simulatedEvent) {
        var rest = [];
        for (var _i = 2; _i < arguments.length; _i++) {
          rest[_i - 2] = arguments[_i];
        }
        var args = [
          component,
          simulatedEvent
        ].concat(rest);
        if (handler.abort.apply(undefined, args)) {
          simulatedEvent.stop();
        } else if (handler.can.apply(undefined, args)) {
          handler.run.apply(undefined, args);
        }
      };
    };
    var missingOrderError = function (eventName, tuples) {
      return Result.error(['The event (' + eventName + ') has more than one behaviour that listens to it.\nWhen this occurs, you must ' + 'specify an event ordering for the behaviours in your spec (e.g. [ "listing", "toggling" ]).\nThe behaviours that ' + 'can trigger it are: ' + Json.stringify(map$1(tuples, function (c) {
          return c.name();
        }), null, 2)]);
    };
    var fuse$1 = function (tuples, eventOrder, eventName) {
      var order = eventOrder[eventName];
      if (!order) {
        return missingOrderError(eventName, tuples);
      } else {
        return sortKeys('Event: ' + eventName, 'name', tuples, order).map(function (sortedTuples) {
          var handlers = map$1(sortedTuples, function (tuple) {
            return tuple.handler();
          });
          return fuse(handlers);
        });
      }
    };
    var combineGroups = function (byEventName, eventOrder) {
      var r = mapToArray(byEventName, function (tuples, eventName) {
        var combined = tuples.length === 1 ? Result.value(tuples[0].handler()) : fuse$1(tuples, eventOrder, eventName);
        return combined.map(function (handler) {
          var assembled = assemble(handler);
          var purpose = tuples.length > 1 ? filter(eventOrder, function (o) {
            return contains(tuples, function (t) {
              return t.name() === o;
            });
          }).join(' > ') : tuples[0].name();
          return wrap$2(eventName, uncurried(assembled, purpose));
        });
      });
      return consolidate(r, {});
    };

    var toInfo = function (spec) {
      return asRaw('custom.definition', objOf([
        field('dom', 'dom', strict(), objOf([
          strict$1('tag'),
          defaulted$1('styles', {}),
          defaulted$1('classes', []),
          defaulted$1('attributes', {}),
          option('value'),
          option('innerHtml')
        ])),
        strict$1('components'),
        strict$1('uid'),
        defaulted$1('events', {}),
        defaulted$1('apis', {}),
        field('eventOrder', 'eventOrder', mergeWith({
          'alloy.execute': [
            'disabling',
            'alloy.base.behaviour',
            'toggling',
            'typeaheadevents'
          ],
          'alloy.focus': [
            'alloy.base.behaviour',
            'focusing',
            'keying'
          ],
          'alloy.system.init': [
            'alloy.base.behaviour',
            'disabling',
            'toggling',
            'representing'
          ],
          'input': [
            'alloy.base.behaviour',
            'representing',
            'streaming',
            'invalidating'
          ],
          'alloy.system.detached': [
            'alloy.base.behaviour',
            'representing',
            'item-events',
            'tooltipping'
          ],
          'mousedown': [
            'focusing',
            'alloy.base.behaviour',
            'item-type-events'
          ],
          'mouseover': [
            'item-type-events',
            'tooltipping'
          ]
        }), anyValue$1()),
        option('domModification')
      ]), spec);
    };
    var toDefinition = function (detail) {
      return __assign({}, detail.dom, {
        uid: detail.uid,
        domChildren: map$1(detail.components, function (comp) {
          return comp.element();
        })
      });
    };
    var toModification = function (detail) {
      return detail.domModification.fold(function () {
        return nu$5({});
      }, nu$5);
    };
    var toEvents = function (info) {
      return info.events;
    };

    var add$3 = function (element, classes) {
      each$1(classes, function (x) {
        add$2(element, x);
      });
    };
    var remove$6 = function (element, classes) {
      each$1(classes, function (x) {
        remove$4(element, x);
      });
    };

    var renderToDom = function (definition) {
      var subject = Element$$1.fromTag(definition.tag);
      setAll(subject, definition.attributes);
      add$3(subject, definition.classes);
      setAll$1(subject, definition.styles);
      definition.innerHtml.each(function (html) {
        return set$1(subject, html);
      });
      var children = definition.domChildren;
      append$1(subject, children);
      definition.value.each(function (value) {
        set$7(subject, value);
      });
      if (!definition.uid) {
        debugger;
      }
      writeOnly(subject, definition.uid);
      return subject;
    };

    var getBehaviours$1 = function (spec) {
      var behaviours = readOr$1('behaviours', {})(spec);
      var keys$$1 = filter(keys(behaviours), function (k) {
        return behaviours[k] !== undefined;
      });
      return map$1(keys$$1, function (k) {
        return behaviours[k].me;
      });
    };
    var generateFrom$1 = function (spec, all) {
      return generateFrom(spec, all);
    };
    var generate$4 = function (spec) {
      var all = getBehaviours$1(spec);
      return generateFrom$1(spec, all);
    };

    var getDomDefinition = function (info, bList, bData) {
      var definition = toDefinition(info);
      var infoModification = toModification(info);
      var baseModification = { 'alloy.base.modification': infoModification };
      var modification = bList.length > 0 ? combine$1(bData, baseModification, bList, definition) : infoModification;
      return merge$1(definition, modification);
    };
    var getEvents = function (info, bList, bData) {
      var baseEvents = { 'alloy.base.behaviour': toEvents(info) };
      return combine$2(bData, info.eventOrder, bList, baseEvents).getOrDie();
    };
    var build = function (spec) {
      var getMe = function () {
        return me;
      };
      var systemApi = Cell(singleton);
      var info = getOrDie$1(toInfo(spec));
      var bBlob = generate$4(spec);
      var bList = getBehaviours(bBlob);
      var bData = getData(bBlob);
      var modDefinition = getDomDefinition(info, bList, bData);
      var item = renderToDom(modDefinition);
      var events = getEvents(info, bList, bData);
      var subcomponents = Cell(info.components);
      var connect = function (newApi) {
        systemApi.set(newApi);
      };
      var disconnect = function () {
        systemApi.set(NoContextApi(getMe));
      };
      var syncComponents = function () {
        var children$$1 = children(item);
        var subs = bind(children$$1, function (child$$1) {
          return systemApi.get().getByDom(child$$1).fold(function () {
            return [];
          }, function (c) {
            return [c];
          });
        });
        subcomponents.set(subs);
      };
      var config = function (behaviour) {
        var b = bData;
        var f = isFunction(b[behaviour.name()]) ? b[behaviour.name()] : function () {
          throw new Error('Could not find ' + behaviour.name() + ' in ' + Json.stringify(spec, null, 2));
        };
        return f();
      };
      var hasConfigured = function (behaviour) {
        return isFunction(bData[behaviour.name()]);
      };
      var getApis = function () {
        return info.apis;
      };
      var readState = function (behaviourName) {
        return bData[behaviourName]().map(function (b) {
          return b.state.readState();
        }).getOr('not enabled');
      };
      var me = {
        getSystem: systemApi.get,
        config: config,
        hasConfigured: hasConfigured,
        spec: constant(spec),
        readState: readState,
        getApis: getApis,
        connect: connect,
        disconnect: disconnect,
        element: constant(item),
        syncComponents: syncComponents,
        components: subcomponents.get,
        events: constant(events)
      };
      return me;
    };

    var buildSubcomponents = function (spec) {
      var components = readOr$1('components', [])(spec);
      return map$1(components, build$1);
    };
    var buildFromSpec = function (userSpec) {
      var _a = make$1(userSpec), specEvents = _a.events, spec = __rest(_a, ['events']);
      var components = buildSubcomponents(spec);
      var completeSpec = __assign({}, spec, {
        events: __assign({}, DefaultEvents, specEvents),
        components: components
      });
      return Result.value(build(completeSpec));
    };
    var text = function (textContent) {
      var element = Element$$1.fromText(textContent);
      return external$1({ element: element });
    };
    var external$1 = function (spec) {
      var extSpec = asRawOrDie('external.component', objOfOnly([
        strict$1('element'),
        option('uid')
      ]), spec);
      var systemApi = Cell(NoContextApi());
      var connect = function (newApi) {
        systemApi.set(newApi);
      };
      var disconnect = function () {
        systemApi.set(NoContextApi(function () {
          return me;
        }));
      };
      extSpec.uid.each(function (uid) {
        writeOnly(extSpec.element, uid);
      });
      var me = {
        getSystem: systemApi.get,
        config: Option.none,
        hasConfigured: constant(false),
        connect: connect,
        disconnect: disconnect,
        getApis: function () {
          return {};
        },
        element: constant(extSpec.element),
        spec: constant(spec),
        readState: constant('No state'),
        syncComponents: noop,
        components: constant([]),
        events: constant({})
      };
      return premade(me);
    };
    var uids = generate$3;
    var build$1 = function (spec) {
      return getPremade(spec).fold(function () {
        var userSpecWithUid = spec.hasOwnProperty('uid') ? spec : __assign({ uid: uids('') }, spec);
        return buildFromSpec(userSpecWithUid).getOrDie();
      }, function (prebuilt) {
        return prebuilt;
      });
    };
    var premade$1 = premade;

    var hoverEvent = 'alloy.item-hover';
    var focusEvent = 'alloy.item-focus';
    var onHover = function (item) {
      if (search(item.element()).isNone() || Focusing.isFocused(item)) {
        if (!Focusing.isFocused(item)) {
          Focusing.focus(item);
        }
        emitWith(item, hoverEvent, { item: item });
      }
    };
    var onFocus = function (item) {
      emitWith(item, focusEvent, { item: item });
    };
    var hover = constant(hoverEvent);
    var focus$4 = constant(focusEvent);

    var builder = function (detail) {
      return {
        dom: detail.dom,
        domModification: __assign({}, detail.domModification, { attributes: __assign({ 'role': detail.toggling.isSome() ? 'menuitemcheckbox' : 'menuitem' }, detail.domModification.attributes, { 'aria-haspopup': detail.hasSubmenu }, detail.hasSubmenu ? { 'aria-expanded': false } : {}) }),
        behaviours: SketchBehaviours.augment(detail.itemBehaviours, [
          detail.toggling.fold(Toggling.revoke, function (tConfig) {
            return Toggling.config(__assign({ aria: { mode: 'checked' } }, tConfig));
          }),
          Focusing.config({
            ignore: detail.ignoreFocus,
            stopMousedown: detail.ignoreFocus,
            onFocus: function (component) {
              onFocus(component);
            }
          }),
          Keying.config({ mode: 'execution' }),
          Representing.config({
            store: {
              mode: 'memory',
              initialValue: detail.data
            }
          }),
          config('item-type-events', [
            run(tapOrClick(), emitExecute),
            cutter(mousedown()),
            run(mouseover(), onHover),
            run(focusItem(), Focusing.focus)
          ])
        ]),
        components: detail.components,
        eventOrder: detail.eventOrder
      };
    };
    var schema$a = [
      strict$1('data'),
      strict$1('components'),
      strict$1('dom'),
      defaulted$1('hasSubmenu', false),
      option('toggling'),
      SketchBehaviours.field('itemBehaviours', [
        Toggling,
        Focusing,
        Keying,
        Representing
      ]),
      defaulted$1('ignoreFocus', false),
      defaulted$1('domModification', {}),
      output$1('builder', builder),
      defaulted$1('eventOrder', {})
    ];

    var builder$1 = function (detail) {
      return {
        dom: detail.dom,
        components: detail.components,
        events: derive([stopper(focusItem())])
      };
    };
    var schema$b = [
      strict$1('dom'),
      strict$1('components'),
      output$1('builder', builder$1)
    ];

    var owner$2 = function () {
      return 'item-widget';
    };
    var parts = constant([required({
        name: 'widget',
        overrides: function (detail) {
          return {
            behaviours: derive$1([Representing.config({
                store: {
                  mode: 'manual',
                  getValue: function (component) {
                    return detail.data;
                  },
                  setValue: function () {
                  }
                }
              })])
          };
        }
      })]);

    var builder$2 = function (detail) {
      var subs = substitutes(owner$2(), detail, parts());
      var components$$1 = components(owner$2(), detail, subs.internals());
      var focusWidget = function (component) {
        return getPart(component, detail, 'widget').map(function (widget) {
          Keying.focusIn(widget);
          return widget;
        });
      };
      var onHorizontalArrow = function (component, simulatedEvent) {
        return inside(simulatedEvent.event().target()) ? Option.none() : function () {
          if (detail.autofocus) {
            simulatedEvent.setSource(component.element());
            return Option.none();
          } else {
            return Option.none();
          }
        }();
      };
      return {
        dom: detail.dom,
        components: components$$1,
        domModification: detail.domModification,
        events: derive([
          runOnExecute(function (component, simulatedEvent) {
            focusWidget(component).each(function (widget) {
              simulatedEvent.stop();
            });
          }),
          run(mouseover(), onHover),
          run(focusItem(), function (component, simulatedEvent) {
            if (detail.autofocus) {
              focusWidget(component);
            } else {
              Focusing.focus(component);
            }
          })
        ]),
        behaviours: SketchBehaviours.augment(detail.widgetBehaviours, [
          Representing.config({
            store: {
              mode: 'memory',
              initialValue: detail.data
            }
          }),
          Focusing.config({
            ignore: detail.ignoreFocus,
            onFocus: function (component) {
              onFocus(component);
            }
          }),
          Keying.config({
            mode: 'special',
            focusIn: detail.autofocus ? function (component) {
              focusWidget(component);
            } : revoke(),
            onLeft: onHorizontalArrow,
            onRight: onHorizontalArrow,
            onEscape: function (component, simulatedEvent) {
              if (!Focusing.isFocused(component) && !detail.autofocus) {
                Focusing.focus(component);
                return Option.some(true);
              } else if (detail.autofocus) {
                simulatedEvent.setSource(component.element());
                return Option.none();
              } else {
                return Option.none();
              }
            }
          })
        ])
      };
    };
    var schema$c = [
      strict$1('uid'),
      strict$1('data'),
      strict$1('components'),
      strict$1('dom'),
      defaulted$1('autofocus', false),
      defaulted$1('ignoreFocus', false),
      SketchBehaviours.field('widgetBehaviours', [
        Representing,
        Focusing,
        Keying
      ]),
      defaulted$1('domModification', {}),
      defaultUidsSchema(parts()),
      output$1('builder', builder$2)
    ];

    var itemSchema$1 = choose$1('type', {
      widget: schema$c,
      item: schema$a,
      separator: schema$b
    });
    var configureGrid = function (detail, movementInfo) {
      return {
        mode: 'flatgrid',
        selector: '.' + detail.markers.item,
        initSize: {
          numColumns: movementInfo.initSize.numColumns,
          numRows: movementInfo.initSize.numRows
        },
        focusManager: detail.focusManager
      };
    };
    var configureMatrix = function (detail, movementInfo) {
      return {
        mode: 'matrix',
        selectors: {
          row: movementInfo.rowSelector,
          cell: '.' + detail.markers.item
        },
        focusManager: detail.focusManager
      };
    };
    var configureMenu = function (detail, movementInfo) {
      return {
        mode: 'menu',
        selector: '.' + detail.markers.item,
        moveOnTab: movementInfo.moveOnTab,
        focusManager: detail.focusManager
      };
    };
    var parts$1 = constant([group({
        factory: {
          sketch: function (spec) {
            var itemInfo = asRawOrDie('menu.spec item', itemSchema$1, spec);
            return itemInfo.builder(itemInfo);
          }
        },
        name: 'items',
        unit: 'item',
        defaults: function (detail, u) {
          return u.hasOwnProperty('uid') ? u : __assign({}, u, { uid: generate$3('item') });
        },
        overrides: function (detail, u) {
          return {
            type: u.type,
            ignoreFocus: detail.fakeFocus,
            domModification: { classes: [detail.markers.item] }
          };
        }
      })]);
    var schema$d = constant([
      strict$1('value'),
      strict$1('items'),
      strict$1('dom'),
      strict$1('components'),
      defaulted$1('eventOrder', {}),
      field$1('menuBehaviours', [
        Highlighting,
        Representing,
        Composing,
        Keying
      ]),
      defaultedOf('movement', {
        mode: 'menu',
        moveOnTab: true
      }, choose$1('mode', {
        grid: [
          initSize(),
          output$1('config', configureGrid)
        ],
        matrix: [
          output$1('config', configureMatrix),
          strict$1('rowSelector')
        ],
        menu: [
          defaulted$1('moveOnTab', true),
          output$1('config', configureMenu)
        ]
      })),
      itemMarkers(),
      defaulted$1('fakeFocus', false),
      defaulted$1('focusManager', dom()),
      onHandler('onHighlight')
    ]);

    var focus$5 = constant('alloy.menu-focus');

    var make$2 = function (detail, components, spec, externals) {
      return {
        uid: detail.uid,
        dom: detail.dom,
        markers: detail.markers,
        behaviours: augment(detail.menuBehaviours, [
          Highlighting.config({
            highlightClass: detail.markers.selectedItem,
            itemClass: detail.markers.item,
            onHighlight: detail.onHighlight
          }),
          Representing.config({
            store: {
              mode: 'memory',
              initialValue: detail.value
            }
          }),
          Composing.config({ find: Option.some }),
          Keying.config(detail.movement.config(detail, detail.movement))
        ]),
        events: derive([
          run(focus$4(), function (menu, simulatedEvent) {
            var event = simulatedEvent.event();
            menu.getSystem().getByDom(event.target()).each(function (item) {
              Highlighting.highlight(menu, item);
              simulatedEvent.stop();
              emitWith(menu, focus$5(), {
                menu: menu,
                item: item
              });
            });
          }),
          run(hover(), function (menu, simulatedEvent) {
            var item = simulatedEvent.event().item();
            Highlighting.highlight(menu, item);
          })
        ]),
        components: components,
        eventOrder: detail.eventOrder,
        domModification: { attributes: { role: 'menu' } }
      };
    };

    var Menu = composite$1({
      name: 'Menu',
      configFields: schema$d(),
      partFields: parts$1(),
      factory: make$2
    });

    var preserve$2 = function (f, container) {
      var ownerDoc = owner(container);
      var refocus = active(ownerDoc).bind(function (focused) {
        var hasFocus$$1 = function (elem) {
          return eq(focused, elem);
        };
        return hasFocus$$1(container) ? Option.some(container) : descendant(container, hasFocus$$1);
      });
      var result = f(container);
      refocus.each(function (oldFocus) {
        active(ownerDoc).filter(function (newFocus) {
          return eq(newFocus, oldFocus);
        }).fold(function () {
          focus$2(oldFocus);
        }, noop);
      });
      return result;
    };

    var set$8 = function (component, replaceConfig, replaceState, data) {
      detachChildren(component);
      preserve$2(function () {
        var children = map$1(data, component.getSystem().build);
        each$1(children, function (l) {
          attach(component, l);
        });
      }, component.element());
    };
    var insert = function (component, replaceConfig, insertion, childSpec) {
      var child = component.getSystem().build(childSpec);
      attachWith(component, child, insertion);
    };
    var append$2 = function (component, replaceConfig, replaceState, appendee) {
      insert(component, replaceConfig, append, appendee);
    };
    var prepend$2 = function (component, replaceConfig, replaceState, prependee) {
      insert(component, replaceConfig, prepend, prependee);
    };
    var remove$7 = function (component, replaceConfig, replaceState, removee) {
      var children = contents(component, replaceConfig);
      var foundChild = find$2(children, function (child) {
        return eq(removee.element(), child.element());
      });
      foundChild.each(detach);
    };
    var contents = function (component, replaceConfig) {
      return component.components();
    };
    var replaceAt = function (component, replaceConfig, replaceState, replaceeIndex, replacer) {
      var children = contents(component, replaceConfig);
      return Option.from(children[replaceeIndex]).map(function (replacee) {
        remove$7(component, replaceConfig, replaceState, replacee);
        replacer.each(function (r) {
          insert(component, replaceConfig, function (p, c) {
            appendAt(p, c, replaceeIndex);
          }, r);
        });
        return replacee;
      });
    };
    var replaceBy = function (component, replaceConfig, replaceState, replaceePred, replacer) {
      var children = contents(component, replaceConfig);
      return findIndex(children, replaceePred).bind(function (replaceeIndex) {
        return replaceAt(component, replaceConfig, replaceState, replaceeIndex, replacer);
      });
    };

    var ReplaceApis = /*#__PURE__*/Object.freeze({
        append: append$2,
        prepend: prepend$2,
        remove: remove$7,
        replaceAt: replaceAt,
        replaceBy: replaceBy,
        set: set$8,
        contents: contents
    });

    var Replacing = create$1({
      fields: [],
      name: 'replacing',
      apis: ReplaceApis
    });

    var transpose = function (obj) {
      return tupleMap(obj, function (v, k) {
        return {
          k: v,
          v: k
        };
      });
    };
    var trace = function (items, byItem, byMenu, finish) {
      return readOptFrom$1(byMenu, finish).bind(function (triggerItem) {
        return readOptFrom$1(items, triggerItem).bind(function (triggerMenu) {
          var rest = trace(items, byItem, byMenu, triggerMenu);
          return Option.some([triggerMenu].concat(rest));
        });
      }).getOr([]);
    };
    var generate$5 = function (menus, expansions) {
      var items = {};
      each(menus, function (menuItems, menu) {
        each$1(menuItems, function (item) {
          items[item] = menu;
        });
      });
      var byItem = expansions;
      var byMenu = transpose(expansions);
      var menuPaths = map(byMenu, function (_triggerItem, submenu) {
        return [submenu].concat(trace(items, byItem, byMenu, submenu));
      });
      return map(items, function (menu) {
        return readOptFrom$1(menuPaths, menu).getOr([menu]);
      });
    };

    var init$4 = function () {
      var expansions = Cell({});
      var menus = Cell({});
      var paths = Cell({});
      var primary = Cell(Option.none());
      var directory = Cell({});
      var clear = function () {
        expansions.set({});
        menus.set({});
        paths.set({});
        primary.set(Option.none());
      };
      var isClear = function () {
        return primary.get().isNone();
      };
      var setMenuBuilt = function (menuName, built) {
        var _a;
        menus.set(__assign({}, menus.get(), (_a = {}, _a[menuName] = {
          type: 'prepared',
          menu: built
        }, _a)));
      };
      var setContents = function (sPrimary, sMenus, sExpansions, dir) {
        primary.set(Option.some(sPrimary));
        expansions.set(sExpansions);
        menus.set(sMenus);
        directory.set(dir);
        var sPaths = generate$5(dir, sExpansions);
        paths.set(sPaths);
      };
      var expand = function (itemValue) {
        return readOptFrom$1(expansions.get(), itemValue).map(function (menu) {
          var current = readOptFrom$1(paths.get(), itemValue).getOr([]);
          return [menu].concat(current);
        });
      };
      var collapse = function (itemValue) {
        return readOptFrom$1(paths.get(), itemValue).bind(function (path) {
          return path.length > 1 ? Option.some(path.slice(1)) : Option.none();
        });
      };
      var refresh = function (itemValue) {
        return readOptFrom$1(paths.get(), itemValue);
      };
      var lookupMenu = function (menuValue) {
        return readOptFrom$1(menus.get(), menuValue);
      };
      var otherMenus = function (path) {
        var menuValues = directory.get();
        return difference(keys(menuValues), path);
      };
      var getPrimary = function () {
        return primary.get().bind(function (primaryName) {
          return lookupMenu(primaryName).bind(function (prep) {
            return prep.type === 'prepared' ? Option.some(prep.menu) : Option.none();
          });
        });
      };
      var getMenus = function () {
        return menus.get();
      };
      return {
        setMenuBuilt: setMenuBuilt,
        setContents: setContents,
        expand: expand,
        refresh: refresh,
        collapse: collapse,
        lookupMenu: lookupMenu,
        otherMenus: otherMenus,
        getPrimary: getPrimary,
        getMenus: getMenus,
        clear: clear,
        isClear: isClear
      };
    };
    var LayeredState = { init: init$4 };

    var make$3 = function (detail, rawUiSpec) {
      var submenuParentItems = Cell(Option.none());
      var buildMenus = function (container, primaryName, menus) {
        return map(menus, function (spec, name) {
          var makeSketch = function () {
            return Menu.sketch(__assign({ dom: spec.dom }, spec, {
              value: name,
              items: spec.items,
              markers: detail.markers,
              fakeFocus: detail.fakeFocus,
              onHighlight: detail.onHighlight,
              focusManager: detail.fakeFocus ? highlights() : dom()
            }));
          };
          return name === primaryName ? {
            type: 'prepared',
            menu: container.getSystem().build(makeSketch())
          } : {
            type: 'notbuilt',
            nbMenu: makeSketch
          };
        });
      };
      var layeredState = LayeredState.init();
      var setup = function (container) {
        var componentMap = buildMenus(container, detail.data.primary, detail.data.menus);
        var directory = toDirectory(container);
        layeredState.setContents(detail.data.primary, componentMap, detail.data.expansions, directory);
        return layeredState.getPrimary();
      };
      var getItemValue = function (item) {
        return Representing.getValue(item).value;
      };
      var toDirectory = function (container) {
        return map(detail.data.menus, function (data, menuName) {
          return bind(data.items, function (item) {
            return item.type === 'separator' ? [] : [item.data.value];
          });
        });
      };
      var setActiveMenu = function (container, menu) {
        Highlighting.highlight(container, menu);
        Highlighting.getHighlighted(menu).orThunk(function () {
          return Highlighting.getFirst(menu);
        }).each(function (item) {
          dispatch(container, item.element(), focusItem());
        });
      };
      var getMenus = function (state, menuValues) {
        return cat(map$1(menuValues, function (mv) {
          return state.lookupMenu(mv).bind(function (prep) {
            return prep.type === 'prepared' ? Option.some(prep.menu) : Option.none();
          });
        }));
      };
      var closeOthers = function (container, state, path) {
        var others = getMenus(state, state.otherMenus(path));
        each$1(others, function (o) {
          remove$6(o.element(), [detail.markers.backgroundMenu]);
          if (!detail.stayInDom) {
            Replacing.remove(container, o);
          }
        });
      };
      var getSubmenuParents = function (container) {
        return submenuParentItems.get().getOrThunk(function () {
          var r = {};
          var items = descendants$1(container.element(), '.' + detail.markers.item);
          var parentItems = filter(items, function (i) {
            return get$1(i, 'aria-haspopup') === 'true';
          });
          each$1(parentItems, function (i) {
            container.getSystem().getByDom(i).each(function (itemComp) {
              var key = getItemValue(itemComp);
              r[key] = itemComp;
            });
          });
          submenuParentItems.set(Option.some(r));
          return r;
        });
      };
      var updateAriaExpansions = function (container, path) {
        var parentItems = getSubmenuParents(container);
        each(parentItems, function (v, k) {
          var expanded = contains(path, k);
          set(v.element(), 'aria-expanded', expanded);
        });
      };
      var updateMenuPath = function (container, state, path) {
        return Option.from(path[0]).bind(function (latestMenuName) {
          return state.lookupMenu(latestMenuName).bind(function (menuPrep) {
            if (menuPrep.type === 'notbuilt') {
              return Option.none();
            } else {
              var activeMenu = menuPrep.menu;
              var rest = getMenus(state, path.slice(1));
              each$1(rest, function (r) {
                add$2(r.element(), detail.markers.backgroundMenu);
              });
              if (!inBody(activeMenu.element())) {
                Replacing.append(container, premade$1(activeMenu));
              }
              remove$6(activeMenu.element(), [detail.markers.backgroundMenu]);
              setActiveMenu(container, activeMenu);
              closeOthers(container, state, path);
              return Option.some(activeMenu);
            }
          });
        });
      };
      var ExpandHighlightDecision;
      (function (ExpandHighlightDecision) {
        ExpandHighlightDecision[ExpandHighlightDecision['HighlightSubmenu'] = 0] = 'HighlightSubmenu';
        ExpandHighlightDecision[ExpandHighlightDecision['HighlightParent'] = 1] = 'HighlightParent';
      }(ExpandHighlightDecision || (ExpandHighlightDecision = {})));
      var buildIfRequired = function (container, menuName, menuPrep) {
        if (menuPrep.type === 'notbuilt') {
          var menu = container.getSystem().build(menuPrep.nbMenu());
          layeredState.setMenuBuilt(menuName, menu);
          return menu;
        } else {
          return menuPrep.menu;
        }
      };
      var expandRight = function (container, item, decision) {
        if (decision === void 0) {
          decision = ExpandHighlightDecision.HighlightSubmenu;
        }
        var value = getItemValue(item);
        return layeredState.expand(value).bind(function (path) {
          updateAriaExpansions(container, path);
          return Option.from(path[0]).bind(function (menuName) {
            return layeredState.lookupMenu(menuName).bind(function (activeMenuPrep) {
              var activeMenu = buildIfRequired(container, menuName, activeMenuPrep);
              if (!inBody(activeMenu.element())) {
                Replacing.append(container, premade$1(activeMenu));
              }
              detail.onOpenSubmenu(container, item, activeMenu);
              if (decision === ExpandHighlightDecision.HighlightSubmenu) {
                Highlighting.highlightFirst(activeMenu);
                return updateMenuPath(container, layeredState, path);
              } else {
                Highlighting.dehighlightAll(activeMenu);
                return Option.some(item);
              }
            });
          });
        });
      };
      var collapseLeft = function (container, item) {
        var value = getItemValue(item);
        return layeredState.collapse(value).bind(function (path) {
          updateAriaExpansions(container, path);
          return updateMenuPath(container, layeredState, path).map(function (activeMenu) {
            detail.onCollapseMenu(container, item, activeMenu);
            return activeMenu;
          });
        });
      };
      var updateView = function (container, item) {
        var value = getItemValue(item);
        return layeredState.refresh(value).bind(function (path) {
          updateAriaExpansions(container, path);
          return updateMenuPath(container, layeredState, path);
        });
      };
      var onRight = function (container, item) {
        return inside(item.element()) ? Option.none() : expandRight(container, item, ExpandHighlightDecision.HighlightSubmenu);
      };
      var onLeft = function (container, item) {
        return inside(item.element()) ? Option.none() : collapseLeft(container, item);
      };
      var onEscape = function (container, item) {
        return collapseLeft(container, item).orThunk(function () {
          return detail.onEscape(container, item).map(function () {
            return container;
          });
        });
      };
      var keyOnItem = function (f) {
        return function (container, simulatedEvent) {
          return closest$3(simulatedEvent.getSource(), '.' + detail.markers.item).bind(function (target) {
            return container.getSystem().getByDom(target).toOption().bind(function (item) {
              return f(container, item).map(function () {
                return true;
              });
            });
          });
        };
      };
      var events = derive([
        run(focus$5(), function (sandbox, simulatedEvent) {
          var menu = simulatedEvent.event().menu();
          Highlighting.highlight(sandbox, menu);
          var value = getItemValue(simulatedEvent.event().item());
          layeredState.refresh(value).each(function (path) {
            return closeOthers(sandbox, layeredState, path);
          });
        }),
        runOnExecute(function (component, simulatedEvent) {
          var target = simulatedEvent.event().target();
          component.getSystem().getByDom(target).each(function (item) {
            var itemValue = getItemValue(item);
            if (itemValue.indexOf('collapse-item') === 0) {
              collapseLeft(component, item);
            }
            expandRight(component, item, ExpandHighlightDecision.HighlightSubmenu).fold(function () {
              detail.onExecute(component, item);
            }, function () {
            });
          });
        }),
        runOnAttached(function (container, simulatedEvent) {
          setup(container).each(function (primary) {
            Replacing.append(container, premade$1(primary));
            detail.onOpenMenu(container, primary);
            if (detail.highlightImmediately) {
              setActiveMenu(container, primary);
            }
          });
        })
      ].concat(detail.navigateOnHover ? [run(hover(), function (sandbox, simulatedEvent) {
          var item = simulatedEvent.event().item();
          updateView(sandbox, item);
          expandRight(sandbox, item, ExpandHighlightDecision.HighlightParent);
          detail.onHover(sandbox, item);
        })] : []));
      var collapseMenuApi = function (container) {
        Highlighting.getHighlighted(container).each(function (currentMenu) {
          Highlighting.getHighlighted(currentMenu).each(function (currentItem) {
            collapseLeft(container, currentItem);
          });
        });
      };
      var highlightPrimary = function (container) {
        layeredState.getPrimary().each(function (primary) {
          setActiveMenu(container, primary);
        });
      };
      var apis = {
        collapseMenu: collapseMenuApi,
        highlightPrimary: highlightPrimary
      };
      return {
        uid: detail.uid,
        dom: detail.dom,
        markers: detail.markers,
        behaviours: augment(detail.tmenuBehaviours, [
          Keying.config({
            mode: 'special',
            onRight: keyOnItem(onRight),
            onLeft: keyOnItem(onLeft),
            onEscape: keyOnItem(onEscape),
            focusIn: function (container, keyInfo) {
              layeredState.getPrimary().each(function (primary) {
                dispatch(container, primary.element(), focusItem());
              });
            }
          }),
          Highlighting.config({
            highlightClass: detail.markers.selectedMenu,
            itemClass: detail.markers.menu
          }),
          Composing.config({
            find: function (container) {
              return Highlighting.getHighlighted(container);
            }
          }),
          Replacing.config({})
        ]),
        eventOrder: detail.eventOrder,
        apis: apis,
        events: events
      };
    };
    var collapseItem = constant('collapse-item');

    var tieredData = function (primary, menus, expansions) {
      return {
        primary: primary,
        menus: menus,
        expansions: expansions
      };
    };
    var singleData = function (name, menu) {
      return {
        primary: name,
        menus: wrap$2(name, menu),
        expansions: {}
      };
    };
    var collapseItem$1 = function (text) {
      return {
        value: generate$1(collapseItem()),
        meta: { text: text }
      };
    };
    var tieredMenu = single$2({
      name: 'TieredMenu',
      configFields: [
        onStrictKeyboardHandler('onExecute'),
        onStrictKeyboardHandler('onEscape'),
        onStrictHandler('onOpenMenu'),
        onStrictHandler('onOpenSubmenu'),
        onHandler('onCollapseMenu'),
        defaulted$1('highlightImmediately', true),
        strictObjOf('data', [
          strict$1('primary'),
          strict$1('menus'),
          strict$1('expansions')
        ]),
        defaulted$1('fakeFocus', false),
        onHandler('onHighlight'),
        onHandler('onHover'),
        tieredMenuMarkers(),
        strict$1('dom'),
        defaulted$1('navigateOnHover', true),
        defaulted$1('stayInDom', false),
        field$1('tmenuBehaviours', [
          Keying,
          Highlighting,
          Composing,
          Replacing
        ]),
        defaulted$1('eventOrder', {})
      ],
      apis: {
        collapseMenu: function (apis, tmenu) {
          apis.collapseMenu(tmenu);
        },
        highlightPrimary: function (apis, tmenu) {
          apis.highlightPrimary(tmenu);
        }
      },
      factory: make$3,
      extraApis: {
        tieredData: tieredData,
        singleData: singleData,
        collapseItem: collapseItem$1
      }
    });

    var findRoute = function (component, transConfig, transState, route) {
      return readOptFrom$1(transConfig.routes, route.start).bind(function (sConfig) {
        return readOptFrom$1(sConfig, route.destination);
      });
    };
    var getTransition = function (comp, transConfig, transState) {
      var route = getCurrentRoute(comp, transConfig, transState);
      return route.bind(function (r) {
        return getTransitionOf(comp, transConfig, transState, r);
      });
    };
    var getTransitionOf = function (comp, transConfig, transState, route) {
      return findRoute(comp, transConfig, transState, route).bind(function (r) {
        return r.transition.map(function (t) {
          return {
            transition: t,
            route: r
          };
        });
      });
    };
    var disableTransition = function (comp, transConfig, transState) {
      getTransition(comp, transConfig, transState).each(function (routeTransition) {
        var t = routeTransition.transition;
        remove$4(comp.element(), t.transitionClass);
        remove$1(comp.element(), transConfig.destinationAttr);
      });
    };
    var getNewRoute = function (comp, transConfig, transState, destination) {
      return {
        start: get$1(comp.element(), transConfig.stateAttr),
        destination: destination
      };
    };
    var getCurrentRoute = function (comp, transConfig, transState) {
      var el = comp.element();
      return has$1(el, transConfig.destinationAttr) ? Option.some({
        start: get$1(comp.element(), transConfig.stateAttr),
        destination: get$1(comp.element(), transConfig.destinationAttr)
      }) : Option.none();
    };
    var jumpTo = function (comp, transConfig, transState, destination) {
      disableTransition(comp, transConfig, transState);
      if (has$1(comp.element(), transConfig.stateAttr) && get$1(comp.element(), transConfig.stateAttr) !== destination) {
        transConfig.onFinish(comp, destination);
      }
      set(comp.element(), transConfig.stateAttr, destination);
    };
    var fasttrack = function (comp, transConfig, transState, destination) {
      if (has$1(comp.element(), transConfig.destinationAttr)) {
        set(comp.element(), transConfig.stateAttr, get$1(comp.element(), transConfig.destinationAttr));
        remove$1(comp.element(), transConfig.destinationAttr);
      }
    };
    var progressTo = function (comp, transConfig, transState, destination) {
      fasttrack(comp, transConfig, transState, destination);
      var route = getNewRoute(comp, transConfig, transState, destination);
      getTransitionOf(comp, transConfig, transState, route).fold(function () {
        jumpTo(comp, transConfig, transState, destination);
      }, function (routeTransition) {
        disableTransition(comp, transConfig, transState);
        var t = routeTransition.transition;
        add$2(comp.element(), t.transitionClass);
        set(comp.element(), transConfig.destinationAttr, destination);
      });
    };
    var getState$1 = function (comp, transConfig, transState) {
      var e = comp.element();
      return has$1(e, transConfig.stateAttr) ? Option.some(get$1(e, transConfig.stateAttr)) : Option.none();
    };

    var TransitionApis = /*#__PURE__*/Object.freeze({
        findRoute: findRoute,
        disableTransition: disableTransition,
        getCurrentRoute: getCurrentRoute,
        jumpTo: jumpTo,
        progressTo: progressTo,
        getState: getState$1
    });

    var events$9 = function (transConfig, transState) {
      return derive([
        run(transitionend(), function (component, simulatedEvent) {
          var raw = simulatedEvent.event().raw();
          getCurrentRoute(component, transConfig, transState).each(function (route) {
            findRoute(component, transConfig, transState, route).each(function (rInfo) {
              rInfo.transition.each(function (rTransition) {
                if (raw.propertyName === rTransition.property) {
                  jumpTo(component, transConfig, transState, route.destination);
                  transConfig.onTransition(component, route);
                }
              });
            });
          });
        }),
        runOnAttached(function (comp, se) {
          jumpTo(comp, transConfig, transState, transConfig.initialState);
        })
      ]);
    };

    var ActiveTransitioning = /*#__PURE__*/Object.freeze({
        events: events$9
    });

    var TransitionSchema = [
      defaulted$1('destinationAttr', 'data-transitioning-destination'),
      defaulted$1('stateAttr', 'data-transitioning-state'),
      strict$1('initialState'),
      onHandler('onTransition'),
      onHandler('onFinish'),
      strictOf('routes', setOf$1(Result.value, setOf$1(Result.value, objOfOnly([optionObjOfOnly('transition', [
          strict$1('property'),
          strict$1('transitionClass')
        ])]))))
    ];

    var createRoutes = function (routes) {
      var r = {};
      each(routes, function (v, k) {
        var waypoints = k.split('<->');
        r[waypoints[0]] = wrap$2(waypoints[1], v);
        r[waypoints[1]] = wrap$2(waypoints[0], v);
      });
      return r;
    };
    var createBistate = function (first, second, transitions) {
      return wrapAll$1([
        {
          key: first,
          value: wrap$2(second, transitions)
        },
        {
          key: second,
          value: wrap$2(first, transitions)
        }
      ]);
    };
    var createTristate = function (first, second, third, transitions) {
      return wrapAll$1([
        {
          key: first,
          value: wrapAll$1([
            {
              key: second,
              value: transitions
            },
            {
              key: third,
              value: transitions
            }
          ])
        },
        {
          key: second,
          value: wrapAll$1([
            {
              key: first,
              value: transitions
            },
            {
              key: third,
              value: transitions
            }
          ])
        },
        {
          key: third,
          value: wrapAll$1([
            {
              key: first,
              value: transitions
            },
            {
              key: second,
              value: transitions
            }
          ])
        }
      ]);
    };
    var Transitioning = create$1({
      fields: TransitionSchema,
      name: 'transitioning',
      active: ActiveTransitioning,
      apis: TransitionApis,
      extra: {
        createRoutes: createRoutes,
        createBistate: createBistate,
        createTristate: createTristate
      }
    });

    var scrollable = Styles.resolve('scrollable');
    var register = function (element) {
      add$2(element, scrollable);
    };
    var deregister = function (element) {
      remove$4(element, scrollable);
    };
    var Scrollable = {
      register: register,
      deregister: deregister,
      scrollable: constant(scrollable)
    };

    var getValue$4 = function (item) {
      return readOptFrom$1(item, 'format').getOr(item.title);
    };
    var convert$1 = function (formats, memMenuThunk) {
      var mainMenu = makeMenu('Styles', [].concat(map$1(formats.items, function (k) {
        return makeItem(getValue$4(k), k.title, k.isSelected(), k.getPreview(), hasKey$1(formats.expansions, getValue$4(k)));
      })), memMenuThunk, false);
      var submenus = map(formats.menus, function (menuItems, menuName) {
        var items = map$1(menuItems, function (item) {
          return makeItem(getValue$4(item), item.title, item.isSelected !== undefined ? item.isSelected() : false, item.getPreview !== undefined ? item.getPreview() : '', hasKey$1(formats.expansions, getValue$4(item)));
        });
        return makeMenu(menuName, items, memMenuThunk, true);
      });
      var menus = deepMerge(submenus, wrap$2('styles', mainMenu));
      var tmenu = tieredMenu.tieredData('styles', menus, formats.expansions);
      return { tmenu: tmenu };
    };
    var makeItem = function (value, text$$1, selected, preview, isMenu) {
      return {
        data: {
          value: value,
          text: text$$1
        },
        type: 'item',
        dom: {
          tag: 'div',
          classes: isMenu ? [Styles.resolve('styles-item-is-menu')] : []
        },
        toggling: {
          toggleOnExecute: false,
          toggleClass: Styles.resolve('format-matches'),
          selected: selected
        },
        itemBehaviours: derive$1(isMenu ? [] : [Receivers.format(value, function (comp, status) {
            var toggle = status ? Toggling.on : Toggling.off;
            toggle(comp);
          })]),
        components: [{
            dom: {
              tag: 'div',
              attributes: { style: preview },
              innerHtml: text$$1
            }
          }]
      };
    };
    var makeMenu = function (value, items, memMenuThunk, collapsable) {
      return {
        value: value,
        dom: { tag: 'div' },
        components: [
          Button.sketch({
            dom: {
              tag: 'div',
              classes: [Styles.resolve('styles-collapser')]
            },
            components: collapsable ? [
              {
                dom: {
                  tag: 'span',
                  classes: [Styles.resolve('styles-collapse-icon')]
                }
              },
              text(value)
            ] : [text(value)],
            action: function (item) {
              if (collapsable) {
                var comp = memMenuThunk().get(item);
                tieredMenu.collapseMenu(comp);
              }
            }
          }),
          {
            dom: {
              tag: 'div',
              classes: [Styles.resolve('styles-menu-items-container')]
            },
            components: [Menu.parts().items({})],
            behaviours: derive$1([config('adhoc-scrollable-menu', [
                runOnAttached(function (component, simulatedEvent) {
                  set$3(component.element(), 'overflow-y', 'auto');
                  set$3(component.element(), '-webkit-overflow-scrolling', 'touch');
                  Scrollable.register(component.element());
                }),
                runOnDetached(function (component) {
                  remove$5(component.element(), 'overflow-y');
                  remove$5(component.element(), '-webkit-overflow-scrolling');
                  Scrollable.deregister(component.element());
                })
              ])])
          }
        ],
        items: items,
        menuBehaviours: derive$1([Transitioning.config({
            initialState: 'after',
            routes: Transitioning.createTristate('before', 'current', 'after', {
              transition: {
                property: 'transform',
                transitionClass: 'transitioning'
              }
            })
          })])
      };
    };
    var sketch$9 = function (settings) {
      var dataset = convert$1(settings.formats, function () {
        return memMenu;
      });
      var memMenu = record(tieredMenu.sketch({
        dom: {
          tag: 'div',
          classes: [Styles.resolve('styles-menu')]
        },
        components: [],
        fakeFocus: true,
        stayInDom: true,
        onExecute: function (tmenu, item) {
          var v = Representing.getValue(item);
          settings.handle(item, v.value);
          return Option.none();
        },
        onEscape: function () {
          return Option.none();
        },
        onOpenMenu: function (container, menu) {
          var w = get$7(container.element());
          set$5(menu.element(), w);
          Transitioning.jumpTo(menu, 'current');
        },
        onOpenSubmenu: function (container, item, submenu) {
          var w = get$7(container.element());
          var menu = ancestor$2(item.element(), '[role="menu"]').getOrDie('hacky');
          var menuComp = container.getSystem().getByDom(menu).getOrDie();
          set$5(submenu.element(), w);
          Transitioning.progressTo(menuComp, 'before');
          Transitioning.jumpTo(submenu, 'after');
          Transitioning.progressTo(submenu, 'current');
        },
        onCollapseMenu: function (container, item, menu) {
          var submenu = ancestor$2(item.element(), '[role="menu"]').getOrDie('hacky');
          var submenuComp = container.getSystem().getByDom(submenu).getOrDie();
          Transitioning.progressTo(submenuComp, 'after');
          Transitioning.progressTo(menu, 'current');
        },
        navigateOnHover: false,
        highlightImmediately: true,
        data: dataset.tmenu,
        markers: {
          backgroundMenu: Styles.resolve('styles-background-menu'),
          menu: Styles.resolve('styles-menu'),
          selectedMenu: Styles.resolve('styles-selected-menu'),
          item: Styles.resolve('styles-item'),
          selectedItem: Styles.resolve('styles-selected-item')
        }
      }));
      return memMenu.asSpec();
    };
    var StylesMenu = { sketch: sketch$9 };

    var getFromExpandingItem = function (item) {
      var newItem = deepMerge(exclude$1(item, ['items']), { menu: true });
      var rest = expand(item.items);
      var newMenus = deepMerge(rest.menus, wrap$2(item.title, rest.items));
      var newExpansions = deepMerge(rest.expansions, wrap$2(item.title, item.title));
      return {
        item: newItem,
        menus: newMenus,
        expansions: newExpansions
      };
    };
    var getFromItem = function (item) {
      return hasKey$1(item, 'items') ? getFromExpandingItem(item) : {
        item: item,
        menus: {},
        expansions: {}
      };
    };
    var expand = function (items) {
      return foldr(items, function (acc, item) {
        var newData = getFromItem(item);
        return {
          menus: deepMerge(acc.menus, newData.menus),
          items: [newData.item].concat(acc.items),
          expansions: deepMerge(acc.expansions, newData.expansions)
        };
      }, {
        menus: {},
        expansions: {},
        items: []
      });
    };
    var StyleConversions = { expand: expand };

    var register$1 = function (editor, settings) {
      var isSelectedFor = function (format) {
        return function () {
          return editor.formatter.match(format);
        };
      };
      var getPreview = function (format) {
        return function () {
          var styles = editor.formatter.getCssText(format);
          return styles;
        };
      };
      var enrichSupported = function (item) {
        return deepMerge(item, {
          isSelected: isSelectedFor(item.format),
          getPreview: getPreview(item.format)
        });
      };
      var enrichMenu = function (item) {
        return deepMerge(item, {
          isSelected: constant(false),
          getPreview: constant('')
        });
      };
      var enrichCustom = function (item) {
        var formatName = generate$1(item.title);
        var newItem = deepMerge(item, {
          format: formatName,
          isSelected: isSelectedFor(formatName),
          getPreview: getPreview(formatName)
        });
        editor.formatter.register(formatName, newItem);
        return newItem;
      };
      var formats = readOptFrom$1(settings, 'style_formats').getOr(DefaultStyleFormats);
      var doEnrich = function (items) {
        return map$1(items, function (item) {
          if (hasKey$1(item, 'items')) {
            var newItems = doEnrich(item.items);
            return deepMerge(enrichMenu(item), { items: newItems });
          } else if (hasKey$1(item, 'format')) {
            return enrichSupported(item);
          } else {
            return enrichCustom(item);
          }
        });
      };
      return doEnrich(formats);
    };
    var prune = function (editor, formats) {
      var doPrune = function (items) {
        return bind(items, function (item) {
          if (item.items !== undefined) {
            var newItems = doPrune(item.items);
            return newItems.length > 0 ? [item] : [];
          } else {
            var keep = hasKey$1(item, 'format') ? editor.formatter.canApply(item.format) : true;
            return keep ? [item] : [];
          }
        });
      };
      var prunedItems = doPrune(formats);
      return StyleConversions.expand(prunedItems);
    };
    var ui = function (editor, formats, onDone) {
      var pruned = prune(editor, formats);
      return StylesMenu.sketch({
        formats: pruned,
        handle: function (item, value) {
          editor.undoManager.transact(function () {
            if (Toggling.isOn(item)) {
              editor.formatter.remove(value);
            } else {
              editor.formatter.apply(value);
            }
          });
          onDone();
        }
      });
    };
    var StyleFormats = {
      register: register$1,
      ui: ui
    };

    var defaults = [
      'undo',
      'bold',
      'italic',
      'link',
      'image',
      'bullist',
      'styleselect'
    ];
    var extract$1 = function (rawToolbar) {
      var toolbar = rawToolbar.replace(/\|/g, ' ').trim();
      return toolbar.length > 0 ? toolbar.split(/\s+/) : [];
    };
    var identifyFromArray = function (toolbar) {
      return bind(toolbar, function (item) {
        return isArray(item) ? identifyFromArray(item) : extract$1(item);
      });
    };
    var identify = function (settings) {
      var toolbar = settings.toolbar !== undefined ? settings.toolbar : defaults;
      return isArray(toolbar) ? identifyFromArray(toolbar) : extract$1(toolbar);
    };
    var setup = function (realm, editor) {
      var commandSketch = function (name) {
        return function () {
          return Buttons.forToolbarCommand(editor, name);
        };
      };
      var stateCommandSketch = function (name) {
        return function () {
          return Buttons.forToolbarStateCommand(editor, name);
        };
      };
      var actionSketch = function (name, query, action) {
        return function () {
          return Buttons.forToolbarStateAction(editor, name, query, action);
        };
      };
      var undo = commandSketch('undo');
      var redo = commandSketch('redo');
      var bold = stateCommandSketch('bold');
      var italic = stateCommandSketch('italic');
      var underline = stateCommandSketch('underline');
      var removeformat = commandSketch('removeformat');
      var link = function () {
        return sketch$8(realm, editor);
      };
      var unlink = actionSketch('unlink', 'link', function () {
        editor.execCommand('unlink', null, false);
      });
      var image = function () {
        return sketch$5(editor);
      };
      var bullist = actionSketch('unordered-list', 'ul', function () {
        editor.execCommand('InsertUnorderedList', null, false);
      });
      var numlist = actionSketch('ordered-list', 'ol', function () {
        editor.execCommand('InsertOrderedList', null, false);
      });
      var fontsizeselect = function () {
        return sketch$4(realm, editor);
      };
      var forecolor = function () {
        return ColorSlider.sketch(realm, editor);
      };
      var styleFormats = StyleFormats.register(editor, editor.settings);
      var styleFormatsMenu = function () {
        return StyleFormats.ui(editor, styleFormats, function () {
          editor.fire('scrollIntoView');
        });
      };
      var styleselect = function () {
        return Buttons.forToolbar('style-formats', function (button) {
          editor.fire('toReading');
          realm.dropup().appear(styleFormatsMenu, Toggling.on, button);
        }, derive$1([
          Toggling.config({
            toggleClass: Styles.resolve('toolbar-button-selected'),
            toggleOnExecute: false,
            aria: { mode: 'pressed' }
          }),
          Receiving.config({
            channels: wrapAll$1([
              Receivers.receive(TinyChannels.orientationChanged(), Toggling.off),
              Receivers.receive(TinyChannels.dropupDismissed(), Toggling.off)
            ])
          })
        ]), editor);
      };
      var feature = function (prereq, sketch) {
        return {
          isSupported: function () {
            return prereq.forall(function (p) {
              return hasKey$1(editor.buttons, p);
            });
          },
          sketch: sketch
        };
      };
      return {
        undo: feature(Option.none(), undo),
        redo: feature(Option.none(), redo),
        bold: feature(Option.none(), bold),
        italic: feature(Option.none(), italic),
        underline: feature(Option.none(), underline),
        removeformat: feature(Option.none(), removeformat),
        link: feature(Option.none(), link),
        unlink: feature(Option.none(), unlink),
        image: feature(Option.none(), image),
        bullist: feature(Option.some('bullist'), bullist),
        numlist: feature(Option.some('numlist'), numlist),
        fontsizeselect: feature(Option.none(), fontsizeselect),
        forecolor: feature(Option.none(), forecolor),
        styleselect: feature(Option.none(), styleselect)
      };
    };
    var detect$4 = function (settings, features) {
      var itemNames = identify(settings);
      var present = {};
      return bind(itemNames, function (iName) {
        var r = !hasKey$1(present, iName) && hasKey$1(features, iName) && features[iName].isSupported() ? [features[iName].sketch()] : [];
        present[iName] = true;
        return r;
      });
    };
    var Features = {
      identify: identify,
      setup: setup,
      detect: detect$4
    };

    var mkEvent = function (target, x, y, stop, prevent, kill, raw) {
      return {
        target: constant(target),
        x: constant(x),
        y: constant(y),
        stop: stop,
        prevent: prevent,
        kill: kill,
        raw: constant(raw)
      };
    };
    var handle = function (filter, handler) {
      return function (rawEvent) {
        if (!filter(rawEvent)) {
          return;
        }
        var target = Element$$1.fromDom(rawEvent.target);
        var stop = function () {
          rawEvent.stopPropagation();
        };
        var prevent = function () {
          rawEvent.preventDefault();
        };
        var kill = compose(prevent, stop);
        var evt = mkEvent(target, rawEvent.clientX, rawEvent.clientY, stop, prevent, kill, rawEvent);
        handler(evt);
      };
    };
    var binder = function (element, event, filter, handler, useCapture) {
      var wrapped = handle(filter, handler);
      element.dom().addEventListener(event, wrapped, useCapture);
      return { unbind: curry(unbind, element, event, wrapped, useCapture) };
    };
    var bind$2 = function (element, event, filter, handler) {
      return binder(element, event, filter, handler, false);
    };
    var capture = function (element, event, filter, handler) {
      return binder(element, event, filter, handler, true);
    };
    var unbind = function (element, event, handler, useCapture) {
      element.dom().removeEventListener(event, handler, useCapture);
    };

    var filter$1 = constant(true);
    var bind$3 = function (element, event, handler) {
      return bind$2(element, event, filter$1, handler);
    };
    var capture$1 = function (element, event, handler) {
      return capture(element, event, filter$1, handler);
    };

    var INTERVAL = 50;
    var INSURANCE = 1000 / INTERVAL;
    var get$c = function (outerWindow) {
      var isPortrait = outerWindow.matchMedia('(orientation: portrait)').matches;
      return { isPortrait: constant(isPortrait) };
    };
    var getActualWidth = function (outerWindow) {
      var isIos = PlatformDetection$1.detect().os.isiOS();
      var isPortrait = get$c(outerWindow).isPortrait();
      return isIos && !isPortrait ? outerWindow.screen.height : outerWindow.screen.width;
    };
    var onChange = function (outerWindow, listeners) {
      var win = Element$$1.fromDom(outerWindow);
      var poller = null;
      var change = function () {
        clearInterval(poller);
        var orientation = get$c(outerWindow);
        listeners.onChange(orientation);
        onAdjustment(function () {
          listeners.onReady(orientation);
        });
      };
      var orientationHandle = bind$3(win, 'orientationchange', change);
      var onAdjustment = function (f) {
        clearInterval(poller);
        var flag = outerWindow.innerHeight;
        var insurance = 0;
        poller = setInterval(function () {
          if (flag !== outerWindow.innerHeight) {
            clearInterval(poller);
            f(Option.some(outerWindow.innerHeight));
          } else if (insurance > INSURANCE) {
            clearInterval(poller);
            f(Option.none());
          }
          insurance++;
        }, INTERVAL);
      };
      var destroy = function () {
        orientationHandle.unbind();
      };
      return {
        onAdjustment: onAdjustment,
        destroy: destroy
      };
    };
    var Orientation = {
      get: get$c,
      onChange: onChange,
      getActualWidth: getActualWidth
    };

    function DelayedFunction (fun, delay) {
      var ref = null;
      var schedule = function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        ref = setTimeout(function () {
          fun.apply(null, args);
          ref = null;
        }, delay);
      };
      var cancel = function () {
        if (ref !== null) {
          clearTimeout(ref);
          ref = null;
        }
      };
      return {
        cancel: cancel,
        schedule: schedule
      };
    }

    var SIGNIFICANT_MOVE = 5;
    var LONGPRESS_DELAY = 400;
    var getTouch = function (event) {
      var raw = event.raw();
      if (raw.touches === undefined || raw.touches.length !== 1) {
        return Option.none();
      }
      return Option.some(raw.touches[0]);
    };
    var isFarEnough = function (touch, data) {
      var distX = Math.abs(touch.clientX - data.x());
      var distY = Math.abs(touch.clientY - data.y());
      return distX > SIGNIFICANT_MOVE || distY > SIGNIFICANT_MOVE;
    };
    var monitor = function (settings) {
      var startData = Cell(Option.none());
      var longpress$$1 = DelayedFunction(function (event) {
        startData.set(Option.none());
        settings.triggerEvent(longpress(), event);
      }, LONGPRESS_DELAY);
      var handleTouchstart = function (event) {
        getTouch(event).each(function (touch) {
          longpress$$1.cancel();
          var data = {
            x: constant(touch.clientX),
            y: constant(touch.clientY),
            target: event.target
          };
          longpress$$1.schedule(event);
          startData.set(Option.some(data));
        });
        return Option.none();
      };
      var handleTouchmove = function (event) {
        longpress$$1.cancel();
        getTouch(event).each(function (touch) {
          startData.get().each(function (data) {
            if (isFarEnough(touch, data)) {
              startData.set(Option.none());
            }
          });
        });
        return Option.none();
      };
      var handleTouchend = function (event) {
        longpress$$1.cancel();
        var isSame = function (data) {
          return eq(data.target(), event.target());
        };
        return startData.get().filter(isSame).map(function (data) {
          return settings.triggerEvent(tap(), event);
        });
      };
      var handlers = wrapAll$1([
        {
          key: touchstart(),
          value: handleTouchstart
        },
        {
          key: touchmove(),
          value: handleTouchmove
        },
        {
          key: touchend(),
          value: handleTouchend
        }
      ]);
      var fireIfReady = function (event, type) {
        return readOptFrom$1(handlers, type).bind(function (handler) {
          return handler(event);
        });
      };
      return { fireIfReady: fireIfReady };
    };

    var monitor$1 = function (editorApi) {
      var tapEvent = monitor({
        triggerEvent: function (type, evt) {
          editorApi.onTapContent(evt);
        }
      });
      var onTouchend = function () {
        return bind$3(editorApi.body(), 'touchend', function (evt) {
          tapEvent.fireIfReady(evt, 'touchend');
        });
      };
      var onTouchmove = function () {
        return bind$3(editorApi.body(), 'touchmove', function (evt) {
          tapEvent.fireIfReady(evt, 'touchmove');
        });
      };
      var fireTouchstart = function (evt) {
        tapEvent.fireIfReady(evt, 'touchstart');
      };
      return {
        fireTouchstart: fireTouchstart,
        onTouchend: onTouchend,
        onTouchmove: onTouchmove
      };
    };
    var TappingEvent = { monitor: monitor$1 };

    var isAndroid6 = PlatformDetection$1.detect().os.version.major >= 6;
    var initEvents = function (editorApi, toolstrip, alloy) {
      var tapping = TappingEvent.monitor(editorApi);
      var outerDoc = owner(toolstrip);
      var isRanged = function (sel) {
        return !eq(sel.start(), sel.finish()) || sel.soffset() !== sel.foffset();
      };
      var hasRangeInUi = function () {
        return active(outerDoc).filter(function (input) {
          return name(input) === 'input';
        }).exists(function (input) {
          return input.dom().selectionStart !== input.dom().selectionEnd;
        });
      };
      var updateMargin = function () {
        var rangeInContent = editorApi.doc().dom().hasFocus() && editorApi.getSelection().exists(isRanged);
        alloy.getByDom(toolstrip).each((rangeInContent || hasRangeInUi()) === true ? Toggling.on : Toggling.off);
      };
      var listeners = [
        bind$3(editorApi.body(), 'touchstart', function (evt) {
          editorApi.onTouchContent();
          tapping.fireTouchstart(evt);
        }),
        tapping.onTouchmove(),
        tapping.onTouchend(),
        bind$3(toolstrip, 'touchstart', function (evt) {
          editorApi.onTouchToolstrip();
        }),
        editorApi.onToReading(function () {
          blur$$1(editorApi.body());
        }),
        editorApi.onToEditing(noop),
        editorApi.onScrollToCursor(function (tinyEvent) {
          tinyEvent.preventDefault();
          editorApi.getCursorBox().each(function (bounds) {
            var cWin = editorApi.win();
            var isOutside = bounds.top() > cWin.innerHeight || bounds.bottom() > cWin.innerHeight;
            var cScrollBy = isOutside ? bounds.bottom() - cWin.innerHeight + 50 : 0;
            if (cScrollBy !== 0) {
              cWin.scrollTo(cWin.pageXOffset, cWin.pageYOffset + cScrollBy);
            }
          });
        })
      ].concat(isAndroid6 === true ? [] : [
        bind$3(Element$$1.fromDom(editorApi.win()), 'blur', function () {
          alloy.getByDom(toolstrip).each(Toggling.off);
        }),
        bind$3(outerDoc, 'select', updateMargin),
        bind$3(editorApi.doc(), 'selectionchange', updateMargin)
      ]);
      var destroy = function () {
        each$1(listeners, function (l) {
          l.unbind();
        });
      };
      return { destroy: destroy };
    };
    var AndroidEvents = { initEvents: initEvents };

    var safeParse = function (element, attribute) {
      var parsed = parseInt(get$1(element, attribute), 10);
      return isNaN(parsed) ? 0 : parsed;
    };
    var DataAttributes = { safeParse: safeParse };

    function NodeValue (is, name) {
      var get = function (element) {
        if (!is(element)) {
          throw new Error('Can only get ' + name + ' value of a ' + name + ' node');
        }
        return getOption(element).getOr('');
      };
      var getOptionIE10 = function (element) {
        try {
          return getOptionSafe(element);
        } catch (e) {
          return Option.none();
        }
      };
      var getOptionSafe = function (element) {
        return is(element) ? Option.from(element.dom().nodeValue) : Option.none();
      };
      var browser = PlatformDetection$1.detect().browser;
      var getOption = browser.isIE() && browser.version.major === 10 ? getOptionIE10 : getOptionSafe;
      var set = function (element, value) {
        if (!is(element)) {
          throw new Error('Can only set raw ' + name + ' value of a ' + name + ' node');
        }
        element.dom().nodeValue = value;
      };
      return {
        get: get,
        getOption: getOption,
        set: set
      };
    }

    var api$3 = NodeValue(isText, 'text');
    var get$d = function (element) {
      return api$3.get(element);
    };
    var getOption = function (element) {
      return api$3.getOption(element);
    };

    var getEnd = function (element) {
      return name(element) === 'img' ? 1 : getOption(element).fold(function () {
        return children(element).length;
      }, function (v) {
        return v.length;
      });
    };
    var NBSP = '\xA0';
    var isTextNodeWithCursorPosition = function (el) {
      return getOption(el).filter(function (text) {
        return text.trim().length !== 0 || text.indexOf(NBSP) > -1;
      }).isSome();
    };
    var elementsWithCursorPosition = [
      'img',
      'br'
    ];
    var isCursorPosition = function (elem) {
      var hasCursorPosition = isTextNodeWithCursorPosition(elem);
      return hasCursorPosition || contains(elementsWithCursorPosition, name(elem));
    };

    var adt$4 = Adt.generate([
      { before: ['element'] },
      {
        on: [
          'element',
          'offset'
        ]
      },
      { after: ['element'] }
    ]);
    var cata = function (subject, onBefore, onOn, onAfter) {
      return subject.fold(onBefore, onOn, onAfter);
    };
    var getStart = function (situ) {
      return situ.fold(identity, identity, identity);
    };
    var before$2 = adt$4.before;
    var on$1 = adt$4.on;
    var after$2 = adt$4.after;
    var Situ = {
      before: before$2,
      on: on$1,
      after: after$2,
      cata: cata,
      getStart: getStart
    };

    var type$1 = Adt.generate([
      { domRange: ['rng'] },
      {
        relative: [
          'startSitu',
          'finishSitu'
        ]
      },
      {
        exact: [
          'start',
          'soffset',
          'finish',
          'foffset'
        ]
      }
    ]);
    var range$2 = Immutable('start', 'soffset', 'finish', 'foffset');
    var relative = type$1.relative;
    var exact = type$1.exact;

    var makeRange = function (start, soffset, finish, foffset) {
      var doc = owner(start);
      var rng = doc.dom().createRange();
      rng.setStart(start.dom(), soffset);
      rng.setEnd(finish.dom(), foffset);
      return rng;
    };
    var after$3 = function (start, soffset, finish, foffset) {
      var r = makeRange(start, soffset, finish, foffset);
      var same = eq(start, finish) && soffset === foffset;
      return r.collapsed && !same;
    };

    var setStart = function (rng, situ) {
      situ.fold(function (e) {
        rng.setStartBefore(e.dom());
      }, function (e, o) {
        rng.setStart(e.dom(), o);
      }, function (e) {
        rng.setStartAfter(e.dom());
      });
    };
    var setFinish = function (rng, situ) {
      situ.fold(function (e) {
        rng.setEndBefore(e.dom());
      }, function (e, o) {
        rng.setEnd(e.dom(), o);
      }, function (e) {
        rng.setEndAfter(e.dom());
      });
    };
    var relativeToNative = function (win, startSitu, finishSitu) {
      var range = win.document.createRange();
      setStart(range, startSitu);
      setFinish(range, finishSitu);
      return range;
    };
    var exactToNative = function (win, start, soffset, finish, foffset) {
      var rng = win.document.createRange();
      rng.setStart(start.dom(), soffset);
      rng.setEnd(finish.dom(), foffset);
      return rng;
    };
    var toRect = function (rect) {
      return {
        left: constant(rect.left),
        top: constant(rect.top),
        right: constant(rect.right),
        bottom: constant(rect.bottom),
        width: constant(rect.width),
        height: constant(rect.height)
      };
    };
    var getFirstRect = function (rng) {
      var rects = rng.getClientRects();
      var rect = rects.length > 0 ? rects[0] : rng.getBoundingClientRect();
      return rect.width > 0 || rect.height > 0 ? Option.some(rect).map(toRect) : Option.none();
    };

    var adt$5 = Adt.generate([
      {
        ltr: [
          'start',
          'soffset',
          'finish',
          'foffset'
        ]
      },
      {
        rtl: [
          'start',
          'soffset',
          'finish',
          'foffset'
        ]
      }
    ]);
    var fromRange = function (win, type, range) {
      return type(Element$$1.fromDom(range.startContainer), range.startOffset, Element$$1.fromDom(range.endContainer), range.endOffset);
    };
    var getRanges = function (win, selection) {
      return selection.match({
        domRange: function (rng) {
          return {
            ltr: constant(rng),
            rtl: Option.none
          };
        },
        relative: function (startSitu, finishSitu) {
          return {
            ltr: cached(function () {
              return relativeToNative(win, startSitu, finishSitu);
            }),
            rtl: cached(function () {
              return Option.some(relativeToNative(win, finishSitu, startSitu));
            })
          };
        },
        exact: function (start, soffset, finish, foffset) {
          return {
            ltr: cached(function () {
              return exactToNative(win, start, soffset, finish, foffset);
            }),
            rtl: cached(function () {
              return Option.some(exactToNative(win, finish, foffset, start, soffset));
            })
          };
        }
      });
    };
    var doDiagnose = function (win, ranges) {
      var rng = ranges.ltr();
      if (rng.collapsed) {
        var reversed = ranges.rtl().filter(function (rev) {
          return rev.collapsed === false;
        });
        return reversed.map(function (rev) {
          return adt$5.rtl(Element$$1.fromDom(rev.endContainer), rev.endOffset, Element$$1.fromDom(rev.startContainer), rev.startOffset);
        }).getOrThunk(function () {
          return fromRange(win, adt$5.ltr, rng);
        });
      } else {
        return fromRange(win, adt$5.ltr, rng);
      }
    };
    var diagnose = function (win, selection) {
      var ranges = getRanges(win, selection);
      return doDiagnose(win, ranges);
    };
    var asLtrRange = function (win, selection) {
      var diagnosis = diagnose(win, selection);
      return diagnosis.match({
        ltr: function (start, soffset, finish, foffset) {
          var rng = win.document.createRange();
          rng.setStart(start.dom(), soffset);
          rng.setEnd(finish.dom(), foffset);
          return rng;
        },
        rtl: function (start, soffset, finish, foffset) {
          var rng = win.document.createRange();
          rng.setStart(finish.dom(), foffset);
          rng.setEnd(start.dom(), soffset);
          return rng;
        }
      });
    };

    var searchForPoint = function (rectForOffset, x, y, maxX, length) {
      if (length === 0) {
        return 0;
      } else if (x === maxX) {
        return length - 1;
      }
      var xDelta = maxX;
      for (var i = 1; i < length; i++) {
        var rect = rectForOffset(i);
        var curDeltaX = Math.abs(x - rect.left);
        if (y <= rect.bottom) {
          if (y < rect.top || curDeltaX > xDelta) {
            return i - 1;
          } else {
            xDelta = curDeltaX;
          }
        }
      }
      return 0;
    };
    var inRect = function (rect, x, y) {
      return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
    };

    var locateOffset = function (doc, textnode, x, y, rect) {
      var rangeForOffset = function (o) {
        var r = doc.dom().createRange();
        r.setStart(textnode.dom(), o);
        r.collapse(true);
        return r;
      };
      var rectForOffset = function (o) {
        var r = rangeForOffset(o);
        return r.getBoundingClientRect();
      };
      var length = get$d(textnode).length;
      var offset = searchForPoint(rectForOffset, x, y, rect.right, length);
      return rangeForOffset(offset);
    };
    var locate$1 = function (doc, node, x, y) {
      var r = doc.dom().createRange();
      r.selectNode(node.dom());
      var rects = r.getClientRects();
      var foundRect = findMap(rects, function (rect) {
        return inRect(rect, x, y) ? Option.some(rect) : Option.none();
      });
      return foundRect.map(function (rect) {
        return locateOffset(doc, node, x, y, rect);
      });
    };

    var searchInChildren = function (doc, node, x, y) {
      var r = doc.dom().createRange();
      var nodes = children(node);
      return findMap(nodes, function (n) {
        r.selectNode(n.dom());
        return inRect(r.getBoundingClientRect(), x, y) ? locateNode(doc, n, x, y) : Option.none();
      });
    };
    var locateNode = function (doc, node, x, y) {
      var locator = isText(node) ? locate$1 : searchInChildren;
      return locator(doc, node, x, y);
    };
    var locate$2 = function (doc, node, x, y) {
      var r = doc.dom().createRange();
      r.selectNode(node.dom());
      var rect = r.getBoundingClientRect();
      var boundedX = Math.max(rect.left, Math.min(rect.right, x));
      var boundedY = Math.max(rect.top, Math.min(rect.bottom, y));
      return locateNode(doc, node, boundedX, boundedY);
    };

    var first$3 = function (element) {
      return descendant(element, isCursorPosition);
    };
    var last$2 = function (element) {
      return descendantRtl(element, isCursorPosition);
    };
    var descendantRtl = function (scope, predicate) {
      var descend = function (element) {
        var children$$1 = children(element);
        for (var i = children$$1.length - 1; i >= 0; i--) {
          var child$$1 = children$$1[i];
          if (predicate(child$$1)) {
            return Option.some(child$$1);
          }
          var res = descend(child$$1);
          if (res.isSome()) {
            return res;
          }
        }
        return Option.none();
      };
      return descend(scope);
    };

    var COLLAPSE_TO_LEFT = true;
    var COLLAPSE_TO_RIGHT = false;
    var getCollapseDirection = function (rect, x) {
      return x - rect.left < rect.right - x ? COLLAPSE_TO_LEFT : COLLAPSE_TO_RIGHT;
    };
    var createCollapsedNode = function (doc, target, collapseDirection) {
      var r = doc.dom().createRange();
      r.selectNode(target.dom());
      r.collapse(collapseDirection);
      return r;
    };
    var locateInElement = function (doc, node, x) {
      var cursorRange = doc.dom().createRange();
      cursorRange.selectNode(node.dom());
      var rect = cursorRange.getBoundingClientRect();
      var collapseDirection = getCollapseDirection(rect, x);
      var f = collapseDirection === COLLAPSE_TO_LEFT ? first$3 : last$2;
      return f(node).map(function (target) {
        return createCollapsedNode(doc, target, collapseDirection);
      });
    };
    var locateInEmpty = function (doc, node, x) {
      var rect = node.dom().getBoundingClientRect();
      var collapseDirection = getCollapseDirection(rect, x);
      return Option.some(createCollapsedNode(doc, node, collapseDirection));
    };
    var search$1 = function (doc, node, x) {
      var f = children(node).length === 0 ? locateInEmpty : locateInElement;
      return f(doc, node, x);
    };

    var caretPositionFromPoint = function (doc, x, y) {
      return Option.from(doc.dom().caretPositionFromPoint(x, y)).bind(function (pos) {
        if (pos.offsetNode === null) {
          return Option.none();
        }
        var r = doc.dom().createRange();
        r.setStart(pos.offsetNode, pos.offset);
        r.collapse();
        return Option.some(r);
      });
    };
    var caretRangeFromPoint = function (doc, x, y) {
      return Option.from(doc.dom().caretRangeFromPoint(x, y));
    };
    var searchTextNodes = function (doc, node, x, y) {
      var r = doc.dom().createRange();
      r.selectNode(node.dom());
      var rect = r.getBoundingClientRect();
      var boundedX = Math.max(rect.left, Math.min(rect.right, x));
      var boundedY = Math.max(rect.top, Math.min(rect.bottom, y));
      return locate$2(doc, node, boundedX, boundedY);
    };
    var searchFromPoint = function (doc, x, y) {
      return Element$$1.fromPoint(doc, x, y).bind(function (elem) {
        var fallback = function () {
          return search$1(doc, elem, x);
        };
        return children(elem).length === 0 ? fallback() : searchTextNodes(doc, elem, x, y).orThunk(fallback);
      });
    };
    var availableSearch = document.caretPositionFromPoint ? caretPositionFromPoint : document.caretRangeFromPoint ? caretRangeFromPoint : searchFromPoint;

    var beforeSpecial = function (element, offset) {
      var name$$1 = name(element);
      if ('input' === name$$1) {
        return Situ.after(element);
      } else if (!contains([
          'br',
          'img'
        ], name$$1)) {
        return Situ.on(element, offset);
      } else {
        return offset === 0 ? Situ.before(element) : Situ.after(element);
      }
    };
    var preprocessExact = function (start, soffset, finish, foffset) {
      var startSitu = beforeSpecial(start, soffset);
      var finishSitu = beforeSpecial(finish, foffset);
      return relative(startSitu, finishSitu);
    };

    var doSetNativeRange = function (win, rng) {
      Option.from(win.getSelection()).each(function (selection) {
        selection.removeAllRanges();
        selection.addRange(rng);
      });
    };
    var doSetRange = function (win, start, soffset, finish, foffset) {
      var rng = exactToNative(win, start, soffset, finish, foffset);
      doSetNativeRange(win, rng);
    };
    var setLegacyRtlRange = function (win, selection, start, soffset, finish, foffset) {
      selection.collapse(start.dom(), soffset);
      selection.extend(finish.dom(), foffset);
    };
    var setRangeFromRelative = function (win, relative$$1) {
      return diagnose(win, relative$$1).match({
        ltr: function (start, soffset, finish, foffset) {
          doSetRange(win, start, soffset, finish, foffset);
        },
        rtl: function (start, soffset, finish, foffset) {
          var selection = win.getSelection();
          if (selection.setBaseAndExtent) {
            selection.setBaseAndExtent(start.dom(), soffset, finish.dom(), foffset);
          } else if (selection.extend) {
            try {
              setLegacyRtlRange(win, selection, start, soffset, finish, foffset);
            } catch (e) {
              doSetRange(win, finish, foffset, start, soffset);
            }
          } else {
            doSetRange(win, finish, foffset, start, soffset);
          }
        }
      });
    };
    var setExact = function (win, start, soffset, finish, foffset) {
      var relative$$1 = preprocessExact(start, soffset, finish, foffset);
      setRangeFromRelative(win, relative$$1);
    };
    var readRange = function (selection) {
      if (selection.rangeCount > 0) {
        var firstRng = selection.getRangeAt(0);
        var lastRng = selection.getRangeAt(selection.rangeCount - 1);
        return Option.some(range$2(Element$$1.fromDom(firstRng.startContainer), firstRng.startOffset, Element$$1.fromDom(lastRng.endContainer), lastRng.endOffset));
      } else {
        return Option.none();
      }
    };
    var doGetExact = function (selection) {
      var anchorNode = Element$$1.fromDom(selection.anchorNode);
      var focusNode = Element$$1.fromDom(selection.focusNode);
      return after$3(anchorNode, selection.anchorOffset, focusNode, selection.focusOffset) ? Option.some(range$2(Element$$1.fromDom(selection.anchorNode), selection.anchorOffset, Element$$1.fromDom(selection.focusNode), selection.focusOffset)) : readRange(selection);
    };
    var getExact = function (win) {
      return Option.from(win.getSelection()).filter(function (sel) {
        return sel.rangeCount > 0;
      }).bind(doGetExact);
    };
    var get$e = function (win) {
      return getExact(win).map(function (range) {
        return exact(range.start(), range.soffset(), range.finish(), range.foffset());
      });
    };
    var getFirstRect$1 = function (win, selection) {
      var rng = asLtrRange(win, selection);
      return getFirstRect(rng);
    };
    var clear$1 = function (win) {
      var selection = win.getSelection();
      selection.removeAllRanges();
    };

    var COLLAPSED_WIDTH = 2;
    var collapsedRect = function (rect) {
      return {
        left: rect.left,
        top: rect.top,
        right: rect.right,
        bottom: rect.bottom,
        width: constant(COLLAPSED_WIDTH),
        height: rect.height
      };
    };
    var toRect$1 = function (rawRect) {
      return {
        left: constant(rawRect.left),
        top: constant(rawRect.top),
        right: constant(rawRect.right),
        bottom: constant(rawRect.bottom),
        width: constant(rawRect.width),
        height: constant(rawRect.height)
      };
    };
    var getRectsFromRange = function (range$$1) {
      if (!range$$1.collapsed) {
        return map$1(range$$1.getClientRects(), toRect$1);
      } else {
        var start_1 = Element$$1.fromDom(range$$1.startContainer);
        return parent(start_1).bind(function (parent$$1) {
          var selection = exact(start_1, range$$1.startOffset, parent$$1, getEnd(parent$$1));
          var optRect = getFirstRect$1(range$$1.startContainer.ownerDocument.defaultView, selection);
          return optRect.map(collapsedRect).map(pure);
        }).getOr([]);
      }
    };
    var getRectangles = function (cWin) {
      var sel = cWin.getSelection();
      return sel !== undefined && sel.rangeCount > 0 ? getRectsFromRange(sel.getRangeAt(0)) : [];
    };
    var Rectangles = { getRectangles: getRectangles };

    var autocompleteHack = function () {
      return function (f) {
        setTimeout(function () {
          f();
        }, 0);
      };
    };
    var resume = function (cWin) {
      cWin.focus();
      var iBody = Element$$1.fromDom(cWin.document.body);
      var inInput = active().exists(function (elem) {
        return contains([
          'input',
          'textarea'
        ], name(elem));
      });
      var transaction = inInput ? autocompleteHack() : apply;
      transaction(function () {
        active().each(blur$$1);
        focus$2(iBody);
      });
    };
    var ResumeEditing = { resume: resume };

    var EXTRA_SPACING = 50;
    var data = 'data-' + Styles.resolve('last-outer-height');
    var setLastHeight = function (cBody, value) {
      set(cBody, data, value);
    };
    var getLastHeight = function (cBody) {
      return DataAttributes.safeParse(cBody, data);
    };
    var getBoundsFrom = function (rect) {
      return {
        top: constant(rect.top()),
        bottom: constant(rect.top() + rect.height())
      };
    };
    var getBounds$3 = function (cWin) {
      var rects = Rectangles.getRectangles(cWin);
      return rects.length > 0 ? Option.some(rects[0]).map(getBoundsFrom) : Option.none();
    };
    var findDelta = function (outerWindow, cBody) {
      var last = getLastHeight(cBody);
      var current = outerWindow.innerHeight;
      return last > current ? Option.some(last - current) : Option.none();
    };
    var calculate = function (cWin, bounds, delta) {
      var isOutside = bounds.top() > cWin.innerHeight || bounds.bottom() > cWin.innerHeight;
      return isOutside ? Math.min(delta, bounds.bottom() - cWin.innerHeight + EXTRA_SPACING) : 0;
    };
    var setup$1 = function (outerWindow, cWin) {
      var cBody = Element$$1.fromDom(cWin.document.body);
      var toEditing = function () {
        ResumeEditing.resume(cWin);
      };
      var onResize = bind$3(Element$$1.fromDom(outerWindow), 'resize', function () {
        findDelta(outerWindow, cBody).each(function (delta) {
          getBounds$3(cWin).each(function (bounds) {
            var cScrollBy = calculate(cWin, bounds, delta);
            if (cScrollBy !== 0) {
              cWin.scrollTo(cWin.pageXOffset, cWin.pageYOffset + cScrollBy);
            }
          });
        });
        setLastHeight(cBody, outerWindow.innerHeight);
      });
      setLastHeight(cBody, outerWindow.innerHeight);
      var destroy = function () {
        onResize.unbind();
      };
      return {
        toEditing: toEditing,
        destroy: destroy
      };
    };
    var AndroidSetup = { setup: setup$1 };

    var getBodyFromFrame = function (frame) {
      return Option.some(Element$$1.fromDom(frame.dom().contentWindow.document.body));
    };
    var getDocFromFrame = function (frame) {
      return Option.some(Element$$1.fromDom(frame.dom().contentWindow.document));
    };
    var getWinFromFrame = function (frame) {
      return Option.from(frame.dom().contentWindow);
    };
    var getSelectionFromFrame = function (frame) {
      var optWin = getWinFromFrame(frame);
      return optWin.bind(getExact);
    };
    var getFrame = function (editor) {
      return editor.getFrame();
    };
    var getOrDerive = function (name, f) {
      return function (editor) {
        var g = editor[name].getOrThunk(function () {
          var frame = getFrame(editor);
          return function () {
            return f(frame);
          };
        });
        return g();
      };
    };
    var getOrListen = function (editor, doc, name, type) {
      return editor[name].getOrThunk(function () {
        return function (handler) {
          return bind$3(doc, type, handler);
        };
      });
    };
    var toRect$2 = function (rect) {
      return {
        left: constant(rect.left),
        top: constant(rect.top),
        right: constant(rect.right),
        bottom: constant(rect.bottom),
        width: constant(rect.width),
        height: constant(rect.height)
      };
    };
    var getActiveApi = function (editor) {
      var frame = getFrame(editor);
      var tryFallbackBox = function (win) {
        var isCollapsed$$1 = function (sel) {
          return eq(sel.start(), sel.finish()) && sel.soffset() === sel.foffset();
        };
        var toStartRect = function (sel) {
          var rect = sel.start().dom().getBoundingClientRect();
          return rect.width > 0 || rect.height > 0 ? Option.some(rect).map(toRect$2) : Option.none();
        };
        return getExact(win).filter(isCollapsed$$1).bind(toStartRect);
      };
      return getBodyFromFrame(frame).bind(function (body) {
        return getDocFromFrame(frame).bind(function (doc) {
          return getWinFromFrame(frame).map(function (win) {
            var html = Element$$1.fromDom(doc.dom().documentElement);
            var getCursorBox = editor.getCursorBox.getOrThunk(function () {
              return function () {
                return get$e(win).bind(function (sel) {
                  return getFirstRect$1(win, sel).orThunk(function () {
                    return tryFallbackBox(win);
                  });
                });
              };
            });
            var setSelection = editor.setSelection.getOrThunk(function () {
              return function (start, soffset, finish, foffset) {
                setExact(win, start, soffset, finish, foffset);
              };
            });
            var clearSelection = editor.clearSelection.getOrThunk(function () {
              return function () {
                clear$1(win);
              };
            });
            return {
              body: constant(body),
              doc: constant(doc),
              win: constant(win),
              html: constant(html),
              getSelection: curry(getSelectionFromFrame, frame),
              setSelection: setSelection,
              clearSelection: clearSelection,
              frame: constant(frame),
              onKeyup: getOrListen(editor, doc, 'onKeyup', 'keyup'),
              onNodeChanged: getOrListen(editor, doc, 'onNodeChanged', 'selectionchange'),
              onDomChanged: editor.onDomChanged,
              onScrollToCursor: editor.onScrollToCursor,
              onScrollToElement: editor.onScrollToElement,
              onToReading: editor.onToReading,
              onToEditing: editor.onToEditing,
              onToolbarScrollStart: editor.onToolbarScrollStart,
              onTouchContent: editor.onTouchContent,
              onTapContent: editor.onTapContent,
              onTouchToolstrip: editor.onTouchToolstrip,
              getCursorBox: getCursorBox
            };
          });
        });
      });
    };
    var PlatformEditor = {
      getBody: getOrDerive('getBody', getBodyFromFrame),
      getDoc: getOrDerive('getDoc', getDocFromFrame),
      getWin: getOrDerive('getWin', getWinFromFrame),
      getSelection: getOrDerive('getSelection', getSelectionFromFrame),
      getFrame: getFrame,
      getActiveApi: getActiveApi
    };

    var attr = 'data-ephox-mobile-fullscreen-style';
    var siblingStyles = 'display:none!important;';
    var ancestorPosition = 'position:absolute!important;';
    var ancestorStyles = 'top:0!important;left:0!important;margin:0' + '!important;padding:0!important;width:100%!important;';
    var bgFallback = 'background-color:rgb(255,255,255)!important;';
    var isAndroid = PlatformDetection$1.detect().os.isAndroid();
    var matchColor = function (editorBody) {
      var color = get$4(editorBody, 'background-color');
      return color !== undefined && color !== '' ? 'background-color:' + color + '!important' : bgFallback;
    };
    var clobberStyles = function (container, editorBody) {
      var gatherSibilings = function (element) {
        var siblings = siblings$2(element, '*');
        return siblings;
      };
      var clobber = function (clobberStyle) {
        return function (element) {
          var styles = get$1(element, 'style');
          var backup = styles === undefined ? 'no-styles' : styles.trim();
          if (backup === clobberStyle) {
            return;
          } else {
            set(element, attr, backup);
            set(element, 'style', clobberStyle);
          }
        };
      };
      var ancestors = ancestors$1(container, '*');
      var siblings = bind(ancestors, gatherSibilings);
      var bgColor = matchColor(editorBody);
      each$1(siblings, clobber(siblingStyles));
      each$1(ancestors, clobber(ancestorPosition + ancestorStyles + bgColor));
      var containerStyles = isAndroid === true ? '' : ancestorPosition;
      clobber(containerStyles + ancestorStyles + bgColor)(container);
    };
    var restoreStyles = function () {
      var clobberedEls = all$3('[' + attr + ']');
      each$1(clobberedEls, function (element) {
        var restore = get$1(element, attr);
        if (restore !== 'no-styles') {
          set(element, 'style', restore);
        } else {
          remove$1(element, 'style');
        }
        remove$1(element, attr);
      });
    };
    var Thor = {
      clobberStyles: clobberStyles,
      restoreStyles: restoreStyles
    };

    var tag = function () {
      var head = first$2('head').getOrDie();
      var nu = function () {
        var meta = Element$$1.fromTag('meta');
        set(meta, 'name', 'viewport');
        append(head, meta);
        return meta;
      };
      var element = first$2('meta[name="viewport"]').getOrThunk(nu);
      var backup = get$1(element, 'content');
      var maximize = function () {
        set(element, 'content', 'width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0');
      };
      var restore = function () {
        if (backup !== undefined && backup !== null && backup.length > 0) {
          set(element, 'content', backup);
        } else {
          set(element, 'content', 'user-scalable=yes');
        }
      };
      return {
        maximize: maximize,
        restore: restore
      };
    };
    var MetaViewport = { tag: tag };

    var create$5 = function (platform, mask) {
      var meta = MetaViewport.tag();
      var androidApi = api$2();
      var androidEvents = api$2();
      var enter = function () {
        mask.hide();
        add$2(platform.container, Styles.resolve('fullscreen-maximized'));
        add$2(platform.container, Styles.resolve('android-maximized'));
        meta.maximize();
        add$2(platform.body, Styles.resolve('android-scroll-reload'));
        androidApi.set(AndroidSetup.setup(platform.win, PlatformEditor.getWin(platform.editor).getOrDie('no')));
        PlatformEditor.getActiveApi(platform.editor).each(function (editorApi) {
          Thor.clobberStyles(platform.container, editorApi.body());
          androidEvents.set(AndroidEvents.initEvents(editorApi, platform.toolstrip, platform.alloy));
        });
      };
      var exit = function () {
        meta.restore();
        mask.show();
        remove$4(platform.container, Styles.resolve('fullscreen-maximized'));
        remove$4(platform.container, Styles.resolve('android-maximized'));
        Thor.restoreStyles();
        remove$4(platform.body, Styles.resolve('android-scroll-reload'));
        androidEvents.clear();
        androidApi.clear();
      };
      return {
        enter: enter,
        exit: exit
      };
    };
    var AndroidMode = { create: create$5 };

    var first$4 = function (fn, rate) {
      var timer = null;
      var cancel = function () {
        if (timer !== null) {
          clearTimeout(timer);
          timer = null;
        }
      };
      var throttle = function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        if (timer === null) {
          timer = setTimeout(function () {
            fn.apply(null, args);
            timer = null;
          }, rate);
        }
      };
      return {
        cancel: cancel,
        throttle: throttle
      };
    };
    var last$3 = function (fn, rate) {
      var timer = null;
      var cancel = function () {
        if (timer !== null) {
          clearTimeout(timer);
          timer = null;
        }
      };
      var throttle = function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        if (timer !== null)
          clearTimeout(timer);
        timer = setTimeout(function () {
          fn.apply(null, args);
          timer = null;
        }, rate);
      };
      return {
        cancel: cancel,
        throttle: throttle
      };
    };

    var sketch$a = function (onView, translate) {
      var memIcon = record(Container.sketch({
        dom: dom$2('<div aria-hidden="true" class="${prefix}-mask-tap-icon"></div>'),
        containerBehaviours: derive$1([Toggling.config({
            toggleClass: Styles.resolve('mask-tap-icon-selected'),
            toggleOnExecute: false
          })])
      }));
      var onViewThrottle = first$4(onView, 200);
      return Container.sketch({
        dom: dom$2('<div class="${prefix}-disabled-mask"></div>'),
        components: [Container.sketch({
            dom: dom$2('<div class="${prefix}-content-container"></div>'),
            components: [Button.sketch({
                dom: dom$2('<div class="${prefix}-content-tap-section"></div>'),
                components: [memIcon.asSpec()],
                action: function (button) {
                  onViewThrottle.throttle();
                },
                buttonBehaviours: derive$1([Toggling.config({ toggleClass: Styles.resolve('mask-tap-icon-selected') })])
              })]
          })]
      });
    };
    var TapToEditMask = { sketch: sketch$a };

    var MobileSchema = objOf([
      strictObjOf('editor', [
        strict$1('getFrame'),
        option('getBody'),
        option('getDoc'),
        option('getWin'),
        option('getSelection'),
        option('setSelection'),
        option('clearSelection'),
        option('cursorSaver'),
        option('onKeyup'),
        option('onNodeChanged'),
        option('getCursorBox'),
        strict$1('onDomChanged'),
        defaulted$1('onTouchContent', noop),
        defaulted$1('onTapContent', noop),
        defaulted$1('onTouchToolstrip', noop),
        defaulted$1('onScrollToCursor', constant({ unbind: noop })),
        defaulted$1('onScrollToElement', constant({ unbind: noop })),
        defaulted$1('onToEditing', constant({ unbind: noop })),
        defaulted$1('onToReading', constant({ unbind: noop })),
        defaulted$1('onToolbarScrollStart', identity)
      ]),
      strict$1('socket'),
      strict$1('toolstrip'),
      strict$1('dropup'),
      strict$1('toolbar'),
      strict$1('container'),
      strict$1('alloy'),
      state$1('win', function (spec) {
        return owner(spec.socket).dom().defaultView;
      }),
      state$1('body', function (spec) {
        return Element$$1.fromDom(spec.socket.dom().ownerDocument.body);
      }),
      defaulted$1('translate', identity),
      defaulted$1('setReadOnly', noop),
      defaulted$1('readOnlyOnInit', constant(true))
    ]);

    var produce = function (raw) {
      var mobile = asRawOrDie('Getting AndroidWebapp schema', MobileSchema, raw);
      set$3(mobile.toolstrip, 'width', '100%');
      var onTap = function () {
        mobile.setReadOnly(mobile.readOnlyOnInit());
        mode.enter();
      };
      var mask = build$1(TapToEditMask.sketch(onTap, mobile.translate));
      mobile.alloy.add(mask);
      var maskApi = {
        show: function () {
          mobile.alloy.add(mask);
        },
        hide: function () {
          mobile.alloy.remove(mask);
        }
      };
      append(mobile.container, mask.element());
      var mode = AndroidMode.create(mobile, maskApi);
      return {
        setReadOnly: mobile.setReadOnly,
        refreshStructure: noop,
        enter: mode.enter,
        exit: mode.exit,
        destroy: noop
      };
    };
    var AndroidWebapp = { produce: produce };

    var schema$e = constant([
      defaulted$1('shell', true),
      field$1('toolbarBehaviours', [Replacing])
    ]);
    var enhanceGroups = function (detail) {
      return { behaviours: derive$1([Replacing.config({})]) };
    };
    var parts$2 = constant([optional({
        name: 'groups',
        overrides: enhanceGroups
      })]);

    var factory$4 = function (detail, components$$1, spec, _externals) {
      var setGroups = function (toolbar$$1, groups) {
        getGroupContainer(toolbar$$1).fold(function () {
          console.error('Toolbar was defined to not be a shell, but no groups container was specified in components');
          throw new Error('Toolbar was defined to not be a shell, but no groups container was specified in components');
        }, function (container) {
          Replacing.set(container, groups);
        });
      };
      var getGroupContainer = function (component) {
        return detail.shell ? Option.some(component) : getPart(component, detail, 'groups');
      };
      var extra = detail.shell ? {
        behaviours: [Replacing.config({})],
        components: []
      } : {
        behaviours: [],
        components: components$$1
      };
      return {
        uid: detail.uid,
        dom: detail.dom,
        components: extra.components,
        behaviours: augment(detail.toolbarBehaviours, extra.behaviours),
        apis: { setGroups: setGroups },
        domModification: { attributes: { role: 'group' } }
      };
    };
    var Toolbar = composite$1({
      name: 'Toolbar',
      configFields: schema$e(),
      partFields: parts$2(),
      factory: factory$4,
      apis: {
        setGroups: function (apis, toolbar$$1, groups) {
          apis.setGroups(toolbar$$1, groups);
        }
      }
    });

    var schema$f = constant([
      strict$1('items'),
      markers(['itemSelector']),
      field$1('tgroupBehaviours', [Keying])
    ]);
    var parts$3 = constant([group({
        name: 'items',
        unit: 'item'
      })]);

    var factory$5 = function (detail, components, spec, _externals) {
      return {
        'uid': detail.uid,
        'dom': detail.dom,
        'components': components,
        'behaviours': augment(detail.tgroupBehaviours, [Keying.config({
            mode: 'flow',
            selector: detail.markers.itemSelector
          })]),
        domModification: { attributes: { role: 'toolbar' } }
      };
    };
    var ToolbarGroup = composite$1({
      name: 'ToolbarGroup',
      configFields: schema$f(),
      partFields: parts$3(),
      factory: factory$5
    });

    var dataHorizontal = 'data-' + Styles.resolve('horizontal-scroll');
    var canScrollVertically = function (container) {
      container.dom().scrollTop = 1;
      var result = container.dom().scrollTop !== 0;
      container.dom().scrollTop = 0;
      return result;
    };
    var canScrollHorizontally = function (container) {
      container.dom().scrollLeft = 1;
      var result = container.dom().scrollLeft !== 0;
      container.dom().scrollLeft = 0;
      return result;
    };
    var hasVerticalScroll = function (container) {
      return container.dom().scrollTop > 0 || canScrollVertically(container);
    };
    var hasHorizontalScroll = function (container) {
      return container.dom().scrollLeft > 0 || canScrollHorizontally(container);
    };
    var markAsHorizontal = function (container) {
      set(container, dataHorizontal, 'true');
    };
    var hasScroll = function (container) {
      return get$1(container, dataHorizontal) === 'true' ? hasHorizontalScroll(container) : hasVerticalScroll(container);
    };
    var exclusive = function (scope, selector) {
      return bind$3(scope, 'touchmove', function (event) {
        closest$3(event.target(), selector).filter(hasScroll).fold(function () {
          event.raw().preventDefault();
        }, noop);
      });
    };
    var Scrollables = {
      exclusive: exclusive,
      markAsHorizontal: markAsHorizontal
    };

    function ScrollingToolbar () {
      var makeGroup = function (gSpec) {
        var scrollClass = gSpec.scrollable === true ? '${prefix}-toolbar-scrollable-group' : '';
        return {
          dom: dom$2('<div aria-label="' + gSpec.label + '" class="${prefix}-toolbar-group ' + scrollClass + '"></div>'),
          tgroupBehaviours: derive$1([config('adhoc-scrollable-toolbar', gSpec.scrollable === true ? [runOnInit(function (component, simulatedEvent) {
                set$3(component.element(), 'overflow-x', 'auto');
                Scrollables.markAsHorizontal(component.element());
                Scrollable.register(component.element());
              })] : [])]),
          components: [Container.sketch({ components: [ToolbarGroup.parts().items({})] })],
          markers: { itemSelector: '.' + Styles.resolve('toolbar-group-item') },
          items: gSpec.items
        };
      };
      var toolbar = build$1(Toolbar.sketch({
        dom: dom$2('<div class="${prefix}-toolbar"></div>'),
        components: [Toolbar.parts().groups({})],
        toolbarBehaviours: derive$1([
          Toggling.config({
            toggleClass: Styles.resolve('context-toolbar'),
            toggleOnExecute: false,
            aria: { mode: 'none' }
          }),
          Keying.config({ mode: 'cyclic' })
        ]),
        shell: true
      }));
      var wrapper = build$1(Container.sketch({
        dom: { classes: [Styles.resolve('toolstrip')] },
        components: [premade$1(toolbar)],
        containerBehaviours: derive$1([Toggling.config({
            toggleClass: Styles.resolve('android-selection-context-toolbar'),
            toggleOnExecute: false
          })])
      }));
      var resetGroups = function () {
        Toolbar.setGroups(toolbar, initGroups.get());
        Toggling.off(toolbar);
      };
      var initGroups = Cell([]);
      var setGroups = function (gs) {
        initGroups.set(gs);
        resetGroups();
      };
      var createGroups = function (gs) {
        return map$1(gs, compose(ToolbarGroup.sketch, makeGroup));
      };
      var refresh = function () {
      };
      var setContextToolbar = function (gs) {
        Toggling.on(toolbar);
        Toolbar.setGroups(toolbar, gs);
      };
      var restoreToolbar = function () {
        if (Toggling.isOn(toolbar)) {
          resetGroups();
        }
      };
      var focus = function () {
        Keying.focusIn(toolbar);
      };
      return {
        wrapper: constant(wrapper),
        toolbar: constant(toolbar),
        createGroups: createGroups,
        setGroups: setGroups,
        setContextToolbar: setContextToolbar,
        restoreToolbar: restoreToolbar,
        refresh: refresh,
        focus: focus
      };
    }

    var makeEditSwitch = function (webapp) {
      return build$1(Button.sketch({
        dom: dom$2('<div class="${prefix}-mask-edit-icon ${prefix}-icon"></div>'),
        action: function () {
          webapp.run(function (w) {
            w.setReadOnly(false);
          });
        }
      }));
    };
    var makeSocket = function () {
      return build$1(Container.sketch({
        dom: dom$2('<div class="${prefix}-editor-socket"></div>'),
        components: [],
        containerBehaviours: derive$1([Replacing.config({})])
      }));
    };
    var showEdit = function (socket, switchToEdit) {
      Replacing.append(socket, premade$1(switchToEdit));
    };
    var hideEdit = function (socket, switchToEdit) {
      Replacing.remove(socket, switchToEdit);
    };
    var updateMode = function (socket, switchToEdit, readOnly, root) {
      var swap = readOnly === true ? Swapping.toAlpha : Swapping.toOmega;
      swap(root);
      var f = readOnly ? showEdit : hideEdit;
      f(socket, switchToEdit);
    };
    var CommonRealm = {
      makeEditSwitch: makeEditSwitch,
      makeSocket: makeSocket,
      updateMode: updateMode
    };

    var getAnimationRoot = function (component, slideConfig) {
      return slideConfig.getAnimationRoot.fold(function () {
        return component.element();
      }, function (get) {
        return get(component);
      });
    };

    var getDimensionProperty = function (slideConfig) {
      return slideConfig.dimension.property;
    };
    var getDimension = function (slideConfig, elem) {
      return slideConfig.dimension.getDimension(elem);
    };
    var disableTransitions = function (component, slideConfig) {
      var root = getAnimationRoot(component, slideConfig);
      remove$6(root, [
        slideConfig.shrinkingClass,
        slideConfig.growingClass
      ]);
    };
    var setShrunk = function (component, slideConfig) {
      remove$4(component.element(), slideConfig.openClass);
      add$2(component.element(), slideConfig.closedClass);
      set$3(component.element(), getDimensionProperty(slideConfig), '0px');
      reflow(component.element());
    };
    var setGrown = function (component, slideConfig) {
      remove$4(component.element(), slideConfig.closedClass);
      add$2(component.element(), slideConfig.openClass);
      remove$5(component.element(), getDimensionProperty(slideConfig));
    };
    var doImmediateShrink = function (component, slideConfig, slideState, _calculatedSize) {
      slideState.setCollapsed();
      set$3(component.element(), getDimensionProperty(slideConfig), getDimension(slideConfig, component.element()));
      reflow(component.element());
      disableTransitions(component, slideConfig);
      setShrunk(component, slideConfig);
      slideConfig.onStartShrink(component);
      slideConfig.onShrunk(component);
    };
    var doStartShrink = function (component, slideConfig, slideState, calculatedSize) {
      var size = calculatedSize.getOrThunk(function () {
        return getDimension(slideConfig, component.element());
      });
      slideState.setCollapsed();
      set$3(component.element(), getDimensionProperty(slideConfig), size);
      reflow(component.element());
      var root = getAnimationRoot(component, slideConfig);
      remove$4(root, slideConfig.growingClass);
      add$2(root, slideConfig.shrinkingClass);
      setShrunk(component, slideConfig);
      slideConfig.onStartShrink(component);
    };
    var doStartSmartShrink = function (component, slideConfig, slideState) {
      var size = getDimension(slideConfig, component.element());
      var shrinker = size === '0px' ? doImmediateShrink : doStartShrink;
      shrinker(component, slideConfig, slideState, Option.some(size));
    };
    var doStartGrow = function (component, slideConfig, slideState) {
      var root = getAnimationRoot(component, slideConfig);
      var wasShrinking = has$2(root, slideConfig.shrinkingClass);
      var beforeSize = getDimension(slideConfig, component.element());
      setGrown(component, slideConfig);
      var fullSize = getDimension(slideConfig, component.element());
      var startPartialGrow = function () {
        set$3(component.element(), getDimensionProperty(slideConfig), beforeSize);
        reflow(component.element());
      };
      var startCompleteGrow = function () {
        setShrunk(component, slideConfig);
      };
      var setStartSize = wasShrinking ? startPartialGrow : startCompleteGrow;
      setStartSize();
      remove$4(root, slideConfig.shrinkingClass);
      add$2(root, slideConfig.growingClass);
      setGrown(component, slideConfig);
      set$3(component.element(), getDimensionProperty(slideConfig), fullSize);
      slideState.setExpanded();
      slideConfig.onStartGrow(component);
    };
    var grow = function (component, slideConfig, slideState) {
      if (!slideState.isExpanded()) {
        doStartGrow(component, slideConfig, slideState);
      }
    };
    var shrink = function (component, slideConfig, slideState) {
      if (slideState.isExpanded()) {
        doStartSmartShrink(component, slideConfig, slideState);
      }
    };
    var immediateShrink = function (component, slideConfig, slideState) {
      if (slideState.isExpanded()) {
        doImmediateShrink(component, slideConfig, slideState, Option.none());
      }
    };
    var hasGrown = function (component, slideConfig, slideState) {
      return slideState.isExpanded();
    };
    var hasShrunk = function (component, slideConfig, slideState) {
      return slideState.isCollapsed();
    };
    var isGrowing = function (component, slideConfig, slideState) {
      var root = getAnimationRoot(component, slideConfig);
      return has$2(root, slideConfig.growingClass) === true;
    };
    var isShrinking = function (component, slideConfig, slideState) {
      var root = getAnimationRoot(component, slideConfig);
      return has$2(root, slideConfig.shrinkingClass) === true;
    };
    var isTransitioning = function (component, slideConfig, slideState) {
      return isGrowing(component, slideConfig, slideState) === true || isShrinking(component, slideConfig, slideState) === true;
    };
    var toggleGrow = function (component, slideConfig, slideState) {
      var f = slideState.isExpanded() ? doStartSmartShrink : doStartGrow;
      f(component, slideConfig, slideState);
    };

    var SlidingApis = /*#__PURE__*/Object.freeze({
        grow: grow,
        shrink: shrink,
        immediateShrink: immediateShrink,
        hasGrown: hasGrown,
        hasShrunk: hasShrunk,
        isGrowing: isGrowing,
        isShrinking: isShrinking,
        isTransitioning: isTransitioning,
        toggleGrow: toggleGrow,
        disableTransitions: disableTransitions
    });

    var exhibit$5 = function (base, slideConfig) {
      var expanded = slideConfig.expanded;
      return expanded ? nu$5({
        classes: [slideConfig.openClass],
        styles: {}
      }) : nu$5({
        classes: [slideConfig.closedClass],
        styles: wrap$2(slideConfig.dimension.property, '0px')
      });
    };
    var events$a = function (slideConfig, slideState) {
      return derive([runOnSource(transitionend(), function (component, simulatedEvent) {
          var raw = simulatedEvent.event().raw();
          if (raw.propertyName === slideConfig.dimension.property) {
            disableTransitions(component, slideConfig);
            if (slideState.isExpanded()) {
              remove$5(component.element(), slideConfig.dimension.property);
            }
            var notify = slideState.isExpanded() ? slideConfig.onGrown : slideConfig.onShrunk;
            notify(component);
          }
        })]);
    };

    var ActiveSliding = /*#__PURE__*/Object.freeze({
        exhibit: exhibit$5,
        events: events$a
    });

    var SlidingSchema = [
      strict$1('closedClass'),
      strict$1('openClass'),
      strict$1('shrinkingClass'),
      strict$1('growingClass'),
      option('getAnimationRoot'),
      onHandler('onShrunk'),
      onHandler('onStartShrink'),
      onHandler('onGrown'),
      onHandler('onStartGrow'),
      defaulted$1('expanded', false),
      strictOf('dimension', choose$1('property', {
        width: [
          output$1('property', 'width'),
          output$1('getDimension', function (elem) {
            return get$7(elem) + 'px';
          })
        ],
        height: [
          output$1('property', 'height'),
          output$1('getDimension', function (elem) {
            return get$5(elem) + 'px';
          })
        ]
      }))
    ];

    var init$5 = function (spec) {
      var state = Cell(spec.expanded);
      var readState = function () {
        return 'expanded: ' + state.get();
      };
      return nu$6({
        isExpanded: function () {
          return state.get() === true;
        },
        isCollapsed: function () {
          return state.get() === false;
        },
        setCollapsed: curry(state.set, false),
        setExpanded: curry(state.set, true),
        readState: readState
      });
    };

    var SlidingState = /*#__PURE__*/Object.freeze({
        init: init$5
    });

    var Sliding = create$1({
      fields: SlidingSchema,
      name: 'sliding',
      active: ActiveSliding,
      apis: SlidingApis,
      state: SlidingState
    });

    var build$2 = function (refresh, scrollIntoView) {
      var dropup = build$1(Container.sketch({
        dom: {
          tag: 'div',
          classes: [Styles.resolve('dropup')]
        },
        components: [],
        containerBehaviours: derive$1([
          Replacing.config({}),
          Sliding.config({
            closedClass: Styles.resolve('dropup-closed'),
            openClass: Styles.resolve('dropup-open'),
            shrinkingClass: Styles.resolve('dropup-shrinking'),
            growingClass: Styles.resolve('dropup-growing'),
            dimension: { property: 'height' },
            onShrunk: function (component) {
              refresh();
              scrollIntoView();
              Replacing.set(component, []);
            },
            onGrown: function (component) {
              refresh();
              scrollIntoView();
            }
          }),
          Receivers.orientation(function (component, data) {
            disappear(noop);
          })
        ])
      }));
      var appear = function (menu, update, component) {
        if (Sliding.hasShrunk(dropup) === true && Sliding.isTransitioning(dropup) === false) {
          window.requestAnimationFrame(function () {
            update(component);
            Replacing.set(dropup, [menu()]);
            Sliding.grow(dropup);
          });
        }
      };
      var disappear = function (onReadyToShrink) {
        window.requestAnimationFrame(function () {
          onReadyToShrink();
          Sliding.shrink(dropup);
        });
      };
      return {
        appear: appear,
        disappear: disappear,
        component: constant(dropup),
        element: dropup.element
      };
    };

    var closest$4 = function (scope, selector, isRoot) {
      return closest$3(scope, selector, isRoot).isSome();
    };

    var isDangerous = function (event$$1) {
      var keyEv = event$$1.raw();
      return keyEv.which === BACKSPACE()[0] && !contains([
        'input',
        'textarea'
      ], name(event$$1.target())) && !closest$4(event$$1.target(), '[contenteditable="true"]');
    };
    var isFirefox = PlatformDetection$1.detect().browser.isFirefox();
    var settingsSchema = objOfOnly([
      strictFunction('triggerEvent'),
      defaulted$1('stopBackspace', true)
    ]);
    var bindFocus = function (container, handler) {
      if (isFirefox) {
        return capture$1(container, 'focus', handler);
      } else {
        return bind$3(container, 'focusin', handler);
      }
    };
    var bindBlur = function (container, handler) {
      if (isFirefox) {
        return capture$1(container, 'blur', handler);
      } else {
        return bind$3(container, 'focusout', handler);
      }
    };
    var setup$2 = function (container, rawSettings) {
      var settings = asRawOrDie('Getting GUI events settings', settingsSchema, rawSettings);
      var pointerEvents = PlatformDetection$1.detect().deviceType.isTouch() ? [
        'touchstart',
        'touchmove',
        'touchend',
        'gesturestart'
      ] : [
        'mousedown',
        'mouseup',
        'mouseover',
        'mousemove',
        'mouseout',
        'click'
      ];
      var tapEvent = monitor(settings);
      var simpleEvents = map$1(pointerEvents.concat([
        'selectstart',
        'input',
        'contextmenu',
        'change',
        'transitionend',
        'drag',
        'dragstart',
        'dragend',
        'dragenter',
        'dragleave',
        'dragover',
        'drop',
        'keyup'
      ]), function (type$$1) {
        return bind$3(container, type$$1, function (event$$1) {
          tapEvent.fireIfReady(event$$1, type$$1).each(function (tapStopped) {
            if (tapStopped) {
              event$$1.kill();
            }
          });
          var stopped = settings.triggerEvent(type$$1, event$$1);
          if (stopped) {
            event$$1.kill();
          }
        });
      });
      var pasteTimeout = Cell(Option.none());
      var onPaste = bind$3(container, 'paste', function (event$$1) {
        tapEvent.fireIfReady(event$$1, 'paste').each(function (tapStopped) {
          if (tapStopped) {
            event$$1.kill();
          }
        });
        var stopped = settings.triggerEvent('paste', event$$1);
        if (stopped) {
          event$$1.kill();
        }
        pasteTimeout.set(Option.some(setTimeout(function () {
          settings.triggerEvent(postPaste(), event$$1);
        }, 0)));
      });
      var onKeydown = bind$3(container, 'keydown', function (event$$1) {
        var stopped = settings.triggerEvent('keydown', event$$1);
        if (stopped) {
          event$$1.kill();
        } else if (settings.stopBackspace === true && isDangerous(event$$1)) {
          event$$1.prevent();
        }
      });
      var onFocusIn = bindFocus(container, function (event$$1) {
        var stopped = settings.triggerEvent('focusin', event$$1);
        if (stopped) {
          event$$1.kill();
        }
      });
      var focusoutTimeout = Cell(Option.none());
      var onFocusOut = bindBlur(container, function (event$$1) {
        var stopped = settings.triggerEvent('focusout', event$$1);
        if (stopped) {
          event$$1.kill();
        }
        focusoutTimeout.set(Option.some(setTimeout(function () {
          settings.triggerEvent(postBlur(), event$$1);
        }, 0)));
      });
      var unbind = function () {
        each$1(simpleEvents, function (e) {
          e.unbind();
        });
        onKeydown.unbind();
        onFocusIn.unbind();
        onFocusOut.unbind();
        onPaste.unbind();
        pasteTimeout.get().each(clearTimeout);
        focusoutTimeout.get().each(clearTimeout);
      };
      return { unbind: unbind };
    };

    var derive$2 = function (rawEvent, rawTarget) {
      var source = readOptFrom$1(rawEvent, 'target').map(function (getTarget) {
        return getTarget();
      }).getOr(rawTarget);
      return Cell(source);
    };

    var fromSource = function (event, source) {
      var stopper = Cell(false);
      var cutter = Cell(false);
      var stop = function () {
        stopper.set(true);
      };
      var cut = function () {
        cutter.set(true);
      };
      return {
        stop: stop,
        cut: cut,
        isStopped: stopper.get,
        isCut: cutter.get,
        event: constant(event),
        setSource: source.set,
        getSource: source.get
      };
    };
    var fromExternal = function (event) {
      var stopper = Cell(false);
      var stop = function () {
        stopper.set(true);
      };
      return {
        stop: stop,
        cut: noop,
        isStopped: stopper.get,
        isCut: constant(false),
        event: constant(event),
        setSource: die('Cannot set source of a broadcasted event'),
        getSource: die('Cannot get source of a broadcasted event')
      };
    };

    var adt$6 = Adt.generate([
      { stopped: [] },
      { resume: ['element'] },
      { complete: [] }
    ]);
    var doTriggerHandler = function (lookup, eventType, rawEvent, target, source, logger) {
      var handler = lookup(eventType, target);
      var simulatedEvent = fromSource(rawEvent, source);
      return handler.fold(function () {
        logger.logEventNoHandlers(eventType, target);
        return adt$6.complete();
      }, function (handlerInfo) {
        var descHandler = handlerInfo.descHandler();
        var eventHandler = getCurried(descHandler);
        eventHandler(simulatedEvent);
        if (simulatedEvent.isStopped()) {
          logger.logEventStopped(eventType, handlerInfo.element(), descHandler.purpose());
          return adt$6.stopped();
        } else if (simulatedEvent.isCut()) {
          logger.logEventCut(eventType, handlerInfo.element(), descHandler.purpose());
          return adt$6.complete();
        } else {
          return parent(handlerInfo.element()).fold(function () {
            logger.logNoParent(eventType, handlerInfo.element(), descHandler.purpose());
            return adt$6.complete();
          }, function (parent$$1) {
            logger.logEventResponse(eventType, handlerInfo.element(), descHandler.purpose());
            return adt$6.resume(parent$$1);
          });
        }
      });
    };
    var doTriggerOnUntilStopped = function (lookup, eventType, rawEvent, rawTarget, source, logger) {
      return doTriggerHandler(lookup, eventType, rawEvent, rawTarget, source, logger).fold(function () {
        return true;
      }, function (parent$$1) {
        return doTriggerOnUntilStopped(lookup, eventType, rawEvent, parent$$1, source, logger);
      }, function () {
        return false;
      });
    };
    var triggerHandler = function (lookup, eventType, rawEvent, target, logger) {
      var source = derive$2(rawEvent, target);
      return doTriggerHandler(lookup, eventType, rawEvent, target, source, logger);
    };
    var broadcast = function (listeners, rawEvent, logger) {
      var simulatedEvent = fromExternal(rawEvent);
      each$1(listeners, function (listener) {
        var descHandler = listener.descHandler();
        var handler = getCurried(descHandler);
        handler(simulatedEvent);
      });
      return simulatedEvent.isStopped();
    };
    var triggerUntilStopped = function (lookup, eventType, rawEvent, logger) {
      var rawTarget = rawEvent.target();
      return triggerOnUntilStopped(lookup, eventType, rawEvent, rawTarget, logger);
    };
    var triggerOnUntilStopped = function (lookup, eventType, rawEvent, rawTarget, logger) {
      var source = derive$2(rawEvent, rawTarget);
      return doTriggerOnUntilStopped(lookup, eventType, rawEvent, rawTarget, source, logger);
    };

    var eventHandler = Immutable('element', 'descHandler');
    var broadcastHandler = function (id, handler) {
      return {
        id: constant(id),
        descHandler: constant(handler)
      };
    };
    function EventRegistry () {
      var registry = {};
      var registerId = function (extraArgs, id, events) {
        each(events, function (v, k) {
          var handlers = registry[k] !== undefined ? registry[k] : {};
          handlers[id] = curryArgs(v, extraArgs);
          registry[k] = handlers;
        });
      };
      var findHandler = function (handlers, elem) {
        return read$2(elem).fold(function () {
          return Option.none();
        }, function (id) {
          var reader = readOpt$1(id);
          return handlers.bind(reader).map(function (descHandler) {
            return eventHandler(elem, descHandler);
          });
        });
      };
      var filterByType = function (type) {
        return readOptFrom$1(registry, type).map(function (handlers) {
          return mapToArray(handlers, function (f, id) {
            return broadcastHandler(id, f);
          });
        }).getOr([]);
      };
      var find$$1 = function (isAboveRoot, type, target) {
        var readType = readOpt$1(type);
        var handlers = readType(registry);
        return closest$1(target, function (elem) {
          return findHandler(handlers, elem);
        }, isAboveRoot);
      };
      var unregisterId = function (id) {
        each(registry, function (handlersById, eventName) {
          if (handlersById.hasOwnProperty(id)) {
            delete handlersById[id];
          }
        });
      };
      return {
        registerId: registerId,
        unregisterId: unregisterId,
        filterByType: filterByType,
        find: find$$1
      };
    }

    function Registry () {
      var events = EventRegistry();
      var components = {};
      var readOrTag = function (component) {
        var elem = component.element();
        return read$2(elem).fold(function () {
          return write('uid-', component.element());
        }, function (uid) {
          return uid;
        });
      };
      var failOnDuplicate = function (component, tagId) {
        var conflict = components[tagId];
        if (conflict === component) {
          unregister(component);
        } else {
          throw new Error('The tagId "' + tagId + '" is already used by: ' + element(conflict.element()) + '\nCannot use it for: ' + element(component.element()) + '\n' + 'The conflicting element is' + (inBody(conflict.element()) ? ' ' : ' not ') + 'already in the DOM');
        }
      };
      var register = function (component) {
        var tagId = readOrTag(component);
        if (hasKey$1(components, tagId)) {
          failOnDuplicate(component, tagId);
        }
        var extraArgs = [component];
        events.registerId(extraArgs, tagId, component.events());
        components[tagId] = component;
      };
      var unregister = function (component) {
        read$2(component.element()).each(function (tagId) {
          components[tagId] = undefined;
          events.unregisterId(tagId);
        });
      };
      var filter = function (type) {
        return events.filterByType(type);
      };
      var find = function (isAboveRoot, type, target) {
        return events.find(isAboveRoot, type, target);
      };
      var getById = function (id) {
        return readOpt$1(id)(components);
      };
      return {
        find: find,
        filter: filter,
        register: register,
        unregister: unregister,
        getById: getById
      };
    }

    var takeover = function (root) {
      var isAboveRoot = function (el) {
        return parent(root.element()).fold(function () {
          return true;
        }, function (parent$$1) {
          return eq(el, parent$$1);
        });
      };
      var registry = Registry();
      var lookup = function (eventName, target) {
        return registry.find(isAboveRoot, eventName, target);
      };
      var domEvents = setup$2(root.element(), {
        triggerEvent: function (eventName, event) {
          return monitorEvent(eventName, event.target(), function (logger) {
            return triggerUntilStopped(lookup, eventName, event, logger);
          });
        }
      });
      var systemApi = {
        debugInfo: constant('real'),
        triggerEvent: function (eventName, target, data) {
          monitorEvent(eventName, target, function (logger) {
            triggerOnUntilStopped(lookup, eventName, data, target, logger);
          });
        },
        triggerFocus: function (target, originator) {
          read$2(target).fold(function () {
            focus$2(target);
          }, function (_alloyId) {
            monitorEvent(focus$1(), target, function (logger) {
              triggerHandler(lookup, focus$1(), {
                originator: constant(originator),
                kill: noop,
                prevent: noop,
                target: constant(target)
              }, target, logger);
            });
          });
        },
        triggerEscape: function (comp, simulatedEvent) {
          systemApi.triggerEvent('keydown', comp.element(), simulatedEvent.event());
        },
        getByUid: function (uid) {
          return getByUid(uid);
        },
        getByDom: function (elem) {
          return getByDom(elem);
        },
        build: build$1,
        addToGui: function (c) {
          add(c);
        },
        removeFromGui: function (c) {
          remove$$1(c);
        },
        addToWorld: function (c) {
          addToWorld(c);
        },
        removeFromWorld: function (c) {
          removeFromWorld(c);
        },
        broadcast: function (message) {
          broadcast$$1(message);
        },
        broadcastOn: function (channels, message) {
          broadcastOn(channels, message);
        },
        broadcastEvent: function (eventName, event) {
          broadcastEvent(eventName, event);
        },
        isConnected: constant(true)
      };
      var addToWorld = function (component) {
        component.connect(systemApi);
        if (!isText(component.element())) {
          registry.register(component);
          each$1(component.components(), addToWorld);
          systemApi.triggerEvent(systemInit(), component.element(), { target: constant(component.element()) });
        }
      };
      var removeFromWorld = function (component) {
        if (!isText(component.element())) {
          each$1(component.components(), removeFromWorld);
          registry.unregister(component);
        }
        component.disconnect();
      };
      var add = function (component) {
        attach(root, component);
      };
      var remove$$1 = function (component) {
        detach(component);
      };
      var destroy = function () {
        domEvents.unbind();
        remove(root.element());
      };
      var broadcastData = function (data) {
        var receivers = registry.filter(receive());
        each$1(receivers, function (receiver) {
          var descHandler = receiver.descHandler();
          var handler = getCurried(descHandler);
          handler(data);
        });
      };
      var broadcast$$1 = function (message) {
        broadcastData({
          universal: constant(true),
          data: constant(message)
        });
      };
      var broadcastOn = function (channels, message) {
        broadcastData({
          universal: constant(false),
          channels: constant(channels),
          data: constant(message)
        });
      };
      var broadcastEvent = function (eventName, event) {
        var listeners = registry.filter(eventName);
        return broadcast(listeners, event);
      };
      var getByUid = function (uid) {
        return registry.getById(uid).fold(function () {
          return Result.error(new Error('Could not find component with uid: "' + uid + '" in system.'));
        }, Result.value);
      };
      var getByDom = function (elem) {
        var uid = read$2(elem).getOr('not found');
        return getByUid(uid);
      };
      addToWorld(root);
      return {
        root: constant(root),
        element: root.element,
        destroy: destroy,
        add: add,
        remove: remove$$1,
        getByUid: getByUid,
        getByDom: getByDom,
        addToWorld: addToWorld,
        removeFromWorld: removeFromWorld,
        broadcast: broadcast$$1,
        broadcastOn: broadcastOn,
        broadcastEvent: broadcastEvent
      };
    };

    var READ_ONLY_MODE_CLASS = constant(Styles.resolve('readonly-mode'));
    var EDIT_MODE_CLASS = constant(Styles.resolve('edit-mode'));
    function OuterContainer (spec) {
      var root = build$1(Container.sketch({
        dom: { classes: [Styles.resolve('outer-container')].concat(spec.classes) },
        containerBehaviours: derive$1([Swapping.config({
            alpha: READ_ONLY_MODE_CLASS(),
            omega: EDIT_MODE_CLASS()
          })])
      }));
      return takeover(root);
    }

    function AndroidRealm (scrollIntoView) {
      var alloy = OuterContainer({ classes: [Styles.resolve('android-container')] });
      var toolbar = ScrollingToolbar();
      var webapp = api$2();
      var switchToEdit = CommonRealm.makeEditSwitch(webapp);
      var socket = CommonRealm.makeSocket();
      var dropup = build$2(noop, scrollIntoView);
      alloy.add(toolbar.wrapper());
      alloy.add(socket);
      alloy.add(dropup.component());
      var setToolbarGroups = function (rawGroups) {
        var groups = toolbar.createGroups(rawGroups);
        toolbar.setGroups(groups);
      };
      var setContextToolbar = function (rawGroups) {
        var groups = toolbar.createGroups(rawGroups);
        toolbar.setContextToolbar(groups);
      };
      var focusToolbar = function () {
        toolbar.focus();
      };
      var restoreToolbar = function () {
        toolbar.restoreToolbar();
      };
      var init = function (spec) {
        webapp.set(AndroidWebapp.produce(spec));
      };
      var exit = function () {
        webapp.run(function (w) {
          w.exit();
          Replacing.remove(socket, switchToEdit);
        });
      };
      var updateMode = function (readOnly) {
        CommonRealm.updateMode(socket, switchToEdit, readOnly, alloy.root());
      };
      return {
        system: constant(alloy),
        element: alloy.element,
        init: init,
        exit: exit,
        setToolbarGroups: setToolbarGroups,
        setContextToolbar: setContextToolbar,
        focusToolbar: focusToolbar,
        restoreToolbar: restoreToolbar,
        updateMode: updateMode,
        socket: constant(socket),
        dropup: constant(dropup)
      };
    }

    var input$1 = function (parent, operation) {
      var input = Element$$1.fromTag('input');
      setAll$1(input, {
        opacity: '0',
        position: 'absolute',
        top: '-1000px',
        left: '-1000px'
      });
      append(parent, input);
      focus$2(input);
      operation(input);
      remove(input);
    };
    var CaptureBin = { input: input$1 };

    var refreshInput = function (input) {
      var start = input.dom().selectionStart;
      var end = input.dom().selectionEnd;
      var dir = input.dom().selectionDirection;
      setTimeout(function () {
        input.dom().setSelectionRange(start, end, dir);
        focus$2(input);
      }, 50);
    };
    var refresh = function (winScope) {
      var sel = winScope.getSelection();
      if (sel.rangeCount > 0) {
        var br = sel.getRangeAt(0);
        var r = winScope.document.createRange();
        r.setStart(br.startContainer, br.startOffset);
        r.setEnd(br.endContainer, br.endOffset);
        sel.removeAllRanges();
        sel.addRange(r);
      }
    };
    var CursorRefresh = {
      refreshInput: refreshInput,
      refresh: refresh
    };

    var resume$1 = function (cWin, frame) {
      active().each(function (active$$1) {
        if (!eq(active$$1, frame)) {
          blur$$1(active$$1);
        }
      });
      cWin.focus();
      focus$2(Element$$1.fromDom(cWin.document.body));
      CursorRefresh.refresh(cWin);
    };
    var ResumeEditing$1 = { resume: resume$1 };

    var stubborn = function (outerBody, cWin, page, frame) {
      var toEditing = function () {
        ResumeEditing$1.resume(cWin, frame);
      };
      var toReading = function () {
        CaptureBin.input(outerBody, blur$$1);
      };
      var captureInput = bind$3(page, 'keydown', function (evt) {
        if (!contains([
            'input',
            'textarea'
          ], name(evt.target()))) {
          toEditing();
        }
      });
      var onToolbarTouch = function () {
      };
      var destroy = function () {
        captureInput.unbind();
      };
      return {
        toReading: toReading,
        toEditing: toEditing,
        onToolbarTouch: onToolbarTouch,
        destroy: destroy
      };
    };
    var timid = function (outerBody, cWin, page, frame) {
      var dismissKeyboard = function () {
        blur$$1(frame);
      };
      var onToolbarTouch = function () {
        dismissKeyboard();
      };
      var toReading = function () {
        dismissKeyboard();
      };
      var toEditing = function () {
        ResumeEditing$1.resume(cWin, frame);
      };
      return {
        toReading: toReading,
        toEditing: toEditing,
        onToolbarTouch: onToolbarTouch,
        destroy: noop
      };
    };
    var IosKeyboard = {
      stubborn: stubborn,
      timid: timid
    };

    var initEvents$1 = function (editorApi, iosApi, toolstrip, socket, dropup) {
      var saveSelectionFirst = function () {
        iosApi.run(function (api) {
          api.highlightSelection();
        });
      };
      var refreshIosSelection = function () {
        iosApi.run(function (api) {
          api.refreshSelection();
        });
      };
      var scrollToY = function (yTop, height) {
        var y = yTop - socket.dom().scrollTop;
        iosApi.run(function (api) {
          api.scrollIntoView(y, y + height);
        });
      };
      var scrollToElement = function (target) {
        scrollToY(iosApi, socket);
      };
      var scrollToCursor = function () {
        editorApi.getCursorBox().each(function (box) {
          scrollToY(box.top(), box.height());
        });
      };
      var clearSelection = function () {
        iosApi.run(function (api) {
          api.clearSelection();
        });
      };
      var clearAndRefresh = function () {
        clearSelection();
        refreshThrottle.throttle();
      };
      var refreshView = function () {
        scrollToCursor();
        iosApi.run(function (api) {
          api.syncHeight();
        });
      };
      var reposition = function () {
        var toolbarHeight = get$5(toolstrip);
        iosApi.run(function (api) {
          api.setViewportOffset(toolbarHeight);
        });
        refreshIosSelection();
        refreshView();
      };
      var toEditing = function () {
        iosApi.run(function (api) {
          api.toEditing();
        });
      };
      var toReading = function () {
        iosApi.run(function (api) {
          api.toReading();
        });
      };
      var onToolbarTouch = function (event) {
        iosApi.run(function (api) {
          api.onToolbarTouch(event);
        });
      };
      var tapping = TappingEvent.monitor(editorApi);
      var refreshThrottle = last$3(refreshView, 300);
      var listeners = [
        editorApi.onKeyup(clearAndRefresh),
        editorApi.onNodeChanged(refreshIosSelection),
        editorApi.onDomChanged(refreshThrottle.throttle),
        editorApi.onDomChanged(refreshIosSelection),
        editorApi.onScrollToCursor(function (tinyEvent) {
          tinyEvent.preventDefault();
          refreshThrottle.throttle();
        }),
        editorApi.onScrollToElement(function (event) {
          scrollToElement(event.element());
        }),
        editorApi.onToEditing(toEditing),
        editorApi.onToReading(toReading),
        bind$3(editorApi.doc(), 'touchend', function (touchEvent) {
          if (eq(editorApi.html(), touchEvent.target()) || eq(editorApi.body(), touchEvent.target())) ;
        }),
        bind$3(toolstrip, 'transitionend', function (transitionEvent) {
          if (transitionEvent.raw().propertyName === 'height') {
            reposition();
          }
        }),
        capture$1(toolstrip, 'touchstart', function (touchEvent) {
          saveSelectionFirst();
          onToolbarTouch(touchEvent);
          editorApi.onTouchToolstrip();
        }),
        bind$3(editorApi.body(), 'touchstart', function (evt) {
          clearSelection();
          editorApi.onTouchContent();
          tapping.fireTouchstart(evt);
        }),
        tapping.onTouchmove(),
        tapping.onTouchend(),
        bind$3(editorApi.body(), 'click', function (event) {
          event.kill();
        }),
        bind$3(toolstrip, 'touchmove', function () {
          editorApi.onToolbarScrollStart();
        })
      ];
      var destroy = function () {
        each$1(listeners, function (l) {
          l.unbind();
        });
      };
      return { destroy: destroy };
    };
    var IosEvents = { initEvents: initEvents$1 };

    function FakeSelection (win, frame) {
      var doc = win.document;
      var container = Element$$1.fromTag('div');
      add$2(container, Styles.resolve('unfocused-selections'));
      append(Element$$1.fromDom(doc.documentElement), container);
      var onTouch = bind$3(container, 'touchstart', function (event) {
        event.prevent();
        ResumeEditing$1.resume(win, frame);
        clear();
      });
      var make = function (rectangle) {
        var span = Element$$1.fromTag('span');
        add$3(span, [
          Styles.resolve('layer-editor'),
          Styles.resolve('unfocused-selection')
        ]);
        setAll$1(span, {
          left: rectangle.left() + 'px',
          top: rectangle.top() + 'px',
          width: rectangle.width() + 'px',
          height: rectangle.height() + 'px'
        });
        return span;
      };
      var update = function () {
        clear();
        var rectangles = Rectangles.getRectangles(win);
        var spans = map$1(rectangles, make);
        append$1(container, spans);
      };
      var clear = function () {
        empty(container);
      };
      var destroy = function () {
        onTouch.unbind();
        remove(container);
      };
      var isActive = function () {
        return children(container).length > 0;
      };
      return {
        update: update,
        isActive: isActive,
        destroy: destroy,
        clear: clear
      };
    }

    var nu$7 = function (baseFn) {
      var data = Option.none();
      var callbacks = [];
      var map = function (f) {
        return nu$7(function (nCallback) {
          get(function (data) {
            nCallback(f(data));
          });
        });
      };
      var get = function (nCallback) {
        if (isReady())
          call(nCallback);
        else
          callbacks.push(nCallback);
      };
      var set = function (x) {
        data = Option.some(x);
        run(callbacks);
        callbacks = [];
      };
      var isReady = function () {
        return data.isSome();
      };
      var run = function (cbs) {
        each$1(cbs, call);
      };
      var call = function (cb) {
        data.each(function (x) {
          setTimeout(function () {
            cb(x);
          }, 0);
        });
      };
      baseFn(set);
      return {
        get: get,
        map: map,
        isReady: isReady
      };
    };
    var pure$1 = function (a) {
      return nu$7(function (callback) {
        callback(a);
      });
    };
    var LazyValue = {
      nu: nu$7,
      pure: pure$1
    };

    var bounce = function (f) {
      return function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }
        var me = this;
        setTimeout(function () {
          f.apply(me, args);
        }, 0);
      };
    };

    var nu$8 = function (baseFn) {
      var get = function (callback) {
        baseFn(bounce(callback));
      };
      var map = function (fab) {
        return nu$8(function (callback) {
          get(function (a) {
            var value = fab(a);
            callback(value);
          });
        });
      };
      var bind = function (aFutureB) {
        return nu$8(function (callback) {
          get(function (a) {
            aFutureB(a).get(callback);
          });
        });
      };
      var anonBind = function (futureB) {
        return nu$8(function (callback) {
          get(function (a) {
            futureB.get(callback);
          });
        });
      };
      var toLazy = function () {
        return LazyValue.nu(get);
      };
      var toCached = function () {
        var cache = null;
        return nu$8(function (callback) {
          if (cache === null) {
            cache = toLazy();
          }
          cache.get(callback);
        });
      };
      return {
        map: map,
        bind: bind,
        anonBind: anonBind,
        toLazy: toLazy,
        toCached: toCached,
        get: get
      };
    };
    var pure$2 = function (a) {
      return nu$8(function (callback) {
        callback(a);
      });
    };
    var Future = {
      nu: nu$8,
      pure: pure$2
    };

    var adjust = function (value, destination, amount) {
      if (Math.abs(value - destination) <= amount) {
        return Option.none();
      } else if (value < destination) {
        return Option.some(value + amount);
      } else {
        return Option.some(value - amount);
      }
    };
    var create$7 = function () {
      var interval = null;
      var animate = function (getCurrent, destination, amount, increment, doFinish, rate) {
        var finished = false;
        var finish = function (v) {
          finished = true;
          doFinish(v);
        };
        clearInterval(interval);
        var abort = function (v) {
          clearInterval(interval);
          finish(v);
        };
        interval = setInterval(function () {
          var value = getCurrent();
          adjust(value, destination, amount).fold(function () {
            clearInterval(interval);
            finish(destination);
          }, function (s) {
            increment(s, abort);
            if (!finished) {
              var newValue = getCurrent();
              if (newValue !== s || Math.abs(newValue - destination) > Math.abs(value - destination)) {
                clearInterval(interval);
                finish(destination);
              }
            }
          });
        }, rate);
      };
      return { animate: animate };
    };
    var SmoothAnimation = {
      create: create$7,
      adjust: adjust
    };

    var findDevice = function (deviceWidth, deviceHeight) {
      var devices = [
        {
          width: 320,
          height: 480,
          keyboard: {
            portrait: 300,
            landscape: 240
          }
        },
        {
          width: 320,
          height: 568,
          keyboard: {
            portrait: 300,
            landscape: 240
          }
        },
        {
          width: 375,
          height: 667,
          keyboard: {
            portrait: 305,
            landscape: 240
          }
        },
        {
          width: 414,
          height: 736,
          keyboard: {
            portrait: 320,
            landscape: 240
          }
        },
        {
          width: 768,
          height: 1024,
          keyboard: {
            portrait: 320,
            landscape: 400
          }
        },
        {
          width: 1024,
          height: 1366,
          keyboard: {
            portrait: 380,
            landscape: 460
          }
        }
      ];
      return findMap(devices, function (device) {
        return deviceWidth <= device.width && deviceHeight <= device.height ? Option.some(device.keyboard) : Option.none();
      }).getOr({
        portrait: deviceHeight / 5,
        landscape: deviceWidth / 4
      });
    };
    var Devices = { findDevice: findDevice };

    var softKeyboardLimits = function (outerWindow) {
      return Devices.findDevice(outerWindow.screen.width, outerWindow.screen.height);
    };
    var accountableKeyboardHeight = function (outerWindow) {
      var portrait = Orientation.get(outerWindow).isPortrait();
      var limits = softKeyboardLimits(outerWindow);
      var keyboard = portrait ? limits.portrait : limits.landscape;
      var visualScreenHeight = portrait ? outerWindow.screen.height : outerWindow.screen.width;
      return visualScreenHeight - outerWindow.innerHeight > keyboard ? 0 : keyboard;
    };
    var getGreenzone = function (socket, dropup) {
      var outerWindow = owner(socket).dom().defaultView;
      var viewportHeight = get$5(socket) + get$5(dropup);
      var acc = accountableKeyboardHeight(outerWindow);
      return viewportHeight - acc;
    };
    var updatePadding = function (contentBody, socket, dropup) {
      var greenzoneHeight = getGreenzone(socket, dropup);
      var deltaHeight = get$5(socket) + get$5(dropup) - greenzoneHeight;
      set$3(contentBody, 'padding-bottom', deltaHeight + 'px');
    };
    var DeviceZones = {
      getGreenzone: getGreenzone,
      updatePadding: updatePadding
    };

    var fixture = Adt.generate([
      {
        fixed: [
          'element',
          'property',
          'offsetY'
        ]
      },
      {
        scroller: [
          'element',
          'offsetY'
        ]
      }
    ]);
    var yFixedData = 'data-' + Styles.resolve('position-y-fixed');
    var yFixedProperty = 'data-' + Styles.resolve('y-property');
    var yScrollingData = 'data-' + Styles.resolve('scrolling');
    var windowSizeData = 'data-' + Styles.resolve('last-window-height');
    var getYFixedData = function (element) {
      return DataAttributes.safeParse(element, yFixedData);
    };
    var getYFixedProperty = function (element) {
      return get$1(element, yFixedProperty);
    };
    var getLastWindowSize = function (element) {
      return DataAttributes.safeParse(element, windowSizeData);
    };
    var classifyFixed = function (element, offsetY) {
      var prop = getYFixedProperty(element);
      return fixture.fixed(element, prop, offsetY);
    };
    var classifyScrolling = function (element, offsetY) {
      return fixture.scroller(element, offsetY);
    };
    var classify = function (element) {
      var offsetY = getYFixedData(element);
      var classifier = get$1(element, yScrollingData) === 'true' ? classifyScrolling : classifyFixed;
      return classifier(element, offsetY);
    };
    var findFixtures = function (container) {
      var candidates = descendants$1(container, '[' + yFixedData + ']');
      return map$1(candidates, classify);
    };
    var takeoverToolbar = function (toolbar) {
      var oldToolbarStyle = get$1(toolbar, 'style');
      setAll$1(toolbar, {
        position: 'absolute',
        top: '0px'
      });
      set(toolbar, yFixedData, '0px');
      set(toolbar, yFixedProperty, 'top');
      var restore = function () {
        set(toolbar, 'style', oldToolbarStyle || '');
        remove$1(toolbar, yFixedData);
        remove$1(toolbar, yFixedProperty);
      };
      return { restore: restore };
    };
    var takeoverViewport = function (toolbarHeight, height, viewport) {
      var oldViewportStyle = get$1(viewport, 'style');
      Scrollable.register(viewport);
      setAll$1(viewport, {
        position: 'absolute',
        height: height + 'px',
        width: '100%',
        top: toolbarHeight + 'px'
      });
      set(viewport, yFixedData, toolbarHeight + 'px');
      set(viewport, yScrollingData, 'true');
      set(viewport, yFixedProperty, 'top');
      var restore = function () {
        Scrollable.deregister(viewport);
        set(viewport, 'style', oldViewportStyle || '');
        remove$1(viewport, yFixedData);
        remove$1(viewport, yScrollingData);
        remove$1(viewport, yFixedProperty);
      };
      return { restore: restore };
    };
    var takeoverDropup = function (dropup, toolbarHeight, viewportHeight) {
      var oldDropupStyle = get$1(dropup, 'style');
      setAll$1(dropup, {
        position: 'absolute',
        bottom: '0px'
      });
      set(dropup, yFixedData, '0px');
      set(dropup, yFixedProperty, 'bottom');
      var restore = function () {
        set(dropup, 'style', oldDropupStyle || '');
        remove$1(dropup, yFixedData);
        remove$1(dropup, yFixedProperty);
      };
      return { restore: restore };
    };
    var deriveViewportHeight = function (viewport, toolbarHeight, dropupHeight) {
      var outerWindow = owner(viewport).dom().defaultView;
      var winH = outerWindow.innerHeight;
      set(viewport, windowSizeData, winH + 'px');
      return winH - toolbarHeight - dropupHeight;
    };
    var takeover$1 = function (viewport, contentBody, toolbar, dropup) {
      var outerWindow = owner(viewport).dom().defaultView;
      var toolbarSetup = takeoverToolbar(toolbar);
      var toolbarHeight = get$5(toolbar);
      var dropupHeight = get$5(dropup);
      var viewportHeight = deriveViewportHeight(viewport, toolbarHeight, dropupHeight);
      var viewportSetup = takeoverViewport(toolbarHeight, viewportHeight, viewport);
      var dropupSetup = takeoverDropup(dropup, toolbarHeight, viewportHeight);
      var isActive = true;
      var restore = function () {
        isActive = false;
        toolbarSetup.restore();
        viewportSetup.restore();
        dropupSetup.restore();
      };
      var isExpanding = function () {
        var currentWinHeight = outerWindow.innerHeight;
        var lastWinHeight = getLastWindowSize(viewport);
        return currentWinHeight > lastWinHeight;
      };
      var refresh = function () {
        if (isActive) {
          var newToolbarHeight = get$5(toolbar);
          var dropupHeight_1 = get$5(dropup);
          var newHeight = deriveViewportHeight(viewport, newToolbarHeight, dropupHeight_1);
          set(viewport, yFixedData, newToolbarHeight + 'px');
          set$3(viewport, 'height', newHeight + 'px');
          set$3(dropup, 'bottom', -(newToolbarHeight + newHeight + dropupHeight_1) + 'px');
          DeviceZones.updatePadding(contentBody, viewport, dropup);
        }
      };
      var setViewportOffset = function (newYOffset) {
        var offsetPx = newYOffset + 'px';
        set(viewport, yFixedData, offsetPx);
        refresh();
      };
      DeviceZones.updatePadding(contentBody, viewport, dropup);
      return {
        setViewportOffset: setViewportOffset,
        isExpanding: isExpanding,
        isShrinking: not(isExpanding),
        refresh: refresh,
        restore: restore
      };
    };
    var IosViewport = {
      findFixtures: findFixtures,
      takeover: takeover$1,
      getYFixedData: getYFixedData
    };

    var animator = SmoothAnimation.create();
    var ANIMATION_STEP = 15;
    var NUM_TOP_ANIMATION_FRAMES = 10;
    var ANIMATION_RATE = 10;
    var lastScroll = 'data-' + Styles.resolve('last-scroll-top');
    var getTop = function (element) {
      var raw = getRaw(element, 'top').getOr('0');
      return parseInt(raw, 10);
    };
    var getScrollTop = function (element) {
      return parseInt(element.dom().scrollTop, 10);
    };
    var moveScrollAndTop = function (element, destination, finalTop) {
      return Future.nu(function (callback) {
        var getCurrent = curry(getScrollTop, element);
        var update = function (newScroll) {
          element.dom().scrollTop = newScroll;
          set$3(element, 'top', getTop(element) + ANIMATION_STEP + 'px');
        };
        var finish = function () {
          element.dom().scrollTop = destination;
          set$3(element, 'top', finalTop + 'px');
          callback(destination);
        };
        animator.animate(getCurrent, destination, ANIMATION_STEP, update, finish, ANIMATION_RATE);
      });
    };
    var moveOnlyScroll = function (element, destination) {
      return Future.nu(function (callback) {
        var getCurrent = curry(getScrollTop, element);
        set(element, lastScroll, getCurrent());
        var update = function (newScroll, abort) {
          var previous = DataAttributes.safeParse(element, lastScroll);
          if (previous !== element.dom().scrollTop) {
            abort(element.dom().scrollTop);
          } else {
            element.dom().scrollTop = newScroll;
            set(element, lastScroll, newScroll);
          }
        };
        var finish = function () {
          element.dom().scrollTop = destination;
          set(element, lastScroll, destination);
          callback(destination);
        };
        var distance = Math.abs(destination - getCurrent());
        var step = Math.ceil(distance / NUM_TOP_ANIMATION_FRAMES);
        animator.animate(getCurrent, destination, step, update, finish, ANIMATION_RATE);
      });
    };
    var moveOnlyTop = function (element, destination) {
      return Future.nu(function (callback) {
        var getCurrent = curry(getTop, element);
        var update = function (newTop) {
          set$3(element, 'top', newTop + 'px');
        };
        var finish = function () {
          update(destination);
          callback(destination);
        };
        var distance = Math.abs(destination - getCurrent());
        var step = Math.ceil(distance / NUM_TOP_ANIMATION_FRAMES);
        animator.animate(getCurrent, destination, step, update, finish, ANIMATION_RATE);
      });
    };
    var updateTop = function (element, amount) {
      var newTop = amount + IosViewport.getYFixedData(element) + 'px';
      set$3(element, 'top', newTop);
    };
    var moveWindowScroll = function (toolbar, viewport, destY) {
      var outerWindow = owner(toolbar).dom().defaultView;
      return Future.nu(function (callback) {
        updateTop(toolbar, destY);
        updateTop(viewport, destY);
        outerWindow.scrollTo(0, destY);
        callback(destY);
      });
    };
    var IosScrolling = {
      moveScrollAndTop: moveScrollAndTop,
      moveOnlyScroll: moveOnlyScroll,
      moveOnlyTop: moveOnlyTop,
      moveWindowScroll: moveWindowScroll
    };

    function BackgroundActivity (doAction) {
      var action = Cell(LazyValue.pure({}));
      var start = function (value) {
        var future = LazyValue.nu(function (callback) {
          return doAction(value).get(callback);
        });
        action.set(future);
      };
      var idle = function (g) {
        action.get().get(function () {
          g();
        });
      };
      return {
        start: start,
        idle: idle
      };
    }

    var scrollIntoView = function (cWin, socket, dropup, top, bottom) {
      var greenzone = DeviceZones.getGreenzone(socket, dropup);
      var refreshCursor = curry(CursorRefresh.refresh, cWin);
      if (top > greenzone || bottom > greenzone) {
        IosScrolling.moveOnlyScroll(socket, socket.dom().scrollTop - greenzone + bottom).get(refreshCursor);
      } else if (top < 0) {
        IosScrolling.moveOnlyScroll(socket, socket.dom().scrollTop + top).get(refreshCursor);
      }
    };
    var Greenzone = { scrollIntoView: scrollIntoView };

    var par = function (asyncValues, nu) {
      return nu(function (callback) {
        var r = [];
        var count = 0;
        var cb = function (i) {
          return function (value) {
            r[i] = value;
            count++;
            if (count >= asyncValues.length) {
              callback(r);
            }
          };
        };
        if (asyncValues.length === 0) {
          callback([]);
        } else {
          each$1(asyncValues, function (asyncValue, i) {
            asyncValue.get(cb(i));
          });
        }
      });
    };

    var par$1 = function (futures) {
      return par(futures, Future.nu);
    };

    var updateFixed = function (element, property, winY, offsetY) {
      var destination = winY + offsetY;
      set$3(element, property, destination + 'px');
      return Future.pure(offsetY);
    };
    var updateScrollingFixed = function (element, winY, offsetY) {
      var destTop = winY + offsetY;
      var oldProp = getRaw(element, 'top').getOr(offsetY);
      var delta = destTop - parseInt(oldProp, 10);
      var destScroll = element.dom().scrollTop + delta;
      return IosScrolling.moveScrollAndTop(element, destScroll, destTop);
    };
    var updateFixture = function (fixture, winY) {
      return fixture.fold(function (element, property, offsetY) {
        return updateFixed(element, property, winY, offsetY);
      }, function (element, offsetY) {
        return updateScrollingFixed(element, winY, offsetY);
      });
    };
    var updatePositions = function (container, winY) {
      var fixtures = IosViewport.findFixtures(container);
      var updates = map$1(fixtures, function (fixture) {
        return updateFixture(fixture, winY);
      });
      return par$1(updates);
    };
    var IosUpdates = { updatePositions: updatePositions };

    var VIEW_MARGIN = 5;
    var register$2 = function (toolstrip, socket, container, outerWindow, structure, cWin) {
      var scroller = BackgroundActivity(function (y) {
        return IosScrolling.moveWindowScroll(toolstrip, socket, y);
      });
      var scrollBounds = function () {
        var rects = Rectangles.getRectangles(cWin);
        return Option.from(rects[0]).bind(function (rect) {
          var viewTop = rect.top() - socket.dom().scrollTop;
          var outside = viewTop > outerWindow.innerHeight + VIEW_MARGIN || viewTop < -VIEW_MARGIN;
          return outside ? Option.some({
            top: constant(viewTop),
            bottom: constant(viewTop + rect.height())
          }) : Option.none();
        });
      };
      var scrollThrottle = last$3(function () {
        scroller.idle(function () {
          IosUpdates.updatePositions(container, outerWindow.pageYOffset).get(function () {
            var extraScroll = scrollBounds();
            extraScroll.each(function (extra) {
              socket.dom().scrollTop = socket.dom().scrollTop + extra.top();
            });
            scroller.start(0);
            structure.refresh();
          });
        });
      }, 1000);
      var onScroll = bind$3(Element$$1.fromDom(outerWindow), 'scroll', function () {
        if (outerWindow.pageYOffset < 0) {
          return;
        }
        scrollThrottle.throttle();
      });
      IosUpdates.updatePositions(container, outerWindow.pageYOffset).get(identity);
      return { unbind: onScroll.unbind };
    };
    var setup$3 = function (bag) {
      var cWin = bag.cWin();
      var ceBody = bag.ceBody();
      var socket = bag.socket();
      var toolstrip = bag.toolstrip();
      var toolbar = bag.toolbar();
      var contentElement = bag.contentElement();
      var keyboardType = bag.keyboardType();
      var outerWindow = bag.outerWindow();
      var dropup = bag.dropup();
      var structure = IosViewport.takeover(socket, ceBody, toolstrip, dropup);
      var keyboardModel = keyboardType(bag.outerBody(), cWin, body(), contentElement, toolstrip, toolbar);
      var toEditing = function () {
        keyboardModel.toEditing();
        clearSelection();
      };
      var toReading = function () {
        keyboardModel.toReading();
      };
      var onToolbarTouch = function (event) {
        keyboardModel.onToolbarTouch(event);
      };
      var onOrientation = Orientation.onChange(outerWindow, {
        onChange: noop,
        onReady: structure.refresh
      });
      onOrientation.onAdjustment(function () {
        structure.refresh();
      });
      var onResize = bind$3(Element$$1.fromDom(outerWindow), 'resize', function () {
        if (structure.isExpanding()) {
          structure.refresh();
        }
      });
      var onScroll = register$2(toolstrip, socket, bag.outerBody(), outerWindow, structure, cWin);
      var unfocusedSelection = FakeSelection(cWin, contentElement);
      var refreshSelection = function () {
        if (unfocusedSelection.isActive()) {
          unfocusedSelection.update();
        }
      };
      var highlightSelection = function () {
        unfocusedSelection.update();
      };
      var clearSelection = function () {
        unfocusedSelection.clear();
      };
      var scrollIntoView = function (top, bottom) {
        Greenzone.scrollIntoView(cWin, socket, dropup, top, bottom);
      };
      var syncHeight = function () {
        set$3(contentElement, 'height', contentElement.dom().contentWindow.document.body.scrollHeight + 'px');
      };
      var setViewportOffset = function (newYOffset) {
        structure.setViewportOffset(newYOffset);
        IosScrolling.moveOnlyTop(socket, newYOffset).get(identity);
      };
      var destroy = function () {
        structure.restore();
        onOrientation.destroy();
        onScroll.unbind();
        onResize.unbind();
        keyboardModel.destroy();
        unfocusedSelection.destroy();
        CaptureBin.input(body(), blur$$1);
      };
      return {
        toEditing: toEditing,
        toReading: toReading,
        onToolbarTouch: onToolbarTouch,
        refreshSelection: refreshSelection,
        clearSelection: clearSelection,
        highlightSelection: highlightSelection,
        scrollIntoView: scrollIntoView,
        updateToolbarPadding: noop,
        setViewportOffset: setViewportOffset,
        syncHeight: syncHeight,
        refreshStructure: structure.refresh,
        destroy: destroy
      };
    };
    var IosSetup = { setup: setup$3 };

    var create$8 = function (platform, mask) {
      var meta = MetaViewport.tag();
      var priorState = value$3();
      var scrollEvents = value$3();
      var iosApi = api$2();
      var iosEvents = api$2();
      var enter = function () {
        mask.hide();
        var doc = Element$$1.fromDom(document);
        PlatformEditor.getActiveApi(platform.editor).each(function (editorApi) {
          priorState.set({
            socketHeight: getRaw(platform.socket, 'height'),
            iframeHeight: getRaw(editorApi.frame(), 'height'),
            outerScroll: document.body.scrollTop
          });
          scrollEvents.set({ exclusives: Scrollables.exclusive(doc, '.' + Scrollable.scrollable()) });
          add$2(platform.container, Styles.resolve('fullscreen-maximized'));
          Thor.clobberStyles(platform.container, editorApi.body());
          meta.maximize();
          set$3(platform.socket, 'overflow', 'scroll');
          set$3(platform.socket, '-webkit-overflow-scrolling', 'touch');
          focus$2(editorApi.body());
          var setupBag = MixedBag([
            'cWin',
            'ceBody',
            'socket',
            'toolstrip',
            'toolbar',
            'dropup',
            'contentElement',
            'cursor',
            'keyboardType',
            'isScrolling',
            'outerWindow',
            'outerBody'
          ], []);
          iosApi.set(IosSetup.setup(setupBag({
            cWin: editorApi.win(),
            ceBody: editorApi.body(),
            socket: platform.socket,
            toolstrip: platform.toolstrip,
            toolbar: platform.toolbar,
            dropup: platform.dropup.element(),
            contentElement: editorApi.frame(),
            cursor: noop,
            outerBody: platform.body,
            outerWindow: platform.win,
            keyboardType: IosKeyboard.stubborn,
            isScrolling: function () {
              var scrollValue = scrollEvents;
              return scrollValue.get().exists(function (s) {
                return s.socket.isScrolling();
              });
            }
          })));
          iosApi.run(function (api) {
            api.syncHeight();
          });
          iosEvents.set(IosEvents.initEvents(editorApi, iosApi, platform.toolstrip, platform.socket, platform.dropup));
        });
      };
      var exit = function () {
        meta.restore();
        iosEvents.clear();
        iosApi.clear();
        mask.show();
        priorState.on(function (s) {
          s.socketHeight.each(function (h) {
            set$3(platform.socket, 'height', h);
          });
          s.iframeHeight.each(function (h) {
            set$3(platform.editor.getFrame(), 'height', h);
          });
          document.body.scrollTop = s.scrollTop;
        });
        priorState.clear();
        scrollEvents.on(function (s) {
          s.exclusives.unbind();
        });
        scrollEvents.clear();
        remove$4(platform.container, Styles.resolve('fullscreen-maximized'));
        Thor.restoreStyles();
        Scrollable.deregister(platform.toolbar);
        remove$5(platform.socket, 'overflow');
        remove$5(platform.socket, '-webkit-overflow-scrolling');
        blur$$1(platform.editor.getFrame());
        PlatformEditor.getActiveApi(platform.editor).each(function (editorApi) {
          editorApi.clearSelection();
        });
      };
      var refreshStructure = function () {
        iosApi.run(function (api) {
          api.refreshStructure();
        });
      };
      return {
        enter: enter,
        refreshStructure: refreshStructure,
        exit: exit
      };
    };
    var IosMode = { create: create$8 };

    var produce$1 = function (raw) {
      var mobile = asRawOrDie('Getting IosWebapp schema', MobileSchema, raw);
      set$3(mobile.toolstrip, 'width', '100%');
      set$3(mobile.container, 'position', 'relative');
      var onView = function () {
        mobile.setReadOnly(mobile.readOnlyOnInit());
        mode.enter();
      };
      var mask = build$1(TapToEditMask.sketch(onView, mobile.translate));
      mobile.alloy.add(mask);
      var maskApi = {
        show: function () {
          mobile.alloy.add(mask);
        },
        hide: function () {
          mobile.alloy.remove(mask);
        }
      };
      var mode = IosMode.create(mobile, maskApi);
      return {
        setReadOnly: mobile.setReadOnly,
        refreshStructure: mode.refreshStructure,
        enter: mode.enter,
        exit: mode.exit,
        destroy: noop
      };
    };
    var IosWebapp = { produce: produce$1 };

    function IosRealm (scrollIntoView) {
      var alloy = OuterContainer({ classes: [Styles.resolve('ios-container')] });
      var toolbar = ScrollingToolbar();
      var webapp = api$2();
      var switchToEdit = CommonRealm.makeEditSwitch(webapp);
      var socket = CommonRealm.makeSocket();
      var dropup = build$2(function () {
        webapp.run(function (w) {
          w.refreshStructure();
        });
      }, scrollIntoView);
      alloy.add(toolbar.wrapper());
      alloy.add(socket);
      alloy.add(dropup.component());
      var setToolbarGroups = function (rawGroups) {
        var groups = toolbar.createGroups(rawGroups);
        toolbar.setGroups(groups);
      };
      var setContextToolbar = function (rawGroups) {
        var groups = toolbar.createGroups(rawGroups);
        toolbar.setContextToolbar(groups);
      };
      var focusToolbar = function () {
        toolbar.focus();
      };
      var restoreToolbar = function () {
        toolbar.restoreToolbar();
      };
      var init = function (spec) {
        webapp.set(IosWebapp.produce(spec));
      };
      var exit = function () {
        webapp.run(function (w) {
          Replacing.remove(socket, switchToEdit);
          w.exit();
        });
      };
      var updateMode = function (readOnly) {
        CommonRealm.updateMode(socket, switchToEdit, readOnly, alloy.root());
      };
      return {
        system: constant(alloy),
        element: alloy.element,
        init: init,
        exit: exit,
        setToolbarGroups: setToolbarGroups,
        setContextToolbar: setContextToolbar,
        focusToolbar: focusToolbar,
        restoreToolbar: restoreToolbar,
        updateMode: updateMode,
        socket: constant(socket),
        dropup: constant(dropup)
      };
    }

    var global$2 = tinymce.util.Tools.resolve('tinymce.EditorManager');

    var derive$3 = function (editor) {
      var base = readOptFrom$1(editor.settings, 'skin_url').fold(function () {
        return global$2.baseURL + '/skins/ui/oxide';
      }, function (url) {
        return url;
      });
      return {
        content: base + '/content.mobile.min.css',
        ui: base + '/skin.mobile.min.css'
      };
    };
    var CssUrls = { derive: derive$3 };

    var fontSizes = [
      'x-small',
      'small',
      'medium',
      'large',
      'x-large'
    ];
    var fireChange = function (realm, command, state) {
      realm.system().broadcastOn([TinyChannels.formatChanged()], {
        command: command,
        state: state
      });
    };
    var init$6 = function (realm, editor) {
      var allFormats = keys(editor.formatter.get());
      each$1(allFormats, function (command) {
        editor.formatter.formatChanged(command, function (state) {
          fireChange(realm, command, state);
        });
      });
      each$1([
        'ul',
        'ol'
      ], function (command) {
        editor.selection.selectorChanged(command, function (state, data) {
          fireChange(realm, command, state);
        });
      });
    };
    var FormatChangers = {
      init: init$6,
      fontSizes: constant(fontSizes)
    };

    var fireSkinLoaded = function (editor) {
      var done = function () {
        editor._skinLoaded = true;
        editor.fire('SkinLoaded');
      };
      return function () {
        if (editor.initialized) {
          done();
        } else {
          editor.on('init', done);
        }
      };
    };
    var SkinLoaded = { fireSkinLoaded: fireSkinLoaded };

    var READING = constant('toReading');
    var EDITING = constant('toEditing');
    var renderMobileTheme = function (editor) {
      var renderUI = function () {
        var targetNode = editor.getElement();
        var cssUrls = CssUrls.derive(editor);
        if (isSkinDisabled(editor) === false) {
          editor.contentCSS.push(cssUrls.content);
          global.DOM.styleSheetLoader.load(cssUrls.ui, SkinLoaded.fireSkinLoaded(editor));
        } else {
          SkinLoaded.fireSkinLoaded(editor)();
        }
        var doScrollIntoView = function () {
          editor.fire('scrollIntoView');
        };
        var wrapper = Element$$1.fromTag('div');
        var realm = PlatformDetection$1.detect().os.isAndroid() ? AndroidRealm(doScrollIntoView) : IosRealm(doScrollIntoView);
        var original = Element$$1.fromDom(targetNode);
        after(original, wrapper);
        attachSystem(wrapper, realm.system());
        var findFocusIn = function (elem) {
          return search(elem).bind(function (focused) {
            return realm.system().getByDom(focused).toOption();
          });
        };
        var outerWindow = targetNode.ownerDocument.defaultView;
        var orientation = Orientation.onChange(outerWindow, {
          onChange: function () {
            var alloy = realm.system();
            alloy.broadcastOn([TinyChannels.orientationChanged()], { width: Orientation.getActualWidth(outerWindow) });
          },
          onReady: noop
        });
        var setReadOnly = function (dynamicGroup, readOnlyGroups, mainGroups, ro) {
          if (ro === false) {
            editor.selection.collapse();
          }
          var toolbars = configureToolbar(dynamicGroup, readOnlyGroups, mainGroups);
          realm.setToolbarGroups(ro === true ? toolbars.readOnly : toolbars.main);
          editor.setMode(ro === true ? 'readonly' : 'design');
          editor.fire(ro === true ? READING() : EDITING());
          realm.updateMode(ro);
        };
        var configureToolbar = function (dynamicGroup, readOnlyGroups, mainGroups) {
          var dynamic = dynamicGroup.get();
          var toolbars = {
            readOnly: dynamic.backToMask.concat(readOnlyGroups.get()),
            main: dynamic.backToMask.concat(mainGroups.get())
          };
          return toolbars;
        };
        var bindHandler = function (label, handler) {
          editor.on(label, handler);
          return {
            unbind: function () {
              editor.off(label);
            }
          };
        };
        editor.on('init', function () {
          realm.init({
            editor: {
              getFrame: function () {
                return Element$$1.fromDom(editor.contentAreaContainer.querySelector('iframe'));
              },
              onDomChanged: function () {
                return { unbind: noop };
              },
              onToReading: function (handler) {
                return bindHandler(READING(), handler);
              },
              onToEditing: function (handler) {
                return bindHandler(EDITING(), handler);
              },
              onScrollToCursor: function (handler) {
                editor.on('scrollIntoView', function (tinyEvent) {
                  handler(tinyEvent);
                });
                var unbind = function () {
                  editor.off('scrollIntoView');
                  orientation.destroy();
                };
                return { unbind: unbind };
              },
              onTouchToolstrip: function () {
                hideDropup();
              },
              onTouchContent: function () {
                var toolbar = Element$$1.fromDom(editor.editorContainer.querySelector('.' + Styles.resolve('toolbar')));
                findFocusIn(toolbar).each(emitExecute);
                realm.restoreToolbar();
                hideDropup();
              },
              onTapContent: function (evt) {
                var target = evt.target();
                if (name(target) === 'img') {
                  editor.selection.select(target.dom());
                  evt.kill();
                } else if (name(target) === 'a') {
                  var component = realm.system().getByDom(Element$$1.fromDom(editor.editorContainer));
                  component.each(function (container) {
                    if (Swapping.isAlpha(container)) {
                      TinyCodeDupe.openLink(target.dom());
                    }
                  });
                }
              }
            },
            container: Element$$1.fromDom(editor.editorContainer),
            socket: Element$$1.fromDom(editor.contentAreaContainer),
            toolstrip: Element$$1.fromDom(editor.editorContainer.querySelector('.' + Styles.resolve('toolstrip'))),
            toolbar: Element$$1.fromDom(editor.editorContainer.querySelector('.' + Styles.resolve('toolbar'))),
            dropup: realm.dropup(),
            alloy: realm.system(),
            translate: noop,
            setReadOnly: function (ro) {
              setReadOnly(dynamicGroup, readOnlyGroups, mainGroups, ro);
            },
            readOnlyOnInit: function () {
              return readOnlyOnInit(editor);
            }
          });
          var hideDropup = function () {
            realm.dropup().disappear(function () {
              realm.system().broadcastOn([TinyChannels.dropupDismissed()], {});
            });
          };
          var backToMaskGroup = {
            label: 'The first group',
            scrollable: false,
            items: [Buttons.forToolbar('back', function () {
                editor.selection.collapse();
                realm.exit();
              }, {}, editor)]
          };
          var backToReadOnlyGroup = {
            label: 'Back to read only',
            scrollable: false,
            items: [Buttons.forToolbar('readonly-back', function () {
                setReadOnly(dynamicGroup, readOnlyGroups, mainGroups, true);
              }, {}, editor)]
          };
          var readOnlyGroup = {
            label: 'The read only mode group',
            scrollable: true,
            items: []
          };
          var features = Features.setup(realm, editor);
          var items = Features.detect(editor.settings, features);
          var actionGroup = {
            label: 'the action group',
            scrollable: true,
            items: items
          };
          var extraGroup = {
            label: 'The extra group',
            scrollable: false,
            items: []
          };
          var mainGroups = Cell([
            actionGroup,
            extraGroup
          ]);
          var readOnlyGroups = Cell([
            readOnlyGroup,
            extraGroup
          ]);
          var dynamicGroup = Cell({
            backToMask: [backToMaskGroup],
            backToReadOnly: [backToReadOnlyGroup]
          });
          FormatChangers.init(realm, editor);
        });
        return {
          iframeContainer: realm.socket().element().dom(),
          editorContainer: realm.element().dom()
        };
      };
      return {
        getNotificationManagerImpl: function () {
          return {
            open: constant({
              progressBar: { value: noop },
              close: noop
            }),
            close: noop,
            reposition: noop,
            getArgs: identity
          };
        },
        renderUI: renderUI
      };
    };
    global$1.add('mobile', renderMobileTheme);
    function Theme () {
    }

    exports.renderMobileTheme = renderMobileTheme;
    exports.default = Theme;

    return exports;

}({}));
})();
