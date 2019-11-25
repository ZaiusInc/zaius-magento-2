if (document.getElementById('bwiPHWlgeUcE')) {
    define(['jquery', 'underscore', 'domReady!'], function ($, _) {
        'use strict';
        return function (config) {

            (function () {
                var e = document.createElement("script");
                e.type = "text/javascript";
                e.async = true;
                e.src = ("https:" === document.location.protocol ? "https://" : "http://") + config.js;
                var t = document.getElementsByTagName("script")[0];
                t.parentNode.insertBefore(e, t)
            })();

            var zaius = window['zaius'] || (window['zaius'] = []);
            zaius.methods = [
                "initialize",
                "onload",
                "event",
                "entity",
                "identify",
                "anonymize"
            ];
            zaius.factory = function (e) {
                return function () {
                    var t = Array.prototype.slice.call(arguments);
                    t.unshift(e);
                    zaius.push(t);
                    return zaius
                }
            };
            for (var i = 0; i < zaius.methods.length; i++) {
                var method = zaius.methods[i];
                zaius[method] = zaius.factory(method)
            }

            _.each(config.events, function (event) {
                if (event.type === 'anonymize') {
                    zaius.anonymize();
                } else {
                    if (("data" in event) && ("data_source_details" in event.data)) {
                        event.data.data_source_details += "Sent via Zaius web SDK;";
                    }
                    zaius.event(event.type, event.data);
                }
            });
        };
    });
} else {
    console.warn("Blocking Ads: Zaius not loaded!");
}